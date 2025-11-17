<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Staff;
use Nexus\Backoffice\Contracts\StaffInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;

class DbStaffRepository implements StaffRepositoryInterface
{
    public function findById(string $id): ?StaffInterface { return Staff::find($id); }
    public function findByEmployeeId(string $employeeId): ?StaffInterface { return Staff::where('employee_id', $employeeId)->first(); }
    public function findByStaffCode(string $staffCode): ?StaffInterface { return Staff::where('staff_code', $staffCode)->first(); }
    public function findByEmail(string $companyId, string $email): ?StaffInterface { /* TODO: add company relation */ return Staff::where('email', $email)->first(); }
    public function getByCompany(string $companyId): array { /* TODO */ return []; }
    public function getActiveByCompany(string $companyId): array { /* TODO */ return []; }
    public function getByDepartment(string $departmentId): array { /* TODO */ return []; }
    public function getByOffice(string $officeId): array { /* TODO */ return []; }
    public function getDirectReports(string $supervisorId): array { /* TODO */ return []; }
    public function getAllReports(string $supervisorId): array { /* TODO */ return []; }
    public function getSupervisorChain(string $staffId): array { /* TODO */ return []; }
    public function search(array $filters): array { /* TODO */ return []; }
    public function save(array $data): StaffInterface { return Staff::create($data); }
    public function update(string $id, array $data): StaffInterface { $staff = Staff::findOrFail($id); $staff->update($data); return $staff->fresh(); }
    public function delete(string $id): bool { return Staff::destroy($id) > 0; }
    public function employeeIdExists(string $employeeId, ?string $excludeId = null): bool { $query = Staff::where('employee_id', $employeeId); if ($excludeId) $query->where('id', '!=', $excludeId); return $query->exists(); }
    public function staffCodeExists(string $staffCode, ?string $excludeId = null): bool { $query = Staff::where('staff_code', $staffCode); if ($excludeId) $query->where('id', '!=', $excludeId); return $query->exists(); }
    public function emailExists(string $companyId, string $email, ?string $excludeId = null): bool { $query = Staff::where('email', $email); if ($excludeId) $query->where('id', '!=', $excludeId); return $query->exists(); }
    public function getSupervisorChainDepth(string $staffId): int { /* TODO */ return 0; }
    public function hasCircularSupervisor(string $staffId, string $proposedSupervisorId): bool { /* TODO */ return false; }
}
