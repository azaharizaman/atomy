# SupplyChainOperations Orchestrator

The `SupplyChainOperations` orchestrator is responsible for coordinating end-to-end business processes that span across Procurement, Inventory, Warehouse, and Sales domains.

## 🎯 Key Responsibilities

### Phase 1: Advanced Operational Flows ✅
- **Dropshipping Orchestration (SC-1.1)**: Automatically create vendor POs when dropship sales orders are confirmed.
- **Landed Cost Capitalization (SC-1.2)**: Bridge freight/tariff invoices to inventory receipts for true COGS accuracy.

### Phase 2: Intelligence & Optimization ✅
- **Predictive Replenishment (SC-2.1)**: ML-based dynamic reorder points using demand forecasting.
- **Dynamic Lead Time / ATP (SC-2.2)**: Real-time Available-to-Promise calculations using ProcurementML analytics.

### Phase 3: Lifecycle & Logistical Integrity (Planned)
- **RMA & Reverse Supply Chain (SC-3.1)**: End-to-end return lifecycle orchestration.
- **Regional Multi-Warehouse Balancing (SC-3.2)**: Optimize stock distribution across warehouses.

## 🏗️ Architecture

This package follows the **Nexus Three-Layer Architecture**:

- **Coordinators**: Stateless services for synchronous cross-domain orchestration.
- **Listeners**: Reactive event subscribers that trigger business logic based on domain events.
- **Workflows**: Stateful Sagas for long-running supply chain processes.
- **Value Objects**: Immutable data structures (e.g., `AvailableToPromiseResult`).

**Framework-Agnostic**: This orchestrator contains no framework-specific code. DI registration and event wiring should be done in the **adapters/** layer (Layer 3).

## 📦 Dependencies

### Required
- `Nexus\Inventory`: Stock management, demand forecasting.
- `Nexus\Procurement`: Purchase orders, vendor management.
- `Nexus\Sales`: Sales orders, fulfillment.
- `Nexus\AuditLogger`: Cross-domain audit trails.

### Optional (for Phase 2 features)
- `Nexus\ProcurementML`: Delivery analytics for ATP calculations.
- `Nexus\Payable`: Invoice processing for landed costs.

## 🚀 Getting Started

### Coordinators

**DropshipCoordinator** - Create dropship purchase orders:
```php
$poId = $dropshipCoordinator->createDropshipPo($salesOrder, $lines, $vendorId);
```

**LandedCostCoordinator** - Capitalize costs onto inventory:
```php
$coordinator->distributeLandedCost($grnId, $amount, 'value');
```

**ReplenishmentCoordinator** - ML-enhanced replenishment:
```php
// Traditional threshold-based
$reqId = $coordinator->createAutoRequisition($tenantId, $warehouseId, $requesterId, $replenishmentMap);

// ML-based forecast evaluation
$evaluation = $coordinator->evaluateProductWithForecast($tenantId, $productId, $warehouseId);
```

**DynamicLeadTimeCoordinator** - Available-to-Promise calculations:
```php
$atpResult = $coordinator->calculateAtpDate($productId, $quantity, $warehouseId, $preferredVendorId);
// Returns: promised date, confidence score, lead time breakdown
```

### Event Listeners

Register these listeners in your application's event dispatcher:

- `DropshipListener` - Triggered on `SalesOrderConfirmedEvent`
- `DropshipFulfillmentListener` - Triggered on `GoodsReceiptCreatedEvent`
- `LandedCostListener` - Triggered on `InvoiceApprovedForPaymentEvent`
- `StockLevelListener` - Triggered on `StockIssuedEvent` and `StockAdjustedEvent`

### Dependency Injection

**Note**: This orchestrator is framework-agnostic. You must register services in your **adapters/** layer:

```php
// Example: Laravel ServiceProvider in adapters/
$this->app->singleton(DropshipCoordinator::class);
$this->app->singleton(LandedCostCoordinator::class);
$this->app->singleton(ReplenishmentCoordinator::class);
$this->app->singleton(DynamicLeadTimeCoordinator::class);

// Event wiring
Event::listen(SalesOrderConfirmedEvent::class, DropshipListener::class);
Event::listen(GoodsReceiptCreatedEvent::class, DropshipFulfillmentListener::class);
```
