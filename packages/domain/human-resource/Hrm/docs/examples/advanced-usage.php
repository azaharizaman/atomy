<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Hrm Package
 * 
 * This example demonstrates:
 * 1. Performance review workflow (self-assessment + manager review)
 * 2. Disciplinary case management
 * 3. Training program enrollment and completion
 * 4. Complex leave scenarios (overlap prevention, balance validation)
 * 5. Integration with Nexus\Backoffice for organizational structure
 */

use Nexus\Hrm\Services\EmployeeManager;
use Nexus\Hrm\Services\PerformanceReviewManager;
use Nexus\Hrm\Services\DisciplinaryManager;
use Nexus\Hrm\Services\TrainingManager;
use Nexus\Hrm\Services\LeaveManager;
use Nexus\Hrm\ValueObjects\ReviewType;
use Nexus\Hrm\ValueObjects\ReviewStatus;
use Nexus\Hrm\ValueObjects\DisciplinarySeverity;
use Nexus\Hrm\ValueObjects\TrainingStatus;
use Nexus\Hrm\Contracts\OrganizationServiceContract;

// ============================================
// Scenario 1: Annual Performance Review Cycle
// ============================================

/** @var PerformanceReviewManager $reviewManager */
$reviewManager = app(PerformanceReviewManager::class);

/** @var EmployeeManager $employeeManager */
$employeeManager = app(EmployeeManager::class);

$employeeId = 'emp_001';
$managerId = 'emp_mgr_001';

// Create annual performance review
$review = $reviewManager->createPerformanceReview([
    'employee_id' => $employeeId,
    'review_cycle_id' => '2025-Annual',
    'review_type' => ReviewType::annual,
    'reviewer_id' => $managerId,
    'review_period_start' => '2024-01-01',
    'review_period_end' => '2024-12-31',
]);

echo "Performance Review Created:\n";
echo "- Review ID: {$review->getId()}\n";
echo "- Cycle: {$review->getReviewCycleId()}\n";
echo "- Type: {$review->getReviewType()}\n";
echo "- Status: {$review->getStatus()}\n\n"; // draft

// Employee submits self-assessment
$selfAssessment = $reviewManager->submitSelfAssessment($review->getId(), [
    'score' => 4.5,
    'comments' => 'Exceeded targets in Q3 and Q4. Led migration to microservices architecture.',
    'achievements' => [
        'Reduced system latency by 40%',
        'Mentored 3 junior developers',
        'Completed AWS certification',
    ],
    'areas_for_improvement' => [
        'Better time management',
        'Public speaking skills',
    ],
]);

echo "Self-Assessment Submitted:\n";
echo "- Self Score: {$selfAssessment->getSelfAssessmentScore()}\n";
echo "- Status: {$selfAssessment->getStatus()}\n\n"; // manager_review_pending

// Manager submits review
$managerReview = $reviewManager->submitManagerReview($review->getId(), [
    'score' => 4.2,
    'comments' => 'Strong technical performance. Needs to improve stakeholder communication.',
    'strengths' => [
        'Technical excellence',
        'Problem-solving',
        'Team collaboration',
    ],
    'development_areas' => [
        'Project management',
        'Client communication',
    ],
    'recommended_actions' => [
        'Enroll in leadership training',
        'Shadow senior PMs',
    ],
]);

echo "Manager Review Submitted:\n";
echo "- Manager Score: {$managerReview->getManagerAssessmentScore()}\n";
echo "- Final Rating: {$managerReview->getFinalRating()}\n";
echo "- Status: {$managerReview->getStatus()}\n\n"; // completed

// ============================================
// Scenario 2: 360-Degree Feedback Review
// ============================================

$review360 = $reviewManager->createPerformanceReview([
    'employee_id' => $employeeId,
    'review_cycle_id' => '2025-Q2-360',
    'review_type' => ReviewType::feedback_360,
    'reviewer_id' => $managerId,
    'review_period_start' => '2025-04-01',
    'review_period_end' => '2025-06-30',
]);

// Collect peer feedback
$peerFeedback = [
    [
        'reviewer_id' => 'emp_peer_001',
        'score' => 4.3,
        'comments' => 'Great team player, very responsive to code reviews.',
    ],
    [
        'reviewer_id' => 'emp_peer_002',
        'score' => 4.5,
        'comments' => 'Excellent technical skills, always willing to help.',
    ],
    [
        'reviewer_id' => 'emp_peer_003',
        'score' => 4.0,
        'comments' => 'Good collaborator, could improve documentation.',
    ],
];

$reviewManager->addPeerFeedback($review360->getId(), $peerFeedback);

echo "360-Degree Feedback Collected:\n";
echo "- Peer Reviews: " . count($peerFeedback) . "\n";
echo "- Average Peer Score: 4.27\n\n";

