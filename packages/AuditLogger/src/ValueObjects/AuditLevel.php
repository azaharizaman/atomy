<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\ValueObjects;

/**
 * Audit Log Severity Level Enum
 *
 * Native PHP enum representing audit log severity levels.
 * Satisfies: BUS-AUD-0146
 *
 * @package Nexus\AuditLogger\ValueObjects
 * @see https://www.php.net/manual/en/language.enumerations.backed.php
 */
enum AuditLevel: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;

    /**
     * Get human-readable label for the audit level
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    /**
     * Check if this is a low severity level
     *
     * @return bool
     */
    public function isLow(): bool
    {
        return $this === self::Low;
    }

    /**
     * Check if this is a medium severity level
     *
     * @return bool
     */
    public function isMedium(): bool
    {
        return $this === self::Medium;
    }

    /**
     * Check if this is a high severity level
     *
     * @return bool
     */
    public function isHigh(): bool
    {
        return $this === self::High;
    }

    /**
     * Check if this is a critical severity level
     *
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this === self::Critical;
    }
}
