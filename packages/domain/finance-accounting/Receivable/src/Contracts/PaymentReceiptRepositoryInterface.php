<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

/**
 * Payment Receipt Repository Interface
 *
 * Defines data persistence operations for payment receipts.
 */
interface PaymentReceiptRepositoryInterface
{
    public function findById(string $id): ?PaymentReceiptInterface;

    public function findByNumber(string $tenantId, string $receiptNumber): ?PaymentReceiptInterface;

    public function save(PaymentReceiptInterface $receipt): void;

    public function delete(string $id): void;

    /**
     * Get all receipts for a customer
     *
     * @return PaymentReceiptInterface[]
     */
    public function getByCustomer(string $tenantId, string $customerId): array;

    /**
     * Get receipts with unapplied balance
     *
     * @return PaymentReceiptInterface[]
     */
    public function getUnappliedReceipts(string $tenantId, string $customerId): array;

    /**
     * Get receipts by status
     *
     * @return PaymentReceiptInterface[]
     */
    public function getByStatus(string $tenantId, string $status): array;
}
