<?php

namespace App\Services\PaymentGateways;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use DB;
use Log;

class PaymentService
{
    protected $gatewayManager;

    public function __construct(PaymentGatewayManager $gatewayManager)
    {
        $this->gatewayManager = $gatewayManager;
    }

    /**
     * Create invoice across multiple gateways
     */
    public function createInvoice(array $invoiceData, ?string $gateway = null): array
    {
        DB::beginTransaction();
        
        try {
            $gatewayResponse = null;
            
            // If gateway specified, create on gateway first
            if ($gateway) {
                $gatewayResponse = $this->gatewayManager
                    ->gateway($gateway)
                    ->createInvoice($invoiceData);
                
                Log::info('Gateway response received', [
                    'gateway' => $gateway,
                    'success' => $gatewayResponse['success'],
                    'response_data' => $gatewayResponse['data'] ?? null
                ]);
                
                if (!$gatewayResponse['success']) {
                    DB::rollback();
                    return [
                        'success' => false,
                        'error' => $gatewayResponse['error']
                    ];
                }
                
                // Use invoice number from gateway response
                $invoiceData['invoice_number'] = $gatewayResponse['data']['invoice_number'] ?? null;
                $invoiceData['gateway_invoice_id'] = $gatewayResponse['data']['id'] ?? null;
                $invoiceData['gateway_response'] = $gatewayResponse['data'];
                
                // If no invoice number from gateway, log warning
                if (!$invoiceData['invoice_number']) {
                    Log::warning('Gateway did not return invoice_number', [
                        'gateway' => $gateway,
                        'response_keys' => array_keys($gatewayResponse['data'] ?? [])
                    ]);
                }
            }
            
            // Store invoice locally (with or without gateway response)
            $invoice = $this->storeInvoiceLocally($invoiceData, $gateway);
            
            // Record initial transaction
            $this->recordTransaction(
                'invoice',
                $invoiceData['user_id'],
                $invoiceData['fullname'] ?? $invoiceData['c_name'],
                $invoiceData['amount'],
                'pending',
                [
                    'invoice_number' => $invoice->invoice_number,
                    'gateway' => $gateway ?? 'local'
                ]
            );

            DB::commit();
            
            return [
                'success' => true,
                'invoice' => $invoice,
                'gateway_response' => $gatewayResponse
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Invoice creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process payment with fallback gateways
     */
    public function processPayment(array $paymentData, ?array $gateways = null): array
    {
        $gateways = $gateways ?? [$this->gatewayManager->getDefaultGateway()];
        $lastError = null;

        foreach ($gateways as $gateway) {
            try {
                DB::beginTransaction();
                
                $result = $this->gatewayManager
                    ->gateway($gateway)
                    ->processPayment($paymentData);

                if ($result['success']) {
                    // Store payment locally
                    $payment = $this->storePaymentLocally(
                        array_merge($paymentData, $result['data']),
                        $gateway
                    );
                    
                    // Update invoice status
                    $this->updateInvoiceStatus($paymentData['invoice_number'], 'paid');
                    
                    // Record successful transaction
                    $this->recordTransaction(
                        'payment',
                        $paymentData['user_id'],
                        $paymentData['fullname'],
                        $paymentData['amount'],
                        'completed',
                        [
                            'gateway' => $gateway,
                            'reference' => $result['reference'] ?? null,
                            'invoice_number' => $paymentData['invoice_number']
                        ]
                    );

                    DB::commit();
                    
                    return [
                        'success' => true,
                        'payment' => $payment,
                        'gateway' => $gateway
                    ];
                }
            } catch (\Exception $e) {
                DB::rollback();
                $lastError = $e->getMessage();
                Log::error("Payment failed on gateway {$gateway}: " . $e->getMessage());
                continue;
            }
        }

        // All gateways failed
        $this->recordTransaction(
            'payment',
            $paymentData['user_id'],
            $paymentData['fullname'],
            $paymentData['amount'],
            'failed',
            [
                'error' => $lastError,
                'attempted_gateways' => $gateways
            ]
        );

        return [
            'success' => false,
            'error' => $lastError
        ];
    }

    private function storeInvoiceLocally(array $data, ?string $gateway = null): Invoice
    {
        // Generate required fields that are missing
        $invoiceId = $this->generateInvoiceId();
        $invoiceData = array_merge($data, [
            'id' => $invoiceId,
            'invoice_number' => $data['invoice_number'] ?? $this->generateInvoiceNumber(),
            'mda_id' => $data['mda_id'] ?? 1, // Default MDA ID
            'mda_code' => $data['mda_code'] ?? 'MDA001', // Default MDA Code
            'log_time' => now(),
            'status' => 0, // unpaid
            'gateway' => $gateway ?? 'local'
        ]);

        // Extract items before creating invoice
        $items = $invoiceData['items'] ?? [];
        unset($invoiceData['items']);

        // Create the invoice
        $invoice = Invoice::create($invoiceData);

        // Create invoice items if provided
        if (!empty($items)) {
            foreach ($items as $item) {
                $invoice->items()->create([
                    'invoice_id' => $invoice->id,
                    'i_code' => $item['i_code'],
                    'i_name' => $item['i_name'],
                    'i_amount' => $item['i_amount'],
                    'note' => $item['note'] ?? null,
                    'i_type' => $item['i_type'] ?? null,
                    'i_org' => $item['i_org'] ?? null,
                ]);
            }
        }

        return $invoice->load('items'); // Return invoice with items loaded
    }

    private function storePaymentLocally(array $data, string $gateway): Payment
    {
        // Payment storage logic with gateway info
        return Payment::create(array_merge($data, ['gateway' => $gateway]));
    }

    private function recordTransaction(string $type, $userId, string $fullname, $amount, string $status, array $metadata = []): Transaction
    {
        return Transaction::create([
            'user_id' => $userId,
            'fullname' => $fullname,
            'transaction_type' => $type,
            'transaction_name' => $type === 'invoice' ? 'Invoice Created' : 'Payment Processed',
            'transaction_amount' => $amount,
            'transaction_status' => $status,
            'transaction_metadata' => $metadata
        ]);
    }

    private function updateTransactionStatus(string $invoiceNumber, string $status, array $metadata = [])
    {
        Transaction::where('transaction_metadata->invoice_number', $invoiceNumber)
            ->update([
                'transaction_status' => $status,
                'transaction_metadata' => DB::raw("JSON_MERGE_PATCH(transaction_metadata, '" . json_encode($metadata) . "')")
            ]);
    }

    private function updateInvoiceStatus(string $invoiceNumber, string $status)
    {
        Invoice::where('invoice_number', $invoiceNumber)
            ->update(['status' => $status === 'paid' ? 1 : 0]);
    }

    /**
     * Generate a unique invoice ID
     */
    private function generateInvoiceId(): int
    {
        // Get the latest invoice ID and increment it
        $lastInvoice = Invoice::orderBy('id', 'desc')->first();
        $nextId = $lastInvoice ? $lastInvoice->id + 1 : 1000001; // Start from 1000001 if no invoices exist
        
        // Ensure uniqueness
        while (Invoice::where('id', $nextId)->exists()) {
            $nextId++;
        }
        
        return $nextId;
    }

    /**
     * Generate a unique invoice number (only used when no gateway is specified)
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-LOCAL-' . date('Y') . '-';
        $sequence = str_pad(Invoice::whereYear('created_at', date('Y'))->count() + 1, 6, '0', STR_PAD_LEFT);
        $invoiceNumber = $prefix . $sequence;
        
        // Ensure uniqueness
        $counter = 1;
        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $sequence = str_pad(Invoice::whereYear('created_at', date('Y'))->count() + 1 + $counter, 6, '0', STR_PAD_LEFT);
            $invoiceNumber = $prefix . $sequence;
            $counter++;
        }
        
        return $invoiceNumber;
    }
}