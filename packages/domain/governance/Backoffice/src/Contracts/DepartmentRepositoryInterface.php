<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

use Nexus\Backoffice\Contracts\Persistence\DepartmentPersistenceInterface;
use Nexus\Backoffice\Contracts\Query\DepartmentQueryInterface;
use Nexus\Backoffice\Contracts\Validation\DepartmentValidationInterface;

/**
 * Repository interface for Department persistence operations.
 *
 * @deprecated This fat interface violates ISP and CQRS. Use the segregated interfaces instead:
 *             - DepartmentPersistenceInterface for write operations
 *             - DepartmentQueryInterface for read operations
 *             - DepartmentValidationInterface for validation operations
 *             - DepartmentHierarchyService for business logic
 *
 * This interface is kept for backward compatibility and now extends the new interfaces.
 * It will be removed in v2.0.
 */
interface DepartmentRepositoryInterface extends
    DepartmentPersistenceInterface,
    DepartmentQueryInterface,
    DepartmentValidationInterface
{
    public function findById(string $id): ?DepartmentInterface;

    public function findByCode(string $companyId, string $code, ?string $parentDepartmentId = null): ?DepartmentInterface;

    /**
     * @return array<DepartmentInterface>
     */
    public function getByCompany(string $companyId): array;

    /**
     * Get all active departments for a company.
     *
     * @deprecated Use DepartmentHierarchyService::getActiveByCompany() instead.
     * @return array<DepartmentInterface>
     */
    public function getActiveByCompany(string $companyId): array;

    /**
     * Get sub-departments.
     *
     * @deprecated Use DepartmentHierarchyService::getSubDepartments() instead.
     * @return array<DepartmentInterface>
     */
    public function getSubDepartments(string $parentDepartmentId): array;

    /**
     * Get parent chain.
     *
     * @deprecated Use DepartmentHierarchyService::getParentChain() instead.
     * @return array<DepartmentInterface>
     */
    public function getParentChain(string $departmentId): array;

    /**
     * Get all descendants.
     *
     * @deprecated Use DepartmentHierarchyService::getAllDescendants() instead.
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

    /**
     * Get hierarchy depth.
     *
     * @deprecated Use DepartmentHierarchyService::getHierarchyDepth() instead.
     */
    public function getHierarchyDepth(string $departmentId): int;

    /**
     * Check for circular reference.
     *
     * @deprecated Use DepartmentHierarchyService::hasCircularReference() instead.
     */
    public function hasCircularReference(string $departmentId, string $proposedParentId): bool;
}