// ============================================
// Scenario 3: Disciplinary Case Management
// ============================================

/** @var DisciplinaryManager $disciplinaryManager */
$disciplinaryManager = app(DisciplinaryManager::class);

// Create disciplinary case for policy violation
$disciplinaryCase = $disciplinaryManager->createDisciplinaryCase([
    'employee_id' => 'emp_002',
    'case_number' => 'DISC-2025-001',
    'incident_date' => '2025-05-15',
    'severity' => DisciplinarySeverity::moderate,
    'description' => 'Unauthorized access to production database outside of approved change window.',
    'evidence' => [
        'type' => 'audit_log',
        'timestamp' => '2025-05-15 22:30:00',
        'action' => 'SELECT * FROM customers',
    ],
]);

echo "Disciplinary Case Created:\n";
echo "- Case Number: {$disciplinaryCase->getCaseNumber()}\n";
echo "- Severity: {$disciplinaryCase->getSeverity()}\n";
echo "- Status: {$disciplinaryCase->getStatus()}\n\n"; // investigation

// Assign investigator
$investigated = $disciplinaryManager->assignInvestigator(
    $disciplinaryCase->getId(),
    'emp_hr_001'
);

echo "Investigator Assigned: {$investigated->getInvestigatorId()}\n\n";

// Resolve case after investigation
$resolved = $disciplinaryManager->resolveCase($disciplinaryCase->getId(), [
    'action_taken' => 'Written warning + mandatory security training',
    'resolution_notes' => 'Employee acknowledged violation. No malicious intent found. Database access revoked.',
    'follow_up_required' => true,
    'follow_up_date' => '2025-08-15',
]);

echo "Case Resolved:\n";
echo "- Action Taken: {$resolved->getActionTaken()}\n";
echo "- Status: {$resolved->getStatus()}\n\n"; // resolved

// ============================================
// Scenario 4: Training Program Management
// ============================================

/** @var TrainingManager $trainingManager */
$trainingManager = app(TrainingManager::class);

// Create training program
$training = $trainingManager->createTrainingProgram([
    'training_code' => 'TRN-SEC-001',
    'title' => 'Advanced Security Best Practices',
    'description' => 'Comprehensive course on secure coding, OWASP Top 10, and compliance.',
    'provider' => 'SANS Institute',
    'duration_hours' => 40.0,
    'certification_awarded' => true,
    'certification_validity_months' => 24,
    'status' => TrainingStatus::scheduled,
]);

echo "Training Program Created:\n";
echo "- Code: {$training->getTrainingCode()}\n";
echo "- Title: {$training->getTitle()}\n";
echo "- Duration: {$training->getDurationHours()} hours\n";
echo "- Certification: " . ($training->getCertificationAwarded() ? 'Yes' : 'No') . "\n\n";

// Enroll employee (from disciplinary case)
$enrollment = $trainingManager->enrollEmployee('emp_002', $training->getId());

echo "Employee Enrolled:\n";
echo "- Employee ID: {$enrollment->getEmployeeId()}\n";
echo "- Training ID: {$enrollment->getTrainingId()}\n";
echo "- Enrollment Date: {$enrollment->getEnrollmentDate()->format('Y-m-d')}\n";
echo "- Status: {$enrollment->getStatus()}\n\n"; // enrolled

// Mark training as completed
$completed = $trainingManager->markTrainingCompleted($enrollment->getId(), [
    'completion_date' => '2025-07-01',
    'final_score' => 92.5,
    'certification_issued' => true,
]);

echo "Training Completed:\n";
echo "- Completion Date: {$completed->getCompletionDate()->format('Y-m-d')}\n";
echo "- Certification Expiry: {$completed->getCertificationExpiryDate()->format('Y-m-d')}\n";
echo "- Status: {$completed->getStatus()}\n\n"; // completed

// ============================================
// Scenario 5: Complex Leave Management
// ============================================

/** @var LeaveManager $leaveManager */
$leaveManager = app(LeaveManager::class);

// Attempt to create overlapping leave (should fail)
try {
    $leaveManager->createLeaveRequest([
        'employee_id' => 'emp_001',
        'leave_type_id' => 'lt_annual_001',
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-10',
        'reason' => 'Extended vacation',
    ]);

    $leaveManager->createLeaveRequest([
        'employee_id' => 'emp_001',
        'leave_type_id' => 'lt_annual_001',
        'start_date' => '2025-06-05', // Overlaps with above
        'end_date' => '2025-06-12',
        'reason' => 'Another trip',
    ]);
} catch (\Nexus\Hrm\Exceptions\LeaveOverlapException $e) {
    echo "❌ Leave Overlap Prevented:\n";
    echo "- Error: {$e->getMessage()}\n\n";
}

