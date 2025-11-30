<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

/**
 * Bank Account Repository Interface
 *
 * Contract for bank account persistence operations.
 */
interface BankAccountRepositoryInterface
{
    /**
     * Find bank account by ID
     */
    public function findById(string $id): ?BankAccountInterface;

    /**
     * Find bank account by account code
     */
    public function findByAccountCode(string $tenantId, string $accountCode): ?BankAccountInterface;

    /**
     * Find bank account by account number
     */
    public function findByAccountNumber(string $tenantId, string $accountNumber): ?BankAccountInterface;

    /**
     * Get all bank accounts for a tenant
     *
     * @param array<string, mixed> $filters
     * @return array<BankAccountInterface>
     */
    public function getByTenant(string $tenantId, array $filters = []): array;

    /**
     * Get active bank accounts for a tenant
     *
     * @return array<BankAccountInterface>
     */
    public function getActiveByTenant(string $tenantId): array;

    /**
     * Create new bank account
     *
     * @param array<string, mixed> $data
     */
    public function create(string $tenantId, array $data): BankAccountInterface;

    /**
     * Update existing bank account
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): BankAccountInterface;

    /**
     * Update account balance
     */
    public function updateBalance(string $id, string $newBalance): void;

    /**
     * Update last reconciled timestamp
     */
    public function updateLastReconciledAt(string $id, \DateTimeImmutable $reconciledAt): void;

    /**
     * Delete bank account
     */
    public function delete(string $id): bool;
}
