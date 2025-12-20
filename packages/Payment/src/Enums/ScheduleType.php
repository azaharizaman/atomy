<?php

declare(strict_types=1);

namespace Nexus\Payment\Enums;

/**
 * Enum representing disbursement scheduling types.
 *
 * Used for PAY-034: Disbursement scheduling support.
 */
enum ScheduleType: string
{
    /**
     * Process immediately when approved.
     */
    case IMMEDIATE = 'immediate';

    /**
     * Process on a specific future date.
     */
    case SCHEDULED = 'scheduled';

    /**
     * Process on a recurring schedule.
     */
    case RECURRING = 'recurring';

    /**
     * Check if this schedule type requires a scheduled date.
     */
    public function requiresScheduledDate(): bool
    {
        return match ($this) {
            self::IMMEDIATE => false,
            self::SCHEDULED, self::RECURRING => true,
        };
    }

    /**
     * Check if this schedule type supports recurrence.
     */
    public function supportsRecurrence(): bool
    {
        return $this === self::RECURRING;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::IMMEDIATE => 'Immediate',
            self::SCHEDULED => 'Scheduled',
            self::RECURRING => 'Recurring',
        };
    }
}
