<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly DepartmentRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->query('company_id');

        if (!$companyId) {
            return response()->json([
                'message' => 'company_id query parameter is required'
            ], 400);
        }

        $departments = $this->repository->getByCompany($companyId);
        return response()->json($departments);
    }

    public function show(string $id): JsonResponse
    {
        $department = $this->manager->getDepartment($id);
        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }
        return response()->json($department);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            'code' => 'required|string',
            'name' => 'required|string',
            'parent_department_id' => 'nullable|string',
            'manager_id' => 'nullable|string',
            'cost_center' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        try {
            $department = $this->manager->createDepartment($data);
            return response()->json($department, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'code' => 'nullable|string',
            'name' => 'nullable|string',
            'parent_department_id' => 'nullable|string',
            'manager_id' => 'nullable|string',
            'cost_center' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        try {
            $department = $this->manager->updateDepartment($id, $data);
            return response()->json($department);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->manager->deleteDepartment($id);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
