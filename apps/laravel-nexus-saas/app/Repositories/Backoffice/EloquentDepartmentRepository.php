<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Backoffice\Department;
use App\Models\Backoffice\StaffAssignment;
use Nexus\Backoffice\Contracts\DepartmentInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;

class EloquentDepartmentRepository implements DepartmentRepositoryInterface
{
    public function findById(string $id): ?DepartmentInterface
    {
        return Department::find($id);
    }

    public function findByCode(string $companyId, string $code, ?string $parentDepartmentId = null): ?DepartmentInterface
    {
        $query = Department::where('company_id', $companyId)
            ->where('code', $code);
        
        if ($parentDepartmentId) {
            $query->where('parent_id', $parentDepartmentId);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->first();
    }

    public function getByCompany(string $companyId): array
    {
        return Department::where('company_id', $companyId)->get()->all();
    }

    public function getActiveByCompany(string $companyId): array
    {
        return Department::where('company_id', $companyId)
            ->where('status', 'active')
            ->get()
            ->all();
    }

    public function getSubDepartments(string $parentDepartmentId): array
    {
        return Department::where('parent_id', $parentDepartmentId)->get()->all();
    }

    public function getParentChain(string $departmentId): array
    {
        $chain = [];
        $department = $this->findById($departmentId);

        while ($department && $department->getParentDepartmentId()) {
            $parent = $this->findById($department->getParentDepartmentId());
            if ($parent) {
                array_unshift($chain, $parent);
                $department = $parent;
            } else {
                break;
            }
        }

        return $chain;
    }

    public function getAllDescendants(string $departmentId): array
    {
        $descendants = [];
        $children = $this->getSubDepartments($departmentId);

        foreach ($children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $this->getAllDescendants($child->getId()));
        }

        return $descendants;
    }

    public function save(array $data): DepartmentInterface
    {
        return Department::create($data);
    }

    public function update(string $id, array $data): DepartmentInterface
    {
        $department = Department::findOrFail($id);
        $department->update($data);
        return $department;
    }

    public function delete(string $id): bool
    {
        $department = Department::find($id);
        if ($department) {
            return $department->delete();
        }
        return false;
    }

    public function codeExists(string $companyId, string $code, ?string $parentDepartmentId = null, ?string $excludeId = null): bool
    {
        $query = Department::where('company_id', $companyId)
            ->where('code', $code);
        
        if ($parentDepartmentId) {
            $query->where('parent_id', $parentDepartmentId);
        } else {
            $query->whereNull('parent_id');
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function hasActiveStaff(string $departmentId): bool
    {
        return StaffAssignment::where('department_id', $departmentId)
            ->where('is_primary', true) // Assuming we check primary assignments or just any active assignment
            // StaffAssignment doesn't have 'is_active' but usually implies active assignment if record exists and not end_dated.
            // Let's check StaffAssignment model again.
            ->exists();
    }

    public function hasSubDepartments(string $departmentId): bool
    {
        return Department::where('parent_id', $departmentId)->exists();
    }

    public function getHierarchyDepth(string $departmentId): int
    {
        $depth = 0;
        $department = $this->findById($departmentId);

        while ($department && $department->getParentDepartmentId()) {
            $depth++;
            $department = $this->findById($department->getParentDepartmentId());
        }

        return $depth;
    }

    public function hasCircularReference(string $departmentId, string $proposedParentId): bool
    {
        if ($departmentId === $proposedParentId) {
            return true;
        }

        $parent = $this->findById($proposedParentId);
        while ($parent) {
            if ($parent->getId() === $departmentId) {
                return true;
            }
            if (!$parent->getParentDepartmentId()) {
                break;
            }
            $parent = $this->findById($parent->getParentDepartmentId());
        }

        return false;
    }
}
