<?php

namespace Database\Seeders;

use App\Models\Backoffice\Company;
use App\Models\Backoffice\Department;
use App\Models\Backoffice\Office;
use App\Models\Backoffice\Staff;
use App\Models\Backoffice\StaffAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BackofficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 2 Tenants
        $tenants = [
            (string) Str::ulid(),
            (string) Str::ulid(),
        ];

        foreach ($tenants as $tenantId) {
            $this->seedTenantData($tenantId);
        }
    }

    private function seedTenantData(string $tenantId): void
    {
        // 1. Create Company
        $company = Company::factory()->create([
            'tenant_id' => $tenantId,
            'name' => 'Nexus Corp ' . substr($tenantId, 0, 4),
        ]);

        // 2. Create Offices
        $headOffice = Office::factory()->create([
            'tenant_id' => $tenantId,
            'company_id' => $company->id,
            'name' => 'Head Office',
            'is_head_office' => true,
            'type' => 'headquarters',
        ]);

        $branchOffice = Office::factory()->create([
            'tenant_id' => $tenantId,
            'company_id' => $company->id,
            'name' => 'Downtown Branch',
            'is_head_office' => false,
            'type' => 'branch',
            'parent_office_id' => $headOffice->id,
        ]);

        // 3. Create Departments
        $departments = [];
        $deptNames = ['Human Resources', 'Information Technology', 'Sales & Marketing'];

        foreach ($deptNames as $name) {
            $departments[] = Department::factory()->create([
                'tenant_id' => $tenantId,
                'company_id' => $company->id,
                'name' => $name,
                'code' => strtoupper(substr($name, 0, 3)),
            ]);
        }

        // 4. Create Staff and Assignments
        // Create a Manager for each department
        foreach ($departments as $dept) {
            $manager = Staff::factory()->create([
                'tenant_id' => $tenantId,
                'position' => 'Manager',
            ]);

            // Assign as manager to department
            $dept->update(['manager_staff_id' => $manager->id]);

            // Create assignment
            StaffAssignment::create([
                'id' => (string) Str::ulid(),
                'staff_id' => $manager->id,
                'department_id' => $dept->id,
                'job_title' => 'Manager',
                'is_primary' => true,
                'start_date' => now(),
            ]);

            // Create 3 staff members for each department
            $staffMembers = Staff::factory(3)->create([
                'tenant_id' => $tenantId,
            ]);

            foreach ($staffMembers as $staff) {
                StaffAssignment::create([
                    'id' => (string) Str::ulid(),
                    'staff_id' => $staff->id,
                    'department_id' => $dept->id,
                    'job_title' => 'Associate',
                    'is_primary' => true,
                    'start_date' => now(),
                ]);
            }
        }
    }
}
