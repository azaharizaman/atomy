# Nexus Architectural Guidelines & Coding Standards

This document serves as the single source of truth for the architectural integrity and development standards within the Nexus monorepo. **Adherence to these rules is mandatory.**

---

## 1. The Three-Layer Architecture

Nexus separates concerns into three distinct vertical layers to ensure portability, testability, and scalability.

### Layer 1: Atomic Packages (`packages/`)
- **Purpose**: Pure business logic and domain "truth".
- **Rules**: 
    - Pure PHP 8.3+ only.
    - Zero framework dependencies.
    - No database logic or migrations.
    - Define all external needs (storage, config) via interfaces (`Contracts/`).
- **Structure**: Flat (`src/Services`, `src/Contracts`) or DDD (`src/Domain`, `src/Application`).

### Layer 2: Orchestrators (`orchestrators/`)
- **Purpose**: Cross-package workflow coordination.
- **Rules**:
    - Pure PHP (framework-agnostic).
    - Coordinates interactions between multiple Layer 1 packages.
    - Implements the "Advanced Orchestrator Pattern" (see Section 3).
- **Structure**: `Coordinators/`, `DataProviders/`, `Rules/`, `Workflows/`.

### Layer 3: Adapters (`adapters/`)
- **Purpose**: Framework-specific implementations (e.g., Laravel, Symfony).
- **Contains**: Eloquent models, migrations, service providers, controllers, and jobs.
- **Role**: Concrete implementation of interfaces defined in Layers 1 and 2.

---

## 2. Package Atomicity Principles

An **Atomic Package** is a self-contained unit addressing ONE business domain.

### Mandatory Metadata
Every package MUST include:
- `README.md`: Usage examples and interface definitions.
- `composer.json`: Requiring PHP ^8.3 and proper namespaces.
- `IMPLEMENTATION_SUMMARY.md`: Progress and checklist.
- `VALUATION_MATRIX.md`: Metrics on complexity and coverage.

### Anti-Pattern: God Packages
Avoid packages that handle multiple unrelated domains. If a package description needs more than two "and" conjunctions, it should likely be split (e.g., "Finance" split into "Receivable", "Payable", "GeneralLedger").

---

## 3. The Advanced Orchestrator Pattern

Used in `orchestrators/` to prevent "God Class" coordinators.

| Component | Responsibility | Rule |
| :--- | :--- | :--- |
| **Coordinators** | Traffic management. | Directs flow, executes no logic or fetching. |
| **DataProviders** | Aggregation. | Fetches context from multiple packages into a Context DTO. |
| **Rules** | Validation. | Single-class business constraints (e.g., `PeriodOpenRule`). |
| **Services** | Calculations. | Heavy lifting and cross-boundary logic. |
| **Workflows** | Statefulness. | Manages long-running Sagas and state-machine transitions. |

---

## 4. Coding Standards (PHP 8.3+)

### Core Requirements
1. **Strict Types**: `declare(strict_types=1);` is mandatory in every file.
2. **Framework Agnosticism**: No `use Illuminate\*` or `use Symfony\*` in Layers 1 or 2.
3. **Dependency Injection**: Constructor injection only. Depend on **Interfaces**, never concrete classes.
4. **Immutability**:
    - **Service Classes**: `final readonly class`.
    - **Value Objects**: Per-property `readonly` on promoted properties.

### REPOSITORY DESIGN (CQRS)
Every domain repository should be split into:
- `{Entity}QueryInterface`: For side-effect-free read operations.
- `{Entity}PersistInterface`: For write operations.

### Error Handling
- Never throw generic `\Exception`.
- Use domain-specific exceptions (e.g., `TenantNotFoundException`).
- Use "Throw in Expressions" for cleaner null-coalescing handler.

---

## 5. Security & Authorization

### The Rule of Identity
All authorization MUST flow through **`Nexus\Identity`**. Do not implement custom Auth logic.

- **RBAC**: Use `PermissionCheckerInterface` for simple capability checks.
- **ABAC**: Use `PolicyEvaluatorInterface` for context-aware rules (e.g., "User can edit if they are the creator").
- **Implementation**: Concrete policy evaluations (e.g., in a Laravel closure) reside in the `adapters/` layer.

---

## 6. Decision Matrix: "Where Does Logic Go?"

- **Single Domain Logic?** → Move it to an Atomic Package (`packages/`).
- **Cross-Domain Workflow?** → Move it to an Orchestrator (`orchestrators/`).
- **Database/Framework Logic?** → Move it to an Adapter (`adapters/`).
- **Validation Check?** → Create a Rule class in the Orchestrator.
- **Data Aggregation?** → Create a DataProvider in the Orchestrator.
