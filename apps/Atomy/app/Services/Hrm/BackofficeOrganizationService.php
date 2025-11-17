<?php

declare(strict_types=1);

namespace App\Services\Hrm;

use Nexus\Hrm\Contracts\OrganizationServiceContract;
use App\Models\Employee;

/**
 * Backoffice Organization Service
 * 
 * Integrates HRM package with Backoffice package for organizational structure queries.
 */
class BackofficeOrganizationService implements OrganizationServiceContract
{
    public function getEmployeeManager(string $employeeId): ?string
    {
        $employee = Employee::find($employeeId);
        
        return $employee?->manager_id;
    }

    public function getEmployeeDepartment(string $employeeId): ?array
    {
        $employee = Employee::find($employeeId);
        
        if (!$employee?->department_id) {
            return null;
        }
        
        // TODO: Once Backoffice Department model is integrated, fetch actual name
        return [
            'id' => $employee->department_id,
            'name' => 'Department', // Placeholder until Backoffice integration
        ];
    }

    public function getEmployeeOffice(string $employeeId): ?array
    {
        $employee = Employee::find($employeeId);
        
        if (!$employee?->office_id) {
            return null;
        }
        
        // TODO: Once Backoffice Office model is integrated, fetch actual name
        return [
            'id' => $employee->office_id,
            'name' => 'Office', // Placeholder until Backoffice integration
        ];
    }

    public function getDirectReports(string $managerId): array
    {
        return Employee::where('manager_id', $managerId)
            ->pluck('id')
            ->toArray();
    }

    public function isManager(string $employeeId): bool
    {
        return Employee::where('manager_id', $employeeId)->exists();
    }

    public function getDepartmentHead(string $departmentId): ?string
    {
        // This would integrate with Backoffice package's Department model
        // For now, returning null - to be implemented when Backoffice is integrated
        return null;
    }

    public function getOfficeManager(string $officeId): ?string
    {
        // This would integrate with Backoffice package's Office model
        // For now, returning null - to be implemented when Backoffice is integrated
        return null;
    }
}
