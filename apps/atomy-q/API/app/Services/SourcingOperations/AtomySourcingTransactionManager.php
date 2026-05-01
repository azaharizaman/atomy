<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use Closure;
use Illuminate\Support\Facades\DB;
use Nexus\SourcingOperations\Contracts\SourcingTransactionManagerInterface;

/**
 * Provides the transaction boundary for multi-write sourcing operations.
 *
 * Coordinators use this port so RFQ header, line-item, invitation, and lifecycle
 * changes can commit or roll back as one Laravel database transaction.
 */
final readonly class AtomySourcingTransactionManager implements SourcingTransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return DB::transaction(Closure::fromCallable($callback));
    }
}
