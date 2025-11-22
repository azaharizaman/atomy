<?php

declare(strict_types=1);

namespace Nexus\Analytics\Contracts;

/**
 * Main entry point for the Analytics package.
 * 
 * Provides high-level orchestration for analytics queries, predictions,
 * and business intelligence operations.
 */
interface AnalyticsManagerInterface
{
    /**
     * Execute an analytics query.
     *
     * @param QueryDefinitionInterface $query The query to execute
     * @return QueryResultInterface The query result
     */
    public function executeQuery(QueryDefinitionInterface $query): QueryResultInterface;

    /**
     * Save a query definition for later execution.
     *
     * @param QueryDefinitionInterface $query The query to save
     * @return string The saved query ID
     */
    public function saveQuery(QueryDefinitionInterface $query): string;

    /**
     * Retrieve a saved query by ID.
     *
     * @param string $queryId The query ID
     * @return QueryDefinitionInterface|null The query definition or null if not found
     */
    public function getQuery(string $queryId): ?QueryDefinitionInterface;

    /**
     * Delete a saved query.
     *
     * @param string $queryId The query ID to delete
     * @return bool True if deleted, false if not found
     */
    public function deleteQuery(string $queryId): bool;

    /**
     * List all saved queries.
     *
     * @return array<QueryDefinitionInterface> Array of query definitions
     */
    public function listQueries(): array;
}
