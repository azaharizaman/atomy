<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Http\Requests\Hrm\CreateLeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Hrm\Services\LeaveManager;

/**
 * Leave Management API Controller
 * 
 * Handles leave requests: create, approve, reject, cancel.
 */
class LeaveController
{
    public function __construct(
        private readonly LeaveManager $leaveManager
    ) {}
    
    /**
     * List leave requests with filters.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'employee_id' => $request->input('employee_id'),
            'leave_type_id' => $request->input('leave_type_id'),
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
        
        $perPage = min((int) $request->input('per_page', 15), 100);
        $page = (int) $request->input('page', 1);
        
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
     * Create a new leave request.
     * 
     * @param CreateLeaveRequest $request
     * @return JsonResponse
     */
    public function store(CreateLeaveRequest $request): JsonResponse
    {
        $leaveId = $this->leaveManager->requestLeave(
            employeeId: $request->input('employee_id'),
            leaveTypeId: $request->input('leave_type_id'),
            startDate: new \DateTime($request->input('start_date')),
            endDate: new \DateTime($request->input('end_date')),
            reason: $request->input('reason'),
            metadata: [
                'half_day' => $request->input('half_day', false),
                'contact_number' => $request->input('contact_number'),
                'emergency_contact' => $request->input('emergency_contact'),
            ]
        );
        
        return response()->json([
            'message' => 'Leave request created successfully',
            'data' => [
                'leave_id' => $leaveId,
            ],
        ], 201);
    }
    
    /**
     * Get a specific leave request.
     * 
     * @param string $leaveId
     * @return JsonResponse
     */
    public function show(string $leaveId): JsonResponse
    {
        $leave = $this->leaveManager->getLeaveRequest($leaveId);
        
        return response()->json([
            'data' => $leave,
        ]);
    }
    
    /**
     * Approve a leave request.
     * 
     * @param string $leaveId
     * @param Request $request
     * @return JsonResponse
     */
    public function approve(string $leaveId, Request $request): JsonResponse
    {
        $request->validate([
            'approver_id' => 'required|string',
            'remarks' => 'nullable|string|max:500',
        ]);
        
        $this->leaveManager->approveLeave(
            $leaveId,
            $request->input('approver_id'),
            $request->input('remarks')
        );
        
        return response()->json([
            'message' => 'Leave request approved successfully',
        ]);
    }
    
    /**
     * Reject a leave request.
     * 
     * @param string $leaveId
     * @param Request $request
     * @return JsonResponse
     */
    public function reject(string $leaveId, Request $request): JsonResponse
    {
        $request->validate([
            'approver_id' => 'required|string',
            'rejection_reason' => 'required|string|max:500',
        ]);
        
        $this->leaveManager->rejectLeave(
            $leaveId,
            $request->input('approver_id'),
            $request->input('rejection_reason')
        );
        
        return response()->json([
            'message' => 'Leave request rejected successfully',
        ]);
    }
    
    /**
     * Cancel a leave request.
     * 
     * @param string $leaveId
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel(string $leaveId, Request $request): JsonResponse
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);
        
        $this->leaveManager->cancelLeave(
            $leaveId,
            $request->input('cancellation_reason')
        );
        
        return response()->json([
            'message' => 'Leave request cancelled successfully',
        ]);
    }
}
