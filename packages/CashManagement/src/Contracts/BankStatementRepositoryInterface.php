<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

/**
 * Bank Statement Repository Interface
 */
interface BankStatementRepositoryInterface
{
    public function findById(string $id): ?BankStatementInterface;

    public function findByStatementNumber(string $tenantId, string $statementNumber): ?BankStatementInterface;

    public function findByHash(string $tenantId, string $hash): ?BankStatementInterface;

    /**
     * @param array<string, mixed> $filters
     * @return array<BankStatementInterface>
     */
    public function getByBankAccount(string $bankAccountId, array $filters = []): array;

    /**
     * @param array<string, mixed> $filters
     * @return array<BankStatementInterface>
     */
    public function getByTenant(string $tenantId, array $filters = []): array;

    /**
     * @return array<BankStatementInterface>
     */
    public function getUnreconciled(string $bankAccountId): array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): BankStatementInterface;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): BankStatementInterface;

    public function markAsReconciled(string $id, \DateTimeImmutable $reconciledAt): void;

    public function delete(string $id): bool;
}
