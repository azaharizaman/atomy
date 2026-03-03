<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Services;

use Nexus\QueryEngine\Contracts\QueryDefinitionInterface;
use Nexus\QueryEngine\Contracts\QueryResultInterface;

/**
 * Forward-facing alias for AnalyticsManager to reduce naming drift.
 *
 * This class preserves existing behavior while enabling new integrations
 * to adopt query-centric naming.
 */
final readonly class QueryManager
{
    public function __construct(private AnalyticsManager $analyticsManager) {}

    /**
     * @param array<string, mixed> $parameters
     */
    public function runQuery(string $queryName, string $modelType, string $modelId, array $parameters = []): QueryResultInterface
    {
        return $this->analyticsManager->runQuery($queryName, $modelType, $modelId, $parameters);
    }

    public function executeQuery(QueryDefinitionInterface $query): QueryResultInterface
    {
        return $this->analyticsManager->executeQuery($query);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(string $modelType, string $modelId, int $limit = 50): array
    {
        return $this->analyticsManager->getHistory($modelType, $modelId, $limit);
    }

    /**
     * @param array<string, mixed> $queryData
     */
    public function registerQuery(string $modelType, string $modelId, array $queryData): string
    {
        return $this->analyticsManager->registerQuery($modelType, $modelId, $queryData);
    }
}
