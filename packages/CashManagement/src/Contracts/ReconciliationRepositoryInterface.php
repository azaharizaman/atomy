<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

/**
 * Reconciliation Repository Interface
 */
interface ReconciliationRepositoryInterface
{
    public function findById(string $id): ?ReconciliationInterface;

    /**
     * @return array<ReconciliationInterface>
     */
    public function getByTransaction(string $bankTransactionId): array;

    /**
     * @param array<string, mixed> $filters
     * @return array<ReconciliationInterface>
     */
    public function getByTenant(string $tenantId, array $filters = []): array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): ReconciliationInterface;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): ReconciliationInterface;

    public function markAsReconciled(string $id, string $reconciledBy, \DateTimeImmutable $reconciledAt): void;

    public function delete(string $id): bool;
}
