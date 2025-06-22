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
            // Store invoice locally first
            $invoice = $this->storeInvoiceLocally($invoiceData);
            
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

            // If gateway specified, create on gateway
            if ($gateway) {
                $gatewayResponse = $this->gatewayManager
                    ->gateway($gateway)
                    ->createInvoice($invoiceData);
                
                if (!$gatewayResponse['success']) {
                    // Update transaction status
                    $this->updateTransactionStatus($invoice->invoice_number, 'failed', $gatewayResponse['error']);
                }
            }

            DB::commit();
            
            return [
                'success' => true,
                'invoice' => $invoice,
                'gateway_response' => $gatewayResponse ?? null
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

    private function storeInvoiceLocally(array $data): Invoice
    {
        // invoice storage logic
        return Invoice::create($data);
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
}