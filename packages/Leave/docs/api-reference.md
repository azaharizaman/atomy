# API Reference: Leave

**Package:** `Nexus\Leave`  
**Version:** 1.0.0-alpha

---

## Interfaces

### LeaveRepositoryInterface

**Location:** `src/Contracts/LeaveRepositoryInterface.php`

**Purpose:** Defines contract for leave request data persistence operations.

**Methods:**

#### findById()

```php
public function findById(string $id): ?object;
```

**Description:** Retrieves a leave request by its unique identifier.

**Parameters:**
- `$id` (string) - The leave request ULID

**Returns:** `?object` - The leave entity or null if not found

**Example:**
```php
$leave = $leaveRepository->findById('01HXK5V...');
if ($leave !== null) {
    echo "Leave status: " . $leave->status;
}
```

---

#### findByEmployeeId()

```php
public function findByEmployeeId(string $employeeId): array;
```

**Description:** Retrieves all leave requests for a specific employee.

**Parameters:**
- `$employeeId` (string) - The employee ULID

**Returns:** `array` - Array of leave entities

**Example:**
```php
$leaves = $leaveRepository->findByEmployeeId('emp-001');
foreach ($leaves as $leave) {
    echo "Leave from {$leave->start_date} to {$leave->end_date}\n";
}
```

---

#### save()

```php
public function save(object $leave): string;
```

**Description:** Persists a leave request entity.

**Parameters:**
- `$leave` (object) - The leave entity to save

**Returns:** `string` - The saved leave ID

**Example:**
```php
$leaveId = $leaveRepository->save($leave);
```

---

#### delete()

```php
public function delete(string $id): void;
```

**Description:** Deletes a leave request by ID.

**Parameters:**
- `$id` (string) - The leave request ULID

**Returns:** `void`

**Throws:**
- `LeaveNotFoundException` - When leave record doesn't exist

**Example:**
```php
$leaveRepository->delete('01HXK5V...');
```

---

### LeaveBalanceRepositoryInterface

**Location:** `src/Contracts/LeaveBalanceRepositoryInterface.php`

**Purpose:** Defines contract for leave balance data operations.

**Methods:**

#### findByEmployeeAndType()

```php
public function findByEmployeeAndType(string $employeeId, string $leaveTypeId): ?object;
```

**Description:** Retrieves the leave balance for an employee and leave type combination.

**Parameters:**
- `$employeeId` (string) - The employee ULID
- `$leaveTypeId` (string) - The leave type ULID

**Returns:** `?object` - The balance entity or null if not found

**Example:**
```php
$balance = $balanceRepository->findByEmployeeAndType('emp-001', 'annual-leave');
echo "Current balance: " . $balance->balance;
```

---

#### save()

```php
public function save(object $balance): string;
```

**Description:** Persists a leave balance entity.

**Parameters:**
- `$balance` (object) - The balance entity to save

**Returns:** `string` - The saved balance ID

---

#### updateBalance()

```php
public function updateBalance(string $balanceId, float $amount): void;
```

**Description:** Updates the balance amount for a specific balance record.

**Parameters:**
- `$balanceId` (string) - The balance record ULID
- `$amount` (float) - The new balance amount

**Returns:** `void`

**Example:**
```php
$balanceRepository->updateBalance('bal-001', 10.5);
```

---

### LeaveTypeRepositoryInterface

**Location:** `src/Contracts/LeaveTypeRepositoryInterface.php`

**Purpose:** Defines contract for leave type configuration management.

**Methods:**

#### findById()

```php
public function findById(string $id): ?object;
```

**Description:** Retrieves a leave type by ID.

**Parameters:**
- `$id` (string) - The leave type ULID

**Returns:** `?object` - The leave type entity or null

---

#### findAll()

```php
public function findAll(): array;
```

**Description:** Retrieves all configured leave types.

**Returns:** `array` - Array of leave type entities

**Example:**
```php
$leaveTypes = $leaveTypeRepository->findAll();
foreach ($leaveTypes as $type) {
    echo "{$type->name}: {$type->annual_entitlement} days/year\n";
}
```

---

#### save()

