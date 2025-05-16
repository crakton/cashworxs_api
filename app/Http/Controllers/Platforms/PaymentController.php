<?php

namespace App\Http\Controllers\Platforms;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Transaction;
use DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends BaseController
{
    protected $apiBaseUrl;
    protected $bearerToken;

    private function authenticate()
    {
        try {
            $response = Http::post($this->apiBaseUrl . '/authenticate', [
                'access_key' => '7AwUxeq96x49f542',
                'access_secret' => 'DekEULYFNtMVrXll'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['access_token'];
            }

            Log::error('Authentication failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Authentication error: ' . $e->getMessage());
            return null;
        }
    }

    private function recordTransaction($type, $user_id, $fullname, $amount, $status, $metadata = [])
    {
        return Transaction::create([
            'user_id' => $user_id,
            'fullname' => $fullname,
            'transaction_type' => $type,
            'transaction_name' => $type === 'invoice' ? 'Invoice Created' : 'Payment Processed',
            'transaction_amount' => $amount,
            'transaction_status' => $status,
            'transaction_metadata' => $metadata
        ]);
    }


    public function __construct()
    {
        $this->apiBaseUrl = 'https://server.inteliworxtest.com';
        $this->bearerToken = $this->authenticate();
    }

    /**
     * Create a new invoice using Cashworx API
     */
    public function createInvoice(Request $request)
    {
        try {
            // Validate the incoming request including our custom fields
            $validated = $request->validate([
                'tdate' => 'required|string',
                'note' => 'nullable|string',
                'amount' => 'required|numeric',
                'c_code' => 'required|string',
                'c_name' => 'required|string',
                'c_address' => 'required|string',
                'c_phone' => 'required|string',
                'c_number' => 'required|string',
                'c_email' => 'required|email',
                'items' => 'required|array',
                'items.*.i_name' => 'required|string',
                'items.*.i_code' => 'required|string',
                'items.*.i_amount' => 'required|numeric',
                'items.*.note' => 'nullable|string',
                'items.*.i_type' => 'nullable|string',
                'items.*.i_org' => 'nullable|string',
                // Custom fields validation
                'year_of_assessment' => 'nullable|integer',
                'irs_id' => 'nullable|string',
                'irs_name' => 'nullable|string',
                'tax_type' => 'nullable|string',
                'fullname' => 'nullable|string',
                'custom_fields' => 'nullable|array',
            ]);

            // Extract only the fields needed for the API request
            $apiRequestData = array_filter($validated, function ($key) {
                return !in_array($key, [
                    'year_of_assessment',
                    'irs_id',
                    'irs_name',
                    'tax_type',
                    'fullname',
                    'custom_fields'
                ]);
            }, ARRAY_FILTER_USE_KEY);

            // Store custom fields separately
            $customFields = array_filter($validated, function ($key) {
                return in_array($key, [
                    'year_of_assessment',
                    'irs_id',
                    'irs_name',
                    'tax_type',
                    'fullname',
                    'custom_fields'
                ]);
            }, ARRAY_FILTER_USE_KEY);

            // Make API request to Cashworx payment gateway with filtered data
            $response = Http::withToken($this->bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiBaseUrl . '/invoices', $apiRequestData);

            // Handle the response
            if ($response->successful()) {
                $responseData = $response->json();

                // Log the raw response to help with debugging
                Log::debug('API Response: ' . json_encode($responseData));

                // Extract the invoice data from the response
                $invoiceData = isset($responseData['invoice']) ? $responseData['invoice'] : $responseData;

                // Get user_id from the authenticated user
                $user_id = $request->user()->id;

                // Merge custom fields with invoice data
                $invoiceDataWithCustomFields = array_merge($invoiceData, $customFields);

                // Store invoice in local database with debugging
                try {
                    $invoice = $this->storeInvoiceInDatabase($invoiceDataWithCustomFields, $user_id);

                    return $this->sendResponse(
                        $invoice,
                        'Invoice created successfully',
                        201
                    );
                } catch (\Exception $dbError) {
                    Log::error('Database error: ' . $dbError->getMessage());
                    Log::error('Invoice data: ' . json_encode($invoiceDataWithCustomFields));
                    throw $dbError;
                }
            }

            return $this->sendError(
                $response->json(),
                'Failed to create invoice',
                $response->status()
            );
        } catch (\Exception $e) {
            Log::error('Invoice creation error: ' . $e->getMessage());
            return $this->sendError('An error occurred while creating the invoice', $e->getMessage(), 500);
        }
    }

    /**
     * Get all invoices (with optional pagination)
     */
    public function getAllInvoices(Request $request)
    {
        $paginated = $request->query('paginated', false);
        $endpoint = $paginated ? '/invoices/paginate' : '/invoices';

        try {
            $response = Http::withToken($this->bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->get($this->apiBaseUrl . $endpoint);

            if ($response->successful()) {
                return $this->sendResponse($response->json());
            }

            return $this->sendError('Failed to retrieve invoices', $response->json(), $response->status());
        } catch (\Exception $e) {
            Log::error('Get invoices error: ' . $e->getMessage());
            return $this->sendError('An error occurred while retrieving invoices', $e->getMessage(), 500);
        }
    }

    /**
     * Store invoice data in the local database
     */
    private function storeInvoiceInDatabase($invoiceData, $user_id)
    {
        // Generate a UUID for the ID if it's not present in the API response
        // This ensures we always have a value for the required ID field
        if (!isset($invoiceData['id']) || empty($invoiceData['id'])) {
            $invoiceData['id'] = (string) \Illuminate\Support\Str::uuid();
        } else {
            $invoiceData['id'] = (string) $invoiceData['id'];
        }

        // Log the data being inserted
        Log::debug('Creating invoice with data: ' . json_encode($invoiceData));

        // Prepare custom_fields data if it's not already in the right format
        if (isset($invoiceData['custom_fields']) && !is_array($invoiceData['custom_fields'])) {
            $invoiceData['custom_fields'] = json_decode($invoiceData['custom_fields'], true) ?? [];
        }

        // Create the invoice with explicit ID field and user_id
        $invoice = new Invoice();
        $invoice->id = $invoiceData['id'];
        $invoice->user_id = $user_id;
        $invoice->invoice_number = $invoiceData['invoice_number'];
        $invoice->mda_id = $invoiceData['mda_id'];
        $invoice->mda_code = $invoiceData['mda_code'];
        $invoice->tdate = $invoiceData['tdate'];
        $invoice->amount = $invoiceData['amount'];
        $invoice->c_code = $invoiceData['c_code'];
        $invoice->c_name = $invoiceData['c_name'];
        $invoice->c_address = $invoiceData['c_address'];
        $invoice->c_phone = $invoiceData['c_phone'];
        $invoice->c_number = $invoiceData['c_number'];
        $invoice->c_email = $invoiceData['c_email'];
        $invoice->client_invoice_number = $invoiceData['client_invoice_number'] ?? null;
        $invoice->status = $invoiceData['status'];
        $invoice->note = $invoiceData['note'] ?? null;
        $invoice->log_time = $invoiceData['log_time'];

        // Add custom fields
        $invoice->year_of_assessment = $invoiceData['year_of_assessment'] ?? null;
        $invoice->irs_id = $invoiceData['irs_id'] ?? null;
        $invoice->irs_name = $invoiceData['irs_name'] ?? null;
        $invoice->tax_type = $invoiceData['tax_type'] ?? null;
        $invoice->fullname = $invoiceData['fullname'] ?? null;
        $invoice->custom_fields = $invoiceData['custom_fields'] ?? null;

        $invoice->save();

        // If you need to store invoice items as well
        if (isset($invoiceData['items']) && count($invoiceData['items']) > 0) {
            foreach ($invoiceData['items'] as $item) {
                $invoiceItem = new InvoiceItem();
                $invoiceItem->id = (string) ($item['id'] ?? \Illuminate\Support\Str::uuid());
                $invoiceItem->invoice_id = $invoice->id;
                $invoiceItem->i_code = $item['i_code'];
                $invoiceItem->i_name = $item['i_name'];
                $invoiceItem->i_amount = $item['i_amount'];
                $invoiceItem->note = $item['note'] ?? null;
                $invoiceItem->i_type = $item['i_type'] ?? null;
                $invoiceItem->i_org = $item['i_org'] ?? null;
                $invoiceItem->save();
            }
        }
        // Record transaction
        $this->recordTransaction(
            'invoice',
            $user_id,
            $invoice->c_name,
            $invoice->amount,
            'pending',
            ['invoice_number' => $invoice->invoice_number]
        );

        return $invoice;
    }

    /**
     * Process payment for an invoice
     */
    public function processPayment(Request $request)
    {
        try {
            // Validate the incoming request including custom fields
            $validated = $request->validate([
                'invoice_number' => 'required|string',
                'tdate' => 'required|string',
                'amount' => 'required|numeric',
                'receipt_no' => 'required|string',
                // Custom fields validation
                'year_of_assessment' => 'nullable|integer',
                'irs_id' => 'nullable|string',
                'irs_name' => 'nullable|string',
                'tax_type' => 'nullable|string',
                'fullname' => 'nullable|string',
                'custom_fields' => 'nullable|array',
            ]);

            // Extract only the fields needed for the API request
            $apiRequestData = array_filter($validated, function ($key) {
                return !in_array($key, [
                    'year_of_assessment',
                    'irs_id',
                    'irs_name',
                    'tax_type',
                    'fullname',
                    'custom_fields'
                ]);
            }, ARRAY_FILTER_USE_KEY);

            // Store custom fields separately
            $customFields = array_filter($validated, function ($key) {
                return in_array($key, [
                    'year_of_assessment',
                    'irs_id',
                    'irs_name',
                    'tax_type',
                    'fullname',
                    'custom_fields'
                ]);
            }, ARRAY_FILTER_USE_KEY);

            // Make API request to Cashworx
            $response = Http::withToken($this->bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiBaseUrl . '/payments', $apiRequestData);

            // Handle the response
            if ($response->successful()) {
                $responseData = $response->json();

                // Log the raw response to help with debugging
                Log::debug('Payment API Response: ' . json_encode($responseData));

                // Extract the payment data from the response
                $paymentData = isset($responseData['payment']) ? $responseData['payment'] : $responseData;

                // Merge custom fields with payment data
                $paymentDataWithCustomFields = array_merge($paymentData, $customFields);

                // Get user_id from the authenticated user
                $user_id = $request->user()->id;

                try {
                    // Store payment in local database
                    $payment = $this->storePaymentInDatabase($paymentDataWithCustomFields, $user_id);

                    // Update invoice status
                    $this->updateInvoiceStatus($paymentData['invoice_number']);

                    // Record transaction
                    $this->recordTransaction(
                        'payment',
                        $user_id,
                        $payment->fullname,
                        $payment->amount,
                        'completed',
                        [
                            'invoice_number' => $payment->invoice_number,
                            'receipt_no' => $payment->receipt_no
                        ]
                    );

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment processed successfully',
                        'data' => $payment
                    ], 201);
                } catch (\Exception $dbError) {
                    Log::error('Payment database error: ' . $dbError->getMessage());
                    Log::error('Payment data: ' . json_encode($paymentDataWithCustomFields));
                    throw $dbError;
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
                'error' => $response->json()
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store payment data in the local database
     */
    private function storePaymentInDatabase($paymentData, $user_id)
    {
        try {
            // Generate a proper ULID for the ID
            $ulid = (string) \Illuminate\Support\Str::ulid();

            // Prepare custom_fields data if it's not already in the right format
            if (isset($paymentData['custom_fields']) && !is_array($paymentData['custom_fields'])) {
                $paymentData['custom_fields'] = json_decode($paymentData['custom_fields'], true) ?? [];
            }

            // Insert with proper ULID
            $payment = new Payment();
            $payment->id = $ulid;
            $payment->user_id = $user_id;
            $payment->invoice_number = $paymentData['invoice_number'];
            $payment->receipt_no = $paymentData['receipt_no'];
            $payment->tdate = $paymentData['tdate'];
            $payment->amount = $paymentData['amount'];
            $payment->note = $paymentData['note'] ?? null;
            $payment->status = $paymentData['status'] ?? 1;
            $payment->log_time = $paymentData['log_time'];

            // Add custom fields
            $payment->year_of_assessment = $paymentData['year_of_assessment'] ?? null;
            $payment->irs_id = $paymentData['irs_id'] ?? null;
            $payment->irs_name = $paymentData['irs_name'] ?? null;
            $payment->tax_type = $paymentData['tax_type'] ?? null;
            $payment->fullname = $paymentData['fullname'] ?? null;
            $payment->custom_fields = $paymentData['custom_fields'] ?? null;

            $payment->save();

            return $payment;
        } catch (\Exception $e) {
            Log::error('Payment insert error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update the status of an invoice after payment
     */
    private function updateInvoiceStatus($invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if ($invoice) {
            $invoice->status = 1; // Assuming 1 is for 'paid' status
            $invoice->save();
            Log::info("Updated invoice {$invoiceNumber} status to paid");
        } else {
            Log::warning("Could not find invoice {$invoiceNumber} to update status");
        }
    }

    /**
     * Get all payments (with optional pagination)
     */
    public function getAllPayments(Request $request)
    {
        $paginated = $request->query('paginated', false);
        $endpoint = $paginated ? '/payments/paginate' : '/payments';

        try {
            $response = Http::withToken($this->bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->get($this->apiBaseUrl . $endpoint);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments',
                'error' => $response->json()
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('Get payments error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific payment by invoice number
     */
    public function getPayment($invoiceNumber)
    {
        try {
            $response = Http::withToken($this->bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->get($this->apiBaseUrl . '/payments/' . $invoiceNumber);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment',
                'error' => $response->json()
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('Get payment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /* 
        Get user payments
    */
    public function getPayments(Request $request)
    {
        try {
            $user = $request->user();
            $payments = Payment::where('user_id', $user->id)->get();

            return $this->sendResponse(
                $payments,
                "Payments retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get payments error: ' . $e->getMessage());
            return $this->sendError([
                'success' => false,
                'message' => 'An error occurred while retrieving payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user invoices
     *  */
    public function getInvoices(Request $request)
    {
        try {
            $user = $request->user();
            $invoices = Invoice::where('user_id', $user->id)->get();

            return $this->sendResponse(
                $invoices,
                "Invoices retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get invoices error: ' . $e->getMessage());
            return $this->sendError([
                'success' => false,
                'message' => 'An error occurred while retrieving invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *  Get a specific invoice by invoice number
     */
    public function getInvoice($invoiceNumber)
    {
        try {
            // get db state invoice
            $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

            if (!$invoice) {
                // get thirdparty state invoice
                $endpoint = '/invoices/' . $invoiceNumber;
                $response = Http::withToken($this->bearerToken)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->get($this->apiBaseUrl . $endpoint);
                if ($response->successful()) {
                    return $this->sendResponse(
                        $response->json(),
                        'Invoice',
                    );
                } else {
                    return $this->sendError('Failed to retrieve invoice', $response->json(), $response->status());
                }
            }

            return $this->sendResponse(
                $invoice,
                "Invoice retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get invoice error: ' . $e->getMessage());
            return $this->sendError([
                'success' => false,
                'message' => 'An error occurred while retrieving the invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
