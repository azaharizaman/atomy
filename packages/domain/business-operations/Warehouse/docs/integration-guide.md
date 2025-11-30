# Integration Guide: Warehouse

Complete integration examples for Laravel and Symfony frameworks.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Database Schema](#database-schema)
4. [Common Patterns](#common-patterns)
5. [Performance Optimization](#performance-optimization)
6. [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/warehouse:"*@dev"
```

### Step 2: Create Database Migrations

#### Warehouses Table

```php
<?php
// database/migrations/2025_11_27_000001_create_warehouses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID
            $table->char('tenant_id', 26)->index();
            $table->string('code', 50)->index();
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
```

#### Bin Locations Table

```php
<?php
// database/migrations/2025_11_27_000002_create_bin_locations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bin_locations', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID
            $table->char('warehouse_id', 26)->index();
            $table->string('code', 50)->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['warehouse_id', 'code']);
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bin_locations');
    }
};
```

### Step 3: Create Eloquent Models

#### Warehouse Model

```php
<?php
// app/Models/Warehouse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Warehouse\Contracts\WarehouseInterface;
use Symfony\Component\Uid\Ulid;

class Warehouse extends Model implements WarehouseInterface
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'tenant_id',
        'code',
        'name',
        'address',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Warehouse $warehouse) {
            if (empty($warehouse->id)) {
                $warehouse->id = (string) new Ulid();
            }
            
            // Auto-assign tenant from context
            if (empty($warehouse->tenant_id)) {
                $warehouse->tenant_id = app('tenant.context')->getCurrentTenantId();
            }
        });
    }

    // WarehouseInterface implementation
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    // Eloquent relationships
    public function binLocations()
    {
        return $this->hasMany(BinLocation::class);
    }
}
```

#### BinLocation Model

```php
<?php
// app/Models/BinLocation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Warehouse\Contracts\BinLocationInterface;
use Symfony\Component\Uid\Ulid;

class BinLocation extends Model implements BinLocationInterface
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'warehouse_id',
        'code',
        'latitude',
        'longitude',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BinLocation $bin) {
            if (empty($bin->id)) {
                $bin->id = (string) new Ulid();
            }
        });
    }

    // BinLocationInterface implementation
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
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    // Eloquent relationships
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
```

### Step 4: Create Repository Implementations

#### Warehouse Repository

```php
<?php
// app/Repositories/Warehouse/EloquentWarehouseRepository.php

namespace App\Repositories\Warehouse;

use App\Models\Warehouse;
use Nexus\Warehouse\Contracts\WarehouseInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;

