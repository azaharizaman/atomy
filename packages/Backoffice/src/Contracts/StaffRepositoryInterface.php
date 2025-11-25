<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

use Nexus\Backoffice\Contracts\Persistence\StaffPersistenceInterface;
use Nexus\Backoffice\Contracts\Query\StaffQueryInterface;
use Nexus\Backoffice\Contracts\Validation\StaffValidationInterface;

/**
 * Repository interface for Staff persistence operations.
 *
 * @deprecated This interface violates ISP and CQRS principles.
 *             Use segregated interfaces instead:
 *             - StaffPersistenceInterface for write operations
 *             - StaffQueryInterface for read operations
 *             - StaffValidationInterface for validation operations
 *             - StaffAssignmentService for business logic
 *             This interface will be removed in v2.0.
 */
interface StaffRepositoryInterface extends StaffPersistenceInterface, StaffQueryInterface, StaffValidationInterface
{
    public function findById(string $id): ?StaffInterface;

    public function findByEmployeeId(string $employeeId): ?StaffInterface;

    public function findByStaffCode(string $staffCode): ?StaffInterface;

    public function findByEmail(string $companyId, string $email): ?StaffInterface;

    /**
     * @return array<StaffInterface>
     */
    public function getByCompany(string $companyId): array;

    /**
     * Get all active staff for a company.
     *
     * @deprecated Use StaffAssignmentService::getActiveByCompany() instead
     * @return array<StaffInterface>
     */
    public function getActiveByCompany(string $companyId): array;

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
     * Get direct reports for a supervisor.
     *
     * @deprecated Use StaffAssignmentService::getDirectReports() instead
     * @return array<StaffInterface>
     */
    public function getDirectReports(string $supervisorId): array;

    /**
     * Get all reports (direct and indirect) for a supervisor.
     *
     * @deprecated Use StaffAssignmentService::getAllReports() instead
     * @return array<StaffInterface>
     */
    public function getAllReports(string $supervisorId): array;

    /**
     * Get supervisor chain from staff to top-level.
     *
     * @deprecated Use StaffAssignmentService::getSupervisorChain() instead
     * @return array<StaffInterface>
     */
    public function getSupervisorChain(string $staffId): array;

    /**
     * @param array<string, mixed> $filters
     * @return array<StaffInterface>
     */
    public function search(array $filters): array;

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): StaffInterface;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): StaffInterface;

    public function delete(string $id): bool;

    public function employeeIdExists(string $employeeId, ?string $excludeId = null): bool;

    public function staffCodeExists(string $staffCode, ?string $excludeId = null): bool;

    public function emailExists(string $companyId, string $email, ?string $excludeId = null): bool;

    /**
     * Get depth of supervisor chain for a staff member.
     *
     * @deprecated Use StaffAssignmentService::getSupervisorChainDepth() instead
     */
    public function getSupervisorChainDepth(string $staffId): int;

    public function hasCircularSupervisor(string $staffId, string $proposedSupervisorId): bool;
}
