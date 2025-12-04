<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Contracts;

use Nexus\ChartOfAccount\Enums\AccountType;

/**
 * Query operations for Chart of Accounts (CQRS Query model).
 *
 * This interface defines read-only operations for retrieving account data.
 * Consuming applications must provide a concrete implementation using
 * their chosen persistence mechanism (Eloquent, Doctrine, etc.).
 */
interface AccountQueryInterface
{
    /**
     * Find an account by its unique identifier.
     *
     * @param string $id Account ULID
     * @return AccountInterface|null Account if found, null otherwise
     */
    public function find(string $id): ?AccountInterface;

    /**
     * Find an account by its code.
     *
     * @param string $code Account code
     * @return AccountInterface|null Account if found, null otherwise
     */
    public function findByCode(string $code): ?AccountInterface;

    /**
     * Get all accounts matching optional filters.
     *
     * @param array<string, mixed> $filters Optional filters:
     *   - 'type' => AccountType - Filter by account type
     *   - 'is_header' => bool - Filter by header/postable
     *   - 'is_active' => bool - Filter by active status
     *   - 'parent_id' => string|null - Filter by parent
     * @return array<AccountInterface> List of accounts
     */
    public function findAll(array $filters = []): array;

    /**
     * Find all child accounts of a parent account.
     *
     * @param string $parentId Parent account ULID
     * @return array<AccountInterface> List of child accounts
     */
    public function findChildren(string $parentId): array;

    /**
     * Find all accounts of a specific type.
     *
     * @param AccountType $type Account type to filter by
     * @return array<AccountInterface> List of accounts
     */
    public function findByType(AccountType $type): array;

    /**
     * Find all active postable accounts.
     *
     * Postable accounts are non-header accounts that can receive transactions.
     *
     * @return array<AccountInterface> List of postable accounts
     */
    public function findPostableAccounts(): array;

    /**
     * Check if an account code already exists.
     *
     * @param string $code Account code to check
     * @param string|null $excludeId Optional account ID to exclude (for updates)
     * @return bool True if code exists
     */
    public function codeExists(string $code, ?string $excludeId = null): bool;

    /**
     * Check if an account has any child accounts.
     *
     * @param string $id Account ULID
     * @return bool True if account has children
     */
    public function hasChildren(string $id): bool;
}
