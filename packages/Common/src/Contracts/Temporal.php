<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects representing time periods/ranges.
 *
 * **When to Use:**
 * Implement this interface for any value object that represents a span of time.
 * Common use cases across multiple domains:
 *
 * - **Finance & Accounting:**
 *   - Fiscal periods (FY2024-Q1, JAN-2024)
 *   - Reporting periods
 *   - Budget periods
 *
 * - **Inventory Management:**
 *   - Inventory valuation periods
 *   - Stock count periods
 *   - Lot expiry tracking
 *
 * - **HR & Payroll:**
 *   - Pay periods
 *   - Leave periods
 *   - Contract validity periods
 *
 * - **General Business:**
 *   - Project timelines
 *   - Subscription periods
 *   - Promotional periods
 *
 * **Expected Behavior:**
 * - getStartDate() and getEndDate() MUST return DateTimeImmutable (immutable)
 * - contains() checks if a single point in time falls within the period (inclusive)
 * - overlaps() checks if two periods share any common time
 * - Start date MUST be less than or equal to end date
 *
 * **Example Implementation:**
 * ```php
 * final readonly class DateRange implements Temporal
 * {
 *     public function __construct(
 *         private \DateTimeImmutable $startDate,
 *         private \DateTimeImmutable $endDate
 *     ) {
 *         if ($endDate < $startDate) {
 *             throw new InvalidValueException('End must be after start');
 *         }
 *     }
 *
 *     public function getStartDate(): \DateTimeImmutable
 *     {
 *         return $this->startDate;
 *     }
 *
 *     public function getEndDate(): \DateTimeImmutable
 *     {
 *         return $this->endDate;
 *     }
 *
 *     public function contains(\DateTimeImmutable $date): bool
 *     {
 *         return $date >= $this->startDate && $date <= $this->endDate;
 *     }
 *
 *     public function overlaps(Temporal $other): bool
 *     {
 *         return $this->startDate <= $other->getEndDate()
 *             && $this->endDate >= $other->getStartDate();
 *     }
 * }
 * ```
 *
 * @see \Nexus\Common\ValueObjects\DateRange
 * @see \Nexus\Common\ValueObjects\Period
 * @see AdjustableTime For time manipulation operations
 */
interface Temporal
{
    /**
     * Get the start date/time of this period.
     *
     * @return \DateTimeImmutable The period's start (inclusive)
     */
    public function getStartDate(): \DateTimeImmutable;

    /**
     * Get the end date/time of this period.
     *
     * @return \DateTimeImmutable The period's end (inclusive)
     */
    public function getEndDate(): \DateTimeImmutable;

    /**
     * Check if a specific date/time falls within this period.
     *
     * @param \DateTimeImmutable $date The date to check
     * @return bool True if date is within period (inclusive of boundaries)
     */
    public function contains(\DateTimeImmutable $date): bool;

    /**
     * Check if this period overlaps with another temporal period.
     *
     * Two periods overlap if they share any common time.
     *
     * @param self $other The other temporal period to compare
     * @return bool True if periods share any common time
     */
    public function overlaps(self $other): bool;
}
