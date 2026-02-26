<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Contracts;

/**
 * Represents an analytics query definition
 */
interface QueryDefinitionInterface
{
    /**
     * Get the unique identifier for this query
     */
    public function getId(): string;

    /**
     * Get the query name
     */
    public function getName(): string;

    /**
     * Get the query type (e.g., 'aggregation', 'prediction', 'report')
     */
    public function getType(): string;

    /**
     * Get the query parameters
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array;

    /**
     * Get guard conditions that must pass before execution
     *
     * @return array<string, mixed>
     */
    public function getGuards(): array;

    /**
     * Get data source configurations
     *
     * @return array<string, mixed>
     */
    public function getDataSources(): array;

    /**
     * Check if this query requires ACID transaction
     */
    public function requiresTransaction(): bool;

    /**
     * Get the maximum execution timeout in seconds
     */
    public function getTimeout(): int;

    /**
     * Check if this query can be executed in parallel with others
     */
    public function supportsParallelExecution(): bool;
}
