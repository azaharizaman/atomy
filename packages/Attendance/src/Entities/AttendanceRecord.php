<?php

declare(strict_types=1);

namespace Nexus\Attendance\Entities;

use Nexus\Attendance\Contracts\AttendanceRecordInterface;
use Nexus\Attendance\Enums\AttendanceStatus;
use Nexus\Attendance\ValueObjects\AttendanceId;
use Nexus\Attendance\ValueObjects\WorkHours;

/**
 * Attendance Record entity
 * Represents a single day's attendance record for an employee
 */
final readonly class AttendanceRecord implements AttendanceRecordInterface
{
    public function __construct(
        private AttendanceId $id,
        private string $employeeId,
        private \DateTimeImmutable $date,
        private ?\DateTimeImmutable $checkInTime = null,
        private ?\DateTimeImmutable $checkOutTime = null,
        private AttendanceStatus $status = AttendanceStatus::ABSENT,
        private ?WorkHours $workHours = null,
        private ?string $scheduleId = null,
        private ?string $locationId = null,
        private ?float $latitude = null,
        private ?float $longitude = null,
        private ?string $notes = null
    ) {}

    public function getId(): AttendanceId
    {
        return $this->id;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getCheckInTime(): ?\DateTimeImmutable
    {
        return $this->checkInTime;
    }

    public function getCheckOutTime(): ?\DateTimeImmutable
    {
        return $this->checkOutTime;
    }

    public function getStatus(): AttendanceStatus
    {
        return $this->status;
    }

    public function getWorkHours(): ?WorkHours
    {
        return $this->workHours;
    }

    public function getScheduleId(): ?string
    {
        return $this->scheduleId;
    }

    public function getLocationId(): ?string
    {
        return $this->locationId;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Create a new attendance record with check-in
     */
    public function withCheckIn(
        \DateTimeImmutable $checkInTime,
        ?string $locationId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): self {
        return new self(
            $this->id,
            $this->employeeId,
            $this->date,
            $checkInTime,
            $this->checkOutTime,
            AttendanceStatus::PRESENT,
            $this->workHours,
            $this->scheduleId,
            $locationId,
            $latitude,
            $longitude,
            $this->notes
        );
    }

    /**
     * Create a new attendance record with check-out
     */
    public function withCheckOut(
        \DateTimeImmutable $checkOutTime,
        ?WorkHours $workHours = null
    ): self {
        return new self(
            $this->id,
            $this->employeeId,
            $this->date,
            $this->checkInTime,
            $checkOutTime,
            $this->status,
            $workHours,
            $this->scheduleId,
            $this->locationId,
            $this->latitude,
            $this->longitude,
            $this->notes
        );
    }

    /**
     * Check if employee is currently checked in
     */
    public function isCheckedIn(): bool
    {
        return $this->checkInTime !== null && $this->checkOutTime === null;
    }

    /**
     * Check if attendance is complete for the day
     */
    public function isComplete(): bool
    {
        return $this->checkInTime !== null && $this->checkOutTime !== null;
    }
}
