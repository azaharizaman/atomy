<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

/**
 * Cardinality Guard Interface
 *
 * Contract for protecting against high-cardinality metric tags that can
 * explode TSDB storage costs and performance.
 *
 * @package Nexus\Telemetry\Contracts
 */
interface CardinalityGuardInterface
{
    /**
     * Validate tag cardinality before storing metric.
     * Throws exception if cardinality limit exceeded.
     *
     * @param array<string, scalar> $tags Tags to validate
     * @return void
     * @throws \Nexus\Telemetry\Exceptions\CardinalityLimitExceededException
     */
    public function validateTags(array $tags): void;

    /**
     * Get current cardinality for a specific tag key.
     *
     * @param string $tagKey Tag key to check
     * @return int Number of unique values
     */
    public function getCardinality(string $tagKey): int;
}
