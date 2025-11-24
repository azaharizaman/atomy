# Getting Started with Nexus\Assets

**Framework-agnostic fixed asset management with progressive delivery**

---

## Overview

Nexus\Assets is a comprehensive fixed asset management package that supports the complete asset lifecycle from acquisition through depreciation to disposal. The package uses a **progressive delivery model** with three tiers:

- **Tier 1 (Basic):** Simple asset tracking with straight-line depreciation
- **Tier 2 (Advanced):** Adds accelerated depreciation (DDB), maintenance/warranty tracking, TCO analysis
- **Tier 3 (Enterprise):** Adds units of production depreciation, GL posting, physical audits, multi-currency

---

## Prerequisites

- PHP 8.3 or higher
- Composer

---

## Installation

```bash
composer require nexus/assets:"*@dev"
```

---

## Basic Configuration

### Step 1: Choose Your Tier

Set your tier in the consuming application's configuration (e.g., `.env` for Laravel):

```env
# Options: basic, advanced, enterprise
ASSETS_TIER=basic
```

### Step 2: Implement Required Interfaces

The package defines **what** it needs via interfaces. Your consuming application provides the **how**.

#### Minimum Required Implementations:

1. **AssetRepositoryInterface** - Data persistence
2. **AssetCategoryRepositoryInterface** - Category management
3. **SettingsManagerInterface** - Tier configuration (from Nexus\Setting)

**Example Repository Implementation (Laravel):**

```php
<?php

namespace App\Repositories;

use App\Models\Asset;
use Nexus\Assets\Contracts\AssetInterface;
use Nexus\Assets\Contracts\AssetRepositoryInterface;
use Nexus\Assets\Exceptions\AssetNotFoundException;

final readonly class AssetRepository implements AssetRepositoryInterface
{
    public function create(array $data): AssetInterface
    {
        return Asset::create($data);
    }

    public function findById(string $id): AssetInterface
    {
        $asset = Asset::find($id);
        if (!$asset) {
            throw AssetNotFoundException::withId($id);
        }
        return $asset;
    }

    public function findAll(array $filters = []): array
    {
        $query = Asset::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category_ids'])) {
            $query->whereIn('category_id', $filters['category_ids']);
        }

        return $query->get()->all();
    }

    // ... implement remaining methods
}
```

### Step 3: Bind Implementations to Interfaces

**Laravel Service Provider:**

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Assets\Contracts\AssetManagerInterface;
use Nexus\Assets\Contracts\AssetRepositoryInterface;
use Nexus\Assets\Services\AssetManager;
use App\Repositories\AssetRepository;

class AssetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            AssetRepositoryInterface::class,
            AssetRepository::class
        );

        // Bind manager
        $this->app->singleton(
            AssetManagerInterface::class,
            AssetManager::class
        );
    }
}
```

**Register in `config/app.php`:**

```php
'providers' => [
    // ...
    App\Providers\AssetServiceProvider::class,
],
```

---

## Your First Asset (Tier 1 - Basic)

### 1. Create an Asset

```php
<?php

use Nexus\Assets\Contracts\AssetManagerInterface;
use Nexus\Assets\Enums\DepreciationMethod;

// Inject the manager
public function __construct(
    private readonly AssetManagerInterface $assetManager
) {}

public function createOfficeDesk(): void
{
    $asset = $this->assetManager->createAsset([
        'name' => 'Office Desk - Executive',
        'cost' => 1200.00,
        'salvage_value' => 100.00,
        'acquisition_date' => new \DateTimeImmutable('2025-01-15'),
        'depreciation_method' => DepreciationMethod::STRAIGHT_LINE,
        'useful_life_months' => 60, // 5 years
        'location' => 'HQ Office, Floor 3',
        'category_id' => 'furniture',
    ]);

    echo "Asset created: {$asset->getAssetTag()}\n";
    // Output: Asset created: AST-000001
}
```

### 2. Record Monthly Depreciation

```php
use Nexus\Assets\Contracts\AssetManagerInterface;

public function recordDepreciation(string $assetId): void
{
    $record = $this->assetManager->recordDepreciation(
        id: $assetId,
        periodStart: new \DateTimeImmutable('2025-01-01'),
        periodEnd: new \DateTimeImmutable('2025-01-31')
    );

    echo "Depreciation recorded: {$record->getAmount()}\n";
    echo "Net Book Value: {$record->getNetBookValue()}\n";
}
```

### 3. Dispose of an Asset

```php
use Nexus\Assets\Enums\DisposalMethod;

