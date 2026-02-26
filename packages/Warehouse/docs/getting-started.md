# Getting Started with Nexus Warehouse

## Overview

`Nexus\Warehouse` is a framework-agnostic warehouse management package that provides multi-warehouse support, bin location tracking with GPS coordinates, and intelligent picking route optimization using the Traveling Salesman Problem (TSP) algorithm.

The package achieves **15-30% reduction in picking distances** by optimizing the sequence in which items are picked from bin locations based on their physical coordinates.

## Prerequisites

- **PHP 8.3 or higher**
- **Composer** for dependency management
- **Required Nexus Packages:**
  - `nexus/routing` - TSP-based route optimization
  - `nexus/geo` - Coordinate handling and geospatial operations
  - `nexus/tenant` - Multi-tenancy context management

## Installation

```bash
composer require nexus/warehouse:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ **Multi-warehouse operations** - Manage multiple warehouses per tenant
- ✅ **Bin location management** - Track inventory locations with optional GPS coordinates
- ✅ **Pick route optimization** - Reduce walking distance for order fulfillment
- ✅ **Warehouse efficiency** - Improve operational efficiency with data-driven picking
- ✅ **Distribution centers** - Large warehouses with hundreds of bin locations

Do NOT use this package for:
- ❌ Simple single-location inventory (use `Nexus\Inventory` directly)
- ❌ External warehouse integrations (use `Nexus\Connector`)
- ❌ Real-time tracking (Phase 2 feature - deferred)

## Core Concepts

### 1. Warehouse

A physical or logical warehouse location managed by the system.

**Key Properties:**
- **Code:** Unique warehouse identifier (e.g., `WH-MAIN`, `WH-BRANCH-KL`)
- **Name:** Human-readable warehouse name
- **Metadata:** Custom attributes (address, capacity, operational hours, etc.)

**Multi-Tenancy:** All warehouses are automatically scoped to the current tenant.

### 2. Bin Location

A specific storage location within a warehouse where inventory is stored.

**Key Properties:**
- **Code:** Bin identifier (e.g., `A1-05-03` for Aisle 1, Row 5, Shelf 3)
- **Warehouse ID:** Parent warehouse reference
- **Coordinates:** Optional GPS coordinates (`[latitude, longitude]`) for route optimization

**Why GPS Coordinates?**
- Enables TSP-based route optimization
- Reduces picker walking distance by 15-30%
- Improves order fulfillment speed

### 3. Picking Optimization

The package uses the **Traveling Salesman Problem (TSP)** algorithm to find the shortest route through multiple bin locations.

**How It Works:**
1. Picker receives list of items to pick (pick list)
2. System identifies bin locations for each item
3. TSP algorithm calculates optimal visiting sequence
4. Picker follows optimized route, reducing distance traveled

**Performance Benefits:**
- **15-30% distance reduction** vs. sequential picking
- Faster order fulfillment
- Reduced picker fatigue
- Improved throughput

### 4. Integration with Nexus Packages

**Warehouse package dependencies:**
- **`Nexus\Routing`** - Provides TSP algorithm implementation (`TspOptimizer`)
- **`Nexus\Geo`** - Provides `Coordinates` value object for GPS data
- **`Nexus\Tenant`** - Provides tenant context for multi-tenancy

**Optional integrations:**
- **`Nexus\Inventory`** - Stock levels and product locations
- **`Nexus\AuditLogger`** - Track warehouse operations
- **`Nexus\Telemetry`** - Performance metrics for pick operations

## Basic Setup

### Step 1: Implement Required Interfaces

The package defines interfaces for data persistence. Your application must provide implementations.

#### Warehouse Repository

```php
<?php

namespace App\Repositories\Warehouse;

use Nexus\Warehouse\Contracts\WarehouseInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;
use Nexus\Warehouse\Exceptions\WarehouseNotFoundException;
use App\Models\Warehouse;

final readonly class EloquentWarehouseRepository implements WarehouseRepositoryInterface
{
    public function findById(string $id): WarehouseInterface
    {
        $warehouse = Warehouse::find($id);
        
        if ($warehouse === null) {
            throw WarehouseNotFoundException::withId($id);
        }
        
        return $warehouse;
    }
    
    public function findByCode(string $code): ?WarehouseInterface
    {
        return Warehouse::where('code', $code)->first();
    }
    
    public function save(WarehouseInterface $warehouse): void
    {
        if ($warehouse instanceof Warehouse) {
            $warehouse->save();
        }
    }
    
    public function delete(string $id): void
    {
        Warehouse::destroy($id);
    }
}
```

#### Bin Location Repository

```php
<?php

