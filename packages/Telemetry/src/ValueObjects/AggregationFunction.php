<?php

declare(strict_types=1);

namespace Nexus\Telemetry\ValueObjects;

/**
 * Aggregation Function Enumeration
 *
 * Defines supported metric aggregation functions for time-series queries.
 * Functions are classified as standard (SQL-native) or custom (TSDB-specific).
 *
 * @package Nexus\Telemetry\ValueObjects
 */
enum AggregationFunction: string
{
    /** Average value */
    case AVG = 'avg';

    /** Sum of values */
    case SUM = 'sum';

    /** Minimum value */
    case MIN = 'min';

    /** Maximum value */
    case MAX = 'max';

    /** Count of values */
    case COUNT = 'count';

    /** 50th percentile (median) */
    case P50 = 'p50';

    /** 95th percentile */
    case P95 = 'p95';

    /** 99th percentile */
    case P99 = 'p99';

    /** Standard deviation */
    case STDDEV = 'stddev';

    /** Rate of change per second */
    case RATE = 'rate';

    /** Exponential Weighted Moving Average (TSDB-specific) */
    case EWMA = 'ewma';

    /**
     * Check if this is a standard SQL-compatible aggregation function.
     * Standard functions can be computed by basic SQL databases.
     *
     * @return bool
     */
    public function isStandard(): bool
    {
        return match ($this) {
            self::AVG, self::SUM, self::MIN, self::MAX, self::COUNT => true,
            default => false,
        };
    }

    /**
     * Check if this is a percentile function.
     *
     * @return bool
     */
    public function isPercentile(): bool
    {
        return match ($this) {
            self::P50, self::P95, self::P99 => true,
            default => false,
        };
    }

    /**
     * Get human-readable label for this function.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::AVG => 'Average',
            self::SUM => 'Sum',
            self::MIN => 'Minimum',
            self::MAX => 'Maximum',
            self::COUNT => 'Count',
            self::P50 => 'P50 (Median)',
            self::P95 => 'P95',
            self::P99 => 'P99',
            self::STDDEV => 'Standard Deviation',
            self::RATE => 'Rate',
            self::EWMA => 'Exponential Weighted Moving Average',
        };
    }
}