```php
public function save(object $leaveType): string;
```

**Description:** Persists a leave type configuration.

**Parameters:**
- `$leaveType` (object) - The leave type entity

**Returns:** `string` - The saved leave type ID

---

### LeaveCalculatorInterface

**Location:** `src/Contracts/LeaveCalculatorInterface.php`

**Purpose:** Defines contract for leave balance and accrual calculations.

**Methods:**

#### calculateBalance()

```php
public function calculateBalance(string $employeeId, string $leaveTypeId): float;
```

**Description:** Calculates the current available leave balance for an employee.

**Parameters:**
- `$employeeId` (string) - The employee ULID
- `$leaveTypeId` (string) - The leave type ULID

**Returns:** `float` - The available balance in days

**Example:**
```php
$balance = $calculator->calculateBalance('emp-001', 'annual-leave');
echo "Available: {$balance} days";
```

---

#### calculateAccrual()

```php
public function calculateAccrual(
    string $employeeId,
    string $leaveTypeId,
    \DateTimeImmutable $asOfDate
): float;
```

**Description:** Calculates the accrued leave amount up to a specific date.

**Parameters:**
- `$employeeId` (string) - The employee ULID
- `$leaveTypeId` (string) - The leave type ULID
- `$asOfDate` (\DateTimeImmutable) - The date to calculate accrual up to

**Returns:** `float` - The accrued amount in days

**Example:**
```php
$accrued = $calculator->calculateAccrual(
    'emp-001',
    'annual-leave',
    new \DateTimeImmutable('2025-06-30')
);
echo "Accrued as of June 30: {$accrued} days";
```

---

### AccrualStrategyInterface

**Location:** `src/Contracts/AccrualStrategyInterface.php`

**Purpose:** Defines contract for pluggable accrual calculation strategies.

**Methods:**

#### calculate()

```php
public function calculate(
    string $employeeId,
    string $leaveTypeId,
    \DateTimeImmutable $asOfDate
): float;
```

**Description:** Calculates the accrued amount based on the strategy's algorithm.

**Parameters:**
- `$employeeId` (string) - The employee ULID
- `$leaveTypeId` (string) - The leave type ULID
- `$asOfDate` (\DateTimeImmutable) - The date to calculate up to

**Returns:** `float` - The calculated accrual amount

---

#### getName()

```php
public function getName(): string;
```

**Description:** Returns the unique name identifier for this strategy.

**Returns:** `string` - The strategy name (e.g., 'monthly', 'fixed_allocation')

**Example:**
```php
echo "Using strategy: " . $strategy->getName();
```

---

### AccrualStrategyResolverInterface

**Location:** `src/Contracts/AccrualStrategyResolverInterface.php`

**Purpose:** Resolves the appropriate accrual strategy by name.

**Methods:**

#### resolve()

```php
public function resolve(string $strategyName): AccrualStrategyInterface;
```

**Description:** Returns the accrual strategy implementation for the given name.

**Parameters:**
- `$strategyName` (string) - The strategy name to resolve

**Returns:** `AccrualStrategyInterface` - The strategy implementation

**Throws:**
- `\InvalidArgumentException` - When strategy name is unknown

**Example:**
```php
$strategy = $resolver->resolve('monthly');
$accrual = $strategy->calculate($employeeId, $leaveTypeId, $date);
```

---

### LeavePolicyInterface

**Location:** `src/Contracts/LeavePolicyInterface.php`

**Purpose:** Defines contract for leave policy validation and enforcement.

**Methods:**

#### canApplyLeave()

```php
public function canApplyLeave(
    string $employeeId,
    string $leaveTypeId,
    \DateTimeImmutable $startDate,
    \DateTimeImmutable $endDate
): bool;
```

**Description:** Determines if an employee can apply for leave during the specified period.

**Parameters:**
- `$employeeId` (string) - The employee ULID
- `$leaveTypeId` (string) - The leave type ULID
- `$startDate` (\DateTimeImmutable) - Leave start date
- `$endDate` (\DateTimeImmutable) - Leave end date

**Returns:** `bool` - True if leave can be applied

