# FixedAssetDepreciation Package Architecture

## 1. Package Overview

### Purpose

The **Nexus\FixedAssetDepreciation** package provides a comprehensive depreciation calculation engine for fixed assets within the Nexus ERP system. It serves as the **specialized depreciation layer** that enables organizations to calculate, track, and manage asset depreciation using multiple methodologies compliant with both GAAP and IFRS accounting standards. This package extends beyond basic depreciation offered in the Assets package by providing advanced calculation methods, schedule management, tax-book reconciliation, and revaluation capabilities.

### Scope

This package handles all fixed asset depreciation functions distinct from general asset lifecycle management:

- **Depreciation Calculation Engine**: Multiple depreciation methods (straight-line, declining balance, sum-of-years-digits, units-of-production, annuity)
- **Depreciation Schedule Management**: Automated schedule generation, adjustment, and forecasting
- **Asset Revaluation**: IFRS-compliant revaluation model with equity adjustments
- **Tax vs Book Depreciation**: Parallel calculation for tax reporting and book purposes
- **Depreciation Journal Entries**: Integration with general ledger for automated posting
- **Partial Asset Depreciation**: Support for asset splits, combinations, and impairments

### Namespace

```text
Nexus\FixedAssetDepreciation
```

### Position in Architecture

| Layer | Role |
|-------|------|
| Layer 1 (Atomic) | Pure business logic, framework-agnostic |
| Domain | Financial Management |
| Dependencies | Common, PSR interfaces only |
| Dependents | Orchestrators (FinanceOperations), Adapters |

---

## 2. Architecture Principles

This package adheres strictly to the Layer 1 (Atomic Package) requirements as defined in the root [`ARCHITECTURE.md`](../../ARCHITECTURE.md).

### 2.1 Framework Agnosticism

The package contains **zero framework dependencies**:

- ✅ No Laravel, Symfony, or other framework imports in `/src`
- ✅ All external dependencies expressed via interfaces in `Contracts/`
- ✅ Pure PHP 8.3+ with strict typing
- ✅ PSR-3 logging and PSR-14 event dispatching support

### 2.2 Dependency Injection

All dependencies are injected via constructors:

```php
final readonly class DepreciationCalculator
{
    public function __construct(
        private DepreciationMethodInterface $method,
        private AssetDataProviderInterface $assetProvider,
        private PeriodProviderInterface $periodProvider,
    ) {}
}
```

### 2.3 CQRS Repository Pattern

Following the repository design pattern from ARCHITECTURE.md:

- `{Entity}QueryInterface`: Side-effect-free read operations
- `{Entity}PersistInterface`: Write operations with validation

### 2.4 Immutability

- **Service Classes**: `final readonly class` with constructor property promotion
- **Value Objects**: All properties are `readonly`
- **Entities**: Use immutable design patterns where possible

### 2.5 Single Responsibility

The package follows the atomicity principle - it addresses ONE business domain:

- ❌ Does NOT handle General Ledger (Finance package)
- ❌ Does NOT handle Asset acquisition/lifecycle (Assets package)
- ❌ Does NOT handle Budget management (Budget package)
- ❌ Does NOT handle Tax calculations (Tax package)
- ✅ Focuses ONLY on depreciation calculations, schedules, and revaluations

---

## 3. Directory Structure

