# Nexus\FixedAssetDepreciation

Enterprise-grade fixed asset depreciation calculation engine with Progressive Delivery for Small Business to Large Enterprise.

## Overview

The **Nexus\FixedAssetDepreciation** package provides a comprehensive depreciation calculation engine for fixed assets within the Nexus ERP system. It serves as the **specialized depreciation layer** that enables organizations to calculate, track, and manage asset depreciation using multiple methodologies compliant with both GAAP and IFRS accounting standards.

This package extends beyond basic depreciation offered in the [Nexus\Assets](../Assets/README.md) package by providing:
- Advanced calculation methods beyond basic straight-line
- Comprehensive schedule management with forecasting
- Tax vs Book parallel depreciation
- IFRS-compliant revaluation model
- Automated GL journal entry integration

## Key Features

### 🎯 Core Capabilities

- **Multiple Depreciation Methods**: Straight-Line, Double Declining, Sum-of-Years-Digits, Units of Production, Annuity, MACRS
- **Depreciation Scheduling**: Automated schedule generation, adjustment, and forecasting
- **Asset Revaluation**: IFRS IAS 16 compliant revaluation with equity adjustments
- **Tax-Book Reconciliation**: Parallel calculation for tax reporting and book purposes
- **GL Integration**: Automated journal entry posting for depreciation expense
- **Impairment Support**: Partial asset impairment calculations

### 🔄 Depreciation Methods

| Method | Tier | Description |
|--------|------|-------------|
| **Straight-Line** | 1 | Equal depreciation over useful life |
| **Straight-Line Daily** | 1 | Daily prorating for mid-month acquisitions (GAAP-compliant) |
| **Double Declining Balance** | 2 | 200% of straight-line rate (accelerated) |
| **150% Declining Balance** | 2 | 150% of straight-line rate |
| **Sum-of-Years-Digits** | 2 | Accelerated based on sum of years digits |
| **Units of Production** | 3 | Usage-based depreciation |
| **Annuity Method** | 3 | Interest-based calculation |
| **MACRS** | 3 | US tax depreciation (IRS tables) |
| **Bonus Depreciation** | 3 | First-year bonus deduction |

### 🔐 Financial Controls

- **Tenant Isolation**: Multi-tenant data security with per-tenant depreciation configurations
- **Period-Based Depreciation**: Fiscal period validation and depreciation tracking
- **GL Account Mapping**: Integration with chart of accounts for expense and accumulated depreciation
- **Audit Trail**: Comprehensive logging via Nexus\Audit
- **Approval Workflows**: Optional approval for revaluations (Tier 3)

## Progressive Feature Tiers

| Tier | Target | Key Features |
|------|--------|--------------|
| **Tier 1: Basic** | Small Business (SB) | Straight-Line (basic + daily), Simple schedules, Manual GL posting |
| **Tier 2: Advanced** | Medium Business (MB) | DDB, 150% DB, SYD, Schedule adjustments, Forecasting, Basic revaluation |
| **Tier 3: Enterprise** | Large Enterprise (LE) | UOP, Annuity, MACRS, Tax-Book parallel, Full IFRS revaluation, Auto GL posting |

## Architecture

### Framework Agnosticism

This package contains **pure PHP logic** and is completely framework-agnostic:

- ✅ No Laravel dependencies in `/src`
- ✅ All dependencies via contracts (interfaces)
- ✅ Readonly constructor property promotion
- ✅ Native PHP 8.3 enums with business logic
- ✅ Immutable value objects
- ✅ PSR-3 logging, PSR-14 event dispatching

### Directory Structure

