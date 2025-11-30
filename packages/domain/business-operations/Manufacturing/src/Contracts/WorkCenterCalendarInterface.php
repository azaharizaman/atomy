<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Contract for Work Center Calendar entity.
 *
 * Represents available working time for a work center.
 */
interface WorkCenterCalendarInterface
{
    /**
     * Get the unique identifier.
     */
    public function getId(): string;

    /**
     * Get the work center ID.
     */
    public function getWorkCenterId(): string;

    /**
     * Get the calendar date.
     */
    public function getDate(): \DateTimeImmutable;

    /**
     * Check if this is a working day.
     */
    public function isWorkingDay(): bool;

    /**
     * Get available hours for this day.
     */
    public function getAvailableHours(): float;

    /**
     * Get shift start time.
     */
    public function getShiftStart(): ?string;

    /**
     * Get shift end time.
     */
    public function getShiftEnd(): ?string;

    /**
     * Get break duration in minutes.
     */
    public function getBreakMinutes(): float;

    /**
     * Get overtime available hours.
     */
    public function getOvertimeHours(): float;

    /**
     * Get reason for non-working day (holiday, maintenance, etc.).
     */
    public function getNonWorkingReason(): ?string;
}
