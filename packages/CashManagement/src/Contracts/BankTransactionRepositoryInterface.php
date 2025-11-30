<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

/**
 * Bank Transaction Repository Interface
 */
interface BankTransactionRepositoryInterface
{
    public function findById(string $id): ?BankTransactionInterface;

    /**
     * @return array<BankTransactionInterface>
     */
    public function getByStatement(string $statementId): array;

    /**
     * @return array<BankTransactionInterface>
     */
    public function getUnreconciled(string $statementId): array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): BankTransactionInterface;

    /**
     * @param array<array<string, mixed>> $transactions
     * @return array<BankTransactionInterface>
     */
    public function createBatch(array $transactions): array;

    public function markAsReconciled(string $id, string $reconciliationId): void;

    public function delete(string $id): bool;
}
