# Nexus\Sequencing

**Framework-agnostic auto-numbering and sequence generation package.**

## Overview

`Nexus\Sequencing` provides a robust, atomic, and transaction-safe system for generating unique sequential identifiers (e.g., invoice numbers, order IDs, reference codes) with customizable patterns, counter management, and advanced features like gap filling, bulk generation, and pattern versioning.

## Key Features

- **Framework-Agnostic Core**: Pure PHP logic with no Laravel dependencies
- **Atomic Number Generation**: Database-level locking (`SELECT FOR UPDATE`) ensures zero duplicates
- **Transaction-Safe**: Counter increments roll back if the parent transaction fails
- **Flexible Patterns**: Support for variables like `{YEAR}`, `{MONTH}`, `{COUNTER}`, and custom context variables
- **Counter Reset Strategies**: Daily, Monthly, Yearly, Never, or custom count-based resets
- **Preview Mode**: Preview the next number without consuming the counter
- **Gap Management**: Fill gaps from voided/cancelled numbers or report gaps
- **Bulk Generation**: Generate multiple numbers atomically with optimized locking
- **Pattern Versioning**: Support time-based pattern changes with effective dates
- **Exhaustion Monitoring**: Detect when counters approach maximum values
- **Number Reservation**: Reserve numbers temporarily with configurable TTL
- **Validation**: Validate if a given number matches a pattern's format

## Architecture

This package follows the **Nexus monorepo architecture**:

- **`packages/Sequencing/`**: Framework-agnostic business logic (the "engine")
- **`apps/Atomy/`**: Laravel implementation with Eloquent models, migrations, and repositories

### Package Structure

```
packages/Sequencing/
├── composer.json
├── LICENSE
├── README.md
└── src/
    ├── Contracts/              # All interfaces
    │   ├── SequenceInterface.php
    │   ├── SequenceRepositoryInterface.php
    │   ├── SequenceAuditInterface.php
    │   ├── CounterRepositoryInterface.php
    │   ├── ReservationRepositoryInterface.php
    │   └── GapRepositoryInterface.php
    ├── Exceptions/             # Domain exceptions
    │   ├── InvalidPatternException.php
    │   ├── SequenceExhaustedException.php
    │   ├── SequenceNotFoundException.php
    │   ├── SequenceLockedException.php
    │   ├── CounterOverflowException.php
    │   ├── PatternCollisionException.php
    │   ├── InvalidResetPeriodException.php
    │   └── ReservationExpiredException.php
    ├── ValueObjects/           # Immutable value objects
    │   ├── ResetPeriod.php
    │   ├── OverflowBehavior.php
    │   ├── GapPolicy.php
    │   ├── PatternVariable.php
    │   └── SequenceMetrics.php
    └── Services/               # Business logic
        ├── SequenceManager.php
        ├── PatternParser.php
        ├── CounterService.php
        ├── BulkGeneratorService.php
        ├── GapManager.php
        ├── ReservationService.php
        ├── PatternVersionManager.php
        ├── PatternMigrationService.php
        ├── ExhaustionMonitor.php
        ├── SequenceValidationService.php
        └── SequenceMetricsService.php
```

## Core Concepts

### 1. Sequence Definition

A sequence is defined by:
- **Name**: Unique identifier (e.g., `invoice_number`, `po_number`)
- **Scope**: Optional scope identifier for multi-tenant or partitioned sequences
- **Pattern**: Template with variables (e.g., `INV-{YEAR}-{COUNTER:5}`)
- **Reset Period**: When the counter resets (Daily, Monthly, Yearly, Never)
- **Step Size**: Increment value (default: 1)
- **Reset Limit**: Counter-based reset (e.g., reset after 1000 numbers)
- **Gap Policy**: How to handle gaps in sequences
- **Overflow Behavior**: What to do when counter reaches maximum

### 2. Pattern Variables

Built-in variables:
- `{YEAR}` - Full year (e.g., 2025)
- `{YY}` - Short year (e.g., 25)
- `{MONTH}` - Month with leading zero (01-12)
- `{DAY}` - Day with leading zero (01-31)
- `{COUNTER}` - Sequential counter
- `{COUNTER:5}` - Counter with padding (00001, 00002, etc.)

Custom context variables:
- `{DEPARTMENT}` - Provided at generation time
- `{BRANCH}` - Provided at generation time
- Any custom variable you define

### 3. Reset Periods

