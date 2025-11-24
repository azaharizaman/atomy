# Integration Guide: Assets

**Complete Laravel and Symfony integration examples for Nexus\Assets**

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Common Patterns](#common-patterns)
4. [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/assets:"*@dev"
```

### Step 2: Create Eloquent Models

**File:** `app/Models/Asset.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Assets\Contracts\AssetInterface;
use Nexus\Assets\Enums\AssetStatus;
use Nexus\Assets\Enums\DepreciationMethod;

class Asset extends Model implements AssetInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'asset_tag', 'name', 'description', 'category_id',
        'cost', 'salvage_value', 'accumulated_depreciation',
        'acquisition_date', 'depreciation_method', 'useful_life_months',
        'total_expected_units', 'unit_type',
        'location', 'location_id',
        'status', 'disposal_date', 'disposal_method',
        'disposal_proceeds', 'disposal_notes',
        'warranty_expiry', 'warranty_provider', 'currency_code',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'total_expected_units' => 'decimal:2',
        'disposal_proceeds' => 'decimal:2',
        'acquisition_date' => 'date',
        'disposal_date' => 'date',
        'warranty_expiry' => 'date',
        'status' => AssetStatus::class,
        'depreciation_method' => DepreciationMethod::class,
    ];

    // AssetInterface implementation
    public function getId(): string { return $this->id; }
    public function getAssetTag(): string { return $this->asset_tag; }
    public function getName(): string { return $this->name; }
    public function getCost(): float { return (float) $this->cost; }
    public function getSalvageValue(): float { return (float) $this->salvage_value; }
    public function getAccumulatedDepreciation(): float { return (float) $this->accumulated_depreciation; }
    public function getAcquisitionDate(): \DateTimeImmutable { 
        return new \DateTimeImmutable($this->acquisition_date->format('Y-m-d')); 
    }
    public function getDepreciationMethod(): DepreciationMethod { return $this->depreciation_method; }
    public function getUsefulLifeMonths(): int { return $this->useful_life_months; }
    public function getTotalExpectedUnits(): ?float { 
        return $this->total_expected_units !== null ? (float) $this->total_expected_units : null; 
    }
    public function getStatus(): AssetStatus { return $this->status; }
    
    // Tier-aware location getter
    public function getLocation(): string|object
    {
        $tier = config('assets.tier', 'basic');
        
        if ($tier === 'basic') {
            return $this->location ?? 'Unknown';
        }
        
        return $this->locationRelation ?? (object)[
            'id' => $this->location_id,
            'name' => $this->location
        ];
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function depreciationRecords()
    {
        return $this->hasMany(DepreciationRecord::class);
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class);
    }
}
```

### Step 3: Create Repository Implementation

**File:** `app/Repositories/AssetRepository.php`

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

    public function update(string $id, array $data): AssetInterface
    {
        $asset = $this->findById($id);
        $asset->update($data);
        return $asset->fresh();
    }

    public function findById(string $id): AssetInterface
    {
        $asset = Asset::find($id);
        if (!$asset) {
            throw AssetNotFoundException::withId($id);
        }
        return $asset;
    }

    public function findByAssetTag(string $tag): ?AssetInterface
    {
        return Asset::where('asset_tag', $tag)->first();
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

        if (isset($filters['location_ids'])) {
            $query->whereIn('location_id', $filters['location_ids']);
        }

        if (isset($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        return $query->get()->all();
    }

    public function getNextSequence(): int
    {
        return Asset::count() + 1;
    }

    public function getMaintenanceRecords(string $assetId): array
    {
        return Asset::findOrFail($assetId)->maintenanceRecords()->get()->all();
    }
}
```

### Step 4: Create Service Provider

**File:** `app/Providers/AssetServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Assets\Contracts\AssetManagerInterface;
use Nexus\Assets\Contracts\AssetRepositoryInterface;
use Nexus\Assets\Contracts\MaintenanceAnalyzerInterface;
use Nexus\Assets\Contracts\AssetVerifierInterface;
use Nexus\Assets\Services\AssetManager;
use Nexus\Assets\Services\MaintenanceAnalyzer;
use Nexus\Assets\Services\AssetVerifier;
use App\Repositories\AssetRepository;

class AssetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Always bind repository
        $this->app->singleton(AssetRepositoryInterface::class, AssetRepository::class);

        // Always bind core manager
        $this->app->singleton(AssetManagerInterface::class, AssetManager::class);

        // Tier 2+: Maintenance analyzer
        $this->app->singleton(MaintenanceAnalyzerInterface::class, function ($app) {
            $tier = config('assets.tier', 'basic');
            if (in_array($tier, ['advanced', 'enterprise'])) {
                return $app->make(MaintenanceAnalyzer::class);
            }
            throw new \LogicException('MaintenanceAnalyzer requires tier: advanced or enterprise');
        });

        // Tier 3: Asset verifier
        $this->app->singleton(AssetVerifierInterface::class, function ($app) {
            $tier = config('assets.tier', 'basic');
            if ($tier === 'enterprise') {
                return $app->make(AssetVerifier::class);
            }
            throw new \LogicException('AssetVerifier requires tier: enterprise');
        });

        // Register job handler for Scheduler
        $this->app->tag([
            \Nexus\Assets\Integration\DepreciationJobHandler::class,
        ], 'job_handlers');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/assets.php' => config_path('assets.php'),
            ], 'assets-config');
        }
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

### Step 5: Create Configuration File

**File:** `config/assets.php`

```php
<?php

