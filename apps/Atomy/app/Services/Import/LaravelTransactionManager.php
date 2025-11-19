<?php

declare(strict_types=1);

namespace App\Services\Import;

use Illuminate\Support\Facades\DB;
use Nexus\Import\Contracts\TransactionManagerInterface;
use Nexus\Import\Exceptions\TransactionException;

/**
 * Laravel implementation of TransactionManager using DB facade
 */
final class LaravelTransactionManager implements TransactionManagerInterface
{
    private int $transactionLevel = 0;
    private array $savepoints = [];

    public function begin(): void
    {
        try {
            DB::beginTransaction();
            $this->transactionLevel++;
        } catch (\Throwable $e) {
            throw new TransactionException(
                "Failed to begin transaction: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function commit(): void
    {
        if ($this->transactionLevel === 0) {
            throw new TransactionException('No active transaction to commit');
        }

        try {
            DB::commit();
            $this->transactionLevel--;
            
            // Clear savepoints when transaction commits
            if ($this->transactionLevel === 0) {
                $this->savepoints = [];
            }
        } catch (\Throwable $e) {
            throw new TransactionException(
                "Failed to commit transaction: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function rollback(): void
    {
        if ($this->transactionLevel === 0) {
            throw new TransactionException('No active transaction to rollback');
        }

        try {
            DB::rollBack();
            $this->transactionLevel--;
            
            // Clear savepoints when transaction rolls back
            if ($this->transactionLevel === 0) {
                $this->savepoints = [];
            }
        } catch (\Throwable $e) {
            throw new TransactionException(
                "Failed to rollback transaction: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function savepoint(string $name): void
    {
        if ($this->transactionLevel === 0) {
            throw new TransactionException('Cannot create savepoint outside of transaction');
        }

        try {
            DB::statement("SAVEPOINT {$name}");
            $this->savepoints[] = $name;
        } catch (\Throwable $e) {
            throw new TransactionException(
                "Failed to create savepoint '{$name}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function rollbackToSavepoint(string $name): void
    {
        if (!in_array($name, $this->savepoints, true)) {
            throw new TransactionException("Savepoint '{$name}' does not exist");
        }

        try {
            DB::statement("ROLLBACK TO SAVEPOINT {$name}");
            
            // Remove this savepoint and all after it
            $index = array_search($name, $this->savepoints, true);
            $this->savepoints = array_slice($this->savepoints, 0, $index);
        } catch (\Throwable $e) {
            throw new TransactionException(
                "Failed to rollback to savepoint '{$name}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function inTransaction(): bool
    {
        return $this->transactionLevel > 0;
    }

    public function getTransactionLevel(): int
    {
        return $this->transactionLevel;
    }
}
