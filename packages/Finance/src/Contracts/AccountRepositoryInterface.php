<?php

declare(strict_types=1);

namespace Nexus\Finance\Contracts;

/**
 * Account Repository Interface
 * 
 * Persistence contract for account operations.
 */
interface AccountRepositoryInterface
{
    /**
     * Find an account by ID
     */
    public function find(string $id): ?AccountInterface;

    /**
     * Find an account by code
     */
    public function findByCode(string $code): ?AccountInterface;

    /**
     * Find all accounts (optionally filtered)
     * 
     * @param array<string, mixed> $filters
     * @return array<AccountInterface>
     */
    public function findAll(array $filters = []): array;

    /**
     * Find child accounts of a parent
     * 
     * @return array<AccountInterface>
     */
    public function findChildren(string $parentId): array;

    /**
     * Save an account (create or update)
     */
    public function save(AccountInterface $account): void;

    /**
     * Delete an account
     * 
     * @throws \Nexus\Finance\Exceptions\AccountHasTransactionsException
     */
    public function delete(string $id): void;

    /**
     * Check if an account code exists
     */
    public function codeExists(string $code, ?string $excludeId = null): bool;

    /**
     * Get the count of transactions for an account
     */
    public function getTransactionCount(string $accountId): int;
}
