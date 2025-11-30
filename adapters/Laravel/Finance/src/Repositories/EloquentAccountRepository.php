<?php

declare(strict_types=1);

namespace Nexus\Laravel\Finance\Repositories;

use Nexus\Finance\Domain\Contracts\AccountInterface;
use Nexus\Finance\Domain\Contracts\AccountRepositoryInterface;
use Nexus\Laravel\Finance\Models\Account;

/**
 * Eloquent implementation of Account Repository
 */
final readonly class EloquentAccountRepository implements AccountRepositoryInterface
{
    public function find(string $id): ?AccountInterface
    {
        return Account::find($id);
    }

    public function findByCode(string $code): ?AccountInterface
    {
        return Account::where('code', $code)->first();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<AccountInterface>
     */
    public function findAll(array $filters = []): array
    {
        $query = Account::query();
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['is_header'])) {
            $query->where('is_header', $filters['is_header']);
        }
        
        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }
        
        return $query->orderBy('code')->get()->all();
    }

    /**
     * @return array<AccountInterface>
     */
    public function findChildren(string $parentId): array
    {
        return Account::where('parent_id', $parentId)
            ->orderBy('code')
            ->get()
            ->all();
    }

    public function save(AccountInterface $account): void
    {
        if ($account instanceof Account) {
            $account->save();
            return;
        }

        // Handle case where it's not an Eloquent model
        Account::updateOrCreate(
            ['id' => $account->getId()],
            [
                'code' => $account->getCode(),
                'name' => $account->getName(),
                'type' => $account->getType(),
                'currency' => $account->getCurrency(),
                'parent_id' => $account->getParentId(),
                'is_header' => $account->isHeader(),
                'is_active' => $account->isActive(),
                'description' => $account->getDescription(),
            ]
        );
    }

    public function delete(string $id): void
    {
        $transactionCount = $this->getTransactionCount($id);
        
        if ($transactionCount > 0) {
            throw new \Nexus\Finance\Domain\Exceptions\AccountHasTransactionsException(
                "Cannot delete account {$id}: has {$transactionCount} transactions"
            );
        }
        
        Account::destroy($id);
    }

    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $query = Account::where('code', $code);
        
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function getTransactionCount(string $accountId): int
    {
        $account = Account::find($accountId);
        
        if ($account === null) {
            return 0;
        }
        
        return $account->journalEntryLines()->count();
    }
}
