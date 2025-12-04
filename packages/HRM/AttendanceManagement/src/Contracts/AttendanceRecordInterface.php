<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Contracts;

use Nexus\AttendanceManagement\Enums\AttendanceStatus;
use Nexus\AttendanceManagement\Enums\CheckType;
use Nexus\AttendanceManagement\ValueObjects\AttendanceId;
use Nexus\AttendanceManagement\ValueObjects\WorkHours;

/**
 * Contract for attendance record entity
 */
interface AttendanceRecordInterface
{
    public function getId(): AttendanceId;
    
    public function getEmployeeId(): string;
    
    public function getDate(): \DateTimeImmutable;
    
    public function getCheckInTime(): ?\DateTimeImmutable;
    
    public function getCheckOutTime(): ?\DateTimeImmutable;
    
    public function getStatus(): AttendanceStatus;
    
    public function getWorkHours(): ?WorkHours;
    
    public function getScheduleId(): ?string;
    
    public function getLocationId(): ?string;
    
    public function getLatitude(): ?float;
    
    public function getLongitude(): ?float;
    
    public function getNotes(): ?string;
    
    public function isCheckedIn(): bool;
    
    public function isComplete(): bool;
}