```text
packages/FixedAssetDepreciation/
├── src/
│   ├── Contracts/
│   │   ├── DepreciationManagerInterface.php        # Primary facade
│   │   ├── DepreciationCalculatorInterface.php     # Calculation engine
│   │   ├── DepreciationScheduleManagerInterface.php # Schedule management
│   │   ├── AssetRevaluationInterface.php           # Revaluation operations
│   │   ├── DepreciationMethodInterface.php         # Method abstraction
│   │   ├── DepreciationQueryInterface.php          # CQRS: Read operations
│   │   ├── DepreciationPersistInterface.php        # CQRS: Write operations
│   │   ├── DepreciationScheduleQueryInterface.php # Schedule queries
│   │   ├── DepreciationSchedulePersistInterface.php # Schedule persistence
│   │   ├── RevaluationQueryInterface.php           # Revaluation queries
│   │   ├── RevaluationPersistInterface.php        # Revaluation persistence
│   │   └── Integration/
│   │       ├── AssetDataProviderInterface.php      # Asset data access
│   │       ├── ChartOfAccountProviderInterface.php # GL account mapping
│   │       ├── JournalEntryProviderInterface.php   # JE posting
│   │       ├── PeriodProviderInterface.php         # Fiscal period
│   │       └── CurrencyProviderInterface.php       # Multi-currency
│   │
│   ├── Entities/
│   │   ├── AssetDepreciation.php                  # Depreciation entity
│   │   ├── DepreciationSchedule.php               # Schedule entity
│   │   ├── DepreciationMethod.php                 # Method entity
│   │   ├── AssetRevaluation.php                   # Revaluation entity
│   │   └── DepreciationAdjustment.php             # Adjustment entity
│   │
│   ├── Enums/
│   │   ├── DepreciationMethodType.php             # SL, DDB, SYD, UOP, Annuity
│   │   ├── DepreciationStatus.php                 # Calculated, Posted, Reversed
│   │   ├── RevaluationType.php                    # Increment, Decrement
│   │   ├── DepreciationType.php                   # Book, Tax
│   │   └── ProrateConvention.php                   # Full month, Daily, None
│   │
│   ├── ValueObjects/
│   │   ├── DepreciationAmount.php                 # Immutable depreciation amount
│   │   ├── DepreciationSchedulePeriod.php         # Single period schedule
│   │   ├── DepreciationForecast.php              # Future depreciation
│   │   ├── BookValue.php                          # Current book value
│   │   ├── RevaluationAmount.php                  # Revaluation delta
│   │   └── DepreciationLife.php                   # Useful life and salvage
│   │
│   ├── Services/
│   │   ├── DepreciationManager.php                # Main orchestrator
│   │   ├── DepreciationCalculator.php             # Calculation engine
│   │   ├── DepreciationScheduleGenerator.php      # Schedule generation
│   │   ├── AssetRevaluationService.php            # Revaluation logic
│   │   ├── DepreciationMethodFactory.php          # Method instantiation
│   │   └── TaxBookDepreciationEngine.php          # Tax vs Book parallel
│   │
│   ├── Exceptions/
│   │   ├── DepreciationException.php              # Base exception
│   │   ├── DepreciationCalculationException.php   # Calculation error
│   │   ├── InvalidDepreciationMethodException.php # Invalid method
│   │   ├── AssetNotDepreciableException.php       # Non-depreciable asset
│   │   ├── DepreciationPeriodClosedException.php  # Period closed
│   │   ├── RevaluationException.php               # Revaluation error
│   │   ├── ScheduleNotFoundException.php          # Schedule not found
│   │   └── DepreciationOverrideException.php      # Override conflict
│   │
│   └── Events/
│       ├── DepreciationCalculatedEvent.php        # Calculation complete
│       ├── DepreciationPostedEvent.php             # JE posted
│       ├── DepreciationReversedEvent.php          # Reversal complete
│       ├── AssetRevaluedEvent.php                 # Revaluation complete
│       └── DepreciationScheduleAdjustedEvent.php  # Schedule modified
│
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   ├── ValueObjects/
│   │   └── Entities/
│   └── Feature/
│
├── composer.json
├── README.md
├── ARCHITECTURE.md
├── REQUIREMENTS.md
├── IMPLEMENTATION_SUMMARY.md
├── VALUATION_MATRIX.md
└── LICENSE
```

---

## 4. Domain Model

### 4.1 Core Entities

#### AssetDepreciation

Represents a single depreciation calculation for an asset in a specific period.

```php
final class AssetDepreciation
{
    public function __construct(
        public readonly string $id,
        public readonly string $assetId,
        public readonly string $scheduleId,
        public readonly string $periodId,
        public readonly DepreciationMethodType $methodType,
        public readonly DepreciationType $depreciationType, // Book or Tax
        public readonly DepreciationAmount $depreciationAmount,
        public readonly BookValue $bookValueBefore,
        public readonly BookValue $bookValueAfter,
        public readonly DateTimeImmutable $calculationDate,
        public readonly ?DateTimeImmutable $postingDate,
        public readonly DepreciationStatus $status,
    ) {}
}
```

#### DepreciationSchedule

Represents the full depreciation schedule for an asset from acquisition to disposal.

```php
final class DepreciationSchedule
{
    public function __construct(
        public readonly string $id,
        public readonly string $assetId,
        public readonly DepreciationMethodType $methodType,
        public readonly DepreciationType $depreciationType,
        public readonly DepreciationLife $depreciationLife,
        public readonly DateTimeImmutable $acquisitionDate,
        public readonly DateTimeImmutable $startDepreciationDate,
        public readonly ?DateTimeImmutable $endDepreciationDate,
        public readonly ProrateConvention $prorateConvention,
        public readonly array $periods, // DepreciationSchedulePeriod[]
        public readonly DepreciationStatus $status,
    ) {}
}
```