namespace App\Repositories\Warehouse;

use Nexus\Warehouse\Contracts\BinLocationInterface;
use Nexus\Warehouse\Contracts\BinLocationRepositoryInterface;
use App\Models\BinLocation;

final readonly class EloquentBinLocationRepository implements BinLocationRepositoryInterface
{
    public function findById(string $id): ?BinLocationInterface
    {
        return BinLocation::find($id);
    }
    
    public function findByWarehouse(string $warehouseId): array
    {
        return BinLocation::where('warehouse_id', $warehouseId)->get()->all();
    }
    
    public function save(BinLocationInterface $binLocation): void
    {
        if ($binLocation instanceof BinLocation) {
            $binLocation->save();
        }
    }
}
```

### Step 2: Create Database Models

#### Warehouse Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Warehouse\Contracts\WarehouseInterface;
use Symfony\Component\Uid\Ulid;

class Warehouse extends Model implements WarehouseInterface
{
    protected $fillable = ['id', 'tenant_id', 'code', 'name', 'metadata'];
    
    protected $casts = [
        'metadata' => 'array',
    ];
    
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) new Ulid();
            }
        });
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getCode(): string
    {
        return $this->code;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }
}
```

#### Bin Location Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Warehouse\Contracts\BinLocationInterface;
use Symfony\Component\Uid\Ulid;

class BinLocation extends Model implements BinLocationInterface
{
    protected $fillable = ['id', 'tenant_id', 'warehouse_id', 'code', 'latitude', 'longitude'];
    
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) new Ulid();
            }
        });
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getCode(): string
    {
        return $this->code;
    }
    
    public function getWarehouseId(): string
    {
        return $this->warehouse_id;
    }
    
    public function getCoordinates(): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }
        
        return [
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
        ];
    }
}
```

### Step 3: Create Database Migrations

#### Warehouses Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'code']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
```

#### Bin Locations Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bin_locations', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('warehouse_id', 26);
            $table->string('code', 50);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('cascade');
            
            $table->unique(['warehouse_id', 'code']);
            $table->index(['tenant_id', 'warehouse_id']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('bin_locations');
    }
};
```

### Step 4: Register Service Provider

#### Laravel Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;
use Nexus\Warehouse\Contracts\BinLocationRepositoryInterface;
use Nexus\Warehouse\Contracts\PickingOptimizerInterface;
use Nexus\Warehouse\Services\WarehouseManager;
use Nexus\Warehouse\Services\PickingOptimizer;
use App\Repositories\Warehouse\EloquentWarehouseRepository;
use App\Repositories\Warehouse\EloquentBinLocationRepository;

class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            WarehouseRepositoryInterface::class,
            EloquentWarehouseRepository::class
        );
        
        $this->app->singleton(
            BinLocationRepositoryInterface::class,
            EloquentBinLocationRepository::class
        );
        
        // Bind managers
        $this->app->singleton(
            WarehouseManagerInterface::class,
            WarehouseManager::class
        );
        
        $this->app->singleton(
            PickingOptimizerInterface::class,
            PickingOptimizer::class
        );
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\WarehouseServiceProvider::class,
],
```

## Your First Integration

### Example 1: Create Warehouse and Bin Locations

```php
<?php

use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Illuminate\Http\Request;

final readonly class WarehouseSetupController
{
    public function __construct(
        private WarehouseManagerInterface $warehouseManager
    ) {}
    
    public function setupWarehouse(Request $request)
    {
        // Create warehouse
        $warehouseId = $this->warehouseManager->createWarehouse(
            code: 'WH-MAIN',
            name: 'Main Distribution Center',
            metadata: [
                'address' => '123 Industrial Park, Kuala Lumpur',
                'capacity' => 10000,
                'operational_hours' => '8:00-18:00',
            ]
        );
        
        // Create bin locations with GPS coordinates
        $bins = [
            ['code' => 'A1-01-01', 'lat' => 3.0521, 'lng' => 101.6942],
            ['code' => 'A1-01-02', 'lat' => 3.0522, 'lng' => 101.6942],
            ['code' => 'A1-02-01', 'lat' => 3.0521, 'lng' => 101.6945],
            ['code' => 'B1-01-01', 'lat' => 3.0525, 'lng' => 101.6942],
        ];
        
        foreach ($bins as $bin) {
            $binLocation = new \App\Models\BinLocation([
                'warehouse_id' => $warehouseId,
                'code' => $bin['code'],
                'latitude' => $bin['lat'],
                'longitude' => $bin['lng'],
            ]);
            $binLocation->save();
        }
        
        return response()->json([
            'warehouse_id' => $warehouseId,
            'bins_created' => count($bins),
        ]);
    }
}
```

