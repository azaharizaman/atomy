# Nexus\Period

Framework-agnostic fiscal period management package for the Nexus ERP system.

## Purpose

The Period package manages fiscal periods for various business processes (Accounting, Inventory, Payroll, Manufacturing). It ensures that transactions can only be posted to open periods and provides period lifecycle management (Pending → Open → Closed → Locked).

## Key Features

- **Multiple Period Types**: Independent period management for Accounting, Inventory, Payroll, Manufacturing
- **Period Lifecycle**: Pending → Open → Closed → Locked with validation
- **Transaction Validation**: Fast period validation (<5ms) for every transaction
- **Audit Trail**: Comprehensive logging of period status changes
- **Year-End Close**: Automated year-end closing with reconciliation support

## Architecture

### Contracts (Interfaces)
- `PeriodManagerInterface` - Main service contract for period operations
- `PeriodInterface` - Period entity contract
- `PeriodRepositoryInterface` - Persistence contract
- `PeriodValidatorInterface` - Validation logic contract
- `PeriodAuditLoggerInterface` - Audit logging contract

### Enums
- `PeriodType` - Accounting, Inventory, Payroll, Manufacturing
- `PeriodStatus` - Pending, Open, Closed, Locked

### Value Objects
- `PeriodDateRange` - Immutable start/end date range with validation
- `PeriodMetadata` - Name, description, fiscal year information
- `FiscalYear` - Fiscal year with start/end dates

## Usage Example

```php
use Nexus\Period\Services\PeriodManager;
use Nexus\Period\Enums\PeriodType;

// Check if posting is allowed for a specific date
$canPost = $periodManager->isPostingAllowed(
    new \DateTimeImmutable('2024-11-15'),
    PeriodType::Accounting
);

// Close the current accounting period
$periodManager->closePeriod(
    $periodId,
    'Monthly close for October 2024'
);

// Get the currently open period
$openPeriod = $periodManager->getOpenPeriod(PeriodType::Accounting);
```

## Integration

This package is consumed by:
- `Nexus\Finance` - for journal entry validation
- `Nexus\Accounting` - for financial statement period management
- `Nexus\Inventory` - for stock movement validation
- `Nexus\Payroll` - for payroll posting validation
- `Nexus\Manufacturing` - for production order validation

## Performance Requirements

- Period posting validation: < 5ms (critical path for all transactions)
- Get open period query: < 10ms with proper caching
- Period status change: < 50ms including audit logging
- List periods for fiscal year: < 100ms
- Bulk period creation (12 periods): < 500ms
## Fiscal Year Support

The Period package supports configurable fiscal years that differ from calendar years. This is essential for Finance integration and multi-period reporting.

### Configuration

Configure the fiscal year start month when binding the `PeriodManager` in your application service provider:

```php
// In AppServiceProvider.php (Laravel example)
$this->app->singleton(PeriodManagerInterface::class, function ($app) {
    return new PeriodManager(
        $app->make(PeriodRepositoryInterface::class),
        $app->make(CacheRepositoryInterface::class),
        $app->make(AuthorizationInterface::class),
        $app->make(AuditLoggerInterface::class),
        fiscalYearStartMonth: 7 // Fiscal year starts in July
    );
});
```

**Default:** If not specified, fiscal year start month defaults to `1` (January = calendar year).

### Fiscal Year API

#### 1. Get Fiscal Year Start Month

```php
$startMonth = $periodManager->getFiscalYearStartMonth();
// Returns: 7 (July) if configured, otherwise 1 (January)
```

#### 2. Get Period for Date

Convenience method to find which period a transaction date belongs to:

```php
$period = $periodManager->getPeriodForDate(
    new \DateTimeImmutable('2024-11-15'),
    PeriodType::Accounting
);

if ($period === null) {
    // No period exists for this date
} else {
    echo "Transaction belongs to period: {$period->getName()}";
}
```

#### 3. Get Fiscal Year for Date

Determines which fiscal year a date belongs to:

```php
// Example: Fiscal year starts in July (month 7)
$fy1 = $periodManager->getFiscalYearForDate(new \DateTimeImmutable('2024-06-30'));
// Returns: "2024" (before July, so belongs to FY-2024)

$fy2 = $periodManager->getFiscalYearForDate(new \DateTimeImmutable('2024-07-01'));
// Returns: "2025" (July or later, so belongs to FY-2025)
```

**Logic:**
- If the date's month is **before** the fiscal year start month → current calendar year
- If the date's month is **on or after** the fiscal year start month → next calendar year

#### 4. Get Fiscal Year Start Date

Returns the first day of a specified fiscal year:

```php
// Example: Fiscal year starts in July (month 7)
$startDate = $periodManager->getFiscalYearStartDate('2024');
// Returns: 2023-07-01 (FY-2024 starts July 1, 2023)

$startDate = $periodManager->getFiscalYearStartDate('2025');
// Returns: 2024-07-01 (FY-2025 starts July 1, 2024)
```

**Logic:**
- If fiscal year starts in **January** (month 1): FY-2024 starts on 2024-01-01
- If fiscal year starts in **any other month**: FY-2024 starts on (2024-1)-MM-01

### Fiscal Year Examples

#### Calendar Year (Default)

```php
// Fiscal year = Calendar year (starts January 1)
$manager = new PeriodManager(..., fiscalYearStartMonth: 1);

$fy = $manager->getFiscalYearForDate(new \DateTimeImmutable('2024-06-15'));
// Returns: "2024"

$start = $manager->getFiscalYearStartDate('2024');
// Returns: 2024-01-01
```

#### July Start (Common in Governments)

```php
// Fiscal year starts July 1
$manager = new PeriodManager(..., fiscalYearStartMonth: 7);

$fy1 = $manager->getFiscalYearForDate(new \DateTimeImmutable('2024-06-30'));
// Returns: "2024" (before July → FY-2024)

$fy2 = $manager->getFiscalYearForDate(new \DateTimeImmutable('2024-07-01'));
// Returns: "2025" (July or after → FY-2025)

$start = $manager->getFiscalYearStartDate('2024');
// Returns: 2023-07-01 (FY-2024 starts July 1, 2023)
```

#### April Start (Common in UK/India)

```php
// Fiscal year starts April 1
$manager = new PeriodManager(..., fiscalYearStartMonth: 4);

$fy1 = $manager->getFiscalYearForDate(new \DateTimeImmutable('2024-03-31'));
// Returns: "2024" (before April → FY-2024)

$fy2 = $manager->getFiscalYearForDate(new \DateTimeImmutable('2024-04-01'));
// Returns: "2025" (April or after → FY-2025)

$start = $manager->getFiscalYearStartDate('2024');
// Returns: 2023-04-01 (FY-2024 starts April 1, 2023)
```

### Finance Integration

The fiscal year support enables Finance package features:

```php
// Find which period a journal entry belongs to
$entryDate = new \DateTimeImmutable('2024-11-15');
$period = $periodManager->getPeriodForDate($entryDate, PeriodType::Accounting);

if ($period === null) {
    throw new \Exception("No accounting period exists for {$entryDate->format('Y-m-d')}");
}

// Determine fiscal year for financial statements
$fiscalYear = $periodManager->getFiscalYearForDate($entryDate);
echo "Entry belongs to FY-{$fiscalYear}";

// Get all periods for a fiscal year
$periods = $periodManager->listPeriods(PeriodType::Accounting, $fiscalYear);
```