# API Reference: Period

Complete API documentation for all interfaces, services, value objects, enums, and exceptions.

---

## Interfaces (6 total)

### Core Interfaces

---

#### PeriodManagerInterface

**Location:** `src/Contracts/PeriodManagerInterface.php`

**Purpose:** Main service contract for period management operations. This is the primary API for period-related operations.

**Methods:**

##### isPostingAllowed()

```php
public function isPostingAllowed(DateTimeImmutable $date, PeriodType $type): bool
```

**Description:** Check if posting is allowed for a specific date and period type. This is a **critical performance path** that must execute in < 5ms.

**Parameters:**
- `$date` (DateTimeImmutable) - The transaction date to validate
- `$type` (PeriodType) - The period type (Accounting, Inventory, Payroll, Manufacturing)

**Returns:** `bool` - True if posting is allowed, false otherwise

**Throws:**
- `NoOpenPeriodException` - If no open period exists for the type

**Example:**
```php
$canPost = $periodManager->isPostingAllowed(
    new DateTimeImmutable('2024-01-15'),
    PeriodType::Accounting
);

if ($canPost) {
    // Proceed with transaction
}
```

---

##### getOpenPeriod()

```php
public function getOpenPeriod(PeriodType $type): ?PeriodInterface
```

**Description:** Get the currently open period for a specific type.

**Parameters:**
- `$type` (PeriodType) - The period type to query

**Returns:** `PeriodInterface|null` - The open period, or null if none exists

**Example:**
```php
$openPeriod = $periodManager->getOpenPeriod(PeriodType::Accounting);

if ($openPeriod !== null) {
    echo "Current period: " . $openPeriod->getName();
}
```

---

##### getCurrentPeriodForDate()

```php
public function getCurrentPeriodForDate(DateTimeImmutable $date, PeriodType $type): ?PeriodInterface
```

**Description:** Get the period that contains a specific date.

**Parameters:**
- `$date` (DateTimeImmutable) - The date to find the period for
- `$type` (PeriodType) - The period type

**Returns:** `PeriodInterface|null` - The period containing the date, or null

**Example:**
```php
$period = $periodManager->getCurrentPeriodForDate(
    new DateTimeImmutable('2024-03-15'),
    PeriodType::Inventory
);
```

---

##### closePeriod()

```php
public function closePeriod(string $periodId, string $reason, string $userId): void
```

**Description:** Close a period with audit reason. Transitions period from Open to Closed status.

**Parameters:**
- `$periodId` (string) - The ULID of the period to close
- `$reason` (string) - The reason for closing (audit trail)
- `$userId` (string) - The user performing the operation

**Throws:**
- `PeriodNotFoundException` - If period doesn't exist
- `InvalidPeriodStatusException` - If period can't transition to Closed

**Example:**
```php
$periodManager->closePeriod(
    periodId: '01HY7X8Z9ABC...',
    reason: 'Month-end close completed',
    userId: '01HY7XUSER...'
);
```

---

##### reopenPeriod()

```php
public function reopenPeriod(string $periodId, string $reason, string $userId): void
```

**Description:** Reopen a closed period. Requires authorization - only users with `period.reopen` permission can perform this action.

**Parameters:**
- `$periodId` (string) - The ULID of the period to reopen
- `$reason` (string) - The reason for reopening (audit trail)
- `$userId` (string) - The user performing the operation

**Throws:**
- `PeriodNotFoundException` - If period doesn't exist
- `PeriodReopeningUnauthorizedException` - If user lacks permission
- `InvalidPeriodStatusException` - If period can't be reopened (e.g., Locked)

**Example:**
```php
try {
    $periodManager->reopenPeriod(
        periodId: '01HY7X8Z9ABC...',
        reason: 'Late invoice adjustment required',
        userId: '01HY7XUSER...'
    );
} catch (PeriodReopeningUnauthorizedException $e) {
    throw new \RuntimeException('You are not authorized to reopen periods');
}
```

