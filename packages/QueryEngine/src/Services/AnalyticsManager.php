<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Services;

use Nexus\QueryEngine\Contracts\AnalyticsRepositoryInterface;
use Nexus\QueryEngine\Contracts\AnalyticsAuthorizerInterface;
use Nexus\QueryEngine\Contracts\AnalyticsContextInterface;
use Nexus\QueryEngine\Contracts\QueryDefinitionInterface;
use Nexus\QueryEngine\Contracts\QueryResultInterface;
use Nexus\QueryEngine\Core\Contracts\QueryExecutorInterface;
use Nexus\QueryEngine\ValueObjects\QueryDefinition;
use Nexus\QueryEngine\Exceptions\QueryNotFoundException;
use Nexus\QueryEngine\Exceptions\UnauthorizedQueryException;
use Nexus\QueryEngine\Exceptions\AnalyticsInstanceNotFoundException;

/**
 * Main analytics service orchestrator
 * 
 * Coordinates query execution, permission checking, and history management
 */
final readonly class AnalyticsManager
{
    public function __construct(
        private AnalyticsRepositoryInterface $repository,
        private AnalyticsAuthorizerInterface $authorizer,
        private QueryExecutorInterface $executor,
        private AnalyticsContextInterface $context
    ) {
    }

    /**
     * Run a named query for a model
     *
     * @param string $queryName
     * @param string $modelType
     * @param string $modelId
     * @param array<string, mixed> $parameters
     * @return QueryResultInterface
     * @throws QueryNotFoundException
     * @throws UnauthorizedQueryException
     */
    public function runQuery(string $queryName, string $modelType, string $modelId, array $parameters = []): QueryResultInterface
    {
        // Find query definition
        $definitions = $this->repository->findQueryDefinitionsByModel($modelType, $modelId);
        $queryData = null;

        foreach ($definitions as $def) {
            if ($def['name'] === $queryName) {
                $queryData = $def;
                break;
            }
        }

        if ($queryData === null) {
            throw new QueryNotFoundException($queryName);
        }

        $query = QueryDefinition::fromArray(array_merge($queryData, ['parameters' => $parameters]));

        // Check authorization
        $userId = $this->context->getUserId() ?? 'system';
        if (!$this->authorizer->can($userId, 'execute', $query->getId())) {
            throw new UnauthorizedQueryException($userId, $query->getId());
        }

        // Execute query
        $result = $this->executor->executeWithRetry($query, $this->context);

        // Store result in history
        $this->repository->storeQueryResult([
            'query_id' => $query->getId(),
            'query_name' => $query->getName(),
            'model_type' => $modelType,
            'model_id' => $modelId,
            'executed_by' => $userId,
            'executed_at' => $result->getExecutedAt()->format('Y-m-d H:i:s'),
            'duration_ms' => $result->getDurationMs(),
            'is_successful' => $result->isSuccessful(),
            'error' => $result->getError(),
            'result_data' => $result->getData(),
        ]);

        return $result;
    }

    /**
     * Check if user can perform an action on a query
     *
     * @param string $action
     * @param string $queryId
     */
    public function can(string $action, string $queryId): bool
    {
        $userId = $this->context->getUserId() ?? 'system';
        return $this->authorizer->can($userId, $action, $queryId);
    }

    /**
     * Get analytics execution history for a model
     *
     * @param string $modelType
     * @param string $modelId
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(string $modelType, string $modelId, int $limit = 50): array
    {
        return $this->repository->getHistory($modelType, $modelId, $limit);
    }

    /**
     * Register a new query definition for a model
     *
     * @param string $modelType
     * @param string $modelId
     * @param array<string, mixed> $queryData
     * @return string Query ID
     */
    public function registerQuery(string $modelType, string $modelId, array $queryData): string
    {
        $data = array_merge($queryData, [
            'model_type' => $modelType,
            'model_id' => $modelId,
        ]);

        return $this->repository->storeQueryDefinition($data);
    }

    /**
     * Get or create analytics instance for a model
     *
     * @param string $modelType
     * @param string $modelId
     * @return array<string, mixed>
     */
    public function getOrCreateInstance(string $modelType, string $modelId): array
    {
        $instance = $this->repository->findAnalyticsInstance($modelType, $modelId);

        if ($instance !== null) {
            return $instance;
        }

        // Create new instance
        $instanceId = $this->repository->storeAnalyticsInstance([
            'model_type' => $modelType,
            'model_id' => $modelId,
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'created_by' => $this->context->getUserId() ?? 'system',
        ]);

        return [
            'id' => $instanceId,
            'model_type' => $modelType,
            'model_id' => $modelId,
        ];
    }

    /**
     * Execute a query definition directly
     *
     * @param QueryDefinitionInterface $query
     * @return QueryResultInterface
     */
    public function executeQuery(QueryDefinitionInterface $query): QueryResultInterface
    {
        $userId = $this->context->getUserId() ?? 'system';
        
        if (!$this->authorizer->can($userId, 'execute', $query->getId())) {
            throw new UnauthorizedQueryException($userId, $query->getId());
        }

        return $this->executor->execute($query, $this->context);
    }

    /**
     * Get all permissions for current user on a query
     *
     * @param string $queryId
     * @return array<int, string>
     */
    public function getPermissions(string $queryId): array
    {
        $userId = $this->context->getUserId() ?? 'system';
        return $this->authorizer->getPermissions($userId, $queryId);
    }
}
