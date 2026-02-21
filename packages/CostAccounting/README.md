# Nexus\CostAccounting

Enterprise-grade cost accounting and management package for the Nexus ERP system.

## Overview

The **Nexus\CostAccounting** package provides comprehensive cost management capabilities including cost center management, cost pool aggregation, product costing, and cost allocation. It serves as the **cost control layer** that enables organizations to track, allocate, and analyze costs across their operational units using both traditional costing and Activity-Based Costing (ABC) methodologies.

## Key Features

### 🎯 Core Capabilities

- **Cost Center Management**: Hierarchical organizational units for cost collection and responsibility tracking
- **Cost Pool Aggregation**: Indirect cost pooling for overhead allocation
- **Cost Element Classification**: Material, Labor, and Overhead categorization
- **Product Cost Calculation**: Multi-level cost rollup with standard and actual cost tracking
- **Cost Allocation**: Multiple allocation methods (Direct, Step-Down, Reciprocal) with ABC support
- **Variance Analysis**: Tracking and reporting of actual versus standard cost variances

### 🔄 Cost Allocation Methods

- **Direct Allocation**: Allocates costs directly from source to receiving cost centers based on allocation ratios
- **Step-Down Allocation**: Allocates costs sequentially from service cost centers to production cost centers
- **Reciprocal Allocation**: Allocates costs using simultaneous equations to handle reciprocal relationships

### 📊 Product Costing

- **Standard Costing**: Pre-determined costs for planning and control
- **Actual Costing**: Real costs incurred for accurate product valuation
- **Multi-Level Rollup**: Cost accumulation through bill of materials hierarchies
- **Unit Cost Calculation**: Per-unit cost determination for pricing and profitability

### 🔐 Financial Controls

- **Tenant Isolation**: Multi-tenant data security with per-tenant cost center hierarchies
- **Period-Based Costing**: Fiscal period validation and cost tracking
- **Budget Integration**: Cost center linkage to budget management
- **GL Mapping**: Integration with chart of accounts for cost posting

## Architecture

### Framework Agnosticism

This package contains **pure PHP logic** and is completely framework-agnostic:

- ✅ No Laravel dependencies in `/src`
- ✅ All dependencies via contracts (interfaces)
- ✅ Readonly constructor property promotion
- ✅ Native PHP 8.3 enums with business logic
- ✅ Immutable value objects
- ✅ PSR-3 logging, PSR-14 event dispatching

### Directory Structure

```
src/
├── Contracts/
│   ├── CostAccountingManagerInterface.php     # Primary facade
│   ├── CostCenterManagerInterface.php        # Cost center management
│   ├── CostCenterQueryInterface.php           # CQRS: Read operations
│   ├── CostCenterPersistInterface.php         # CQRS: Write operations
│   ├── CostPoolQueryInterface.php            # Pool queries
│   ├── CostPoolPersistInterface.php           # Pool persistence
│   ├── ProductCostCalculatorInterface.php     # Product costing
│   ├── CostAllocationEngineInterface.php      # Allocation execution
│   └── Integration/                           # External package integrations
├── Entities/
│   ├── CostCenter.php                         # Cost center entity
│   ├── CostPool.php                           # Cost pool entity
│   ├── CostElement.php                        # Cost element entity
│   ├── ProductCost.php                        # Product cost entity
│   └── CostAllocationRule.php                 # Allocation rule entity
├── Enums/
│   ├── AllocationMethod.php                   # Direct, Step-Down, Reciprocal
│   ├── CostElementType.php                   # Material, Labor, Overhead
│   └── CostCenterStatus.php                  # Active, Inactive, Pending
├── ValueObjects/
│   ├── CostAmount.php                         # Immutable monetary amount
│   ├── CostAllocationRatio.php               # Validated allocation percentages
│   ├── CostCenterHierarchy.php               # Tree structure
│   ├── ProductCostSnapshot.php                # Historical cost capture
│   ├── CostVarianceBreakdown.php             # Variance analysis
│   └── ActivityDriver.php                     # ABC driver
├── Services/
│   ├── CostAccountingManager.php              # Main orchestrator
│   ├── CostCenterManager.php                  # Center management
│   ├── ProductCostCalculator.php              # Cost calculations
│   └── CostAllocationEngine.php              # Allocation execution
├── Exceptions/
│   ├── CostAccountingException.php            # Base exception
│   ├── CostCenterNotFoundException.php        # Not found
│   ├── InvalidAllocationRuleException.php     # Validation
│   ├── AllocationCycleDetectedException.php   # Circular dependency
│   ├── InsufficientCostPoolException.php      # Pool balance
│   └── ProductCostNotFoundException.php       # Not found
└── Events/
    └── CostCenterCreatedEvent.php             # Domain event
```

