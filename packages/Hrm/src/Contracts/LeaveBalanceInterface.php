<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

/**
 * Represents leave balance for an employee and leave type.
 */
interface LeaveBalanceInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getEmployeeId(): string;
    
    public function getLeaveTypeId(): string;
    
    public function getYear(): int;
    
    public function getEntitledDays(): float;
    
    public function getUsedDays(): float;
    
    public function getCarriedForwardDays(): float;
    
    public function getRemainingDays(): float;
    
    public function getMetadata(): array;
}
