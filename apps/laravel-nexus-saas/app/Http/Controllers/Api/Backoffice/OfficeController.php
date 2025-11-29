<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;

class OfficeController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly OfficeRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $offices = $this->repository->getAll();
        return response()->json($offices);
    }

    public function show(string $id): JsonResponse
    {
        $office = $this->manager->getOffice($id);
        if (!$office) {
            return response()->json(['message' => 'Office not found'], 404);
        }
        return response()->json($office);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            'code' => 'required|string',
            'name' => 'required|string',
            'type' => 'nullable|string',
            'address_line1' => 'nullable|string',
            'address_line2' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'required|string',
            'country' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'timezone' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        try {
            $office = $this->manager->createOffice($data);
            return response()->json($office, 201);
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
            'address_line1' => 'nullable|string',
            'address_line2' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'timezone' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        try {
            $office = $this->manager->updateOffice($id, $data);
            return response()->json($office);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->manager->deleteOffice($id);
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
