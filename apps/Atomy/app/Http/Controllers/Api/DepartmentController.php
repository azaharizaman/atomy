<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\DepartmentRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Exceptions\DepartmentNotFoundException;
use Nexus\Backoffice\Exceptions\CompanyNotFoundException;
use Nexus\Backoffice\Exceptions\DuplicateCodeException;
use Nexus\Backoffice\Exceptions\InvalidOperationException;
use Nexus\Backoffice\Exceptions\InvalidHierarchyException;
use Nexus\Backoffice\Exceptions\CircularReferenceException;

/**
 * Department Management API Controller
 */
class DepartmentController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly DepartmentRepositoryInterface $repository,
        private readonly StaffRepositoryInterface $staffRepository
    ) {}

    /**
     * List all departments
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $status = $request->get('status');
        
        if ($companyId) {
            $departments = $status === 'active' 
                ? $this->repository->getActiveByCompany($companyId)
                : $this->repository->getByCompany($companyId);
        } else {
            $departments = [];
        }

        return response()->json(['data' => $departments]);
    }

    /**
     * Store a new department
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|string|exists:companies,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:functional,divisional,matrix,project_based',
            'parent_department_id' => 'nullable|string|exists:departments,id',
            'manager_staff_id' => 'nullable|string|exists:staff,id',
            'cost_center' => 'nullable|string|max:50',
            'budget_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'metadata' => 'nullable|array',
        ]);

        try {
            $department = $this->manager->createDepartment($validated);
            return response()->json(['data' => $department], 201);
        } catch (CompanyNotFoundException | DepartmentNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (DuplicateCodeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (InvalidHierarchyException | CircularReferenceException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display a specific department
     */
    public function show(string $id): JsonResponse
    {
        $department = $this->manager->getDepartment($id);
        
        if (!$department) {
            return response()->json(['error' => 'Department not found'], 404);
        }

        return response()->json(['data' => $department]);
    }

    /**
     * Update an existing department
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:functional,divisional,matrix,project_based',
            'parent_department_id' => 'nullable|string|exists:departments,id',
            'manager_staff_id' => 'nullable|string|exists:staff,id',
            'cost_center' => 'nullable|string|max:50',
            'budget_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'metadata' => 'nullable|array',
        ]);

        try {
            $department = $this->manager->updateDepartment($id, $validated);
            return response()->json(['data' => $department]);
        } catch (DepartmentNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (DuplicateCodeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (InvalidHierarchyException | CircularReferenceException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a department
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->manager->deleteDepartment($id);
            return response()->json(['success' => $deleted], $deleted ? 200 : 500);
        } catch (DepartmentNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get departments by company
     */
    public function byCompany(string $companyId): JsonResponse
    {
        try {
            $departments = $this->repository->getByCompany($companyId);
            return response()->json(['data' => $departments]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get sub-departments
     */
    public function subDepartments(string $id): JsonResponse
    {
        try {
            $subDepartments = $this->repository->getSubDepartments($id);
            return response()->json(['data' => $subDepartments]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get staff in department
     */
    public function staff(string $id): JsonResponse
    {
        try {
            $staff = $this->staffRepository->getByDepartment($id);
            return response()->json(['data' => $staff]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