---

##### createNextPeriod()

```php
public function createNextPeriod(PeriodType $type): PeriodInterface
```

**Description:** Create the next sequential period for a type. Automatically calculates dates based on the last period.

**Parameters:**
- `$type` (PeriodType) - The period type

**Returns:** `PeriodInterface` - The newly created period

**Throws:**
- `OverlappingPeriodException` - If new period would overlap existing periods
- `RuntimeException` - If no existing periods to base calculation on

**Example:**
```php
$nextPeriod = $periodManager->createNextPeriod(PeriodType::Accounting);
echo "Created: " . $nextPeriod->getName();
```

---

##### listPeriods()

```php
public function listPeriods(PeriodType $type, ?string $fiscalYear = null): array
```

**Description:** List all periods for a specific type, optionally filtered by fiscal year.

**Parameters:**
- `$type` (PeriodType) - The period type to filter by
- `$fiscalYear` (string|null) - Optional fiscal year filter (e.g., "2024")

**Returns:** `array<PeriodInterface>` - Array of matching periods

**Example:**
```php
// All accounting periods
$periods = $periodManager->listPeriods(PeriodType::Accounting);

// Only 2024 accounting periods
$periods2024 = $periodManager->listPeriods(PeriodType::Accounting, '2024');
```

---

##### findById()

```php
public function findById(string $periodId): PeriodInterface
```

**Description:** Find a specific period by ID.

**Parameters:**
- `$periodId` (string) - The period ULID

**Returns:** `PeriodInterface` - The period entity

**Throws:**
- `PeriodNotFoundException` - If period doesn't exist

**Example:**
```php
$period = $periodManager->findById('01HY7X8Z9ABC...');
```

---

---

#### PeriodInterface

**Location:** `src/Contracts/PeriodInterface.php`

**Purpose:** Entity contract representing a fiscal period.

