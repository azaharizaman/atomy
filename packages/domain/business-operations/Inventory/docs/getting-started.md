# Getting Started with Nexus Inventory

## Prerequisites

- PHP 8.3 or higher
- Composer
- `nexus/uom` package (automatically installed)

## Installation

```bash
composer require nexus/inventory:"*@dev"
```

## Optional Dependencies (Progressive Disclosure)

```bash
# Event Sourcing for stock replay and temporal queries (recommended for large enterprises)
composer require nexus/event-stream:"*@dev"

# Demand forecasting and stock optimization (requires minimum 90 days of historical data)
composer require nexus/machine-learning:"*@dev"
```

Core inventory features work without these dependencies.

---

## When to Use This Package

This package is designed for:
- ✅ Multi-warehouse inventory management
- ✅ Accurate COGS calculation (FIFO, Weighted Average, Standard Cost)
- ✅ Lot tracking with expiry date management (FEFO enforcement)
- ✅ Serial number tracking
- ✅ Stock reservations for sales orders
- ✅ Inter-warehouse stock transfers
- ✅ GL integration via domain events

Do NOT use this package for:
- ❌ Warehouse bin-level tracking (use `Nexus\Warehouse`)
- ❌ Work order management (use `Nexus\Manufacturing`)
- ❌ Purchase order management (use `Nexus\Procurement`)

---

## Core Concepts

### 1. Stock Levels

Stock levels are maintained per product per warehouse:
- **Quantity:** Total physical stock
- **Reserved Quantity:** Stock reserved for sales orders/work orders
- **Available Quantity:** `quantity - reserved_quantity`

### 2. Valuation Methods

Three valuation methods supported:

| Method | Best For | Performance | COGS Accuracy |
|--------|----------|-------------|---------------|
| **FIFO** | Perishables, regulated industries | O(n) issue | Matches actual flow |
| **Weighted Average** | Commodities, bulk materials | O(1) both ops | Smooths cost fluctuations |
| **Standard Cost** | Manufacturing, electronics | O(1) both ops | Variance analysis |

### 3. Lot Tracking (FEFO)

**First-Expiry-First-Out (FEFO):** System automatically issues stock from lots with earliest expiry date.

**Regulatory Compliance:** Meets FDA requirements for perishable goods.

### 4. Stock Reservations

Temporary stock holds with configurable TTL (24-72 hours):
- Created when sales order confirmed
- Auto-expire after TTL
- Released on order fulfillment or cancellation

### 5. Stock Transfers

Finite State Machine workflow:
- **pending** → Order initiated
- **in_transit** → Shipment started
- **completed** → Transfer completed, stock levels updated
- **cancelled** → Transfer cancelled

---

## Basic Configuration

### Step 1: Implement Required Interfaces

#### 1.1 Stock Level Repository

```php
// App/Repositories/Inventory/DbStockLevelRepository.php
namespace App\Repositories\Inventory;

use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Inventory\Contracts\StockLevelInterface;
use App\Models\StockLevel;

final readonly class DbStockLevelRepository implements StockLevelRepositoryInterface
{
    public function getStockLevel(string $tenantId, string $productId, string $warehouseId): ?StockLevelInterface
    {
        return StockLevel::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }
    
    public function incrementStock(string $tenantId, string $productId, string $warehouseId, float $quantity): void
    {
        StockLevel::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->increment('quantity', $quantity);
    }
    
    public function decrementStock(string $tenantId, string $productId, string $warehouseId, float $quantity): void
    {
        StockLevel::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->decrement('quantity', $quantity);
    }
    
    // ... other methods
}
```

#### 1.2 Configuration Adapter

```php
// App/Services/Inventory/InventoryConfigurationAdapter.php
namespace App\Services\Inventory;

use Nexus\Inventory\Contracts\ConfigurationInterface;
use Nexus\Inventory\Enums\ValuationMethod;
use Nexus\Setting\Contracts\SettingsManagerInterface;

final readonly class InventoryConfigurationAdapter implements ConfigurationInterface
{
    public function __construct(
        private SettingsManagerInterface $settings
    ) {}
    
    public function getValuationMethod(string $productId): ValuationMethod
    {
        $method = $this->settings->getString("inventory.products.{$productId}.valuation_method")
            ?? $this->settings->getString('inventory.default_valuation_method', 'weighted_average');
        
        return ValuationMethod::from($method);
    }
    
    public function isNegativeStockAllowed(string $productId): bool
    {
        return $this->settings->getBoolean("inventory.products.{$productId}.allow_negative", false);
    }
    
    public function getReservationTTL(): int
    {
        return $this->settings->getInt('inventory.reservation_ttl_hours', 48);
    }
}
```

#### 1.3 Event Publisher

```php
// App/Services/Inventory/LaravelEventPublisher.php
namespace App\Services\Inventory;

use Nexus\Inventory\Contracts\EventPublisherInterface;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class LaravelEventPublisher implements EventPublisherInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}
    
    public function publish(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
```

