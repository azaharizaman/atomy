<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Enums;

/**
 * Status of a saga execution.
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
    case CANCELLED = 'cancelled';

    /**
     * Check if this is a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::COMPENSATED,
            self::COMPENSATION_FAILED,
            self::CANCELLED,
        ], true);
    }

    /**
     * Check if saga was successful.
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if compensation occurred.
     */
    public function hasCompensation(): bool
    {
        return in_array($this, [
            self::COMPENSATING,
            self::COMPENSATED,
            self::COMPENSATION_FAILED,
        ], true);
    }
}