public function disposeAsset(string $assetId): void
{
    $disposal = $this->assetManager->disposeAsset(
        id: $assetId,
        method: DisposalMethod::SALE,
        disposalDate: new \DateTimeImmutable('2026-06-30'),
        proceeds: 800.00,
        notes: 'Sold to employee at book value'
    );

    echo "Gain/Loss: {$disposal['gain_loss']}\n";
    // Output: Gain/Loss: 50.00 (if NBV was $750)
}
```

---

## Tier 2 (Advanced) Features

### 1. Add Warranty Information

```php
$asset = $this->assetManager->createAsset([
    // ... basic fields ...
    'depreciation_method' => DepreciationMethod::DOUBLE_DECLINING_BALANCE,
])->withWarranty(
    provider: 'Dell Inc.',
    startDate: new \DateTimeImmutable('2025-01-15'),
    expiryDate: new \DateTimeImmutable('2028-01-15'),
    coverageType: 'Full parts and labor'
);
```

### 2. Track Maintenance and Calculate TCO

```php
use Nexus\Assets\Contracts\MaintenanceAnalyzerInterface;
use Nexus\Assets\Enums\MaintenanceType;

public function __construct(
    private readonly AssetManagerInterface $assetManager,
    private readonly MaintenanceAnalyzerInterface $maintenanceAnalyzer
) {}

public function recordMaintenance(string $assetId): void
{
    // Record maintenance
    $this->assetManager->withMaintenance(
        assetId: $assetId,
        type: MaintenanceType::PREVENTIVE,
        description: 'Annual service and cleaning',
        cost: 150.00,
        performedDate: new \DateTimeImmutable()
    );

    // Calculate Total Cost of Ownership
    $tco = $this->maintenanceAnalyzer->calculateTCO(
        assetId: $assetId,
        projectedYears: 5
    );

    echo "Total Cost of Ownership (5 years): {$tco['total']}\n";
    echo "  - Acquisition Cost: {$tco['acquisition_cost']}\n";
    echo "  - Maintenance Cost: {$tco['maintenance_cost']}\n";
    echo "  - Projected Maintenance: {$tco['projected_maintenance']}\n";
}
```

### 3. Predictive Maintenance

```php
public function predictMaintenance(string $assetId): void
{
    $prediction = $this->maintenanceAnalyzer->predictNextMaintenance($assetId);

    echo "Next maintenance recommended: {$prediction['predicted_date']->format('Y-m-d')}\n";
    echo "Based on average interval: {$prediction['average_interval_days']} days\n";
}
```

---

## Tier 3 (Enterprise) Features

### 1. Units of Production Depreciation

```php
use Nexus\Assets\Enums\DepreciationMethod;

$asset = $this->assetManager->createAsset([
    'name' => 'Industrial Printer',
    'cost' => 50000.00,
    'salvage_value' => 5000.00,
    'acquisition_date' => new \DateTimeImmutable('2025-01-01'),
    'depreciation_method' => DepreciationMethod::UNITS_OF_PRODUCTION,
    'useful_life_months' => 60,
    'total_expected_units' => 1000000, // 1 million prints
    'unit_type' => 'prints',
    'location_id' => 'WAREHOUSE-A', // FK to locations table
    'currency_code' => 'MYR',
]);

// Record depreciation based on actual usage
$record = $this->assetManager->recordDepreciation(
    id: $asset->getId(),
    periodStart: new \DateTimeImmutable('2025-01-01'),
    periodEnd: new \DateTimeImmutable('2025-01-31'),
    unitsConsumed: 50000 // 50,000 prints this month
);
```

### 2. Physical Asset Audits

```php
use Nexus\Assets\Contracts\AssetVerifierInterface;

public function __construct(
    private readonly AssetVerifierInterface $assetVerifier
) {}

public function conductPhysicalAudit(): void
{
    // Initiate audit for specific location
    $audit = $this->assetVerifier->initiatePhysicalAudit([
        'location_ids' => ['WAREHOUSE-A', 'WAREHOUSE-B'],
        'scheduled_date' => new \DateTimeImmutable('2025-12-31'),
    ]);

    echo "Audit ID: {$audit->getId()}\n";
    echo "Assets to verify: {$audit->getTotalAssetsExpected()}\n";

    // Record physical verification
    $this->assetVerifier->recordPhysicalVerification(
        auditId: $audit->getId(),
        assetTag: 'AST-000123',
        condition: 'Good',
        actualLocation: 'WAREHOUSE-A',
        notes: 'Asset found and verified'
    );

    // Complete audit
    $results = $this->assetVerifier->completePhysicalAudit($audit->getId());

    echo "Audit Results:\n";
    echo "  - Assets Verified: {$results['verified_count']}\n";
    echo "  - Missing Assets: {$results['missing_count']}\n";
    echo "  - Accuracy Rate: {$results['accuracy_rate']}%\n";
}
```

### 3. Automatic GL Posting

When an asset is disposed (Tier 3 with `auto_gl_posting` enabled), an event is published with GL posting data:

```php
// AssetDisposedEvent is automatically published
// Your application listens and posts to GL