```text
src/
├── Contracts/
│   ├── DepreciationManagerInterface.php          # Primary facade
│   ├── DepreciationCalculatorInterface.php       # Calculation engine
│   ├── DepreciationScheduleManagerInterface.php  # Schedule management
│   ├── AssetRevaluationInterface.php             # Revaluation operations
│   ├── DepreciationMethodInterface.php            # Method abstraction
│   ├── DepreciationQueryInterface.php             # CQRS: Read operations
│   ├── DepreciationPersistInterface.php           # CQRS: Write operations
│   └── Integration/                               # External package integrations
├── Entities/
│   ├── AssetDepreciation.php                     # Depreciation entity
│   ├── DepreciationSchedule.php                  # Schedule entity
│   ├── DepreciationMethod.php                    # Method entity
│   ├── AssetRevaluation.php                      # Revaluation entity
│   └── DepreciationAdjustment.php                # Adjustment entity
├── Enums/
│   ├── DepreciationMethodType.php                 # SL, DDB, SYD, UOP, Annuity
│   ├── DepreciationStatus.php                    # Calculated, Posted, Reversed
│   ├── RevaluationType.php                       # Increment, Decrement
│   ├── DepreciationType.php                       # Book, Tax
│   └── ProrateConvention.php                      # Full month, Daily, None
├── ValueObjects/
│   ├── DepreciationAmount.php                    # Immutable depreciation amount
│   ├── DepreciationSchedulePeriod.php            # Single period schedule
│   ├── DepreciationForecast.php                  # Future depreciation
│   ├── BookValue.php                             # Current book value
│   ├── RevaluationAmount.php                     # Revaluation delta
│   └── DepreciationLife.php                     # Useful life and salvage
├── Services/
│   ├── DepreciationManager.php                    # Main orchestrator
│   ├── DepreciationCalculator.php                # Calculation engine
│   ├── DepreciationScheduleGenerator.php         # Schedule generation
│   ├── AssetRevaluationService.php               # Revaluation logic
│   ├── DepreciationMethodFactory.php             # Method instantiation
│   └── TaxBookDepreciationEngine.php             # Tax vs Book parallel
├── Exceptions/
│   ├── DepreciationException.php                 # Base exception
│   ├── DepreciationCalculationException.php      # Calculation error
│   ├── InvalidDepreciationMethodException.php    # Invalid method
│   ├── AssetNotDepreciableException.php          # Non-depreciable asset
│   ├── DepreciationPeriodClosedException.php    # Period closed
│   ├── RevaluationException.php                 # Revaluation error
│   └── ScheduleNotFoundException.php            # Schedule not found
└── Events/
    ├── DepreciationCalculatedEvent.php           # Calculation complete
    ├── DepreciationPostedEvent.php               # JE posted
    ├── DepreciationReversedEvent.php            # Reversal complete
    ├── AssetRevaluedEvent.php                    # Revaluation complete
    └── DepreciationScheduleAdjustedEvent.php    # Schedule modified
```

### Integration Points

| Package | Integration Purpose |
|---------|-------------------|
| **Nexus\Assets** | Asset data, acquisition info, book values |
| **Nexus\ChartOfAccount** | Depreciation expense and accumulated depreciation GL accounts |
| **Nexus\JournalEntry** | Automatic JE posting for depreciation |
| **Nexus\Period** | Fiscal period validation |
| **Nexus\Tenant** | Multi-entity isolation |
| **Nexus\Tax** | Tax depreciation calculations (MACRS, bonus) - Optional |
| **Nexus\Currency** | Multi-currency asset depreciation - Optional |

## Installation

### 1. Add to Root Composer

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/FixedAssetDepreciation"
        }
    ]
}
```

### 2. Install Package

```bash
composer require nexus/fixed-asset-depreciation:"*@dev"
```

### 3. Register Service Provider (Laravel)

In `apps/Atomy/config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\FixedAssetDepreciationServiceProvider::class,
],
```

### 4. Configure Tier

Set the tier for your tenant in application settings:

```php
use Nexus\Setting\SettingManager;

$settings->setString('fixed_asset_depreciation.tier', 'basic'); // or 'advanced', 'enterprise'
```

## Quick Start

### Tier 1: Basic Depreciation

```php
use Nexus\FixedAssetDepreciation\Contracts\DepreciationManagerInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;

$manager = app(DepreciationManagerInterface::class);

// Calculate depreciation for a single asset in a period
$depreciation = $manager->calculateDepreciation(
    assetId: '01ASSET-A001',
    periodId: '01PERIOD-2026-01'
);

