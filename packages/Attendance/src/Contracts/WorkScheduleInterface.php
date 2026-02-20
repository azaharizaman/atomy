<?php

declare(strict_types=1);

namespace Nexus\Attendance\Contracts;

use Nexus\Attendance\ValueObjects\ScheduleId;

/**
 * Contract for work schedule entity
 */
interface WorkScheduleInterface
{
    public function getId(): ScheduleId;
    
    public function getEmployeeId(): string;
    
    public function getScheduleName(): string;
    
    public function getStartTime(): \DateTimeImmutable;
    
    public function getEndTime(): \DateTimeImmutable;
    
    public function getDayOfWeek(): ?int;
    
    public function isEffectiveOn(\DateTimeImmutable $date): bool;
    
    public function getEffectiveFrom(): \DateTimeImmutable;
    
    public function getEffectiveTo(): ?\DateTimeImmutable;
    
    public function getExpectedHours(): float;
}
