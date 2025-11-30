<?php

declare(strict_types=1);

namespace Nexus\Analytics\Core\Contracts;

use Nexus\Analytics\Contracts\QueryDefinitionInterface;
use Nexus\Analytics\Contracts\QueryResultInterface;
use Nexus\Analytics\Contracts\AnalyticsContextInterface;

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
     * @throws \Nexus\Analytics\Exceptions\QueryExecutionException
     * @throws \Nexus\Analytics\Exceptions\UnauthorizedQueryException
     */
    public function execute(QueryDefinitionInterface $query, AnalyticsContextInterface $context): QueryResultInterface;

    /**
     * Validate guard conditions before execution
     *
     * @param QueryDefinitionInterface $query
     * @param AnalyticsContextInterface $context
     * @throws \Nexus\Analytics\Exceptions\GuardConditionFailedException
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
