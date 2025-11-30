<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

/**
 * Unapplied Cash Repository Interface
 *
 * Defines data persistence operations for unapplied customer prepayments.
 */
interface UnappliedCashRepositoryInterface
{
    public function findById(string $id): ?UnappliedCashInterface;

    public function save(UnappliedCashInterface $unappliedCash): void;

    public function delete(string $id): void;

    /**
     * Get unapplied cash for a customer
     *
     * @return UnappliedCashInterface[]
     */
    public function getByCustomer(string $tenantId, string $customerId): array;

    /**
     * Get total unapplied cash amount for a customer
     */
    public function getTotalUnapplied(string $tenantId, string $customerId): float;

    /**
     * Get unapplied cash by receipt
     */
    public function getByReceipt(string $receiptId): ?UnappliedCashInterface;
}
