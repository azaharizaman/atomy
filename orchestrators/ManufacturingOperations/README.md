# Manufacturing Operations Orchestrator

Production-ready orchestrator for manufacturing operations. Coordinates production lifecycles, BOM reconciliation, costing, quality control, and work center management.

## Features

- **Production Planning**: Create planned orders with BOM validation and cost estimation.
- **Order Release**: Automatic stock availability checks and reservation.
- **Production Execution**: Track progress and consume materials.
- **Quality Integration**: Enforce quality inspections before completion.
- **Completion & Costing**: Receive finished goods and record actual costs.

## Architecture

This package follows the Nexus Architecture Layer 2 (Orchestrator) pattern.
It defines its own required interfaces (Providers) in `src/Contracts/Providers/`.

### Interfaces

- `ManufacturingProviderInterface`: Work Order and Operation management.
- `BomProviderInterface`: BOM explosion and validation.
- `InventoryProviderInterface`: Stock checks, reservations, issues, and receipts.
- `QualityProviderInterface`: Inspection management.
- `CostingProviderInterface`: Cost estimation and recording.

## Usage

```php
use Nexus\Orchestrators\ManufacturingOperations\Services\ManufacturingOrchestrator;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\ProductionOrderRequest;

// 1. Plan Production
$request = new ProductionOrderRequest(
    productId: 'P-100',
    quantity: 50.0,
    dueDate: new \DateTimeImmutable('+2 weeks')
);
$order = $orchestrator->planProduction($tenantId, $request);

// 2. Release Order (Reserves Stock)
$releasedOrder = $orchestrator->releaseOrder($tenantId, $order->id);

// 3. Complete Order (Check Quality, Issue Stock, Receive Goods)
$completedOrder = $orchestrator->completeOrder($tenantId, $order->id);
```

## Testing

Run tests from the project root:

```bash
vendor/bin/phpunit orchestrators/ManufacturingOperations/tests
```