### Example 2: Optimize Pick Route

```php
<?php

use Nexus\Warehouse\Contracts\PickingOptimizerInterface;

final readonly class OrderFulfillmentService
{
    public function __construct(
        private PickingOptimizerInterface $pickingOptimizer
    ) {}
    
    public function generateOptimizedPickList(string $orderId): array
    {
        // Get order items with bin locations
        $pickItems = [
            ['bin_id' => 'bin_a1_01_01', 'product_id' => 'prod_001', 'quantity' => 10],
            ['bin_id' => 'bin_a1_02_01', 'product_id' => 'prod_002', 'quantity' => 5],
            ['bin_id' => 'bin_b1_01_01', 'product_id' => 'prod_003', 'quantity' => 3],
        ];
        
        // Optimize route
        $result = $this->pickingOptimizer->optimizePickRoute(
            warehouseId: 'wh_main',
            pickItems: $pickItems
        );
        
        // Get optimized sequence
        $optimizedSequence = $result->getOptimizedSequence();
        
        return [
            'pick_sequence' => $optimizedSequence,
            'total_distance_meters' => $result->getTotalDistance(),
            'distance_improvement_percent' => $result->getDistanceImprovement(),
            'optimization_time_ms' => $result->getExecutionTime(),
        ];
    }
}
```

## Next Steps

- **[API Reference](api-reference.md)** - Complete interface and method documentation
- **[Integration Guide](integration-guide.md)** - Framework-specific integration examples
- **[Basic Usage Examples](examples/basic-usage.php)** - Simple usage patterns
- **[Advanced Usage Examples](examples/advanced-usage.php)** - Complex scenarios

## Troubleshooting

### Common Issues

**Issue 1: "Bin missing coordinates" warning**
- **Cause:** Bin location does not have GPS coordinates set
- **Solution:** Either:
  1. Add GPS coordinates: `UPDATE bin_locations SET latitude = X, longitude = Y WHERE id = 'bin_id'`
  2. Accept sequential picking (no optimization) for bins without coordinates

**Issue 2: Pick route optimization returns sequential order**
- **Cause:** None of the bin locations have GPS coordinates
- **Solution:** Ensure bin locations have `latitude` and `longitude` values populated

**Issue 3: "BinLocationNotFoundException" exception**
- **Cause:** Referenced bin ID does not exist
- **Solution:** Verify bin IDs in pick list match existing bin location records

**Issue 4: Poor optimization results (<5% improvement)**
- **Cause:** Bin locations are physically close together or coordinates are inaccurate
- **Solution:** 
  1. Verify GPS coordinates are accurate (use warehouse floor plan measurements)
  2. For closely clustered bins, sequential picking may already be optimal

## Performance Considerations

### GPS Coordinate Accuracy

- Use **high-precision coordinates** (7 decimal places) for best results
- Coordinates should reflect actual walking paths, not straight-line distances
- Consider aisle layout when placing coordinate points

### Optimization Scaling

- TSP algorithm performance: O(n²) to O(n!)
- Recommended maximum: **50 bins per pick route**
- For larger orders, split into multiple optimized routes

### Caching Recommendations

Cache bin location coordinates:

```php
$binCoordinates = Cache::remember(
    "warehouse.{$warehouseId}.bin_coordinates",
    3600,
    fn() => $this->binRepository->findByWarehouse($warehouseId)
);
```

## Related Packages

- **`Nexus\Inventory`** - Stock management
- **`Nexus\Routing`** - TSP algorithm
- **`Nexus\Geo`** - Geospatial operations
- **`Nexus\Tenant`** - Multi-tenancy context
