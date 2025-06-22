<?php

namespace App\Services\PaymentGateways;

use Http;

/**
 * Cashworx Gateway Implementation
 */
class CashworxsGateway implements PaymentGatewayInterface
{
    protected $apiBaseUrl;
    protected $bearerToken;

    public function __construct()
    {
        $this->apiBaseUrl = config('payment.gateways.cashworxs.base_url');
        $this->bearerToken = $this->authenticate();
    }

    public function createInvoice(array $invoiceData): array
    {
        // Your existing createInvoice logic here
        $response = Http::withToken($this->bearerToken)
            ->post($this->apiBaseUrl . '/invoices', $invoiceData);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
                'gateway' => 'cashworxs'
            ];
        }

        return [
            'success' => false,
            'error' => $response->json(),
            'gateway' => 'cashworxs'
        ];
    }

    public function processPayment(array $paymentData): array
    {
        $response = Http::withToken($this->bearerToken)
            ->post($this->apiBaseUrl . '/payments', $paymentData);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
                'gateway' => 'cashworxs',
                'reference' => $response->json()['receipt_no'] ?? null
            ];
        }

        return [
            'success' => false,
            'error' => $response->json(),
            'gateway' => 'cashworxs'
        ];
    }

    public function verifyPayment(string $reference): array
    {
        $response = Http::withToken($this->bearerToken)
            ->get($this->apiBaseUrl . '/payments/' . $reference);

        return [
            'success' => $response->successful(),
            'data' => $response->json(),
            'gateway' => 'cashworxs'
        ];
    }

    public function getPaymentStatus(string $reference): string
    {
        $verification = $this->verifyPayment($reference);
        
        if ($verification['success']) {
            return $verification['data']['status'] == 1 ? 'completed' : 'pending';
        }
        
        return 'failed';
    }

    public function webhookHandler(array $payload): array
    {
        // Handle Cashworx webhooks
        return [
            'success' => true,
            'data' => $payload,
            'gateway' => 'cashworxs'
        ];
    }

    private function authenticate()
    {
        // Your existing authentication logic
        $response = Http::post($this->apiBaseUrl . '/authenticate', [
            'access_key' => config('payment.gateways.cashworxs.access_key'),
            'access_secret' => config('payment.gateways.cashworxs.access_secret')
        ]);

        return $response->successful() ? $response->json()['data']['access_token'] : null;
    }
}