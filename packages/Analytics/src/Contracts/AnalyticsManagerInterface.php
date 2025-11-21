<?php

declare(strict_types=1);

namespace Nexus\Analytics\Contracts;

/**
 * Analytics Manager Interface
 *
 * Main service for executing analytics queries.
 */
interface AnalyticsManagerInterface
{
    /**
     * Execute a named analytics query for a model
     *
     * @param string $queryName The query name to execute
     * @param string $modelType The model type (e.g., 'invoice', 'customer')
     * @param string $modelId The model instance ID
     * @param array<string, mixed> $parameters Query parameters
     * @return QueryResultInterface The query result
     * @throws \Nexus\Analytics\Exceptions\QueryNotFoundException
     * @throws \Nexus\Analytics\Exceptions\UnauthorizedQueryException
     */
    public function runQuery(
        string $queryName,
        string $modelType,
        string $modelId,
        array $parameters = []
    ): QueryResultInterface;
}
