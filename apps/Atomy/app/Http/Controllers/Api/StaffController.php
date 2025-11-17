<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Backoffice\Contracts\BackofficeManagerInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\Backoffice\Exceptions\StaffNotFoundException;
use Nexus\Backoffice\Exceptions\DepartmentNotFoundException;
use Nexus\Backoffice\Exceptions\OfficeNotFoundException;
use Nexus\Backoffice\Exceptions\DuplicateCodeException;
use Nexus\Backoffice\Exceptions\InvalidOperationException;
use Nexus\Backoffice\Exceptions\InvalidHierarchyException;
use Nexus\Backoffice\Exceptions\CircularReferenceException;

/**
 * Staff Management API Controller
 */
class StaffController extends Controller
{
    public function __construct(
        private readonly BackofficeManagerInterface $manager,
        private readonly StaffRepositoryInterface $repository
    ) {}

    /**
     * List all staff with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $departmentId = $request->get('department_id');
        $officeId = $request->get('office_id');
        $search = $request->get('search');
        
        if ($companyId) {
            $staff = $this->repository->getActiveByCompany($companyId);
        } elseif ($departmentId) {
            $staff = $this->repository->getByDepartment($departmentId);
        } elseif ($officeId) {
            $staff = $this->repository->getByOffice($officeId);
        } elseif ($search) {
            $staff = $this->repository->search(['search' => $search]);
        } else {
            $staff = [];
        }

        return response()->json(['data' => $staff]);
    }

    /**
     * Store a new staff member
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|max:50|unique:staff,employee_id',
            'staff_code' => 'nullable|string|max:50|unique:staff,staff_code',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'type' => 'required|string|in:permanent,contract,temporary,intern,consultant',
            'status' => 'nullable|string|in:active,inactive,on_leave,terminated',
            'hire_date' => 'required|date',
            'termination_date' => 'nullable|date',
            'position' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:50',
            'salary_band' => 'nullable|string|max:50',
            'probation_end_date' => 'nullable|date',
            'confirmation_date' => 'nullable|date',
            'photo_url' => 'nullable|string|max:500',
            'company_id' => 'nullable|string|exists:companies,id',
            'supervisor_id' => 'nullable|string|exists:staff,id',
            'metadata' => 'nullable|array',
        ]);

        try {
            $staff = $this->manager->createStaff($validated);
            return response()->json(['data' => $staff], 201);
        } catch (DuplicateCodeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (StaffNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (CircularReferenceException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display a specific staff member
     */
    public function show(string $id): JsonResponse
    {
        $staff = $this->manager->getStaff($id);
        
        if (!$staff) {
            return response()->json(['error' => 'Staff not found'], 404);
        }

        return response()->json(['data' => $staff]);
    }

    /**
     * Update an existing staff member
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'sometimes|string|max:50',
            'staff_code' => 'nullable|string|max:50',
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'type' => 'sometimes|string|in:permanent,contract,temporary,intern,consultant',
            'status' => 'nullable|string|in:active,inactive,on_leave,terminated',
            'hire_date' => 'sometimes|date',
            'termination_date' => 'nullable|date',
            'position' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:50',
            'salary_band' => 'nullable|string|max:50',
            'probation_end_date' => 'nullable|date',
            'confirmation_date' => 'nullable|date',
            'photo_url' => 'nullable|string|max:500',
            'company_id' => 'nullable|string|exists:companies,id',
            'supervisor_id' => 'nullable|string|exists:staff,id',
            'metadata' => 'nullable|array',
        ]);

        try {
            $staff = $this->manager->updateStaff($id, $validated);
            return response()->json(['data' => $staff]);
        } catch (StaffNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (DuplicateCodeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (CircularReferenceException | InvalidHierarchyException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a staff member
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->manager->deleteStaff($id);
            return response()->json(['success' => $deleted], $deleted ? 200 : 500);
        } catch (StaffNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Assign staff to department
     */
    public function assignDepartment(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|string|exists:departments,id',
            'role' => 'required|string|max:255',
            'is_primary' => 'nullable|boolean',
        ]);

        try {
            $this->manager->assignStaffToDepartment(
                $id,
                $validated['department_id'],
                $validated['role'],
                $validated['is_primary'] ?? false
            );
            return response()->json(['success' => true]);
        } catch (StaffNotFoundException | DepartmentNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Assign staff to office
     */
    public function assignOffice(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'office_id' => 'required|string|exists:offices,id',
            'effective_date' => 'required|date',
        ]);

        try {
            $this->manager->assignStaffToOffice(
                $id,
                $validated['office_id'],
                new \DateTime($validated['effective_date'])
            );
            return response()->json(['success' => true]);
        } catch (StaffNotFoundException | OfficeNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (InvalidOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Set supervisor for staff
     */
    public function setSupervisor(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'supervisor_id' => 'required|string|exists:staff,id',
        ]);

        try {
            $this->manager->setSupervisor($id, $validated['supervisor_id']);
            return response()->json(['success' => true]);
        } catch (StaffNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (CircularReferenceException | InvalidHierarchyException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get staff assignments (placeholder for application-level logic)
     */
    public function assignments(string $id): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'To be implemented']);
    }

    /**
     * Get direct reports
     */
    public function directReports(string $id): JsonResponse
    {
        try {
            $reports = $this->repository->getDirectReports($id);
            return response()->json(['data' => $reports]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all reports (recursive)
     */
    public function allReports(string $id): JsonResponse
    {
        try {
            $reports = $this->repository->getAllReports($id);
            return response()->json(['data' => $reports]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get supervisor chain
     */
    public function supervisorChain(string $id): JsonResponse
    {
        try {
            $chain = $this->repository->getSupervisorChain($id);
            return response()->json(['data' => $chain]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
