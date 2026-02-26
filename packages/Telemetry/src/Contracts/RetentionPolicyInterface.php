<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

/**
 * Retention Policy Interface
 *
 * Contract for defining metric retention periods by metric key.
 *
 * @package Nexus\Telemetry\Contracts
 */
interface RetentionPolicyInterface
{
    /**
     * Get retention period in days for a specific metric key.
     *
     * @param string $metricKey Metric name or pattern
     * @return int Number of days to retain the metric
     */
    public function getRetentionDays(string $metricKey): int;
}
