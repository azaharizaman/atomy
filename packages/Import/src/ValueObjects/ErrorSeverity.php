<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Error severity level enumeration
 * 
 * Categorizes import errors by their impact on data integrity.
 */
enum ErrorSeverity: string
{
    case WARNING = 'warning';   // Non-critical issue, row can be imported
    case ERROR = 'error';       // Critical issue, row must be skipped
    case CRITICAL = 'critical'; // System-level failure, import should halt

    /**
     * Check if severity should skip the row
     */
    public function shouldSkipRow(): bool
    {
        return match($this) {
            self::ERROR, self::CRITICAL => true,
            self::WARNING => false,
        };
    }

    /**
     * Check if severity should halt the import
     */
    public function shouldHaltImport(): bool
    {
        return match($this) {
            self::CRITICAL => true,
            self::ERROR, self::WARNING => false,
        };
    }

    /**
     * Get numeric priority (higher = more severe)
     */
    public function getPriority(): int
    {
        return match($this) {
            self::WARNING => 1,
            self::ERROR => 2,
            self::CRITICAL => 3,
        };
    }

    /**
     * Get color code for UI display
     */
    public function getColorCode(): string
    {
        return match($this) {
            self::WARNING => '#FFA500',  // Orange
            self::ERROR => '#DC3545',    // Red
            self::CRITICAL => '#8B0000', // Dark Red
        };
    }
}
