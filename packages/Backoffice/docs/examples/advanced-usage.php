<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Backoffice
 * 
 * This example demonstrates:
 * 1. Department hierarchies with nested set queries
 * 2. Matrix organizations with cross-functional units
 * 3. Staff transfers with approval workflows
 * 4. Organizational chart generation
 * 5. Advanced reporting (span of control, turnover)
 */

use Nexus\Backoffice\Contracts\{BackofficeManagerInterface, TransferManagerInterface};
use Nexus\Backoffice\ValueObjects\{
    DepartmentType,
    UnitType,
    UnitStatus,
    TransferType,
    TransferStatus,
    StaffStatus
};

// ============================================
// SCENARIO: Complex Enterprise Organization
// ============================================

/**
 * This example simulates a large enterprise with:
 * - Multi-level department hierarchy
 * - Cross-functional project teams
 * - Staff promotions and transfers
 * - Organizational analytics
 */

// Assume services are injected
/** @var BackofficeManagerInterface $backoffice */
/** @var TransferManagerInterface $transferManager */

$tenantId = 'tenant-enterprise-001';
$companyId = 'company-123';

// ============================================
// Feature 1: Hierarchical Department Structure
// ============================================

echo "=== Feature 1: Hierarchical Department Structure ===\n";
echo "Creating multi-level department hierarchy...\n\n";

// Level 1: Engineering Department (parent)
$engineeringDeptId = $backoffice->createDepartment(
    tenantId: $tenantId,
    companyId: $companyId,
    code: 'ENG',
    name: 'Engineering',
    type: DepartmentType::Functional,
    metadata: ['cost_center' => 'CC-100']
);

echo "✓ Created Engineering Department (Level 1)\n";

// Level 2: Frontend Engineering (child of Engineering)
$frontendDeptId = $backoffice->createDepartment(
    tenantId: $tenantId,
    companyId: $companyId,
    code: 'ENG-FE',
    name: 'Frontend Engineering',
    type: DepartmentType::Functional,
    parentId: $engineeringDeptId, // ← Hierarchical relationship
    metadata: ['cost_center' => 'CC-110']
);

echo "✓ Created Frontend Engineering (Level 2)\n";

// Level 2: Backend Engineering (child of Engineering)
$backendDeptId = $backoffice->createDepartment(
    tenantId: $tenantId,
    companyId: $companyId,
    code: 'ENG-BE',
    name: 'Backend Engineering',
    type: DepartmentType::Functional,
    parentId: $engineeringDeptId,
    metadata: ['cost_center' => 'CC-120']
);

echo "✓ Created Backend Engineering (Level 2)\n";

// Level 3: Platform Team (child of Backend Engineering)
$platformDeptId = $backoffice->createDepartment(
    tenantId: $tenantId,
    companyId: $companyId,
    code: 'ENG-BE-PLT',
    name: 'Platform Team',
    type: DepartmentType::ProjectBased,
    parentId: $backendDeptId,
    metadata: ['cost_center' => 'CC-121']
);

echo "✓ Created Platform Team (Level 3)\n\n";

// Query all descendants of Engineering (nested set query)
$allEngDepts = $backoffice->getDepartmentDescendants($engineeringDeptId);

echo "Engineering Department Tree:\n";
echo "  Engineering (Level 1)\n";
echo "    ├─ Frontend Engineering (Level 2)\n";
echo "    └─ Backend Engineering (Level 2)\n";
echo "        └─ Platform Team (Level 3)\n";
echo "\n";
echo "Total descendants: " . count($allEngDepts) . "\n\n";

// ============================================
// Feature 2: Matrix Organizations (Units)
// ============================================

echo "=== Feature 2: Matrix Organizations (Cross-Functional Units) ===\n";
echo "Creating cross-functional project team...\n\n";

// Create a project team that spans departments
$projectUnitId = $backoffice->createUnit(
    tenantId: $tenantId,
    companyId: $companyId,
    code: 'PROJ-MOBILE',
    name: 'Mobile App Project Team',
    type: UnitType::ProjectTeam,
    purpose: 'Develop next-generation mobile app',
    startDate: new \DateTimeImmutable('2024-01-01'),
    endDate: new \DateTimeImmutable('2024-12-31'), // Temporary unit
    metadata: [
        'project_manager' => 'user-456',
        'budget' => 500000,
    ]
);