return [
    'tier' => env('ASSETS_TIER', 'basic'),

    'depreciation' => [
        'full_month_convention' => env('ASSETS_FULL_MONTH', false),
        'auto_gl_posting' => env('ASSETS_AUTO_GL_POST', true),
    ],

    'asset_tag' => [
        'prefix' => env('ASSETS_TAG_PREFIX', 'AST'),
        'length' => env('ASSETS_TAG_LENGTH', 6),
    ],

    'audit' => [
        'accuracy_threshold' => 95.0,
    ],
];
```

### Step 6: Create Migration

**File:** `database/migrations/2025_11_24_000000_create_assets_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('asset_tag')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->ulid('category_id')->nullable();
            
            $table->decimal('cost', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            
            $table->date('acquisition_date');
            $table->string('depreciation_method');
            $table->integer('useful_life_months');
            
            // Tier 3: UOP
            $table->decimal('total_expected_units', 15, 2)->nullable();
            $table->string('unit_type')->nullable();
            
            // Location (hybrid: string for Tier 1, FK for Tier 3)
            $table->string('location')->nullable();
            $table->ulid('location_id')->nullable();
            
            $table->string('status')->default('active');
            
            // Disposal
            $table->date('disposal_date')->nullable();
            $table->string('disposal_method')->nullable();
            $table->decimal('disposal_proceeds', 15, 2)->nullable();
            $table->text('disposal_notes')->nullable();
            
            // Tier 2: Warranty
            $table->date('warranty_expiry')->nullable();
            $table->string('warranty_provider')->nullable();
            
            // Tier 3: Multi-currency
            $table->string('currency_code', 3)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'depreciation_method']);
            $table->index('acquisition_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
```

### Step 7: Create GL Listener (Tier 3)

**File:** `app/Listeners/AssetGLListener.php`

```php
<?php

namespace App\Listeners;

use Nexus\Assets\Events\AssetDisposedEvent;
use Nexus\Assets\Events\DepreciationRecordedEvent;
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

class AssetGLListener implements ShouldQueue
{
    public function __construct(
        private readonly GeneralLedgerManagerInterface $ledger
    ) {}

    public function handleDepreciation(DepreciationRecordedEvent $event): void
    {
        if (config('assets.tier') !== 'enterprise') {
            return;
        }

        $this->ledger->createJournalEntry([
            'date' => $event->periodEnd,
            'reference' => "DEP-{$event->assetId}",
            'description' => "Depreciation expense",
            'lines' => [
                ['account' => '7200', 'debit' => $event->depreciationAmount],
                ['account' => '1500', 'credit' => $event->depreciationAmount],
            ],
        ]);
    }

    public function handleDisposal(AssetDisposedEvent $event): void
    {
        if (!$event->shouldPostToGL) {
            return;
        }

        // Journal entry for disposal
        $lines = [
            ['account' => '1500', 'debit' => $event->accumulatedDepreciation],
            ['account' => '1000', 'debit' => $event->proceeds],
            ['account' => '1400', 'credit' => $event->originalCost],
        ];

        if ($event->gainLoss != 0) {
            $lines[] = $event->gainLoss > 0
                ? ['account' => '8100', 'credit' => abs($event->gainLoss)]
                : ['account' => '8200', 'debit' => abs($event->gainLoss)];
        }

        $this->ledger->createJournalEntry([
            'date' => $event->disposalDate,
            'reference' => "DISP-{$event->assetTag}",
            'lines' => $lines,
        ]);
    }

    public function subscribe($events): array
    {
        return [
            DepreciationRecordedEvent::class => 'handleDepreciation',
            AssetDisposedEvent::class => 'handleDisposal',
        ];
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/assets:"*@dev"
```

### Step 2: Create Doctrine Entity

**File:** `src/Entity/Asset.php`

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Assets\Contracts\AssetInterface;
use Nexus\Assets\Enums\AssetStatus;
use Nexus\Assets\Enums\DepreciationMethod;

#[ORM\Entity]
#[ORM\Table(name: 'assets')]
class Asset implements AssetInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private string $id;

    #[ORM\Column(type: 'string', unique: true)]
    private string $assetTag;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private float $cost;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private float $salvageValue = 0.0;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private float $accumulatedDepreciation = 0.0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $acquisitionDate;

    #[ORM\Column(type: 'string', enumType: DepreciationMethod::class)]
    private DepreciationMethod $depreciationMethod;

    #[ORM\Column(type: 'integer')]
    private int $usefulLifeMonths;

    #[ORM\Column(type: 'string', enumType: AssetStatus::class)]
    private AssetStatus $status = AssetStatus::ACTIVE;

    // Getters implementing AssetInterface
    public function getId(): string { return $this->id; }
    public function getAssetTag(): string { return $this->assetTag; }
    public function getName(): string { return $this->name; }
    public function getCost(): float { return $this->cost; }
    public function getSalvageValue(): float { return $this->salvageValue; }
    public function getAccumulatedDepreciation(): float { return $this->accumulatedDepreciation; }
    public function getAcquisitionDate(): \DateTimeImmutable { return $this->acquisitionDate; }
    public function getDepreciationMethod(): DepreciationMethod { return $this->depreciationMethod; }
    public function getUsefulLifeMonths(): int { return $this->usefulLifeMonths; }
    public function getStatus(): AssetStatus { return $this->status; }
}
```

### Step 3: Create Repository

**File:** `src/Repository/AssetRepository.php`

```php
<?php

namespace App\Repository;

use App\Entity\Asset;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\Assets\Contracts\AssetInterface;
use Nexus\Assets\Contracts\AssetRepositoryInterface;
use Nexus\Assets\Exceptions\AssetNotFoundException;

final readonly class AssetRepository implements AssetRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function create(array $data): AssetInterface
    {
        $asset = new Asset();
        // ... populate from $data
        $this->entityManager->persist($asset);
        $this->entityManager->flush();
        return $asset;
    }

    public function findById(string $id): AssetInterface
    {
        $asset = $this->entityManager->find(Asset::class, $id);
        if (!$asset) {
            throw AssetNotFoundException::withId($id);
        }
        return $asset;
    }

    // ... implement remaining methods
}
```

### Step 4: Configure Services

**File:** `config/services.yaml`

```yaml
services:
    # Repositories
    Nexus\Assets\Contracts\AssetRepositoryInterface:
        class: App\Repository\AssetRepository
        arguments: ['@doctrine.orm.entity_manager']

    # Services
    Nexus\Assets\Contracts\AssetManagerInterface:
        class: Nexus\Assets\Services\AssetManager
        arguments:
            - '@Nexus\Assets\Contracts\AssetRepositoryInterface'
            - '@Nexus\Assets\Contracts\DepreciationEngineInterface'
            - '@Psr\EventDispatcher\EventDispatcherInterface'

    # Tier 2+ services (conditional)
    Nexus\Assets\Contracts\MaintenanceAnalyzerInterface:
        class: Nexus\Assets\Services\MaintenanceAnalyzer
        arguments:
            - '@Nexus\Assets\Contracts\AssetRepositoryInterface'
```

---

## Common Patterns

### Pattern 1: Controller Integration (Laravel)

```php
<?php

namespace App\Http\Controllers;

use Nexus\Assets\Contracts\AssetManagerInterface;
use Nexus\Assets\Enums\DepreciationMethod;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(
        private readonly AssetManagerInterface $assetManager
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'cost' => 'required|numeric|min:0.01',
            'salvage_value' => 'required|numeric|min:0',
            'acquisition_date' => 'required|date',
            'useful_life_months' => 'required|integer|min:1',
            'depreciation_method' => 'required|string',
        ]);

        $asset = $this->assetManager->createAsset([
            ...$validated,
            'depreciation_method' => DepreciationMethod::from($validated['depreciation_method']),
            'acquisition_date' => new \DateTimeImmutable($validated['acquisition_date']),
        ]);

        return response()->json($asset, 201);
    }
}
```

### Pattern 2: Scheduled Job (Laravel)

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Assets\Services\DepreciationScheduler;

class DepreciateAssetsCommand extends Command
{
    protected $signature = 'assets:depreciate {--month=} {--year=}';

    public function __construct(
        private readonly DepreciationScheduler $scheduler
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $month = $this->option('month') ?? date('m');
        $year = $this->option('year') ?? date('Y');

        $periodStart = new \DateTimeImmutable("{$year}-{$month}-01");
        $periodEnd = $periodStart->modify('last day of this month');

        $summary = $this->scheduler->runMonthlyDepreciation($periodStart, $periodEnd);

        $this->info("Processed {$summary['processed_count']} assets");
        $this->info("Total depreciation: {$summary['total_depreciation']}");
    }
}
```

---

## Troubleshooting

### Issue: "UnsupportedDepreciationMethodException"

**Cause:** Trying to use Tier 2/3 depreciation method on Tier 1.

**Solution:** Update `.env`:
```env
ASSETS_TIER=advanced  # or enterprise
```

### Issue: "MaintenanceAnalyzer not bound"

**Cause:** Tier 1 doesn't support maintenance features.

**Solution:** Upgrade to Tier 2+ or remove maintenance-related code.

### Issue: "Asset tag already exists"

**Cause:** Duplicate asset tag.

**Solution:** Use unique tag generation via `AssetTag::fromSequence()`.

---

**For code examples, see [`docs/examples/`](examples/).**
