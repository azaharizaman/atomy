<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for time-based value objects that can be adjusted.
 *
 * Provides operations to manipulate time-based value objects by shifting
 * or extending their temporal boundaries while maintaining immutability.
 *
 * **When to Use:**
 * - Date range value objects (rental periods, booking windows)
 * - Scheduling value objects (availability slots, appointment times)
 * - Temporal spans that need adjustment (lease terms, subscription periods)
 * - Planning horizons that need extension or shifting
 *
 * **Expected Behavior:**
 * - All operations MUST return new instances (immutability)
 * - Original instance remains unchanged
 * - DateInterval is used for flexible time adjustments
 * - Negative intervals should shift/extend backwards
 *
 * @example Example implementation for a DateRange:
 * ```php
 * final readonly class DateRange implements AdjustableTime
 * {
 *     public function __construct(
 *         private \DateTimeImmutable $start,
 *         private \DateTimeImmutable $end
 *     ) {}
 *
 *     public function shift(\DateInterval $interval): static
 *     {
 *         return new self(
 *             $this->start->add($interval),
 *             $this->end->add($interval)
 *         );
 *     }
 *
 *     public function extend(\DateInterval $interval): static
 *     {
 *         return new self($this->start, $this->end->add($interval));
 *     }
 * }
 *
 * // Usage
 * $booking = new DateRange($checkIn, $checkOut);
 * $extendedBooking = $booking->extend(new \DateInterval('P2D')); // Extend by 2 days
 * $rescheduled = $booking->shift(new \DateInterval('P1W'));      // Shift by 1 week
 * ```
 */
interface AdjustableTime
{
    /**
     * Shift the period by a time interval.
     * 
     * @return static New instance with shifted dates
     */
    public function shift(\DateInterval $interval): static;

    /**
     * Extend the end date by a time interval.
     * 
     * @return static New instance with extended end date
     */
    public function extend(\DateInterval $interval): static;
}
