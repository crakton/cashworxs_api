<?php

namespace App\Services\PaymentGateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        try {
            // Transform data to Cashworxs format if needed
            $cashworxsData = $this->transformInvoiceData($invoiceData);
            
            $response = Http::withToken($this->bearerToken)
                ->post($this->apiBaseUrl . '/invoices', $cashworxsData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'gateway' => 'cashworxs'
                ];
            }

            Log::error('Cashworxs invoice creation failed', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Invoice creation failed',
                'gateway' => 'cashworxs'
            ];
        } catch (\Exception $e) {
            Log::error('Cashworxs invoice creation exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'cashworxs'
            ];
        }
    }

    public function processPayment(array $paymentData): array
    {
        try {
            // Transform data to Cashworxs format if needed
            $cashworxsData = $this->transformPaymentData($paymentData);
            
            $response = Http::withToken($this->bearerToken)
                ->post($this->apiBaseUrl . '/payments', $cashworxsData);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'data' => $responseData,
                    'gateway' => 'cashworxs',
                    'reference' => $responseData['receipt_no'] ?? $responseData['reference'] ?? null
                ];
            }

            Log::error('Cashworxs payment processing failed', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Payment processing failed',
                'gateway' => 'cashworxs'
            ];
        } catch (\Exception $e) {
            Log::error('Cashworxs payment processing exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'cashworxs'
            ];
        }
    }

    public function verifyPayment(string $reference): array
    {
        try {
            $response = Http::withToken($this->bearerToken)
                ->get($this->apiBaseUrl . '/payments/' . $reference);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'gateway' => 'cashworxs'
            ];
        } catch (\Exception $e) {
            Log::error('Cashworxs payment verification exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'cashworxs'
            ];
        }
    }

    public function getPaymentStatus(string $reference): string
    {
        $verification = $this->verifyPayment($reference);
        
        if ($verification['success']) {
            $status = $verification['data']['status'] ?? 0;
            return $status == 1 ? 'completed' : 'pending';
        }
        
        return 'failed';
    }

    public function webhookHandler(array $payload): array
    {
        try {
            // Validate webhook signature if needed
            $isValid = $this->validateWebhookSignature($payload);
            
            if (!$isValid) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook signature',
                    'gateway' => 'cashworxs'
                ];
            }

            return [
                'success' => true,
                'data' => $payload,
                'gateway' => 'cashworxs'
            ];
        } catch (\Exception $e) {
            Log::error('Cashworxs webhook handling exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway' => 'cashworxs'
            ];
        }
    }

    private function authenticate()
    {
        try {
            $response = Http::post($this->apiBaseUrl . '/authenticate', [
                'access_key' => config('payment.gateways.cashworxs.access_key'),
                'access_secret' => config('payment.gateways.cashworxs.access_secret')
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['access_token'] ?? null;
            }

            Log::error('Cashworxs authentication failed', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cashworxs authentication exception: ' . $e->getMessage());
            return null;
        }
    }

    private function transformInvoiceData(array $data): array
    {
        // Transform your invoice data to match Cashworxs API format
        return [
            'tdate' => $data['tdate'],
            'note' => $data['note'] ?? '',
            'amount' => $data['amount'],
            'c_code' => $data['c_code'],
            'c_name' => $data['c_name'],
            'c_address' => $data['c_address'],
            'c_phone' => $data['c_phone'],
            'c_number' => $data['c_number'],
            'c_email' => $data['c_email'],
            'items' => $data['items'],
            // Add any additional fields as needed
            'year_of_assessment' => $data['year_of_assessment'] ?? null,
            'irs_id' => $data['irs_id'] ?? null,
            'irs_name' => $data['irs_name'] ?? null,
            'tax_type' => $data['tax_type'] ?? null,
            'fullname' => $data['fullname'] ?? $data['c_name'],
            'custom_fields' => $data['custom_fields'] ?? [],
        ];
    }

    private function transformPaymentData(array $data): array
    {
        // Transform your payment data to match Cashworxs API format
        return [
            'invoice_number' => $data['invoice_number'],
            'tdate' => $data['tdate'],
            'amount' => $data['amount'],
            'receipt_no' => $data['receipt_no'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            // Add any additional fields as needed
            'year_of_assessment' => $data['year_of_assessment'] ?? null,
            'irs_id' => $data['irs_id'] ?? null,
            'irs_name' => $data['irs_name'] ?? null,
            'tax_type' => $data['tax_type'] ?? null,
            'fullname' => $data['fullname'] ?? '',
            'custom_fields' => $data['custom_fields'] ?? [],
        ];
    }

    private function validateWebhookSignature(array $payload): bool
    {
        // Implement webhook signature validation if Cashworxs provides it
        // For now, return true - implement actual validation based on Cashworxs docs
        return true;
    }
}