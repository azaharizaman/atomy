<?php

declare(strict_types=1);

namespace Nexus\Telemetry\ValueObjects;

/**
 * Health Status Enumeration
 *
 * Defines the five health status levels with severity weights for prioritization.
 * Lower weights indicate better health.
 *
 * @package Nexus\Telemetry\ValueObjects
 */
enum HealthStatus: string
{
    /**
     * Healthy: System operating normally
     * Severity Weight: 0 (lowest)
     */
    case HEALTHY = 'healthy';

    /**
     * Warning: Minor degradation, no user impact
     * Severity Weight: 25
     */
    case WARNING = 'warning';

    /**
     * Degraded: Significant degradation, partial functionality
     * Severity Weight: 50
     */
    case DEGRADED = 'degraded';

    /**
     * Critical: Major failure, service impacted
     * Severity Weight: 75
     */
    case CRITICAL = 'critical';

    /**
     * Offline: Complete failure, service unavailable
     * Severity Weight: 100 (highest)
     */
    case OFFLINE = 'offline';

    /**
     * Get numerical severity weight for this health status.
     * Used for prioritization and aggregation logic.
     *
     * @return int Weight from 0 (best) to 100 (worst)
     */
    public function getSeverityWeight(): int
    {
        return match ($this) {
            self::HEALTHY => 0,
            self::WARNING => 25,
            self::DEGRADED => 50,
            self::CRITICAL => 75,
            self::OFFLINE => 100,
        };
    }

    /**
     * Check if this status represents a healthy state.
     *
     * @return bool True only for HEALTHY status
     */
    public function isHealthy(): bool
    {
        return $this === self::HEALTHY;
    }

    /**
     * Check if this status is critical or worse.
     *
     * @return bool True for CRITICAL or OFFLINE
     */
    public function isCritical(): bool
    {
        return match ($this) {
            self::CRITICAL, self::OFFLINE => true,
            default => false,
        };
    }

    /**
     * Get human-readable label for this health status.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::HEALTHY => 'Healthy',
            self::WARNING => 'Warning',
            self::DEGRADED => 'Degraded',
            self::CRITICAL => 'Critical',
            self::OFFLINE => 'Offline',
        };
    }
}