#### AssetRevaluation

Represents an asset revaluation event (IFRS IAS 16 compliant).

```php
final class AssetRevaluation
{
    public function __construct(
        public readonly string $id,
        public readonly string $assetId,
        public readonly DateTimeImmutable $revaluationDate,
        public readonly RevaluationType $revaluationType,
        public readonly BookValue $previousBookValue,
        public readonly BookValue $newBookValue,
        public readonly RevaluationAmount $revaluationAmount,
        public readonly ?string $glAccountId,
        public readonly string $reason,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
```

### 4.2 Value Objects

#### DepreciationAmount

Immutable value object representing depreciation amounts with currency support.

```php
final readonly class DepreciationAmount
{
    public function __construct(
        public float $amount,
        public string $currency,
        public ?float $accumulatedDepreciation,
    ) {}

    public function add(self $other): self
    public function subtract(self $other): self
    public function multiply(float $factor): self
}
```

#### BookValue

Immutable value object representing the book value of an asset.

```php
final readonly class BookValue
{
    public function __construct(
        public float $cost,
        public float $salvageValue,
        public float $accumulatedDepreciation,
        public float $netBookValue,
    ) {}

    public function depreciate(DepreciationAmount $amount): self
    public function revalue(float $newCost, float $newSalvage): self
}
```

#### DepreciationLife

Immutable value object representing the depreciation life of an asset.

```php
final readonly class DepreciationLife
{
    public function __construct(
        public int $usefulLifeYears,
        public int $usefulLifeMonths,
        public float $salvageValue,
        public float $totalDepreciableAmount,
    ) {}

    public function getTotalMonths(): int
    public function getMonthlyDepreciation(): float
}
```

---

## 5. Depreciation Methods

### 5.1 Supported Methods

The package implements multiple depreciation methods compliant with GAAP and IFRS:

| Method | Code | Tier | Description |
|--------|------|------|-------------|
| Straight-Line | `STRAIGHT_LINE` | 1 | Equal depreciation over useful life |
| Straight-Line Daily | `STRAIGHT_LINE_DAILY` | 1 | Daily prorating for mid-month |
| Double Declining Balance | `DOUBLE_DECLINING` | 2 | 200% of straight-line rate |
| 150% Declining Balance | `DECLINING_150` | 2 | 150% of straight-line rate |
| Sum-of-Years-Digits | `SUM_OF_YEARS` | 2 | Accelerated based on sum of years |
| Units of Production | `UNITS_OF_PRODUCTION` | 3 | Usage-based depreciation |
| Annuity Method | `ANNUITY` | 3 | Interest-based calculation |
| MACRS | `MACRS` | 3 | US tax depreciation |
| Bonus Depreciation | `BONUS` | 3 | First-year bonus deduction |

### 5.2 Method Interface

Each depreciation method implements the `DepreciationMethodInterface`:

```php
interface DepreciationMethodInterface
{
    public function calculate(
        float $cost,
        float $salvageValue,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $options = []
    ): DepreciationAmount;

    public function getType(): DepreciationMethodType;

    public function supportsProrate(): bool;

    public function isAccelerated(): bool;

    public function validate(float $cost, float $salvageValue, array $options): bool;
}
```

### 5.3 Method Implementation Example

```php
final readonly class StraightLineDepreciationMethod implements DepreciationMethodInterface
{
    public function calculate(
        float $cost,
        float $salvageValue,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $options = []
    ): DepreciationAmount {
        $depreciableAmount = $cost - $salvageValue;
        $usefulLifeMonths = $options['useful_life_months'] ?? 12;
        
        $monthlyDepreciation = $depreciableAmount / $usefulLifeMonths;
        
        // Handle daily prorating if specified
        if (($options['prorate_daily'] ?? false) && $startDate instanceof DateTimeImmutable) {
            $daysInMonth = (int)date('t', $startDate->getTimestamp());
            $dayOfMonth = (int)$startDate->format('j');
            $prorateFactor = ($daysInMonth - $dayOfMonth + 1) / $daysInMonth;
            $monthlyDepreciation *= $prorateFactor;
        }
        
        return new DepreciationAmount(
            amount: round($monthlyDepreciation, 2),
            currency: $options['currency'] ?? 'USD',
            accumulatedDepreciation: null
        );
    }

    public function getType(): DepreciationMethodType
    {
        return DepreciationMethodType::STRAIGHT_LINE;
    }

    public function supportsProrate(): bool
    {
        return true;
    }

    public function isAccelerated(): bool
    {
        return false;
    }

    public function validate(float $cost, float $salvageValue, array $options): bool
    {
        return $cost > $salvageValue && ($options['useful_life_months'] ?? 0) > 0;
    }
}
```

