<?php

namespace App\Http\Controllers\Platforms;

use App\Http\Controllers\Api\BaseController;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentGateway;
use DB;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function createInvoice(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'tdate' => 'required|date',
            'note' => 'nullable|string',
            'amount' => 'required|numeric',
            'c_code' => 'required|string',
            'c_name' => 'required|string',
            'c_address' => 'required|string',
            'c_phone' => 'required|string',
            'c_number' => 'required|string',
            'c_email' => 'required|email',
            'items' => 'required|array',
            // 'items.*.i_name' => 'required|string',
            // 'items.*.i_code' => 'required|string',
            // 'items.*.i_amount' => 'required|numeric',
            // 'items.*.note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Create invoice in payment gateway first
            $response = $this->paymentGateway->createInvoice($validated);

            if (!$response->successful()) {
                throw new \Exception('Failed to create invoice in payment gateway');
            }

            // Get the response data
            $gatewayData = $response->json();

            // Extract invoice data from gateway response
            $invoiceData = [
                'invoice_number' => $gatewayData['invoice']['invoice_number'],
                'client_invoice_number' => $gatewayData['invoice']['client_invoice_number'],
                'tdate' => $validated['tdate'],
                'note' => $validated['note'],
                'amount' => $validated['amount'],
                'c_code' => $validated['c_code'],
                'c_name' => $validated['c_name'],
                'c_address' => $validated['c_address'],
                'c_phone' => $validated['c_phone'],
                'c_number' => $validated['c_number'],
                'c_email' => $validated['c_email'],
                'status' => 'pending'
            ];

            // Create invoice in local database with gateway's invoice number
            $invoice = Invoice::create($invoiceData);

            // Create invoice items
            foreach ($request->items as $item) {
                $invoice->items()->create($item);
            }

            DB::commit();

            return $this->sendResponse([
                'status' => 'success',
                'data' => $gatewayData,
                'local_invoice' => $invoice->load('items')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError([
                'status' => 'error',
                'message' => 'Failed to create invoice: ' . $e->getMessage()
            ], 400);
        }
    }

    public function processPayment(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'invoice_number' => 'required|exists:invoices,invoice_number',
            'tdate' => 'required|date',
            'amount' => 'required|numeric',
            'receipt_no' => 'required|string|unique:payments,receipt_no'
        ]);

        try {
            DB::beginTransaction();

            // Create payment record
            $payment = Payment::create($validated);

            // Update invoice status
            Invoice::where('invoice_number', $validated['invoice_number'])
                ->update(['status' => 'paid']);

            // Record payment in payment gateway
            $response = $this->paymentGateway->recordPayment($validated);

            if (!$response->successful()) {
                throw new \Exception('Payment gateway error');
            }

            DB::commit();

            return $this->sendResponse([
                'status' => 'success',
                'message' => 'Payment processed successfully',
                'data' => [
                    'payment' => $payment,
                    'gateway_response' => $response->json()
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError([
                'status' => 'error',
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 400);
        }
    }
}
