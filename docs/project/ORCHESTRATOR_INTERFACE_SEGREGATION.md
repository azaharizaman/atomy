# Orchestrator Interface Segregation Pattern

## Overview

This document defines the architectural pattern for how Orchestrators (Layer 2) interact with Atomic Packages (Layer 1) through **orchestrator-defined interfaces**, ensuring complete decoupling and enabling implementation swappability via Adapters (Layer 3).

---

## 1. The Problem

In a modular monorepo, allowing Orchestrators to directly depend on Atomic Package interfaces creates several issues:

### 1.1 Tight Coupling
```php
// ❌ BAD: Orchestrator directly depends on Inventory package interface
use Nexus\Inventory\Contracts\StockManagerInterface;

class ReplenishmentCoordinator
{
    public function __construct(
        private StockManagerInterface $stockManager // Tightly coupled!
    ) {}
}
```

**Problems:**
- Orchestrator cannot be published standalone
- Changes to Inventory package interfaces break Orchestrator
- Cannot swap Inventory implementation without modifying Orchestrator
- Violates Dependency Inversion Principle (DIP)

### 1.2 Atomic Package Pollution
When Orchestrators need specialized methods, the temptation is to add them to Atomic Package interfaces:

```php
// ❌ BAD: Adding orchestrator-specific methods to atomic package interface
interface StockManagerInterface
{
    // Core methods...
    public function getCurrentStock(string $productId, string $warehouseId): float;
    
    // Added just for RMA workflow - pollutes the interface!
    public function receiveReturn(string $tenantId, string $productId, ...): void;
    public function writeOff(string $tenantId, string $productId, ...): void;
}
```

**Problems:**
- Violates Interface Segregation Principle (ISP)
- Atomic package becomes aware of use-case specific requirements
- Other consumers of Inventory package are forced to implement unused methods
- Atomic package loses its "atomic" nature

---

## 2. The Solution: Orchestrator Interface Segregation

### 2.1 Core Principle

**Orchestrators define their own interfaces for what they need.**

```
┌─────────────────────────────────────────────────────────────────┐
│                    Layer 3: Adapters                            │
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐  │
│   │  SupplyChainStockManagerAdapter                         │  │
│   │  implements: SupplyChainStockManagerInterface           │  │
│   │  uses: Nexus\Inventory\Contracts\StockManagerInterface  │  │
│   └─────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │ implements
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Layer 2: Orchestrators                       │
│                                                                 │
│   orchestrators/SupplyChainOperations/src/Contracts/           │
│   ├── SupplyChainStockManagerInterface.php    <-- Own interface│
│   ├── SupplyChainTransferManagerInterface.php                  │
│   ├── SupplyChainReceivableManagerInterface.php                │
│   ├── WarehouseInfoInterface.php                               │
│   └── ...                                                      │
│                                                                 │
│   Dependencies in composer.json:                                │
│   - php: ^8.3                                                  │
│   - psr/log: ^3.0                                              │
│   - psr/event-dispatcher: ^1.0                                 │
└─────────────────────────────────────────────────────────────────┘
                              │ uses (via interfaces)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Layer 1: Atomic Packages                     │
│                                                                 │
│   packages/Inventory/src/Contracts/                            │
│   ├── StockManagerInterface.php                                │
│   ├── TransferManagerInterface.php                             │
│   └── ...                                                      │
│                                                                 │
│   No knowledge of orchestrator requirements                     │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Implementation Example

#### Step 1: Orchestrator Defines Its Interface

```php
// orchestrators/SupplyChainOperations/src/Contracts/SupplyChainStockManagerInterface.php
<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface SupplyChainStockManagerInterface
{
    public function getCurrentStock(string $productId, string $warehouseId): float;

    public function adjustStock(
        string $productId,
        string $warehouseId,
        float $adjustmentQty,
        string $reason
    ): void;

    public function receiveReturn(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        ?string $reference = null
    ): void;

    public function writeOff(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        string $reason,
        ?string $reference = null
    ): void;
}
```

#### Step 2: Orchestrator Uses Its Interface

```php
// orchestrators/SupplyChainOperations/src/Workflows/Rma/RmaWorkflow.php
<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\Rma;

use Nexus\SupplyChainOperations\Contracts\SupplyChainStockManagerInterface;
use Nexus\SupplyChainOperations\Contracts\SupplyChainReceivableManagerInterface;
use Nexus\SupplyChainOperations\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

final readonly class RmaWorkflow
{
    public function __construct(
        private SupplyChainStockManagerInterface $stockManager,
        private SupplyChainReceivableManagerInterface $receivableManager,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}
    
    // Implementation uses only orchestrator interfaces...
}
```

#### Step 3: Orchestrator composer.json (PSR-only dependencies)

```json
{
    "name": "nexus/supply-chain-operations",
    "require": {
        "php": "^8.3",
        "psr/log": "^3.0",
        "psr/event-dispatcher": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Nexus\\SupplyChainOperations\\": "src/"
        }
    }
}
```

#### Step 4: Adapter Implements Orchestrator Interface

```php
// adapters/Laravel/SupplyChainOperations/src/Adapters/SupplyChainStockManagerAdapter.php
<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperationsAdapter\Adapters;

