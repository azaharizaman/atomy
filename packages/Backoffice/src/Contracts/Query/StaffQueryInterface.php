<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Query;

use Nexus\Backoffice\Contracts\StaffInterface;

/**
 * Query interface for Staff read operations (CQRS - Query).
 * Handles data retrieval without side effects.
 */
interface StaffQueryInterface
{
    /**
     * Find staff by ID.
     */
    public function findById(string $id): ?StaffInterface;

    /**
     * Find staff by employee ID.
     */
    public function findByEmployeeId(string $employeeId): ?StaffInterface;

    /**
     * Find staff by staff code.
     */
    public function findByStaffCode(string $staffCode): ?StaffInterface;

    /**
     * Find staff by email within a company.
     */
    public function findByEmail(string $companyId, string $email): ?StaffInterface;

    /**
     * Get all staff for a company.
     *
     * @return array<StaffInterface>
     */
    public function getByCompany(string $companyId): array;

    /**
     * Get staff by department.
     *
     * @return array<StaffInterface>
     */
    public function getByDepartment(string $departmentId): array;

    /**
     * Get staff by office.
     *
     * @return array<StaffInterface>
     */
    public function getByOffice(string $officeId): array;

    /**
     * Search staff with filters.
     *
     * @param array<string, mixed> $filters
     * @return array<StaffInterface>
     */
    public function search(array $filters): array;
}