---

## 6. Integration Points

### 6.1 Required Dependencies

| Package | Integration Purpose |
|---------|-------------------|
| **Nexus\Assets** | Asset data, acquisition info, book values |
| **Nexus\ChartOfAccount** | Depreciation expense and accumulated depreciation GL accounts |
| **Nexus\JournalEntry** | Automatic JE posting for depreciation |
| **Nexus\Period** | Fiscal period validation |
| **Nexus\Tenant** | Multi-entity isolation |

### 6.2 Optional Dependencies

| Package | Integration Purpose |
|---------|-------------------|
| **Nexus\Tax** | Tax depreciation calculations (MACRS, bonus) |
| **Nexus\Currency** | Multi-currency asset depreciation |
| **Nexus\Audit** | Audit trail for depreciation changes |
| **Nexus\Setting** | Per-tenant tier configuration |

### 6.3 Integration Interfaces

```php
interface AssetDataProviderInterface
{
    public function getAsset(string $assetId): ?AssetInterface;
    public function getAssetCost(string $assetId): float;
    public function getAssetSalvageValue(string $assetId): float;
    public function getAssetUsefulLife(string $assetId): int;
    public function getAssetDepreciationMethod(string $assetId): DepreciationMethodType;
    public function getAccumulatedDepreciation(string $assetId): float;
    public function updateAccumulatedDepreciation(string $assetId, float $amount): void;
}

interface JournalEntryProviderInterface
{
    public function createDepreciationEntry(
        string $assetId,
        string $periodId,
        float $depreciationAmount,
        string $expenseAccountId,
        string $accumulatedDepreciationAccountId,
        ?string $description = null
    ): JournalEntryInterface;

    public function reverseDepreciationEntry(string $originalEntryId): JournalEntryInterface;
}
```

---

## 7. Progressive Delivery Tiers

Following the progressive disclosure pattern used in other Nexus packages:

### Tier 1: Small Business (SB)

- Straight-Line depreciation (basic)
- Straight-Line with daily prorating
- Simple depreciation schedule generation
- Manual GL posting
- Basic depreciation reports

### Tier 2: Medium Business (MB)

- All Tier 1 features
- Double Declining Balance (DDB)
- 150% Declining Balance
- Sum-of-Years-Digits (SYD)
- Automatic schedule adjustments
- Depreciation forecasting
- Basic revaluation support

### Tier 3: Large Enterprise (LE)

- All Tier 1+2 features
- Units of Production (UOP)
- Annuity Method
- MACRS (US Tax)
- Bonus Depreciation
- Full IFRS/IAS 16 revaluation model
- Tax-Book parallel depreciation
- Automatic GL posting
- Multi-currency support
- Full audit trail

---

## 8. Service Orchestration

### 8.1 DepreciationManager

The primary facade coordinating all depreciation operations:

```php
final readonly class DepreciationManager implements DepreciationManagerInterface
{
    public function __construct(
        private DepreciationCalculatorInterface $calculator,
        private DepreciationScheduleManagerInterface $scheduleManager,
        private AssetRevaluationInterface $revaluationService,
        private AssetDataProviderInterface $assetProvider,
        private JournalEntryProviderInterface $journalProvider,
    ) {}

    public function calculateDepreciation(string $assetId, string $periodId): AssetDepreciation;
    public function generateSchedule(string $assetId): DepreciationSchedule;
    public function runPeriodicDepreciation(string $periodId): DepreciationRunResult;
    public function reverseDepreciation(string $depreciationId): void;
    public function revalueAsset(string $assetId, float $newValue): AssetRevaluation;
    public function calculateTaxDepreciation(string $assetId, string $taxMethod): DepreciationAmount;
}
```

### 8.2 DepreciationCalculator

The calculation engine handling method-specific logic:

