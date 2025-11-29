<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\UnitRepositoryInterface;

class UnitController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly UnitRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->query('company_id');

        if (!$companyId) {
            return response()->json([
                'message' => 'company_id query parameter is required'
            ], 400);
        }

        $units = $this->repository->getByCompany($companyId);
        return response()->json($units);
    }

    public function show(string $id): JsonResponse
    {
        $unit = $this->manager->getUnit($id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], 404);
        }
        return response()->json($unit);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            'code' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        try {
            $unit = $this->manager->createUnit($data);
            return response()->json($unit, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'code' => 'nullable|string',
            'name' => 'nullable|string',
            'type' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        try {
            $unit = $this->manager->updateUnit($id, $data);
            return response()->json($unit);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->manager->deleteUnit($id);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
