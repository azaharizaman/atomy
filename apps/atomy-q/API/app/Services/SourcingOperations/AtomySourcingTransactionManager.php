<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use Illuminate\Support\Facades\DB;
use Nexus\SourcingOperations\Contracts\SourcingTransactionManagerInterface;

final readonly class AtomySourcingTransactionManager implements SourcingTransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
