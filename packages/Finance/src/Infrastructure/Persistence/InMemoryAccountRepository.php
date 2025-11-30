<?php

declare(strict_types=1);

namespace Nexus\Finance\Infrastructure\Persistence;

use Nexus\Finance\Domain\Contracts\AccountInterface;
use Nexus\Finance\Domain\Contracts\AccountRepositoryInterface;
use Nexus\Finance\Domain\Exceptions\AccountHasTransactionsException;

/**
 * In-Memory Account Repository
 * 
 * Internal adapter for testing and development purposes.
 * This repository stores accounts in memory and does not persist data.
 */
final class InMemoryAccountRepository implements AccountRepositoryInterface
{
    /** @var array<string, AccountInterface> */
    private array $accounts = [];

    /** @var array<string, int> Transaction counts for accounts */
    private array $transactionCounts = [];

    /**
     * {@inheritDoc}
     */
    public function find(string $id): ?AccountInterface
    {
        return $this->accounts[$id] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByCode(string $code): ?AccountInterface
    {
        foreach ($this->accounts as $account) {
            if ($account->getCode() === $code) {
                return $account;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(array $filters = []): array
    {
        $result = $this->accounts;

        // Apply filters if provided
        if (isset($filters['type'])) {
            $result = array_filter(
                $result,
                fn(AccountInterface $account) => $account->getType() === $filters['type']
            );
        }

        if (isset($filters['active'])) {
            $result = array_filter(
                $result,
                fn(AccountInterface $account) => $account->isActive() === $filters['active']
            );
        }

        if (isset($filters['is_header'])) {
            $result = array_filter(
                $result,
                fn(AccountInterface $account) => $account->isHeader() === $filters['is_header']
            );
        }

        return array_values($result);
    }

    /**
     * {@inheritDoc}
     */
    public function findChildren(string $parentId): array
    {
        return array_values(
            array_filter(
                $this->accounts,
                fn(AccountInterface $account) => $account->getParentId() === $parentId
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function save(AccountInterface $account): void
    {
        $this->accounts[$account->getId()] = $account;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): void
    {
        if ($this->getTransactionCount($id) > 0) {
            throw new AccountHasTransactionsException(
                "Cannot delete account {$id} because it has transactions"
            );
        }

        unset($this->accounts[$id]);
    }

    /**
     * {@inheritDoc}
     */
    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        foreach ($this->accounts as $account) {
            if ($account->getCode() === $code) {
                if ($excludeId !== null && $account->getId() === $excludeId) {
                    continue;
                }
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionCount(string $accountId): int
    {
        return $this->transactionCounts[$accountId] ?? 0;
    }

    /**
     * Set transaction count for testing purposes
     */
    public function setTransactionCount(string $accountId, int $count): void
    {
        $this->transactionCounts[$accountId] = $count;
    }

    /**
     * Clear all accounts (for testing)
     */
    public function clear(): void
    {
        $this->accounts = [];
        $this->transactionCounts = [];
    }
}
