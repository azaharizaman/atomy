<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Represents an employee entity in the HRM domain.
 */
interface EmployeeInterface
{
    public function getId(): string;
    
    public function getTenantId(): string;
    
    public function getEmployeeCode(): string;
    
    public function getFirstName(): string;
    
    public function getLastName(): string;
    
    public function getFullName(): string;
    
    public function getEmail(): string;
    
    public function getPhoneNumber(): ?string;
    
    public function getDateOfBirth(): DateTimeInterface;
    
    public function getHireDate(): DateTimeInterface;
    
    public function getConfirmationDate(): ?DateTimeInterface;
    
    public function getTerminationDate(): ?DateTimeInterface;
    
    public function getStatus(): string;
    
    public function getManagerId(): ?string;
    
    public function getDepartmentId(): ?string;
    
    public function getOfficeId(): ?string;
    
    public function getJobTitle(): ?string;
    
    public function getEmploymentType(): string;
    
    public function getMetadata(): array;
    
    public function isActive(): bool;
    
    public function isProbationary(): bool;
    
    public function isConfirmed(): bool;
    
    public function isTerminated(): bool;
}
