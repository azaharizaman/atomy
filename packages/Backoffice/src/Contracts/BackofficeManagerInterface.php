<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Main orchestration interface for Backoffice operations.
 *
 * Provides the public API for managing organizational structure.
 */
interface BackofficeManagerInterface
{
    /**
     * Create a new company.
     *
     * @param array<string, mixed> $data
     */
    public function createCompany(array $data): CompanyInterface;

    /**
     * Update an existing company.
     *
     * @param array<string, mixed> $data
     */
    public function updateCompany(string $id, array $data): CompanyInterface;

    /**
     * Delete a company.
     */
    public function deleteCompany(string $id): bool;

    /**
     * Get a company by ID.
     */
    public function getCompany(string $id): ?CompanyInterface;

    /**
     * Create a new office.
     *
     * @param array<string, mixed> $data
     */
    public function createOffice(array $data): OfficeInterface;

    /**
     * Update an existing office.
     *
     * @param array<string, mixed> $data
     */
    public function updateOffice(string $id, array $data): OfficeInterface;

    /**
     * Delete an office.
     */
    public function deleteOffice(string $id): bool;

    /**
     * Get an office by ID.
     */
    public function getOffice(string $id): ?OfficeInterface;

    /**
     * Create a new department.
     *
     * @param array<string, mixed> $data
     */
    public function createDepartment(array $data): DepartmentInterface;

    /**
     * Update an existing department.
     *
     * @param array<string, mixed> $data
     */
    public function updateDepartment(string $id, array $data): DepartmentInterface;

    /**
     * Delete a department.
     */
    public function deleteDepartment(string $id): bool;

    /**
     * Get a department by ID.
     */
    public function getDepartment(string $id): ?DepartmentInterface;

    /**
     * Create a new staff member.
     *
     * @param array<string, mixed> $data
     */
    public function createStaff(array $data): StaffInterface;

    /**
     * Update an existing staff member.
     *
     * @param array<string, mixed> $data
     */
    public function updateStaff(string $id, array $data): StaffInterface;

    /**
     * Delete a staff member.
     */
    public function deleteStaff(string $id): bool;

    /**
     * Get a staff member by ID.
     */
    public function getStaff(string $id): ?StaffInterface;

    /**
     * Assign staff to a department.
     */
    public function assignStaffToDepartment(
        string $staffId,
        string $departmentId,
        string $role,
        bool $isPrimary = false
    ): void;

    /**
     * Assign staff to an office.
     */
    public function assignStaffToOffice(
        string $staffId,
        string $officeId,
        \DateTimeInterface $effectiveDate
    ): void;

    /**
     * Set supervisor for a staff member.
     */
    public function setSupervisor(string $staffId, string $supervisorId): void;

    /**
     * Create a new unit.
     *
     * @param array<string, mixed> $data
     */
    public function createUnit(array $data): UnitInterface;

    /**
     * Update an existing unit.
     *
     * @param array<string, mixed> $data
     */
    public function updateUnit(string $id, array $data): UnitInterface;

    /**
     * Delete a unit.
     */
    public function deleteUnit(string $id): bool;

    /**
     * Get a unit by ID.
     */
    public function getUnit(string $id): ?UnitInterface;

    /**
     * Add a member to a unit.
     */
    public function addUnitMember(string $unitId, string $staffId, string $role): void;

    /**
     * Remove a member from a unit.
     */
    public function removeUnitMember(string $unitId, string $staffId): void;

    /**
     * Generate organizational chart.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function generateOrganizationalChart(string $companyId, string $format, array $options = []): array;

    /**
     * Export organizational chart.
     *
     * @param array<string, mixed> $chartData
     */
    public function exportOrganizationalChart(array $chartData, string $format): string;
}