### Integration Points

| Package | Integration Purpose |
|---------|-------------------|
| **Nexus\Inventory** | Material cost data, inventory valuation |
| **Nexus\Manufacturing** | Production data, work in progress |
| **Nexus\ChartOfAccount** | GL account mapping |
| **Nexus\Budget** | Cost center budgets |
| **Nexus\Finance** | Cost posting to general ledger |
| **Nexus\Period** | Fiscal period validation |
| **Nexus\Tenant** | Multi-entity isolation |
| **Nexus\Currency** | Multi-currency support |

## Installation

### 1. Add to Root Composer

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/CostAccounting"
        }
    ]
}
```

### 2. Install Package

```bash
composer require nexus/cost-accounting:"*@dev"
```

### 3. Register Service Provider (Laravel)

In `apps/Atomy/config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\CostAccountingServiceProvider::class,
],
```

### 4. Run Migrations

```bash
php artisan migrate
```

## Quick Start

### Create a Cost Center

```php
use Nexus\CostAccounting\Contracts\CostAccountingManagerInterface;
use Nexus\CostAccounting\Enums\CostCenterStatus;

$manager = app(CostAccountingManagerInterface::class);

$costCenter = $manager->createCostCenter([
    'code' => 'CC-MFG-001',
    'name' => 'Manufacturing Unit 1',
    'description' => 'Main manufacturing facility',
    'tenant_id' => '01TENANT...',
    'status' => CostCenterStatus::Active,
    'cost_center_type' => 'production',
    'responsible_person_id' => '01USER...',
]);
```

### Create a Cost Pool

```php
use Nexus\CostAccounting\Enums\AllocationMethod;

$pool = $manager->createCostPool([
    'code' => 'CP-OVERHEAD-001',
    'name' => 'Factory Overhead',
    'description' => 'Indirect manufacturing costs',
    'cost_center_id' => $costCenter->getId(),
    'period_id' => '01PERIOD-2026-Q1',
    'tenant_id' => '01TENANT...',
    'allocation_method' => AllocationMethod::StepDown,
    'total_amount' => 50000.00,
]);
```

### Allocate Pool Costs

```php
use Nexus\CostAccounting\Exceptions\AllocationCycleDetectedException;
use Nexus\CostAccounting\Exceptions\InvalidAllocationRuleException;

try {
    $allocationResult = $manager->allocatePoolCosts(
        poolId: $pool->getId(),
        periodId: '01PERIOD-2026-Q1'
    );
    
    foreach ($allocationResult['allocations'] as $costCenterId => $amount) {
        echo "Allocated {$amount} to cost center {$costCenterId}";
    }
} catch (AllocationCycleDetectedException $e) {
    echo "Circular allocation detected: " . $e->getMessage();
} catch (InvalidAllocationRuleException $e) {
    echo "Invalid allocation rule: " . $e->getMessage();
}
```

### Calculate Product Cost

```php
$productCost = $manager->calculateProductCost(
    productId: '01PRODUCT-A001',
    periodId: '01PERIOD-2026-Q1',
    costType: 'standard'
);

echo "Material: " . $productCost->getMaterialCost();
echo "Labor: " . $productCost->getLaborCost();
echo "Overhead: " . $productCost->getOverheadCost();
echo "Total: " . $productCost->getTotalCost();
```

### Perform Cost Rollup

```php
$snapshot = $manager->performCostRollup(
    productId: '01PRODUCT-A001',
    periodId: '01PERIOD-2026-Q1'
);

echo "Level {$snapshot->getLevel()}: Total Cost = {$snapshot->getTotalCost()}";
echo "Unit Cost: {$snapshot->getUnitCost()}";
```

### Calculate Variances

```php
$variance = $manager->calculateVariances(
    productId: '01PRODUCT-A001',
    periodId: '01PERIOD-2026-Q1'
);

