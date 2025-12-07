<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Saga execution status.
 */
enum SagaStatus: string
{
    case PENDING = 'pending';
    case EXECUTING = 'executing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case COMPENSATING = 'compensating';
    case COMPENSATED = 'compensated';
    case COMPENSATION_FAILED = 'compensation_failed';

    /**
     * Check if status is terminal (no further transitions).
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::COMPENSATED, self::COMPENSATION_FAILED => true,
            default => false,
        };
    }

    /**
     * Check if saga was compensated (rolled back).
     */
    public function wasCompensated(): bool
    {
        return match ($this) {
            self::COMPENSATING, self::COMPENSATED, self::COMPENSATION_FAILED => true,
            default => false,
        };
    }

    /**
     * Check if compensation completed successfully.
     */
    public function isCompensationComplete(): bool
    {
        return $this === self::COMPENSATED;
    }

    /**
     * Check if status indicates failure.
     */
    public function isFailed(): bool
    {
        return match ($this) {
            self::FAILED, self::COMPENSATION_FAILED => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::EXECUTING => 'Executing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::COMPENSATING => 'Compensating',
            self::COMPENSATED => 'Compensated (Rolled Back)',
            self::COMPENSATION_FAILED => 'Compensation Failed',
        };
    }
}
