<?php

declare(strict_types=1);

namespace App\Repositories\Hrm;

use App\Models\Employee;
use Nexus\Hrm\Contracts\EmployeeInterface;
use Nexus\Hrm\Contracts\EmployeeRepositoryInterface;
use Nexus\Hrm\Exceptions\EmployeeNotFoundException;

class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function findById(string $id): EmployeeInterface
    {
        $employee = Employee::find($id);
        
        if (!$employee) {
            throw EmployeeNotFoundException::forId($id);
        }
        
        return $employee;
    }

    public function findByEmployeeCode(string $tenantId, string $employeeCode): EmployeeInterface
    {
        $employee = Employee::where('tenant_id', $tenantId)
            ->where('employee_code', $employeeCode)
            ->first();
        
        if (!$employee) {
            throw EmployeeNotFoundException::forEmployeeCode($employeeCode);
        }
        
        return $employee;
    }

    public function findByEmail(string $tenantId, string $email): ?EmployeeInterface
    {
        return Employee::where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();
    }

    public function getAllForTenant(string $tenantId, array $filters = []): array
    {
        $query = Employee::where('tenant_id', $tenantId);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        
        if (isset($filters['manager_id'])) {
            $query->where('manager_id', $filters['manager_id']);
        }
        
        return $query->get()->all();
    }

    public function getDirectReports(string $managerId): array
    {
        return Employee::where('manager_id', $managerId)->get()->all();
    }

    public function save(EmployeeInterface $employee): EmployeeInterface
    {
        if ($employee instanceof Employee) {
            $employee->save();
            return $employee;
        }
        
        throw new \InvalidArgumentException('Employee must be an Eloquent model');
    }

    public function delete(string $id): void
    {
        $employee = Employee::find($id);
        
        if (!$employee) {
            throw EmployeeNotFoundException::forId($id);
        }
        
        $employee->delete();
    }

    public function employeeCodeExists(string $tenantId, string $employeeCode, ?string $excludeId = null): bool
    {
        $query = Employee::where('tenant_id', $tenantId)
            ->where('employee_code', $employeeCode);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function emailExists(string $tenantId, string $email, ?string $excludeId = null): bool
    {
        $query = Employee::where('tenant_id', $tenantId)
            ->where('email', $email);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}
