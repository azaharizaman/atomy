# Requirements: Manufacturing

**Package:** `Nexus\Manufacturing`  
**Total Requirements:** 48  
**Last Updated:** 2025-11-25

---

## Architectural Requirements (ARC)

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Architectural Requirement | ARC-MFG-0001 | Package MUST be framework-agnostic with no Laravel/Symfony dependencies | composer.json | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Architectural Requirement | ARC-MFG-0002 | Package MUST use PHP 8.3+ features (readonly, enums, match, constructor promotion) | All src/ files | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Architectural Requirement | ARC-MFG-0003 | All dependencies MUST be injected via constructor as interfaces | src/Services/ | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Architectural Requirement | ARC-MFG-0004 | Package MUST NOT contain database migrations or schema definitions | - | ⏳ Pending | Consumer responsibility | 2025-11-25 |
| `Nexus\Manufacturing` | Architectural Requirement | ARC-MFG-0005 | Package MUST be stateless - no session/request state stored | All src/ files | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Architectural Requirement | ARC-MFG-0006 | All persistence MUST be delegated via Repository interfaces | src/Contracts/ | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Architectural Requirement | ARC-MFG-0007 | Package MUST use PSR-3 LoggerInterface for logging | src/Services/ | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Architectural Requirement | ARC-MFG-0008 | Complex MRP/CRP algorithms MUST be in src/Core/Engine/ folder | src/Core/Engine/ | ⏳ Pending | Internal engine | 2025-11-25 |

---

## Business Requirements - Bill of Materials (BUS-BOM)

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Business Requirements | BUS-BOM-0001 | System MUST support multi-level BOMs with unlimited depth | src/Services/BomManager.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-BOM-0002 | System MUST detect and prevent circular BOM references using DFS | src/Core/Engine/BomExplosionEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-BOM-0003 | System MUST support phantom BOMs that inline sub-assemblies | src/Core/Engine/BomExplosionEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-BOM-0004 | System MUST support BOM versioning with effectivity dates (effectiveFrom/effectiveTo) | src/Contracts/EffectivityInterface.php | ⏳ Pending | ECO support | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-BOM-0005 | System MUST explode BOMs respecting effectivity dates at explosion time | src/Services/BomManager.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-BOM-0006 | System MUST support cost rollup calculation from component costs | src/Services/BomManager.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-BOM-0007 | System MUST convert component quantities using UoM converter | src/Services/BomManager.php | ⏳ Pending | nexus/uom integration | 2025-11-25 |

---

## Business Requirements - Engineering Change Orders (BUS-ECO)

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Business Requirements | BUS-ECO-0001 | System MUST support Engineering Change Orders for BOM/Routing modifications | src/Services/ChangeOrderManager.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-ECO-0002 | ECO MUST track effectiveFrom and effectiveTo dates for version transitions | src/ValueObjects/EffectivityPeriod.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-ECO-0003 | ECO workflow MUST follow draft→approved→released state machine | src/Services/ChangeOrderManager.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-ECO-0004 | System MUST publish ChangeOrderApprovedEvent when ECO is approved | src/Events/ChangeOrderApprovedEvent.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-ECO-0005 | System MUST prevent overlapping effectivity periods for same BOM/Routing | src/Services/ChangeOrderManager.php | ⏳ Pending | - | 2025-11-25 |

---

## Business Requirements - Work Orders (BUS-WO)

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Business Requirements | BUS-WO-0001 | Work Orders MUST follow FSM: planned→released→in_progress→completed/cancelled | src/Services/WorkOrderManager.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-WO-0002 | System MUST validate state transitions (e.g., cannot complete without releasing) | src/Services/WorkOrderManager.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-WO-0003 | System MUST reserve materials via Inventory interface when WO is released | src/Services/WorkOrderManager.php | ⏳ Pending | nexus/inventory integration | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-WO-0004 | System MUST track actual vs planned quantities and durations | src/Services/WorkOrderManager.php | ⏳ Pending | Variance analysis | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-WO-0005 | System MUST publish WorkOrderCreatedEvent, WorkOrderReleasedEvent, etc. | src/Events/ | ⏳ Pending | - | 2025-11-25 |

---

## Business Requirements - Routing (BUS-RTG)

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Business Requirements | BUS-RTG-0001 | Routings MUST be separate versioned entities reusable across products | src/Contracts/RoutingInterface.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-RTG-0002 | Routings MUST support versioning with effectivity dates like BOMs | src/Services/RoutingManager.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-RTG-0003 | Each routing MUST contain sequence of operations with work center assignments | src/ValueObjects/RoutingStep.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-RTG-0004 | Operations MUST define setup time, run time per unit, and teardown time | src/Contracts/OperationInterface.php | ⏳ Pending | - | 2025-11-25 |

---

