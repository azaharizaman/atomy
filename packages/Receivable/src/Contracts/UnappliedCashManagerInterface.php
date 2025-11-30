<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

/**
 * Unapplied Cash Manager Interface
 *
 * Manages customer prepayments and payments received before invoice creation.
 */
interface UnappliedCashManagerInterface
{
    /**
     * Record unapplied cash (prepayment)
     *
     * Creates GL entry: Debit Cash, Credit Unapplied Revenue
     *
     * @param string $tenantId
     * @param string $customerId
     * @param string $receiptId
     * @param float $amount
     * @param string $currency
     * @return UnappliedCashInterface
     */
    public function recordUnappliedCash(
        string $tenantId,
        string $customerId,
        string $receiptId,
        float $amount,
        string $currency
    ): UnappliedCashInterface;

    /**
     * Apply unapplied cash to an invoice
     *
     * Reverses liability and applies to invoice.
     *
     * @param string $unappliedCashId
     * @param string $invoiceId
     * @return void
     */
    public function applyToInvoice(string $unappliedCashId, string $invoiceId): void;

    /**
     * Get total unapplied cash for a customer
     *
     * @param string $tenantId
     * @param string $customerId
     * @return float
     */
    public function getTotalUnapplied(string $tenantId, string $customerId): float;

    /**
     * Get unapplied cash entries for a customer
     *
     * @param string $tenantId
     * @param string $customerId
     * @return UnappliedCashInterface[]
     */
    public function getUnappliedEntries(string $tenantId, string $customerId): array;
}
