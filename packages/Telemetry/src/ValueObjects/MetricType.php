<?php

declare(strict_types=1);

namespace Nexus\Telemetry\ValueObjects;

/**
 * Metric Type Enumeration
 *
 * Defines the four core metric types supported by the monitoring system.
 * Each type represents a different way of measuring and aggregating data.
 *
 * @package Nexus\Telemetry\ValueObjects
 */
enum MetricType: string
{
    /**
     * Counter: Monotonically increasing value
     * Used for: Total requests, errors, items processed
     */
    case COUNTER = 'counter';

    /**
     * Gauge: Instantaneous point-in-time value
     * Used for: Current queue size, active connections, memory usage
     */
    case GAUGE = 'gauge';

    /**
     * Timing: Duration measurement in milliseconds
     * Used for: API latency, database query time, processing duration
     */
    case TIMING = 'timing';

    /**
     * Histogram: Distribution of values across buckets
     * Used for: Response time distribution, size distribution
     */
    case HISTOGRAM = 'histogram';

    /**
     * Check if this metric type represents a numeric value.
     * All current metric types are numeric.
     *
     * @return bool
     */
    public function isNumeric(): bool
    {
        return match ($this) {
            self::COUNTER, self::GAUGE, self::TIMING, self::HISTOGRAM => true,
        };
    }

    /**
     * Get human-readable label for this metric type.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::COUNTER => 'Counter',
            self::GAUGE => 'Gauge',
            self::TIMING => 'Timing',
            self::HISTOGRAM => 'Histogram',
        };
    }
}
