<?php

declare(strict_types=1);

/**
 * Basic Usage Example for Nexus Leave Package
 * 
 * This example demonstrates basic leave management operations:
 * - Checking leave balance
 * - Applying for leave
 * - Calculating accruals
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Leave\Contracts\LeaveRepositoryInterface;
use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\Leave\Contracts\LeaveCalculatorInterface;
use Nexus\Leave\Services\LeaveBalanceCalculator;
use Nexus\Leave\Enums\LeaveStatus;
use Nexus\Leave\Enums\LeaveCategory;
use Nexus\Leave\Enums\LeaveDuration;
use Nexus\Leave\Exceptions\InsufficientBalanceException;

// ===================================================================
// STEP 1: Create Mock Repositories for Testing
// ===================================================================

// Mock Leave Repository
$leaveRepository = new class implements LeaveRepositoryInterface {
    private array $leaves = [];
    
    public function findById(string $id): ?object
    {
        return $this->leaves[$id] ?? null;
    }
    
    public function findByEmployeeId(string $employeeId): array
    {
        return array_filter($this->leaves, fn($leave) => $leave->employee_id === $employeeId);
    }
    
    public function save(object $leave): string
    {
        $leave->id = $leave->id ?? 'leave-' . uniqid();
        $this->leaves[$leave->id] = $leave;
        return $leave->id;
    }
    
    public function delete(string $id): void
    {
        unset($this->leaves[$id]);
    }
};

// Mock Leave Balance Repository
$balanceRepository = new class implements LeaveBalanceRepositoryInterface {
    private array $balances = [
        'emp-001:annual-leave' => (object)[
            'id' => 'bal-001',
            'employee_id' => 'emp-001',
            'leave_type_id' => 'annual-leave',
            'balance' => 14.0,
            'accrued_ytd' => 14.0,
            'used_ytd' => 0.0,
            'year' => 2025,
        ],
        'emp-001:sick-leave' => (object)[
            'id' => 'bal-002',
            'employee_id' => 'emp-001',
            'leave_type_id' => 'sick-leave',
            'balance' => 14.0,
            'accrued_ytd' => 14.0,
            'used_ytd' => 0.0,
            'year' => 2025,
        ],
    ];
    
    public function findByEmployeeAndType(string $employeeId, string $leaveTypeId): ?object
    {
        $key = "{$employeeId}:{$leaveTypeId}";
        return $this->balances[$key] ?? null;
    }
    
    public function save(object $balance): string
    {
        $key = "{$balance->employee_id}:{$balance->leave_type_id}";
        $this->balances[$key] = $balance;
        return $balance->id;
    }
    
    public function updateBalance(string $balanceId, float $amount): void
    {
        foreach ($this->balances as &$balance) {
            if ($balance->id === $balanceId) {
                $balance->balance = $amount;
                break;
            }
        }
    }
};

// ===================================================================
// STEP 2: Initialize Leave Calculator Service
// ===================================================================

$calculator = new LeaveBalanceCalculator($balanceRepository);

echo "=== Nexus Leave - Basic Usage Examples ===\n\n";

// ===================================================================
// EXAMPLE 1: Check Leave Balance
// ===================================================================

echo "Example 1: Checking Leave Balance\n";
echo str_repeat("-", 50) . "\n";

$employeeId = 'emp-001';
$annualLeaveTypeId = 'annual-leave';
$sickLeaveTypeId = 'sick-leave';

$annualBalance = $calculator->calculateBalance($employeeId, $annualLeaveTypeId);
$sickBalance = $calculator->calculateBalance($employeeId, $sickLeaveTypeId);

echo "Employee ID: {$employeeId}\n";
echo "Annual Leave Balance: {$annualBalance} days\n";
echo "Sick Leave Balance: {$sickBalance} days\n\n";

// ===================================================================
// EXAMPLE 2: Apply for Leave (Successful)
// ===================================================================

echo "Example 2: Applying for Leave (Successful)\n";
echo str_repeat("-", 50) . "\n";

$daysRequested = 5.0;

echo "Requesting {$daysRequested} days of annual leave...\n";

// Check if employee has sufficient balance
if ($daysRequested > $annualBalance) {
    echo "❌ ERROR: Insufficient balance!\n";
    echo "   Requested: {$daysRequested} days\n";
    echo "   Available: {$annualBalance} days\n\n";
} else {
    echo "✓ Balance check passed\n";
    
    // Create leave request object
    $leave = (object)[
        'employee_id' => $employeeId,
        'leave_type_id' => $annualLeaveTypeId,
        'start_date' => new DateTimeImmutable('2025-02-10'),
        'end_date' => new DateTimeImmutable('2025-02-14'),
        'days' => $daysRequested,
        'duration_type' => LeaveDuration::FULL_DAY->value,
        'status' => LeaveStatus::PENDING->value,
        'reason' => 'Family vacation',
        'created_at' => new DateTimeImmutable(),
    ];
    
    // Save leave request
    $leaveId = $leaveRepository->save($leave);
    
    echo "✓ Leave application submitted successfully\n";
    echo "   Leave ID: {$leaveId}\n";
    echo "   Status: " . LeaveStatus::from($leave->status)->name . "\n";
    echo "   Period: {$leave->start_date->format('Y-m-d')} to {$leave->end_date->format('Y-m-d')}\n";
    echo "   Duration: {$leave->days} days\n\n";
    
    // Update balance (would be done after approval in real system)
    $newBalance = $annualBalance - $daysRequested;
    $balanceObj = $balanceRepository->findByEmployeeAndType($employeeId, $annualLeaveTypeId);
    $balanceRepository->updateBalance($balanceObj->id, $newBalance);
    
    echo "✓ Balance updated\n";
    echo "   New balance: {$newBalance} days\n\n";
}

// ===================================================================
// EXAMPLE 3: Apply for Leave (Insufficient Balance)
// ===================================================================

echo "Example 3: Applying for Leave (Insufficient Balance)\n";
echo str_repeat("-", 50) . "\n";

$daysRequested = 15.0; // More than available
$currentBalance = $calculator->calculateBalance($employeeId, $annualLeaveTypeId);

echo "Requesting {$daysRequested} days of annual leave...\n";
echo "Current balance: {$currentBalance} days\n";

try {
    if ($daysRequested > $currentBalance) {
        throw new InsufficientBalanceException(
            $employeeId,
            'Annual Leave',
            $daysRequested,
            $currentBalance
        );
    }
} catch (InsufficientBalanceException $e) {
    echo "❌ ERROR: {$e->getMessage()}\n\n";
}

// ===================================================================
// EXAMPLE 4: Calculate Year-to-Date Accrual
// ===================================================================

echo "Example 4: Calculating Year-to-Date Accrual\n";
echo str_repeat("-", 50) . "\n";

$asOfDate = new DateTimeImmutable('2025-06-30');

echo "Calculating accrual as of: {$asOfDate->format('Y-m-d')}\n";

// In a real implementation, this would use the AccrualEngine
// For this example, we'll just display the stored accrued_ytd value
$balanceObj = $balanceRepository->findByEmployeeAndType($employeeId, $annualLeaveTypeId);

echo "Employee ID: {$employeeId}\n";
echo "Leave Type: Annual Leave\n";
echo "Accrued YTD: {$balanceObj->accrued_ytd} days\n";
echo "Used YTD: {$balanceObj->used_ytd} days\n";
echo "Current Balance: {$balanceObj->balance} days\n\n";

// ===================================================================
// EXAMPLE 5: Half-Day Leave Application
// ===================================================================

echo "Example 5: Applying for Half-Day Leave\n";
echo str_repeat("-", 50) . "\n";

$halfDayLeave = (object)[
    'employee_id' => $employeeId,
    'leave_type_id' => $annualLeaveTypeId,
    'start_date' => new DateTimeImmutable('2025-03-15'),
    'end_date' => new DateTimeImmutable('2025-03-15'),
    'days' => 0.5,
    'duration_type' => LeaveDuration::HALF_DAY_AM->value,
    'status' => LeaveStatus::PENDING->value,
    'reason' => 'Medical appointment',
    'created_at' => new DateTimeImmutable(),
];

$halfDayLeaveId = $leaveRepository->save($halfDayLeave);

echo "✓ Half-day leave application submitted\n";
echo "   Leave ID: {$halfDayLeaveId}\n";
echo "   Date: {$halfDayLeave->start_date->format('Y-m-d')}\n";
echo "   Duration: {$halfDayLeave->days} days (Morning)\n";
echo "   Status: " . LeaveStatus::from($halfDayLeave->status)->name . "\n\n";

// ===================================================================
// EXAMPLE 6: View Employee's Leave History
// ===================================================================

echo "Example 6: Viewing Employee Leave History\n";
echo str_repeat("-", 50) . "\n";

$employeeLeaves = $leaveRepository->findByEmployeeId($employeeId);

echo "Total leave requests: " . count($employeeLeaves) . "\n\n";

foreach ($employeeLeaves as $leave) {
    $status = LeaveStatus::from($leave->status);
    $duration = LeaveDuration::from($leave->duration_type);
    
    echo "Leave ID: {$leave->id}\n";
    echo "  Period: {$leave->start_date->format('Y-m-d')} to {$leave->end_date->format('Y-m-d')}\n";
    echo "  Days: {$leave->days}\n";
    echo "  Type: {$duration->name}\n";
    echo "  Status: {$status->name}\n";
    echo "  Reason: {$leave->reason}\n";
    echo "\n";
}

// ===================================================================
// EXAMPLE 7: Leave Status Transitions
// ===================================================================

echo "Example 7: Leave Status Lifecycle\n";
echo str_repeat("-", 50) . "\n";

$leave = $leaveRepository->findById($leaveId);

echo "Current status: " . LeaveStatus::from($leave->status)->name . "\n";

// Simulate approval
$leave->status = LeaveStatus::APPROVED->value;
$leave->approved_at = new DateTimeImmutable();
$leaveRepository->save($leave);

echo "Updated status: " . LeaveStatus::from($leave->status)->name . "\n";
echo "Approved at: {$leave->approved_at->format('Y-m-d H:i:s')}\n\n";

// ===================================================================
// SUMMARY
// ===================================================================

echo "=== Summary ===\n";
echo str_repeat("-", 50) . "\n";

$finalBalance = $calculator->calculateBalance($employeeId, $annualLeaveTypeId);

echo "Employee: {$employeeId}\n";
echo "Total leave requests submitted: " . count($employeeLeaves) . "\n";
echo "Current annual leave balance: {$finalBalance} days\n";
echo "\nFor more examples, see advanced-usage.php\n";
