<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

use DateTimeInterface;

/**
 * Represents a payslip entity.
 */
interface PayslipInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getEmployeeId(): string;
    
    public function getPayslipNumber(): string;
    
    public function getPeriodStart(): DateTimeInterface;
    
    public function getPeriodEnd(): DateTimeInterface;
    
    public function getPayDate(): DateTimeInterface;
    
    public function getGrossPay(): float;
    
    public function getTotalEarnings(): float;
    
    public function getTotalDeductions(): float;
    
    public function getNetPay(): float;
    
    public function getEarningsBreakdown(): array;
    
    public function getDeductionsBreakdown(): array;
    
    public function getEmployerContributions(): array;
    
    public function getStatus(): string;
    
    public function getApprovedBy(): ?string;
    
    public function getApprovedAt(): ?DateTimeInterface;
    
    public function getMetadata(): array;
    
    public function isDraft(): bool;
    
    public function isApproved(): bool;
    
    public function isPaid(): bool;
}
