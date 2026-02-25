## Context

The `ManufacturingOperations` orchestrator is a Layer 2 package designed to coordinate the production lifecycle across multiple domains (Manufacturing, Inventory, Quality, Scheduler). In accordance with the Nexus Three-Layer Architecture, this package must be stateless, framework-agnostic, and have zero hard dependencies on Layer 1 atomic packages (except `Nexus\Common`). This allows the orchestrator to be published independently on Packagist.

## Goals / Non-Goals

**Goals:**
- **Strict Isolation**: Define internal interfaces (Contracts) for all domain interactions (Stock, Production, Quality).
- **Independent Publishability**: Ensure `composer.json` only requires `Nexus\Common` and PSR interfaces, with atomic packages in `suggest`.
- **Statelessness**: Logic must not depend on internal state; all context is passed via DTOs or Providers.
- **Multi-Tenant Safety**: Explicit `tenantId` handling across all orchestration flows.
- **Real-time Aggregation**: Provide a unified view of production costs and stock status without direct database access.

**Non-Goals:**
- Implementation of domain logic (this belongs in Layer 1).
- Framework-specific integration (this belongs in Layer 3 Adapters).
- Direct persistence of data (Orchestrators use PersistInterfaces provided by Adapters).

## Decisions

### 1. Internal Contract Definition (Interface-First)
- **Decision**: Define all external service requirements in `src/Contracts/Services/`.
- **Rationale**: This decouples the Orchestrator from specific Layer 1 implementations. For example, instead of using `Nexus\Inventory\StockManager`, the orchestrator uses its own `StockServiceInterface`.
- **Alternatives**: Depending on Layer 1 interfaces. *Rejected* because Layer 1 interfaces might not be stable or could introduce circular/unnecessary dependencies for a standalone package.

### 2. Provider-Based Data Acquisition
- **Decision**: Use `DataProvider` interfaces for aggregate reads (e.g., `ProductionCostProviderInterface`).
- **Rationale**: Simplifies complex data fetching from multiple domains (HR for labor, Inventory for material) into a single call that the Adapter implements.
- **Alternatives**: Orchestrator fetching each piece individually. *Rejected* to minimize noise and improve performance in the Orchestrator logic.

### 3. Immutable DTOs for Cross-Package Communication
- **Decision**: All data passing through the Orchestrator uses `final readonly class` DTOs.
- **Rationale**: Guarantees data integrity and follows PHP 8.3 best practices.

### 4. Stateless Workflow Services
- **Decision**: Core logic is implemented in stateless services (e.g., `ProductionLifecycleManager`) that receive all necessary context in each method call.
- **Rationale**: Improves testability and fits the horizontal scaling requirements of a SaaS platform.

## Risks / Trade-offs

- **[Risk] Adapter Complexity** → The burden of mapping Orchestrator interfaces to Layer 1 packages shifts to Layer 3. *Mitigation*: Design Orchestrator interfaces to be as close to domain standards as possible.
- **[Risk] Contract Divergence** → If Layer 1 changes significantly, Orchestrator contracts might become outdated. *Mitigation*: Use semantic versioning and clear documentation for Adapter implementers.
- **[Risk] Latency** → Multiple levels of abstraction and cross-package calls. *Mitigation*: Use efficient DataProvider patterns to batch reads where possible.
