# Nexus\Assets

Framework-agnostic Fixed Asset Management with Progressive Delivery for Small Business to Large Enterprise.

## Overview

The **Nexus\Assets** package provides comprehensive fixed asset lifecycle management from acquisition through disposal, with automated depreciation calculation and GL integration. The package implements a **progressive delivery model** with three tiers of complexity, allowing the same codebase to serve small businesses with simple tracking needs and large enterprises requiring full compliance features.

## Progressive Feature Tiers

| Tier | Target | Key Features |
|------|--------|--------------|
| **Tier 1: Basic** | Small Business (SB) | Simple tracking (ID, Cost, User assignment), Straight-Line depreciation only, Basic disposal logging |
| **Tier 2: Advanced** | Medium Business (MB) | Double Declining Balance depreciation, Maintenance/Warranty tracking, Multi-location inventory, TCO analysis |
| **Tier 3: Enterprise** | Large Enterprise (LE) | Units-of-Production depreciation, Physical audit features, Multi-currency support, Automatic GL posting |

## Key Features

### Core (All Tiers)
- **Asset Lifecycle Management**: Acquisition → Active → Disposal workflow
- **Straight-Line Depreciation**: Daily prorating for mid-month acquisitions (GAAP-compliant)
- **Asset Tagging**: Sequential numbering via `Nexus\Sequencing`
- **Simple Assignment**: User-based custody tracking
- **Audit Trail**: All lifecycle events logged via `Nexus\Audit`

### Tier 2 (Advanced)
- **Double Declining Balance Depreciation**: Accelerated depreciation method
- **Maintenance Tracking**: Service records with cost and downtime analysis
- **Warranty Management**: Vendor warranty tracking with expiry alerts
- **Location Tracking**: Integration with `Nexus\Inventory\LocationInterface`
- **TCO Analysis**: Total Cost of Ownership calculation and replacement recommendations

### Tier 3 (Enterprise)
- **Units-of-Production Depreciation**: Usage-based depreciation with UOM integration
- **Automatic GL Posting**: Event-driven journal entries via `Nexus\Finance`
- **Physical Audits**: Scheduled verification with discrepancy tracking
- **Multi-Currency Assets**: Foreign currency acquisition with functional currency reporting
- **Barcode/QR Generation**: Printable asset tags using `Nexus\Product\Barcode`

## Installation

### 1. Install Package

```bash
composer require nexus/assets:*@dev
```

### 2. Configure Tier

Set the tier for your tenant in application settings:

```php
// Via Nexus\Setting
$settings->setString('assets.tier', 'basic'); // or 'advanced', 'enterprise'
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Bind Interfaces

```php
// app/Providers/AssetServiceProvider.php
public function register(): void
{
    // Bind repositories
    $this->app->singleton(AssetRepositoryInterface::class, DbAssetRepository::class);
    $this->app->singleton(AssetCategoryRepositoryInterface::class, DbAssetCategoryRepository::class);
    $this->app->singleton(DepreciationRecordRepositoryInterface::class, DbDepreciationRecordRepository::class);
    
    // Tier 2+ bindings
    if ($this->isTierAdvancedOrHigher()) {
        $this->app->singleton(MaintenanceRecordRepositoryInterface::class, DbMaintenanceRecordRepository::class);
        $this->app->singleton(WarrantyRecordRepositoryInterface::class, DbWarrantyRecordRepository::class);
        $this->app->singleton(MaintenanceAnalyzerInterface::class, MaintenanceAnalyzer::class);
    }
    
    // Tier 3 bindings
    if ($this->isTierEnterprise()) {
        $this->app->singleton(AssetVerifierInterface::class, AssetVerifier::class);
    }
}
```

## Usage

### Tier 1: Basic Asset Management

```php
use Nexus\Assets\Services\AssetManager;
use Nexus\Assets\Enums\DepreciationMethod;

$assetManager = app(AssetManager::class);

// Acquire an asset
$asset = $assetManager->acquireAsset([
    'category_id' => '01JCXA...',
    'description' => 'Pressure Washer',
    'acquisition_cost' => 10000.00,
    'acquisition_date' => new \DateTimeImmutable('2025-01-15'),
    'depreciation_method' => DepreciationMethod::STRAIGHT_LINE,
    'useful_life_years' => 3,
    'salvage_value' => 1000.00,
    'location' => 'Warehouse 1',
]);

// Assign to user
$assetManager->assignAsset($asset->getId(), 'user-123');

// Dispose of asset
$gainLoss = $assetManager->disposeAsset(
    $asset->getId(),
    DisposalMethod::SALE,
    saleProceeds: 5000.00
);
```

### Tier 2: Advanced Features

```php
// Track warranty
$assetManager->withWarranty(new WarrantyRecord(
    vendorId: 'vendor-456',
    expiryDate: new \DateTimeImmutable('+2 years'),
    coverageDetails: 'Full parts and labor',
));

