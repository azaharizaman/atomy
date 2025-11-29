<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;

class StaffController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly StaffRepositoryInterface $repository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly DepartmentRepositoryInterface $departmentRepository,
        private readonly OfficeRepositoryInterface $officeRepository
    ) {}

    public function index(Request $request): Response
    {
        // Assuming search([]) returns all staff or we can implement pagination later
        $staff = $this->repository->search([]);
        return Inertia::render('backoffice/staff/Index', [
            'staff' => $staff,
        ]);
    }

    public function create(): Response
    {
        $companies = $this->companyRepository->getAll();
        $departments = $this->departmentRepository->getAll();
        $offices = $this->officeRepository->getAll();
        
        return Inertia::render('backoffice/staff/Create', [
            'companies' => $companies,
            'departments' => $departments,
            'offices' => $offices,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            'employee_id' => 'required|string',
            'staff_code' => 'nullable|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'mobile' => 'nullable|string',
            'type' => 'required|string',
            'status' => 'required|string',
            'position' => 'nullable|string',
            'hire_date' => 'required|date',
            'department_id' => 'nullable|string',
            'office_id' => 'nullable|string',
        ]);

        $staff = $this->manager->createStaff($data);

        // Handle assignments if department or office is selected
        if (!empty($data['department_id'])) {
            $this->manager->assignStaffToDepartment($staff->getId(), $data['department_id'], 'member');
        }

        if (!empty($data['office_id'])) {
            $this->manager->assignStaffToOffice($staff->getId(), $data['office_id'], new \DateTimeImmutable());
        }

        return to_route('backoffice.staff.index');
    }

    public function edit(string $id): Response
    {
        $staff = $this->manager->getStaff($id);
        $companies = $this->companyRepository->getAll();
        $departments = $this->departmentRepository->getAll();
        $offices = $this->officeRepository->getAll();

        return Inertia::render('backoffice/staff/Edit', [
            'staff' => $staff,
            'companies' => $companies,
            'departments' => $departments,
            'offices' => $offices,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            'employee_id' => 'required|string',
            'staff_code' => 'nullable|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'mobile' => 'nullable|string',
            'type' => 'required|string',
            'status' => 'required|string',
            'position' => 'nullable|string',
            'hire_date' => 'required|date',
            'department_id' => 'nullable|string',
            'office_id' => 'nullable|string',
        ]);

        $this->manager->updateStaff($id, $data);

        // Handle assignments updates - simplified for now, might need more logic to handle changes
        if (!empty($data['department_id'])) {
            $this->manager->assignStaffToDepartment($id, $data['department_id'], 'member');
        }

        if (!empty($data['office_id'])) {
            $this->manager->assignStaffToOffice($id, $data['office_id'], new \DateTimeImmutable());
        }

        return to_route('backoffice.staff.index');
    }

    public function destroy(string $id)
    {
        $this->manager->deleteStaff($id);
        return to_route('backoffice.staff.index');
    }
}
