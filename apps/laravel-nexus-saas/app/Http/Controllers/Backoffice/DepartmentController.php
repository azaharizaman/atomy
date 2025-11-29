<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly DepartmentRepositoryInterface $repository,
        private readonly CompanyRepositoryInterface $companyRepository
    ) {}

    public function index(Request $request): Response
    {
        $departments = $this->repository->getAll();
        return Inertia::render('backoffice/departments/Index', [
            'departments' => $departments,
        ]);
    }

    public function create(): Response
    {
        $companies = $this->companyRepository->getAll();
        // Ideally we would also fetch parent departments and potential managers here
        return Inertia::render('backoffice/departments/Create', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            'code' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
            'status' => 'required|string',
            'parent_department_id' => 'nullable|string',
            'manager_staff_id' => 'nullable|string',
            'cost_center' => 'nullable|string',
            'budget_amount' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $this->manager->createDepartment($data);

        return to_route('backoffice.departments.index');
    }

    public function edit(string $id): Response
    {
        $department = $this->manager->getDepartment($id);
        $companies = $this->companyRepository->getAll();
        return Inertia::render('backoffice/departments/Edit', [
            'department' => $department,
            'companies' => $companies,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            'code' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
            'status' => 'required|string',
            'parent_department_id' => 'nullable|string',
            'manager_staff_id' => 'nullable|string',
            'cost_center' => 'nullable|string',
            'budget_amount' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $this->manager->updateDepartment($id, $data);

        return to_route('backoffice.departments.index');
    }

    public function destroy(string $id)
    {
        $this->manager->deleteDepartment($id);
        return to_route('backoffice.departments.index');
    }
}
