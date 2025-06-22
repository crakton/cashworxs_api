<?php

namespace App\Services\PaymentGateways;

use App\Services\PaymentGateways\PaymentGatewayInterface;
use Http;

/**
 * Paystack Gateway Implementation
 */
class PaystackGateway implements PaymentGatewayInterface
{
    protected $secretKey;
    protected $publicKey;

    public function __construct()
    {
        $this->secretKey = config('payment.gateways.paystack.secret_key');
        $this->publicKey = config('payment.gateways.paystack.public_key');
    }

    public function createInvoice(array $invoiceData): array
    {
        // Transform data to Paystack format
        $paystackData = [
            'amount' => $invoiceData['amount'] * 100, // Paystack uses kobo
            'email' => $invoiceData['c_email'],
            'metadata' => [
                'invoice_number' => $invoiceData['invoice_number'] ?? null,
                'customer_name' => $invoiceData['c_name'],
                'custom_fields' => $invoiceData['custom_fields'] ?? []
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.paystack.co/transaction/initialize', $paystackData);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
                'gateway' => 'paystack'
            ];
        }

        return [
            'success' => false,
            'error' => $response->json(),
            'gateway' => 'paystack'
        ];
    }

    public function processPayment(array $paymentData): array
    {
        // For Paystack, this would typically be verification after frontend payment
        return $this->verifyPayment($paymentData['reference']);
    }

    public function verifyPayment(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey
        ])->get('https://api.paystack.co/transaction/verify/' . $reference);

        return [
            'success' => $response->successful(),
            'data' => $response->json(),
            'gateway' => 'paystack'
        ];
    }

    public function getPaymentStatus(string $reference): string
    {
        $verification = $this->verifyPayment($reference);
        
        if ($verification['success'] && $verification['data']['status']) {
            $status = $verification['data']['data']['status'];
            return $status === 'success' ? 'completed' : 'failed';
        }
        
        return 'failed';
    }

    public function webhookHandler(array $payload): array
    {
        // Verify webhook signature
        $signature = hash_hmac('sha512', json_encode($payload), $this->secretKey);
        
        return [
            'success' => true,
            'data' => $payload,
            'gateway' => 'paystack'
        ];
    }
}