<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Severity levels for policy violations.
 */
enum PolicyViolationSeverity: string
{
    /**
     * Informational - no action required
     */
    case INFO = 'info';

    /**
     * Warning - review recommended but transaction can proceed
     */
    case WARNING = 'warning';

    /**
     * Error - transaction should not proceed without override
     */
    case ERROR = 'error';

    /**
     * Critical - transaction must be blocked
     */
    case CRITICAL = 'critical';

    /**
     * Get human-readable label for this severity.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::INFO => 'Information',
            self::WARNING => 'Warning',
            self::ERROR => 'Error',
            self::CRITICAL => 'Critical',
        };
    }

    /**
     * Check if this severity level blocks transactions.
     */
    public function isBlocking(): bool
    {
        return match ($this) {
            self::INFO,
            self::WARNING => false,
            self::ERROR,
            self::CRITICAL => true,
        };
    }

    /**
     * Check if this severity level requires override approval.
     */
    public function requiresOverride(): bool
    {
        return match ($this) {
            self::INFO => false,
            self::WARNING,
            self::ERROR,
            self::CRITICAL => true,
        };
    }

    /**
     * Get numeric weight for sorting/comparison.
     */
    public function getWeight(): int
    {
        return match ($this) {
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4,
        };
    }
}
