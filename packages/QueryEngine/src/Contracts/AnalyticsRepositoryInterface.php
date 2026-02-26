<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Contracts;

/**
 * Repository interface for persisting analytics definitions and results
 */
interface AnalyticsRepositoryInterface
{
    /**
     * Store a query definition
     *
     * @param array<string, mixed> $data
     */
    public function storeQueryDefinition(array $data): string;

    /**
     * Find a query definition by ID
     *
     * @return array<string, mixed>|null
     */
    public function findQueryDefinition(string $id): ?array;

    /**
     * Find query definitions by model type
     *
     * @return array<int, array<string, mixed>>
     */
    public function findQueryDefinitionsByModel(string $modelType, string $modelId): array;

    /**
     * Store a query execution result
     *
     * @param array<string, mixed> $data
     */
    public function storeQueryResult(array $data): string;

    /**
     * Get analytics history for a specific entity
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(string $entityType, string $entityId, int $limit = 50): array;

    /**
     * Store analytics instance metadata
     *
     * @param array<string, mixed> $data
     */
    public function storeAnalyticsInstance(array $data): string;

    /**
     * Find analytics instance by model
     *
     * @return array<string, mixed>|null
     */
    public function findAnalyticsInstance(string $modelType, string $modelId): ?array;

    /**
     * Update analytics instance metadata
     *
     * @param array<string, mixed> $data
     */
    public function updateAnalyticsInstance(string $id, array $data): bool;

    /**
     * Delete a query definition
     */
    public function deleteQueryDefinition(string $id): bool;
}
