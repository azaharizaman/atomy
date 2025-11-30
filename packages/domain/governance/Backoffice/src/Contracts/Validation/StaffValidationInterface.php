<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Validation;

/**
 * Validation interface for Staff business rules.
 * Handles existence checks and constraint validation.
 */
interface StaffValidationInterface
{
    /**
     * Check if employee ID exists.
     */
    public function employeeIdExists(string $employeeId, ?string $excludeId = null): bool;

    /**
     * Check if staff code exists.
     */
    public function staffCodeExists(string $staffCode, ?string $excludeId = null): bool;

    /**
     * Check if email exists within a company.
     */
    public function emailExists(string $companyId, string $email, ?string $excludeId = null): bool;

    /**
     * Check if proposed supervisor assignment creates circular reference.
     */
    public function hasCircularSupervisor(string $staffId, string $proposedSupervisorId): bool;
}