**Methods:**

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getId()` | `string` | Get the period ULID |
| `getType()` | `PeriodType` | Get the period type (Accounting, Inventory, etc.) |
| `getStatus()` | `PeriodStatus` | Get the current status (Pending, Open, Closed, Locked) |
| `getStartDate()` | `DateTimeImmutable` | Get the period start date |
| `getEndDate()` | `DateTimeImmutable` | Get the period end date |
| `getFiscalYear()` | `string` | Get the fiscal year (e.g., "2024") |
| `getName()` | `string` | Get the period name (e.g., "JAN-2024") |
| `getDescription()` | `?string` | Get optional description |
| `containsDate(DateTimeImmutable $date)` | `bool` | Check if date falls within period |
| `isPostingAllowed()` | `bool` | Check if transactions can be posted |
| `getCreatedAt()` | `DateTimeImmutable` | Get creation timestamp |
| `getUpdatedAt()` | `DateTimeImmutable` | Get last update timestamp |

---

#### PeriodRepositoryInterface

**Location:** `src/Contracts/PeriodRepositoryInterface.php`

**Purpose:** Persistence contract for period data. Your application must implement this.

**Methods:**

| Method | Return Type | Description |
|--------|-------------|-------------|
| `findById(string $id)` | `PeriodInterface` | Find period by ULID |
| `findOpenByType(PeriodType $type)` | `?PeriodInterface` | Find open period for type |
| `findByDate(DateTimeImmutable $date, PeriodType $type)` | `?PeriodInterface` | Find period containing date |
| `findByType(PeriodType $type)` | `array<PeriodInterface>` | Get all periods for type |
| `save(PeriodInterface $period)` | `void` | Persist period changes |

---

#### CacheRepositoryInterface

**Location:** `src/Contracts/CacheRepositoryInterface.php`

**Purpose:** Cache abstraction for performance optimization. Must be implemented with Redis/Memcached for < 5ms validation.

**Methods:**

| Method | Parameters | Return Type | Description |
|--------|------------|-------------|-------------|
| `get()` | `string $key` | `mixed` | Get cached value |
| `put()` | `string $key, mixed $value, int $ttl` | `void` | Store value with TTL |
| `forget()` | `string $key` | `void` | Remove cached value |

---

#### AuthorizationInterface

**Location:** `src/Contracts/AuthorizationInterface.php`

**Purpose:** Authorization contract for sensitive operations like period reopening.

**Methods:**

```php
public function canReopenPeriod(string $userId): bool
```

**Description:** Check if user is authorized to reopen closed periods.

**Example Implementation:**
```php
final readonly class PeriodAuthorization implements AuthorizationInterface
{
    public function canReopenPeriod(string $userId): bool
    {
        // Typically only CFO/Controller can reopen periods
        return User::find($userId)?->hasPermission('period.reopen') ?? false;
    }
}
```

---

#### AuditLoggerInterface

**Location:** `src/Contracts/AuditLoggerInterface.php`

**Purpose:** Audit logging contract for period operations.

**Methods:**

```php
public function log(string $entityId, string $action, string $description): void
```

**Description:** Log an auditable action on a period.

**Example Implementation:**
```php
final readonly class PeriodAuditLogger implements AuditLoggerInterface
{
    public function __construct(
        private AuditLogManagerInterface $auditLogger
    ) {}

    public function log(string $entityId, string $action, string $description): void
    {
        $this->auditLogger->log($entityId, $action, $description);
    }
}
```

---

## Services (1 total)

---

### PeriodManager

**Location:** `src/Services/PeriodManager.php`

**Purpose:** Main service implementation for period management with caching.

**Constructor Dependencies:**

| Dependency | Interface | Description |
|------------|-----------|-------------|
| `$repository` | `PeriodRepositoryInterface` | Period data access |
| `$cache` | `CacheRepositoryInterface` | Performance caching |
| `$authorization` | `AuthorizationInterface` | Permission checking |
| `$auditLogger` | `AuditLoggerInterface` | Audit trail logging |

**Cache Strategy:**
- Cache key prefix: `period:`
- Default TTL: 3600 seconds (1 hour)
- Open periods cached by type: `period:open:{type}`
- Cache invalidated on close/reopen operations

---

## Enums (2 total)

---

### PeriodType

**Location:** `src/Enums/PeriodType.php`

**Purpose:** Defines the independent period types supported by the system.

**Cases:**

| Case | Value | Label | Description |
|------|-------|-------|-------------|
| `Accounting` | `'accounting'` | Accounting Period | Financial accounting (GL, AR, AP) |
| `Inventory` | `'inventory'` | Inventory Period | Stock management |
| `Payroll` | `'payroll'` | Payroll Period | Salary processing |
| `Manufacturing` | `'manufacturing'` | Manufacturing Period | Production tracking |

**Methods:**

```php
public function label(): string  // Returns human-readable label
```

**Example:**
```php
$type = PeriodType::Accounting;
echo $type->value;  // 'accounting'
echo $type->label();  // 'Accounting Period'
```

---

### PeriodStatus

**Location:** `src/Enums/PeriodStatus.php`

**Purpose:** Defines the lifecycle states of a fiscal period.

**Cases:**

| Case | Value | Label | Posting Allowed? |
|------|-------|-------|------------------|
| `Pending` | `'pending'` | Pending | ❌ No |
| `Open` | `'open'` | Open | ✅ Yes |
| `Closed` | `'closed'` | Closed | ❌ No |
| `Locked` | `'locked'` | Locked | ❌ No |

**Methods:**

```php
public function label(): string  // Returns human-readable label
public function canTransitionTo(PeriodStatus $newStatus): bool  // Check valid transitions
public function isPostingAllowed(): bool  // Check if posting allowed
```

**State Transitions:**

| From | Allowed To |
|------|------------|
| Pending | Open |
| Open | Closed |
| Closed | Locked, Open (reopen) |
| Locked | (none - terminal state) |

**Example:**
```php
$status = PeriodStatus::Open;

