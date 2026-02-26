<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

use Nexus\Telemetry\ValueObjects\Metric;

/**
 * Sampling Strategy Interface
 *
 * Contract for determining whether a metric should be sampled (recorded).
 * Used to reduce storage costs for high-volume metrics.
 *
 * @package Nexus\Telemetry\Contracts
 */
interface SamplingStrategyInterface
{
    /**
     * Determine if a metric should be sampled (recorded).
     *
     * @param Metric $metric Metric to evaluate
     * @return bool True if metric should be recorded, false to skip
     */
    public function shouldSample(Metric $metric): bool;
}
