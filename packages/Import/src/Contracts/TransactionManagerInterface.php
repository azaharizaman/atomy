<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

/**
 * Transaction manager contract
 * 
 * Manages database transaction boundaries for import operations.
 * Implemented in Atomy using Laravel's database connection.
 */
interface TransactionManagerInterface
{
    /**
     * Start a new transaction
     * 
     * @throws \RuntimeException If transaction cannot be started
     */
    public function begin(): void;

    /**
     * Commit the current transaction
     * 
     * @throws \RuntimeException If no active transaction
     */
    public function commit(): void;

    /**
     * Rollback the current transaction
     * 
     * @throws \RuntimeException If no active transaction
     */
    public function rollback(): void;

    /**
     * Create a savepoint within the current transaction
     * 
     * @param string $name Savepoint name
     * @throws \RuntimeException If no active transaction
     */
    public function savepoint(string $name): void;

    /**
     * Rollback to a specific savepoint
     * 
     * @param string $name Savepoint name
     * @throws \RuntimeException If savepoint doesn't exist
     */
    public function rollbackToSavepoint(string $name): void;

    /**
     * Check if currently in a transaction
     */
    public function inTransaction(): bool;

    /**
     * Get current transaction nesting level
     * 
     * @return int 0 if not in transaction, >0 for nested transactions
     */
    public function getTransactionLevel(): int;
}
