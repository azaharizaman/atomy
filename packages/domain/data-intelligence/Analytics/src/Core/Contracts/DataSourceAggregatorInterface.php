<?php

declare(strict_types=1);

namespace Nexus\Analytics\Core\Contracts;

/**
 * Internal interface for data source aggregation
 */
interface DataSourceAggregatorInterface
{
    /**
     * Merge data from multiple parallel sources
     *
     * @param array<int, array<string, mixed>> $sources Source configurations
     * @param array<string, mixed> $context Execution context
     * @return array<string, mixed> Merged result
     * @throws \Nexus\Analytics\Exceptions\DataSourceException
     */
    public function aggregateParallel(array $sources, array $context): array;

    /**
     * Check if a data source is available
     *
     * @param array<string, mixed> $source Source configuration
     */
    public function isSourceAvailable(array $source): bool;

    /**
     * Fetch data from a single source with timeout
     *
     * @param array<string, mixed> $source Source configuration
     * @param int $timeoutSeconds
     * @return array<string, mixed>
     */
    public function fetchFromSource(array $source, int $timeoutSeconds = 30): array;
}