**Example:**
```php
if ($policy->canApplyLeave($empId, $typeId, $start, $end)) {
    // Proceed with leave application
}
```

---

#### validateLeaveRequest()

```php
public function validateLeaveRequest(array $leaveData): array;
```

**Description:** Validates a leave request and returns validation errors.

**Parameters:**
- `$leaveData` (array) - The leave request data to validate

**Returns:** `array` - Array of validation errors (empty if valid)

**Example:**
```php
$errors = $policy->validateLeaveRequest([
    'employee_id' => 'emp-001',
    'leave_type_id' => 'annual',
    'start_date' => '2025-01-15',
    'end_date' => '2025-01-20',
    'days' => 5,
]);

if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "Error: {$error}\n";
    }
}
```

---

### LeaveAccrualEngineInterface

**Location:** `src/Contracts/LeaveAccrualEngineInterface.php`

**Purpose:** Defines contract for processing leave accruals over a period.

**Methods:**

#### processAccrual()

```php
public function processAccrual(
    string $employeeId,
    string $leaveTypeId,
    \DateTimeImmutable $periodStart,
    \DateTimeImmutable $periodEnd
): float;
```

**Description:** Processes accrual for an employee over a specified period.

**Parameters:**
- `$employeeId` (string) - The employee ULID
- `$leaveTypeId` (string) - The leave type ULID
- `$periodStart` (\DateTimeImmutable) - Period start date
- `$periodEnd` (\DateTimeImmutable) - Period end date

**Returns:** `float` - The amount accrued during the period

**Example:**
```php
$accrued = $engine->processAccrual(
    'emp-001',
    'annual-leave',
    new \DateTimeImmutable('2025-01-01'),
    new \DateTimeImmutable('2025-01-31')
);
echo "January accrual: {$accrued} days";
```

---

### CountryLawRepositoryInterface

**Location:** `src/Contracts/CountryLawRepositoryInterface.php`

**Purpose:** Defines contract for retrieving country-specific statutory leave rules.

**Methods:**

#### findByCountryCode()

```php
public function findByCountryCode(string $countryCode): ?object;
```

**Description:** Retrieves country law configuration by ISO country code.

**Parameters:**
- `$countryCode` (string) - ISO 3166-1 alpha-2 country code (e.g., 'MY', 'US')

**Returns:** `?object` - The country law entity or null

**Example:**
```php
$malaysiaLaws = $countryLawRepository->findByCountryCode('MY');
```

---

#### getLeaveRules()

```php
public function getLeaveRules(string $countryCode, string $leaveType): array;
```

**Description:** Retrieves specific leave rules for a country and leave type.

**Parameters:**
- `$countryCode` (string) - ISO 3166-1 alpha-2 country code
- `$leaveType` (string) - The type of leave (e.g., 'annual', 'maternity')

**Returns:** `array` - Array of leave rules

**Example:**
```php
$maternityRules = $countryLawRepository->getLeaveRules('MY', 'maternity');
// Returns: ['min_days' => 98, 'paid_days' => 98, 'eligibility' => '...]
```

---

## Services

### LeaveBalanceCalculator

**Location:** `src/Services/LeaveBalanceCalculator.php`

**Purpose:** Calculates leave balances and accruals for employees.

**Constructor Dependencies:**
- `LeaveBalanceRepositoryInterface` - For accessing balance data

**Public Methods:**

#### calculateBalance()

Implementation of `LeaveCalculatorInterface::calculateBalance()`.

**Example:**
```php
$calculator = new LeaveBalanceCalculator($balanceRepository);
$balance = $calculator->calculateBalance('emp-001', 'annual-leave');
```

---

### MonthlyAccrualStrategy

**Location:** `src/Services/AccrualStrategies/MonthlyAccrualStrategy.php`

**Purpose:** Implements monthly accrual calculation (e.g., 14 days/year = 1.17 days/month).

**Public Methods:**

#### calculate()

Calculates accrual based on completed months of service.

#### getName()

Returns `'monthly'`.

**Example:**
```php
$strategy = new MonthlyAccrualStrategy();
$accrual = $strategy->calculate('emp-001', 'annual-leave', new \DateTimeImmutable());
```

