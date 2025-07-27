<?php

namespace App\Services\PaymentGateways;

/**
 * Payment Gateway Manager
 */
class PaymentGatewayManager
{
    protected $gateways = [];
    protected $defaultGateway;
    
    public function __construct()
    {
        $this->defaultGateway = config('payment.default_gateway', 'cashworxs');
    }
    
    /**
     * Get gateway instance
     */
    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        $gatewayName = $name ?? $this->defaultGateway;
        
        if (!isset($this->gateways[$gatewayName])) {
            $this->gateways[$gatewayName] = $this->createGateway($gatewayName);
        }
        
        return $this->gateways[$gatewayName];
    }
    
    /**
     * Create gateway instance
     */
    private function createGateway(string $name): PaymentGatewayInterface
    {
        switch ($name) {
            case 'cashworxs':
                return new CashworxsGateway();
            case 'paystack':
                return new PaystackGateway();
            default:
                throw new \InvalidArgumentException("Gateway {$name} not supported");
        }
    }
    
    /**
     * Get available gateways
     */
    public function getAvailableGateways(): array
    {
        return ['cashworxs', 'paystack'];
    }
    
    /**
     * Get default gateway
     */
    public function getDefaultGateway(): string
    {
        return $this->defaultGateway;
    }
    
    /**
     * Check if gateway is available
     */
    public function isGatewayAvailable(string $gateway): bool
    {
        return in_array($gateway, $this->getAvailableGateways());
    }
    
    /**
     * Get gateway fallback order
     */
    public function getFallbackOrder(): array
    {
        return config('payment.fallback_order', ['cashworxs', 'paystack']);
    }
}