<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Enums;

enum SagaStatus: string
{
    case PENDING = 'pending';
    case EXECUTING = 'executing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case COMPENSATING = 'compensating';
    case COMPENSATED = 'compensated';
    case COMPENSATION_FAILED = 'compensation_failed';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::COMPENSATED, self::COMPENSATION_FAILED => true,
            default => false,
        };
    }

    public function wasCompensated(): bool
    {
        return match ($this) {
            self::COMPENSATING, self::COMPENSATED, self::COMPENSATION_FAILED => true,
            default => false,
        };
    }

    public function isCompensationComplete(): bool
    {
        return $this === self::COMPENSATED;
    }

    public function isFailed(): bool
    {
        return match ($this) {
            self::FAILED, self::COMPENSATION_FAILED => true,
            default => false,
        };
    }

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
