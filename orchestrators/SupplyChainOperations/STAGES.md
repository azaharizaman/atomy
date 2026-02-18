# SupplyChainOperations: Development Roadmap (Phases)

This document outlines the phased development of the `SupplyChainOperations` orchestrator to achieve full modern ERP capabilities.

## Phase 1: Advanced Operational Flows (Baseline Enrichment) ✅
*Focus: Direct revenue generation and financial integrity.*

*   **[SC-1.1] Dropshipping Orchestration** ✅
    *   Coordinate `Sales` confirmation with `Procurement` direct-to-customer PO creation.
    *   Automate vendor shipment notifications back to `Sales` fulfillments.
    *   *Implementation: `DropshipCoordinator`, `DropshipListener`, `DropshipFulfillmentListener`*
*   **[SC-1.2] Landed Cost Capitalization** ✅
    *   Bridge freight/tariff invoices from `Payable` to origin `Inventory` receipts.
    *   Call `Inventory\StockManager::capitalizeLandedCost` for true COGS accuracy.
    *   *Implementation: `LandedCostCoordinator`, `LandedCostListener`*

## Phase 2: Intelligence & Optimization (Predictive Power) ✅
*Focus: Data-driven decision making using existing ML packages.*

*   **[SC-2.1] Predictive Replenishment** ✅
    *   Integrate `Inventory\DemandForecastExtractor` to dynamically update reorder thresholds.
    *   Automate `ReplenishmentCoordinator` triggers based on projected stock-outs.
    *   *Implementation: `ReplenishmentCoordinator::evaluateProductWithForecast()`*
*   **[SC-2.2] Dynamic Lead Time (ATP)** ✅
    *   Ingest `ProcurementML` delivery analytics to calculate real-time "Available-to-Promise" dates.
    *   Expose dynamic delivery estimates to the `Sales` module.
    *   *Implementation: `DynamicLeadTimeCoordinator`, `AvailableToPromiseResult`*

## Phase 3: Lifecycle & Logistical Integrity (Domain Coordination)
*Focus: Complex, long-running workflows across the entire enterprise.*

*   **[SC-3.1] RMA & Reverse Supply Chain**
    *   Orchestrate the end-to-end return lifecycle: `Sales` (Return Auth) → `Warehouse` (Receipt) → `QualityControl` (Inspection) → `Inventory` (Restock/Scrap).
    *   Ensure financial credit notes in `Receivable` match physical status.
*   **[SC-3.2] Regional Multi-Warehouse Balancing**
    *   Coordinate `Geo` location data with `Inventory` levels to optimize regional stock distribution.
    *   Auto-generate `TransferOrders` between nodes to minimize last-mile shipping costs.

---
**Strategy:** Library-first construction using stateless `Coordinators` for Phase 1, moving to stateful `Noms/Workflows` for Phase 3.
