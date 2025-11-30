<?php

declare(strict_types=1);

namespace Nexus\Audit\ValueObjects;

/**
 * Audit Log Severity Level Enum
 *
 * Native PHP enum representing audit log severity levels.
 * Satisfies: BUS-AUD-0146
 *
 * @package Nexus\Audit\ValueObjects
 */
enum AuditLevel: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;

    /**
     * Get human-readable label for the audit level
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
     * Check if this level should use synchronous logging
     * 
     * Critical and High events are logged synchronously for compliance.
     */
    public function requiresSyncLogging(): bool
    {
        return $this === self::Critical || $this === self::High;
    }

    /**
     * Check if this is a critical severity level
     */
    public function isCritical(): bool
    {
        return $this === self::Critical;
    }

    /**
     * Check if this is a high severity level
     */
    public function isHigh(): bool
    {
        return $this === self::High;
    }
}