---

### FixedAllocationStrategy

**Location:** `src/Services/AccrualStrategies/FixedAllocationStrategy.php`

**Purpose:** Implements fixed allocation (full entitlement granted at once).

**Public Methods:**

#### calculate()

Returns full allocation amount if allocation date has passed.

#### getName()

Returns `'fixed_allocation'`.

**Example:**
```php
$strategy = new FixedAllocationStrategy();
$accrual = $strategy->calculate('emp-001', 'maternity', new \DateTimeImmutable());
```

---

## Enums

### LeaveStatus

**Location:** `src/Enums/LeaveStatus.php`

**Purpose:** Represents the lifecycle status of a leave request.

**Cases:**
- `DRAFT = 'draft'` - Initial creation, not yet submitted
- `PENDING = 'pending'` - Submitted, awaiting approval
- `APPROVED = 'approved'` - Approved by manager
- `REJECTED = 'rejected'` - Rejected by manager
- `CANCELLED = 'cancelled'` - Cancelled by employee

**Example:**
```php
use Nexus\Leave\Enums\LeaveStatus;

$leave->status = LeaveStatus::PENDING;

if ($leave->status === LeaveStatus::APPROVED) {
    // Deduct from balance
}
```

---

### ApprovalStatus

**Location:** `src/Enums/ApprovalStatus.php`

**Purpose:** Represents the approval workflow status.

**Cases:**
- `PENDING = 'pending'` - Awaiting decision
- `APPROVED = 'approved'` - Approved
- `REJECTED = 'rejected'` - Rejected

---

### LeaveCategory

**Location:** `src/Enums/LeaveCategory.php`

**Purpose:** Categorizes different types of leave.

**Cases:**
- `ANNUAL = 'annual'` - Annual/vacation leave
- `SICK = 'sick'` - Medical/sick leave
- `MATERNITY = 'maternity'` - Maternity leave
- `PATERNITY = 'paternity'` - Paternity leave
- `UNPAID = 'unpaid'` - Leave without pay
- `COMPASSIONATE = 'compassionate'` - Bereavement leave
- `STUDY = 'study'` - Educational leave
- `OTHER = 'other'` - Custom categories

**Example:**
```php
use Nexus\Leave\Enums\LeaveCategory;

$leaveType->category = LeaveCategory::ANNUAL;
```

---

### LeaveDuration

**Location:** `src/Enums/LeaveDuration.php`

**Purpose:** Represents the duration type of a leave request.

**Cases:**
- `FULL_DAY = 'full_day'` - Complete day (8 hours)
- `HALF_DAY_AM = 'half_day_am'` - Morning half (4 hours)
- `HALF_DAY_PM = 'half_day_pm'` - Afternoon half (4 hours)
- `HOURS = 'hours'` - Hourly leave

**Example:**
```php
use Nexus\Leave\Enums\LeaveDuration;

$leave->duration_type = LeaveDuration::HALF_DAY_AM;
```

---

### AccrualFrequency

**Location:** `src/Enums/AccrualFrequency.php`

**Purpose:** Defines how often leave is accrued.

**Cases:**
- `MONTHLY = 'monthly'` - Accrued each month
- `QUARTERLY = 'quarterly'` - Accrued each quarter
- `YEARLY = 'yearly'` - Accrued at year start
- `FIXED_ALLOCATION = 'fixed_allocation'` - Fixed amount (not accrued)

**Example:**
```php
use Nexus\Leave\Enums\AccrualFrequency;

$leaveType->accrual_frequency = AccrualFrequency::MONTHLY;
```

---

## Exceptions

### LeaveException

**Location:** `src/Exceptions/LeaveException.php`

**Extends:** `\Exception`

**Purpose:** Base exception for all leave-related errors.

**Example:**
```php
use Nexus\Leave\Exceptions\LeaveException;

try {
    // Leave operation
} catch (LeaveException $e) {
    // Handle any leave-related error
}
```

---

### InsufficientBalanceException

**Location:** `src/Exceptions/InsufficientBalanceException.php`

**Extends:** `LeaveException`

