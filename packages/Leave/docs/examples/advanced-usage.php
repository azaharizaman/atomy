<?php

declare(strict_types=1);

/**
 * Advanced Usage Example for Nexus Leave Package
 * 
 * This example demonstrates advanced scenarios:
 * - Custom accrual strategies
 * - Leave policy validation
 * - Year-end carry-forward
 * - Proration calculations
 * - Country-specific statutory rules
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Leave\Contracts\AccrualStrategyInterface;
use Nexus\Leave\Contracts\AccrualStrategyResolverInterface;
use Nexus\Leave\Contracts\LeavePolicyInterface;
use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\Leave\Services\AccrualStrategies\MonthlyAccrualStrategy;
use Nexus\Leave\Services\AccrualStrategies\FixedAllocationStrategy;
use Nexus\Leave\Enums\AccrualFrequency;
use Nexus\Leave\Enums\LeaveCategory;

echo "=== Nexus Leave - Advanced Usage Examples ===\n\n";

// ===================================================================
// EXAMPLE 1: Custom Accrual Strategy Implementation
// ===================================================================

echo "Example 1: Implementing Custom Accrual Strategy\n";
echo str_repeat("-", 50) . "\n";

/**
 * Custom quarterly accrual strategy
 * Example: 14 days/year = 3.5 days per quarter
 */
class QuarterlyAccrualStrategy implements AccrualStrategyInterface
{
    public function calculate(
        string $employeeId,
        string $leaveTypeId,
        DateTimeImmutable $asOfDate
    ): float {
        // Mock employee join date
        $joinDate = new DateTimeImmutable('2025-01-15');
        
        // Calculate completed quarters since join date
        $interval = $joinDate->diff($asOfDate);
        $monthsWorked = ($interval->y * 12) + $interval->m;
        $quartersCompleted = floor($monthsWorked / 3);
        
        // Annual entitlement: 14 days
        $quarterlyAccrual = 14.0 / 4; // 3.5 days per quarter
        
        $accrued = $quartersCompleted * $quarterlyAccrual;
        
        echo "  Employee join date: {$joinDate->format('Y-m-d')}\n";
        echo "  Calculation date: {$asOfDate->format('Y-m-d')}\n";
        echo "  Months worked: {$monthsWorked}\n";
        echo "  Quarters completed: {$quartersCompleted}\n";
        echo "  Accrued: {$accrued} days ({$quarterlyAccrual} days/quarter)\n\n";
        
        return $accrued;
    }
    
    public function getName(): string
    {
        return 'quarterly';
    }
}

$quarterlyStrategy = new QuarterlyAccrualStrategy();
$accrued = $quarterlyStrategy->calculate(
    'emp-001',
    'annual-leave',
    new DateTimeImmutable('2025-06-30')
);

echo "Total accrued as of Jun 30, 2025: {$accrued} days\n\n";

// ===================================================================
// EXAMPLE 2: Accrual Strategy Resolver
// ===================================================================

echo "Example 2: Accrual Strategy Resolver\n";
echo str_repeat("-", 50) . "\n";

class SimpleStrategyResolver implements AccrualStrategyResolverInterface
{
    private array $strategies = [];
    
    public function __construct()
    {
        $this->strategies = [
            'monthly' => new MonthlyAccrualStrategy(),
            'quarterly' => new QuarterlyAccrualStrategy(),
            'fixed_allocation' => new FixedAllocationStrategy(),
        ];
    }
    
    public function resolve(string $strategyName): AccrualStrategyInterface
    {
        if (!isset($this->strategies[$strategyName])) {
            throw new InvalidArgumentException("Unknown strategy: {$strategyName}");
        }
        
        return $this->strategies[$strategyName];
    }
}

$resolver = new SimpleStrategyResolver();

// Test different strategies
$strategies = ['monthly', 'quarterly', 'fixed_allocation'];

foreach ($strategies as $strategyName) {
    $strategy = $resolver->resolve($strategyName);
    echo "Strategy: {$strategy->getName()}\n";
    
    $accrual = $strategy->calculate(
        'emp-001',
        'annual-leave',
        new DateTimeImmutable('2025-12-31')
    );
    
    echo "Year-end accrual: {$accrual} days\n\n";
}

