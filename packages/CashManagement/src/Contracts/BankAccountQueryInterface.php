<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

/**
 * Query interface for bank account retrieval operations.
 */
interface BankAccountQueryInterface
{
    /**
     * Find bank account by tenant and ID.
     *
     * @param string $tenantId Tenant ULID
     * @param string $id Bank account ULID
     * @return BankAccountInterface|null
     */
    public function findByTenantAndId(string $tenantId, string $id): ?BankAccountInterface;

    /**
     * Find bank account by account code.
     *
     * @param string $tenantId Tenant ULID
     * @param string $accountCode Bank account code
     * @return BankAccountInterface|null
     */
    public function findByAccountCode(string $tenantId, string $accountCode): ?BankAccountInterface;

    /**
     * Find bank account by account number.
     *
     * @param string $tenantId Tenant ULID
     * @param string $accountNumber Bank account number
     * @return BankAccountInterface|null
     */
    public function findByAccountNumber(string $tenantId, string $accountNumber): ?BankAccountInterface;

    /**
     * Get all bank accounts for a tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param array<string, mixed> $filters Optional filters
     * @return array<BankAccountInterface>
     */
    public function getByTenant(string $tenantId, array $filters = []): array;

    /**
     * Get active bank accounts for a tenant.
     *
     * @param string $tenantId Tenant ULID
     * @return array<BankAccountInterface>
     */
    public function getActiveByTenant(string $tenantId): array;
}