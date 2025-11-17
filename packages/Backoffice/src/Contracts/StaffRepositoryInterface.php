<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Repository interface for Staff persistence operations.
 */
interface StaffRepositoryInterface
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
     * @return array<StaffInterface>
     */
    public function getActiveByCompany(string $companyId): array;

    /**
     * @return array<StaffInterface>
     */
    public function getByDepartment(string $departmentId): array;

    /**
     * @return array<StaffInterface>
     */
    public function getByOffice(string $officeId): array;

    /**
     * @return array<StaffInterface>
     */
    public function getDirectReports(string $supervisorId): array;

    /**
     * @return array<StaffInterface>
     */
    public function getAllReports(string $supervisorId): array;

    /**
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

    public function getSupervisorChainDepth(string $staffId): int;

    public function hasCircularSupervisor(string $staffId, string $proposedSupervisorId): bool;
}
