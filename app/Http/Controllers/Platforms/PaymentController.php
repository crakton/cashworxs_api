<?php

namespace App\Http\Controllers\Platforms;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
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
            // Validate the incoming request
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
            ]);

            // Make API request to Cashworx payment gateway
            $response = Http::withToken($this->bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiBaseUrl . '/invoices', $validated);

            // Handle the response
            if ($response->successful()) {
                $responseData = $response->json();

                // Log the raw response to help with debugging
                Log::debug('API Response: ' . json_encode($responseData));

                // Extract the invoice data from the response
                $invoiceData = isset($responseData['invoice']) ? $responseData['invoice'] : $responseData;

                // Get user_id from the authenticated user
                $user_id = $request->user()->id;

                // Store invoice in local database with debugging
                try {
                    $invoice = $this->storeInvoiceInDatabase($invoiceData, $user_id);

                    return $this->sendResponse(
                        $invoice,
                        'Invoice created successfully',
                        201
                    );
                } catch (\Exception $dbError) {
                    Log::error('Database error: ' . $dbError->getMessage());
                    Log::error('Invoice data: ' . json_encode($invoiceData));
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
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the invoice',
                'error' => $e->getMessage()
            ], 500);
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

        return $invoice;
    }
    /**
     * Process payment for an invoice
     */
    public function processPayment(Request $request)
    {
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'invoice_number' => 'required|string',
                'tdate' => 'required|string',
                'amount' => 'required|numeric',
                'receipt_no' => 'required|string',
            ]);

            // Make API request to Cashworx
            $response = Http::withToken($this->bearerToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiBaseUrl . '/payments', $validated);

            // Handle the response
            if ($response->successful()) {
                $responseData = $response->json();

                // Log the raw response to help with debugging
                Log::debug('Payment API Response: ' . json_encode($responseData));

                // Extract the payment data from the response
                $paymentData = isset($responseData['payment']) ? $responseData['payment'] : $responseData;

                // Get user_id from the authenticated user
                $user_id = $request->user()->id;

                try {
                    // Store payment in local database
                    $payment = $this->storePaymentInDatabase($paymentData, $user_id);

                    // Update invoice status
                    $this->updateInvoiceStatus($paymentData['invoice_number']);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment processed successfully',
                        'data' => $payment
                    ], 201);
                } catch (\Exception $dbError) {
                    Log::error('Payment database error: ' . $dbError->getMessage());
                    Log::error('Payment data: ' . json_encode($paymentData));
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
}