Control when counters reset:
- **Never**: Counter never resets (monotonically increasing)
- **Daily**: Counter resets at midnight
- **Monthly**: Counter resets on the 1st of each month
- **Yearly**: Counter resets on January 1st

### 4. Gap Policies

Handle missing numbers in sequences:
- **allow_gaps**: Default behavior, gaps are allowed (e.g., when transactions fail)
- **fill_gaps**: Reuse voided/cancelled numbers to fill gaps
- **report_gaps_only**: Track gaps but don't fill them

### 5. Overflow Behavior

Define what happens when counter approaches maximum:
- **throw_exception**: Throw `SequenceExhaustedException`
- **switch_pattern**: Automatically migrate to a new pattern
- **extend_padding**: Increase padding size (e.g., 9999 → 10000)

## Usage Example

### Generating a Number

```php
use Nexus\Sequencing\Services\SequenceManager;

// Inject via dependency injection
public function __construct(
    private readonly SequenceManager $sequenceManager
) {}

// Generate the next number
$invoiceNumber = $this->sequenceManager->generate(
    sequenceName: 'invoice_number',
    scopeIdentifier: 'tenant_123',
    contextVariables: [
        'DEPARTMENT' => 'SALES',
    ]
);
// Result: "INV-2025-SALES-00001"
```

### Previewing the Next Number

```php
$preview = $this->sequenceManager->preview(
    sequenceName: 'invoice_number',
    scopeIdentifier: 'tenant_123'
);
// Result: "INV-2025-SALES-00002" (counter not incremented)
```

### Bulk Generation

```php
$numbers = $this->bulkGenerator->generateBulk(
    sequenceName: 'ticket_number',
    count: 100
);
// Result: ["TKT-00001", "TKT-00002", ..., "TKT-00100"]
```

### Reserving Numbers

```php
// Reserve 10 numbers for batch processing
$reservedNumbers = $this->reservationService->reserve(
    sequenceName: 'order_number',
    count: 10,
    ttlMinutes: 30
);
// Result: ["ORD-001", "ORD-002", ..., "ORD-010"]

// Release unused numbers
$this->reservationService->release(
    sequenceName: 'order_number',
    numbers: ['ORD-005', 'ORD-008']
);
```

### Gap Management

```php
// Report on gaps in a sequence
$gapReport = $this->gapManager->getGapReport(
    sequenceName: 'invoice_number'
);
// Result: ["INV-00005", "INV-00012", "INV-00023"]

// Reclaim a gap (for fill_gaps policy)
$this->gapManager->reclaimGap(
    sequenceName: 'invoice_number',
    number: 'INV-00005'
);
```

## Business Rules

1. **Immutability**: Generated numbers cannot be changed once consumed
2. **Atomicity**: Database-level locking ensures no duplicates
3. **Transaction Safety**: Counter increments roll back with parent transaction
4. **Uniqueness**: Sequence name + scope identifier forms a composite key
5. **Manual Override**: Overridden values must be greater than the last generated number
6. **Preview Safety**: Previewing does not increment the counter
7. **Gap Reclaim**: Only voided/cancelled numbers can be reclaimed (if gap_policy allows)

## Integration with Atomy

The Laravel application (`apps/Atomy`) provides:

1. **Eloquent Models**: `Sequence`, `SequenceCounter`, `SequenceReservation`, `SequenceGap`
2. **Repository Implementations**: `DbSequenceRepository`, `DbCounterRepository`, etc.
3. **Migrations**: Database schema for sequences and counters
4. **Service Provider**: IoC bindings for dependency injection
5. **API Endpoints**: RESTful API for sequence management
6. **Configuration**: Tenant-specific sequence definitions

## Requirements Coverage

This package fulfills all requirements defined in `REQUIREMENTS.csv`:

- ✅ **ARC-SEQ-0019 to ARC-SEQ-0026**: Architectural requirements (framework-agnostic, interfaces, repositories)
- ✅ **BUS-SEQ-0043 to BUS-SEQ-0239**: Business requirements (immutability, atomicity, padding, overrides)
- ✅ **FUN-SEQ-0210 to FUN-SEQ-0245**: Functional requirements (generation, preview, bulk, gaps, versioning)
- ✅ **PER-SEQ-0329, PER-SEQ-0336**: Performance requirements (< 50ms generation, concurrent requests)

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


## License

MIT License. See [LICENSE](LICENSE) for details.

## Contributing

This package is part of the Nexus monorepo. See the main [ARCHITECTURE.md](../../ARCHITECTURE.md) for contribution guidelines.
