<?php

declare(strict_types=1);

namespace Nexus\Monitoring\ValueObjects;

/**
 * Alert Severity Enumeration
 *
 * Defines the three alert severity levels for incident management.
 *
 * @package Nexus\Monitoring\ValueObjects
 */
enum AlertSeverity: string
{
    /**
     * Info: Informational alert, no action required
     */
    case INFO = 'info';

    /**
     * Warning: Non-critical issue, review recommended
     */
    case WARNING = 'warning';

    /**
     * Critical: Critical issue, immediate action required
     */
    case CRITICAL = 'critical';

    /**
     * Get human-readable label for this alert severity.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Info',
            self::WARNING => 'Warning',
            self::CRITICAL => 'Critical',
        };
    }

    /**
     * Get numerical priority for this severity (higher = more urgent).
     *
     * @return int
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::INFO => 10,
            self::WARNING => 50,
            self::CRITICAL => 100,
        };
    }

    /**
     * Check if this severity requires immediate notification.
     *
     * @return bool
     */
    public function requiresImmediateNotification(): bool
    {
        return $this === self::CRITICAL;
    }
}
