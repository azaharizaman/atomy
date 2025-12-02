<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects representing time periods.
 */
interface Temporal
{
    /**
     * Get start date/time.
     */
    public function getStartDate(): \DateTimeImmutable;

    /**
     * Get end date/time.
     */
    public function getEndDate(): \DateTimeImmutable;

    /**
     * Check if a date falls within this period.
     */
    public function contains(\DateTimeImmutable $date): bool;

    /**
     * Check if this period overlaps with another.
     */
    public function overlaps(self $other): bool;
}
