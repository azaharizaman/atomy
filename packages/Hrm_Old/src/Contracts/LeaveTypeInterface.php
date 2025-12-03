<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

/**
 * Represents a leave type configuration entity.
 */
interface LeaveTypeInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getName(): string;
    
    public function getCode(): string;
    
    public function getDescription(): ?string;
    
    public function getDefaultDaysPerYear(): ?float;
    
    public function getMaxCarryForwardDays(): ?float;
    
    public function isRequiresApproval(): bool;
    
    public function isUnpaid(): bool;
    
    public function isActive(): bool;
    
    public function getAccrualRule(): ?string;
    
    public function getMetadata(): array;
}
