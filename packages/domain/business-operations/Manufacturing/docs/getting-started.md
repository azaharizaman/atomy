# Getting Started with Nexus Manufacturing

This guide will help you quickly integrate the Manufacturing package into your application.

---

## Installation

```bash
composer require nexus/manufacturing
```

---

## Basic Setup

### 1. Implement Repository Interfaces

The Manufacturing package requires you to implement repository interfaces for data persistence:

```php
use Nexus\Manufacturing\Contracts\BomRepositoryInterface;
use Nexus\Manufacturing\Contracts\BomInterface;

class EloquentBomRepository implements BomRepositoryInterface
{
    public function findById(string $id): ?BomInterface
    {
        $model = Bom::find($id);
        return $model ? $this->toInterface($model) : null;
    }
    
    public function findByProductId(string $productId, ?\DateTimeImmutable $effectiveDate = null): ?BomInterface
    {
        $query = Bom::where('product_id', $productId)
            ->where('status', 'released');
            
        if ($effectiveDate) {
            $query->where('effective_from', '<=', $effectiveDate)
                  ->where(fn($q) => $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $effectiveDate));
        }
        
        $model = $query->orderBy('version', 'desc')->first();
        return $model ? $this->toInterface($model) : null;
    }
    
    public function create(array $data): BomInterface
    {
        $model = Bom::create($data);
        return $this->toInterface($model);
    }
    
    // ... implement other methods
}
```

### 2. Configure Service Container

```php
// Laravel Service Provider example
use Nexus\Manufacturing\Services\BomManager;
use Nexus\Manufacturing\Services\WorkOrderManager;
use Nexus\Manufacturing\Contracts\BomRepositoryInterface;

class ManufacturingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(BomRepositoryInterface::class, EloquentBomRepository::class);
        $this->app->singleton(WorkOrderRepositoryInterface::class, EloquentWorkOrderRepository::class);
        $this->app->singleton(RoutingRepositoryInterface::class, EloquentRoutingRepository::class);
        
        // Bind managers
        $this->app->singleton(BomManager::class, function ($app) {
            return new BomManager(
                $app->make(BomRepositoryInterface::class)
            );
        });
        
        $this->app->singleton(WorkOrderManager::class, function ($app) {
            return new WorkOrderManager(
                $app->make(WorkOrderRepositoryInterface::class),
                $app->make(BomManager::class),
                $app->make(RoutingManager::class)
            );
        });
    }
}
```

---

## Your First BOM

### Create a Simple BOM

```php
use Nexus\Manufacturing\Services\BomManager;
use Nexus\Manufacturing\ValueObjects\BomLine;

class ProductionController
{
    public function __construct(
        private readonly BomManager $bomManager
    ) {}
    
    public function createBom(): void
    {
        // Create BOM for a bicycle
        $bom = $this->bomManager->create(
            productId: 'BICYCLE-001',
            version: '1.0',
            type: 'manufacturing',
            lines: [],
            effectiveFrom: new \DateTimeImmutable('2024-01-01')
        );
        
        // Add frame
        $this->bomManager->addLine($bom->getId(), new BomLine(
            productId: 'FRAME-001',
            quantity: 1.0,
            uomCode: 'EA',
            lineNumber: 10
        ));
        
        // Add wheels
        $this->bomManager->addLine($bom->getId(), new BomLine(
            productId: 'WHEEL-001',
            quantity: 2.0,
            uomCode: 'EA',
            lineNumber: 20
        ));
        
        // Add handlebars
        $this->bomManager->addLine($bom->getId(), new BomLine(
            productId: 'HANDLEBAR-001',
            quantity: 1.0,
            uomCode: 'EA',
            lineNumber: 30
        ));
        
        // Release the BOM for production use
        $this->bomManager->release($bom->getId());
    }
}
```

---

## Your First Work Order

### Create and Process a Work Order

