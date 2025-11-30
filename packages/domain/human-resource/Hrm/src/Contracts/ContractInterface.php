<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Represents an employment contract entity.
 */
interface ContractInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getEmployeeId(): string;
    
    public function getContractType(): string;
    
    public function getStartDate(): DateTimeInterface;
    
    public function getEndDate(): ?DateTimeInterface;
    
    public function getBasicSalary(): float;
    
    public function getCurrency(): string;
    
    public function getPayFrequency(): string;
    
    public function getProbationPeriodMonths(): ?int;
    
    public function getNoticePeriodDays(): ?int;
    
    public function getWorkingHoursPerWeek(): ?float;
    
    public function getTerms(): ?string;
    
    public function getStatus(): string;
    
    public function getSignedAt(): ?DateTimeInterface;
    
    public function getApprovedBy(): ?string;
    
    public function getApprovedAt(): ?DateTimeInterface;
    
    public function getMetadata(): array;
    
    public function isActive(): bool;
    
    public function isExpired(): bool;
}
