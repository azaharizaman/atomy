<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sales\Adapters;

use Illuminate\Support\Facades\DB;
use Nexus\Sales\Contracts\TransactionManagerInterface;

/**
 * Laravel implementation of the Sales transaction manager.
 *
 * Uses Laravel's DB facade for transaction management.
 */
final readonly class TransactionManagerAdapter implements TransactionManagerInterface
{
    /**
     * Execute a callback within a database transaction.
     *
     * @template T
     * @param callable(): T $callback The operation to execute
     * @return T The result of the callback
     * @throws \Exception If the transaction fails
     */
    public function wrap(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
