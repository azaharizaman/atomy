<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Contracts;

/**
 * Persistence operations for Chart of Accounts (CQRS Command model).
 *
 * This interface defines write operations for managing account data.
 * Consuming applications must provide a concrete implementation using
 * their chosen persistence mechanism (Eloquent, Doctrine, etc.).
 */
interface AccountPersistInterface
{
    /**
     * Save an account (create or update).
     *
     * @param AccountInterface $account Account to save
     * @return AccountInterface Saved account with updated timestamps
     * @throws \Nexus\ChartOfAccount\Exceptions\DuplicateAccountCodeException If code already exists
     * @throws \Nexus\ChartOfAccount\Exceptions\InvalidAccountException If validation fails
     */
    public function save(AccountInterface $account): AccountInterface;

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
