# Nexus Manufacturing Package

[![PHP Version](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-160%20passed-brightgreen.svg)]()

**A comprehensive, framework-agnostic manufacturing management package for PHP 8.3+**

The Manufacturing package provides enterprise-grade production management capabilities including Bill of Materials (BOM), Work Orders, Routings, Material Requirements Planning (MRP), and Capacity Requirements Planning (CRP).

---

## ✨ Features

### Core Capabilities

- **📋 Bill of Materials (BOM)**
  - Multi-level BOMs with unlimited nesting
  - Phantom (non-stocked) components
  - Versioning with effectivity dates
  - Cycle detection for circular references
  - Configurable BOMs for variants

- **🔄 Routings & Operations**
  - Operation sequencing with work centers
  - Setup, run, and queue times
  - Overlapping operations
  - Alternate routings
  - Subcontract operations

- **📝 Work Orders**
  - Full lifecycle management (Draft → Planned → Released → In Progress → Completed → Closed)
  - Material reservation and issue tracking
  - Operation completion recording
  - Scrap tracking
  - Sub-assemblies support

- **📊 MRP Engine**
  - Gross-to-net requirements calculation
  - Time-phased planning buckets
  - Multiple lot-sizing strategies:
    - Fixed Order Quantity
    - Economic Order Quantity (EOQ)
    - Period Order Quantity (POQ)
    - Least Unit Cost
  - Lead time offsetting
  - Net change vs regenerative modes

- **⚡ Capacity Planning**
  - Finite and infinite capacity loading
  - Work center calendar integration
  - Capacity profiles and utilization
  - Planning horizon zones (Frozen/Slushy/Liquid)
  - Intelligent overload resolution suggestions

- **🔮 Demand Forecasting**
  - ML-powered predictions integration
  - Historical fallback with confidence tracking
  - Seasonal adjustment support
  - Forecast accuracy metrics

---

## 📦 Installation

```bash
composer require nexus/manufacturing
```

---

## 🚀 Quick Start

### Basic BOM Management

```php
use Nexus\Manufacturing\Services\BomManager;
use Nexus\Manufacturing\ValueObjects\BomLine;

// Create BOM manager with repository dependency
$bomManager = new BomManager($bomRepository);

// Create a new BOM
$bom = $bomManager->create(
    productId: 'PROD-001',
    version: '1.0',
    type: 'manufacturing',
    lines: [],
    effectiveFrom: new DateTimeImmutable('2024-01-01')
);

// Add components
$bomManager->addLine($bom->getId(), new BomLine(
    productId: 'COMP-001',
    quantity: 2.0,
    uomCode: 'EA',
    lineNumber: 10,
    scrapFactor: 0.05
));

$bomManager->addLine($bom->getId(), new BomLine(
    productId: 'COMP-002',
    quantity: 1.5,
    uomCode: 'KG',
    lineNumber: 20,
));

// Explode BOM to get all materials
$materials = $bomManager->explode($bom->getId(), quantity: 100);
```

### Work Order Lifecycle

```php
use Nexus\Manufacturing\Services\WorkOrderManager;
use Nexus\Manufacturing\Enums\WorkOrderStatus;

$workOrderManager = new WorkOrderManager($repository, $bomManager, $routingManager);

// Create work order
$workOrder = $workOrderManager->create(
    productId: 'PROD-001',
    quantity: 100.0,
    plannedStartDate: new DateTimeImmutable('2024-02-01'),
    plannedEndDate: new DateTimeImmutable('2024-02-15'),
    bomId: 'bom-001',
    routingId: 'routing-001'
);

// Release for production
$workOrderManager->release($workOrder->getId());

// Start production
$workOrderManager->start($workOrder->getId());

// Record operation completion
$workOrderManager->completeOperation(
    workOrderId: $workOrder->getId(),
    operationSequence: 10,
    completedQty: 100.0,
    scrapQty: 2.0,
    laborHours: 8.5
);

// Complete and close
$workOrderManager->complete($workOrder->getId());
$workOrderManager->close($workOrder->getId());
```

### MRP Calculation

```php
use Nexus\Manufacturing\Services\MrpEngine;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Nexus\Manufacturing\Enums\LotSizingStrategy;

$mrpEngine = new MrpEngine(
    $bomManager,
    $inventoryProvider,
    $demandProvider,
    $plannedOrderRepository
);

// Define planning horizon
$horizon = new PlanningHorizon(
    startDate: new DateTimeImmutable('2024-01-01'),
    endDate: new DateTimeImmutable('2024-03-31'),
    bucketSize: 7, // Weekly buckets
    frozenDays: 14,
    slushyDays: 28
);

// Run MRP
$result = $mrpEngine->run(
    productIds: ['PROD-001', 'PROD-002'],
    horizon: $horizon,
    lotSizingStrategy: LotSizingStrategy::ECONOMIC_ORDER_QUANTITY
);

// Process planned orders
foreach ($result->getPlannedOrders() as $order) {
    echo sprintf(
        "Product: %s, Qty: %.2f, Due: %s\n",
        $order->getProductId(),
        $order->getQuantity(),
        $order->getDueDate()->format('Y-m-d')
    );
}
```

