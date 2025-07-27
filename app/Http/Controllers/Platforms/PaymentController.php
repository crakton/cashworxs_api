<?php

namespace App\Http\Controllers\Platforms;

use App\Http\Controllers\Api\BaseController;
use App\Services\PaymentGateways\PaymentService;
use App\Services\PaymentGateways\PaymentGatewayManager;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentController extends BaseController
{
    protected $paymentService;
    protected $gatewayManager;

    public function __construct(PaymentService $paymentService, PaymentGatewayManager $gatewayManager)
    {
        $this->paymentService = $paymentService;
        $this->gatewayManager = $gatewayManager;
    }

    /**
     * Create a new invoice with multi-gateway support
     */
    public function createInvoice(Request $request)
    {
        try {
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
                // Custom fields
                'year_of_assessment' => 'nullable|integer',
                'irs_id' => 'nullable|string',
                'irs_name' => 'nullable|string',
                'tax_type' => 'nullable|string',
                'fullname' => 'nullable|string',
                'custom_fields' => 'nullable|array',
                // Gateway selection
                'gateway' => 'nullable|string|in:' . implode(',', $this->gatewayManager->getAvailableGateways()),
                'preferred_gateways' => 'nullable|array'
            ]);

            // Add user ID to validated data
            $validated['user_id'] = $request->user()->id;

            // Create invoice using the service
            $result = $this->paymentService->createInvoice(
                $validated,
                $validated['gateway'] ?? null
            );

            if ($result['success']) {
                return $this->sendResponse(
                    [
                        'invoice' => $result['invoice'],
                        'gateway_response' => $result['gateway_response'],
                        'available_gateways' => $this->gatewayManager->getAvailableGateways()
                    ],
                    'Invoice created successfully',
                    201
                );
            }

            return $this->sendError(
                $result['error'],
                'Failed to create invoice',
                400
            );

        } catch (\Exception $e) {
            Log::error('Invoice creation error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while creating the invoice',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Process payment with multi-gateway support and fallback
     */
    public function processPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'invoice_number' => 'required|string',
                'tdate' => 'required|string',
                'amount' => 'required|numeric',
                'receipt_no' => 'nullable|string',
                'gateway' => 'nullable|string|in:' . implode(',', $this->gatewayManager->getAvailableGateways()),
                'preferred_gateways' => 'nullable|array',
                'payment_reference' => 'nullable|string', // For verification-based payments
                // Custom fields
                'year_of_assessment' => 'nullable|integer',
                'irs_id' => 'nullable|string',
                'irs_name' => 'nullable|string',
                'tax_type' => 'nullable|string',
                'fullname' => 'nullable|string',
                'custom_fields' => 'nullable|array',
            ]);

            // Add user ID
            $validated['user_id'] = $request->user()->id;

            // Determine gateways to try
            $gateways = $validated['preferred_gateways'] ?? 
                       ($validated['gateway'] ? [$validated['gateway']] : null);

            // Process payment with fallback
            $result = $this->paymentService->processPayment($validated, $gateways);

            if ($result['success']) {
                return $this->sendResponse(
                    [
                        'payment' => $result['payment'],
                        'gateway_used' => $result['gateway']
                    ],
                    'Payment processed successfully',
                    201
                );
            }

            return $this->sendError(
                $result['error'],
                'Payment processing failed',
                400
            );

        } catch (\Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while processing the payment',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Verify payment across gateways
     */
    public function verifyPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'reference' => 'required|string',
                'gateway' => 'required|string|in:' . implode(',', $this->gatewayManager->getAvailableGateways())
            ]);

            $gateway = $this->gatewayManager->gateway($validated['gateway']);
            $result = $gateway->verifyPayment($validated['reference']);

            if ($result['success']) {
                // Update local records if payment is successful
                $this->updatePaymentStatus($validated['reference'], $result['data']);
                
                return $this->sendResponse(
                    $result['data'],
                    'Payment verified successfully'
                );
            }

            return $this->sendError(
                $result['error'],
                'Payment verification failed',
                400
            );

        } catch (\Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while verifying the payment',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Handle webhooks from different gateways
     */
    public function handleWebhook(Request $request, string $gateway)
    {
        try {
            if (!in_array($gateway, $this->gatewayManager->getAvailableGateways())) {
                return response()->json(['error' => 'Unsupported gateway'], 400);
            }

            $gatewayHandler = $this->gatewayManager->gateway($gateway);
            $result = $gatewayHandler->webhookHandler($request->all());

            if ($result['success']) {
                // Process webhook data
                $this->processWebhookData($result['data'], $gateway);
                
                return response()->json(['status' => 'success'], 200);
            }

            return response()->json(['error' => 'Webhook processing failed'], 400);

        } catch (\Exception $e) {
            Log::error("Webhook error for {$gateway}: " . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Get payment status across gateways
     */
    public function getPaymentStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'reference' => 'required|string',
                'gateway' => 'required|string|in:' . implode(',', $this->gatewayManager->getAvailableGateways())
            ]);

            $gateway = $this->gatewayManager->gateway($validated['gateway']);
            $status = $gateway->getPaymentStatus($validated['reference']);

            return $this->sendResponse(
                ['status' => $status],
                'Payment status retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Get payment status error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while retrieving payment status',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get available payment gateways
     */
    public function getAvailableGateways()
    {
        return $this->sendResponse(
            $this->gatewayManager->getAvailableGateways(),
            'Available payment gateways'
        );
    }

    /**
     * Get payment by invoice number 
     */ 
    public function getPaymentByInvoiceNumber(Request $request, string $invoiceNumber)
    {
        try {
            $user = $request->user();
            $payment = Payment::where('invoice_number', $invoiceNumber)
                ->where('user_id', $user->id)
                ->with(['invoice', 'transactions'])
                ->first();
            if (!$payment) {
                return $this->sendError(
                    'Payment not found for this invoice',
                    [],
                    404
                );
            }
            return $this->sendResponse(
                $payment,
                "Payment retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get payment by invoice error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while retrieving the payment',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get user payments with gateway information
     */
    public function getPayments(Request $request)
    {
        try {
            $user = $request->user();
            
            $payments = Payment::where('user_id', $user->id)
                ->with(['invoice', 'transactions'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendResponse(
                $payments,
                "Payments retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get payments error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while retrieving payments',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get all payments 
     */
    public function getAllPayments(Request $request)
    {
        try {
            $payments = Payment::with(['user', 'invoice', 'transactions'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendResponse(
                $payments,
                "All payments retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get all payments error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while retrieving all payments',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get invoice by invoice number
     * This method retrieves a specific invoice by its number.
     * It is intended for user access and should be protected by appropriate middleware.
     */
    public function getInvoiceByInvoiceNumber(Request $request, string $invoiceNumber)
    {
        try {
            $user = $request->user();
            $invoice = Invoice::where('invoice_number', $invoiceNumber)
                ->where('user_id', $user->id)
                ->with(['items', 'payment', 'transactions'])
                ->first();
            if (!$invoice) {
                return $this->sendError(
                    'Invoice not found',
                    [],
                    404
                );
            }
            return $this->sendResponse(
                $invoice,
                "Invoice retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get invoice by number error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while retrieving the invoice',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get user invoices
     */
    public function getInvoices(Request $request)
    {
        try {
            $user = $request->user();
            
            $invoices = Invoice::where('user_id', $user->id)
                ->with(['items', 'payment', 'transactions'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendResponse(
                $invoices,
                "Invoices retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get invoices error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while retrieving invoices',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get all invoices
     * This method retrieves all invoices across the platform.
     * It is intended for administrative use and should be protected by appropriate middleware.
     */

    public function getAllInvoices(Request $request)
    {
        try {
            $invoices = Invoice::with(['user', 'items', 'payment', 'transactions'])
                ->orderBy('created_at', 'desc')
                ->get();
            return $this->sendResponse(
                $invoices,
                "All invoices retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get all invoices error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while retrieving all invoices',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get user transactions
     */
    public function getTransactions(Request $request)
    {
        try {
            $user = $request->user();
            
            $transactions = Transaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendResponse(
                $transactions,
                "Transactions retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get transactions error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while retrieving transactions',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get all transactions
     * This method retrieves all transactions across the platform.
     * It is intended for administrative use and should be protected by appropriate middleware.
     */
    public function getAllTransactions(Request $request)
    {
        try {
            $transactions = Transaction::with(['user', 'payment', 'invoice'])
                ->orderBy('created_at', 'desc')
                ->get();
            return $this->sendResponse(
                $transactions,
                "All transactions retrieved successfully"
            );
        } catch (\Exception $e) {
            Log::error('Get all transactions error: ' . $e->getMessage());
            return $this->sendError(
                'An error occurred while retrieving all transactions',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update payment status from gateway response
     */
    private function updatePaymentStatus(string $reference, array $data)
    {
        DB::transaction(function () use ($reference, $data) {
            // Update payment record
            $payment = Payment::where('receipt_no', $reference)
                ->orWhere('payment_reference', $reference)
                ->first();

            if ($payment) {
                $payment->update([
                    'status' => $data['status'] ?? 'completed',
                    'gateway_response' => $data
                ]);

                // Update related invoice
                if ($payment->invoice_number) {
                    $this->updateInvoiceStatus($payment->invoice_number, 'paid');
                }

                // Update transaction
                Transaction::where('transaction_metadata->reference', $reference)
                    ->update([
                        'transaction_status' => 'completed',
                        'transaction_metadata' => DB::raw("JSON_MERGE_PATCH(transaction_metadata, '" . json_encode($data) . "')")
                    ]);
            }
        });
    }

    /**
     * Process webhook data from different gateways
     */
    private function processWebhookData(array $data, string $gateway)
    {
        // Process based on gateway type
        switch ($gateway) {
            case 'paystack':
                $this->processPaystackWebhook($data);
                break;
            case 'flutterwave':
                $this->processFlutterwaveWebhook($data);
                break;
            case 'cashworx':
                $this->processCashworxWebhook($data);
                break;
            default:
                Log::warning("Unknown gateway webhook: {$gateway}");
        }
    }

    private function processPaystackWebhook(array $data)
    {
        if ($data['event'] === 'charge.success') {
            $reference = $data['data']['reference'];
            $this->updatePaymentStatus($reference, $data['data']);
        }
    }

    private function processFlutterwaveWebhook(array $data)
    {
        if ($data['event'] === 'charge.completed') {
            $reference = $data['data']['tx_ref'];
            $this->updatePaymentStatus($reference, $data['data']);
        }
    }

    private function processCashworxWebhook(array $data)
    {
        // Process Cashworx webhook
        if (isset($data['status']) && $data['status'] === 'success') {
            $reference = $data['receipt_no'];
            $this->updatePaymentStatus($reference, $data);
        }
    }

    private function updateInvoiceStatus(string $invoiceNumber, string $status)
    {
        Invoice::where('invoice_number', $invoiceNumber)
            ->update(['status' => $status === 'paid' ? 1 : 0]);
    }
}