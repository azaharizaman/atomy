<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Validation;

/**
 * Validation interface for Department uniqueness and constraint checks.
 *
 * Handles validation operations for departments.
 * Follows ISP by focusing solely on validation operations.
 */
interface DepartmentValidationInterface
{
    /**
     * Check if a department code exists.
     *
     * @param string $companyId Company identifier
     * @param string $code Department code to check
     * @param string|null $parentDepartmentId Parent department identifier
     * @param string|null $excludeId Department ID to exclude from check (for updates)
     * @return bool True if code exists
     */
    public function codeExists(
        string $companyId,
        string $code,
        ?string $parentDepartmentId = null,
        ?string $excludeId = null
    ): bool;

    /**
     * Check if department has active staff members.
     *
     * @param string $departmentId Department identifier
     * @return bool True if department has active staff
     */
    public function hasActiveStaff(string $departmentId): bool;

    /**
     * Check if department has sub-departments.
     *
     * @param string $departmentId Department identifier
     * @return bool True if department has sub-departments
     */
    public function hasSubDepartments(string $departmentId): bool;
}
