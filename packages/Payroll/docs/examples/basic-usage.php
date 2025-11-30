<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Payroll
 *
 * This example demonstrates:
 * 1. Setting up payroll components (earnings and deductions)
 * 2. Assigning components to employees
 * 3. Processing payroll for a period
 * 4. Retrieving and displaying payslip data
 *
 * Prerequisites:
 * - Repository interfaces bound in your DI container
 * - Statutory calculator installed (e.g., nexus/payroll-mys-statutory)
 * - Employee data available in your system
 */

use Nexus\Payroll\Services\PayrollEngine;
use Nexus\Payroll\Services\ComponentManager;
use Nexus\Payroll\Services\PayslipManager;
use Nexus\Payroll\ValueObjects\ComponentType;
use Nexus\Payroll\ValueObjects\CalculationMethod;
use Nexus\Payroll\ValueObjects\PayslipStatus;

// ============================================
// Step 1: Set Up Payroll Components
// ============================================

/**
 * Create the core payroll components for your organization.
 * This is typically done once during initial setup.
 */

$componentManager = app(ComponentManager::class);

// Basic Salary - Fixed amount per employee
$basicSalary = $componentManager->createComponent([
    'tenant_id' => 'tenant-001',
    'code' => 'BASIC',
    'name' => 'Basic Salary',
    'type' => ComponentType::EARNING,
    'calculation_method' => CalculationMethod::FIXED,
    'is_taxable' => true,
    'is_statutory' => true, // Contributes to EPF/SOCSO calculations
    'is_active' => true,
]);

echo "Created component: {$basicSalary->getCode()} - {$basicSalary->getName()}\n";

// Housing Allowance - 15% of basic salary
$housingAllowance = $componentManager->createComponent([
    'tenant_id' => 'tenant-001',
    'code' => 'HOUSING',
    'name' => 'Housing Allowance',
    'type' => ComponentType::EARNING,
    'calculation_method' => CalculationMethod::PERCENTAGE_OF_BASIC,
    'percentage' => 15.0,
    'is_taxable' => true,
    'is_statutory' => false, // Does not contribute to statutory calculations
    'is_active' => true,
]);

echo "Created component: {$housingAllowance->getCode()} - {$housingAllowance->getName()}\n";

// Transport Allowance - Fixed amount
$transportAllowance = $componentManager->createComponent([
    'tenant_id' => 'tenant-001',
    'code' => 'TRANSPORT',
    'name' => 'Transport Allowance',
    'type' => ComponentType::EARNING,
    'calculation_method' => CalculationMethod::FIXED,
    'is_taxable' => false, // Tax-exempt up to a limit
    'is_statutory' => false,
    'is_active' => true,
]);

echo "Created component: {$transportAllowance->getCode()} - {$transportAllowance->getName()}\n";

// Loan Deduction - Employee loan repayment
$loanDeduction = $componentManager->createComponent([
    'tenant_id' => 'tenant-001',
    'code' => 'LOAN',
    'name' => 'Loan Repayment',
    'type' => ComponentType::DEDUCTION,
    'calculation_method' => CalculationMethod::FIXED,
    'is_taxable' => false,
    'is_statutory' => false,
    'is_active' => true,
]);

echo "Created component: {$loanDeduction->getCode()} - {$loanDeduction->getName()}\n";

// ============================================
// Step 2: Assign Components to Employees
// ============================================

/**
 * Link components to specific employees with their amounts.
 * This would typically be done via an EmployeeComponentManager.
 *
 * Note: This is a simplified example. In real implementation,
 * use the EmployeeComponentPersistInterface.
 */

$employeeComponentData = [
    [
        'tenant_id' => 'tenant-001',
        'employee_id' => 'emp-001',
        'component_id' => $basicSalary->getId(),
        'amount' => 5000.00, // RM 5,000 basic salary
        'effective_from' => '2025-01-01',
        'effective_to' => null, // No end date
    ],
    [
        'tenant_id' => 'tenant-001',
        'employee_id' => 'emp-001',
        'component_id' => $transportAllowance->getId(),
        'amount' => 200.00, // RM 200 transport allowance
        'effective_from' => '2025-01-01',
        'effective_to' => null,
    ],
];

// In real implementation:
// $employeeComponentPersist->create($employeeComponentData[0]);
// $employeeComponentPersist->create($employeeComponentData[1]);

