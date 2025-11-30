<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Entities\Account;

/**
 * Repository interface for Account entity persistence operations.
 *
 * Implementations should handle tenant isolation and chart of accounts structure.
 */
interface AccountRepositoryInterface
{
    /**
     * Find account by ID.
     *
     * @param string $id Account ULID
     * @return Account|null
     */
    public function findById(string $id): ?Account;

    /**
     * Find account by code within tenant context.
     *
     * @param string $tenantId Tenant ULID
     * @param string $code Account code (e.g., "1000", "2100")
     * @return Account|null
     */
    public function findByCode(string $tenantId, string $code): ?Account;

    /**
     * Get all accounts for a tenant.
     *
     * @param string $tenantId Tenant ULID
     * @return array<Account>
     */
    public function findAllByTenant(string $tenantId): array;

    /**
     * Get accounts by type for a tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param string $accountType Account type (asset, liability, equity, revenue, expense)
     * @return array<Account>
     */
    public function findByType(string $tenantId, string $accountType): array;

    /**
     * Get child accounts of a parent account.
     *
     * @param string $parentId Parent account ULID
     * @return array<Account>
     */
    public function findChildren(string $parentId): array;

    /**
     * Save account (create or update).
     *
     * @param Account $account
     * @return Account
     */
    public function save(Account $account): Account;

    /**
     * Delete account.
     *
     * @param string $id Account ULID
     * @return void
     */
    public function delete(string $id): void;

    /**
     * Check if account code exists for tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param string $code Account code
     * @param string|null $excludeId Exclude account ID (for updates)
     * @return bool
     */
    public function codeExists(string $tenantId, string $code, ?string $excludeId = null): bool;
}
