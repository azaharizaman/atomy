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

## 📖 Documentation

### Package Documentation
- [Getting Started Guide](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Integration Guide](docs/integration-guide.md)
- [Examples](docs/examples/)

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress
- `REQUIREMENTS.md` - Requirements
- `TEST_SUITE_SUMMARY.md` - Tests
- `VALUATION_MATRIX.md` - Valuation