echo "✓ Project team created: {$projectUnitId}\n";
echo "  Name: Mobile App Project Team\n";
echo "  Type: Project Team (temporary)\n";
echo "  Duration: Jan 2024 - Dec 2024\n\n";

// Add members from different departments
$frontendStaffId = 'staff-001'; // Frontend engineer
$backendStaffId = 'staff-002';  // Backend engineer
$designStaffId = 'staff-003';   // Designer (from Design dept)

$backoffice->addUnitMember(
    unitId: $projectUnitId,
    staffId: $frontendStaffId,
    role: 'Frontend Lead',
    startDate: new \DateTimeImmutable('2024-01-01')
);

$backoffice->addUnitMember(
    unitId: $projectUnitId,
    staffId: $backendStaffId,
    role: 'Backend Lead',
    startDate: new \DateTimeImmutable('2024-01-01')
);

$backoffice->addUnitMember(
    unitId: $projectUnitId,
    staffId: $designStaffId,
    role: 'UX Designer',
    startDate: new \DateTimeImmutable('2024-01-01')
);

echo "✓ Added 3 members from different departments\n";
echo "  Matrix structure: Staff belong to both functional dept AND project team\n\n";

// ============================================
// Feature 3: Staff Transfer with Approval Workflow
// ============================================

echo "=== Feature 3: Staff Transfer with Approval Workflow ===\n";
echo "Initiating promotion transfer...\n\n";

$staffId = 'staff-004';
$currentDeptId = $frontendDeptId;
$targetDeptId = $engineeringDeptId; // Promotion to Engineering manager

// Initiate transfer
$transferId = $transferManager->initiateTransfer(
    tenantId: $tenantId,
    staffId: $staffId,
    transferType: TransferType::Promotion,
    sourceDepartmentId: $currentDeptId,
    targetDepartmentId: $targetDeptId,
    effectiveDate: new \DateTimeImmutable('2024-06-01'), // Future effective date
    reason: 'Promotion to Engineering Manager',
    metadata: [
        'old_job_title' => 'Senior Frontend Engineer',
        'new_job_title' => 'Engineering Manager',
        'salary_increase_percentage' => 15,
    ]
);

echo "✓ Transfer initiated: {$transferId}\n";
echo "  Type: Promotion\n";
echo "  From: Frontend Engineering → Engineering\n";
echo "  Effective Date: 2024-06-01 (scheduled)\n";
echo "  Status: Pending Approval\n\n";

// Add approvers to workflow
$transferManager->addApprover(
    transferId: $transferId,
    approverId: 'manager-001', // Current department head
    level: 1,
    role: 'Department Head Approval'
);

$transferManager->addApprover(
    transferId: $transferId,
    approverId: 'hr-director-001', // HR Director
    level: 2,
    role: 'HR Director Approval'
);

echo "✓ Approval workflow configured (2 levels)\n";
echo "  Level 1: Department Head\n";
echo "  Level 2: HR Director\n\n";

// Approve at level 1
$transferManager->approve(
    transferId: $transferId,
    approverId: 'manager-001',
    comments: 'Well-deserved promotion. Excellent performance.'
);

echo "✓ Level 1 approved by Department Head\n";

// Approve at level 2
$transferManager->approve(
    transferId: $transferId,
    approverId: 'hr-director-001',
    comments: 'Approved. Compensation package aligned with policy.'
);

echo "✓ Level 2 approved by HR Director\n";
echo "✓ Transfer fully approved - will execute on 2024-06-01\n\n";

// ============================================
// Feature 4: Organizational Chart Generation
// ============================================

echo "=== Feature 4: Organizational Chart Generation ===\n";

// Generate hierarchical organization chart
$orgChart = $backoffice->generateOrganizationalChart(
    companyId: $companyId,
    chartType: 'hierarchical', // Options: hierarchical, matrix, circle_pack
    departmentId: $engineeringDeptId, // Focus on Engineering department
    maxDepth: 3 // Limit depth to 3 levels
);

echo "✓ Organizational chart generated\n";
echo "  Type: Hierarchical Tree\n";
echo "  Root: Engineering Department\n";
echo "  Max Depth: 3 levels\n";
echo "  Nodes: " . count($orgChart['nodes']) . "\n";
echo "  Edges: " . count($orgChart['edges']) . "\n\n";

