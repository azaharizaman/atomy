# 🏗️ SPEC-001: Nexus Sourcing & Vendor Ecosystem Design

**Status:** Draft  
**Author:** Gemini CLI  
**Date:** March 24, 2026  
**Scope:** Atomy-Q Alpha Implementation Gaps (Items 3, 5, 6)

---

## 1. Context and Problem Statement

The Atomy-Q SaaS application currently implements critical sourcing logic (RFQs, Quotations, Normalization, Awards, and Vendor Management) as app-local code. This violates the **Nexus Core Mandates** of package-first implementation and creates a "God App" anti-pattern. Furthermore, the existing `Procurement` package is overloaded with strategic sourcing logic, which should be separated from operational Procure-to-Pay (P2P) flows.

## 2. Goals

- **Decouple Strategic Sourcing from Operational Procurement**: Move RFQ, Negotiation, and Award logic to a dedicated `Sourcing` package.
- **Establish a Stable Vendor Identity**: Create a `Vendor` package that manages the procurement "role" of a `Party`.
- **Implement Normalization Read APIs**: Provide high-performance, tenant-scoped access to normalized quote data.
- **Orchestrate the Award-to-PO Flow**: Ensure a deterministic, audited transition from a sourcing winner to a purchase order.
- **AI-ML Integration**: Refactor `QuotationIntelligence` to provide specialized normalization services to the sourcing workflow.

## 3. Non-Goals

- Implementing a full-scale Supplier Portal in this iteration.
- Complex multi-stage approval workflows beyond basic Alpha needs.
- Handling binary document storage (delegated to `Nexus\Storage`).

## 4. Nexus Package-First Reflection

| Package | Layer | Status | Reason for Creation/Refactor |
| :--- | :--- | :--- | :--- |
| `Nexus\Sourcing` | Layer 1 | **New** | To house strategic negotiation and normalization rules. |
| `Nexus\SourcingScoring` | Layer 1 | **New** | Deterministic engine for vendor selection criteria. |
| `Nexus\Vendor` | Layer 1 | **New** | Role-based extension of `Nexus\Party` identity. |
| `Nexus\Procurement` | Layer 1 | Existing | Refactored to focus strictly on P2P (Orders, Receipts). |
| `Nexus\SourcingOperations` | Layer 2 | **New** | To orchestrate Sourcing, Vendor, and Procurement workflows. |
| `Nexus\QuotationIntelligence`| Layer 2 | Existing | Refactored as a specialized AI/ML **Suggestion Engine**. |

## 5. Ownership Decision Matrix

| Capability | Reusable? | Best Layer | Proposed Owner | Why not another layer? |
| :--- | :--- | :--- | :--- | :--- |
| **RFQ/Quotation Domain** | Yes | Layer 1 | `Nexus\Sourcing` | Fundamental domain rules. |
| **Vendor Role Management** | Yes | Layer 1 | `Nexus\Vendor` | Reusable across any ERP module. |
| **Normalization Rules** | Yes | Layer 1 | `Nexus\Sourcing` | Must remain functional if AI is offline. |
| **Normalization Suggestions**| Yes | Layer 2 | `Nexus\QuotationIntelligence` | Requires AI/ML model coordination. |
| **Award-to-PO Workflow** | Yes | Layer 2 | `Nexus\SourcingOperations` | Tightly links Sourcing and Procurement. |
| **Laravel Persistence** | No | Layer 3 | `SourcingLaravelAdapter` | Framework-specific glue. |

## 6. Architecture and Layering Plan

### Layer 1: Domain Primitives
- **`Nexus\Sourcing`**: Defines `SourcingEvent`, `Quotation`, `NormalizationLine`, `Conflict`, and `Award`.
    - **`Invitation`**: Represents the *intent* to invite (data structure); no side effects.
- **`Nexus\Vendor`**: Defines `VendorProfile` (linked to `PartyId`), `CategoryLink`.
- **`Nexus\SourcingScoring`**: Pure math engine for vendor selection criteria.
- **`Nexus\Notifier`**: (Existing Layer 1) Interface for sending notifications.

### Layer 2: Strategic Orchestration
- **`Nexus\SourcingOperations`**: The primary entry point for the `Atomy-Q` UI. It handles the "Happy Path" from RFQ creation to PO generation.
    - **Invitation Action**: Executes the actual invitation (triggers notifications via `Nexus\Notifier`).
- **`Nexus\QuotationIntelligence`**: Acts as a **Suggestion Engine**. It takes raw `Quotation` data and returns suggested `NormalizationLine` mappings based on AI confidence. It *interacts* with Layer 1 schemas but does not own the source of truth.

### Layer 3: Infrastructure Adapters
- **`Nexus\SourcingLaravelAdapter`**: Eloquent models and migrations for sourcing entities.
- **`Nexus\VendorLaravelAdapter`**: Eloquent models and migrations for vendor roles.
- **`Nexus\SourcingIdempotencyAdapter`**: Replay-safe guards for sourcing mutations.

## 7. Security and Tenant Isolation

- **Mandatory `TenantId`**: Every interface in Layer 1 and 2 requires a `TenantId`.
- **Data Leakage Prevention**: Layer 3 adapters must apply global `tenant_id` scopes to all Eloquent queries.
- **Authorization**: `Nexus\SourcingOperations` will use `Nexus\PolicyEngine` to verify award thresholds and user permissions.
- **Audit Logging**: Use `Nexus\AuditLogger` for the "Award-to-PO" transition to ensure a high-value financial/compliance checkpoint.

## 8. Resilience and Idempotency

- **Direct Orchestration**: The "Award-to-PO" transition is directly orchestrated in Layer 2 for deterministic execution in Alpha.
- **Future Growth**: For production-scale resilience, this transition should be evaluated for the `Nexus\Outbox` pattern to handle distributed transaction failures.
- **Idempotency**: All mutations in `Nexus\SourcingOperations` must utilize `Nexus\Idempotency` to prevent duplicate resource creation.

## 9. Dependency Management

- **Loose Coupling**: `Nexus\Sourcing` depends on `Nexus\Vendor` only via identifiers (`VendorId`) and contracts to maintain Layer 1 decoupling and prevent circular dependencies.
- **Identity Integrity**: `Nexus\Vendor` references `Nexus\Party` by ID; no legal identity data (names, tax IDs) is duplicated in the Vendor role.

## 10. Testing Strategy

- **Unit Tests (Layer 1)**: 100% coverage of normalization rules and scoring math in pure PHP.
- **Integration Tests (Layer 2)**: Verification of the "Award-to-PO" orchestration using `InMemory` repositories.
- **Smoke Tests (Layer 3)**: Laravel-based tests to ensure migrations and Eloquent relations are correct.

## 11. Atomy-Q Refactoring Path

1.  Scaffold new Nexus packages using standard monorepo templates.
2.  Migrate `Atomy-Q` local models to `Nexus\SourcingLaravelAdapter`.
3.  Replace `Atomy-Q` service logic with `Nexus\SourcingOperations` orchestrator calls.
4.  Remove all synthetic identifiers and stubbed 501 responses in `NormalizationController`.

## 12. Acceptance Criteria

- [ ] `GET /normalization/{id}/source-lines` returns real persisted data.
- [ ] `POST /awards` successfully generates a `PurchaseOrder` in the `Procurement` package.
- [ ] `Nexus\Vendor` references `Nexus\Party` without data duplication.
- [ ] AI-driven normalization lines are persisted and retrievable via the new Sourcing API.