final readonly class EloquentWarehouseRepository implements WarehouseRepositoryInterface
{
    public function findById(string $id): ?WarehouseInterface
    {
        return Warehouse::find($id);
    }

    public function findByCode(string $tenantId, string $code): ?WarehouseInterface
    {
        return Warehouse::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    public function findByTenant(string $tenantId): array
    {
        return Warehouse::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->all();
    }

    public function save(WarehouseInterface $warehouse): void
    {
        if ($warehouse instanceof Warehouse) {
            $warehouse->save();
        }
    }

    public function delete(string $id): void
    {
        // Soft delete preserves audit trail
        Warehouse::find($id)?->delete();
    }
}
```

#### BinLocation Repository

```php
<?php
// app/Repositories/Warehouse/EloquentBinLocationRepository.php

namespace App\Repositories\Warehouse;

use App\Models\BinLocation;
use Nexus\Warehouse\Contracts\BinLocationInterface;
use Nexus\Warehouse\Contracts\BinLocationRepositoryInterface;

final readonly class EloquentBinLocationRepository implements BinLocationRepositoryInterface
{
    public function findById(string $id): ?BinLocationInterface
    {
        return BinLocation::find($id);
    }

    public function findByCode(string $warehouseId, string $code): ?BinLocationInterface
    {
        return BinLocation::where('warehouse_id', $warehouseId)
            ->where('code', $code)
            ->first();
    }

    public function findByWarehouse(string $warehouseId): array
    {
        return BinLocation::where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->all();
    }

    public function save(BinLocationInterface $binLocation): void
    {
        if ($binLocation instanceof BinLocation) {
            $binLocation->save();
        }
    }

    public function delete(string $id): void
    {
        BinLocation::find($id)?->delete();
    }
}
```

### Step 5: Register Services in Service Provider

```php
<?php
// app/Providers/WarehouseServiceProvider.php

namespace App\Providers;

use App\Repositories\Warehouse\EloquentWarehouseRepository;
use App\Repositories\Warehouse\EloquentBinLocationRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;
use Nexus\Warehouse\Contracts\BinLocationRepositoryInterface;
use Nexus\Warehouse\Contracts\PickingOptimizerInterface;
use Nexus\Warehouse\Services\WarehouseManager;
use Nexus\Warehouse\Services\PickingOptimizer;
use Nexus\Routing\Contracts\RouteOptimizerInterface;
use Psr\Log\LoggerInterface;

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

        // Bind services
        $this->app->singleton(
            WarehouseManagerInterface::class,
            function ($app) {
                return new WarehouseManager(
                    repository: $app->make(WarehouseRepositoryInterface::class),
                    tenantId: $app->make('tenant.context')->getCurrentTenantId(),
                    logger: $app->make(LoggerInterface::class)
                );
            }
        );

        $this->app->singleton(
            PickingOptimizerInterface::class,
            function ($app) {
                return new PickingOptimizer(
                    binRepository: $app->make(BinLocationRepositoryInterface::class),
                    tspOptimizer: $app->make(RouteOptimizerInterface::class),
                    logger: $app->make(LoggerInterface::class)
                );
            }
        );
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ... other providers
    App\Providers\WarehouseServiceProvider::class,
],
```

### Step 6: Usage in Controllers

```php
<?php
// app/Http/Controllers/WarehouseController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Warehouse\Exceptions\WarehouseNotFoundException;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseManagerInterface $warehouseManager
    ) {}

    public function index()
    {
        $warehouses = $this->warehouseManager->listWarehouses();
        
        return response()->json($warehouses);
    }

    public function show(string $id)
    {
        try {
            $warehouse = $this->warehouseManager->getWarehouse($id);
            return response()->json($warehouse);
        } catch (WarehouseNotFoundException $e) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $warehouseId = $this->warehouseManager->createWarehouse(
            code: $validated['code'],
            name: $validated['name'],
            metadata: array_merge($validated['metadata'] ?? [], [
                'address' => $validated['address'] ?? null,
            ])
        );

        $warehouse = $this->warehouseManager->getWarehouse($warehouseId);

        return response()->json($warehouse, 201);
    }
}
```

```php
<?php
// app/Http/Controllers/PickingController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nexus\Warehouse\Contracts\PickingOptimizerInterface;
use Nexus\Warehouse\Exceptions\BinLocationNotFoundException;

class PickingController extends Controller
{
    public function __construct(
        private readonly PickingOptimizerInterface $pickingOptimizer
    ) {}

