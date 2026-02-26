<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

/**
 * Cardinality Storage Interface
 *
 * Contract for storing and tracking unique tag value counts.
 * Typically implemented using Redis HyperLogLog for efficiency.
 *
 * @package Nexus\Telemetry\Contracts
 */
interface CardinalityStorageInterface
{
    /**
     * Increment counter for a tag value and return current count.
     *
     * @param string $tagKey Tag key (e.g., 'user_id')
     * @param string $tagValue Tag value to track
     * @return int Current unique value count for this tag key
     */
    public function incrementTagValue(string $tagKey, string $tagValue): int;

    /**
     * Get current cardinality (unique value count) for a tag key.
     *
     * @param string $tagKey Tag key to check
     * @return int Number of unique values
     */
    public function getCardinality(string $tagKey): int;
}