use Nexus\SupplyChainOperations\Contracts\SupplyChainStockManagerInterface;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Inventory\Enums\IssueReason;

final readonly class SupplyChainStockManagerAdapter implements SupplyChainStockManagerInterface
{
    public function __construct(
        private StockManagerInterface $inventoryStockManager
    ) {}

    public function getCurrentStock(string $productId, string $warehouseId): float
    {
        return $this->inventoryStockManager->getCurrentStock($productId, $warehouseId);
    }

    public function adjustStock(
        string $productId,
        string $warehouseId,
        float $adjustmentQty,
        string $reason
    ): void
    {
        $this->inventoryStockManager->adjustStock(
            $productId,
            $warehouseId,
            $adjustmentQty,
            $reason
        );
    }

    public function receiveReturn(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        ?string $reference = null
    ): void
    {
        // Adapter handles the translation between orchestrator needs
        // and atomic package capabilities
        $this->inventoryStockManager->quarantineStock(
            $productId,
            $warehouseId,
            $quantity
        );
        
        // Adapter can add additional behavior (e.g., tenant context)
        // that the atomic package doesn't handle directly
    }

    public function writeOff(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        string $reason,
        ?string $reference = null
    ): void
    {
        $this->inventoryStockManager->issueStock(
            $productId,
            $warehouseId,
            $quantity,
            IssueReason::from($reason),
            $reference
        );
    }
}
```

---

## 3. Interface Naming Conventions

### 3.1 Orchestrator Interfaces

| Pattern | Example | Use Case |
|---------|---------|----------|
| `{Orchestrator}{Domain}ManagerInterface` | `SupplyChainStockManagerInterface` | Manager/Service contracts |
| `{Orchestrator}{Entity}Interface` | `WarehouseInfoInterface` | Entity-like data contracts |
| `{Orchestrator}{Entity}ProviderInterface` | `StockLevelProviderInterface` | Repository/fetcher contracts |
| `{Orchestrator}{Action}Interface` | `AuditLoggerInterface` | Action-specific contracts |

### 3.2 Examples

```php
// Manager interfaces - for services that perform actions
SupplyChainStockManagerInterface
SupplyChainTransferManagerInterface
SupplyChainReceivableManagerInterface

// Info interfaces - for read-only entity data
WarehouseInfoInterface
SalesOrderInterface
PurchaseOrderInterface

// Provider interfaces - for fetching data
StockLevelProviderInterface
WarehouseRepositoryInterface
LocationProviderInterface
GoodsReceiptProviderInterface

// Action interfaces - for specific operations
AuditLoggerInterface
DropshipDataProviderInterface
```

---

## 4. Dependency Rules

### 4.1 Allowed Dependencies by Layer

| Layer | Can Depend On | Cannot Depend On |
|-------|---------------|------------------|
| **Atomic Packages** | Common, PSR interfaces | Other atomic packages, Orchestrators, Adapters |
| **Orchestrators** | PSR interfaces, Own interfaces | Atomic packages, Adapters, Frameworks |
| **Adapters** | Everything | Nothing (they are the leaf) |

### 4.2 Dependency Graph

```
                    ┌─────────────┐
                    │   Common    │
                    └──────┬──────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
┌───────────────┐  ┌───────────────┐  ┌───────────────┐
│   Package A   │  │   Package B   │  │   Package C   │
│   (Atomic)    │  │   (Atomic)    │  │   (Atomic)    │
└───────────────┘  └───────────────┘  └───────────────┘
                           │
                           │ (via Orchestrator interfaces)
                           ▼
                  ┌───────────────┐
                  │ Orchestrator  │
                  │  (Own Ifaces) │
                  └───────┬───────┘
                          │
                          │ (implements)
                          ▼
                  ┌───────────────┐
                  │    Adapter    │
                  │ (Glue code)   │
                  └───────────────┘
```

---

## 5. Benefits

### 5.1 Publishability
- Orchestrators can be published as standalone composer packages
- No forced dependencies on specific atomic package implementations
- Users can choose their own implementation stack

### 5.2 Swappability
- Change from one ERP implementation to another by swapping adapters
- Test with mock implementations without touching production code
- A/B test different implementations

### 5.3 SOLID Compliance
| Principle | How Pattern Helps |
|-----------|-------------------|
| **S**ingle Responsibility | Each interface serves one orchestrator's needs |
| **O**pen/Closed | Add new methods to orchestrator interface without affecting atomic packages |
| **L**iskov Substitution | Any adapter implementation can be substituted |
| **I**nterface Segregation | Interfaces contain only methods the orchestrator needs |
| **D**ependency Inversion | Orchestrators depend on abstractions (own interfaces), not concretions |

### 5.4 Testability
```php
// Unit testing orchestrator with simple mocks
$mockStockManager = $this->createMock(SupplyChainStockManagerInterface::class);
$mockStockManager->expects($this->once())
    ->method('adjustStock')
    ->with('product-001', 'warehouse-001', 10.0, 'rma_restock');

