<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

use DateTimeInterface;

/**
 * Customer Invoice Repository Interface
 *
 * Defines data persistence operations for customer invoices.
 */
interface CustomerInvoiceRepositoryInterface
{
    public function findById(string $id): ?CustomerInvoiceInterface;

    public function findByNumber(string $tenantId, string $invoiceNumber): ?CustomerInvoiceInterface;

    public function save(CustomerInvoiceInterface $invoice): void;

    public function delete(string $id): void;

    /**
     * Get all invoices for a customer
     *
     * @return CustomerInvoiceInterface[]
     */
    public function getByCustomer(string $tenantId, string $customerId): array;

    /**
     * Get open (unpaid) invoices for a customer
     *
     * @return CustomerInvoiceInterface[]
     */
    public function getOpenInvoices(string $tenantId, string $customerId): array;

    /**
     * Get overdue invoices
     *
     * @return CustomerInvoiceInterface[]
     */
    public function getOverdueInvoices(string $tenantId, DateTimeInterface $asOfDate, int $minDaysPastDue = 0): array;

    /**
     * Calculate total outstanding balance for a customer
     */
    public function getOutstandingBalance(string $tenantId, string $customerId): float;

    /**
     * Calculate total outstanding balance for a customer group
     */
    public function getGroupOutstandingBalance(string $tenantId, string $groupId): float;

    /**
     * Get invoices by status
     *
     * @return CustomerInvoiceInterface[]
     */
    public function getByStatus(string $tenantId, string $status): array;

    /**
     * Get invoices created from a specific sales order
     *
     * @return CustomerInvoiceInterface[]
     */
    public function getBySalesOrder(string $salesOrderId): array;
}