// ===================================================================
// EXAMPLE 3: Leave Policy Implementation
// ===================================================================

echo "Example 3: Custom Leave Policy Validation\n";
echo str_repeat("-", 50) . "\n";

class CustomLeavePolicy implements LeavePolicyInterface
{
    private const MIN_NOTICE_DAYS = 7;
    private const MAX_CONSECUTIVE_DAYS = 14;
    
    public function canApplyLeave(
        string $employeeId,
        string $leaveTypeId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): bool {
        $errors = $this->validateLeaveRequest([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);
        
        return empty($errors);
    }
    
    public function validateLeaveRequest(array $leaveData): array
    {
        $errors = [];
        $today = new DateTimeImmutable();
        $startDate = new DateTimeImmutable($leaveData['start_date']);
        $endDate = new DateTimeImmutable($leaveData['end_date']);
        
        // Rule 1: Minimum notice period
        $daysNotice = $today->diff($startDate)->days;
        if ($daysNotice < self::MIN_NOTICE_DAYS) {
            $errors[] = "Minimum {$this->MIN_NOTICE_DAYS} days notice required (provided: {$daysNotice})";
        }
        
        // Rule 2: Maximum consecutive days
        $duration = $startDate->diff($endDate)->days + 1;
        if ($duration > self::MAX_CONSECUTIVE_DAYS) {
            $errors[] = "Maximum {$this->MAX_CONSECUTIVE_DAYS} consecutive days allowed (requested: {$duration})";
        }
        
        // Rule 3: No leave on weekends (simplified example)
        if ($startDate->format('N') >= 6) {
            $errors[] = "Leave cannot start on weekend";
        }
        
        return $errors;
    }
}

$policy = new CustomLeavePolicy();

// Test case 1: Valid leave request
echo "Test 1: Leave with sufficient notice\n";
$startDate = (new DateTimeImmutable())->modify('+14 days');
$endDate = $startDate->modify('+4 days');

$errors = $policy->validateLeaveRequest([
    'employee_id' => 'emp-001',
    'leave_type_id' => 'annual-leave',
    'start_date' => $startDate->format('Y-m-d'),
    'end_date' => $endDate->format('Y-m-d'),
]);

if (empty($errors)) {
    echo "  ✓ PASS: Leave request is valid\n\n";
} else {
    echo "  ❌ FAIL: " . implode(', ', $errors) . "\n\n";
}

// Test case 2: Insufficient notice
echo "Test 2: Leave with insufficient notice\n";
$startDate = (new DateTimeImmutable())->modify('+3 days');
$endDate = $startDate->modify('+2 days');

$errors = $policy->validateLeaveRequest([
    'employee_id' => 'emp-001',
    'leave_type_id' => 'annual-leave',
    'start_date' => $startDate->format('Y-m-d'),
    'end_date' => $endDate->format('Y-m-d'),
]);

if (!empty($errors)) {
    echo "  ❌ FAIL: " . implode(', ', $errors) . "\n\n";
}

// Test case 3: Too many consecutive days
echo "Test 3: Exceeding maximum consecutive days\n";
$startDate = (new DateTimeImmutable())->modify('+14 days');
$endDate = $startDate->modify('+20 days');

$errors = $policy->validateLeaveRequest([
    'employee_id' => 'emp-001',
    'leave_type_id' => 'annual-leave',
    'start_date' => $startDate->format('Y-m-d'),
    'end_date' => $endDate->format('Y-m-d'),
]);

if (!empty($errors)) {
    echo "  ❌ FAIL: " . implode(', ', $errors) . "\n\n";
}

// ===================================================================
// EXAMPLE 4: Proration for Mid-Year Joiners
// ===================================================================

echo "Example 4: Proration Calculation for Mid-Year Joiners\n";
echo str_repeat("-", 50) . "\n";

class ProrationCalculator
{
    public function calculateProratedEntitlement(
        float $annualEntitlement,
        DateTimeImmutable $joinDate,
        DateTimeImmutable $yearEnd
    ): float {
        // Calculate remaining months in the year
        $interval = $joinDate->diff($yearEnd);
        $remainingMonths = ($interval->m) + ($interval->d > 0 ? 1 : 0);
        
        // Prorate based on remaining months
        $monthlyRate = $annualEntitlement / 12;
        $proratedEntitlement = $monthlyRate * $remainingMonths;
        
        echo "  Annual entitlement: {$annualEntitlement} days\n";
        echo "  Join date: {$joinDate->format('Y-m-d')}\n";
        echo "  Year end: {$yearEnd->format('Y-m-d')}\n";
        echo "  Remaining months: {$remainingMonths}\n";
        echo "  Monthly rate: {$monthlyRate} days/month\n";
        echo "  Prorated entitlement: " . round($proratedEntitlement, 2) . " days\n\n";
        
        return round($proratedEntitlement, 2);
    }
}

$prorationCalc = new ProrationCalculator();

// Employee joins mid-year
$joinDate = new DateTimeImmutable('2025-06-15');
$yearEnd = new DateTimeImmutable('2025-12-31');
$annualEntitlement = 14.0;

$prorated = $prorationCalc->calculateProratedEntitlement(
    $annualEntitlement,
    $joinDate,
    $yearEnd
);

echo "Employee should receive {$prorated} days for the remainder of 2025\n\n";

// ===================================================================
// EXAMPLE 5: Year-End Carry-Forward Logic
// ===================================================================

echo "Example 5: Year-End Carry-Forward Calculation\n";
echo str_repeat("-", 50) . "\n";

class CarryForwardCalculator
{
    public function calculateCarryForward(
        float $currentBalance,
        float $maxCarryForward,
        bool $useItOrLoseIt = false
    ): array {
        if ($useItOrLoseIt) {
            return [
                'carried_forward' => 0.0,
                'forfeited' => $currentBalance,
            ];
        }
        
        $carriedForward = min($currentBalance, $maxCarryForward);
        $forfeited = max(0, $currentBalance - $maxCarryForward);
        
        return [
            'carried_forward' => $carriedForward,
            'forfeited' => $forfeited,
        ];
    }
}

$carryForwardCalc = new CarryForwardCalculator();

// Scenario 1: Within carry-forward limit
echo "Scenario 1: Balance within carry-forward limit\n";
$result = $carryForwardCalc->calculateCarryForward(
    currentBalance: 8.0,
    maxCarryForward: 10.0
);
echo "  Current balance: 8.0 days\n";
echo "  Max carry-forward: 10.0 days\n";
echo "  ✓ Carried forward: {$result['carried_forward']} days\n";
echo "  Forfeited: {$result['forfeited']} days\n\n";

// Scenario 2: Exceeds carry-forward limit
echo "Scenario 2: Balance exceeds carry-forward limit\n";
$result = $carryForwardCalc->calculateCarryForward(
    currentBalance: 15.0,
    maxCarryForward: 10.0
);
echo "  Current balance: 15.0 days\n";
echo "  Max carry-forward: 10.0 days\n";
echo "  ✓ Carried forward: {$result['carried_forward']} days\n";
echo "  ❌ Forfeited: {$result['forfeited']} days\n\n";

// Scenario 3: Use-it-or-lose-it policy
echo "Scenario 3: Use-it-or-lose-it policy\n";
$result = $carryForwardCalc->calculateCarryForward(
    currentBalance: 12.0,
    maxCarryForward: 10.0,
    useItOrLoseIt: true
);
echo "  Current balance: 12.0 days\n";
echo "  Policy: Use-it-or-lose-it\n";
echo "  ✓ Carried forward: {$result['carried_forward']} days\n";
echo "  ❌ Forfeited: {$result['forfeited']} days\n\n";

// ===================================================================
// EXAMPLE 6: Country-Specific Statutory Rules (Malaysia)
// ===================================================================

echo "Example 6: Country-Specific Statutory Rules (Malaysia)\n";
echo str_repeat("-", 50) . "\n";

class MalaysiaLeaveRules
{
    /**
     * Malaysian Employment Act 1955 statutory leave entitlements
     */
    public function getAnnualLeaveEntitlement(int $yearsOfService): int
    {
        if ($yearsOfService < 2) {
            return 8; // Less than 2 years: 8 days
        } elseif ($yearsOfService < 5) {
            return 12; // 2-5 years: 12 days
        } else {
            return 16; // 5+ years: 16 days
        }
    }
    
