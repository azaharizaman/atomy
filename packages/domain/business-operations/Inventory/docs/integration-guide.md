# Integration Guide: Nexus Inventory

This guide provides comprehensive examples for integrating the Nexus Inventory package into different PHP frameworks and patterns.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Vanilla PHP Integration](#vanilla-php-integration)
4. [GL Integration via Events](#gl-integration-via-events)
5. [Background Jobs](#background-jobs)
6. [Common Patterns](#common-patterns)

---

## Laravel Integration

### Step 1: Create Service Provider

```php
// app/Providers/InventoryServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Inventory\Contracts\*;
use Nexus\Inventory\Services\*;
use Nexus\Inventory\Core\Engine\{FifoEngine, WeightedAverageEngine, StandardCostEngine};
use App\Repositories\Inventory\*;
use App\Services\Inventory\*;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->singleton(StockLevelRepositoryInterface::class, DbStockLevelRepository::class);
        $this->app->singleton(LotRepositoryInterface::class, DbLotRepository::class);
        $this->app->singleton(SerialNumberRepositoryInterface::class, DbSerialNumberRepository::class);
        $this->app->singleton(ReservationRepositoryInterface::class, DbReservationRepository::class);
        $this->app->singleton(TransferRepositoryInterface::class, DbTransferRepository::class);
        
        // Configuration
        $this->app->singleton(ConfigurationInterface::class, InventoryConfigurationAdapter::class);
        
        // Event Publisher
        $this->app->singleton(EventPublisherInterface::class, LaravelEventPublisher::class);
        
        // Valuation Engines (dynamic based on product config)
        $this->app->bind(ValuationEngineInterface::class, function ($app, $params) {
            $productId = $params['productId'] ?? null;
            $config = $app->make(ConfigurationInterface::class);
            
            if ($productId) {
                $method = $config->getValuationMethod($productId);
            } else {
                $method = ValuationMethod::WEIGHTED_AVERAGE; // default
            }
            
            return match($method) {
                ValuationMethod::FIFO => new FifoEngine(...),
                ValuationMethod::WEIGHTED_AVERAGE => new WeightedAverageEngine(...),
                ValuationMethod::STANDARD_COST => new StandardCostEngine(...),
            };
        });
        
        // Service Managers
        $this->app->singleton(StockManagerInterface::class, StockManager::class);
        $this->app->singleton(LotManagerInterface::class, LotManager::class);
        $this->app->singleton(SerialNumberManagerInterface::class, SerialNumberManager::class);
        $this->app->singleton(ReservationManagerInterface::class, ReservationManager::class);
        $this->app->singleton(TransferManagerInterface::class, TransferManager::class);
    }
    
    public function boot(): void
    {
        // Publish migrations
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../database/migrations' => database_path('migrations'),
            ], 'inventory-migrations');
        }
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\InventoryServiceProvider::class,
],
```

---

### Step 2: Implement Repository Interfaces

#### StockLevelRepository

```php
// app/Repositories/Inventory/DbStockLevelRepository.php
namespace App\Repositories\Inventory;

use Nexus\Inventory\Contracts\{StockLevelRepositoryInterface, StockLevelInterface};
use App\Models\Inventory\StockLevel;

final readonly class DbStockLevelRepository implements StockLevelRepositoryInterface
{
    public function getStockLevel(string $tenantId, string $productId, string $warehouseId): ?StockLevelInterface
    {
        return StockLevel::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }
    
    public function createStockLevel(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity
    ): StockLevelInterface {
        return StockLevel::create([
            'id' => \Ulid::generate(),
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
        ]);
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
    
    public function reserveStock(string $tenantId, string $productId, string $warehouseId, float $quantity): void
    {
        StockLevel::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->increment('reserved_quantity', $quantity);
    }
    
    public function releaseReservedStock(string $tenantId, string $productId, string $warehouseId, float $quantity): void
    {
        StockLevel::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->decrement('reserved_quantity', $quantity);
    }
}
```

#### LotRepository

```php
// app/Repositories/Inventory/DbLotRepository.php
namespace App\Repositories\Inventory;

use Nexus\Inventory\Contracts\{LotRepositoryInterface, LotInterface};
use App\Models\Inventory\Lot;

final readonly class DbLotRepository implements LotRepositoryInterface
{
    public function createLot(
        string $tenantId,
        string $productId,
        string $lotNumber,
        float $quantity,
        ?\DateTimeImmutable $expiryDate = null,
        ?\DateTimeImmutable $manufactureDate = null
    ): LotInterface {
        return Lot::create([
            'id' => \Ulid::generate(),
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'lot_number' => $lotNumber,
            'quantity_received' => $quantity,
            'quantity_remaining' => $quantity,
            'expiry_date' => $expiryDate,
            'manufacture_date' => $manufactureDate,
            'received_at' => now(),
        ]);
    }
    
    public function getAvailableLots(string $tenantId, string $productId): array
    {
        return Lot::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('quantity_remaining', '>', 0)
            ->where(fn($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now()))
            ->orderBy('expiry_date', 'asc')  // CRITICAL: FEFO enforcement
            ->orderBy('received_at', 'asc')  // Tie-breaker: older lots first
            ->get()
            ->toArray();
    }
    
    public function decrementLotQuantity(string $lotId, float $quantity): void
    {
        Lot::where('id', $lotId)->decrement('quantity_remaining', $quantity);
    }
    
    public function getExpiringLots(string $tenantId, int $daysThreshold = 30): array
    {
        $thresholdDate = now()->addDays($daysThreshold);
        
        return Lot::where('tenant_id', $tenantId)
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $thresholdDate)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->toArray();
    }
}
```

---

### Step 3: Create Configuration Adapter

```php
// app/Services/Inventory/InventoryConfigurationAdapter.php
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
        // Product-specific override
        $method = $this->settings->getString("inventory.products.{$productId}.valuation_method");
        
        if ($method) {
            return ValuationMethod::from($method);
        }
        
        // Global default
        $default = $this->settings->getString('inventory.default_valuation_method', 'weighted_average');
        return ValuationMethod::from($default);
    }
    
    public function isNegativeStockAllowed(string $productId): bool
    {
        // Product-specific override
        $allowed = $this->settings->getBoolean("inventory.products.{$productId}.allow_negative");
        
        if ($allowed !== null) {
            return $allowed;
        }
        
        // Global default
        return $this->settings->getBoolean('inventory.allow_negative_stock_global', false);
    }
    
    public function isLotTrackingRequired(string $productId): bool
    {
        return $this->settings->getBoolean("inventory.products.{$productId}.require_lot_tracking", false);
    }
    
    public function isSerialTrackingRequired(string $productId): bool
    {
        return $this->settings->getBoolean("inventory.products.{$productId}.require_serial_tracking", false);
    }
    
    public function getReservationTTL(): int
    {
        return $this->settings->getInt('inventory.reservation_ttl_hours', 48);
    }
}
```

---

### Step 4: Use in Controllers

```php
// app/Http/Controllers/Inventory/StockController.php
namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Inventory\Enums\IssueReason;
use Nexus\Currency\ValueObjects\Money;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class StockController extends Controller
{
    public function __construct(
        private StockManagerInterface $stockManager,
        private TenantContextInterface $tenantContext
    ) {}
    
    public function receive(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|ulid',
            'warehouse_id' => 'required|ulid',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'lot_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'reference' => 'nullable|string|max:255',
        ]);
        
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        $this->stockManager->receiveStock(
            tenantId: $tenantId,
            productId: $validated['product_id'],
            warehouseId: $validated['warehouse_id'],
            quantity: (float) $validated['quantity'],
            unitCost: Money::of($validated['unit_cost'], 'MYR'),
            lotNumber: $validated['lot_number'] ?? null,
            expiryDate: isset($validated['expiry_date']) 
                ? new \DateTimeImmutable($validated['expiry_date']) 
                : null,
            reference: $validated['reference'] ?? null
        );
        
        return response()->json(['message' => 'Stock received successfully']);
    }
    
    public function issue(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|ulid',
            'warehouse_id' => 'required|ulid',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|in:sale,production,scrap,adjustment',
            'reference' => 'nullable|string|max:255',
        ]);
        
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        $cogs = $this->stockManager->issueStock(
            tenantId: $tenantId,
            productId: $validated['product_id'],
            warehouseId: $validated['warehouse_id'],
            quantity: (float) $validated['quantity'],
            reason: IssueReason::from($validated['reason']),
            reference: $validated['reference'] ?? null
        );
        
        return response()->json([
            'message' => 'Stock issued successfully',
            'cogs' => [
                'amount' => $cogs->getAmount(),
                'currency' => $cogs->getCurrency(),
            ],
        ]);
    }
}
```

---

## Symfony Integration

### Step 1: Create Services Configuration

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
    
    # Repositories
    App\Repository\Inventory\DbStockLevelRepository:
        public: true
    
    Nexus\Inventory\Contracts\StockLevelRepositoryInterface:
        alias: App\Repository\Inventory\DbStockLevelRepository
    
    # ... other repositories
    
    # Configuration
    App\Service\Inventory\InventoryConfigurationAdapter:
        public: true
    
    Nexus\Inventory\Contracts\ConfigurationInterface:
        alias: App\Service\Inventory\InventoryConfigurationAdapter
    
    # Event Publisher
    App\Service\Inventory\SymfonyEventPublisher:
        arguments:
            - '@event_dispatcher'
    
    Nexus\Inventory\Contracts\EventPublisherInterface:
        alias: App\Service\Inventory\SymfonyEventPublisher
    
    # Valuation Engine (example: Weighted Average)
    Nexus\Inventory\Core\Engine\WeightedAverageEngine:
        arguments:
            - '@Nexus\Inventory\Contracts\StockLevelRepositoryInterface'
    
    Nexus\Inventory\Contracts\ValuationEngineInterface:
        alias: Nexus\Inventory\Core\Engine\WeightedAverageEngine
    
    # Service Managers
    Nexus\Inventory\Services\StockManager:
        arguments:
            - '@Nexus\Inventory\Contracts\StockLevelRepositoryInterface'
            - '@Nexus\Inventory\Contracts\ValuationEngineInterface'
            - '@Nexus\Inventory\Contracts\ConfigurationInterface'
            - '@Nexus\Inventory\Contracts\EventPublisherInterface'
            - '@Psr\Log\LoggerInterface'
    
    Nexus\Inventory\Contracts\StockManagerInterface:
        alias: Nexus\Inventory\Services\StockManager
```

---

### Step 2: Event Publisher

```php
// src/Service/Inventory/SymfonyEventPublisher.php
namespace App\Service\Inventory;

use Nexus\Inventory\Contracts\EventPublisherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class SymfonyEventPublisher implements EventPublisherInterface
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}
    
    public function publish(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
```

---

### Step 3: Use in Controllers

```php
// src/Controller/Inventory/StockController.php
namespace App\Controller\Inventory;

use Nexus\Inventory\Contracts\StockManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/inventory')]
final class StockController extends AbstractController
{
    public function __construct(
        private readonly StockManagerInterface $stockManager
    ) {}
    
    #[Route('/stock/receive', methods: ['POST'])]
    public function receive(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $this->stockManager->receiveStock(
            tenantId: $this->getUser()->getTenantId(),
            productId: $data['product_id'],
            warehouseId: $data['warehouse_id'],
            quantity: (float) $data['quantity'],
            unitCost: Money::of($data['unit_cost'], 'MYR'),
            lotNumber: $data['lot_number'] ?? null,
            reference: $data['reference'] ?? null
        );
        
        return new JsonResponse(['message' => 'Stock received']);
    }
}
```

---

## Vanilla PHP Integration

```php
// bootstrap.php - Dependency setup
use Nexus\Inventory\Services\StockManager;
use Nexus\Inventory\Core\Engine\WeightedAverageEngine;
use App\Repositories\PdoStockLevelRepository;
use App\Services\SimpleEventPublisher;
use App\Services\ArrayConfigurationAdapter;

$pdo = new PDO('mysql:host=localhost;dbname=inventory', 'user', 'pass');

// Create dependencies
$stockLevelRepo = new PdoStockLevelRepository($pdo);
$valuationEngine = new WeightedAverageEngine($stockLevelRepo);
$config = new ArrayConfigurationAdapter([
    'default_valuation_method' => 'weighted_average',
    'reservation_ttl_hours' => 48,
]);
$eventPublisher = new SimpleEventPublisher();
$logger = new \Psr\Log\NullLogger();

// Create stock manager
$stockManager = new StockManager(
    $stockLevelRepo,
    $valuationEngine,
    $config,
    $eventPublisher,
    $logger
);

// Use it
$stockManager->receiveStock(
    tenantId: 'tenant-1',
    productId: 'product-1',
    warehouseId: 'warehouse-1',
    quantity: 100.0,
    unitCost: Money::of(50.00, 'MYR')
);
```

---

## GL Integration via Events

### Event Listener (Laravel)

```php
// app/Listeners/Inventory/InventoryGLListener.php
namespace App\Listeners\Inventory;

use Nexus\Inventory\Events\{StockReceivedEvent, StockIssuedEvent, StockAdjustedEvent};
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;
use Illuminate\Events\Dispatcher;

final readonly class InventoryGLListener
{
    public function __construct(
        private GeneralLedgerManagerInterface $glManager
    ) {}
    
    public function handleStockReceived(StockReceivedEvent $event): void
    {
        // DR Inventory Asset / CR GR-IR Clearing
        $this->glManager->postJournalEntry(
            tenantId: $event->tenantId,
            entries: [
                ['account' => '1200', 'debit' => $event->totalCost], // Inventory Asset
                ['account' => '2100', 'credit' => $event->totalCost], // GR-IR Clearing
            ],
            reference: "Stock Receipt: {$event->reference}",
            postingDate: $event->receivedDate
        );
    }
    
    public function handleStockIssued(StockIssuedEvent $event): void
    {
        // DR COGS / CR Inventory Asset
        $this->glManager->postJournalEntry(
            tenantId: $event->tenantId,
            entries: [
                ['account' => '5000', 'debit' => $event->cogs], // COGS
                ['account' => '1200', 'credit' => $event->cogs], // Inventory Asset
            ],
            reference: "Stock Issue: {$event->reference}",
            postingDate: $event->issuedDate
        );
    }
    
    public function handleStockAdjusted(StockAdjustedEvent $event): void
    {
        // Adjustment depends on reason
        $account = match($event->reason) {
            AdjustmentReason::DAMAGE => '6100', // Loss from Damage
            AdjustmentReason::OBSOLESCENCE => '6110', // Loss from Obsolescence
            AdjustmentReason::CYCLE_COUNT => '6120', // Inventory Variance
            default => '6100',
        };
        
        if ($event->adjustmentQuantity > 0) {
            // Increase: DR Inventory Asset / CR Variance
            $this->glManager->postJournalEntry(
                tenantId: $event->tenantId,
                entries: [
                    ['account' => '1200', 'debit' => $event->adjustmentValue],
                    ['account' => $account, 'credit' => $event->adjustmentValue],
                ],
                reference: "Stock Adjustment: {$event->notes}",
                postingDate: $event->adjustmentDate
            );
        } else {
            // Decrease: DR Variance / CR Inventory Asset
            $this->glManager->postJournalEntry(
                tenantId: $event->tenantId,
                entries: [
                    ['account' => $account, 'debit' => $event->adjustmentValue],
                    ['account' => '1200', 'credit' => $event->adjustmentValue],
                ],
                reference: "Stock Adjustment: {$event->notes}",
                postingDate: $event->adjustmentDate
            );
        }
    }
    
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(StockReceivedEvent::class, [self::class, 'handleStockReceived']);
        $events->listen(StockIssuedEvent::class, [self::class, 'handleStockIssued']);
        $events->listen(StockAdjustedEvent::class, [self::class, 'handleStockAdjusted']);
    }
}
```

Register in `EventServiceProvider`:

```php
protected $subscribe = [
    \App\Listeners\Inventory\InventoryGLListener::class,
];
```

---

## Background Jobs

### Expire Reservations (Laravel)

```php
// app/Console/Commands/ExpireStockReservations.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Inventory\Contracts\ReservationManagerInterface;
use Nexus\Tenant\Contracts\TenantRepositoryInterface;

final class ExpireStockReservations extends Command
{
    protected $signature = 'inventory:expire-reservations';
    protected $description = 'Expire stale stock reservations (TTL exceeded)';
    
    public function __construct(
        private readonly ReservationManagerInterface $reservationManager,
        private readonly TenantRepositoryInterface $tenantRepository
    ) {
        parent::__construct();
    }
    
    public function handle(): int
    {
        $tenants = $this->tenantRepository->getAllActiveTenants();
        $totalExpired = 0;
        
        foreach ($tenants as $tenant) {
            $expired = $this->reservationManager->expireReservations($tenant->getId());
            $totalExpired += $expired;
            
            $this->info("Tenant {$tenant->getName()}: {$expired} reservations expired");
        }
        
        $this->info("Total: {$totalExpired} reservations expired");
        return Command::SUCCESS;
    }
}
```

Schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('inventory:expire-reservations')->hourly();
}
```

---

## Common Patterns

### Pattern 1: Receive Stock from Purchase Order

```php
public function receivePurchaseOrder(PurchaseOrder $po): void
{
    foreach ($po->getLineItems() as $line) {
        $this->stockManager->receiveStock(
            tenantId: $po->getTenantId(),
            productId: $line->getProductId(),
            warehouseId: $po->getWarehouseId(),
            quantity: $line->getQuantity(),
            unitCost: $line->getUnitCost(),
            reference: $po->getNumber()
        );
    }
    
    $po->markAsReceived();
}
```

### Pattern 2: Reserve Stock for Sales Order

```php
public function confirmSalesOrder(SalesOrder $so): void
{
    foreach ($so->getLineItems() as $line) {
        $this->reservationManager->reserve(
            tenantId: $so->getTenantId(),
            productId: $line->getProductId(),
            warehouseId: $so->getWarehouseId(),
            quantity: $line->getQuantity(),
            referenceType: 'SALES_ORDER',
            referenceId: $so->getId(),
            ttlHours: 48
        );
    }
    
    $so->markAsConfirmed();
}
```

### Pattern 3: Issue Stock on Sales Order Fulfillment

```php
public function fulfillSalesOrder(SalesOrder $so): void
{
    foreach ($so->getLineItems() as $line) {
        // Release reservation
        $reservation = $this->reservationManager->getByReference('SALES_ORDER', $so->getId());
        $this->reservationManager->release($reservation->getId(), ReleaseReason::FULFILLED);
        
        // Issue stock
        $cogs = $this->stockManager->issueStock(
            tenantId: $so->getTenantId(),
            productId: $line->getProductId(),
            warehouseId: $so->getWarehouseId(),
            quantity: $line->getQuantity(),
            reason: IssueReason::SALE,
            reference: $so->getNumber()
        );
        
        // Store COGS for margin analysis
        $line->setCogs($cogs);
    }
    
    $so->markAsFulfilled();
}
```

### Pattern 4: Inter-Warehouse Transfer

```php
public function transferStock(string $productId, string $fromWarehouse, string $toWarehouse, float $quantity): void
{
    // Initiate transfer
    $transferId = $this->transferManager->initiateTransfer(
        tenantId: $this->tenantContext->getCurrentTenantId(),
        productId: $productId,
        fromWarehouseId: $fromWarehouse,
        toWarehouseId: $toWarehouse,
        quantity: $quantity,
        reason: 'REBALANCING'
    );
    
    // Start shipment
    $this->transferManager->startShipment($transferId, trackingNumber: 'TRK-12345');
    
    // ... later, when goods received at destination
    $this->transferManager->completeTransfer($transferId);
}
```
