<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Http\Requests\Hrm\CreateEmployeeRequest;
use App\Http\Requests\Hrm\UpdateEmployeeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Hrm\Services\EmployeeManager;

/**
 * Employee API Controller
 * 
 * Manages employee lifecycle: create, update, confirm, terminate.
 */
class EmployeeController
{
    public function __construct(
        private readonly EmployeeManager $employeeManager
    ) {}
    
    /**
     * List employees with pagination and filters.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'employment_type' => $request->input('employment_type'),
            'department_id' => $request->input('department_id'),
            'office_id' => $request->input('office_id'),
            'manager_id' => $request->input('manager_id'),
            'search' => $request->input('search'),
        ];
        
        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $page = (int) $request->input('page', 1);
        
        // This would call a repository method in a real implementation
        // For now, returning a placeholder response
        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => 0,
            ],
        ]);
    }
    
    /**
     * Store a new employee.
     * 
     * @param CreateEmployeeRequest $request
     * @return JsonResponse
     */
    public function store(CreateEmployeeRequest $request): JsonResponse
    {
        $employeeId = $this->employeeManager->createEmployee(
            employeeCode: $request->input('employee_code'),
            firstName: $request->input('first_name'),
            lastName: $request->input('last_name'),
            email: $request->input('email'),
            dateOfBirth: new \DateTime($request->input('date_of_birth')),
            employmentType: $request->input('employment_type'),
            metadata: [
                'phone_number' => $request->input('phone_number'),
                'address' => $request->input('address'),
                'emergency_contact' => $request->input('emergency_contact'),
                'emergency_phone' => $request->input('emergency_phone'),
                'job_title' => $request->input('job_title'),
                'department_id' => $request->input('department_id'),
                'office_id' => $request->input('office_id'),
                'manager_id' => $request->input('manager_id'),
                'basic_salary' => $request->input('basic_salary'),
                'hire_date' => $request->input('hire_date'),
            ]
        );
        
        return response()->json([
            'message' => 'Employee created successfully',
            'data' => [
                'employee_id' => $employeeId,
            ],
        ], 201);
    }
    
    /**
     * Get a specific employee.
     * 
     * @param string $employeeId
     * @return JsonResponse
     */
    public function show(string $employeeId): JsonResponse
    {
        $employee = $this->employeeManager->getEmployee($employeeId);
        
        return response()->json([
            'data' => $employee,
        ]);
    }
    
    /**
     * Update an employee.
     * 
     * @param UpdateEmployeeRequest $request
     * @param string $employeeId
     * @return JsonResponse
     */
    public function update(UpdateEmployeeRequest $request, string $employeeId): JsonResponse
    {
        $updateData = $request->only([
            'employee_code',
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'address',
            'emergency_contact',
            'emergency_phone',
            'job_title',
            'department_id',
            'office_id',
            'manager_id',
            'basic_salary',
            'employment_type',
        ]);
        
        if ($request->has('date_of_birth')) {
            $updateData['date_of_birth'] = new \DateTime($request->input('date_of_birth'));
        }
        
        $this->employeeManager->updateEmployee($employeeId, $updateData);
        
        return response()->json([
            'message' => 'Employee updated successfully',
        ]);
    }
    
    /**
     * Confirm employee (probation completion).
     * 
     * @param string $employeeId
     * @param Request $request
     * @return JsonResponse
     */
    public function confirm(string $employeeId, Request $request): JsonResponse
    {
        $request->validate([
            'confirmation_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $this->employeeManager->confirmEmployee(
            $employeeId,
            new \DateTime($request->input('confirmation_date')),
            $request->input('notes')
        );
        
        return response()->json([
            'message' => 'Employee confirmed successfully',
        ]);
    }
    
    /**
     * Terminate employee.
     * 
     * @param string $employeeId
     * @param Request $request
     * @return JsonResponse
     */
    public function terminate(string $employeeId, Request $request): JsonResponse
    {
        $request->validate([
            'termination_date' => 'required|date',
            'termination_reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $this->employeeManager->terminateEmployee(
            $employeeId,
            new \DateTime($request->input('termination_date')),
            $request->input('termination_reason'),
            $request->input('notes')
        );
        
        return response()->json([
            'message' => 'Employee terminated successfully',
        ]);
    }
    
    /**
     * Delete employee (soft delete).
     * 
     * @param string $employeeId
     * @return JsonResponse
     */
    public function destroy(string $employeeId): JsonResponse
    {
        // Soft delete - actual implementation would be in repository
        return response()->json([
            'message' => 'Employee deleted successfully',
        ]);
    }
}
