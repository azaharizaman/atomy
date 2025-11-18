<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Account;
use Nexus\Finance\Contracts\AccountInterface;
use Nexus\Finance\Contracts\AccountRepositoryInterface;
use Nexus\Finance\Exceptions\AccountNotFoundException;
use Nexus\Finance\Exceptions\DuplicateAccountCodeException;
use Illuminate\Database\QueryException;

/**
 * Eloquent Account Repository
 * 
 * Implements AccountRepositoryInterface using Laravel Eloquent.
 */
final readonly class EloquentAccountRepository implements AccountRepositoryInterface
{
    public function __construct(
        private Account $model
    ) {}

    public function findById(string $id): ?AccountInterface
    {
        return $this->model->find($id);
    }

    public function findByCode(string $code): ?AccountInterface
    {
        return $this->model->where('code', $code)->first();
    }

    public function findByType(string $type): array
    {
        return $this->model->ofType($type)->get()->all();
    }

    public function findAll(): array
    {
        return $this->model->orderBy('code')->get()->all();
    }

    public function findActive(): array
    {
        return $this->model->active()->orderBy('code')->get()->all();
    }

    public function findPostable(): array
    {
        return $this->model->postable()->active()->orderBy('code')->get()->all();
    }

    public function save(AccountInterface $account): void
    {
        if (!$account instanceof Account) {
            throw new \InvalidArgumentException('Account must be an Eloquent model');
        }

        try {
            $account->save();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new DuplicateAccountCodeException(
                    "Account code '{$account->getCode()}' already exists"
                );
            }
            throw $e;
        }
    }

    public function delete(string $id): void
    {
        $account = $this->model->find($id);
        
        if (!$account) {
            throw new AccountNotFoundException("Account with ID {$id} not found");
        }

        $account->delete();
    }

    public function exists(string $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    public function codeExists(string $code): bool
    {
        return $this->model->where('code', $code)->exists();
    }

    public function hasChildren(string $parentId): bool
    {
        return $this->model->where('parent_id', $parentId)->exists();
    }

    public function getHierarchy(): array
    {
        $accounts = $this->model->with('children')->whereNull('parent_id')->get();
        
        return $this->buildHierarchy($accounts->all());
    }

    private function buildHierarchy(array $accounts): array
    {
        $result = [];
        
        foreach ($accounts as $account) {
            $item = [
                'account' => $account,
                'children' => []
            ];
            
            if ($account->children->isNotEmpty()) {
                $item['children'] = $this->buildHierarchy($account->children->all());
            }
            
            $result[] = $item;
        }
        
        return $result;
    }
}
