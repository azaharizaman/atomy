# API Reference: Warehouse

Complete reference for all interfaces, services, and exceptions in Nexus Warehouse package.

---

## Table of Contents

1. [Management Interfaces](#management-interfaces)
2. [Entity Interfaces](#entity-interfaces)
3. [Repository Interfaces](#repository-interfaces)
4. [Optimization Interfaces](#optimization-interfaces)
5. [Services](#services)
6. [Exceptions](#exceptions)

---

## Management Interfaces

### WarehouseManagerInterface

Central interface for warehouse management operations. Provides high-level warehouse CRUD operations with tenant scoping.

**Location:** `src/Contracts/WarehouseManagerInterface.php`

```php
namespace Nexus\Warehouse\Contracts;

interface WarehouseManagerInterface
{
    /**
     * Create warehouse
     * 
     * Creates a new warehouse within the current tenant context.
     * The warehouse code must be unique within the tenant scope.
     * 
     * @param string $code Warehouse code (e.g., "WH-MAIN", "WH-KL-01")
     * @param string $name Human-readable warehouse name
     * @param array $metadata Additional warehouse metadata (address, capacity, etc.)
     * @return string Created warehouse unique identifier (ULID)
     * 
     * @throws \Nexus\Warehouse\Exceptions\WarehouseException If creation fails
     */
    public function createWarehouse(string $code, string $name, array $metadata = []): string;
    
    /**
     * Get warehouse details
     * 
     * Retrieves complete warehouse information including all metadata.
     * 
     * @param string $warehouseId Warehouse unique identifier
     * @return array Warehouse data array with keys: id, code, name, address, is_active, metadata
     * 
     * @throws \Nexus\Warehouse\Exceptions\WarehouseNotFoundException When warehouse is not found
     */
    public function getWarehouse(string $warehouseId): array;
    
    /**
     * List warehouses for tenant
     * 
     * Returns all warehouses belonging to the current tenant.
     * Results are automatically filtered by tenant context.
     * 
     * @return array<array> List of warehouse data arrays
     */
    public function listWarehouses(): array;
}
```

**Usage Examples:**

```php
// Create new warehouse
$warehouseId = $warehouseManager->createWarehouse(
    code: 'WH-MAIN',
    name: 'Main Distribution Center',
    metadata: [
        'address' => '123 Industrial Park, Shah Alam',
        'capacity' => 50000,
        'operational_hours' => '08:00-18:00',
    ]
);

// Get warehouse details
$warehouse = $warehouseManager->getWarehouse($warehouseId);
echo $warehouse['name']; // "Main Distribution Center"

// List all warehouses
$warehouses = $warehouseManager->listWarehouses();
foreach ($warehouses as $wh) {
    echo "{$wh['code']}: {$wh['name']}\n";
}
```

**Requirements:** FUN-WMS-001, FUN-WMS-002, BUS-WMS-101

---

## Entity Interfaces

### WarehouseInterface

Entity contract representing a physical or logical warehouse location.

**Location:** `src/Contracts/WarehouseInterface.php`

```php
namespace Nexus\Warehouse\Contracts;

interface WarehouseInterface
{
    /**
     * Get warehouse unique identifier
     * 
     * @return string ULID identifier
     */
    public function getId(): string;
    
    /**
     * Get warehouse code
     * 
     * @return string Unique code within tenant (e.g., "WH-MAIN")
     */
    public function getCode(): string;
    
    /**
     * Get warehouse name
     * 
     * @return string Human-readable name
     */
    public function getName(): string;
    
    /**
     * Get warehouse address
     * 
     * @return string|null Physical address or null if not set
     */
    public function getAddress(): ?string;
    
    /**
     * Check if warehouse is active
     * 
     * @return bool True if warehouse is operational
     */
    public function isActive(): bool;
    
    /**
     * Get warehouse metadata
     * 
     * @return array<string, mixed> Custom metadata (capacity, hours, contact, etc.)
     */
    public function getMetadata(): array;
}
```

**Implementation Notes:**
- All properties should be `readonly`
- ULID used for identifier generation
- Metadata stored as JSON in database
- Active status controls warehouse availability

---

### BinLocationInterface

Entity contract representing a storage location within a warehouse.

**Location:** `src/Contracts/BinLocationInterface.php`

```php
namespace Nexus\Warehouse\Contracts;

interface BinLocationInterface
{
    /**
     * Get bin location unique identifier
     * 
     * @return string ULID identifier
     */
    public function getId(): string;
    
    /**
     * Get bin location code
     * 
     * Format examples: "A1-05-03" (Aisle-Row-Shelf), "BIN-001", "RACK-A-10"
     * 
     * @return string Unique code within warehouse
     */
    public function getCode(): string;
    
    /**
     * Get parent warehouse identifier
     * 
     * @return string Warehouse ULID
     */
    public function getWarehouseId(): string;
    
    /**
     * Get GPS coordinates for route optimization
     * 
     * Returns null if coordinates not set. When coordinates are available,
     * the picking optimizer can calculate optimal routes using TSP algorithm.
     * 
     * @return array{latitude: float, longitude: float}|null Decimal degree coordinates
     */
    public function getCoordinates(): ?array;
}
```

**GPS Coordinates Format:**
```php
[
    'latitude' => 3.0738,   // Decimal degrees (N/S)
    'longitude' => 101.5183 // Decimal degrees (E/W)
]
```

**Bin Naming Conventions:**
- Aisle-Row-Shelf: `A1-05-03`, `B2-10-02`
- Sequential: `BIN-001`, `BIN-002`
- Zone-based: `ZONE-A-001`, `PICK-FACE-10`
- Rack-based: `RACK-A-10`, `PALLET-B-05`

---

## Repository Interfaces

### WarehouseRepositoryInterface

Persistence contract for warehouse entities.

**Location:** `src/Contracts/WarehouseRepositoryInterface.php`

```php
namespace Nexus\Warehouse\Contracts;

interface WarehouseRepositoryInterface
{
    /**
     * Find warehouse by ID
     * 
     * @param string $id Warehouse unique identifier
     * @return WarehouseInterface|null Warehouse entity or null if not found
     */
    public function findById(string $id): ?WarehouseInterface;
    
    /**
     * Find warehouse by code within tenant
     * 
     * Warehouse codes are unique per tenant, not globally.
     * 
     * @param string $tenantId Tenant unique identifier
     * @param string $code Warehouse code
     * @return WarehouseInterface|null Warehouse entity or null if not found
     */
    public function findByCode(string $tenantId, string $code): ?WarehouseInterface;
    
    /**
     * Find all active warehouses for a tenant
     * 
     * Returns only warehouses with is_active=true.
     * 
     * @param string $tenantId Tenant unique identifier
     * @return array<WarehouseInterface> Array of warehouse entities
     */
    public function findByTenant(string $tenantId): array;
    
    /**
     * Save warehouse entity
     * 
     * Performs insert or update based on existence.
     * 
     * @param WarehouseInterface $warehouse Warehouse entity to persist
     * @return void
     */
    public function save(WarehouseInterface $warehouse): void;
    
    /**
     * Delete warehouse by ID
     * 
     * Soft delete recommended for audit trail preservation.
     * 
     * @param string $id Warehouse unique identifier to delete
     * @return void
     */
    public function delete(string $id): void;
}
```

**Implementation Requirements:**
- Must enforce tenant isolation in all queries
- Soft delete recommended over hard delete
- Consider caching frequently accessed warehouses
- Index on `tenant_id`, `code`, and `is_active`

---

### BinLocationRepositoryInterface

Persistence contract for bin location entities.

**Location:** `src/Contracts/BinLocationRepositoryInterface.php`

```php
namespace Nexus\Warehouse\Contracts;

interface BinLocationRepositoryInterface
{
    /**
     * Find bin location by ID
     * 
     * @param string $id Bin location unique identifier
     * @return BinLocationInterface|null Bin location entity or null if not found
     */
    public function findById(string $id): ?BinLocationInterface;
    
    /**
     * Find bin location by code within warehouse
     * 
     * Bin codes are unique per warehouse, not globally.
     * 
     * @param string $warehouseId Warehouse unique identifier
     * @param string $code Bin location code
     * @return BinLocationInterface|null Bin location entity or null if not found
     */
    public function findByCode(string $warehouseId, string $code): ?BinLocationInterface;
    
    /**
     * Find all active bin locations in warehouse
     * 
     * Returns only active bin locations. For large warehouses (1000+ bins),
     * consider implementing pagination in your application layer.
     * 
     * @param string $warehouseId Warehouse unique identifier
     * @return array<BinLocationInterface> Array of bin location entities
     */
    public function findByWarehouse(string $warehouseId): array;
    
    /**
     * Save bin location entity
     * 
     * Performs insert or update based on existence.
     * 
     * @param BinLocationInterface $binLocation Bin location entity to persist
     * @return void
     */
    public function save(BinLocationInterface $binLocation): void;
    
    /**
     * Delete bin location by ID
     * 
     * Should check for existing inventory before deletion.
     * 
     * @param string $id Bin location unique identifier to delete
     * @return void
     */
    public function delete(string $id): void;
}
```

**Performance Considerations:**
- For warehouses with 1000+ bins, implement pagination
- Cache bin coordinates for optimization performance
- Index on `warehouse_id`, `code`, and `is_active`
- Consider spatial indexes for coordinate queries

---

## Optimization Interfaces

### PickingOptimizerInterface

Interface for optimizing picking routes using Traveling Salesman Problem (TSP) algorithm.

**Location:** `src/Contracts/PickingOptimizerInterface.php`

```php
namespace Nexus\Warehouse\Contracts;

interface PickingOptimizerInterface
{
    /**
     * Optimize pick route using TSP algorithm
     * 
     * Calculates the shortest route through all bin locations to minimize
     * walking distance. Uses Nexus\Routing TSP optimizer under the hood.
     * 
     * Bins without GPS coordinates are placed at the end in original order.
     * 
     * @param string $warehouseId Warehouse identifier
     * @param array<array{bin_id: string, product_id: string, quantity: float}> $pickItems Items to pick
     * @return PickRouteResult Optimized route with metrics
     * 
     * @throws \Nexus\Warehouse\Exceptions\BinLocationNotFoundException If bin not found
     */
    public function optimizePickRoute(string $warehouseId, array $pickItems): PickRouteResult;
}
```

**Pick Items Format:**
```php
[
    ['bin_id' => 'bin-ulid-1', 'product_id' => 'prod-123', 'quantity' => 5.0],
    ['bin_id' => 'bin-ulid-2', 'product_id' => 'prod-456', 'quantity' => 2.0],
    ['bin_id' => 'bin-ulid-3', 'product_id' => 'prod-789', 'quantity' => 10.0],
]
```

**Optimization Algorithm:**
1. Load bin locations with coordinates
2. Build TSP route graph using Nexus\Routing
3. Calculate optimal sequence
4. Append bins without coordinates at end
5. Return optimized sequence with metrics

**Typical Performance:**
- 10-20 picks: ~50-100ms
- 50-100 picks: ~200-500ms
- 100+ picks: ~500-1000ms

---

### PickRouteResult

Result interface for optimized picking routes.

**Location:** `src/Contracts/PickingOptimizerInterface.php`

```php
namespace Nexus\Warehouse\Contracts;

interface PickRouteResult
{
    /**
     * Get optimized sequence of bin locations
     * 
     * Returns bins in optimal visiting order for minimal distance.
     * 
     * @return array<array{bin_id: string, bin_code: string, product_id: string, quantity: float}>
     */
    public function getOptimizedSequence(): array;
    
    /**
     * Get total distance in meters
     * 
     * Sum of all distances between consecutive bins in optimized route.
     * Returns 0.0 if no coordinates available.
     * 
     * @return float Total distance in meters
     */
    public function getTotalDistance(): float;
    
    /**
     * Get distance improvement percentage vs. sequential picking
     * 
     * Compares optimized route distance against sequential (original order) distance.
     * Returns 0.0 if no coordinates available.
     * 
     * @return float Improvement percentage (e.g., 23.5 means 23.5% shorter)
     */
    public function getDistanceImprovement(): float;
    
    /**
     * Get execution time in milliseconds
     * 
     * Time taken to calculate optimization. Useful for performance monitoring.
     * 
     * @return int Execution time in milliseconds
     */
    public function getExecutionTime(): int;
}
```

**Optimized Sequence Format:**
```php
[
    [
        'bin_id' => 'bin-ulid-2',
        'bin_code' => 'A1-05-03',
        'product_id' => 'prod-456',
        'quantity' => 2.0,
    ],
    [
        'bin_id' => 'bin-ulid-1',
        'bin_code' => 'A1-06-01',
        'product_id' => 'prod-123',
        'quantity' => 5.0,
    ],
    // ... more picks in optimal order
]
```

**Metrics Interpretation:**
- **Distance Improvement:** 15-30% typical, 40%+ possible for large warehouses
- **Execution Time:** Should be <1 second for up to 100 picks
- **Total Distance:** Used for performance KPIs and picker route planning

---

## Services

### WarehouseManager

Default implementation of `WarehouseManagerInterface` providing warehouse CRUD operations.

**Location:** `src/Services/WarehouseManager.php`

```php
namespace Nexus\Warehouse\Services;

use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;
use Psr\Log\LoggerInterface;

final readonly class WarehouseManager implements WarehouseManagerInterface
{
    public function __construct(
        private WarehouseRepositoryInterface $repository,
        private string $tenantId,
        private LoggerInterface $logger
    ) {}
    
    // ... implementation
}
```

**Constructor Parameters:**
- `$repository` - Warehouse persistence implementation
- `$tenantId` - Current tenant identifier for scoping
- `$logger` - PSR-3 logger for operation tracking

**Usage:**
```php
$manager = new WarehouseManager(
    repository: $warehouseRepository,
    tenantId: $tenantContext->getCurrentTenantId(),
    logger: $logger
);

$warehouseId = $manager->createWarehouse('WH-01', 'Main Warehouse');
```

---

### PickingOptimizer

Default implementation of `PickingOptimizerInterface` using TSP algorithm via Nexus\Routing.

**Location:** `src/Services/PickingOptimizer.php`

```php
namespace Nexus\Warehouse\Services;

use Nexus\Warehouse\Contracts\PickingOptimizerInterface;
use Nexus\Warehouse\Contracts\BinLocationRepositoryInterface;
use Nexus\Routing\Contracts\RouteOptimizerInterface;
use Psr\Log\LoggerInterface;

final readonly class PickingOptimizer implements PickingOptimizerInterface
{
    public function __construct(
        private BinLocationRepositoryInterface $binRepository,
        private RouteOptimizerInterface $tspOptimizer,
        private LoggerInterface $logger
    ) {}
    
    // ... implementation
}
```

**Constructor Parameters:**
- `$binRepository` - Bin location persistence implementation
- `$tspOptimizer` - Nexus\Routing TSP optimizer service
- `$logger` - PSR-3 logger for performance tracking

**Algorithm Details:**
1. Fetch bin locations for all pick items
2. Extract GPS coordinates (skip bins without coordinates)
3. Build RouteStop objects for TSP input
4. Call `TspOptimizer->optimizeTsp()`
5. Map optimized sequence back to pick items
6. Calculate metrics (distance, improvement, time)

**Performance Logging:**
```php
// Automatically logs:
[info] Optimizing pick route
    warehouse_id: wh-ulid-123
    item_count: 25

[info] Pick route optimized
    item_count: 25
    distance_meters: 350.5
    improvement_pct: 23.4
    execution_ms: 87
```

---

## Exceptions

### WarehouseException

Base exception class for all warehouse-related errors.

**Location:** `src/Exceptions/WarehouseException.php`

```php
namespace Nexus\Warehouse\Exceptions;

class WarehouseException extends \RuntimeException
{
}
```

**Usage:** Catch this exception to handle all warehouse errors generically.

```php
try {
    $warehouse = $manager->getWarehouse($id);
} catch (WarehouseException $e) {
    // Handle any warehouse error
    Log::error('Warehouse operation failed', ['error' => $e->getMessage()]);
}
```

---

### WarehouseNotFoundException

Thrown when a warehouse cannot be found by ID or code.

**Location:** `src/Exceptions/WarehouseNotFoundException.php`

```php
namespace Nexus\Warehouse\Exceptions;

final class WarehouseNotFoundException extends WarehouseException
{
    /**
     * Create exception for warehouse not found by ID
     * 
     * @param string $warehouseId Warehouse unique identifier that was not found
     * @return self
     */
    public static function withId(string $warehouseId): self
    {
        return new self("Warehouse not found: {$warehouseId}");
    }
}
```

**Usage:**
```php
try {
    $warehouse = $manager->getWarehouse($warehouseId);
} catch (WarehouseNotFoundException $e) {
    return response()->json(['error' => 'Warehouse not found'], 404);
}
```

**When Thrown:**
- `WarehouseManagerInterface::getWarehouse()` - When ID doesn't exist
- `WarehouseRepositoryInterface::findById()` - Application layer usage

---

### BinLocationNotFoundException

Thrown when a bin location cannot be found by ID or code.

**Location:** `src/Exceptions/BinLocationNotFoundException.php`

```php
namespace Nexus\Warehouse\Exceptions;

final class BinLocationNotFoundException extends WarehouseException
{
    /**
     * Create exception for bin location not found by ID
     * 
     * @param string $binId Bin location unique identifier that was not found
     * @return self
     */
    public static function withId(string $binId): self
    {
        return new self("Bin location not found: {$binId}");
    }
}
```

**Usage:**
```php
try {
    $result = $optimizer->optimizePickRoute($warehouseId, $pickItems);
} catch (BinLocationNotFoundException $e) {
    // One or more bins in pick list don't exist
    Log::error('Invalid pick list', ['error' => $e->getMessage()]);
}
```

**When Thrown:**
- `PickingOptimizer::optimizePickRoute()` - When bin ID in pick items doesn't exist
- Application layer bin lookups

---

## Related Documentation

- **Getting Started Guide:** `docs/getting-started.md` - Installation and basic setup
- **Integration Guide:** `docs/integration-guide.md` - Laravel and Symfony examples
- **Basic Usage Examples:** `docs/examples/basic-usage.php` - Simple code examples
- **Advanced Usage Examples:** `docs/examples/advanced-usage.php` - Complex scenarios

---

## Package Dependencies

- **`nexus/routing`** - TSP algorithm implementation
- **`nexus/geo`** - Coordinates value object
- **`nexus/tenant`** - Multi-tenancy context
- **`psr/log`** - Logging interface

---

**Last Updated:** November 27, 2025  
**Package Version:** 1.0.0-dev  
**Minimum PHP Version:** 8.3
