<?php

declare(strict_types=1);

namespace App\Repositories\Finance;

use App\Models\Finance\Account;
use Nexus\Finance\Contracts\AccountInterface;
use Nexus\Finance\Contracts\AccountRepositoryInterface;
use Nexus\Finance\Exceptions\AccountHasTransactionsException;

/**
 * Eloquent Account Repository
 * 
 * Implements AccountRepositoryInterface using Laravel Eloquent.
 */
final readonly class EloquentAccountRepository implements AccountRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function find(string $id): ?AccountInterface
    {
        return Account::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByCode(string $code): ?AccountInterface
    {
        return Account::where('code', $code)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(array $filters = []): array
    {
        $query = Account::query();

        if (isset($filters['type'])) {
            $query->where('account_type', $filters['type']);
        }

        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active']);
        }

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (isset($filters['is_header'])) {
            $query->where('is_header', $filters['is_header']);
        }

        return $query->orderBy('code')->get()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function findChildren(string $parentId): array
    {
        return Account::where('parent_id', $parentId)
            ->orderBy('code')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function save(AccountInterface $account): void
    {
        if ($account instanceof Account) {
            $account->save();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): void
    {
        $account = Account::findOrFail($id);

        // Check for transactions
        if ($this->getTransactionCount($id) > 0) {
            throw AccountHasTransactionsException::forAccount($id);
        }

        $account->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $query = Account::where('code', $code);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionCount(string $accountId): int
    {
        $account = Account::findOrFail($accountId);
        
        return $account->journalEntryLines()->count();
    }
}
