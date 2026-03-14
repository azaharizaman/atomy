<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Services;

use Nexus\TimeTracking\Exceptions\InvalidHoursException;

/**
 * Validates timesheet hours (BUS-PRO-0056): not negative, not exceeding 24h per user per day.
 */
final readonly class HoursValidator
{
    private const float MAX_HOURS_PER_DAY = 24.0;

    /**
     * Validate hours for a single entry.
     *
     * @throws InvalidHoursException
     */
    public function validateEntry(float $hours): void
    {
        if ($hours < 0) {
            throw InvalidHoursException::negative($hours);
        }
        if ($hours > self::MAX_HOURS_PER_DAY) {
            throw InvalidHoursException::exceedsMax($hours, self::MAX_HOURS_PER_DAY);
        }
    }

    /**
     * Validate that adding these hours to existing total for user+date does not exceed 24.
     *
     * @param float $existingTotal Sum of hours already logged for this user on this date.
     * @param float $additionalHours Hours to add.
     * @throws InvalidHoursException
     */
    public function validateDailyTotal(float $existingTotal, float $additionalHours): void
    {
        $this->validateEntry($additionalHours);
        $newTotal = $existingTotal + $additionalHours;
        if ($newTotal > self::MAX_HOURS_PER_DAY) {
            throw InvalidHoursException::dailyTotalExceeded($newTotal, self::MAX_HOURS_PER_DAY);
        }
    }
}