```php
final readonly class DepreciationCalculator implements DepreciationCalculatorInterface
{
    public function __construct(
        private DepreciationMethodFactory $methodFactory,
        private AssetDataProviderInterface $assetProvider,
    ) {}

    public function calculate(
        string $assetId,
        ?DateTimeInterface $asOfDate = null,
        ?DepreciationType $type = null
    ): DepreciationAmount;

    public function calculateForPeriod(
        string $assetId,
        string $periodId,
        ?DepreciationType $type = null
    ): AssetDepreciation;

    public function forecast(
        string $assetId,
        int $numberOfPeriods
    ): DepreciationForecast;
}
```

---

## 9. Events

### Published Events

| Event | Severity | Description |
|-------|----------|-------------|
| `DepreciationCalculatedEvent` | MEDIUM | Single depreciation calculation complete |
| `DepreciationPostedEvent` | MEDIUM | JE posted to GL |
| `DepreciationReversedEvent` | HIGH | Depreciation reversed |
| `AssetRevaluedEvent` | HIGH | Asset revaluation complete |
| `DepreciationScheduleAdjustedEvent` | MEDIUM | Schedule modified |
| `DepreciationRunCompletedEvent` | HIGH | Batch depreciation run complete |

### Subscribed Events

| Event | Action |
|-------|--------|
| `Nexus\Assets\Events\AssetAcquiredEvent` | Generate initial depreciation schedule |
| `Nexus\Assets\Events\AssetDisposedEvent` | Close depreciation schedule |
| `Nexus\Period\Events\PeriodClosedEvent` | Process period-end depreciation |

---

## 10. Error Handling

### Domain Exceptions

All exceptions follow the naming convention defined in ARCHITECTURE.md:

```php
// Base exception
class DepreciationException extends \Exception {}

// Specific exceptions
class DepreciationCalculationException extends DepreciationException {}
class InvalidDepreciationMethodException extends DepreciationException {}
class AssetNotDepreciableException extends DepreciationException {}
class DepreciationPeriodClosedException extends DepreciationException {}
class RevaluationException extends DepreciationException {}
class ScheduleNotFoundException extends DepreciationException {}
class DepreciationOverrideException extends DepreciationException {}
```

---

## 11. Testing Strategy

### Unit Tests

- All depreciation method calculations
- Value object arithmetic
- Schedule generation logic
- Revaluation calculations

### Integration Tests

- Asset provider integration
- Journal entry provider integration
- Period validation
- Multi-currency calculations

### Feature Tests

- Full depreciation run workflow
- GL posting integration
- Revaluation workflow

---

## 12. Anti-Patterns Avoided

This package strictly avoids the following anti-patterns:

| Anti-Pattern | Avoidance Strategy |
|--------------|-------------------|
| God Package | Single responsibility - depreciation only |
| Framework Coupling | Pure PHP, interface-driven |
| Circular Dependencies | Clear dependency direction (inward to domain) |
| Stateful Services | Readonly services, external state via interfaces |
| Missing Type Hints | Strict PHP 8.3 types on all methods |
| Duplicate Functionality | Leverages Assets package for asset data |

---

## 13. Configuration

### Tier Configuration

```php
// Via Nexus\Setting
'settings' => [
    'fixed_asset_depreciation.tier' => 'basic', // basic|advanced|enterprise
    'fixed_asset_depreciation.default_method' => 'STRAIGHT_LINE',
    'fixed_asset_depreciation.prorate_convention' => 'daily',
    'fixed_asset_depreciation.auto_gl_post' => false,
]
```

### Method Configuration

```php
// Per-asset or global
'depreciation_methods' => [
    'straight_line' => [
        'class' => StraightLineDepreciationMethod::class,
        'default' => true,
    ],
    'double_declining' => [
        'class' => DoubleDecliningDepreciationMethod::class,
        'factor' => 2.0,
    ],
]
```

---

## 14. Related Documentation

- **[README.md](README.md)** - Package overview and quick start
- **[REQUIREMENTS.md](REQUIREMENTS.md)** - Complete requirements traceability
- **[Getting Started](docs/getting-started.md)** - Installation and configuration
- **[API Reference](docs/api-reference.md)** - Complete API documentation
- **[Integration Guide](docs/integration-guide.md)** - Framework integration
- **[Examples](docs/examples/)** - Usage examples
- Root **[ARCHITECTURE.md](../../ARCHITECTURE.md)** - System architecture
- Root **[FINANCE_ECOSYSTEM_EXECUTION_PLAN.md](../../plans/FINANCE_ECOSYSTEM_EXECUTION_PLAN.md)** - Finance ecosystem plan