namespace App\Listeners;

use Nexus\Assets\Events\AssetDisposedEvent;
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;

class AssetGLListener
{
    public function __construct(
        private readonly GeneralLedgerManagerInterface $ledger
    ) {}

    public function handleDisposal(AssetDisposedEvent $event): void
    {
        if (!$event->shouldPostToGL) {
            return;
        }

        $lines = [
            // DR: Accumulated Depreciation
            ['account' => '1500', 'debit' => $event->accumulatedDepreciation],
            // DR: Cash
            ['account' => '1000', 'debit' => $event->proceeds],
            // CR: Asset Cost
            ['account' => '1400', 'credit' => $event->originalCost],
        ];

        // Add gain/loss line
        if ($event->gainLoss != 0) {
            $lines[] = $event->gainLoss > 0
                ? ['account' => '8100', 'credit' => abs($event->gainLoss)] // Gain
                : ['account' => '8200', 'debit' => abs($event->gainLoss)];  // Loss
        }

        $this->ledger->createJournalEntry([
            'date' => $event->disposalDate,
            'reference' => "DISP-{$event->assetTag}",
            'description' => "Disposal of asset {$event->assetTag}",
            'lines' => $lines,
        ]);
    }
}
```

---

## Batch Depreciation Processing

For monthly automated depreciation runs:

```php
use Nexus\Assets\Services\DepreciationScheduler;

public function __construct(
    private readonly DepreciationScheduler $scheduler
) {}

public function runMonthlyDepreciation(): void
{
    $summary = $this->scheduler->runMonthlyDepreciation(
        periodStart: new \DateTimeImmutable('2025-01-01'),
        periodEnd: new \DateTimeImmutable('2025-01-31'),
        filters: [
            'category_ids' => ['vehicles', 'computers'],
            // Optional: 'location_ids' => ['HQ'],
            // Optional: 'ids' => ['asset-id-1', 'asset-id-2'],
        ]
    );

    echo "Depreciation Summary:\n";
    echo "  - Assets Processed: {$summary['processed_count']}\n";
    echo "  - Total Depreciation: {$summary['total_depreciation']}\n";
    echo "  - Skipped (fully depreciated): {$summary['skipped_count']}\n";
}
```

---

## Integration with Nexus\Scheduler

For fully automated monthly depreciation:

**1. Register Job Handler in Service Provider:**

```php
use Nexus\Assets\Integration\DepreciationJobHandler;

public function register(): void
{
    $this->app->tag([
        DepreciationJobHandler::class,
    ], 'job_handlers');
}
```

**2. Schedule in consuming application:**

```php
use Nexus\Scheduler\Contracts\SchedulerManagerInterface;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\Enums\JobRecurrence;

public function scheduleMonthlyDepreciation(): void
{
    $this->scheduler->createSchedule([
        'job_type' => JobType::ASSET_DEPRECIATION,
        'recurrence_type' => JobRecurrence::MONTHLY,
        'scheduled_day' => 1, // First day of each month
        'scheduled_time' => '02:00', // 2 AM
        'is_active' => true,
    ]);
}
```

---

## Next Steps

1. **API Reference:** See [`docs/api-reference.md`](api-reference.md) for complete interface documentation
2. **Integration Guide:** See [`docs/integration-guide.md`](integration-guide.md) for Laravel/Symfony examples
3. **Examples:** See [`docs/examples/`](examples/) for working code samples
4. **Tier Upgrade Guide:** See root `docs/ASSETS_TIER_UPGRADE_GUIDE.md` for migration between tiers

---

## Common Questions

### Q: Can I use multiple depreciation methods in the same deployment?
**A:** Yes! Each asset can have a different depreciation method. The tier restriction applies to which methods are *available*, not how many you can use.

### Q: What happens if I try to use a Tier 3 feature on Tier 1?
**A:** The package will throw `UnsupportedDepreciationMethodException` or similar tier-specific exceptions. Always check tier configuration before enabling advanced features.

### Q: Can I switch an asset's depreciation method after creation?
**A:** No. Depreciation method is immutable once set. This is a GAAP/IFRS compliance requirement. You must dispose the old asset and create a new one.

### Q: How does DDB auto-switch work?
**A:** The Double Declining Balance engine automatically calculates when switching to straight-line depreciation would result in more depreciation. This typically happens in the final 1-2 years of the asset's useful life.

---

**Next:** [API Reference →](api-reference.md)
