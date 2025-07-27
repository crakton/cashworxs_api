<?php

namespace App\Services\PaymentGateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackGateway implements PaymentGatewayInterface
{
    protected $secretKey;
    protected $publicKey;
    protected $baseUrl;
    
    public function __construct()
    {
        $this->secretKey = config('payment.gateways.paystack.secret_key');
        $this->publicKey = config('payment.gateways.paystack.public_key');
        $this->baseUrl = config('payment.gateways.paystack.base_url', 'https://api.paystack.co');
    }

    public function createInvoice(array $invoiceData): array
    {
        try {
            // Transform data to Paystack format
            $paystackData = [
                'amount' => $invoiceData['amount'] * 100, // Paystack uses kobo
                'email' => $invoiceData['c_email'],
                'reference' => $this->generateReference(),
                'currency' => 'NGN',
                'metadata' => [
                    'invoice_number' => $invoiceData['invoice_number'] ?? null,
                    'customer_name' => $invoiceData['c_name'],
                    'customer_phone' => $invoiceData['c_phone'],
                    'customer_address' => $invoiceData['c_address'],
                    'custom_fields' => $invoiceData['custom_fields'] ?? [],
                    'items' => $invoiceData['items'] ?? []
                ],
                'callback_url' => config('app.url') . '/payment/callback/paystack',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . '/transaction/initialize', $paystackData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'gateway' => 'paystack'
                ];
            }

            Log::error('Paystack invoice creation failed', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Invoice creation failed',
                'gateway' => 'paystack'
            ];
        } catch (\Exception $e) {
            Log::error('Paystack invoice creation exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'paystack'
            ];
        }
    }

    public function processPayment(array $paymentData): array
    {
        try {
            // For Paystack, this would typically be verification after frontend payment
            // If a reference is provided, verify it
            if (isset($paymentData['payment_reference'])) {
                return $this->verifyPayment($paymentData['payment_reference']);
            }

            // Otherwise, initialize a new transaction
            return $this->createInvoice($paymentData);
        } catch (\Exception $e) {
            Log::error('Paystack payment processing exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'paystack'
            ];
        }
    }

    public function verifyPayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/transaction/verify/' . $reference);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data,
                    'gateway' => 'paystack',
                    'status' => $data['data']['status'] ?? 'failed'
                ];
            }

            Log::error('Paystack payment verification failed', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Verification failed',
                'gateway' => 'paystack'
            ];
        } catch (\Exception $e) {
            Log::error('Paystack payment verification exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'paystack'
            ];
        }
    }

    public function getPaymentStatus(string $reference): string
    {
        $verification = $this->verifyPayment($reference);
        
        if ($verification['success'] && isset($verification['data']['status'])) {
            $status = $verification['data']['data']['status'];
            return $status === 'success' ? 'completed' : 'failed';
        }
        
        return 'failed';
    }

    public function webhookHandler(array $payload): array
    {
        try {
            // Verify webhook signature
            $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
            
            if (!$this->validateWebhookSignature($payload, $signature)) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook signature',
                    'gateway' => 'paystack'
                ];
            }

            return [
                'success' => true,
                'data' => $payload,
                'gateway' => 'paystack',
                'event' => $payload['event'] ?? 'unknown'
            ];
        } catch (\Exception $e) {
            Log::error('Paystack webhook handling exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'paystack'
            ];
        }
    }

    private function generateReference(): string
    {
        return 'PS_' . time() . '_' . uniqid();
    }

    private function validateWebhookSignature(array $payload, string $signature): bool
    {
        try {
            $computed_signature = hash_hmac('sha512', json_encode($payload), $this->secretKey);
            return hash_equals($signature, $computed_signature);
        } catch (\Exception $e) {
            Log::error('Paystack signature validation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get transaction details
     */
    public function getTransaction(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/transaction/' . $reference);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'gateway' => 'paystack'
            ];
        } catch (\Exception $e) {
            Log::error('Paystack get transaction exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'paystack'  
            ];
        }
    }

    /**
     * List transactions
     */
    public function listTransactions(array $params = []): array
    {
        try {
            $queryParams = http_build_query($params);
            $url = $this->baseUrl . '/transaction' . ($queryParams ? '?' . $queryParams : '');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Accept' => 'application/json'
            ])->get($url);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'gateway' => 'paystack'
            ];
        } catch (\Exception $e) {
            Log::error('Paystack list transactions exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'paystack'
            ];
        }
    }
}