<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Entities\AccountInterface;

/**
 * Repository interface for Chart of Accounts operations.
 *
 * Provides CRUD operations for managing accounts in the chart of accounts.
 * Implementations must handle tenant isolation and account hierarchy.
 */
interface ChartOfAccountsRepositoryInterface
{
    /**
     * Find an account by its ID.
     *
     * @param string $accountId The unique account identifier
     * @return AccountInterface|null The account or null if not found
     */
    public function findById(string $accountId): ?AccountInterface;

    /**
     * Find an account by its code within a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param string $code The account code
     * @return AccountInterface|null The account or null if not found
     */
    public function findByCode(string $tenantId, string $code): ?AccountInterface;

    /**
     * Get all accounts for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @return array<AccountInterface> List of accounts
     */
    public function findAllByTenant(string $tenantId): array;

    /**
     * Get accounts by type for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param string $accountType The account type (asset, liability, equity, revenue, expense)
     * @return array<AccountInterface> List of accounts of the specified type
     */
    public function findByType(string $tenantId, string $accountType): array;

    /**
     * Get child accounts of a parent account.
     *
     * @param string $parentAccountId The parent account identifier
     * @return array<AccountInterface> List of child accounts
     */
    public function findChildren(string $parentAccountId): array;

    /**
     * Save an account (create or update).
     *
     * @param AccountInterface $account The account to save
     * @return AccountInterface The saved account
     */
    public function save(AccountInterface $account): AccountInterface;

    /**
     * Delete an account.
     *
     * @param string $accountId The account identifier
     * @return bool True if deleted successfully
     */
    public function delete(string $accountId): bool;

    /**
     * Check if an account code exists for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param string $code The account code
     * @param string|null $excludeAccountId Account ID to exclude from check (for updates)
     * @return bool True if code exists
     */
    public function codeExists(string $tenantId, string $code, ?string $excludeAccountId = null): bool;

    /**
     * Check if an account has any transactions.
     *
     * @param string $accountId The account identifier
     * @return bool True if account has transactions
     */
    public function hasTransactions(string $accountId): bool;
}
