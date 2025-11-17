<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Http\Requests\Hrm\ClockInRequest;
use App\Http\Requests\Hrm\ClockOutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Hrm\Services\AttendanceManager;

/**
 * Attendance API Controller
 * 
 * Handles employee attendance clock-in/clock-out operations.
 */
class AttendanceController
{
    public function __construct(
        private readonly AttendanceManager $attendanceManager
    ) {}
    
    /**
     * List attendance records with filters.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'employee_id' => $request->input('employee_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'status' => $request->input('status'),
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
     * Clock in employee.
     * 
     * @param ClockInRequest $request
     * @return JsonResponse
     */
    public function clockIn(ClockInRequest $request): JsonResponse
    {
        $attendanceId = $this->attendanceManager->clockIn(
            employeeId: $request->input('employee_id'),
            clockInTime: new \DateTime($request->input('clock_in_time')),
            metadata: [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'location_name' => $request->input('location_name'),
                'notes' => $request->input('notes'),
            ]
        );
        
        return response()->json([
            'message' => 'Clocked in successfully',
            'data' => [
                'attendance_id' => $attendanceId,
            ],
        ], 201);
    }
    
    /**
     * Clock out employee.
     * 
     * @param ClockOutRequest $request
     * @return JsonResponse
     */
    public function clockOut(ClockOutRequest $request): JsonResponse
    {
        $this->attendanceManager->clockOut(
            attendanceId: $request->input('attendance_id'),
            clockOutTime: new \DateTime($request->input('clock_out_time')),
            metadata: [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'location_name' => $request->input('location_name'),
                'notes' => $request->input('notes'),
            ]
        );
        
        return response()->json([
            'message' => 'Clocked out successfully',
        ]);
    }
    
    /**
     * Get specific attendance record.
     * 
     * @param string $attendanceId
     * @return JsonResponse
     */
    public function show(string $attendanceId): JsonResponse
    {
        $attendance = $this->attendanceManager->getAttendance($attendanceId);
        
        return response()->json([
            'data' => $attendance,
        ]);
    }
}
