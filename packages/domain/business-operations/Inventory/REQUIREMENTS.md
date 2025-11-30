# Requirements: Inventory

**Total Requirements:** 92

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Inventory` | Architectural Requirement | ARC-INV-0001 | Package MUST be framework-agnostic | composer.json | ✅ Complete | Zero framework deps | 2024-11-25 |
| `Nexus\Inventory` | Architectural Requirement | ARC-INV-0002 | All persistence MUST be via repository interfaces | src/Contracts/ | ✅ Complete | 9 interfaces defined | 2024-11-25 |
| `Nexus\Inventory` | Architectural Requirement | ARC-INV-0003 | All dependencies MUST be injected via constructor | src/Services/ | ✅ Complete | All readonly properties | 2024-11-25 |
| `Nexus\Inventory` | Architectural Requirement | ARC-INV-0004 | Package MUST use `declare(strict_types=1)` in all files | src/**/*.php | ✅ Complete | Strict typing enforced | 2024-11-25 |
| `Nexus\Inventory` | Architectural Requirement | ARC-INV-0005 | Optional dependencies MUST be declared in `suggest` section | composer.json | ✅ Complete | event-stream, machine-learning | 2024-11-25 |
| `Nexus\Inventory` | Architectural Requirement | ARC-INV-0006 | Package MUST require PHP ^8.3 | composer.json | ✅ Complete | PHP 8.3+ required | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1001 | System MUST support FIFO valuation method | src/Core/Engine/FifoEngine.php | ✅ Complete | Queue-based cost layers | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1002 | System MUST support Weighted Average valuation method | src/Core/Engine/WeightedAverageEngine.php | ✅ Complete | Running average formula | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1003 | System MUST support Standard Cost valuation method | src/Core/Engine/StandardCostEngine.php | ✅ Complete | Fixed cost + variance | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1004 | System MUST allow valuation method configuration per product | src/Contracts/ConfigurationInterface.php | ✅ Complete | getValuationMethod() | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1005 | System MUST prevent negative stock when configured | src/Services/StockManager.php | ✅ Complete | Throws exception | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1006 | System MUST allow negative stock when configured | src/Services/StockManager.php | ✅ Complete | Configurable via ConfigurationInterface | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1007 | System MUST track stock movements with reason codes | src/Enums/MovementType.php, IssueReason.php | ✅ Complete | Enums defined | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1008 | System MUST support multi-warehouse stock levels | src/Contracts/StockLevelRepositoryInterface.php | ✅ Complete | warehouse_id in methods | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1009 | System MUST calculate available quantity as (quantity - reserved_quantity) | src/Contracts/StockLevelRepositoryInterface.php | ✅ Complete | Business rule documented | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1010 | System MUST publish domain events for GL integration | src/Events/ | ✅ Complete | 8 events defined | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1011 | StockReceivedEvent MUST include total value for GL posting | src/Events/StockReceivedEvent.php | ✅ Complete | totalValue property | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1012 | StockIssuedEvent MUST include COGS for GL posting | src/Events/StockIssuedEvent.php | ✅ Complete | cogs property | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1013 | System MUST support tenant-scoped operations | All repository interfaces | ✅ Complete | tenantId in all methods | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1014 | Lot tracking MUST enforce FEFO (First-Expiry-First-Out) | src/Services/LotManager.php | ✅ Complete | allocateLotsForIssue() | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1015 | System MUST prioritize lots with earliest expiry when issuing stock | src/Services/LotManager.php | ✅ Complete | FEFO queue | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1016 | System MUST validate lot expiry dates | src/ValueObjects/LotNumber.php | ✅ Complete | isExpired() method | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1017 | System MUST prevent issuing from expired lots | src/Services/LotManager.php | ✅ Complete | Validation in allocate | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1018 | Serial numbers MUST be unique per tenant | src/Services/SerialManager.php | ✅ Complete | Uniqueness check | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1019 | System MUST track serial number status | src/Services/SerialManager.php | ✅ Complete | updateSerialStatus() | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1020 | System MUST support serial number allocation and deallocation | src/Services/SerialManager.php | ✅ Complete | allocate/deallocate | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1021 | Stock reservations MUST have configurable TTL | src/Services/ReservationManager.php | ✅ Complete | ConfigurationInterface | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1022 | System MUST auto-expire reservations after TTL | src/Services/ReservationManager.php | ✅ Complete | expireReservations() | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1023 | System MUST publish ReservationExpiredEvent on expiry | src/Events/ReservationExpiredEvent.php | ✅ Complete | Event defined | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1024 | Stock transfers MUST follow FSM workflow (pending→in_transit→completed/cancelled) | src/Services/TransferManager.php | ✅ Complete | State machine | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1025 | System MUST validate transfer quantity availability | src/Services/TransferManager.php | ✅ Complete | Validation logic | 2024-11-25 |
| `Nexus\Inventory` | Business Requirements | BUS-INV-1026 | System MUST update stock levels on transfer completion | src/Services/TransferManager.php | ✅ Complete | completeTransfer() | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2001 | StockManager MUST provide receiveStock() method | src/Services/StockManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2002 | StockManager MUST provide issueStock() method | src/Services/StockManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2003 | StockManager MUST provide adjustStock() method | src/Services/StockManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2004 | StockManager MUST provide getStockLevel() method | src/Services/StockManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2005 | StockManager MUST provide getAvailableStock() method | src/Services/StockManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2006 | receiveStock() MUST calculate total value using valuation engine | src/Services/StockManager.php | ✅ Complete | Engine delegation | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2007 | issueStock() MUST calculate COGS using valuation engine | src/Services/StockManager.php | ✅ Complete | Engine delegation | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2008 | receiveStock() MUST publish StockReceivedEvent | src/Services/StockManager.php | ✅ Complete | Event publishing | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2009 | issueStock() MUST publish StockIssuedEvent | src/Services/StockManager.php | ✅ Complete | Event publishing | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2010 | adjustStock() MUST publish StockAdjustedEvent | src/Services/StockManager.php | ✅ Complete | Event publishing | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2011 | FifoEngine MUST provide processReceipt() method | src/Core/Engine/FifoEngine.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2012 | FifoEngine MUST provide calculateCOGS() method | src/Core/Engine/FifoEngine.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2013 | FifoEngine MUST store cost layers in CostLayerStorage | src/Core/Engine/FifoEngine.php | ✅ Complete | Storage interface | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2014 | FifoEngine MUST consume cost layers in FIFO order | src/Core/Engine/FifoEngine.php | ✅ Complete | Queue algorithm | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2015 | WeightedAverageEngine MUST calculate running average | src/Core/Engine/WeightedAverageEngine.php | ✅ Complete | Formula implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2016 | WeightedAverageEngine formula MUST be: newAvg = (oldAvg * oldQty + receiptCost * receiptQty) / (oldQty + receiptQty) | src/Core/Engine/WeightedAverageEngine.php | ✅ Complete | Correct formula | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2017 | StandardCostEngine MUST retrieve standard cost from StandardCostStorage | src/Core/Engine/StandardCostEngine.php | ✅ Complete | Storage interface | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2018 | StandardCostEngine MUST track cost variance | src/Core/Engine/StandardCostEngine.php | ✅ Complete | Variance calculation | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2019 | StandardCostEngine MUST allow standard cost updates | src/Core/Engine/StandardCostEngine.php | ✅ Complete | Update method | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2020 | LotManager MUST provide createLot() method | src/Services/LotManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2021 | LotManager MUST provide allocateLotsForIssue() method | src/Services/LotManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2022 | LotManager MUST provide adjustLotQuantity() method | src/Services/LotManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2023 | allocateLotsForIssue() MUST return lots ordered by expiry date (FEFO) | src/Services/LotManager.php | ✅ Complete | FEFO queue | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2024 | LotNumber MUST provide isExpired() method | src/ValueObjects/LotNumber.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2025 | LotNumber MUST provide daysUntilExpiry() method | src/ValueObjects/LotNumber.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2026 | SerialManager MUST provide allocateSerial() method | src/Services/SerialManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2027 | SerialManager MUST provide deallocateSerial() method | src/Services/SerialManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2028 | SerialManager MUST provide updateSerialStatus() method | src/Services/SerialManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2029 | allocateSerial() MUST throw DuplicateSerialException if serial exists | src/Services/SerialManager.php | ✅ Complete | Exception thrown | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2030 | SerialNumber MUST enforce max 100 characters | src/ValueObjects/SerialNumber.php | ✅ Complete | Validation | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2031 | ReservationManager MUST provide reserveStock() method | src/Services/ReservationManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2032 | ReservationManager MUST provide releaseReservation() method | src/Services/ReservationManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2033 | ReservationManager MUST provide expireReservations() method | src/Services/ReservationManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2034 | reserveStock() MUST throw InsufficientStockException if not enough available | src/Services/ReservationManager.php | ✅ Complete | Exception thrown | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2035 | reserveStock() MUST calculate expires_at as current time + TTL | src/Services/ReservationManager.php | ✅ Complete | TTL calculation | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2036 | expireReservations() MUST publish ReservationExpiredEvent for each expired reservation | src/Services/ReservationManager.php | ✅ Complete | Event publishing | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2037 | TransferManager MUST provide initiateTransfer() method | src/Services/TransferManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2038 | TransferManager MUST provide startTransfer() method | src/Services/TransferManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2039 | TransferManager MUST provide completeTransfer() method | src/Services/TransferManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2040 | TransferManager MUST provide cancelTransfer() method | src/Services/TransferManager.php | ✅ Complete | Method implemented | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2041 | initiateTransfer() MUST set status to pending | src/Services/TransferManager.php | ✅ Complete | FSM state | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2042 | startTransfer() MUST transition status from pending to in_transit | src/Services/TransferManager.php | ✅ Complete | FSM transition | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2043 | completeTransfer() MUST transition status from in_transit to completed | src/Services/TransferManager.php | ✅ Complete | FSM transition | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2044 | completeTransfer() MUST decrement stock at source warehouse | src/Services/TransferManager.php | ✅ Complete | Stock update | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2045 | completeTransfer() MUST increment stock at destination warehouse | src/Services/TransferManager.php | ✅ Complete | Stock update | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2046 | completeTransfer() MUST publish StockTransferredEvent | src/Services/TransferManager.php | ✅ Complete | Event publishing | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2047 | ConfigurationInterface MUST provide getValuationMethod() | src/Contracts/ConfigurationInterface.php | ✅ Complete | Method defined | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2048 | ConfigurationInterface MUST provide isNegativeStockAllowed() | src/Contracts/ConfigurationInterface.php | ✅ Complete | Method defined | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2049 | ConfigurationInterface MUST provide getReservationTTL() | src/Contracts/ConfigurationInterface.php | ✅ Complete | Method defined | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2050 | EventPublisherInterface MUST provide publish() method | src/Contracts/EventPublisherInterface.php | ✅ Complete | Method defined | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2051 | StockReceivedEvent MUST include productId, warehouseId, quantity, unitCost, totalValue | src/Events/StockReceivedEvent.php | ✅ Complete | Properties defined | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2052 | StockIssuedEvent MUST include productId, warehouseId, quantity, cogs, reason | src/Events/StockIssuedEvent.php | ✅ Complete | Properties defined | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2053 | Package MUST throw InsufficientStockException when stock < issue quantity | src/Exceptions/InsufficientStockException.php | ✅ Complete | Exception defined | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2054 | Package MUST throw NegativeStockNotAllowedException when configured | src/Exceptions/NegativeStockNotAllowedException.php | ✅ Complete | Exception defined | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2055 | Package MUST throw LotNotFoundException when lot not found | src/Exceptions/LotNotFoundException.php | ✅ Complete | Exception defined | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2056 | Package MUST throw SerialNotFoundException when serial not found | src/Exceptions/SerialNotFoundException.php | ✅ Complete | Exception defined | 2024-11-25 |
| `Nexus\Inventory` | Functional Requirement | FUN-INV-2057 | Package MUST throw DuplicateSerialException when serial already exists | src/Exceptions/DuplicateSerialException.php | ✅ Complete | Exception defined | 2024-11-25 |
| `Nexus\Inventory` | Performance Requirement | PERF-INV-3001 | WeightedAverageEngine MUST perform O(1) processReceipt operation | src/Core/Engine/WeightedAverageEngine.php | ✅ Complete | Algorithm verified | 2024-11-25 |
| `Nexus\Inventory` | Performance Requirement | PERF-INV-3002 | WeightedAverageEngine MUST perform O(1) calculateCOGS operation | src/Core/Engine/WeightedAverageEngine.php | ✅ Complete | Algorithm verified | 2024-11-25 |
| `Nexus\Inventory` | Performance Requirement | PERF-INV-3003 | StandardCostEngine MUST perform O(1) processReceipt operation | src/Core/Engine/StandardCostEngine.php | ✅ Complete | Algorithm verified | 2024-11-25 |
| `Nexus\Inventory` | Performance Requirement | PERF-INV-3004 | StandardCostEngine MUST perform O(1) calculateCOGS operation | src/Core/Engine/StandardCostEngine.php | ✅ Complete | Algorithm verified | 2024-11-25 |
| `Nexus\Inventory` | Performance Requirement | PERF-INV-3005 | FifoEngine MUST perform O(1) processReceipt operation | src/Core/Engine/FifoEngine.php | ✅ Complete | Insert to queue | 2024-11-25 |
| `Nexus\Inventory` | Performance Requirement | PERF-INV-3006 | FifoEngine calculateCOGS MUST be O(n) where n = number of cost layers consumed | src/Core/Engine/FifoEngine.php | ✅ Complete | Queue consumption | 2024-11-25 |