    public function optimize(Request $request, string $warehouseId)
    {
        $validated = $request->validate([
            'pick_items' => 'required|array|min:1',
            'pick_items.*.bin_id' => 'required|string',
            'pick_items.*.product_id' => 'required|string',
            'pick_items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            $result = $this->pickingOptimizer->optimizePickRoute(
                warehouseId: $warehouseId,
                pickItems: $validated['pick_items']
            );

            return response()->json([
                'optimized_sequence' => $result->getOptimizedSequence(),
                'total_distance' => $result->getTotalDistance(),
                'improvement_percent' => $result->getDistanceImprovement(),
                'execution_time_ms' => $result->getExecutionTime(),
            ]);
        } catch (BinLocationNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/warehouse:"*@dev"
```

### Step 2: Create Doctrine Entities

#### Warehouse Entity

```php
<?php
// src/Entity/Warehouse.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Warehouse\Contracts\WarehouseInterface;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'warehouses')]
#[ORM\UniqueConstraint(name: 'unique_tenant_code', columns: ['tenant_id', 'code'])]
#[ORM\Index(name: 'idx_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_active', columns: ['is_active'])]
class Warehouse implements WarehouseInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $code;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $tenantId,
        string $code,
        string $name,
        ?string $address = null,
        array $metadata = []
    ) {
        $this->id = (string) new Ulid();
        $this->tenantId = $tenantId;
        $this->code = $code;
        $this->name = $name;
        $this->address = $address;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // WarehouseInterface implementation
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    // Setters
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

#### BinLocation Entity

```php
<?php
// src/Entity/BinLocation.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Warehouse\Contracts\BinLocationInterface;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'bin_locations')]
#[ORM\UniqueConstraint(name: 'unique_warehouse_code', columns: ['warehouse_id', 'code'])]
#[ORM\Index(name: 'idx_warehouse', columns: ['warehouse_id'])]
class BinLocation implements BinLocationInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $warehouseId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $code;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(
        string $warehouseId,
        string $code,
        ?float $latitude = null,
        ?float $longitude = null
    ) {
        $this->id = (string) new Ulid();
        $this->warehouseId = $warehouseId;
        $this->code = $code;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    // BinLocationInterface implementation
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
        return $this->warehouseId;
    }

    public function getCoordinates(): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    public function setCoordinates(float $latitude, float $longitude): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }
}
```

### Step 3: Create Doctrine Repositories

#### Warehouse Repository

```php
<?php
// src/Repository/WarehouseRepository.php

namespace App\Repository;

use App\Entity\Warehouse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Warehouse\Contracts\WarehouseInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;

class WarehouseRepository extends ServiceEntityRepository implements WarehouseRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Warehouse::class);
    }

    public function findById(string $id): ?WarehouseInterface
    {
        return $this->find($id);
    }

    public function findByCode(string $tenantId, string $code): ?WarehouseInterface
    {
        return $this->findOneBy([
            'tenantId' => $tenantId,
            'code' => $code,
        ]);
    }

    public function findByTenant(string $tenantId): array
    {
        return $this->findBy(
            ['tenantId' => $tenantId, 'isActive' => true],
            ['code' => 'ASC']
        );
    }

    public function save(WarehouseInterface $warehouse): void
    {
        $this->getEntityManager()->persist($warehouse);
        $this->getEntityManager()->flush();
    }

    public function delete(string $id): void
    {
        $warehouse = $this->find($id);
        if ($warehouse) {
            $this->getEntityManager()->remove($warehouse);
            $this->getEntityManager()->flush();
        }
    }
}
```

#### BinLocation Repository

```php
<?php
// src/Repository/BinLocationRepository.php

namespace App\Repository;

use App\Entity\BinLocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Warehouse\Contracts\BinLocationInterface;
use Nexus\Warehouse\Contracts\BinLocationRepositoryInterface;

class BinLocationRepository extends ServiceEntityRepository implements BinLocationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BinLocation::class);
    }

    public function findById(string $id): ?BinLocationInterface
    {
        return $this->find($id);
    }

    public function findByCode(string $warehouseId, string $code): ?BinLocationInterface
    {
        return $this->findOneBy([
            'warehouseId' => $warehouseId,
            'code' => $code,
        ]);
    }

    public function findByWarehouse(string $warehouseId): array
    {
        return $this->findBy(
            ['warehouseId' => $warehouseId, 'isActive' => true],
            ['code' => 'ASC']
        );
    }

    public function save(BinLocationInterface $binLocation): void
    {
        $this->getEntityManager()->persist($binLocation);
        $this->getEntityManager()->flush();
    }

    public function delete(string $id): void
    {
        $binLocation = $this->find($id);
        if ($binLocation) {
            $this->getEntityManager()->remove($binLocation);
            $this->getEntityManager()->flush();
        }
    }
}
```

### Step 4: Configure Services

```yaml
# config/services.yaml

services:
    # Repositories
    App\Repository\WarehouseRepository:
        tags: ['doctrine.repository_service']

    App\Repository\BinLocationRepository:
        tags: ['doctrine.repository_service']

    # Bind repository interfaces
    Nexus\Warehouse\Contracts\WarehouseRepositoryInterface:
        alias: App\Repository\WarehouseRepository

    Nexus\Warehouse\Contracts\BinLocationRepositoryInterface:
        alias: App\Repository\BinLocationRepository

    # Warehouse Manager
    Nexus\Warehouse\Contracts\WarehouseManagerInterface:
        class: Nexus\Warehouse\Services\WarehouseManager
        arguments:
            $repository: '@Nexus\Warehouse\Contracts\WarehouseRepositoryInterface'
            $tenantId: '@=service("tenant.context").getCurrentTenantId()'
            $logger: '@logger'

    # Picking Optimizer
    Nexus\Warehouse\Contracts\PickingOptimizerInterface:
        class: Nexus\Warehouse\Services\PickingOptimizer
        arguments:
            $binRepository: '@Nexus\Warehouse\Contracts\BinLocationRepositoryInterface'
            $tspOptimizer: '@Nexus\Routing\Contracts\RouteOptimizerInterface'
            $logger: '@logger'
```

### Step 5: Usage in Controllers

```php
<?php
// src/Controller/WarehouseController.php

namespace App\Controller;

use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Warehouse\Exceptions\WarehouseNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/warehouses')]
class WarehouseController extends AbstractController
{
    public function __construct(
        private readonly WarehouseManagerInterface $warehouseManager
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $warehouses = $this->warehouseManager->listWarehouses();
        return $this->json($warehouses);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $warehouse = $this->warehouseManager->getWarehouse($id);
            return $this->json($warehouse);
        } catch (WarehouseNotFoundException $e) {
            return $this->json(['error' => 'Warehouse not found'], 404);
        }
    }
}
```

---

## Database Schema

### Tables Overview

```sql
-- Warehouses table
CREATE TABLE warehouses (
    id CHAR(26) PRIMARY KEY,
    tenant_id CHAR(26) NOT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSON,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP,
    UNIQUE(tenant_id, code),
    INDEX idx_tenant (tenant_id),
    INDEX idx_active (is_active)
);

-- Bin locations table
CREATE TABLE bin_locations (
    id CHAR(26) PRIMARY KEY,
    warehouse_id CHAR(26) NOT NULL,
    code VARCHAR(50) NOT NULL,
    latitude DECIMAL(10, 7),
    longitude DECIMAL(10, 7),
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSON,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP,
    UNIQUE(warehouse_id, code),
    INDEX idx_warehouse (warehouse_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
);
```

---

## Common Patterns

### Pattern 1: Warehouse Setup with Bin Locations

```php
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use App\Models\BinLocation;

// Create warehouse
$warehouseId = $warehouseManager->createWarehouse(
    code: 'WH-KL-01',
    name: 'Kuala Lumpur Warehouse',
    metadata: [
        'address' => 'Jalan Industri, Shah Alam',
        'capacity' => 100000,
    ]
);

// Create bin locations with coordinates
$bins = [
    ['code' => 'A1-01-01', 'lat' => 3.073800, 'lng' => 101.518300],
    ['code' => 'A1-01-02', 'lat' => 3.073820, 'lng' => 101.518320],
    ['code' => 'A1-02-01', 'lat' => 3.073840, 'lng' => 101.518340],
    // ... more bins
];

foreach ($bins as $bin) {
    BinLocation::create([
        'warehouse_id' => $warehouseId,
        'code' => $bin['code'],
        'latitude' => $bin['lat'],
        'longitude' => $bin['lng'],
    ]);
}
```

### Pattern 2: Pick List Optimization

```php
use Nexus\Warehouse\Contracts\PickingOptimizerInterface;

// Original pick list (unoptimized)
$pickItems = [
    ['bin_id' => 'bin-1', 'product_id' => 'prod-a', 'quantity' => 5],
    ['bin_id' => 'bin-5', 'product_id' => 'prod-b', 'quantity' => 2],
    ['bin_id' => 'bin-2', 'product_id' => 'prod-c', 'quantity' => 10],
    ['bin_id' => 'bin-8', 'product_id' => 'prod-d', 'quantity' => 3],
];

// Optimize route
$result = $pickingOptimizer->optimizePickRoute($warehouseId, $pickItems);

// Use optimized sequence
foreach ($result->getOptimizedSequence() as $pick) {
    echo "Pick {$pick['quantity']} units of {$pick['product_id']} from {$pick['bin_code']}\n";
}

// Log metrics
Log::info('Pick route optimized', [
    'distance_meters' => $result->getTotalDistance(),
    'improvement_pct' => $result->getDistanceImprovement(),
    'time_ms' => $result->getExecutionTime(),
]);
```

### Pattern 3: Multi-Warehouse Product Lookup

```php
use App\Models\Warehouse;
use App\Models\BinLocation;
use App\Models\InventoryItem;

// Find product across all warehouses
function findProductLocations(string $productId): array
{
    $locations = [];
    
    $warehouses = Warehouse::where('is_active', true)->get();
    
    foreach ($warehouses as $warehouse) {
        $inventory = InventoryItem::where('warehouse_id', $warehouse->id)
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->get();
        
        foreach ($inventory as $item) {
            $bin = BinLocation::find($item->bin_location_id);
            
            $locations[] = [
                'warehouse' => $warehouse->name,
                'bin' => $bin->code,
                'quantity' => $item->quantity,
            ];
        }
    }
    
    return $locations;
}
```

---

## Performance Optimization

### 1. Caching Warehouse Data

```php
use Illuminate\Support\Facades\Cache;

// Cache warehouse list
$warehouses = Cache::remember('warehouses:tenant:' . $tenantId, 3600, function () use ($warehouseManager) {
    return $warehouseManager->listWarehouses();
});
```

### 2. Eager Loading for Bin Locations

```php
// Eloquent eager loading
$warehouses = Warehouse::with('binLocations')->get();

foreach ($warehouses as $warehouse) {
    foreach ($warehouse->binLocations as $bin) {
        // No N+1 query problem
    }
}
```

### 3. Batch Bin Creation

```php
// Create bins in batch
$bins = [];
for ($i = 1; $i <= 1000; $i++) {
    $bins[] = [
        'id' => (string) new Ulid(),
        'warehouse_id' => $warehouseId,
        'code' => sprintf('BIN-%04d', $i),
        'latitude' => 3.073800 + ($i * 0.00001),
        'longitude' => 101.518300 + ($i * 0.00001),
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

BinLocation::insert($bins);
```

### 4. Index Optimization

```php
// Add indexes for common queries
Schema::table('bin_locations', function (Blueprint $table) {
    $table->index(['warehouse_id', 'is_active']);
    $table->index(['latitude', 'longitude']); // For spatial queries
});
```

---

## Troubleshooting

### Issue: Optimization Returns 0% Improvement

**Cause:** Bin locations missing GPS coordinates

**Solution:**
```php
// Check bins without coordinates
$binsWithoutCoords = BinLocation::where('warehouse_id', $warehouseId)
    ->whereNull('latitude')
    ->orWhereNull('longitude')
    ->get();

foreach ($binsWithoutCoords as $bin) {
    echo "Bin {$bin->code} missing coordinates\n";
}
```

### Issue: WarehouseNotFoundException

**Cause:** Warehouse ID doesn't exist or belongs to different tenant

**Solution:**
```php
try {
    $warehouse = $warehouseManager->getWarehouse($id);
} catch (WarehouseNotFoundException $e) {
    // Check if warehouse exists at all
    $exists = Warehouse::withTrashed()->find($id);
    
    if ($exists && $exists->deleted_at) {
        // Warehouse was soft deleted
    } elseif ($exists && $exists->tenant_id !== $currentTenantId) {
        // Warehouse belongs to different tenant
    } else {
        // Warehouse never existed
    }
}
```

### Issue: Slow Pick Route Optimization

**Cause:** Large pick list (100+ items) or missing database indexes

**Solution:**
```php
// Break into smaller batches
$pickItems = [...]; // 200 items
$batches = array_chunk($pickItems, 50);

$optimizedSequence = [];
foreach ($batches as $batch) {
    $result = $pickingOptimizer->optimizePickRoute($warehouseId, $batch);
    $optimizedSequence = array_merge($optimizedSequence, $result->getOptimizedSequence());
}
```

### Issue: Duplicate Warehouse Codes

**Cause:** Missing unique constraint or race condition

**Solution:**
```php
// Use database transaction with lock
DB::transaction(function () use ($code, $name) {
    $exists = Warehouse::where('tenant_id', $tenantId)
        ->where('code', $code)
        ->lockForUpdate()
        ->exists();
    
    if ($exists) {
        throw new \RuntimeException("Warehouse code {$code} already exists");
    }
    
    Warehouse::create([
        'tenant_id' => $tenantId,
        'code' => $code,
        'name' => $name,
    ]);
});
```

---

## Related Documentation

- **API Reference:** `docs/api-reference.md` - Complete interface documentation
- **Getting Started:** `docs/getting-started.md` - Quick start guide
- **Basic Examples:** `docs/examples/basic-usage.php` - Simple code examples
- **Advanced Examples:** `docs/examples/advanced-usage.php` - Complex scenarios

---

**Last Updated:** November 27, 2025  
**Package Version:** 1.0.0-dev  
**Minimum PHP Version:** 8.3
