<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Represents an attendance record entity.
 */
interface AttendanceInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getEmployeeId(): string;
    
    public function getDate(): DateTimeInterface;
    
    public function getClockInTime(): ?DateTimeInterface;
    
    public function getClockOutTime(): ?DateTimeInterface;
    
    public function getBreakMinutes(): int;
    
    public function getTotalHours(): ?float;
    
    public function getOvertimeHours(): ?float;
    
    public function getStatus(): string;
    
    public function getClockInLocation(): ?string;
    
    public function getClockOutLocation(): ?string;
    
    public function getClockInLatitude(): ?float;
    
    public function getClockInLongitude(): ?float;
    
    public function getClockOutLatitude(): ?float;
    
    public function getClockOutLongitude(): ?float;
    
    public function getRemarks(): ?string;
    
    public function getApprovedBy(): ?string;
    
    public function getApprovedAt(): ?DateTimeInterface;
    
    public function getMetadata(): array;
    
    public function isPresent(): bool;
    
    public function isAbsent(): bool;
    
    public function isLate(): bool;
    
    public function hasOvertime(): bool;
}
