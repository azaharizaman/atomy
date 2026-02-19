<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

/**
 * Transaction manager contract for sales operations.
 *
 * Manages database transaction boundaries for atomic operations.
 * Implemented in adapters using Laravel's database connection or similar.
 */
interface TransactionManagerInterface
{
    /**
     * Execute a callback within a database transaction.
     *
     * @template T
     * @param callable(): T $callback The operation to execute
     * @return T The result of the callback
     * @throws \Exception If the transaction fails
     */
    public function wrap(callable $callback): mixed;
}