echo "\nAssigned components to employee emp-001\n";

// ============================================
// Step 3: Process Payroll for a Period
// ============================================

/**
 * Process payroll for all employees in a period.
 * The PayrollEngine will:
 * 1. Fetch all active employee components
 * 2. Calculate earnings based on component types
 * 3. Apply statutory calculator for deductions
 * 4. Generate payslips
 */

$payrollEngine = app(PayrollEngine::class);

$payslips = $payrollEngine->processPeriod(
    tenantId: 'tenant-001',
    periodStart: '2025-01-01',
    periodEnd: '2025-01-31',
    filters: [] // Optional: ['department_id' => 'dept-001']
);

echo "\nProcessed payroll: " . count($payslips) . " payslips generated\n";

// ============================================
// Step 4: Review Generated Payslips
// ============================================

foreach ($payslips as $payslip) {
    echo "\n========================================\n";
    echo "Payslip: {$payslip->getPayslipNumber()}\n";
    echo "Employee: {$payslip->getEmployeeId()}\n";
    echo "Period: {$payslip->getPeriodStart()->format('Y-m-d')} to {$payslip->getPeriodEnd()->format('Y-m-d')}\n";
    echo "Status: {$payslip->getStatus()->value}\n";
    echo "----------------------------------------\n";

    // Earnings breakdown
    echo "EARNINGS:\n";
    foreach ($payslip->getEarningsBreakdown() as $code => $amount) {
        echo sprintf("  %-20s: RM %10.2f\n", $code, $amount);
    }
    echo sprintf("  %-20s: RM %10.2f\n", 'GROSS PAY', $payslip->getGrossPay());

    // Deductions breakdown
    echo "\nDEDUCTIONS:\n";
    foreach ($payslip->getDeductionsBreakdown() as $code => $amount) {
        echo sprintf("  %-20s: RM %10.2f\n", $code, $amount);
    }
    echo sprintf("  %-20s: RM %10.2f\n", 'TOTAL DEDUCTIONS', $payslip->getTotalDeductions());

    // Net pay
    echo "\n----------------------------------------\n";
    echo sprintf("NET PAY:              RM %10.2f\n", $payslip->getNetPay());
    echo sprintf("EMPLOYER COST:        RM %10.2f\n", $payslip->getEmployerContributions());
    echo "========================================\n";
}

// ============================================
// Step 5: Update Payslip Status (Approval Workflow)
// ============================================

$payslipManager = app(PayslipManager::class);

// Move through workflow: DRAFT -> CALCULATED -> APPROVED -> PAID
foreach ($payslips as $payslip) {
    // Approve the payslip
    $payslip = $payslipManager->updatePayslipStatus(
        $payslip->getId(),
        PayslipStatus::APPROVED
    );
    echo "\nPayslip {$payslip->getPayslipNumber()} approved\n";

    // Mark as paid (after bank transfer)
    $payslip = $payslipManager->updatePayslipStatus(
        $payslip->getId(),
        PayslipStatus::PAID
    );
    echo "Payslip {$payslip->getPayslipNumber()} marked as paid\n";
}

// ============================================
// Expected Output
// ============================================
/*
Created component: BASIC - Basic Salary
Created component: HOUSING - Housing Allowance
Created component: TRANSPORT - Transport Allowance
Created component: LOAN - Loan Repayment

Assigned components to employee emp-001

Processed payroll: 1 payslips generated

========================================
Payslip: PS-2025-00001
Employee: emp-001
Period: 2025-01-01 to 2025-01-31
Status: calculated
----------------------------------------
EARNINGS:
  BASIC               : RM    5000.00
  HOUSING             : RM     750.00
  TRANSPORT           : RM     200.00
  GROSS PAY           : RM    5950.00

DEDUCTIONS:
  EPF_EMPLOYEE        : RM     550.00
  SOCSO_EMPLOYEE      : RM       8.65
  EIS_EMPLOYEE        : RM       9.90
  PCB                 : RM     125.00
  TOTAL DEDUCTIONS    : RM     693.55

----------------------------------------
NET PAY:              RM    5256.45
EMPLOYER COST:        RM     674.85
========================================

Payslip PS-2025-00001 approved
Payslip PS-2025-00001 marked as paid
*/
