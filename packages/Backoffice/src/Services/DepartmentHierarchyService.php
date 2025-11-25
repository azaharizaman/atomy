<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Services;

use Nexus\Backoffice\Contracts\DepartmentInterface;
use Nexus\Backoffice\Contracts\Query\DepartmentQueryInterface;
use Nexus\Backoffice\ValueObjects\DepartmentStatus;

/**
 * Domain service for department hierarchy and business logic operations.
 *
 * Extracted from DepartmentRepositoryInterface to follow ISP and proper layering.
 * Business logic belongs in services, not repositories.
 */
final readonly class DepartmentHierarchyService
{
    public function __construct(
        private readonly DepartmentQueryInterface $departmentQuery,
    ) {}

    /**
     * Get all active departments for a company.
     *
     * @param string $companyId Company identifier
     * @return array<DepartmentInterface> Active departments
     */
    public function getActiveByCompany(string $companyId): array
    {
        $allDepartments = $this->departmentQuery->getByCompany($companyId);
        
        return array_filter(
            $allDepartments,
            fn(DepartmentInterface $dept) => $dept->getStatus() === DepartmentStatus::Active
        );
    }

    /**
     * Get all sub-departments of a parent department.
     *
     * @param string $parentDepartmentId Parent department identifier
     * @return array<DepartmentInterface> Sub-departments
     */
    public function getSubDepartments(string $parentDepartmentId): array
    {
        $parentDept = $this->departmentQuery->findById($parentDepartmentId);
        
        if ($parentDept === null) {
            return [];
        }
        
        $allDepartments = $this->departmentQuery->getByCompany($parentDept->getCompanyId());
        
        return array_filter(
            $allDepartments,
            fn(DepartmentInterface $dept) => $dept->getParentDepartmentId() === $parentDepartmentId
        );
    }

    /**
     * Get the parent department chain.
     *
     * Returns array of departments from immediate parent up to root.
     *
     * @param string $departmentId Department identifier
     * @return array<DepartmentInterface> Parent chain (ordered from immediate parent to root)
     */
    public function getParentChain(string $departmentId): array
    {
        $chain = [];
        $currentDept = $this->departmentQuery->findById($departmentId);
        
        if ($currentDept === null) {
            return [];
        }

        $parentId = $currentDept->getParentDepartmentId();
        
        while ($parentId !== null) {
            $parent = $this->departmentQuery->findById($parentId);
            
            if ($parent === null) {
                break;
            }
            
            $chain[] = $parent;
            $parentId = $parent->getParentDepartmentId();
        }
        
        return $chain;
    }

    /**
     * Get all descendants of a department (all levels).
     *
     * @param string $departmentId Department identifier
     * @return array<DepartmentInterface> All descendant departments
     */
    public function getAllDescendants(string $departmentId): array
    {
        $descendants = [];
        $directChildren = $this->getSubDepartments($departmentId);
        
        foreach ($directChildren as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $this->getAllDescendants($child->getId()));
        }
        
        return $descendants;
    }

    /**
     * Get the hierarchy depth of a department.
     *
     * Returns the number of levels from root (0 = root level).
     *
     * @param string $departmentId Department identifier
     * @return int Depth level (0 = root)
     */
    public function getHierarchyDepth(string $departmentId): int
    {
        $parentChain = $this->getParentChain($departmentId);
        return count($parentChain);
    }

    /**
     * Check for circular parent reference.
     *
     * Prevents a department from being its own ancestor.
     *
     * @param string $departmentId Department identifier
     * @param string $proposedParentId Proposed parent department identifier
     * @return bool True if circular reference detected
     */
    public function hasCircularReference(string $departmentId, string $proposedParentId): bool
    {
        // A department cannot be its own parent
        if ($departmentId === $proposedParentId) {
            return true;
        }
        
        // Check if departmentId appears in the parent chain of proposedParentId
        $parentChain = $this->getParentChain($proposedParentId);
        
        foreach ($parentChain as $ancestor) {
            if ($ancestor->getId() === $departmentId) {
                return true;
            }
        }
        
        return false;
    }
}
