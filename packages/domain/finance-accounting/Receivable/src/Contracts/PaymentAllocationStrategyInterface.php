<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

/**
 * Payment Allocation Strategy Interface
 *
 * Defines how a payment should be allocated across multiple open invoices.
 * Implementations: FIFO, Proportional, Manual
 */
interface PaymentAllocationStrategyInterface
{
    /**
     * Allocate payment amount across open invoices
     *
     * @param float $paymentAmount
     * @param CustomerInvoiceInterface[] $openInvoices
     * @return array<string, float> Map of invoice_id => allocated_amount
     * @throws \Nexus\Receivable\Exceptions\PaymentAllocationException
     */
    public function allocate(float $paymentAmount, array $openInvoices): array;

    /**
     * Get strategy name
     */
    public function getName(): string;
}
