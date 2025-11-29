<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;

class OfficeController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly OfficeRepositoryInterface $repository,
        private readonly CompanyRepositoryInterface $companyRepository
    ) {}

    public function index(Request $request): Response
    {
        $offices = $this->repository->getAll();
        return Inertia::render('backoffice/offices/Index', [
            'offices' => $offices,
        ]);
    }

    public function create(): Response
    {
        $companies = $this->companyRepository->getAll();
        return Inertia::render('backoffice/offices/Create', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            'code' => 'required|string',
            'name' => 'required|string',
            'type' => 'nullable|string',
            'country' => 'required|string|size:2',
            'postal_code' => 'required|string',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'is_head_office' => 'boolean',
        ]);

        $this->manager->createOffice($data);

        return to_route('backoffice.offices.index');
    }

    public function edit(string $id): Response
    {
        $office = $this->manager->getOffice($id);
        $companies = $this->companyRepository->getAll();
        return Inertia::render('backoffice/offices/Edit', [
            'office' => $office,
            'companies' => $companies,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            'code' => 'required|string',
            'name' => 'required|string',
            'type' => 'nullable|string',
            'country' => 'required|string|size:2',
            'postal_code' => 'required|string',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'is_head_office' => 'boolean',
        ]);

        $this->manager->updateOffice($id, $data);

        return to_route('backoffice.offices.index');
    }

    public function destroy(string $id)
    {
        $this->manager->deleteOffice($id);
        return to_route('backoffice.offices.index');
    }
}
