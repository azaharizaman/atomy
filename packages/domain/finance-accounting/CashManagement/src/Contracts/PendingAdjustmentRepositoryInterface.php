<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

/**
 * Pending Adjustment Repository Interface
 */
interface PendingAdjustmentRepositoryInterface
{
    public function findById(string $id): ?PendingAdjustmentInterface;

    /**
     * @return array<PendingAdjustmentInterface>
     */
    public function getPending(string $tenantId): array;

    /**
     * @return array<PendingAdjustmentInterface>
     */
    public function getByTransaction(string $bankTransactionId): array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): PendingAdjustmentInterface;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): PendingAdjustmentInterface;

    public function markAsPosted(string $id, string $journalEntryId, string $postedBy): void;

    public function delete(string $id): bool;
}
