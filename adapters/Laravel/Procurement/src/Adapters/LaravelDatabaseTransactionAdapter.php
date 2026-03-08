<?php

declare(strict_types=1);

namespace Nexus\Adapters\Laravel\Procurement\Adapters;

use Illuminate\Support\Facades\DB;
use Nexus\Procurement\Contracts\DatabaseTransactionInterface;

/**
 * Laravel implementation of DatabaseTransactionInterface.
 * 
 * Delegates all operations to the Illuminate\Support\Facades\DB facade.
 */
final readonly class LaravelDatabaseTransactionAdapter implements DatabaseTransactionInterface
{
    /**
     * @inheritDoc
     */
    public function begin(): void
    {
        DB::beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commit(): void
    {
        DB::commit();
    }

    /**
     * @inheritDoc
     */
    public function rollback(): void
    {
        DB::rollBack();
    }

    /**
     * @inheritDoc
     */
    public function transactional(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
