<?php

namespace App\Services\PaymentGateways;

/**
 * Payment Gateway Interface
 * 
 * All payment gateways must implement this interface to ensure
 * consistent behavior across different payment providers.
 */
interface PaymentGatewayInterface
{
    /**
     * Create an invoice on the payment gateway
     *
     * @param array $invoiceData Invoice data containing customer info, items, amounts, etc.
     * @return array Response array with 'success', 'data', 'gateway' keys
     */
    public function createInvoice(array $invoiceData): array;

    /**
     * Process a payment through the gateway
     *
     * @param array $paymentData Payment data containing amount, reference, customer info, etc.
     * @return array Response array with 'success', 'data', 'gateway', 'reference' keys
     */
    public function processPayment(array $paymentData): array;

    /**
     * Verify a payment status on the gateway
     *
     * @param string $reference Payment reference/transaction ID
     * @return array Response array with 'success', 'data', 'gateway' keys
     */
    public function verifyPayment(string $reference): array;

    /**
     * Get payment status as a simple string
     *
     * @param string $reference Payment reference/transaction ID
     * @return string Status: 'completed', 'pending', 'failed', etc.
     */
    public function getPaymentStatus(string $reference): string;

    /**
     * Handle webhook notifications from the payment gateway
     *
     * @param array $payload Webhook payload data
     * @return array Response array with 'success', 'data', 'gateway' keys
     */
    public function webhookHandler(array $payload): array;
}