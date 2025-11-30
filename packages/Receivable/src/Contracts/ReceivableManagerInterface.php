<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

use DateTimeInterface;

/**
 * Receivable Manager Interface
 *
 * Main orchestrator for Accounts Receivable operations.
 * Manages invoice lifecycle, payment application, and collections.
 */
interface ReceivableManagerInterface
{
    /**
     * Create an invoice from a Sales Order
     *
     * Snapshots prices, taxes, and terms from the order.
     * Does NOT recalculate them.
     *
     * @param string $tenantId
     * @param string $salesOrderId
     * @param array<string, mixed> $overrides Optional invoice data overrides
     * @return CustomerInvoiceInterface
     */
    public function createInvoiceFromOrder(
        string $tenantId,
        string $salesOrderId,
        array $overrides = []
    ): CustomerInvoiceInterface;

    /**
     * Create a standalone invoice (not from sales order)
     *
     * @param string $tenantId
     * @param array<string, mixed> $invoiceData
     * @return CustomerInvoiceInterface
     */
    public function createInvoice(string $tenantId, array $invoiceData): CustomerInvoiceInterface;

    /**
     * Approve an invoice for posting
     *
     * @param string $invoiceId
     * @param string $approvedBy
     * @return CustomerInvoiceInterface
     */
    public function approveInvoice(string $invoiceId, string $approvedBy): CustomerInvoiceInterface;

    /**
     * Post invoice to General Ledger
     *
     * Creates GL journal entry:
     * - Debit: AR Control (1200)
     * - Credit: Revenue accounts (per line)
     *
     * @param string $invoiceId
     * @return string GL Journal ID
     */
    public function postInvoiceToGL(string $invoiceId): string;

    /**
     * Void/cancel an invoice
     *
     * @param string $invoiceId
     * @param string $reason
     * @return void
     */
    public function voidInvoice(string $invoiceId, string $reason): void;

    /**
     * Record a payment receipt from customer
     *
     * @param string $tenantId
     * @param array<string, mixed> $paymentData
     * @return PaymentReceiptInterface
     */
    public function recordPayment(string $tenantId, array $paymentData): PaymentReceiptInterface;

    /**
     * Apply payment to invoice(s)
     *
     * @param string $receiptId
     * @param array<string, float> $allocations Map of invoice_id => amount
     * @return PaymentReceiptInterface
     */
    public function applyPayment(string $receiptId, array $allocations): PaymentReceiptInterface;

    /**
     * Void a payment receipt
     *
     * @param string $receiptId
     * @param string $reason
     * @return void
     */
    public function voidPayment(string $receiptId, string $reason): void;

    /**
     * Get customer's outstanding balance
     *
     * @param string $tenantId
     * @param string $customerId
     * @return float
     */
    public function getCustomerBalance(string $tenantId, string $customerId): float;

    /**
     * Get aging report for customer or all customers
     *
     * @param string $tenantId
     * @param DateTimeInterface $asOfDate
     * @param string|null $customerId If null, returns all customers
     * @return array<string, mixed>
     */
    public function getAgingReport(
        string $tenantId,
        DateTimeInterface $asOfDate,
        ?string $customerId = null
    ): array;

    /**
     * Get overdue invoices
     *
     * @param string $tenantId
     * @param int $minDaysPastDue
     * @return CustomerInvoiceInterface[]
     */
    public function getOverdueInvoices(string $tenantId, int $minDaysPastDue = 0): array;

    /**
     * Write off invoice as bad debt
     *
     * Creates GL journal entry:
     * - Debit: Bad Debt Expense
     * - Credit: AR Control
     *
     * @param string $invoiceId
     * @param string $reason
     * @return string GL Journal ID
     */
    public function writeOffInvoice(string $invoiceId, string $reason): string;

    /**
     * Check if customer can make a purchase (credit limit check)
     *
     * @param string $customerId
     * @param float $amount
     * @return bool
     * @throws \Nexus\Receivable\Exceptions\CreditLimitExceededException
     */
    public function checkCreditLimit(string $customerId, float $amount): bool;
}
