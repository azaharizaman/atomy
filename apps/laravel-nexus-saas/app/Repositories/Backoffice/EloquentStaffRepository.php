<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Backoffice\Staff;
use App\Models\Backoffice\StaffAssignment;
use App\Models\Backoffice\Supervisor;
use Nexus\Backoffice\Contracts\StaffInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;

class EloquentStaffRepository implements StaffRepositoryInterface
{
    public function findById(string $id): ?StaffInterface
    {
        return Staff::find($id);
    }

    public function findByEmployeeId(string $employeeId): ?StaffInterface
    {
        return Staff::where('employee_id', $employeeId)->first();
    }

    public function findByStaffCode(string $staffCode): ?StaffInterface
    {
        return Staff::where('staff_code', $staffCode)->first();
    }

    public function findByEmail(string $companyId, string $email): ?StaffInterface
    {
        return Staff::where('company_id', $companyId)
            ->where('email', $email)
            ->first();
    }

    public function getByCompany(string $companyId): array
    {
        return Staff::where('company_id', $companyId)->get()->all();
    }

    public function getActiveByCompany(string $companyId): array
    {
        return Staff::where('company_id', $companyId)
            ->where('status', 'active')
            ->get()
            ->all();
    }

    public function getByDepartment(string $departmentId): array
    {
        return Staff::whereHas('assignments', function ($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })->get()->all();
    }

    public function getByOffice(string $officeId): array
    {
        return Staff::where('office_id', $officeId)->get()->all();
    }

    public function getDirectReports(string $supervisorId): array
    {
        return Staff::whereHas('supervisors', function ($query) use ($supervisorId) {
            $query->where('supervisor_id', $supervisorId);
        })->get()->all();
    }

    public function getAllReports(string $supervisorId): array
    {
        $reports = [];
        $directReports = $this->getDirectReports($supervisorId);

        foreach ($directReports as $report) {
            $reports[] = $report;
            $reports = array_merge($reports, $this->getAllReports($report->getId()));
        }

        return $reports;
    }

    public function getSupervisorChain(string $staffId): array
    {
        $chain = [];
        // Assuming a staff has one primary supervisor for the chain, or we just take the first one found.
        // The Supervisor model allows multiple supervisors (matrix structure), but usually chain implies primary reporting line.
        // Let's assume 'type' = 'primary' or just take the first one.
        
        $supervisorRecord = Supervisor::where('staff_id', $staffId)->first();
        
        while ($supervisorRecord) {
            $supervisor = $this->findById($supervisorRecord->supervisor_id);
            if ($supervisor) {
                array_unshift($chain, $supervisor);
                $supervisorRecord = Supervisor::where('staff_id', $supervisor->getId())->first();
            } else {
                break;
            }
        }

        return $chain;
    }

    public function search(array $filters): array
    {
        $query = Staff::query();

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('staff_code', 'like', "%{$search}%");
            });
        }

        return $query->get()->all();
    }

    public function save(array $data): StaffInterface
    {
        return Staff::create($data);
    }

    public function update(string $id, array $data): StaffInterface
    {
        $staff = Staff::findOrFail($id);
        $staff->update($data);
        return $staff;
    }

    public function delete(string $id): bool
    {
        $staff = Staff::find($id);
        if ($staff) {
            return $staff->delete();
        }
        return false;
    }

    public function employeeIdExists(string $employeeId, ?string $excludeId = null): bool
    {
        $query = Staff::where('employee_id', $employeeId);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function staffCodeExists(string $staffCode, ?string $excludeId = null): bool
    {
        $query = Staff::where('staff_code', $staffCode);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function emailExists(string $companyId, string $email, ?string $excludeId = null): bool
    {
        $query = Staff::where('company_id', $companyId)
            ->where('email', $email);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function getSupervisorChainDepth(string $staffId): int
    {
        return count($this->getSupervisorChain($staffId));
    }

    public function hasCircularSupervisor(string $staffId, string $proposedSupervisorId): bool
    {
        if ($staffId === $proposedSupervisorId) {
            return true;
        }

        // Check if staffId is already a supervisor of proposedSupervisorId (up the chain)
        $chain = $this->getSupervisorChain($proposedSupervisorId);
        foreach ($chain as $supervisor) {
            if ($supervisor->getId() === $staffId) {
                return true;
            }
        }

        return false;
    }
}
