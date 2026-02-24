<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

/**
 * Database Transaction Interface
 * 
 * Interface for managing database transactions in a framework-agnostic way.
 */
interface DatabaseTransactionInterface
{
    /**
     * Start a new transaction
     */
    public function begin(): void;

    /**
     * Commit the current transaction
     */
    public function commit(): void;

    /**
     * Roll back the current transaction
     */
    public function rollback(): void;

    /**
     * Execute a callback within a transaction
     * 
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transactional(callable $callback): mixed;
}
