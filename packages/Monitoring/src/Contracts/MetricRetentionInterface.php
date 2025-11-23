<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Contracts;

/**
 * MetricRetentionInterface
 *
 * Defines retention policy for metric data cleanup.
 *
 * @package Nexus\Monitoring\Contracts
 */
interface MetricRetentionInterface
{
    /**
     * Get retention period in seconds.
     *
     * @return int
     */
    public function getRetentionPeriod(): int;

    /**
     * Check if metric should be retained.
     *
     * @param string $metricKey
     * @param int $timestamp
     * @return bool
     */
    public function shouldRetain(string $metricKey, int $timestamp): bool;
}