echo "Depreciation Amount: " . $depreciation->getDepreciationAmount()->getAmount();
echo "Book Value After: " . $depreciation->getBookValueAfter()->getNetBookValue();
```

### Generate Depreciation Schedule

```php
// Generate full depreciation schedule for an asset
$schedule = $manager->generateSchedule(assetId: '01ASSET-A001');

foreach ($schedule->getPeriods() as $period) {
    echo "Period: " . $period->getPeriodId();
    echo "Depreciation: " . $period->getDepreciationAmount();
    echo "Book Value: " . $period->getNetBookValue();
}
```

### Run Periodic Depreciation (Monthly)

```php
// Process all assets for period-end depreciation
$result = $manager->runPeriodicDepreciation(
    periodId: '01PERIOD-2026-01'
);

echo "Processed: " . $result->getProcessedCount() . " assets";
echo "Total Depreciation: " . $result->getTotalDepreciation();
```

### Reverse Depreciation

```php
// Reverse a depreciation calculation (e.g., due to error)
$manager->reverseDepreciation(
    depreciationId: '01DEP-2026-01-001',
    reason: 'Incorrect useful life used'
);
```

### Asset Revaluation (Tier 2+)

```php
use Nexus\FixedAssetDepreciation\Enums\RevaluationType;

// Revalue an asset (IFRS IAS 16 compliant)
$revaluation = $manager->revalueAsset(
    assetId: '01ASSET-A001',
    newValue: 15000.00,
    newSalvageValue: 1500.00,
    revaluationType: RevaluationType::INCREMENT,
    reason: 'Market value increase',
    glAccountId: '01GL-REVALUATION'
);

echo "Revaluation Amount: " . $revaluation->getRevaluationAmount()->getAmount();
echo "New Book Value: " . $revaluation->getNewBookValue()->getNetBookValue();
```

### Tax Depreciation (Tier 3)

```php
// Calculate tax depreciation (MACRS) parallel to book depreciation
$taxDepreciation = $manager->calculateTaxDepreciation(
    assetId: '01ASSET-A001',
    taxMethod: 'MACRS',
    taxYear: 2026
);

echo "Tax Depreciation: " . $taxDepreciation->getAmount();
echo "Book Depreciation: " . $bookDepreciation->getAmount();
echo "Tax vs Book Difference: " . $taxDepreciation->subtract($bookDepreciation);
```

### Depreciation Forecast

```php
// Forecast future depreciation
$forecast = $manager->forecastDepreciation(
    assetId: '01ASSET-A001',
    periods: 12 // Next 12 months
);

