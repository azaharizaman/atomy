<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Enums;

/**
 * Severity level for anomaly detection
 */
enum SeverityLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function isLow(): bool
    {
        return $this === self::LOW;
    }

    public function isMedium(): bool
    {
        return $this === self::MEDIUM;
    }

    public function isHigh(): bool
    {
        return $this === self::HIGH;
    }

    public function isCritical(): bool
    {
        return $this === self::CRITICAL;
    }

    public function getNumericValue(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 4,
        };
    }
}
