<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Core\Contracts;

/**
 * Internal interface for transaction management
 */
interface TransactionManagerInterface
{
    /**
     * Execute callable within an ACID transaction
     *
     * @param callable $callback
     * @return mixed
     * @throws \Nexus\QueryEngine\Exceptions\TransactionException
     */
    public function executeInTransaction(callable $callback): mixed;

    /**
     * Begin a new transaction
     *
     * @throws \Nexus\QueryEngine\Exceptions\TransactionException
     */
    public function begin(): void;

    /**
     * Commit the current transaction
     *
     * @throws \Nexus\QueryEngine\Exceptions\TransactionException
     */
    public function commit(): void;

    /**
     * Rollback the current transaction
     *
     * @throws \Nexus\QueryEngine\Exceptions\TransactionException
     */
    public function rollback(): void;

    /**
     * Execute compensation action for failed query
     *
     * @param string $queryId
     * @param array<string, mixed> $failureContext
     */
    public function compensate(string $queryId, array $failureContext): void;
}
