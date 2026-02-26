<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Core\Contracts;

use Nexus\QueryEngine\Contracts\QueryDefinitionInterface;
use Nexus\QueryEngine\Contracts\QueryResultInterface;
use Nexus\QueryEngine\Contracts\AnalyticsContextInterface;

/**
 * Internal interface for query execution engine
 */
interface QueryExecutorInterface
{
    /**
     * Execute a query definition
     *
     * @param QueryDefinitionInterface $query
     * @param AnalyticsContextInterface $context
     * @return QueryResultInterface
     * @throws \Nexus\QueryEngine\Exceptions\QueryExecutionException
     * @throws \Nexus\QueryEngine\Exceptions\UnauthorizedQueryException
     */
    public function execute(QueryDefinitionInterface $query, AnalyticsContextInterface $context): QueryResultInterface;

    /**
     * Validate guard conditions before execution
     *
     * @param QueryDefinitionInterface $query
     * @param AnalyticsContextInterface $context
     * @throws \Nexus\QueryEngine\Exceptions\GuardConditionFailedException
     */
    public function validateGuards(QueryDefinitionInterface $query, AnalyticsContextInterface $context): bool;

    /**
     * Execute query with retry logic for transient failures
     *
     * @param QueryDefinitionInterface $query
     * @param AnalyticsContextInterface $context
     * @param int $maxRetries
     * @return QueryResultInterface
     */
    public function executeWithRetry(
        QueryDefinitionInterface $query,
        AnalyticsContextInterface $context,
        int $maxRetries = 3
    ): QueryResultInterface;
}
