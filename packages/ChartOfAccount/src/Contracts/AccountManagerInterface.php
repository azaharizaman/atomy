<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Contracts;

use Nexus\ChartOfAccount\Enums\AccountType;

/**
 * High-level Chart of Accounts management interface.
 *
 * This interface provides business-level operations for managing the chart of accounts.
 * It combines query and persistence operations with additional business logic validation.
 *
 * For direct CQRS access, use AccountQueryInterface and AccountPersistInterface separately.
 */
interface AccountManagerInterface
{
    /**
     * Create a new account.
     *
     * @param array<string, mixed> $data Account data:
     *   - 'code' => string (required) - Unique account code
     *   - 'name' => string (required) - Account name
     *   - 'type' => string (required) - AccountType value
     *   - 'currency' => string|null - ISO 4217 currency code
     *   - 'parent_id' => string|null - Parent account ULID
     *   - 'is_header' => bool - Whether this is a header/group account
     *   - 'description' => string|null - Account description
     * @return AccountInterface Created account
     * @throws \Nexus\ChartOfAccount\Exceptions\DuplicateAccountCodeException If code exists
     * @throws \Nexus\ChartOfAccount\Exceptions\InvalidAccountException If validation fails
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountNotFoundException If parent not found
     */
    public function createAccount(array $data): AccountInterface;

    /**
     * Update an existing account.
     *
     * @param string $id Account ULID to update
     * @param array<string, mixed> $data Fields to update
     * @return AccountInterface Updated account
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountNotFoundException If not found
     * @throws \Nexus\ChartOfAccount\Exceptions\DuplicateAccountCodeException If new code exists
     * @throws \Nexus\ChartOfAccount\Exceptions\InvalidAccountException If validation fails
     */
    public function updateAccount(string $id, array $data): AccountInterface;

    /**
     * Find an account by ID.
     *
     * @param string $id Account ULID
     * @return AccountInterface Account
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountNotFoundException If not found
     */
    public function findById(string $id): AccountInterface;

    /**
     * Find an account by code.
     *
     * @param string $code Account code
     * @return AccountInterface Account
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountNotFoundException If not found
     */
    public function findByCode(string $code): AccountInterface;

    /**
     * Get all accounts, optionally filtered.
     *
     * @param array<string, mixed> $filters Optional filters
     * @return array<AccountInterface> List of accounts
     */
    public function getAccounts(array $filters = []): array;

    /**
     * Get child accounts of a parent.
     *
     * @param string $parentId Parent account ULID
     * @return array<AccountInterface> List of child accounts
     */
    public function getChildren(string $parentId): array;

    /**
     * Get accounts by type.
     *
     * @param AccountType $type Account type
     * @return array<AccountInterface> List of accounts
     */
    public function getAccountsByType(AccountType $type): array;

    /**
     * Activate an account.
     *
     * @param string $id Account ULID
     * @return AccountInterface Activated account
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountNotFoundException If not found
     */
    public function activateAccount(string $id): AccountInterface;

    /**
     * Deactivate an account.
     *
     * Deactivated accounts cannot receive new transactions but retain history.
     *
     * @param string $id Account ULID
     * @return AccountInterface Deactivated account
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountNotFoundException If not found
     */
    public function deactivateAccount(string $id): AccountInterface;

    /**
     * Delete an account.
     *
     * @param string $id Account ULID
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountNotFoundException If not found
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountHasChildrenException If has children
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountHasTransactionsException If has transactions
     */
    public function deleteAccount(string $id): void;

    /**
     * Check if an account code is available.
     *
     * @param string $code Account code to check
     * @param string|null $excludeId Account ID to exclude (for updates)
     * @return bool True if code is available
     */
    public function isCodeAvailable(string $code, ?string $excludeId = null): bool;

    /**
     * Validate that a parent-child relationship is valid.
     *
     * Checks:
     * - Parent exists
     * - Parent is a header account
     * - Account types are compatible (child inherits root type)
     *
     * @param string $parentId Parent account ULID
     * @param AccountType $childType Child account type
     * @return bool True if relationship is valid
     */
    public function isValidParentChild(string $parentId, AccountType $childType): bool;
}
