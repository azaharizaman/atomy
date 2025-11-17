<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Represents employee-specific payroll component assignments.
 */
interface EmployeeComponentInterface
{
    public function getId(): string;
    
    public function getEmployeeId(): string;
    
    public function getComponentId(): string;
    
    public function getAmount(): ?float;
    
    public function getPercentageValue(): ?float;
    
    public function getEffectiveFrom(): \DateTimeInterface;
    
    public function getEffectiveTo(): ?\DateTimeInterface;
    
    public function isActive(): bool;
    
    public function getMetadata(): array;
}
