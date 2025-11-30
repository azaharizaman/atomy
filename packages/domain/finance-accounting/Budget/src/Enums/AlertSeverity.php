<?php

declare(strict_types=1);

namespace Nexus\Budget\Enums;

/**
 * Alert Severity enum
 * 
 * Classifies budget alert severity levels.
 */
enum AlertSeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';

    /**
     * Get severity rank (higher = more severe)
     */
    public function getRank(): int
    {
        return match($this) {
            self::Info => 1,
            self::Warning => 2,
            self::Critical => 3,
        };
    }

    /**
     * Check if this severity requires immediate action
     */
    public function requiresImmediateAction(): bool
    {
        return $this === self::Critical;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Info => 'Information',
            self::Warning => 'Warning',
            self::Critical => 'Critical',
        };
    }

    /**
     * Get color code for UI display
     */
    public function colorCode(): string
    {
        return match($this) {
            self::Info => 'blue',
            self::Warning => 'yellow',
            self::Critical => 'red',
        };
    }
}
