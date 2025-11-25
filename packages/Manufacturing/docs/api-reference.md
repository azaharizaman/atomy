# API Reference: Nexus Manufacturing

Complete API documentation for all public interfaces in the Manufacturing package.

---

## Table of Contents

1. [BOM Management](#bom-management)
2. [Routing Management](#routing-management)
3. [Work Order Management](#work-order-management)
4. [MRP Engine](#mrp-engine)
5. [Capacity Planning](#capacity-planning)
6. [Demand Forecasting](#demand-forecasting)
7. [Enums](#enums)
8. [Value Objects](#value-objects)
9. [Exceptions](#exceptions)

---

## BOM Management

### BomManagerInterface

```php
namespace Nexus\Manufacturing\Contracts;

interface BomManagerInterface
{
    /**
     * Create a new Bill of Materials.
     */
    public function create(
        string $productId,
        string $version,
        string $type,
        array $lines = [],
        ?\DateTimeImmutable $effectiveFrom = null
    ): BomInterface;

    /**
     * Get BOM by ID.
     * @throws BomNotFoundException
     */
    public function getById(string $id): BomInterface;

    /**
     * Get effective BOM for product at given date.
     * @throws BomNotFoundException
     */
    public function getEffective(string $productId, ?\DateTimeImmutable $asOf = null): BomInterface;

    /**
     * Create new version from existing BOM.
     */
    public function createVersion(
        string $sourceBomId,
        string $newVersion,
        ?\DateTimeImmutable $effectiveFrom = null
    ): BomInterface;

    /**
     * Add line to BOM.
     * @throws CircularBomException
     */
    public function addLine(string $bomId, BomLine $line): void;

    /**
     * Update existing line.
     */
    public function updateLine(string $bomId, int $lineNumber, BomLine $line): void;

    /**
     * Remove line from BOM.
     */
    public function removeLine(string $bomId, int $lineNumber): void;

    /**
     * Explode BOM to get all materials recursively.
     * @return array<array{productId: string, quantity: float, level: int}>
     */
    public function explode(string $bomId, float $quantity = 1.0): array;

    /**
     * Validate BOM structure.
     * @return array<string> List of validation errors
     */
    public function validate(string $bomId): array;

    /**
     * Release BOM for production use.
     */
    public function release(string $bomId): void;

    /**
     * Mark BOM as obsolete.
     */
    public function obsolete(string $bomId): void;
}
```

### BomInterface

```php
interface BomInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getProductId(): string;
    public function getVersion(): int;
    public function getType(): string;
    public function getName(): string;
    public function getOutputQuantity(): float;
    public function getOutputUom(): string;
    public function getLines(): array;
    public function getEffectiveFrom(): ?\DateTimeImmutable;
    public function getEffectiveTo(): ?\DateTimeImmutable;
    public function isEffective(\DateTimeImmutable $asOf): bool;
    public function isLatestVersion(): bool;
    public function getCreatedAt(): \DateTimeImmutable;
    public function getUpdatedAt(): \DateTimeImmutable;
    public function getStatus(): string;
}
```

---

## Routing Management

### RoutingManagerInterface

```php
interface RoutingManagerInterface
{
    public function create(
        string $productId,
        string $version,
        array $operations = [],
        ?\DateTimeImmutable $effectiveFrom = null
    ): RoutingInterface;

    public function getById(string $id): RoutingInterface;
    public function getEffective(string $productId, ?\DateTimeImmutable $asOf = null): RoutingInterface;

    public function createVersion(
        string $sourceRoutingId,
        string $newVersion,
        ?\DateTimeImmutable $effectiveFrom = null
    ): RoutingInterface;

    public function addOperation(string $routingId, Operation $operation): void;
    public function updateOperation(string $routingId, int $sequence, Operation $operation): void;
    public function removeOperation(string $routingId, int $sequence): void;

    public function calculateTotalTime(string $routingId, float $quantity = 1.0): array;
    public function validate(string $routingId): array;
    public function release(string $routingId): void;
    public function obsolete(string $routingId): void;
}
```

### RoutingInterface

```php
interface RoutingInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getProductId(): string;
    public function getVersion(): int;
    public function getOperations(): array;
    public function getEffectiveFrom(): ?\DateTimeImmutable;
    public function getEffectiveTo(): ?\DateTimeImmutable;
    public function isEffective(\DateTimeImmutable $asOf): bool;
    public function getStatus(): string;
}
```

---

## Work Order Management

### WorkOrderManagerInterface

```php
interface WorkOrderManagerInterface
{
    public function create(
        string $productId,
        float $quantity,
        \DateTimeImmutable $plannedStartDate,
        \DateTimeImmutable $plannedEndDate,
        ?string $bomId = null,
        ?string $routingId = null,
        ?string $sourceReference = null
    ): WorkOrderInterface;

    public function getById(string $id): WorkOrderInterface;
    public function getByNumber(string $number): WorkOrderInterface;

    // Status transitions
    public function release(string $workOrderId): void;
    public function start(string $workOrderId): void;
    public function complete(string $workOrderId): void;
    public function close(string $workOrderId): void;
    public function cancel(string $workOrderId, string $reason): void;
    public function hold(string $workOrderId, string $reason): void;
    public function resume(string $workOrderId): void;

    // Material operations
    public function issueMaterial(
        string $workOrderId,
        string $productId,
        float $quantity,
        ?string $lotNumber = null,
        ?string $serialNumber = null
    ): void;

    public function returnMaterial(
        string $workOrderId,
        string $productId,
        float $quantity,
        string $reason
    ): void;

    // Operation recording
    public function completeOperation(
        string $workOrderId,
        int $operationSequence,
        float $completedQty,
        float $scrapQty = 0.0,
        float $laborHours = 0.0,
        ?string $notes = null
    ): void;

    // Queries
    public function findByStatus(WorkOrderStatus $status): array;
    public function findByProduct(string $productId): array;
    public function findByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array;
}
```

### WorkOrderStatus Enum

```php
enum WorkOrderStatus: string
{
    case DRAFT = 'draft';
    case PLANNED = 'planned';
    case RELEASED = 'released';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';
    case ON_HOLD = 'on_hold';

    public function canTransitionTo(self $target): bool;
    public function getAvailableTransitions(): array;
}
```

---

## MRP Engine

### MrpEngineInterface

```php
interface MrpEngineInterface
{
    /**
     * Run MRP calculation.
     */
    public function run(
        array $productIds,
        PlanningHorizon $horizon,
        LotSizingStrategy $lotSizingStrategy = LotSizingStrategy::FIXED_ORDER_QUANTITY,
        bool $regenerate = false
    ): MrpResult;

    /**
     * Run MRP for single product.
     */
    public function runForProduct(
        string $productId,
        PlanningHorizon $horizon,
        LotSizingStrategy $lotSizingStrategy = LotSizingStrategy::FIXED_ORDER_QUANTITY
    ): MrpResult;

    /**
     * Get planned orders for product.
     */
    public function getPlannedOrders(string $productId): array;

    /**
     * Firm a planned order (convert to work order).
     */
    public function firmPlannedOrder(string $plannedOrderId): WorkOrderInterface;
}
```

### LotSizingStrategy Enum

```php
enum LotSizingStrategy: string
{
    case FIXED_ORDER_QUANTITY = 'fixed_order_quantity';
    case ECONOMIC_ORDER_QUANTITY = 'economic_order_quantity';
    case PERIOD_ORDER_QUANTITY = 'period_order_quantity';
    case LEAST_UNIT_COST = 'least_unit_cost';

    public function getDescription(): string;
    public function requiresOrderQuantity(): bool;
    public function requiresPeriodCount(): bool;
}
```

---

## Capacity Planning

### CapacityPlannerInterface

```php
interface CapacityPlannerInterface
{
    public function getCapacityProfile(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        CapacityLoadType $loadType = CapacityLoadType::FINITE
    ): CapacityProfile;

    public function checkAvailability(
        string $workCenterId,
        \DateTimeImmutable $date,
        float $requiredHours
    ): bool;

    public function calculateLoad(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    public function getResolutionSuggestions(
        string $workCenterId,
        \DateTimeImmutable $overloadDate
    ): array;

    public function getAllCapacityProfiles(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;
}
```

### CapacityLoadType Enum

```php
enum CapacityLoadType: string
{
    case FINITE = 'finite';
    case INFINITE = 'infinite';
}
```

---

## Demand Forecasting

### ForecastProviderInterface

```php
interface ForecastProviderInterface
{
    public function getForecast(
        string $productId,
        \DateTimeImmutable $date
    ): ?DemandForecast;

    public function getForecasts(
        string $productId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;
}
```

### ForecastConfidence Enum

```php
enum ForecastConfidence: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case FALLBACK = 'fallback';
    case UNKNOWN = 'unknown';

    public function getMinConfidencePercent(): int;
    public function isReliable(): bool;
}
```

---

## Value Objects

### BomLine

```php
final readonly class BomLine
{
    public function __construct(
        public string $productId,
        public float $quantity,
        public string $uomCode,
        public int $lineNumber,
        public ?string $positionNumber = null,
        public float $scrapFactor = 0.0,
        public ?\DateTimeImmutable $effectiveFrom = null,
        public ?\DateTimeImmutable $effectiveTo = null,
        public bool $isPhantom = false,
        public ?string $notes = null
    );

    public function getExtendedQuantity(float $parentQuantity): float;
    public function isEffective(\DateTimeImmutable $asOf): bool;
    public function toArray(): array;
}
```

### Operation

```php
final readonly class Operation
{
    public function __construct(
        public int $sequence,
        public string $workCenterId,
        public string $description,
        public float $setupTime,
        public float $runTime,
        public float $queueTime = 0.0,
        public float $moveTime = 0.0,
        public OperationType $type = OperationType::PRODUCTION,
        public bool $overlapping = false,
        public ?int $overlapQuantity = null,
        public ?string $notes = null
    );

    public function getTotalTime(float $quantity): float;
    public function toArray(): array;
}
```

### PlannedOrder

```php
final readonly class PlannedOrder
{
    public function __construct(
        public string $productId,
        public float $quantity,
        public \DateTimeImmutable $dueDate,
        public \DateTimeImmutable $startDate,
        public string $source,
        public ?string $parentOrderId = null
    );

    public function toArray(): array;
}
```

### PlanningHorizon

```php
final readonly class PlanningHorizon
{
    public function __construct(
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public int $bucketSize = 7,
        public int $frozenDays = 14,
        public int $slushyDays = 28
    );

    public function getZone(\DateTimeImmutable $date): PlanningZone;
    public function getBuckets(): array;
    public function isInHorizon(\DateTimeImmutable $date): bool;
}
```

---

## Exceptions

| Exception | When Thrown |
|-----------|-------------|
| `BomNotFoundException` | BOM ID not found |
| `CircularBomException` | Circular reference detected in BOM |
| `InvalidBomVersionException` | Version conflict or invalid version |
| `RoutingNotFoundException` | Routing ID not found |
| `InvalidRoutingVersionException` | Version conflict or invalid routing |
| `WorkOrderNotFoundException` | Work order ID not found |
| `InvalidWorkOrderStatusException` | Invalid status transition |
| `WorkCenterNotFoundException` | Work center ID not found |
| `CapacityExceededException` | Capacity limits exceeded |
| `InsufficientMaterialException` | Not enough materials available |
| `MrpCalculationException` | MRP calculation failed |
| `ForecastUnavailableException` | Forecast data unavailable |
| `ChangeOrderNotFoundException` | Change order ID not found |

---

## Domain Events

| Event | Description |
|-------|-------------|
| `BomCreatedEvent` | New BOM created |
| `BomReleasedEvent` | BOM released for production |
| `WorkOrderCreatedEvent` | Work order created |
| `WorkOrderReleasedEvent` | Work order released |
| `WorkOrderStartedEvent` | Production started |
| `WorkOrderCompletedEvent` | Production completed |
| `MaterialIssuedEvent` | Material issued to work order |
| `OperationCompletedEvent` | Operation completed |
| `PlannedOrderCreatedEvent` | MRP planned order created |
| `CapacityOverloadEvent` | Capacity overload detected |
| `ForecastFallbackUsedEvent` | ML forecast unavailable, using fallback |