### Capacity Planning

```php
use Nexus\Manufacturing\Services\CapacityPlanner;
use Nexus\Manufacturing\Enums\CapacityLoadType;

$capacityPlanner = new CapacityPlanner(
    $workCenterRepository,
    $workOrderRepository,
    $routingManager,
    $capacityResolver
);

// Check capacity for work center
$profile = $capacityPlanner->getCapacityProfile(
    workCenterId: 'WC-001',
    startDate: new DateTimeImmutable('2024-02-01'),
    endDate: new DateTimeImmutable('2024-02-28'),
    loadType: CapacityLoadType::FINITE
);

// Get utilization by period
foreach ($profile->getPeriods() as $period) {
    echo sprintf(
        "%s: %.1f%% utilized\n",
        $period->getDate()->format('Y-m-d'),
        $period->getUtilizationPercent()
    );
}

// Get resolution suggestions for overloads
$suggestions = $capacityPlanner->getResolutionSuggestions('WC-001', $overloadPeriod);
foreach ($suggestions as $suggestion) {
    echo sprintf(
        "Action: %s, Estimated Savings: %.1f hours\n",
        $suggestion->getAction()->value,
        $suggestion->getEstimatedImpact()
    );
}
```

---

## 📂 Package Structure

```
src/
├── Contracts/           # 27 Interfaces
│   ├── BomInterface.php
│   ├── BomManagerInterface.php
│   ├── WorkOrderInterface.php
│   ├── MrpEngineInterface.php
│   └── ...
├── Enums/              # 8 Enums
│   ├── WorkOrderStatus.php
│   ├── BomType.php
│   ├── LotSizingStrategy.php
│   └── ...
├── ValueObjects/       # 13 Value Objects
│   ├── BomLine.php
│   ├── Operation.php
│   ├── PlannedOrder.php
│   └── ...
├── Exceptions/         # 13 Exceptions
│   ├── BomNotFoundException.php
│   ├── CircularBomException.php
│   └── ...
├── Events/            # 11 Domain Events
│   ├── WorkOrderReleasedEvent.php
│   └── ...
└── Services/          # 9 Service Classes
    ├── BomManager.php
    ├── WorkOrderManager.php
    ├── MrpEngine.php
    ├── CapacityPlanner.php
    └── ...
```

---

## 🔌 Integration with Nexus Packages

### Nexus\Inventory

```php
// Implement InventoryDataProviderInterface
class InventoryAdapter implements InventoryDataProviderInterface
{
    public function __construct(
        private readonly StockManagerInterface $stockManager
    ) {}
    
    public function getOnHandQuantity(string $productId, string $warehouseId): float
    {
        return $this->stockManager->getAvailableStock($productId, $warehouseId);
    }
}
```

### Nexus\MachineLearning

```php
// Implement ForecastProviderInterface for ML predictions
class MlForecastAdapter implements ForecastProviderInterface
{
    public function __construct(
        private readonly AnomalyDetectionServiceInterface $mlService
    ) {}
    
    public function getForecast(string $productId, DateTimeImmutable $date): ?DemandForecast
    {
        $prediction = $this->mlService->predict('demand_forecast', [
            'product_id' => $productId,
            'date' => $date->format('Y-m-d'),
        ]);
        
        return new DemandForecast(
            productId: $productId,
            forecastDate: $date,
            quantity: $prediction['quantity'],
            confidence: ForecastConfidence::from($prediction['confidence'])
        );
    }
}
```

---

## 📋 Requirements

- PHP 8.3 or higher
- PSR-4 autoloading
- PSR-3 Logger (optional)

### Required Dependencies
- `psr/log` ^3.0

### Suggested Dependencies
- `nexus/inventory` - For stock integration
- `nexus/product` - For product data
- `nexus/machine-learning` - For demand forecasting
- `nexus/event-stream` - For event sourcing

---

## 📖 Documentation

- [Getting Started](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Integration Guide](docs/integration-guide.md)
- [Examples](docs/examples/)

---

## 🧪 Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test -- --coverage-html coverage/
```

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## 🤝 Contributing

Contributions are welcome! Please read our contributing guidelines before submitting pull requests.

---

## 📚 References

- [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) - Implementation details
- [REQUIREMENTS.md](REQUIREMENTS.md) - Package requirements
- [TEST_SUITE_SUMMARY.md](TEST_SUITE_SUMMARY.md) - Test documentation
