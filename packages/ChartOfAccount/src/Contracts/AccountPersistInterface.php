<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Contracts;

/**
 * Persistence operations for Chart of Accounts (CQRS Command model).
 *
 * This interface defines write operations for managing account data.
 * Consuming applications must provide a concrete implementation using
 * their chosen persistence mechanism (Eloquent, Doctrine, etc.).
 *
 * The implementation is responsible for:
 * - Creating entity instances from data arrays
 * - Persisting entities to storage
 * - Applying updates to existing entities
 */
interface AccountPersistInterface
{
    /**
     * Create a new account from data.
     *
     * @param array<string, mixed> $data Account data including:
     *   - code: string (required)
     *   - name: string (required)
     *   - type: string (required, AccountType value)
     *   - parent_id: string|null (optional)
     *   - is_header: bool (optional, default false)
     *   - is_active: bool (optional, default true)
     *   - description: string|null (optional)
     * @return AccountInterface Created account with generated ID
     * @throws \Nexus\ChartOfAccount\Exceptions\DuplicateAccountCodeException If code already exists
     * @throws \Nexus\ChartOfAccount\Exceptions\InvalidAccountException If validation fails
     */
    public function create(array $data): AccountInterface;

    /**
     * Update an existing account.
     *
     * @param string $id Account ULID to update
     * @param array<string, mixed> $data Fields to update (partial update supported)
     * @return AccountInterface Updated account
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountNotFoundException If account not found
     * @throws \Nexus\ChartOfAccount\Exceptions\DuplicateAccountCodeException If new code already exists
     * @throws \Nexus\ChartOfAccount\Exceptions\InvalidAccountException If validation fails
     */
    public function update(string $id, array $data): AccountInterface;

    /**
     * Delete an account.
     *
     * @param string $id Account ULID to delete
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountNotFoundException If account not found
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountHasChildrenException If account has children
     * @throws \Nexus\ChartOfAccount\Exceptions\AccountHasTransactionsException If account has transactions
     */
    public function delete(string $id): void;
}
