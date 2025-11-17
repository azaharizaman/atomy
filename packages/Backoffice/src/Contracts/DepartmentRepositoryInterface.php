<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Repository interface for Department persistence operations.
 */
interface DepartmentRepositoryInterface
{
    public function findById(string $id): ?DepartmentInterface;

    public function findByCode(string $companyId, string $code, ?string $parentDepartmentId = null): ?DepartmentInterface;

    /**
     * @return array<DepartmentInterface>
     */
    public function getByCompany(string $companyId): array;

    /**
     * @return array<DepartmentInterface>
     */
    public function getActiveByCompany(string $companyId): array;

    /**
     * @return array<DepartmentInterface>
     */
    public function getSubDepartments(string $parentDepartmentId): array;

    /**
     * @return array<DepartmentInterface>
     */
    public function getParentChain(string $departmentId): array;

    /**
     * @return array<DepartmentInterface>
     */
    public function getAllDescendants(string $departmentId): array;

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): DepartmentInterface;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): DepartmentInterface;

    public function delete(string $id): bool;

    public function codeExists(string $companyId, string $code, ?string $parentDepartmentId = null, ?string $excludeId = null): bool;

    public function hasActiveStaff(string $departmentId): bool;

    public function hasSubDepartments(string $departmentId): bool;

    public function getHierarchyDepth(string $departmentId): int;

    public function hasCircularReference(string $departmentId, string $proposedParentId): bool;
}
