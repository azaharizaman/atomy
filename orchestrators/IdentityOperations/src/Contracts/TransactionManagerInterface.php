<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

/**
 * Interface for transaction management.
 */
interface TransactionManagerInterface
{
    /**
     * Execute a callback within a database transaction.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;
}
