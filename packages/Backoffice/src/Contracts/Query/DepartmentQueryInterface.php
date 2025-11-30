<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Query;

use Nexus\Backoffice\Contracts\DepartmentInterface;

/**
 * Query interface for Department read operations (CQRS Query Model).
 *
 * Handles all read operations for departments.
 * Follows ISP by focusing solely on query operations.
 * Follows CQRS by separating reads from writes.
 */
interface DepartmentQueryInterface
{
    /**
     * Find a department by its unique identifier.
     *
     * @param string $id Department identifier
     * @return DepartmentInterface|null Department or null if not found
     */
    public function findById(string $id): ?DepartmentInterface;

    /**
     * Find a department by code within a company and optional parent.
     *
     * @param string $companyId Company identifier
     * @param string $code Department code
     * @param string|null $parentDepartmentId Parent department identifier
     * @return DepartmentInterface|null Department or null if not found
     */
    public function findByCode(string $companyId, string $code, ?string $parentDepartmentId = null): ?DepartmentInterface;

    /**
     * Get all departments for a company.
     *
     * @param string $companyId Company identifier
     * @return array<DepartmentInterface> All departments in company
     */
    public function getByCompany(string $companyId): array;
}
