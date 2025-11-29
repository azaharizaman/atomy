<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;

class CompanyController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly CompanyRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companies = $this->repository->getAll();
        return response()->json($companies);
    }

    public function show(string $id): JsonResponse
    {
        $company = $this->manager->getCompany($id);
        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }
        return response()->json($company);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'registration_number' => 'nullable|string',
            'tax_id' => 'nullable|string',
            'country' => 'required|string',
            'currency' => 'required|string',
            'parent_company_id' => 'nullable|string',
            'status' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        try {
            $company = $this->manager->createCompany($data);
            return response()->json($company, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'code' => 'nullable|string',
            'name' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'tax_id' => 'nullable|string',
            'country' => 'nullable|string',
            'currency' => 'nullable|string',
            'parent_company_id' => 'nullable|string',
            'status' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        try {
            $company = $this->manager->updateCompany($id, $data);
            return response()->json($company);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->manager->deleteCompany($id);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
