<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Hrm Package
 * 
 * This example demonstrates:
 * 1. Employee creation and lifecycle management
 * 2. Leave request creation and approval
 * 3. Attendance tracking (clock-in/clock-out)
 * 4. Basic HR workflows
 */

use Nexus\Hrm\Services\EmployeeManager;
use Nexus\Hrm\Services\LeaveManager;
use Nexus\Hrm\Services\AttendanceManager;
use Nexus\Hrm\ValueObjects\EmploymentType;
use Nexus\Hrm\ValueObjects\EmployeeStatus;

// ============================================
// Step 1: Create a New Employee
// ============================================

/** @var EmployeeManager $employeeManager */
$employeeManager = app(EmployeeManager::class);

$employee = $employeeManager->createEmployee([
    'employee_code' => 'EMP001',
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'email' => 'jane.smith@company.com',
    'phone_number' => '+60123456789',
    'date_of_birth' => '1992-05-15',
    'hire_date' => '2025-01-01',
    'employment_type' => EmploymentType::full_time,
    'job_title' => 'Software Engineer',
]);

echo "Employee created: {$employee->getEmployeeCode()} - {$employee->getFullName()}\n";
echo "Status: {$employee->getStatus()}\n"; // probationary
echo "Hire Date: {$employee->getHireDate()->format('Y-m-d')}\n\n";

// ============================================
// Step 2: Confirm Employee After Probation
// ============================================

// After 3 months probation period
$confirmationDate = new \DateTimeImmutable('2025-04-01');

$confirmedEmployee = $employeeManager->confirmEmployee(
    $employee->getId(),
    $confirmationDate
);

echo "Employee confirmed on: {$confirmedEmployee->getConfirmationDate()->format('Y-m-d')}\n";
echo "New status: {$confirmedEmployee->getStatus()}\n\n"; // confirmed

// ============================================
// Step 3: Create Leave Request
// ============================================

/** @var LeaveManager $leaveManager */
$leaveManager = app(LeaveManager::class);

// First, check leave balance
$leaveBalance = $leaveManager->getLeaveBalance(
    $employee->getId(),
    'lt_annual_leave_001' // Annual leave type ID
);

echo "Leave Balance:\n";
echo "- Entitled: {$leaveBalance->getEntitled()} days\n";
echo "- Available: {$leaveBalance->getAvailable()} days\n";
echo "- Used: {$leaveBalance->getUsed()} days\n\n";

// Create leave request
$leaveRequest = $leaveManager->createLeaveRequest([
    'employee_id' => $employee->getId(),
    'leave_type_id' => 'lt_annual_leave_001',
    'start_date' => '2025-06-01',
    'end_date' => '2025-06-05',
    'days_requested' => 5.0,
    'reason' => 'Annual vacation to Bali',
]);

echo "Leave request created:\n";
echo "- Period: {$leaveRequest->getStartDate()->format('Y-m-d')} to {$leaveRequest->getEndDate()->format('Y-m-d')}\n";
echo "- Days: {$leaveRequest->getDaysRequested()}\n";
echo "- Status: {$leaveRequest->getStatus()}\n\n"; // pending

// ============================================
// Step 4: Approve Leave Request (by Manager)
// ============================================

$managerId = 'emp_manager_001'; // Manager's employee ID

$approvedLeave = $leaveManager->approveLeaveRequest(
    $leaveRequest->getId(),
    $managerId
);

echo "Leave approved by: {$approvedLeave->getApprovedBy()}\n";
echo "Approved at: {$approvedLeave->getApprovedAt()->format('Y-m-d H:i:s')}\n";
echo "Status: {$approvedLeave->getStatus()}\n\n"; // approved

// Check updated balance
$updatedBalance = $leaveManager->getLeaveBalance(
    $employee->getId(),
    'lt_annual_leave_001'
);