echo "Price Variance: {$variance->getPriceVariance()}";
echo "Rate Variance: {$variance->getRateVariance()}";
echo "Efficiency Variance: {$variance->getEfficiencyVariance()}";
echo "Total Variance: {$variance->getTotalVariance()}";

if ($variance->isFavorable()) {
    echo "Variance is favorable (costs under standard)";
} else {
    echo "Variance is unfavorable (costs over standard)";
}
```

### Get Cost Center Hierarchy

```php
$hierarchy = $manager->getCostCenterHierarchy();

$roots = $hierarchy->getRootCostCenters();
foreach ($roots as $root) {
    echo "Root: {$root->getName()}";
    
    $children = $hierarchy->getChildren($root->getId());
    foreach ($children as $child) {
        echo "  Child: {$child->getName()}";
    }
}
```

## Key Interfaces

### CostAccountingManagerInterface

Primary facade providing access to all cost accounting operations:

```php
createCostCenter(array $data): CostCenter
updateCostCenter(string $costCenterId, array $data): CostCenter
getCostCenterHierarchy(?string $rootCostCenterId = null): CostCenterHierarchy
createCostPool(array $data): CostPool
allocatePoolCosts(string $poolId, string $periodId): array
calculateProductCost(string $productId, string $periodId, string $costType): ProductCost
performCostRollup(string $productId, string $periodId): ProductCostSnapshot
calculateVariances(string $productId, string $periodId): CostVarianceBreakdown
```

### CostAllocationEngineInterface

Executes cost allocation from pools to receiving cost centers:

```php
allocate(CostPool $pool, string $periodId): array
validateAllocationRules(CostPool $pool): array
detectCircularDependencies(CostAllocationRule $rule): bool
calculateActivityRates(string $costCenterId, string $periodId): array
allocateStepDown(CostPool $pool, string $periodId, array $order): array
allocateReciprocal(array $pools, string $periodId): array
```

### ProductCostCalculatorInterface

Handles product cost calculations:

```php
calculate(string $productId, string $periodId, string $costType): ProductCost
calculateStandardCost(string $productId, string $periodId): ProductCost
calculateActualCost(string $productId, string $periodId): ProductCost
rollup(string $productId, string $periodId): ProductCostSnapshot
calculateUnitCost(string $productId, string $periodId, float $quantity): float
```

### CostCenterManagerInterface

Cost center CRUD operations:

```php
create(array $data): CostCenter
update(string $costCenterId, array $data): CostCenter
delete(string $costCenterId): void
updateStatus(string $costCenterId, CostCenterStatus $status): void
assignParent(string $costCenterId, ?string $parentCostCenterId): void
linkBudget(string $costCenterId, string $budgetId): void
```

## Domain Model

### Entities

| Entity | Description |
|--------|-------------|
| [`CostCenter`](src/Entities/CostCenter.php) | Organizational unit for cost collection with hierarchical support |
| [`CostPool`](src/Entities/CostPool.php) | Aggregates indirect costs for allocation |
| [`CostElement`](src/Entities/CostElement.php) | Categorizes costs (Material, Labor, Overhead) |
| [`ProductCost`](src/Entities/ProductCost.php) | Stores calculated product costs with rollup info |
| [`CostAllocationRule`](src/Entities/CostAllocationRule.php) | Defines allocation from pools to centers |

### Value Objects

| Value Object | Purpose |
|--------------|---------|
| [`CostAmount`](src/ValueObjects/CostAmount.php) | Immutable monetary amount with arithmetic |
| [`CostAllocationRatio`](src/ValueObjects/CostAllocationRatio.php) | Validated allocation percentages (must sum to 100%) |
| [`CostCenterHierarchy`](src/ValueObjects/CostCenterHierarchy.php) | Tree structure for hierarchical centers |
| [`ProductCostSnapshot`](src/ValueObjects/ProductCostSnapshot.php) | Point-in-time cost capture |
| [`CostVarianceBreakdown`](src/ValueObjects/CostVarianceBreakdown.php) | Variance analysis (price, rate, efficiency) |
| [`ActivityDriver`](src/ValueObjects/ActivityDriver.php) | ABC allocation driver |

### Enums

| Enum | Values |
|------|--------|
| [`AllocationMethod`](src/Enums/AllocationMethod.php) | Direct, StepDown, Reciprocal |
| [`CostElementType`](src/Enums/CostElementType.php) | Material, Labor, Overhead |
| [`CostCenterStatus`](src/Enums/CostCenterStatus.php) | Active, Inactive, Pending |

## Exceptions

| Exception | Description |
|-----------|-------------|
| [`CostAccountingException`](src/Exceptions/CostAccountingException.php) | Base exception |
| [`CostCenterNotFoundException`](src/Exceptions/CostCenterNotFoundException.php) | Cost center not found |
| [`InvalidAllocationRuleException`](src/Exceptions/InvalidAllocationRuleException.php) | Invalid allocation rule |
| [`AllocationCycleDetectedException`](src/Exceptions/AllocationCycleDetectedException.php) | Circular allocation dependency |
| [`InsufficientCostPoolException`](src/Exceptions/InsufficientCostPoolException.php) | Insufficient pool balance |
| [`CostPoolNotFoundException`](src/Exceptions/CostPoolNotFoundException.php) | Cost pool not found |
| [`ProductCostNotFoundException`](src/Exceptions/ProductCostNotFoundException.php) | Product cost not found |

## Configuration

### Settings (via Nexus\Setting)

```php
'cost_accounting.default_allocation_method' => 'step_down',
'cost_accounting.variance_investigation_threshold' => 5.0,      // Percentage
'cost_accounting.enable_abc' => true,
'cost_accounting.cost_calculation_timeout' => 30,             // Seconds
'cost_accounting.max_hierarchy_depth' => 10,
```

## Event Catalog

### Published Events

- `CostCenterCreatedEvent` - New cost center created

### Subscribed Events

- `Nexus\Period\Events\PeriodClosedEvent` - Process period-end allocations
- `Nexus\Inventory\Events\InventoryValuationUpdatedEvent` - Update material costs
- `Nexus\Manufacturing\Events\ProductionCompletedEvent` - Record actual costs

## Requirements

This package implements **78 requirements** covering:

- 6 Architectural Requirements (framework-agnostic, interfaces, immutability)
- 10 Business Requirements (cost centers, pools, allocation, product costing)
- 15 Functional Requirements (CRUD, calculations, reporting)
- 8 Integration Requirements (external package interfaces)
- 8 Interface Requirements (contracts)
- 6 Value Object Requirements
- 6 Exception Requirements
- 3 Security Requirements (tenant isolation, authorization, audit)
- 3 Performance Requirements
- 3 Reliability Requirements (deterministic, idempotent, rollback)
- 3 Documentation Requirements
- 3 Testing Requirements

For detailed requirements, see [REQUIREMENTS.md](REQUIREMENTS.md).

## Architecture (see ARCHITECTURE.md)

For detailed architecture documentation, see [ARCHITECTURE.md](ARCHITECTURE.md) which covers:

- Package overview and scope
- Architecture principles (framework-agnostic, DI, CQRS, immutability)
- Directory structure
- Entity relationships
- Service orchestration
- Integration patterns

## Development

### Testing

```bash
# Package tests (framework-agnostic)
vendor/bin/phpunit packages/CostAccounting/tests

# Atomy integration tests
php artisan test --filter CostAccounting
```

### Code Style

This package follows strict coding standards:

- PHP 8.3+ with strict typing
- `declare(strict_types=1)` in all files
- Readonly properties for immutability
- Constructor property promotion
- PSR-12 coding standards
- Comprehensive docblocks

## Documentation

### Package Documentation

- **[Architecture](ARCHITECTURE.md)** - Detailed architecture documentation
- **[Requirements](REQUIREMENTS.md)** - Complete requirements traceability (78 requirements)
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide
- **[API Reference](docs/api-reference.md)** - Complete API documentation

### Additional Resources

- `docs/examples/` - Usage examples
- See root `ARCHITECTURE.md` for overall system architecture
- See root `ACCOUNTING_OPERATIONS_FINAL_STRUCTURE.md` for orchestration context

## License

MIT License - See [LICENSE](LICENSE) file for details.

## Support

For questions or issues, please refer to the main Nexus documentation or contact the development team.
