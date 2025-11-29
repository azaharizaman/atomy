<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;

class CompanyController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly CompanyRepositoryInterface $repository
    ) {}

    public function index(Request $request): Response
    {
        $companies = $this->repository->getAll();
        return Inertia::render('backoffice/companies/Index', [
            'companies' => $companies,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('backoffice/companies/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => 'required|string',
            'name' => 'required|string',
            'registration_number' => 'nullable|string',
            'tax_id' => 'nullable|string',
            'country' => 'required|string|size:2',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string',
            'status' => 'nullable|string',
        ]);

        $this->manager->createCompany($data);

        return to_route('backoffice.companies.index');
    }

    public function edit(string $id): Response
    {
        $company = $this->manager->getCompany($id);
        return Inertia::render('backoffice/companies/Edit', [
            'company' => $company,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'tax_id' => 'nullable|string',
            'country' => 'nullable|string|size:2',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $this->manager->updateCompany($id, $data);

        return to_route('backoffice.companies.index');
    }

    public function destroy(string $id)
    {
        $this->manager->deleteCompany($id);
        return to_route('backoffice.companies.index');
    }
}
