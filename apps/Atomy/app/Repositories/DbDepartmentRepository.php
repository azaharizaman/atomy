<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Department;
use Nexus\Backoffice\Contracts\DepartmentInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;

class DbDepartmentRepository implements DepartmentRepositoryInterface
{
    public function findById(string $id): ?DepartmentInterface { return Department::find($id); }
    public function findByCode(string $companyId, string $code, ?string $parentDepartmentId = null): ?DepartmentInterface { $query = Department::where('company_id', $companyId)->where('code', $code); if ($parentDepartmentId) $query->where('parent_department_id', $parentDepartmentId); return $query->first(); }
    public function getByCompany(string $companyId): array { return Department::where('company_id', $companyId)->get()->all(); }
    public function getActiveByCompany(string $companyId): array { return Department::where('company_id', $companyId)->where('status', 'active')->get()->all(); }
    public function getSubDepartments(string $parentDepartmentId): array { return Department::where('parent_department_id', $parentDepartmentId)->get()->all(); }
    public function getParentChain(string $departmentId): array { /* TODO */ return []; }
    public function getAllDescendants(string $departmentId): array { /* TODO */ return []; }
    public function save(array $data): DepartmentInterface { return Department::create($data); }
    public function update(string $id, array $data): DepartmentInterface { $dept = Department::findOrFail($id); $dept->update($data); return $dept->fresh(); }
    public function delete(string $id): bool { return Department::destroy($id) > 0; }
    public function codeExists(string $companyId, string $code, ?string $parentDepartmentId = null, ?string $excludeId = null): bool { $query = Department::where('company_id', $companyId)->where('code', $code); if ($parentDepartmentId) $query->where('parent_department_id', $parentDepartmentId); if ($excludeId) $query->where('id', '!=', $excludeId); return $query->exists(); }
    public function hasActiveStaff(string $departmentId): bool { /* TODO */ return false; }
    public function hasSubDepartments(string $departmentId): bool { return Department::where('parent_department_id', $departmentId)->exists(); }
    public function getHierarchyDepth(string $departmentId): int { /* TODO */ return 0; }
    public function hasCircularReference(string $departmentId, string $proposedParentId): bool { /* TODO */ return false; }
}
