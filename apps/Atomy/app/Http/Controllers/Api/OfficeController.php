<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Nexus\Backoffice\Exceptions\DuplicateCodeException;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Exceptions\OfficeNotFoundException;
use Nexus\Backoffice\Contracts\OfficeRepositoryInterface;
use Nexus\Backoffice\Exceptions\CompanyNotFoundException;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Exceptions\InvalidOperationException;

/**
 * Office Management API Controller
 */
class OfficeController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly OfficeRepositoryInterface $repository,
        private readonly StaffRepositoryInterface $staffRepository
    ) {}

    /**
     * List all offices
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $status = $request->get('status');
        
        if ($companyId) {
            $offices = $status === 'active' 
                ? $this->repository->getActiveByCompany($companyId)
                : $this->repository->getByCompany($companyId);
        } else {
            // Get all offices - implementation depends on repository
            $offices = [];
        }

        return response()->json(['data' => $offices]);
    }

    /**
     * Store a new office
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|string|exists:companies,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:head_office,branch,regional,satellite,virtual',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'fax' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:50',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity' => 'nullable|integer|min:0',
            'operating_hours' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'metadata' => 'nullable|array',
        ]);

        try {
            $office = $this->manager->createOffice($validated);
            return response()->json(['data' => $office], 201);
        } catch (CompanyNotFoundException | OfficeNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (DuplicateCodeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (InvalidOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display a specific office
     */
    public function show(string $id): JsonResponse
    {
        $office = $this->manager->getOffice($id);
        
        if (!$office) {
            return response()->json(['error' => 'Office not found'], 404);
        }

        return response()->json(['data' => $office]);
    }

    /**
     * Update an existing office
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:head_office,branch,regional,satellite,virtual',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'fax' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:50',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity' => 'nullable|integer|min:0',
            'operating_hours' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'metadata' => 'nullable|array',
        ]);

        try {
            $office = $this->manager->updateOffice($id, $validated);
            return response()->json(['data' => $office]);
        } catch (OfficeNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (DuplicateCodeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (InvalidOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete an office
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->manager->deleteOffice($id);
            return response()->json(['success' => $deleted], $deleted ? 200 : 500);
        } catch (OfficeNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get offices by company
     */
    public function byCompany(string $companyId): JsonResponse
    {
        try {
            $offices = $this->repository->getByCompany($companyId);
            return response()->json(['data' => $offices]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get staff assigned to an office
     */
    public function staff(string $id): JsonResponse
    {
        try {
            $staff = $this->staffRepository->getByOffice($id);
            return response()->json(['data' => $staff]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
