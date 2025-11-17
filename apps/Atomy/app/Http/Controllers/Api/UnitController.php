<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\UnitRepositoryInterface;
use Nexus\Backoffice\Exceptions\UnitNotFoundException;
use Nexus\Backoffice\Exceptions\StaffNotFoundException;
use Nexus\Backoffice\Exceptions\CompanyNotFoundException;
use Nexus\Backoffice\Exceptions\DuplicateCodeException;
use Nexus\Backoffice\Exceptions\InvalidOperationException;

/**
 * Unit Management API Controller
 */
class UnitController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly UnitRepositoryInterface $repository
    ) {}

    /**
     * List all units
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $status = $request->get('status');
        
        if ($companyId) {
            $units = $status === 'active' 
                ? $this->repository->getActiveByCompany($companyId)
                : $this->repository->getByCompany($companyId);
        } else {
            $units = [];
        }

        return response()->json(['data' => $units]);
    }

    /**
     * Store a new unit
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|string|exists:companies,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:project_team,committee,task_force,working_group,center_of_excellence',
            'leader_staff_id' => 'nullable|string|exists:staff,id',
            'deputy_leader_staff_id' => 'nullable|string|exists:staff,id',
            'purpose' => 'nullable|string',
            'objectives' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string|in:active,inactive,completed,dissolved',
            'metadata' => 'nullable|array',
        ]);

        try {
            $unit = $this->manager->createUnit($validated);
            return response()->json(['data' => $unit], 201);
        } catch (CompanyNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (DuplicateCodeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display a specific unit
     */
    public function show(string $id): JsonResponse
    {
        $unit = $this->manager->getUnit($id);
        
        if (!$unit) {
            return response()->json(['error' => 'Unit not found'], 404);
        }

        return response()->json(['data' => $unit]);
    }

    /**
     * Update an existing unit
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:project_team,committee,task_force,working_group,center_of_excellence',
            'leader_staff_id' => 'nullable|string|exists:staff,id',
            'deputy_leader_staff_id' => 'nullable|string|exists:staff,id',
            'purpose' => 'nullable|string',
            'objectives' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string|in:active,inactive,completed,dissolved',
            'metadata' => 'nullable|array',
        ]);

        try {
            $unit = $this->manager->updateUnit($id, $validated);
            return response()->json(['data' => $unit]);
        } catch (UnitNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (DuplicateCodeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a unit
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->manager->deleteUnit($id);
            return response()->json(['success' => $deleted], $deleted ? 200 : 500);
        } catch (UnitNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Add member to unit
     */
    public function addMember(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|string|exists:staff,id',
            'role' => 'required|string|in:leader,member,secretary,advisor',
        ]);

        try {
            $this->manager->addUnitMember($id, $validated['staff_id'], $validated['role']);
            return response()->json(['success' => true]);
        } catch (UnitNotFoundException | StaffNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove member from unit
     */
    public function removeMember(string $id, string $staffId): JsonResponse
    {
        try {
            $this->manager->removeUnitMember($id, $staffId);
            return response()->json(['success' => true]);
        } catch (UnitNotFoundException | StaffNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Get unit members
     */
    public function members(string $id): JsonResponse
    {
        try {
            $members = $this->repository->getMembers($id);
            return response()->json(['data' => $members]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
