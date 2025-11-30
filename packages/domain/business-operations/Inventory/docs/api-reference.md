# API Reference: Nexus Inventory

**Package:** `Nexus\Inventory`  
**Version:** 1.0.0  
**Namespace:** `Nexus\Inventory`

---

## Table of Contents

1. [Interfaces](#interfaces)
2. [Service Managers](#service-managers)
3. [Valuation Engines](#valuation-engines)
4. [Value Objects](#value-objects)
5. [Enums](#enums)
6. [Events](#events)
7. [Exceptions](#exceptions)

---

## Interfaces

### StockManagerInterface

**Purpose:** Primary interface for stock movements (receive, issue, adjust)

**Location:** `src/Contracts/StockManagerInterface.php`

```php
namespace Nexus\Inventory\Contracts;

use Nexus\Currency\ValueObjects\Money;

interface StockManagerInterface
{
    /**
     * Receive stock into warehouse (from purchase order, production, transfer)
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $quantity Quantity received (must be > 0)
     * @param Money $unitCost Unit cost of received stock
     * @param string|null $lotNumber Lot number (required if product uses lot tracking)
     * @param \DateTimeImmutable|null $expiryDate Lot expiry date (required for perishables)
     * @param string|null $reference Reference document (PO number, GRN number)
     * @param \DateTimeImmutable|null $receivedDate Date of receipt (defaults to now)
     * @return void
     * @throws ProductNotFoundException
     * @throws WarehouseNotFoundException
     * @throws InvalidQuantityException Quantity must be > 0
     * @throws LotRequiredException Product requires lot tracking but lotNumber not provided
     */
    public function receiveStock(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        Money $unitCost,
        ?string $lotNumber = null,
        ?\DateTimeImmutable $expiryDate = null,
        ?string $reference = null,
        ?\DateTimeImmutable $receivedDate = null
    ): void;

    /**
     * Issue stock from warehouse (for sales order, work order, scrap)
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $quantity Quantity to issue (must be > 0)
     * @param IssueReason $reason Issue reason (SALE, PRODUCTION, SCRAP, ADJUSTMENT)
     * @param string|null $reference Reference document (SO number, WO number)
     * @param \DateTimeImmutable|null $issuedDate Date of issue (defaults to now)
     * @return Money Cost of Goods Sold (COGS) calculated by valuation engine
     * @throws InsufficientStockException Available stock < quantity
     * @throws NegativeStockNotAllowedException Negative stock not allowed for product
     * @throws ProductNotFoundException
     * @throws WarehouseNotFoundException
     */
    public function issueStock(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        IssueReason $reason,
        ?string $reference = null,
        ?\DateTimeImmutable $issuedDate = null
    ): Money;

    /**
     * Adjust stock level (for cycle count, damage, found stock)
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $adjustmentQuantity Adjustment quantity (positive for increase, negative for decrease)
     * @param AdjustmentReason $reason Adjustment reason (CYCLE_COUNT, DAMAGE, FOUND, LOST, OBSOLESCENCE)
     * @param string|null $notes Free text notes
     * @param \DateTimeImmutable|null $adjustmentDate Date of adjustment (defaults to now)
     * @return void
     * @throws ProductNotFoundException
     * @throws WarehouseNotFoundException
     */
    public function adjustStock(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $adjustmentQuantity,
        AdjustmentReason $reason,
        ?string $notes = null,
        ?\DateTimeImmutable $adjustmentDate = null
    ): void;

    /**
     * Get available stock (quantity - reserved_quantity)
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @return float Available stock quantity
     */
    public function getAvailableStock(string $tenantId, string $productId, string $warehouseId): float;

    /**
     * Get total stock (including reserved)
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @return float Total stock quantity
     */
    public function getTotalStock(string $tenantId, string $productId, string $warehouseId): float;
}
```

---

### LotManagerInterface

**Purpose:** Lot tracking and FEFO enforcement

**Location:** `src/Contracts/LotManagerInterface.php`

```php
namespace Nexus\Inventory\Contracts;

interface LotManagerInterface
{
    /**
     * Create new lot for product
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param string $lotNumber Lot number (unique per tenant + product)
     * @param float $quantity Initial lot quantity
     * @param \DateTimeImmutable|null $expiryDate Expiry date (required for perishables)
     * @param \DateTimeImmutable|null $manufactureDate Manufacture date
     * @return string Lot ID (ULID)
     * @throws DuplicateLotNumberException Lot number already exists
     * @throws InvalidQuantityException Quantity must be > 0
     */
    public function createLot(
        string $tenantId,
        string $productId,
        string $lotNumber,
        float $quantity,
        ?\DateTimeImmutable $expiryDate = null,
        ?\DateTimeImmutable $manufactureDate = null
    ): string;

    /**
     * Allocate stock from lots using FEFO (First-Expiry-First-Out)
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param float $quantity Total quantity to allocate
     * @return array<LotAllocation> Array of lot allocations (LotAllocation VOs)
     * @throws InsufficientStockException Not enough stock across all lots
     */
    public function allocateFromLots(string $tenantId, string $productId, float $quantity): array;

    /**
     * Get available lots (quantity_remaining > 0, not expired)
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @return array<LotInterface> Array of available lots, ordered by expiry date ASC
     */
    public function getAvailableLots(string $tenantId, string $productId): array;

    /**
     * Check for expiring lots (expiry date within warning threshold)
     *
     * @param string $tenantId Tenant identifier
     * @param int $daysThreshold Warning threshold in days (default: 30)
     * @return array<LotInterface> Array of expiring lots
     */
    public function getExpiringLots(string $tenantId, int $daysThreshold = 30): array;
}
```

---

### SerialNumberManagerInterface

**Purpose:** Serial number tracking and uniqueness enforcement

**Location:** `src/Contracts/SerialNumberManagerInterface.php`

```php
namespace Nexus\Inventory\Contracts;

interface SerialNumberManagerInterface
{
    /**
     * Register serial number
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param string $serialNumber Serial number (max 100 chars, unique per tenant)
     * @param string|null $lotId Associated lot ID (if product uses lot tracking)
     * @param \DateTimeImmutable|null $manufactureDate Manufacture date
     * @return string Serial number ID (ULID)
     * @throws DuplicateSerialNumberException Serial already exists for tenant
     * @throws InvalidSerialNumberException Serial number exceeds 100 chars
     */
    public function registerSerial(
        string $tenantId,
        string $productId,
        string $serialNumber,
        ?string $lotId = null,
        ?\DateTimeImmutable $manufactureDate = null
    ): string;

    /**
     * Mark serial as issued (sold, shipped, consumed)
     *
     * @param string $tenantId Tenant identifier
     * @param string $serialNumber Serial number
     * @param string $reference Reference document (SO, DO, WO)
     * @param \DateTimeImmutable|null $issuedDate Issue date
     * @return void
     * @throws SerialNumberNotFoundException
     * @throws SerialAlreadyIssuedException
     */
    public function issueSerial(
        string $tenantId,
        string $serialNumber,
        string $reference,
        ?\DateTimeImmutable $issuedDate = null
    ): void;

    /**
     * Check if serial number exists and is available
     *
     * @param string $tenantId Tenant identifier
     * @param string $serialNumber Serial number
     * @return bool True if exists and not issued
     */
    public function isAvailable(string $tenantId, string $serialNumber): bool;

    /**
     * Get serial number history (receipt, issue, return)
     *
     * @param string $tenantId Tenant identifier
     * @param string $serialNumber Serial number
     * @return array<array{event: string, date: \DateTimeImmutable, reference: string}>
     */
    public function getHistory(string $tenantId, string $serialNumber): array;
}
```

---

### ReservationManagerInterface

**Purpose:** Stock reservations with auto-expiry

**Location:** `src/Contracts/ReservationManagerInterface.php`

```php
namespace Nexus\Inventory\Contracts;

interface ReservationManagerInterface
{
    /**
     * Reserve stock for sales order or work order
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $quantity Quantity to reserve
     * @param string $referenceType Reference type (SALES_ORDER, WORK_ORDER)
     * @param string $referenceId Reference document ID
     * @param int|null $ttlHours Time-to-live in hours (defaults to system config)
     * @return string Reservation ID (ULID)
     * @throws InsufficientStockException Available stock < quantity
     * @throws InvalidQuantityException Quantity must be > 0
     */
    public function reserve(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        string $referenceType,
        string $referenceId,
        ?int $ttlHours = null
    ): string;

    /**
     * Release reservation (order fulfilled or cancelled)
     *
     * @param string $reservationId Reservation ID
     * @param ReleaseReason $reason Reason for release (FULFILLED, CANCELLED, EXPIRED)
     * @return void
     * @throws ReservationNotFoundException
     * @throws ReservationAlreadyReleasedException
     */
    public function release(string $reservationId, ReleaseReason $reason): void;

    /**
     * Expire stale reservations (TTL exceeded)
     *
     * @param string $tenantId Tenant identifier
     * @return int Number of reservations expired
     */
    public function expireReservations(string $tenantId): int;

    /**
     * Get active reservations for product
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param string|null $warehouseId Warehouse filter (optional)
     * @return array<ReservationInterface> Active reservations
     */
    public function getActiveReservations(
        string $tenantId,
        string $productId,
        ?string $warehouseId = null
    ): array;
}
```

---

### TransferManagerInterface

**Purpose:** Inter-warehouse stock transfers with FSM workflow

**Location:** `src/Contracts/TransferManagerInterface.php`

```php
namespace Nexus\Inventory\Contracts;

interface TransferManagerInterface
{
    /**
     * Initiate stock transfer
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param string $fromWarehouseId Source warehouse
     * @param string $toWarehouseId Destination warehouse
     * @param float $quantity Transfer quantity
     * @param string|null $reason Transfer reason (REBALANCING, DEMAND, REPLENISHMENT)
     * @param \DateTimeImmutable|null $requestedDate Requested transfer date
     * @return string Transfer ID (ULID)
     * @throws InsufficientStockException Source warehouse has insufficient stock
     * @throws SameWarehouseTransferException From and To warehouse are identical
     * @throws InvalidQuantityException Quantity must be > 0
     */
    public function initiateTransfer(
        string $tenantId,
        string $productId,
        string $fromWarehouseId,
        string $toWarehouseId,
        float $quantity,
        ?string $reason = null,
        ?\DateTimeImmutable $requestedDate = null
    ): string;

    /**
     * Start shipment (pending → in_transit transition)
     *
     * @param string $transferId Transfer ID
     * @param \DateTimeImmutable|null $shippedDate Shipment date
     * @param string|null $trackingNumber Carrier tracking number
     * @return void
     * @throws TransferNotFoundException
     * @throws InvalidTransferStateException Transfer not in 'pending' state
     */
    public function startShipment(
        string $transferId,
        ?\DateTimeImmutable $shippedDate = null,
        ?string $trackingNumber = null
    ): void;

    /**
     * Complete transfer (in_transit → completed transition)
     * 
     * Decrements stock at source warehouse and increments at destination.
     *
     * @param string $transferId Transfer ID
     * @param \DateTimeImmutable|null $receivedDate Receipt date at destination
     * @return void
     * @throws TransferNotFoundException
     * @throws InvalidTransferStateException Transfer not in 'in_transit' state
     */
    public function completeTransfer(
        string $transferId,
        ?\DateTimeImmutable $receivedDate = null
    ): void;

    /**
     * Cancel transfer (any state → cancelled transition)
     * 
     * If in_transit, stock is returned to source warehouse.
     *
     * @param string $transferId Transfer ID
     * @param string|null $cancellationReason Reason for cancellation
     * @return void
     * @throws TransferNotFoundException
     * @throws TransferAlreadyCompletedException Cannot cancel completed transfers
     */
    public function cancelTransfer(string $transferId, ?string $cancellationReason = null): void;

    /**
     * Get transfer status
     *
     * @param string $transferId Transfer ID
     * @return TransferStatus Transfer status enum (PENDING, IN_TRANSIT, COMPLETED, CANCELLED)
     * @throws TransferNotFoundException
     */
    public function getStatus(string $transferId): TransferStatus;
}
```

---

## Service Managers

### StockManager
**Implementation:** `src/Services/StockManager.php`  
**Interface:** `StockManagerInterface`  
**Responsibilities:** Stock receive, issue, adjustment, availability calculation

### LotManager
**Implementation:** `src/Services/LotManager.php`  
**Interface:** `LotManagerInterface`  
**Responsibilities:** Lot creation, FEFO allocation, expiry tracking

### SerialNumberManager
**Implementation:** `src/Services/SerialNumberManager.php`  
**Interface:** `SerialNumberManagerInterface`  
**Responsibilities:** Serial registration, uniqueness enforcement, issue tracking

### ReservationManager
**Implementation:** `src/Services/ReservationManager.php`  
**Interface:** `ReservationManagerInterface`  
**Responsibilities:** Reservation creation, TTL enforcement, auto-expiry

### TransferManager
**Implementation:** `src/Services/TransferManager.php`  
**Interface:** `TransferManagerInterface`  
**Responsibilities:** Transfer FSM, state transitions, stock updates

---

## Valuation Engines

### ValuationEngineInterface

**Purpose:** Calculate COGS for stock issues

**Location:** `src/Contracts/ValuationEngineInterface.php`

```php
namespace Nexus\Inventory\Contracts;

use Nexus\Currency\ValueObjects\Money;

interface ValuationEngineInterface
{
    /**
     * Calculate Cost of Goods Sold (COGS) for stock issue
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param float $quantity Quantity issued
     * @return Money COGS amount
     */
    public function calculateCogs(string $tenantId, string $productId, float $quantity): Money;

    /**
     * Update valuation data after stock receipt
     *
     * @param string $tenantId Tenant identifier
     * @param string $productId Product identifier
     * @param float $quantity Quantity received
     * @param Money $unitCost Unit cost of received stock
     * @return void
     */
    public function updateOnReceipt(string $tenantId, string $productId, float $quantity, Money $unitCost): void;
}
```

### Implementations

#### FifoEngine
**Implementation:** `src/Core/Engine/FifoEngine.php`  
**Performance:** O(n) for stock issues  
**Best For:** Perishables, pharmaceuticals, food & beverage  
**Accuracy:** Matches physical flow of goods

#### WeightedAverageEngine
**Implementation:** `src/Core/Engine/WeightedAverageEngine.php`  
**Performance:** O(1) for both receipts and issues  
**Best For:** Commodities, bulk materials, chemicals  
**Calculation:** `new_avg_cost = ((old_qty × old_avg) + (new_qty × new_cost)) / (old_qty + new_qty)`

#### StandardCostEngine
**Implementation:** `src/Core/Engine/StandardCostEngine.php`  
**Performance:** O(1) for both receipts and issues  
**Best For:** Manufacturing, electronics, variance analysis  
**Behavior:** Uses fixed standard cost, ignores actual receipt costs

---

## Value Objects

### LotAllocation

**Purpose:** Represents allocation from a specific lot

**Location:** `src/ValueObjects/LotAllocation.php`

```php
namespace Nexus\Inventory\ValueObjects;

final readonly class LotAllocation
{
    public function __construct(
        public string $lotId,
        public string $lotNumber,
        public float $quantityAllocated,
        public ?\DateTimeImmutable $expiryDate = null
    ) {}
}
```

### StockMovement

**Purpose:** Immutable record of stock transaction

**Location:** `src/ValueObjects/StockMovement.php`

```php
namespace Nexus\Inventory\ValueObjects;

use Nexus\Currency\ValueObjects\Money;

final readonly class StockMovement
{
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $productId,
        public string $warehouseId,
        public MovementType $type,
        public float $quantity,
        public ?Money $unitCost,
        public ?string $reference,
        public \DateTimeImmutable $movementDate
    ) {}
}
```

---

## Enums

### IssueReason
**Location:** `src/Enums/IssueReason.php`

```php
enum IssueReason: string
{
    case SALE = 'sale';
    case PRODUCTION = 'production';
    case SCRAP = 'scrap';
    case ADJUSTMENT = 'adjustment';
}
```

### AdjustmentReason
**Location:** `src/Enums/AdjustmentReason.php`

```php
enum AdjustmentReason: string
{
    case CYCLE_COUNT = 'cycle_count';
    case DAMAGE = 'damage';
    case FOUND = 'found';
    case LOST = 'lost';
    case OBSOLESCENCE = 'obsolescence';
}
```

### TransferStatus
**Location:** `src/Enums/TransferStatus.php`

```php
enum TransferStatus: string
{
    case PENDING = 'pending';
    case IN_TRANSIT = 'in_transit';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
```

### ValuationMethod
**Location:** `src/Enums/ValuationMethod.php`

```php
enum ValuationMethod: string
{
    case FIFO = 'fifo';
    case WEIGHTED_AVERAGE = 'weighted_average';
    case STANDARD_COST = 'standard_cost';
}
```

---

## Events

All events published via `EventPublisherInterface`.

### StockReceivedEvent
**Triggered:** After stock receipt  
**GL Impact:** DR Inventory Asset / CR GR-IR Clearing

### StockIssuedEvent
**Triggered:** After stock issue  
**GL Impact:** DR COGS / CR Inventory Asset  
**Includes:** COGS amount

### StockAdjustedEvent
**Triggered:** After stock adjustment  
**GL Impact:** DR/CR Inventory Asset depending on adjustment type

### LotCreatedEvent
**Triggered:** After lot creation

### LotAllocatedEvent
**Triggered:** After FEFO allocation

### SerialRegisteredEvent
**Triggered:** After serial registration

### ReservationCreatedEvent
**Triggered:** After reservation created

### ReservationExpiredEvent
**Triggered:** After reservation auto-expires

---

## Exceptions

### InsufficientStockException
**Thrown by:** `StockManager::issueStock()`, `ReservationManager::reserve()`  
**Reason:** Available stock < requested quantity

### NegativeStockNotAllowedException
**Thrown by:** `StockManager::issueStock()`  
**Reason:** Issue would result in negative stock, not allowed for product

### DuplicateLotNumberException
**Thrown by:** `LotManager::createLot()`  
**Reason:** Lot number already exists for tenant + product

### DuplicateSerialNumberException
**Thrown by:** `SerialNumberManager::registerSerial()`  
**Reason:** Serial number already exists for tenant

### InvalidTransferStateException
**Thrown by:** `TransferManager::startShipment()`, `TransferManager::completeTransfer()`  
**Reason:** Transfer not in expected state for transition

### ProductNotFoundException
**Thrown by:** All managers  
**Reason:** Product ID not found

### WarehouseNotFoundException
**Thrown by:** `StockManager`, `TransferManager`  
**Reason:** Warehouse ID not found