// Check if can close
if ($status->canTransitionTo(PeriodStatus::Closed)) {
    // Valid transition
}

// Check posting
if ($status->isPostingAllowed()) {
    // Can post transactions
}
```

---

## Value Objects (3 total)

---

### PeriodDateRange

**Location:** `src/ValueObjects/PeriodDateRange.php`

**Purpose:** Immutable representation of a period's date range with validation.

**Constructor:**

```php
public function __construct(
    private DateTimeImmutable $startDate,
    private DateTimeImmutable $endDate
)
```

**Throws:** `InvalidArgumentException` if end date is before start date.

**Factory Methods:**

| Method | Description |
|--------|-------------|
| `fromStrings(string $start, string $end)` | Create from Y-m-d strings |
| `forMonth(int $year, int $month)` | Create monthly range |
| `forQuarter(int $year, int $quarter)` | Create quarterly range |
| `forYear(int $year)` | Create yearly range |

**Instance Methods:**

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getStartDate()` | `DateTimeImmutable` | Get start date |
| `getEndDate()` | `DateTimeImmutable` | Get end date |
| `containsDate(DateTimeImmutable $date)` | `bool` | Check if date in range |
| `overlaps(PeriodDateRange $other)` | `bool` | Check for overlap |
| `getDayCount()` | `int` | Get number of days |
| `equals(PeriodDateRange $other)` | `bool` | Check equality |

**Examples:**

```php
// Create monthly range
$january = PeriodDateRange::forMonth(2024, 1);
echo $january->getStartDate()->format('Y-m-d');  // 2024-01-01
echo $january->getEndDate()->format('Y-m-d');    // 2024-01-31

// Check date containment
$january->containsDate(new DateTimeImmutable('2024-01-15'));  // true

// Check overlap
$q1 = PeriodDateRange::forQuarter(2024, 1);
$january->overlaps($q1);  // true
```

---

### PeriodMetadata

**Location:** `src/ValueObjects/PeriodMetadata.php`

**Purpose:** Immutable container for period descriptive information.

**Constructor:**

```php
public function __construct(
    public readonly string $name,
    public readonly ?string $description,
    public readonly string $fiscalYear
)
```

**Properties:**

| Property | Type | Description |
|----------|------|-------------|
| `$name` | `string` | Period name (e.g., "JAN-2024", "2024-Q1") |
| `$description` | `?string` | Optional description |
| `$fiscalYear` | `string` | Fiscal year (e.g., "2024") |

**Example:**

```php
$metadata = new PeriodMetadata(
    name: 'JAN-2024',
    description: 'January 2024 Accounting Period',
    fiscalYear: '2024'
);
```

---

### FiscalYear

**Location:** `src/ValueObjects/FiscalYear.php`

**Purpose:** Represents a fiscal year with support for non-calendar years.

**Constructor:**

```php
public function __construct(
    public readonly int $year,
    public readonly int $startMonth = 1  // 1 = January (calendar year)
)
```

**Methods:**

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getStartDate()` | `DateTimeImmutable` | First day of fiscal year |
| `getEndDate()` | `DateTimeImmutable` | Last day of fiscal year |
| `contains(DateTimeImmutable $date)` | `bool` | Check if date in fiscal year |
| `equals(FiscalYear $other)` | `bool` | Check equality |

**Examples:**

```php
// Calendar year (Jan-Dec)
$fy2024 = new FiscalYear(2024);

// Fiscal year starting in April (Apr-Mar)
$fyApril = new FiscalYear(2024, 4);
echo $fyApril->getStartDate()->format('Y-m-d');  // 2024-04-01
echo $fyApril->getEndDate()->format('Y-m-d');    // 2025-03-31
```

---

## Exceptions (8 total)

---

### Exception Hierarchy

All exceptions extend `PeriodException`, which extends PHP's base `Exception`:

```
Exception
└── PeriodException (base)
    ├── InvalidPeriodStatusException
    ├── NoOpenPeriodException
    ├── OverlappingPeriodException
    ├── PeriodHasTransactionsException
    ├── PeriodNotFoundException
    ├── PeriodReopeningUnauthorizedException
    └── PostingPeriodClosedException