## Business Requirements - MRP (BUS-MRP)

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Business Requirements | BUS-MRP-0001 | MRP MUST perform gross-to-net calculation against inventory levels | src/Core/Engine/MrpEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-MRP-0002 | MRP MUST support 5 lot-sizing strategies: LotForLot, FixedOrderQty, EOQ, POQ, MinMax | src/Core/Engine/LotSizingEngine.php | ⏳ Pending | Strategy pattern | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-MRP-0003 | MRP MUST offset requirements by lead times to calculate planned order dates | src/Core/Engine/MrpEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-MRP-0004 | MRP MUST generate exception reports for delays, overstocks, capacity issues | src/Core/Engine/MrpEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-MRP-0005 | MRP MUST use time-phased planning buckets (daily/weekly) | src/Core/Engine/MrpEngine.php | ⏳ Pending | - | 2025-11-25 |

---

## Business Requirements - Capacity Planning (BUS-CRP)

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Business Requirements | BUS-CRP-0001 | CRP MUST support configurable planning horizons with frozen/slushy/liquid zones | src/Core/Engine/CapacityPlanningEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-CRP-0002 | CRP MUST calculate work center utilization based on planned orders | src/Core/Engine/CapacityPlanningEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-CRP-0003 | CRP MUST detect bottleneck work centers and capacity overloads | src/Core/Engine/CapacityPlanningEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-CRP-0004 | CRP MUST auto-suggest resolutions: shift earlier, split WC, overtime, alternate routing | src/Core/Engine/CapacityResolutionEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Business Requirements | BUS-CRP-0005 | CRP MUST support finite and infinite loading modes | src/Core/Engine/CapacityPlanningEngine.php | ⏳ Pending | - | 2025-11-25 |

---

## Integration Requirements - ML Forecasting (INT-ML)

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Integration Requirement | INT-ML-0001 | MRP MUST consume DemandForecastInterface for predictive demand input | src/Core/Engine/MrpEngine.php | ⏳ Pending | nexus/machine-learning | 2025-11-25 |
| `Nexus\Manufacturing` | Integration Requirement | INT-ML-0002 | System MUST fall back to historical average when ML unavailable/low-confidence | src/Core/Engine/ForecastIntegrationEngine.php | ⏳ Pending | Graceful degradation | 2025-11-25 |
| `Nexus\Manufacturing` | Integration Requirement | INT-ML-0003 | System MUST publish ForecastFallbackUsedEvent when fallback is triggered | src/Events/ForecastFallbackUsedEvent.php | ⏳ Pending | Consumer awareness | 2025-11-25 |
| `Nexus\Manufacturing` | Integration Requirement | INT-ML-0004 | System MUST track forecast confidence levels for each prediction | src/ValueObjects/DemandForecast.php | ⏳ Pending | - | 2025-11-25 |

---

## Functional Requirements (FUN)

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Functional Requirement | FUN-MFG-0001 | Provide BomManagerInterface for BOM CRUD operations | src/Contracts/BomManagerInterface.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MFG-0002 | Provide WorkOrderManagerInterface for work order lifecycle | src/Contracts/WorkOrderManagerInterface.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MFG-0003 | Provide RoutingManagerInterface for routing operations | src/Contracts/RoutingManagerInterface.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MFG-0004 | Provide WorkCenterManagerInterface for work center capacity | src/Contracts/WorkCenterManagerInterface.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MFG-0005 | Provide MrpCalculatorInterface for MRP calculations | src/Contracts/MrpCalculatorInterface.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Functional Requirement | FUN-MFG-0006 | Provide CapacityPlannerInterface for CRP calculations | src/Contracts/CapacityPlannerInterface.php | ⏳ Pending | - | 2025-11-25 |

---

## Performance Requirements (PERF)

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Manufacturing` | Performance Requirement | PERF-MFG-0001 | BOM explosion MUST use O(n) DFS algorithm, not O(n²) | src/Core/Engine/BomExplosionEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Performance Requirement | PERF-MFG-0002 | MRP MUST support incremental/net-change regeneration | src/Core/Engine/MrpEngine.php | ⏳ Pending | - | 2025-11-25 |
| `Nexus\Manufacturing` | Performance Requirement | PERF-MFG-0003 | CRP MUST process 10,000+ planned orders within acceptable time | src/Core/Engine/CapacityPlanningEngine.php | ⏳ Pending | - | 2025-11-25 |

---

## Summary

| Category | Count | Status |
|----------|-------|--------|
| Architectural (ARC) | 8 | ⏳ Pending |
| Business - BOM (BUS-BOM) | 7 | ⏳ Pending |
| Business - ECO (BUS-ECO) | 5 | ⏳ Pending |
| Business - Work Orders (BUS-WO) | 5 | ⏳ Pending |
| Business - Routing (BUS-RTG) | 4 | ⏳ Pending |
| Business - MRP (BUS-MRP) | 5 | ⏳ Pending |
| Business - CRP (BUS-CRP) | 5 | ⏳ Pending |
| Integration - ML (INT-ML) | 4 | ⏳ Pending |
| Functional (FUN) | 6 | ⏳ Pending |
| Performance (PERF) | 3 | ⏳ Pending |
| **TOTAL** | **48** | **⏳ Pending** |
