<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGateway
{
  private $baseUrl = 'https://server.inteliworxtest.com';
  private $accessKey;
  private $accessSecret;
  private $token;

  public function __construct()
  {
    $this->accessKey = config('services.payment_gateway.key');
    $this->accessSecret = config('services.payment_gateway.secret');
  }

  private function authenticate()
  {
    try {
      $response = Http::post($this->baseUrl . '/authenticate', [
        'access_key' => $this->accessKey,
        'access_secret' => $this->accessSecret
      ]);

      if (!$response->successful()) {
        Log::error('Authentication failed', [
          'status' => $response->status(),
          'response' => $response->json()
        ]);
        throw new \Exception('Authentication failed: ' . $response->status());
      }

      $data = $response->json();
      $this->token = $data['data']['access_token'];

      Log::info('Authentication successful', ['token' => $this->token]);

      return $this->token;
    } catch (\Exception $e) {
      Log::error('Authentication error', [
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  private function ensureAuthenticated()
  {
    if (empty($this->token)) {
      $this->authenticate();
    }
  }

  public function createInvoice($data)
  {
    try {
      $this->ensureAuthenticated();

      $response = Http::withToken($this->token)
        ->post($this->baseUrl . '/invoices', $data);

      Log::info('Create invoice request', [
        'request' => $data,
        'response' => $response->json()
      ]);

      if (!$response->successful()) {
        Log::error('Create invoice failed', [
          'status' => $response->status(),
          'response' => $response->json()
        ]);
      }

      return $response;
    } catch (\Exception $e) {
      Log::error('Create invoice error', [
        'error' => $e->getMessage(),
        'data' => $data
      ]);
      throw $e;
    }
  }

  public function recordPayment($data)
  {
    try {
      $this->ensureAuthenticated();

      $response = Http::withToken($this->token)
        ->post($this->baseUrl . '/payments', $data);

      Log::info('Record payment request', [
        'request' => $data,
        'response' => $response->json()
      ]);

      return $response;
    } catch (\Exception $e) {
      Log::error('Record payment error', [
        'error' => $e->getMessage(),
        'data' => $data
      ]);
      throw $e;
    }
  }

  public function getPayment($invoiceNumber)
  {
    try {
      $this->ensureAuthenticated();

      $response = Http::withToken($this->token)
        ->get($this->baseUrl . '/payments/' . $invoiceNumber);

      Log::info('Get payment request', [
        'invoice_number' => $invoiceNumber,
        'response' => $response->json()
      ]);

      return $response;
    } catch (\Exception $e) {
      Log::error('Get payment error', [
        'error' => $e->getMessage(),
        'invoice_number' => $invoiceNumber
      ]);
      throw $e;
    }
  }
}