echo "Updated Leave Balance:\n";
echo "- Available: {$updatedBalance->getAvailable()} days\n";
echo "- Used: {$updatedBalance->getUsed()} days\n\n";

// ============================================
// Step 5: Track Daily Attendance
// ============================================

/** @var AttendanceManager $attendanceManager */
$attendanceManager = app(AttendanceManager::class);

// Clock in at start of day
$attendance = $attendanceManager->clockIn($employee->getId(), [
    'location' => 'Office HQ',
    'latitude' => 3.1390,
    'longitude' => 101.6869,
]);

echo "Clocked in:\n";
echo "- Time: {$attendance->getClockInTime()->format('Y-m-d H:i:s')}\n";
echo "- Location: {$attendance->getLocation()}\n";
echo "- GPS: {$attendance->getLatitude()}, {$attendance->getLongitude()}\n\n";

// Clock out at end of day
sleep(1); // Simulate time passing
$clockedOut = $attendanceManager->clockOut($attendance->getId(), [
    'break_minutes' => 60, // 1 hour lunch break
]);

echo "Clocked out:\n";
echo "- Time: {$clockedOut->getClockOutTime()->format('Y-m-d H:i:s')}\n";
echo "- Hours Worked: {$clockedOut->getHoursWorked()}\n";
echo "- Break: {$clockedOut->getBreakMinutes()} minutes\n";
echo "- Overtime: {$clockedOut->getOvertimeHours()} hours\n\n";

// ============================================
// Step 6: Get Monthly Attendance Summary
// ============================================

$summary = $attendanceManager->getMonthlyAttendanceSummary(
    $employee->getId(),
    2025,
    6
);

echo "Monthly Attendance Summary (June 2025):\n";
echo "- Total Days: {$summary['total_days']}\n";
echo "- Present Days: {$summary['present_days']}\n";
echo "- Absent Days: {$summary['absent_days']}\n";
echo "- Total Hours: {$summary['total_hours']}\n";
echo "- Overtime Hours: {$summary['overtime_hours']}\n";
echo "- Late Arrivals: {$summary['late_arrivals']}\n\n";

// ============================================
// Step 7: Update Employee Information
// ============================================

$updatedEmployee = $employeeManager->updateEmployee($employee->getId(), [
    'job_title' => 'Senior Software Engineer',
    'phone_number' => '+60123456790',
]);

echo "Employee updated:\n";
echo "- New Job Title: {$updatedEmployee->getJobTitle()}\n";
echo "- New Phone: {$updatedEmployee->getPhoneNumber()}\n\n";

// ============================================
// Step 8: Get All Employees (with filters)
// ============================================

$activeEmployees = $employeeManager->getAllEmployees([
    'status' => EmployeeStatus::confirmed->value,
    'department_id' => 'dept_engineering_001',
]);

echo "Active Employees in Engineering Department: " . count($activeEmployees) . "\n";
foreach ($activeEmployees as $emp) {
    echo "- {$emp->getEmployeeCode()}: {$emp->getFullName()} ({$emp->getJobTitle()})\n";
}

echo "\n";

// ============================================
// Expected Output Summary
// ============================================

echo "==============================================\n";
echo "EXAMPLE EXECUTION COMPLETE\n";
echo "==============================================\n";
echo "✅ Employee created and confirmed\n";
echo "✅ Leave request created and approved\n";
echo "✅ Attendance tracked with clock-in/out\n";
echo "✅ Monthly summary generated\n";
echo "✅ Employee information updated\n";
echo "==============================================\n";

/*
 * Key Takeaways:
 * 
 * 1. All operations use manager services (EmployeeManager, LeaveManager, AttendanceManager)
 * 2. Managers handle business logic and validation
 * 3. Repository interfaces handle persistence (implementation in your app)
 * 4. Value Objects (Enums) ensure type safety
 * 5. All dates use DateTimeImmutable for immutability
 * 6. Multi-tenancy is automatic via TenantContextInterface
 */