### Step 2: Bind Interfaces in Service Provider

```php
// App/Providers/InventoryServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Inventory\Contracts\{
    StockManagerInterface,
    StockLevelRepositoryInterface,
    ConfigurationInterface,
    EventPublisherInterface,
    ValuationEngineInterface
};
use Nexus\Inventory\Services\StockManager;
use Nexus\Inventory\Core\Engine\{FifoEngine, WeightedAverageEngine, StandardCostEngine};
use App\Repositories\Inventory\DbStockLevelRepository;
use App\Services\Inventory\{InventoryConfigurationAdapter, LaravelEventPublisher};

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            StockLevelRepositoryInterface::class,
            DbStockLevelRepository::class
        );
        
        // Bind configuration
        $this->app->singleton(
            ConfigurationInterface::class,
            InventoryConfigurationAdapter::class
        );
        
        // Bind event publisher
        $this->app->singleton(
            EventPublisherInterface::class,
            LaravelEventPublisher::class
        );
        
        // Bind valuation engine (example: Weighted Average as default)
        $this->app->singleton(
            ValuationEngineInterface::class,
            WeightedAverageEngine::class
        );
        
        // Bind stock manager
        $this->app->singleton(
            StockManagerInterface::class,
            StockManager::class
        );
    }
}
```

### Step 3: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\InventoryServiceProvider::class,
],
```

---

## Your First Integration

### Example: Stock Receipt and Issue

```php
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Currency\ValueObjects\Money;

// Inject stock manager
public function __construct(
    private readonly StockManagerInterface $stockManager
) {}

// Receive stock (from purchase order)
public function receiveStockFromPO(string $productId, float $quantity, Money $unitCost): void
{
    $this->stockManager->receiveStock(
        tenantId: 'tenant-1',
        productId: $productId,
        warehouseId: 'warehouse-main',
        quantity: $quantity,
        unitCost: $unitCost,
        reference: 'PO-2024-001',
        receivedDate: new \DateTimeImmutable()
    );
    
    // StockReceivedEvent published automatically
    // GL listener will post: DR Inventory Asset / CR GR-IR Clearing
}

// Issue stock (for sales order)
public function issueStockForSalesOrder(string $productId, float $quantity): void
{
    $cogs = $this->stockManager->issueStock(
        tenantId: 'tenant-1',
        productId: $productId,
        warehouseId: 'warehouse-main',
        quantity: $quantity,
        reason: IssueReason::SALE,
        reference: 'SO-2024-005',
        issuedDate: new \DateTimeImmutable()
    );
    
    // StockIssuedEvent published automatically with COGS
    // GL listener will post: DR COGS / CR Inventory Asset
    
    echo "COGS: {$cogs->getAmount()} {$cogs->getCurrency()}";
}
```

---

## Next Steps

- **Read the [API Reference](api-reference.md)** for detailed interface documentation
- **Check [Integration Guide](integration-guide.md)** for framework-specific examples (Laravel, Symfony)
- **See [Examples](examples/)** for more code samples:
  - [Basic Usage](examples/basic-usage.php) - Simple stock operations
  - [Advanced Usage](examples/advanced-usage.php) - Lot tracking, reservations, transfers

---

## Troubleshooting

### Issue: InsufficientStockException thrown

**Cause:** Available stock < issue quantity  
**Solution:** 
- Check current stock level: `$stockManager->getAvailableStock($tenantId, $productId, $warehouseId)`
- Release any expired reservations: `$reservationManager->expireReservations($tenantId)`
- Consider allowing negative stock via configuration (not recommended for physical goods)

### Issue: NegativeStockNotAllowedException thrown

**Cause:** Negative stock prevention enabled  
**Solution:**
- Enable negative stock for product: `SettingsManager::set("inventory.products.{$productId}.allow_negative", true)`
- Or enable globally: `SettingsManager::set('inventory.allow_negative_stock_global', true)`

### Issue: StockReceivedEvent not triggering GL posting

**Cause:** Event listener not registered  
**Solution:** Ensure `InventoryGLListener` is registered in `EventServiceProvider`:

```php
protected $listen = [
    StockReceivedEvent::class => [InventoryGLListener::class],
    StockIssuedEvent::class => [InventoryGLListener::class],
];
```

### Issue: Lot allocation not respecting FEFO

**Cause:** Lots not loaded in expiry order  
**Solution:** Ensure `LotRepository::getAvailableLots()` returns lots ordered by `expiry_date ASC`:

```php
public function getAvailableLots(string $tenantId, string $productId): array
{
    return Lot::where('tenant_id', $tenantId)
        ->where('product_id', $productId)
        ->where('quantity_remaining', '>', 0)
        ->whereNull('expiry_date')
        ->orWhere('expiry_date', '>', now())
        ->orderBy('expiry_date', 'asc')  // ← Critical for FEFO
        ->get()
        ->toArray();
}
```