$workflow = new RmaWorkflow(
    $mockStockManager,
    $mockReceivableManager,
    $mockAuditLogger,
    $mockLogger
);
```

---

## 6. Decision Checklist

When developing in Orchestrators, use this checklist:

- [ ] Does my orchestrator need to interact with an atomic package?
- [ ] Have I defined an interface in `orchestrators/{Name}/src/Contracts/`?
- [ ] Does my composer.json only require `php` and `psr/*` packages?
- [ ] Am I NOT importing from `packages/*` or `Nexus\{Package}\Contracts\*`?
- [ ] Will the adapter layer be able to implement my interface using atomic packages?
- [ ] Is my interface name following the naming convention?

---

## 7. Common Patterns

### 7.1 Entity Facade Pattern

When orchestrator needs entity-like data from multiple packages:

```php
// Orchestrator interface
interface SalesOrderInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getCustomerId(): string;
    public function getShippingAddress(): ?string;
    public function getLines(): array;
}

// Adapter implementation
final readonly class SalesOrderAdapter implements SalesOrderInterface
{
    public function __construct(
        private Nexus\Sales\Contracts\SalesOrderInterface $salesOrder
    ) {}

    public function getId(): string
    {
        return $this->salesOrder->getId();
    }
    
    // ... delegate to atomic package entity
}
```

### 7.2 Composite Adapter Pattern

When orchestrator operation requires multiple atomic packages:

```php
// Orchestrator interface
interface SupplyChainReceivableManagerInterface
{
    public function createCreditNote(
        string $tenantId,
        string $customerId,
        string $salesOrderId,
        float $amount,
        string $reason,
        ?string $reference = null
    ): string;
}

// Adapter composes multiple atomic packages
final readonly class SupplyChainReceivableManagerAdapter implements SupplyChainReceivableManagerInterface
{
    public function __construct(
        private ReceivableManagerInterface $receivableManager,
        private EventDispatcherInterface $eventDispatcher,
        private TenantContextInterface $tenantContext
    ) {}

    public function createCreditNote(...): string
    {
        // Set tenant context
        $this->tenantContext->setCurrentTenant($tenantId);
        
        // Use atomic package
        $creditNote = $this->receivableManager->createCreditNote(...);
        
        // Dispatch events
        $this->eventDispatcher->dispatch(new CreditNoteCreatedEvent($creditNote));
        
        return $creditNote->getId();
    }
}
```

---

## 8. Anti-Patterns to Avoid

### 8.1 Don't: Direct Atomic Package Import

```php
// ❌ NEVER do this in an orchestrator
use Nexus\Inventory\Contracts\StockManagerInterface;

class MyCoordinator
{
    public function __construct(
        private StockManagerInterface $stockManager
    ) {}
}
```

### 8.2 Don't: Extend Atomic Package Interfaces

```php
// ❌ NEVER do this
interface SupplyChainStockManagerInterface extends StockManagerInterface
{
    // This creates inheritance coupling!
}
```

### 8.3 Don't: Add Orchestrator Methods to Atomic Interfaces

```php
// ❌ NEVER do this in atomic package
interface StockManagerInterface
{
    // Core inventory methods...
    
    // This should NOT be here - it's RMA workflow specific
    public function receiveReturnForRma(...): void;
}
```

---

## 9. Migration Guide

For existing code that violates this pattern:

### Step 1: Identify Violations
```bash
# Find orchestrators importing from packages
grep -r "use Nexus\\\\" orchestrators/*/src/ | grep -v "Contracts"
```

### Step 2: Create Orchestrator Interface
- Copy the minimum required method signatures
- Place in `orchestrators/{Name}/src/Contracts/`
- Follow naming conventions

### Step 3: Update Orchestrator Code
- Replace atomic package imports with orchestrator interfaces
- Update constructor dependencies
- Update composer.json to remove atomic package dependencies

### Step 4: Create Adapter
- Create in `adapters/{Framework}/SupplyChainOperationsAdapter/`
- Implement orchestrator interface
- Delegate to atomic packages

### Step 5: Wire Up in Service Provider
```php
// Laravel example
$this->app->bind(
    SupplyChainStockManagerInterface::class,
    SupplyChainStockManagerAdapter::class
);
```

---

## 10. References

- [ARCHITECTURE.md](../ARCHITECTURE.md) - Core architectural guidelines
- [NEXUS_PACKAGES_REFERENCE.md](NEXUS_PACKAGES_REFERENCE.md) - Package inventory
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Dependency Inversion Principle](https://en.wikipedia.org/wiki/Dependency_inversion_principle)
- [Interface Segregation Principle](https://en.wikipedia.org/wiki/Interface_segregation_principle)