```php
use Nexus\Manufacturing\Services\WorkOrderManager;
use Nexus\Manufacturing\Enums\WorkOrderStatus;

class WorkOrderController
{
    public function __construct(
        private readonly WorkOrderManager $workOrderManager
    ) {}
    
    public function createWorkOrder(): void
    {
        // Create work order for 50 bicycles
        $workOrder = $this->workOrderManager->create(
            productId: 'BICYCLE-001',
            quantity: 50.0,
            plannedStartDate: new \DateTimeImmutable('2024-02-01'),
            plannedEndDate: new \DateTimeImmutable('2024-02-10')
        );
        
        echo "Created Work Order: " . $workOrder->getOrderNumber();
    }
    
    public function processWorkOrder(string $workOrderId): void
    {
        // 1. Release for production
        $this->workOrderManager->release($workOrderId);
        
        // 2. Start production
        $this->workOrderManager->start($workOrderId);
        
        // 3. Record material issues
        $this->workOrderManager->issueMaterial($workOrderId, 'FRAME-001', 50.0);
        $this->workOrderManager->issueMaterial($workOrderId, 'WHEEL-001', 100.0);
        $this->workOrderManager->issueMaterial($workOrderId, 'HANDLEBAR-001', 50.0);
        
        // 4. Record operation completion
        $this->workOrderManager->completeOperation(
            workOrderId: $workOrderId,
            operationSequence: 10,
            completedQty: 50.0,
            scrapQty: 0.0,
            laborHours: 40.0
        );
        
        // 5. Complete and close
        $this->workOrderManager->complete($workOrderId);
        $this->workOrderManager->close($workOrderId);
    }
}
```

---

## Running MRP

### Basic MRP Calculation

```php
use Nexus\Manufacturing\Services\MrpEngine;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Nexus\Manufacturing\Enums\LotSizingStrategy;

class MrpController
{
    public function __construct(
        private readonly MrpEngine $mrpEngine
    ) {}
    
    public function runMrp(): void
    {
        $horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('today'),
            endDate: new \DateTimeImmutable('+90 days'),
            bucketSize: 7,  // Weekly buckets
            frozenDays: 14, // First 2 weeks are frozen
            slushyDays: 28  // Next 2 weeks are slushy
        );
        
        $result = $this->mrpEngine->run(
            productIds: ['BICYCLE-001'],
            horizon: $horizon,
            lotSizingStrategy: LotSizingStrategy::ECONOMIC_ORDER_QUANTITY
        );
        
        // Display planned orders
        foreach ($result->getPlannedOrders() as $order) {
            echo sprintf(
                "Product: %s\nQuantity: %.2f\nDue Date: %s\n\n",
                $order->getProductId(),
                $order->getQuantity(),
                $order->getDueDate()->format('Y-m-d')
            );
        }
        
        // Display material requirements
        foreach ($result->getMaterialRequirements() as $requirement) {
            echo sprintf(
                "Material: %s\nRequired: %.2f %s\nDate: %s\n\n",
                $requirement->getProductId(),
                $requirement->getQuantity(),
                $requirement->getUom(),
                $requirement->getRequiredDate()->format('Y-m-d')
            );
        }
    }
}
```

---

## Next Steps

1. **Read the API Reference** - [api-reference.md](api-reference.md)
2. **Review Integration Guide** - [integration-guide.md](integration-guide.md)
3. **Check Examples** - [examples/](examples/)
4. **Implement Capacity Planning** - Add work centers and track capacity

---

## Common Patterns

### Multi-Level BOM Explosion

```php
// Explode BOM to get all raw materials
$materials = $this->bomManager->explode($bomId, quantity: 100);

foreach ($materials as $material) {
    echo sprintf(
        "Product: %s, Required: %.2f, Level: %d\n",
        $material['productId'],
        $material['quantity'],
        $material['level']
    );
}
```

### BOM Version Management

```php
// Create new version from existing BOM
$newBom = $this->bomManager->createVersion(
    sourceBomId: $existingBomId,
    newVersion: '2.0',
    effectiveFrom: new \DateTimeImmutable('2024-06-01')
);

// The new version is in draft status - make changes then release
$this->bomManager->addLine($newBom->getId(), new BomLine(
    productId: 'NEW-COMP-001',
    quantity: 1.0,
    uomCode: 'EA',
    lineNumber: 40
));

$this->bomManager->release($newBom->getId());
```

### Work Order Status Checks

```php
$workOrder = $this->workOrderManager->getById($workOrderId);

match ($workOrder->getStatus()) {
    WorkOrderStatus::DRAFT => 'Work order is still in draft',
    WorkOrderStatus::PLANNED => 'Ready to release',
    WorkOrderStatus::RELEASED => 'Ready to start production',
    WorkOrderStatus::IN_PROGRESS => 'Production is ongoing',
    WorkOrderStatus::COMPLETED => 'Production finished',
    WorkOrderStatus::CLOSED => 'Work order is closed',
    WorkOrderStatus::CANCELLED => 'Work order was cancelled',
    WorkOrderStatus::ON_HOLD => 'Work order is on hold',
};
```

---

## Getting Help

- Review [REQUIREMENTS.md](../REQUIREMENTS.md) for detailed requirements
- Check [IMPLEMENTATION_SUMMARY.md](../IMPLEMENTATION_SUMMARY.md) for implementation details
- Open an issue on GitHub for bugs or feature requests
