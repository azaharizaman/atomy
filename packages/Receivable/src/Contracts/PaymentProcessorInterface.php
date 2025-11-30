<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

/**
 * Payment Processor Interface
 *
 * Handles payment receipt processing including multi-currency support and FX calculations.
 */
interface PaymentProcessorInterface
{
    /**
     * Process a payment receipt
     *
     * Handles currency conversion if payment currency differs from invoice currency.
     *
     * @param string $tenantId
     * @param array<string, mixed> $paymentData
     * @return PaymentReceiptInterface
     * @throws \Nexus\Receivable\Exceptions\InvalidPaymentException
     */
    public function processPayment(string $tenantId, array $paymentData): PaymentReceiptInterface;

    /**
     * Apply payment to invoices with allocation strategy
     *
     * @param string $receiptId
     * @param array<string, float> $allocations
     * @param string|null $strategyType
     * @return PaymentReceiptInterface
     * @throws \Nexus\Receivable\Exceptions\PaymentAllocationException
     */
    public function applyToInvoices(
        string $receiptId,
        array $allocations,
        ?string $strategyType = null
    ): PaymentReceiptInterface;

    /**
     * Calculate foreign exchange gain/loss for multi-currency payment
     *
     * @param float $paymentAmount
     * @param string $paymentCurrency
     * @param float $invoiceAmount
     * @param string $invoiceCurrency
     * @param float $exchangeRate
     * @return float Positive = gain, negative = loss
     */
    public function calculateFxGainLoss(
        float $paymentAmount,
        string $paymentCurrency,
        float $invoiceAmount,
        string $invoiceCurrency,
        float $exchangeRate
    ): float;

    /**
     * Reverse/void a payment
     *
     * @param string $receiptId
     * @param string $reason
     * @return void
     */
    public function voidPayment(string $receiptId, string $reason): void;
}