**Purpose:** Thrown when an employee attempts to request more leave than available.

**Constructor:**

```php
public function __construct(
    string $employeeId,
    string $leaveType,
    float $requested,
    float $available
)
```

**Message Format:** `"Insufficient balance for employee {$employeeId}, leave type {$leaveType}. Requested: {$requested}, Available: {$available}"`

**Example:**
```php
use Nexus\Leave\Exceptions\InsufficientBalanceException;

if ($requested > $available) {
    throw new InsufficientBalanceException(
        $employeeId,
        'annual',
        $requested,
        $available
    );
}
```

---

### LeaveNotFoundException

**Location:** `src/Exceptions/LeaveNotFoundException.php`

**Extends:** `LeaveException`

**Purpose:** Thrown when a leave record cannot be found.

**Constructor:**

```php
public function __construct(string $leaveId)
```

**Message Format:** `"Leave {$leaveId} not found"`

**Example:**
```php
use Nexus\Leave\Exceptions\LeaveNotFoundException;

$leave = $repository->findById($id) 
    ?? throw new LeaveNotFoundException($id);
```

---

## Usage Patterns

### Pattern 1: Leave Application Flow

```php
use Nexus\Leave\Contracts\LeaveCalculatorInterface;
use Nexus\Leave\Contracts\LeaveRepositoryInterface;
use Nexus\Leave\Contracts\LeavePolicyInterface;
use Nexus\Leave\Enums\LeaveStatus;
use Nexus\Leave\Exceptions\InsufficientBalanceException;

final readonly class LeaveApplicationService
{
    public function __construct(
        private LeaveCalculatorInterface $calculator,
        private LeaveRepositoryInterface $repository,
        private LeavePolicyInterface $policy
    ) {}

    public function apply(
        string $employeeId,
        string $leaveTypeId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        float $days
    ): string {
        // 1. Check policy
        if (!$this->policy->canApplyLeave($employeeId, $leaveTypeId, $startDate, $endDate)) {
            throw new \RuntimeException('Leave application not allowed');
        }

        // 2. Check balance
        $balance = $this->calculator->calculateBalance($employeeId, $leaveTypeId);
        if ($days > $balance) {
            throw new InsufficientBalanceException($employeeId, $leaveTypeId, $days, $balance);
        }

        // 3. Create and save leave request
        $leave = /* create leave entity */;
        return $this->repository->save($leave);
    }
}
```

### Pattern 2: Accrual Processing

```php
use Nexus\Leave\Contracts\LeaveAccrualEngineInterface;
use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;

final readonly class MonthlyAccrualProcessor
{
    public function __construct(
        private LeaveAccrualEngineInterface $engine,
        private LeaveBalanceRepositoryInterface $balanceRepository
    ) {}

    public function processMonthEnd(string $employeeId, string $leaveTypeId): void
    {
        $periodStart = new \DateTimeImmutable('first day of this month');
        $periodEnd = new \DateTimeImmutable('last day of this month');

        $accrued = $this->engine->processAccrual(
            $employeeId,
            $leaveTypeId,
            $periodStart,
            $periodEnd
        );

        $balance = $this->balanceRepository->findByEmployeeAndType($employeeId, $leaveTypeId);
        if ($balance !== null) {
            $newBalance = $balance->balance + $accrued;
            $this->balanceRepository->updateBalance($balance->id, $newBalance);
        }
    }
}
```

### Pattern 3: Strategy Resolution

```php
use Nexus\Leave\Contracts\AccrualStrategyResolverInterface;
use Nexus\Leave\Services\AccrualStrategies\MonthlyAccrualStrategy;
use Nexus\Leave\Services\AccrualStrategies\FixedAllocationStrategy;

final readonly class AccrualStrategyResolver implements AccrualStrategyResolverInterface
{
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            'monthly' => new MonthlyAccrualStrategy(),
            'fixed_allocation' => new FixedAllocationStrategy(),
        ];
    }

    public function resolve(string $strategyName): AccrualStrategyInterface
    {
        return $this->strategies[$strategyName]
            ?? throw new \InvalidArgumentException("Unknown strategy: {$strategyName}");
    }
}
```
