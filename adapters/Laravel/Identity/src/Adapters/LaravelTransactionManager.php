<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use Nexus\IdentityOperations\Contracts\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

/**
 * Laravel implementation of transaction management.
 */
final readonly class LaravelTransactionManager implements TransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