foreach ($forecast->getPeriodForecasts() as $period) {
    echo $period->getPeriodId() . ": " . $period->getForecastedAmount();
}
```

## Key Interfaces

### DepreciationManagerInterface

Primary facade providing access to all depreciation operations:

```php
calculateDepreciation(string $assetId, string $periodId): AssetDepreciation
generateSchedule(string $assetId): DepreciationSchedule
runPeriodicDepreciation(string $periodId): DepreciationRunResult
reverseDepreciation(string $depreciationId, string $reason): void
revalueAsset(string $assetId, float $newValue, RevaluationType $type): AssetRevaluation
calculateTaxDepreciation(string $assetId, string $taxMethod, int $taxYear): DepreciationAmount
forecastDepreciation(string $assetId, int $periods): DepreciationForecast
```

### DepreciationCalculatorInterface

Core calculation engine:

```php
calculate(string $assetId, ?DateTimeInterface $asOfDate, ?DepreciationType $type): DepreciationAmount
calculateForPeriod(string $assetId, string $periodId, ?DepreciationType $type): AssetDepreciation
forecast(string $assetId, int $numberOfPeriods): DepreciationForecast
```

### DepreciationScheduleManagerInterface

Schedule management:

```php
generate(string $assetId): DepreciationSchedule
adjust(string $scheduleId, array $adjustments): DepreciationSchedule
close(string $assetId): void
recalculate(string $assetId): DepreciationSchedule
```

### AssetRevaluationInterface

Revaluation operations:

```php
revalue(string $assetId, float $newCost, RevaluationType $type): AssetRevaluation
reverse(string $revaluationId): AssetRevaluation
getRevaluationHistory(string $assetId): array
```

## Domain Model

### Entities

| Entity | Description |
|--------|-------------|
| [`AssetDepreciation`](src/Entities/AssetDepreciation.php) | Single depreciation calculation per period |
| [`DepreciationSchedule`](src/Entities/DepreciationSchedule.php) | Full schedule from acquisition to disposal |
| [`DepreciationMethod`](src/Entities/DepreciationMethod.php) | Depreciation method configuration |
| [`AssetRevaluation`](src/Entities/AssetRevaluation.php) | Asset revaluation event |
| [`DepreciationAdjustment`](src/Entities/DepreciationAdjustment.php) | Schedule adjustment record |

### Value Objects

| Value Object | Purpose |
|--------------|---------|
| [`DepreciationAmount`](src/ValueObjects/DepreciationAmount.php) | Immutable monetary amount with arithmetic |
| [`BookValue`](src/ValueObjects/BookValue.php) | Current book value with depreciation tracking |
| [`DepreciationLife`](src/ValueObjects/DepreciationLife.php) | Useful life and salvage value |
| [`DepreciationForecast`](src/ValueObjects/DepreciationForecast.php) | Future depreciation projections |
| [`DepreciationSchedulePeriod`](src/ValueObjects/DepreciationSchedulePeriod.php) | Single period in schedule |
| [`RevaluationAmount`](src/ValueObjects/RevaluationAmount.php) | Revaluation delta with breakdown |

### Enums

| Enum | Values |
|------|--------|
| [`DepreciationMethodType`](src/Enums/DepreciationMethodType.php) | STRAIGHT_LINE, DOUBLE_DECLINING, SUM_OF_YEARS, UNITS_OF_PRODUCTION, ANNUITY, MACRS, BONUS |
| [`DepreciationType`](src/Enums/DepreciationType.php) | BOOK, TAX |
| [`DepreciationStatus`](src/Enums/DepreciationStatus.php) | CALCULATED, POSTED, REVERSED, ADJUSTED |
| [`RevaluationType`](src/Enums/RevaluationType.php) | INCREMENT, DECREMENT |
| [`ProrateConvention`](src/Enums/ProrateConvention.php) | FULL_MONTH, DAILY, NONE |

## Exceptions

| Exception | Description |
|-----------|-------------|
| [`DepreciationException`](src/Exceptions/DepreciationException.php) | Base exception |
| [`DepreciationCalculationException`](src/Exceptions/DepreciationCalculationException.php) | Calculation error |
| [`InvalidDepreciationMethodException`](src/Exceptions/InvalidDepreciationMethodException.php) | Invalid method for asset |
| [`AssetNotDepreciableException`](src/Exceptions/AssetNotDepreciableException.php) | Asset not eligible for depreciation |
| [`DepreciationPeriodClosedException`](src/Exceptions/DepreciationPeriodClosedException.php) | Period already closed |
| [`RevaluationException`](src/Exceptions/RevaluationException.php) | Revaluation error |
| [`ScheduleNotFoundException`](src/Exceptions/ScheduleNotFoundException.php) | Schedule not found |
| [`DepreciationOverrideException`](src/Exceptions/DepreciationOverrideException.php) | Override conflict |

## Depreciation Formulas

### Straight-Line (Tier 1)

```
Annual Depreciation = (Cost - Salvage) / Useful Life
```

Daily prorating for mid-month acquisitions:
```
Monthly = Annual / 12
Daily Proration = Monthly × (Days Owned / Days in Month)
```

### Double Declining Balance (Tier 2)

```
Rate = 2 / Useful Life (years)
Year N Depreciation = Book Value at Start of Year × Rate
(Cannot depreciate below salvage value)
```

### Sum-of-Years-Digits (Tier 2)

```
Sum = n(n+1)/2 where n = useful life
Year N Depreciation = (Cost - Salvage) × (Remaining Life / Sum)
```

### Units of Production (Tier 3)

```
Depreciation = (Cost - Salvage) × (Units Produced / Total Expected Units)
```

## Configuration

### Settings (via Nexus\Setting)

```php
'fixed_asset_depreciation.tier' => 'basic',  // basic|advanced|enterprise
'fixed_asset_depreciation.default_method' => 'STRAIGHT_LINE',
'fixed_asset_depreciation.prorate_convention' => 'daily',  // daily|full_month|none
'fixed_asset_depreciation.auto_gl_post' => false,  // Tier 3
'fixed_asset_depreciation.enable_tax_depreciation' => false,  // Tier 3
'fixed_asset_depreciation.max_forecast_periods' => 60,
```

### GL Account Mapping

```php
// Default GL accounts (can be overridden per asset category)
'fixed_asset_depreciation.gl_defaults' => [
    'depreciation_expense' => '6000-DEPR-EXP',
    'accumulated_depreciation' => '1600-ACCUM-DEPR',
    'gains_on_disposal' => '7000-GAIN-DISP',
    'losses_on_disposal' => '8000-LOSS-DISP',
    'revaluation_reserve' => '2500-REVAL-RES',
],
```

## Event Catalog

### Published Events

- `DepreciationCalculatedEvent` - Single depreciation calculation complete
- `DepreciationPostedEvent` - Journal entry posted to GL
- `DepreciationReversedEvent` - Depreciation reversed
- `AssetRevaluedEvent` - Asset revaluation complete
- `DepreciationScheduleAdjustedEvent` - Schedule modified
- `DepreciationRunCompletedEvent` - Batch depreciation run complete

### Subscribed Events

- `Nexus\Assets\Events\AssetAcquiredEvent` - Generate initial depreciation schedule
- `Nexus\Assets\Events\AssetDisposedEvent` - Close depreciation schedule
- `Nexus\Period\Events\PeriodClosedEvent` - Process period-end depreciation

## Requirements

This package implements comprehensive requirements covering:

- **Architectural Requirements**: Framework-agnostic, interfaces, immutability
- **Business Requirements**: Depreciation methods, schedules, revaluation
- **Functional Requirements**: CRUD, calculations, reporting
- **Integration Requirements**: External package interfaces
- **Security Requirements**: Tenant isolation, authorization
- **Performance Requirements**: Batch processing, forecasting

For detailed requirements, see [REQUIREMENTS.md](REQUIREMENTS.md).

## Architecture (see ARCHITECTURE.md)

For detailed architecture documentation, see [ARCHITECTURE.md](ARCHITECTURE.md) which covers:

- Package overview and scope
- Architecture principles (framework-agnostic, DI, CQRS, immutability)
- Directory structure
- Entity relationships
- Service orchestration
- Integration patterns
- Progressive delivery tiers

## Development

### Testing

```bash
# Package tests (framework-agnostic)
vendor/bin/phpunit packages/FixedAssetDepreciation/tests

# Atomy integration tests
php artisan test --filter FixedAssetDepreciation
```

### Code Style

This package follows strict coding standards:

- PHP 8.3+ with strict typing
- `declare(strict_types=1)` in all files
- Readonly properties for immutability
- Constructor property promotion
- PSR-12 coding standards
- Comprehensive docblocks

## Documentation

### Package Documentation

- **[Architecture](ARCHITECTURE.md)** - Detailed architecture documentation
- **[Requirements](REQUIREMENTS.md)** - Complete requirements traceability
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide
- **[API Reference](docs/api-reference.md)** - Complete API documentation

### Additional Resources

- `docs/examples/` - Usage examples
- See root `ARCHITECTURE.md` for overall system architecture
- See `plans/FINANCE_ECOSYSTEM_EXECUTION_PLAN.md` for finance ecosystem context

## License

MIT License - See [LICENSE](LICENSE) file for details.

## Support

For questions or issues, please refer to the main Nexus documentation or contact the development team.
