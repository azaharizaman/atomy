<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;

class StaffController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly StaffRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->query('company_id');

        if (!$companyId) {
            return response()->json([
                'message' => 'company_id query parameter is required'
            ], 400);
        }

        $staff = $this->repository->getByCompany($companyId);
        return response()->json($staff);
    }

    public function show(string $id): JsonResponse
    {
        $staff = $this->manager->getStaff($id);
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }
        return response()->json($staff);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            'user_id' => 'nullable|string',
            'employee_number' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'position' => 'nullable|string',
            'department_id' => 'nullable|string',
            'office_id' => 'nullable|string',
            'status' => 'nullable|string',
            'joined_at' => 'nullable|date',
        ]);

        try {
            $staff = $this->manager->createStaff($data);
            return response()->json($staff, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'nullable|string',
            'employee_number' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'position' => 'nullable|string',
            'department_id' => 'nullable|string',
            'office_id' => 'nullable|string',
            'status' => 'nullable|string',
            'joined_at' => 'nullable|date',
        ]);

        try {
            $staff = $this->manager->updateStaff($id, $data);
            return response()->json($staff);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->manager->deleteStaff($id);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
