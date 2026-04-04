<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Contracts;

interface SourcingTransactionManagerInterface
{
    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function transaction(callable $callback): mixed;
}
