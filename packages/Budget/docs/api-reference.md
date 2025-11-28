# API Reference: Budget

## Interfaces

### BudgetManagerInterface

**Location:** `src/Contracts/BudgetManagerInterface.php`

**Purpose:** Main service orchestrator for all budget operations

**Methods:**

#### createBudget()

```php
public function createBudget(
    string $tenantId,
    string $periodId,
    string $departmentId,
    string $accountId,
    BudgetType $budgetType,
    float $allocatedAmount,
    string $baseCurrency,
    string $presentationCurrency,
    float $exchangeRate,
    ?string $parentBudgetId = null
): BudgetInterface;
```

**Description:** Creates a new budget with dual-currency tracking

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$periodId` (string) - Fiscal period identifier
- `$departmentId` (string) - Department identifier
- `$accountId` (string) - GL account identifier
- `$budgetType` (BudgetType) - Type of budget
- `$allocatedAmount` (float) - Total budget allocation
- `$baseCurrency` (string) - Base currency code
- `$presentationCurrency` (string) - Presentation currency code
- `$exchangeRate` (float) - Exchange rate snapshot
- `$parentBudgetId` (string|null) - Optional parent budget for hierarchy

**Returns:** `BudgetInterface` - Created budget entity

**Throws:**
- `InvalidArgumentException` - When validation fails

#### commitAmount()

```php
public function commitAmount(
    string $budgetId,
    float $amount,
    string $sourceDocumentId,
    string $sourceDocumentType,
    string $description
): void;
```

**Description:** Commits (encumbers) budget amount when PO approved

**Parameters:**
- `$budgetId` (string) - Budget identifier
- `$amount` (float) - Amount to commit
- `$sourceDocumentId` (string) - Source document ID (e.g., PO number)
- `$sourceDocumentType` (string) - Document type
- `$description` (string) - Transaction description

**Throws:**
- `BudgetExceededException` - When commitment exceeds available budget
- `InvalidBudgetStatusException` - When budget not in Active status

#### recordActual()

```php
public function recordActual(
    string $budgetId,
    float $amount,
    string $sourceDocumentId,
    string $sourceDocumentType,
    string $description
): void;
```

**Description:** Records actual expenditure from GL posting

#### calculateVariance()

```php
public function calculateVariance(string $budgetId): BudgetVariance;
```

**Description:** Calculates budget variance (allocated vs actual)

**Returns:** `BudgetVariance` value object with absolute and percentage variance

#### checkAvailability()

```php
public function checkAvailability(string $budgetId, float $amount): BudgetAvailabilityResult;
```

**Description:** Checks if budget has sufficient available amount

**Returns:** `BudgetAvailabilityResult` with availability status and recommendations

---

### BudgetRepositoryInterface

**Location:** `src/Contracts/BudgetRepositoryInterface.php`

**Purpose:** Budget persistence and retrieval

**Methods:**

#### findById()
#### save()
#### findByDepartment()
#### findByPeriod()
#### findDescendants()

Returns all descendant budgets in hierarchy using recursive CTE

---

### BudgetSimulatorInterface

**Location:** `src/Contracts/BudgetSimulatorInterface.php`

**Purpose:** Budget simulation and what-if analysis

**Methods:**

#### createScenario()

Creates a simulation scenario for what-if analysis

#### compareScenarios()

Compares multiple simulation scenarios

---

## Value Objects

### BudgetVariance

**Location:** `src/ValueObjects/BudgetVariance.php`

**Purpose:** Represents budget variance analysis

**Properties:**
- `absoluteVariance` (float) - Absolute variance amount
- `percentageVariance` (float) - Percentage variance
- `severity` (AlertSeverity) - Variance severity classification
- `isUnderBudget` (bool) - Whether under or over budget

**Methods:**

#### constructor

```php
public function __construct(
    public readonly float $absoluteVariance,
    public readonly float $percentageVariance,
    public readonly AlertSeverity $severity,
    public readonly bool $isUnderBudget
)
```

---

### BudgetAvailabilityResult

**Purpose:** Budget availability check result

**Properties:**
- `isAvailable` (bool) - Whether budget is available
- `availableAmount` (float) - Available budget amount
- `requestedAmount` (float) - Requested amount
- `recommendedAction` (string) - Recommended action

---

## Enums

### BudgetType

**Location:** `src/Enums/BudgetType.php`

**Purpose:** Types of budgets

**Cases:**
- `OperatingExpense` - Operating expenses
- `CapitalExpenditure` - Capital expenditures
- `Revenue` - Revenue budgets
- `Project` - Project budgets
- `Departmental` - Department budgets
- `Contingency` - Contingency reserves
- `Discretionary` - Discretionary spending

**Methods:**

#### isExpense()

```php
public function isExpense(): bool
```

Returns true if budget type is an expense

#### isRevenue()

```php
public function isRevenue(): bool
```

Returns true if budget type is revenue

---

### BudgetStatus

**Location:** `src/Enums/BudgetStatus.php`

**Cases:**
- `Draft` - Initial creation
- `PendingApproval` - Awaiting approval
- `Approved` - Approved
- `Active` - Currently active
- `Locked` - Locked for modifications
- `Closed` - Closed
- `Cancelled` - Cancelled
- `Simulated` - Simulation mode

**Methods:**

#### isEditable()
#### canCommit()
#### canLock()

---

## Exceptions

### BudgetExceededException

**Location:** `src/Exceptions/BudgetExceededException.php`

**Extends:** `RuntimeException`

**Purpose:** Thrown when budget is exceeded

**Factory Methods:**

#### forAmount()

```php
public static function forAmount(string $budgetId, float $amount, float $available): self
```

**Returns:** Exception with message "Budget {$budgetId} exceeded: requested {$amount}, available {$available}"

---

## Events

### BudgetCreatedEvent

**Location:** `src/Events/BudgetCreatedEvent.php`

**Purpose:** Emitted when budget is created

**Properties:**
- `budgetId` (string)
- `tenantId` (string)
- `periodId` (string)
- `allocatedAmount` (float)

### BudgetExceededEvent

**Purpose:** Emitted when budget is exceeded (triggers workflow)

### BudgetActualRecordedEvent

**Purpose:** Emitted when actual expenditure is recorded

---

## Usage Patterns

### Pattern 1: Check-Commit-Verify

Always check availability before committing:

```php
$availability = $budgetManager->checkAvailability($budgetId, $amount);

if ($availability->isAvailable) {
    $budgetManager->commitAmount($budgetId, $amount, $sourceId, $sourceType, $desc);
}
```

### Pattern 2: Variance Monitoring

Monitor variance and trigger investigations:

```php
$variance = $budgetManager->calculateVariance($budgetId);

if ($variance->getSeverity() === AlertSeverity::High) {
    // Trigger investigation workflow
}
```