```

---

### PeriodException

**Location:** `src/Exceptions/PeriodException.php`

**Purpose:** Base exception for all period-related errors.

---

### InvalidPeriodStatusException

**Location:** `src/Exceptions/InvalidPeriodStatusException.php`

**Purpose:** Thrown when attempting an invalid status transition.

**Factory Methods:**

```php
public static function forTransition(string $from, string $to): self
```

**Example:**
```php
throw InvalidPeriodStatusException::forTransition('locked', 'open');
// Message: "Cannot transition from locked to open"
```

---

### NoOpenPeriodException

**Location:** `src/Exceptions/NoOpenPeriodException.php`

**Purpose:** Thrown when no open period exists for a type.

**Factory Methods:**

```php
public static function forType(string $typeName): self
```

**Example:**
```php
throw NoOpenPeriodException::forType('Accounting Period');
// Message: "No open period found for type: Accounting Period"
```

---

### OverlappingPeriodException

**Location:** `src/Exceptions/OverlappingPeriodException.php`

**Purpose:** Thrown when creating a period that would overlap with existing periods.

---

### PeriodHasTransactionsException

**Location:** `src/Exceptions/PeriodHasTransactionsException.php`

**Purpose:** Thrown when attempting to delete a period that has transactions posted.

---

### PeriodNotFoundException

**Location:** `src/Exceptions/PeriodNotFoundException.php`

**Purpose:** Thrown when a requested period doesn't exist.

**Factory Methods:**

```php
public static function forId(string $id): self
```

**Example:**
```php
throw PeriodNotFoundException::forId('01HY7X8Z9ABC...');
// Message: "Period not found with ID: 01HY7X8Z9ABC..."
```

---

### PeriodReopeningUnauthorizedException

**Location:** `src/Exceptions/PeriodReopeningUnauthorizedException.php`

**Purpose:** Thrown when user lacks permission to reopen a period.

**Factory Methods:**

```php
public static function forUser(string $userId, string $periodId): self
```

---

### PostingPeriodClosedException

**Location:** `src/Exceptions/PostingPeriodClosedException.php`

**Purpose:** Thrown when attempting to post to a closed period.

---

## Usage Patterns

### Pattern 1: Transaction Posting Validation

```php
public function postTransaction(array $data): void
{
    $date = new DateTimeImmutable($data['date']);
    
    try {
        if (!$this->periodManager->isPostingAllowed($date, PeriodType::Accounting)) {
            throw new \DomainException('Period is not open for posting');
        }
    } catch (NoOpenPeriodException $e) {
        throw new \DomainException('No accounting period is open');
    }
    
    // Proceed with posting...
}
```

### Pattern 2: Month-End Close Process

```php
public function performMonthEndClose(string $periodId, string $userId): void
{
    // 1. Validate all sub-ledgers are reconciled
    // 2. Close the period
    $this->periodManager->closePeriod(
        periodId: $periodId,
        reason: 'Month-end close completed',
        userId: $userId
    );
    
    // 3. Create next period
    $nextPeriod = $this->periodManager->createNextPeriod(PeriodType::Accounting);
    
    // 4. Open the new period (if needed)
    // ...
}
```

### Pattern 3: Year-End Lock

```php
public function performYearEndLock(string $fiscalYear, string $userId): void
{
    $periods = $this->periodManager->listPeriods(
        type: PeriodType::Accounting,
        fiscalYear: $fiscalYear
    );
    
    foreach ($periods as $period) {
        // Close if open
        if ($period->getStatus() === PeriodStatus::Open) {
            $this->periodManager->closePeriod($period->getId(), 'Year-end close', $userId);
        }
        
        // Lock if closed (implement via repository)
        // ...
    }
}
```

---

**Last Updated:** 2024-11-24
