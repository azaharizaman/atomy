<?php

declare(strict_types=1);

namespace Nexus\Compliance\ValueObjects;

/**
 * Immutable value object representing compliance severity levels.
 */
enum SeverityLevel: string
{
    case CRITICAL = 'Critical';
    case HIGH = 'High';
    case MEDIUM = 'Medium';
    case LOW = 'Low';

    /**
     * Get the numeric priority for the severity level.
     * Higher number = higher priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::CRITICAL => 4,
            self::HIGH => 3,
            self::MEDIUM => 2,
            self::LOW => 1,
        };
    }

    /**
     * Check if this severity level requires immediate action.
     *
     * @return bool
     */
    public function requiresImmediateAction(): bool
    {
        return $this === self::CRITICAL || $this === self::HIGH;
    }

    /**
     * Get all severity levels ordered by priority (highest first).
     *
     * @return array<self>
     */
    public static function orderByPriority(): array
    {
        return [
            self::CRITICAL,
            self::HIGH,
            self::MEDIUM,
            self::LOW,
        ];
    }
}
