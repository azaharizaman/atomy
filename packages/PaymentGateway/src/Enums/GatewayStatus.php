<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Enums;

/**
 * Health status of a payment gateway.
 */
enum GatewayStatus: string
{
    case HEALTHY = 'healthy';
    case DEGRADED = 'degraded';
    case UNHEALTHY = 'unhealthy';
    case MAINTENANCE = 'maintenance';
    case UNKNOWN = 'unknown';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::HEALTHY => 'Healthy',
            self::DEGRADED => 'Degraded',
            self::UNHEALTHY => 'Unhealthy',
            self::MAINTENANCE => 'Under Maintenance',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Check if gateway is operational (can process payments).
     */
    public function isOperational(): bool
    {
        return in_array($this, [
            self::HEALTHY,
            self::DEGRADED,
        ], true);
    }

    /**
     * Check if gateway should be used as fallback only.
     */
    public function isBackupOnly(): bool
    {
        return $this === self::DEGRADED;
    }
}