echo "Chart Preview:\n";
echo json_encode($orgChart, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// ============================================
// Feature 5: Advanced Reporting
// ============================================

echo "=== Feature 5: Advanced Reporting & Analytics ===\n\n";

// Headcount Report
$headcountReport = $backoffice->getHeadcountReport(
    companyId: $companyId,
    groupBy: 'department' // Options: company, office, department, type, status
);

echo "Headcount Report (by Department):\n";
foreach ($headcountReport as $dept => $count) {
    echo "  {$dept}: {$count} staff\n";
}
echo "\n";

// Span of Control Report (manager-to-subordinate ratio)
$spanReport = $backoffice->getSpanOfControlReport($companyId);

echo "Span of Control Report:\n";
foreach ($spanReport as $manager => $span) {
    $status = $span['subordinates'] > 12 ? '⚠ High' : '✓ Normal';
    echo "  {$manager}: {$span['subordinates']} subordinates {$status}\n";
}
echo "\n";

// Turnover Report (last 12 months)
$turnoverReport = $backoffice->getTurnoverReport(
    companyId: $companyId,
    startDate: new \DateTimeImmutable('-12 months'),
    endDate: new \DateTimeImmutable()
);

echo "Turnover Report (Last 12 Months):\n";
echo "  Hires: {$turnoverReport['hires']}\n";
echo "  Terminations: {$turnoverReport['terminations']}\n";
echo "  Net Change: {$turnoverReport['net_change']}\n";
echo "  Turnover Rate: {$turnoverReport['turnover_rate']}%\n";
echo "\n";

// Vacancy Report
$vacancyReport = $backoffice->getVacancyReport($companyId);

echo "Vacancy Report:\n";
foreach ($vacancyReport as $dept => $vacancies) {
    echo "  {$dept}: {$vacancies} open positions\n";
}
echo "\n";

// ============================================
// Feature 6: Transfer History & Rollback
// ============================================

echo "=== Feature 6: Transfer History & Rollback ===\n";

// Get transfer history for a staff member
$transferHistory = $transferManager->getStaffTransferHistory($staffId);

echo "Transfer History for Staff {$staffId}:\n";
foreach ($transferHistory as $transfer) {
    echo "  {$transfer->getEffectiveDate()->format('Y-m-d')}: ";
    echo "{$transfer->getTransferType()->value} - ";
    echo "{$transfer->getStatus()->name}\n";
}
echo "\n";

// Rollback a transfer (if needed)
if ($transferHistory[0]->getStatus() === TransferStatus::Completed) {
    echo "Rolling back last transfer (if needed):\n";
    
    try {
        $transferManager->rollbackTransfer($transferHistory[0]->getId());
        echo "✓ Transfer rolled back successfully\n";
        echo "  Staff returned to previous department\n";
    } catch (\Exception $e) {
        echo "✗ Rollback failed: {$e->getMessage()}\n";
    }
}

echo "\n";

// ============================================
// Summary
// ============================================

echo "=== Advanced Features Demonstrated ===\n";
echo "1. ✓ Hierarchical Departments (3-level deep nested set)\n";
echo "2. ✓ Matrix Organizations (cross-functional project team)\n";
echo "3. ✓ Staff Transfers (promotion with 2-level approval)\n";
echo "4. ✓ Organizational Chart Generation (hierarchical tree)\n";
echo "5. ✓ Advanced Reporting (headcount, span, turnover, vacancy)\n";
echo "6. ✓ Transfer History & Rollback\n";
echo "\n";
echo "This backoffice system provides enterprise-grade organizational\n";
echo "management with sophisticated hierarchies and matrix structures.\n";
echo "\n";

/**
 * Key Takeaways:
 * 
 * 1. **Nested Set Hierarchies:** Use getDepartmentDescendants() for
 *    efficient hierarchical queries without recursion
 * 
 * 2. **Matrix Organizations:** Units allow staff to work across
 *    departments while maintaining primary department assignment
 * 
 * 3. **Transfer Workflows:** Support multi-level approvals with
 *    future effective dates for planned organizational changes
 * 
 * 4. **Organizational Charts:** Generate visual representations
 *    of hierarchies for reporting and planning
 * 
 * 5. **Analytics:** Comprehensive reporting for HR metrics
 *    (span of control, turnover, vacancies)
 * 
 * 6. **Audit Trail:** Complete transfer history with rollback
 *    capability for compliance and governance
 */
