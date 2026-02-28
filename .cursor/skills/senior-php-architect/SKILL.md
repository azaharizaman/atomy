---
name: senior-php-architect
description: Applies Nexus three-layer architecture, orchestrator interface segregation, and CQRS patterns when designing PHP systems. Use when architecting new features, refactoring modules, deciding where logic belongs, or reviewing architectural decisions in packages, orchestrators, or adapters.
---

# Senior PHP Software Architect

## Core Philosophy

> **"Logic in Packages, Implementation in Applications"**

## Three-Layer Architecture

| Layer | Location | Purpose | Dependencies |
|-------|----------|---------|--------------|
| **L1** | `packages/` | Pure business logic, framework-agnostic | Common + PSR only |
| **L2** | `orchestrators/` | Cross-package workflow coordination | Own interfaces only |
| **L3** | `adapters/` + `apps/` | Framework-specific implementations | Packages + Orchestrators |

### Layer Rules

- **Packages**: Pure PHP 8.3+, zero framework deps, no DB logic. Define external needs via `Contracts/`.
- **Orchestrators**: Pure PHP, coordinates multi-package workflows. **Defines own interfaces** in `Contracts/`; never depends on package interfaces directly.
- **Adapters**: Implements orchestrator interfaces using atomic packages. Contains Eloquent/Doctrine, migrations, controllers.

## Orchestrator Interface Segregation

**Orchestrators define their own interfaces.** Adapters implement those interfaces by delegating to atomic packages.

```
❌ BAD: Orchestrator depends on Nexus\Inventory\Contracts\StockManagerInterface
✅ GOOD: Orchestrator defines SupplyChainStockManagerInterface; Adapter implements it using StockManagerInterface
```

- Never add orchestrator-specific methods to atomic package interfaces
- Orchestrators remain publishable standalone (php, psr/log, psr/event-dispatcher only)

## Decision Matrix: Where Does Logic Go?

| Scenario | Layer |
|----------|-------|
| Single-domain business rule | Package |
| Cross-package workflow | Orchestrator Coordinator |
| Data aggregation from multiple packages | Orchestrator DataProvider |
| Single validation constraint | Orchestrator Rule |
| Heavy calculation / cross-boundary logic | Orchestrator Service |
| Long-running saga / state machine | Orchestrator Workflow |
| DB migrations, controllers, jobs | Adapter / App |

## CQRS Pattern

Split repositories into read and write:

- `{Entity}QueryInterface` – read-only
- `{Entity}PersistInterface` – write

Example: `UserQueryInterface`, `UserPersistInterface`, `ProjectQueryInterface`, `BudgetPersistInterface`

## Multi-Tenancy

- All entities: `tenant_id` column (row-level isolation)
- API: `X-Tenant-ID` header
- Resolution order: Header → Query param → Authenticated user
- Queue jobs: `TenantAwareJob` for context propagation

## Primary Keys

Use **ULIDs** (26 chars), not auto-increment. Format: `01ARZ3NDEKTSV4RRFFQ69G5FAV`

## Orchestrator Structure

```
orchestrators/{Name}/
├── src/
│   ├── Contracts/      # Orchestrator-defined interfaces
│   ├── Coordinators/   # Flow control only, no business logic
│   ├── DataProviders/  # Cross-package aggregation
│   ├── Rules/          # Single-class validation
│   ├── Services/       # Calculations, cross-boundary logic
│   ├── Workflows/      # Sagas, state machines
│   ├── DTOs/
│   └── Exceptions/
└── tests/
```

## Reference

- `docs/project/ARCHITECTURE.md` – full guidelines
- `docs/project/ORCHESTRATOR_INTERFACE_SEGREGATION.md` – interface segregation
- `docs/project/NEXUS_SYSTEM_OVERVIEW.md` – system overview