    public function getMaternityLeave(): array
    {
        return [
            'total_days' => 98, // 14 weeks (98 days)
            'paid_days' => 98,
            'eligibility' => 'Must have worked at least 90 days in 9 months before confinement',
            'consecutive_children_limit' => 5,
        ];
    }
    
    public function getPaternityLeave(): array
    {
        return [
            'total_days' => 7,
            'paid_days' => 7,
            'eligibility' => 'Married employee, child born to lawful wife',
        ];
    }
    
    public function getSickLeave(int $yearsOfService, bool $isHospitalized): int
    {
        if ($isHospitalized) {
            return 60; // Hospitalization leave: 60 days
        }
        
        if ($yearsOfService < 2) {
            return 14; // Less than 2 years: 14 days outpatient
        } elseif ($yearsOfService < 5) {
            return 18; // 2-5 years: 18 days outpatient
        } else {
            return 22; // 5+ years: 22 days outpatient
        }
    }
}

$malaysiaRules = new MalaysiaLeaveRules();

echo "Annual Leave Entitlement:\n";
for ($years = 1; $years <= 6; $years++) {
    $days = $malaysiaRules->getAnnualLeaveEntitlement($years);
    echo "  {$years} year(s) of service: {$days} days\n";
}
echo "\n";

echo "Maternity Leave:\n";
$maternity = $malaysiaRules->getMaternityLeave();
echo "  Total: {$maternity['total_days']} days ({$maternity['paid_days']} paid)\n";
echo "  Eligibility: {$maternity['eligibility']}\n\n";

echo "Paternity Leave:\n";
$paternity = $malaysiaRules->getPaternityLeave();
echo "  Total: {$paternity['total_days']} days ({$paternity['paid_days']} paid)\n";
echo "  Eligibility: {$paternity['eligibility']}\n\n";

echo "Sick Leave (3 years of service):\n";
echo "  Outpatient: {$malaysiaRules->getSickLeave(3, false)} days\n";
echo "  Hospitalized: {$malaysiaRules->getSickLeave(3, true)} days\n\n";

// ===================================================================
// EXAMPLE 7: Encashment Calculation
// ===================================================================

echo "Example 7: Leave Encashment Calculation\n";
echo str_repeat("-", 50) . "\n";

class EncashmentCalculator
{
    public function calculateEncashment(
        float $unusedDays,
        float $dailyRate,
        float $maxEncashableDays = null
    ): array {
        $encashableDays = $maxEncashableDays !== null 
            ? min($unusedDays, $maxEncashableDays)
            : $unusedDays;
        
        $amount = $encashableDays * $dailyRate;
        
        return [
            'unused_days' => $unusedDays,
            'encashable_days' => $encashableDays,
            'daily_rate' => $dailyRate,
            'total_amount' => round($amount, 2),
        ];
    }
}

$encashmentCalc = new EncashmentCalculator();

$result = $encashmentCalc->calculateEncashment(
    unusedDays: 12.0,
    dailyRate: 200.00,
    maxEncashableDays: 10.0
);

echo "Unused leave balance: {$result['unused_days']} days\n";
echo "Maximum encashable: {$result['encashable_days']} days\n";
echo "Daily rate: RM {$result['daily_rate']}\n";
echo "Total encashment: RM {$result['total_amount']}\n\n";

// ===================================================================
// SUMMARY
// ===================================================================

echo "=== Summary ===\n";
echo str_repeat("-", 50) . "\n";
echo "Advanced concepts demonstrated:\n";
echo "  ✓ Custom accrual strategies (quarterly, monthly, fixed)\n";
echo "  ✓ Strategy pattern implementation\n";
echo "  ✓ Leave policy validation\n";
echo "  ✓ Proration for mid-year joiners\n";
echo "  ✓ Year-end carry-forward logic\n";
echo "  ✓ Country-specific statutory rules (Malaysia)\n";
echo "  ✓ Leave encashment calculation\n";
echo "\nThese patterns can be adapted for your specific business requirements.\n";