// Track maintenance
$assetManager->trackMaintenance(new MaintenanceRecord(
    assetId: $asset->getId(),
    type: MaintenanceType::SCHEDULED,
    cost: 500.00,
    startTime: new \DateTimeImmutable('2025-06-01 08:00'),
    endTime: new \DateTimeImmutable('2025-06-01 12:00'),
));

// Analyze TCO
$analyzer = app(MaintenanceAnalyzerInterface::class);
$tco = $analyzer->calculateTotalCostOfOwnership($asset->getId());
$shouldReplace = $analyzer->recommendReplacement($asset->getId());
```

### Tier 3: Enterprise Features

```php
// Acquire with automatic GL posting
$asset = $assetManager
    ->withLedgerPost()
    ->acquireAsset([...]);

// Schedule physical audit
$assetManager->schedulePhysicalAudit(
    $asset->getId(),
    new \DateTimeImmutable('+6 months')
);

// Generate barcode for physical tagging
$barcodeUrl = $assetManager->generateBarcodeDataUrl($asset->getId());
```

## Depreciation Methods

### Straight-Line (Tier 1)

Formula: `(Cost - Salvage) / Useful Life`

Daily prorating for mid-month acquisitions:
```
Annual Depreciation = (10,000 - 1,000) / 3 = 3,000/year
Monthly = 3,000 / 12 = 250/month
Daily Proration = 250 × (daysOwned / daysInMonth)
```

### Double Declining Balance (Tier 2)

Formula: `Rate × Beginning Book Value` where `Rate = 2 / Useful Life`

Example for 3-year asset:
- Year 1: 10,000 × (2/3) = 6,667
- Year 2: 3,333 × (2/3) = 2,222
- Year 3: 1,111 → 111 (stop at salvage value)

### Units of Production (Tier 3)

Formula: `(Cost - Salvage) × (Units Consumed / Total Expected Units)`

Example for vehicle with 100,000 km life:
```php
$depreciation = $engine->calculateUnits($asset, unitsConsumed: 10000);
// Returns 10% of depreciable amount
```

## Automated Period-End Processing

Schedule monthly depreciation via `Nexus\Scheduler`:

```php
// Automatically scheduled via cron: 0 0 L * * (last day of month)
// Processes all active assets and posts depreciation entries
```

## Integration Points

### Required Dependencies
- **Nexus\Finance**: GL journal entry posting (Tier 3)
- **Nexus\Period**: Fiscal period validation
- **Nexus\Audit**: Comprehensive audit logging
- **Nexus\Setting**: Per-tenant tier configuration
- **Nexus\Sequencing**: Asset tag auto-numbering

### Optional Dependencies
- **Nexus\Inventory**: LocationInterface for advanced tracking (Tier 2+)
- **Nexus\Product**: Barcode generation (Tier 3)
- **Nexus\Workflow**: Disposal approval workflows (Tier 3)
- **Nexus\Notifier**: Warranty expiry alerts (Tier 2+)

## Configuration

```php
// config/assets.php
return [
    'tier' => env('ASSETS_TIER', 'basic'), // basic|advanced|enterprise
    'enable_gl_posting' => env('ASSETS_GL_POSTING', false), // Tier 3
    'enable_physical_audits' => env('ASSETS_PHYSICAL_AUDITS', false), // Tier 3
    'asset_tag_format' => env('ASSETS_TAG_FORMAT', 'sequential'), // sequential|uuid|barcode
    'depreciation_proration' => env('ASSETS_DEPRECIATION_PRORATION', 'daily'), // daily|full_month
];
```

## Events

- **AssetAcquiredEvent** (HIGH severity): Published when asset is acquired
- **DepreciationRecordedEvent** (MEDIUM severity): Published after depreciation calculation
- **AssetDisposedEvent** (CRITICAL severity): Published when asset is disposed
- **AssetDepreciatedEvent** (MEDIUM severity): Batch event for monthly depreciation run
- **PhysicalAuditFailedEvent** (HIGH severity, Tier 3): Asset verification failure

## Performance

- Asset acquisition: < 100ms (p95)
- Depreciation calculation: < 50ms per asset
- Batch depreciation (1000 assets): < 60 seconds
- TCO analysis: < 200ms with 100 maintenance records

---

## Documentation

### Quick Links
- **[Getting Started](docs/getting-started.md)** - Installation and first asset creation
- **[API Reference](docs/api-reference.md)** - Complete API documentation
- **[Integration Guide](docs/integration-guide.md)** - Laravel & Symfony integration
- **[Examples](docs/examples/)** - Working code examples

### Package Documentation
- **[Requirements](REQUIREMENTS.md)** - Comprehensive requirements (147 total)
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Development progress and metrics
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - Test plan and coverage (93 tests planned)
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package valuation ($375,000)

### Additional Resources
- **Tier Upgrade Guide:** See root `docs/ASSETS_TIER_UPGRADE_GUIDE.md`
- **Architecture Overview:** See root `ARCHITECTURE.md`
- **Compliance Standards:** GAAP/IFRS-compliant depreciation methods

---

## License

MIT License - See LICENSE file for details

## Support

For issues, questions, or feature requests, please contact the Nexus development team.
