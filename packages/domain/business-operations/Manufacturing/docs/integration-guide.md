# Integration Guide: Nexus Manufacturing

This guide explains how to integrate the Manufacturing package with other Nexus packages and your application framework.

---

## Table of Contents

1. [Repository Implementation](#repository-implementation)
2. [Nexus Package Integration](#nexus-package-integration)
3. [Framework Integration](#framework-integration)
4. [Event Handling](#event-handling)
5. [Testing Strategies](#testing-strategies)

---

## Repository Implementation

The Manufacturing package requires you to implement repository interfaces for data persistence.

### BomRepositoryInterface

```php
namespace App\Repositories;

use Nexus\Manufacturing\Contracts\BomRepositoryInterface;
use Nexus\Manufacturing\Contracts\BomInterface;
use App\Models\Bom as BomModel;

final class EloquentBomRepository implements BomRepositoryInterface
{
    public function __construct(
        private readonly TenantContextInterface $tenantContext
    ) {}

    public function findById(string $id): ?BomInterface
    {
        $model = BomModel::query()
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->find($id);
            
        return $model ? $this->toInterface($model) : null;
    }

    public function findByProductId(
        string $productId,
        ?\DateTimeImmutable $effectiveDate = null
    ): ?BomInterface {
        $query = BomModel::query()
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('product_id', $productId)
            ->where('status', 'released');

        if ($effectiveDate) {
            $dateStr = $effectiveDate->format('Y-m-d');
            $query->where('effective_from', '<=', $dateStr)
                  ->where(fn($q) => $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $dateStr));
        }

        $model = $query->orderByDesc('version')->first();
        return $model ? $this->toInterface($model) : null;
    }

    public function findAllVersions(string $productId): array
    {
        return BomModel::query()
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('product_id', $productId)
            ->orderByDesc('version')
            ->get()
            ->map(fn($m) => $this->toInterface($m))
            ->all();
    }

    public function create(array $data): BomInterface
    {
        $data['tenant_id'] = $this->tenantContext->getCurrentTenantId();
        $model = BomModel::create($data);
        return $this->toInterface($model);
    }

    public function update(string $id, array $data): void
    {
        BomModel::where('id', $id)
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->update($data);
    }

    public function delete(string $id): void
    {
        BomModel::where('id', $id)
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->delete();
    }

    private function toInterface(BomModel $model): BomInterface
    {
        return new BomEntity(
            id: $model->id,
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            version: $model->version,
            type: $model->type,
            name: $model->name,
            outputQuantity: $model->output_quantity,
            outputUom: $model->output_uom,
            lines: $this->mapLines($model->lines),
            effectiveFrom: $model->effective_from ? new \DateTimeImmutable($model->effective_from) : null,
            effectiveTo: $model->effective_to ? new \DateTimeImmutable($model->effective_to) : null,
            status: $model->status,
            createdAt: new \DateTimeImmutable($model->created_at),
            updatedAt: new \DateTimeImmutable($model->updated_at)
        );
    }
}
```

---

## Nexus Package Integration

### Nexus\Inventory Integration

Integrate with Inventory for stock tracking and material requirements.

```php
namespace App\Services\Manufacturing;

use Nexus\Manufacturing\Contracts\InventoryDataProviderInterface;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Inventory\Contracts\ReservationManagerInterface;

final readonly class InventoryAdapter implements InventoryDataProviderInterface
{
    public function __construct(
        private StockManagerInterface $stockManager,
        private ReservationManagerInterface $reservationManager
    ) {}

    public function getOnHandQuantity(string $productId, string $warehouseId): float
    {
        return $this->stockManager->getOnHandQuantity(
            $this->tenantId(),
            $productId,
            $warehouseId
        );
    }

    public function getAvailableQuantity(string $productId, string $warehouseId): float
    {
        $onHand = $this->getOnHandQuantity($productId, $warehouseId);
        $reserved = $this->reservationManager->getReservedQuantity(
            $this->tenantId(),
            $productId,
            $warehouseId
        );
        return max(0, $onHand - $reserved);
    }

    public function reserveStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        string $referenceType,
        string $referenceId
    ): string {
        return $this->reservationManager->reserve(
            tenantId: $this->tenantId(),
            productId: $productId,
            warehouseId: $warehouseId,
            quantity: $quantity,
            referenceType: $referenceType,
            referenceId: $referenceId,
            ttlHours: 72
        );
    }

    public function issueStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        string $reference
    ): void {
        $this->stockManager->issueStock(
            tenantId: $this->tenantId(),
            productId: $productId,
            warehouseId: $warehouseId,
            quantity: $quantity,
            reason: IssueReason::PRODUCTION,
            reference: $reference
        );
    }
}
```

### Nexus\MachineLearning Integration

Integrate with ML package for demand forecasting.

```php
namespace App\Services\Manufacturing;

use Nexus\Manufacturing\Contracts\ForecastProviderInterface;
use Nexus\Manufacturing\ValueObjects\DemandForecast;
use Nexus\Manufacturing\Enums\ForecastConfidence;
use Nexus\MachineLearning\Contracts\ModelLoaderInterface;
use Nexus\MachineLearning\Contracts\InferenceEngineInterface;

final readonly class MlForecastProvider implements ForecastProviderInterface
{
    public function __construct(
        private ModelLoaderInterface $modelLoader,
        private InferenceEngineInterface $inferenceEngine
    ) {}

    public function getForecast(
        string $productId,
        \DateTimeImmutable $date
    ): ?DemandForecast {
        try {
            // Load demand forecasting model
            $model = $this->modelLoader->load('demand_forecast', stage: 'production');
            
            // Prepare features
            $features = [
                'product_id' => $productId,
                'date' => $date->format('Y-m-d'),
                'day_of_week' => (int)$date->format('N'),
                'month' => (int)$date->format('n'),
            ];
            
            // Run inference
            $prediction = $this->inferenceEngine->predict($model, $features);
            
            return new DemandForecast(
                productId: $productId,
                forecastDate: $date,
                quantity: $prediction['quantity'],
                confidence: $this->mapConfidence($prediction['confidence_score'])
            );
        } catch (\Exception $e) {
            return null; // Will trigger fallback
        }
    }

    private function mapConfidence(float $score): ForecastConfidence
    {
        return match (true) {
            $score >= 0.85 => ForecastConfidence::HIGH,
            $score >= 0.70 => ForecastConfidence::MEDIUM,
            $score >= 0.50 => ForecastConfidence::LOW,
            default => ForecastConfidence::UNKNOWN,
        };
    }
}
```

### Nexus\Monitoring Integration

Track manufacturing metrics with the Monitoring package.

```php
namespace App\Services\Manufacturing;

use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Nexus\Manufacturing\Services\WorkOrderManager as BaseWorkOrderManager;

final readonly class MonitoredWorkOrderManager
{
    public function __construct(
        private BaseWorkOrderManager $workOrderManager,
        private TelemetryTrackerInterface $telemetry
    ) {}

    public function create(
        string $productId,
        float $quantity,
        \DateTimeImmutable $plannedStartDate,
        \DateTimeImmutable $plannedEndDate,
        ?string $bomId = null,
        ?string $routingId = null
    ): WorkOrderInterface {
        $startTime = microtime(true);
        
        try {
            $workOrder = $this->workOrderManager->create(
                $productId,
                $quantity,
                $plannedStartDate,
                $plannedEndDate,
                $bomId,
                $routingId
            );
            
            $this->telemetry->increment('manufacturing.work_orders_created', [
                'product_id' => $productId,
            ]);
            
            $this->telemetry->gauge('manufacturing.work_order_quantity', $quantity, [
                'product_id' => $productId,
            ]);
            
            return $workOrder;
        } finally {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->telemetry->timing('manufacturing.create_work_order_ms', $duration);
        }
    }

    public function complete(string $workOrderId): void
    {
        $workOrder = $this->workOrderManager->getById($workOrderId);
        
        $this->workOrderManager->complete($workOrderId);
        
        $this->telemetry->increment('manufacturing.work_orders_completed');
        
        // Track lead time
        $leadTime = $workOrder->getActualStartDate()
            ? $workOrder->getActualEndDate()->diff($workOrder->getActualStartDate())->days
            : 0;
            
        $this->telemetry->histogram('manufacturing.lead_time_days', $leadTime, [
            'product_id' => $workOrder->getProductId(),
        ]);
    }
}
```

### Nexus\AuditLogger Integration

Audit manufacturing operations.

```php
namespace App\Services\Manufacturing;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Manufacturing\Contracts\WorkOrderManagerInterface;

final readonly class AuditedWorkOrderManager implements WorkOrderManagerInterface
{
    public function __construct(
        private WorkOrderManagerInterface $inner,
        private AuditLogManagerInterface $auditLogger
    ) {}

    public function release(string $workOrderId): void
    {
        $workOrder = $this->inner->getById($workOrderId);
        
        $this->inner->release($workOrderId);
        
        $this->auditLogger->log(
            entityId: $workOrderId,
            action: 'work_order_released',
            description: sprintf(
                'Work order %s released for product %s, quantity %.2f',
                $workOrder->getOrderNumber(),
                $workOrder->getProductId(),
                $workOrder->getPlannedQuantity()
            ),
            metadata: [
                'product_id' => $workOrder->getProductId(),
                'quantity' => $workOrder->getPlannedQuantity(),
                'planned_start' => $workOrder->getPlannedStartDate()->format('Y-m-d'),
            ]
        );
    }

    // Implement other methods with audit logging...
}
```

---

## Framework Integration

### Laravel Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Manufacturing\Services\BomManager;
use Nexus\Manufacturing\Services\WorkOrderManager;
use Nexus\Manufacturing\Services\MrpEngine;
use Nexus\Manufacturing\Services\CapacityPlanner;
use Nexus\Manufacturing\Contracts\*;

class ManufacturingServiceProvider extends ServiceProvider
{
    public array $singletons = [
        // Repositories
        BomRepositoryInterface::class => EloquentBomRepository::class,
        RoutingRepositoryInterface::class => EloquentRoutingRepository::class,
        WorkOrderRepositoryInterface::class => EloquentWorkOrderRepository::class,
        WorkCenterRepositoryInterface::class => EloquentWorkCenterRepository::class,
        
        // Data Providers
        InventoryDataProviderInterface::class => InventoryAdapter::class,
        ForecastProviderInterface::class => MlForecastProvider::class,
    ];

    public function register(): void
    {
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

        $this->app->singleton(MrpEngine::class, function ($app) {
            return new MrpEngine(
                $app->make(BomManager::class),
                $app->make(InventoryDataProviderInterface::class),
                $app->make(DemandDataProviderInterface::class),
                $app->make(PlannedOrderRepositoryInterface::class)
            );
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/manufacturing.php' => config_path('manufacturing.php'),
        ], 'manufacturing-config');
    }
}
```

### Symfony Service Configuration

```yaml
# config/services.yaml
services:
    # Repositories
    App\Repository\BomRepository:
        arguments:
            $tenantContext: '@Nexus\Tenant\Contracts\TenantContextInterface'

    Nexus\Manufacturing\Contracts\BomRepositoryInterface:
        alias: App\Repository\BomRepository

    # Services
    Nexus\Manufacturing\Services\BomManager:
        arguments:
            $repository: '@Nexus\Manufacturing\Contracts\BomRepositoryInterface'

    Nexus\Manufacturing\Services\WorkOrderManager:
        arguments:
            $repository: '@Nexus\Manufacturing\Contracts\WorkOrderRepositoryInterface'
            $bomManager: '@Nexus\Manufacturing\Services\BomManager'
            $routingManager: '@Nexus\Manufacturing\Services\RoutingManager'

    # Aliases for interfaces
    Nexus\Manufacturing\Contracts\BomManagerInterface:
        alias: Nexus\Manufacturing\Services\BomManager
```

---

## Event Handling

### Publishing Events

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Nexus\Manufacturing\Events\WorkOrderReleasedEvent;

final readonly class EventPublishingWorkOrderManager
{
    public function __construct(
        private WorkOrderManagerInterface $inner,
        private EventDispatcherInterface $dispatcher
    ) {}

    public function release(string $workOrderId): void
    {
        $this->inner->release($workOrderId);
        
        $workOrder = $this->inner->getById($workOrderId);
        
        $this->dispatcher->dispatch(new WorkOrderReleasedEvent(
            workOrderId: $workOrder->getId(),
            productId: $workOrder->getProductId(),
            quantity: $workOrder->getPlannedQuantity(),
            plannedStartDate: $workOrder->getPlannedStartDate()
        ));
    }
}
```

### Event Subscribers

```php
namespace App\Listeners;

use Nexus\Manufacturing\Events\WorkOrderReleasedEvent;
use Nexus\Inventory\Contracts\ReservationManagerInterface;

class ReserveMaterialsOnWorkOrderRelease
{
    public function __construct(
        private readonly ReservationManagerInterface $reservationManager,
        private readonly BomManagerInterface $bomManager
    ) {}

    public function __invoke(WorkOrderReleasedEvent $event): void
    {
        // Get BOM materials
        $materials = $this->bomManager->explode(
            $event->bomId,
            $event->quantity
        );

        // Reserve materials
        foreach ($materials as $material) {
            $this->reservationManager->reserve(
                tenantId: $event->tenantId,
                productId: $material['productId'],
                warehouseId: $event->warehouseId,
                quantity: $material['quantity'],
                referenceType: 'WORK_ORDER',
                referenceId: $event->workOrderId,
                ttlHours: 168 // 1 week
            );
        }
    }
}
```

---

## Testing Strategies

### Unit Testing with Mocks

```php
use PHPUnit\Framework\TestCase;
use Nexus\Manufacturing\Services\BomManager;
use Nexus\Manufacturing\Contracts\BomRepositoryInterface;

class BomManagerTest extends TestCase
{
    public function testCreateBom(): void
    {
        $repository = $this->createMock(BomRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('create')
            ->willReturn($this->createMockBom());

        $manager = new BomManager($repository);

        $bom = $manager->create(
            productId: 'PROD-001',
            version: '1.0',
            type: 'manufacturing'
        );

        $this->assertNotNull($bom->getId());
    }
}
```

### Integration Testing

```php
use Tests\TestCase;
use Nexus\Manufacturing\Services\WorkOrderManager;

class WorkOrderIntegrationTest extends TestCase
{
    public function testWorkOrderLifecycle(): void
    {
        $manager = $this->app->make(WorkOrderManager::class);

        // Create
        $workOrder = $manager->create(
            productId: 'PROD-001',
            quantity: 100.0,
            plannedStartDate: now(),
            plannedEndDate: now()->addDays(7)
        );

        // Release
        $manager->release($workOrder->getId());
        $workOrder = $manager->getById($workOrder->getId());
        $this->assertEquals('released', $workOrder->getStatus()->value);

        // Start
        $manager->start($workOrder->getId());
        $workOrder = $manager->getById($workOrder->getId());
        $this->assertEquals('in_progress', $workOrder->getStatus()->value);

        // Complete
        $manager->complete($workOrder->getId());
        $workOrder = $manager->getById($workOrder->getId());
        $this->assertEquals('completed', $workOrder->getStatus()->value);
    }
}
```

---

## Configuration

### Example Configuration File

```php
// config/manufacturing.php
return [
    'mrp' => [
        'default_lot_sizing' => 'economic_order_quantity',
        'default_bucket_size' => 7, // days
        'frozen_zone_days' => 14,
        'slushy_zone_days' => 28,
    ],
    
    'capacity' => [
        'default_load_type' => 'finite',
        'utilization_warning_threshold' => 80.0,
        'utilization_critical_threshold' => 95.0,
    ],
    
    'forecasting' => [
        'use_ml' => true,
        'fallback_to_historical' => true,
        'historical_periods' => 12, // months
        'minimum_confidence' => 0.5,
    ],
    
    'work_orders' => [
        'auto_generate_number' => true,
        'number_prefix' => 'WO',
        'allow_negative_completion' => false,
    ],
];
```

---

## Best Practices

1. **Always use interfaces** - Never depend on concrete implementations
2. **Implement tenant scoping** - All repositories should scope by tenant
3. **Handle events asynchronously** - Use queues for event processing
4. **Add monitoring** - Track key manufacturing KPIs
5. **Test integrations** - Write integration tests for cross-package workflows
6. **Cache frequently accessed data** - BOMs and Routings are read-heavy