// Attempt to request leave exceeding balance (should fail)
try {
    $balance = $leaveManager->getLeaveBalance('emp_001', 'lt_annual_001');
    echo "Current Leave Balance: {$balance->getAvailable()} days\n";

    $leaveManager->createLeaveRequest([
        'employee_id' => 'emp_001',
        'leave_type_id' => 'lt_annual_001',
        'start_date' => '2025-07-01',
        'end_date' => '2025-07-30', // 30 days
        'reason' => 'Month-long sabbatical',
    ]);
} catch (\Nexus\Hrm\Exceptions\LeaveBalanceValidationException $e) {
    echo "❌ Insufficient Leave Balance:\n";
    echo "- Error: {$e->getMessage()}\n\n";
}

// Create emergency leave (retroactive)
$emergencyLeave = $leaveManager->createLeaveRequest([
    'employee_id' => 'emp_001',
    'leave_type_id' => 'lt_emergency_001',
    'start_date' => '2025-05-10', // Past date
    'end_date' => '2025-05-10',
    'days_requested' => 1.0,
    'reason' => 'Family emergency - hospital admission',
    'is_retroactive' => true,
]);

echo "Emergency Leave Approved (Retroactive):\n";
echo "- Date: {$emergencyLeave->getStartDate()->format('Y-m-d')}\n";
echo "- Status: {$emergencyLeave->getStatus()}\n\n";

// ============================================
// Scenario 6: Integration with Backoffice
// ============================================

/** @var OrganizationServiceContract $orgService */
$orgService = app(OrganizationServiceContract::class);

// Get employee's manager from organizational structure
$employeeManagerId = $orgService->getEmployeeManager($employeeId);

echo "Organizational Structure Integration:\n";
echo "- Employee ID: {$employeeId}\n";
echo "- Manager ID: {$employeeManagerId}\n";

// Get employee's department
$departmentId = $orgService->getEmployeeDepartment($employeeId);
echo "- Department ID: {$departmentId}\n";

// Get direct reports
$directReports = $orgService->getDirectReports($managerId);
echo "- Direct Reports: " . count($directReports) . "\n\n";

// ============================================
// Scenario 7: Employee Termination with Settlement
// ============================================

$terminationDate = new \DateTimeImmutable('2025-12-31');

// Calculate final settlement (unused leave encashment)
$settlement = $employeeManager->calculateFinalSettlement($employeeId, $terminationDate);

echo "Final Settlement Calculation:\n";
echo "- Unused Annual Leave: {$settlement['unused_annual_leave']} days\n";
echo "- Leave Encashment: MYR {$settlement['encashment_amount']}\n";
echo "- Notice Period: {$settlement['notice_period_days']} days\n";
echo "- Last Working Day: {$settlement['last_working_day']}\n\n";

// Terminate employee
$terminated = $employeeManager->terminateEmployee(
    $employeeId,
    $terminationDate,
    'Voluntary resignation - career advancement'
);

echo "Employee Terminated:\n";
echo "- Termination Date: {$terminated->getTerminationDate()->format('Y-m-d')}\n";
echo "- Status: {$terminated->getStatus()}\n";
echo "- Settlement Amount: MYR {$settlement['encashment_amount']}\n\n";

// ============================================
// Expected Output Summary
// ============================================

echo "==============================================\n";
echo "ADVANCED EXAMPLE EXECUTION COMPLETE\n";
echo "==============================================\n";
echo "✅ Annual performance review completed\n";
echo "✅ 360-degree feedback collected\n";
echo "✅ Disciplinary case investigated and resolved\n";
echo "✅ Training program enrolled and completed\n";
echo "✅ Leave overlap and balance validation enforced\n";
echo "✅ Organizational structure integrated\n";
echo "✅ Employee terminated with settlement calculation\n";
echo "==============================================\n";

/*
 * Advanced Patterns Demonstrated:
 * 
 * 1. Performance Review Workflow
 *    - Self-assessment → Manager review → Finalization
 *    - 360-degree feedback with peer reviews
 * 
 * 2. Disciplinary Case Lifecycle
 *    - Investigation → Resolution → Follow-up
 *    - Progressive discipline tracking
 * 
 * 3. Training Management
 *    - Program creation → Enrollment → Completion
 *    - Certification tracking with expiry
 * 
 * 4. Complex Leave Scenarios
 *    - Overlap prevention
 *    - Balance validation
 *    - Retroactive emergency leave
 * 
 * 5. Backoffice Integration
 *    - Manager hierarchy from org structure
 *    - Department assignment
 *    - Direct reports retrieval
 * 
 * 6. Employee Termination
 *    - Final settlement calculation
 *    - Leave encashment
 *    - Notice period compliance
 * 
 * Best Practices:
 * - Always validate business rules (balance, overlaps)
 * - Use try-catch for exception handling
 * - Integrate with Workflow for approvals
 * - Audit all lifecycle changes
 * - Leverage Backoffice for organizational data
 */
