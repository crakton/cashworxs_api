<?php

namespace App\Services\PaymentGateways;

/**
 * Abstract Payment Gateway Interface
 */
interface PaymentGatewayInterface
{
    public function createInvoice(array $invoiceData): array;
    public function processPayment(array $paymentData): array;
    public function verifyPayment(string $reference): array;
    public function getPaymentStatus(string $reference): string;
    public function webhookHandler(array $payload): array;
    
}

/**
 * Payment Gateway Manager
 */
class PaymentGatewayManager
{
    protected $gateways = [];
    protected $defaultGateway;
    
    public function __construct()
    {
        // Register available gateways
        $this->gateways = [
            'cashworx' => new CashworxsGateway(),
            'paystack' => new PaystackGateway(),
            // 'flutterwave' => new FlutterwaveGateway(),
            // 'stripe' => new StripeGateway(),
        ];
        
        $this->defaultGateway = config('payment.default_gateway', 'cashworx');
    }

  
    /**
     * Get the default payment gateway.
     *
     * @return string
     */
    public function getDefaultGateway(): string
    {
        // Replace 'your_default_gateway' with your actual default gateway key/name
        return property_exists($this, 'defaultGateway') ? $this->defaultGateway : 'your_default_gateway';
    }

    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        $gatewayName = $name ?? $this->defaultGateway;
        
        if (!isset($this->gateways[$gatewayName])) {
            throw new \InvalidArgumentException("Gateway {$gatewayName} not supported");
        }

        return $this->gateways[$gatewayName];
    }

    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }
}
