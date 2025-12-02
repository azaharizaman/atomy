<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for time-based value objects that can be adjusted.
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
