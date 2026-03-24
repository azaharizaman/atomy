# Nexus ApprovalOperations — Design Specification

**Date:** 2026-03-24  
**Status:** Draft (brainstorm consolidated)  
**Related research:** [Approval_Package_Research_Report.md](../../research/Approval_Package_Research_20260324/Approval_Package_Research_Report.md)  
**Related packages:** `Nexus\Workflow`, `Nexus\PolicyEngine`, `Nexus\Identity`, `Nexus\Storage`, `Nexus\Notifier`, `Nexus\Idempotency`, `Nexus\Outbox`

---

## 1. Purpose

Define **Nexus\ApprovalOperations** (Layer 2 orchestrator): a reusable **operational approval mechanism**—*something* is approved (or rejected) *by someone*, subject to *rules* (templates, policies, strategies, delegation, escalation, SLA).

This spec also **separates** that mechanism from **RFQ quote approvals** (domain activity in sourcing), so naming, APIs, and documentation do not conflate a **product feature** with **platform infrastructure**.

---

## 2. Glossary (non-negotiable)

| Term | Meaning |
|------|---------|
| **Operational approval (ApprovalOperations)** | Generic governance pipeline: assignees, steps, strategies, audit of approval *actions*, optional delegation/escalation. Owned by **`Nexus\ApprovalOperations`** + Laravel adapters. |
| **RFQ quote approval (domain)** | Business activity in the RFQ lifecycle: *quotes received for an RFQ* are reviewed/accepted/rejected/awarded. Owned by **RFQ / procurement / sourcing** bounded context. UI routes like “Approvals” on an RFQ refer to **this** unless explicitly documented otherwise. |
| **Relationship** | RFQ quote flows **may compose** ApprovalOperations **when** a generic multi-step approval mechanism is required. They remain **distinct**: RFQ APIs and copy describe **quotes and RFQ state**; ApprovalOperations describes **the engine** (instances, tasks, rules), not “the RFQ feature.” |

**Rule of thumb:** If the sentence is about *which vendor quote wins*, it is **RFQ domain**. If it is about *who may approve step N under policy P*, it is **ApprovalOperations**.

---

## 3. Goals

- Provide a **framework-agnostic** orchestrator (Layer 2) that **coordinates** existing Layer 1 capabilities instead of reimplementing them.
- Support **template-driven** approval definitions (document/subject type → steps), **hybrid** sequential + parallel stages, **comments and attachment metadata** (blobs via `Nexus\Storage`), and **SLA visibility** aligned with Workflow escalation/SLA primitives.
- Enforce **tenant isolation** on all persisted operations and queries; **404** for missing or cross-tenant resources (no existence leak via 403).
- Integrate with **Idempotency** for mutating approval commands and **Notifier** / **Outbox** for post-commit notifications where appropriate.

---

## 4. Non-goals

- Replacing **RFQ-specific** endpoints or renaming RFQ “approvals” to imply they *are* ApprovalOperations.
- **Duplicating** `Nexus\Workflow`’s `ApprovalEngine`, multi-approver strategies, `EscalationService`, or `DelegationService` in the orchestrator.
- A new **large Layer 1** “Approval” package that mirrors Workflow (avoid unless a clear, shared primitive is proven necessary across multiple packages).
- **Framework code** inside `packages/` or `orchestrators/` (`Illuminate\*`, `Symfony\*` in Layers 1–2 per `ARCHITECTURE.md`).

---

## 5. Architectural stance (aligned with research)

| Layer | Responsibility |
|-------|----------------|
| **L1** `Nexus\Workflow` | State machine, tasks, approval strategies (`ApprovalStrategyInterface`), escalation, delegation, SLA hooks. |
| **L1** `Nexus\PolicyEngine` | Deterministic routing/policy evaluation (thresholds, conditions) for *which branch* or *which policy outcome* applies. |
| **L1** `Nexus\Identity` | Principals, roles, permissions; optional narrow ports for org-based resolution (see §7). |
| **L2** `Nexus\ApprovalOperations` | Template resolution, **composition** of sequential/parallel stages, **ports** for persistence (CQRS-style split), **context assembly** for PolicyEngine and Workflow, **attachment metadata** + **Storage** for files, **read models** for SLA/UI. |
| **L3** Laravel | Eloquent models/migrations, controllers, service provider bindings, jobs, HTTP mapping. |

**Orchestrator-first:** ApprovalOperations **plans and drives** Workflow/PolicyEngine; it does **not** own a second approval evaluator.

---

## 6. High-level flow

```
┌─────────────────────┐     ┌──────────────────────────┐     ┌─────────────────┐
│ Start / decide      │────►│ ApprovalOperations       │────►│ PolicyEngine    │
│ (subject ref, ctx)  │     │ (template + coordinator) │     │ (branch rules)  │
└─────────────────────┘     └───────────┬──────────────┘     └─────────────────┘
                                        │
                                        ▼
                            ┌───────────────────────────┐
                            │ Workflow (instance, tasks,  │
                            │ ApprovalEngine, escalation) │
                            └───────────────────────────┘
                                        │
                                        ▼
                            ┌───────────────────────────┐
                            │ L3: persist, notify,      │
                            │ idempotency, outbox       │
                            └───────────────────────────┘
```

**Subject reference:** Orchestrator uses a **generic** `subject_type` + `subject_id` (or equivalent VO) for **operational approval instances**. RFQ quote flows pass RFQ/quote identifiers as **context** when they call in; that mapping is **domain code**, not the name of ApprovalOperations.

---

## 7. Layer 1 — targeted enhancements (only if needed)

| Package | Allowed enhancement | Notes |
|---------|---------------------|--------|
| **Workflow** | Replace `RuntimeException` in `ApprovalEngine` for unknown strategies with a **domain exception** (e.g. `UnknownApprovalStrategyException`) for consistent error mapping. | Small, test-backed change. |
| **Workflow** | Documentation / examples for **hybrid** flows using existing **states + parallel gateways** (per package README). | No new engine if composition suffices. |
| **PolicyEngine** | New **condition/comparator** types only if approval routing requires them and evaluation stays deterministic. | Avoid “approver identity” in PolicyEngine if that belongs Identity + orchestrator. |
| **Identity** | Optional **narrow interface** (e.g. `OrganizationalApproverResolverInterface`) for reporting-line resolution **if** it is reusable outside ApprovalOperations. | **Not** a second RBAC system. |

**Anti-pattern:** Growing Workflow into “RFQ approvals” or “approval templates” as a single package. Keep **templates and subject mapping** in ApprovalOperations (or L3 persistence behind orchestrator ports).

---

## 8. Layer 2 — `Nexus\ApprovalOperations` (proposed shape)

**Namespace:** `Nexus\ApprovalOperations`

**Principles:** `final readonly` services, constructor injection of **specific interfaces**, **CQRS-style** split for stores (`*QueryInterface` / `*PersistInterface`), **DTOs** for commands and read models.

**Suggested modules (illustrative names):**

| Area | Responsibility |
|------|----------------|
| **Contracts** | `ApprovalTemplateQueryInterface`, `ApprovalTemplatePersistInterface`, `ApprovalInstanceQueryInterface`, `ApprovalInstancePersistInterface`, `ApprovalCommentPersistInterface`, `ApprovalAttachmentMetadataInterface` (or reuse Storage + metadata port). |
| **Services** | `ApprovalTemplateResolver`, `ApprovalProcessCoordinator` (facade: start → workflow transitions → complete/fail). |
| **Services** | `ApprovalSlaViewBuilder` (aggregates Workflow SLA + instance deadlines for UI). |
| **DTOs** | `ApprovalSubjectRef`, `StartOperationalApprovalCommand`, `RecordApprovalDecisionCommand`, read DTOs for inbox/list. |

**Dependencies** (via interfaces only): `Nexus\Workflow\*` managers/engines as appropriate, `PolicyEngineInterface` (or narrower ports), Identity permission/approver ports, `StorageInterface` for blobs, optional `NotificationManagerInterface`, `IdempotencyServiceInterface` at **application** boundary (L3 may wrap).

**Explicit non-dependency:** Other orchestrators (no orchestrator-to-orchestrator coupling); **no** `Illuminate\*`.

---

## 9. Layer 3 — Laravel adapters

**Location:** `adapters/Laravel/ApprovalOperations` (reusable) **and/or** `apps/atomy-q/API` concrete implementations (e.g. `Atomy*` pattern) bound in `AppServiceProvider`—choice is implementation-phase; **bindings** follow existing `ProjectManagementOperations` style.

**Responsibilities:**

- Migrations/models for: approval **templates** (versioned), **instances** (link to Workflow instance / correlation id), **comments**, **attachment metadata** (tenant-scoped, storage key).
- **Tenant-scoped** queries: every query includes `tenant_id` from authenticated context.
- **HTTP:** Controllers validate input, call coordinator, return JSON; **404** for not-found / wrong-tenant.
- **Idempotency:** Mutating routes (`POST` approve/reject/delegate) opt into middleware with stable `OperationRef` per route name.
- **Outbox:** Optional enqueue after commit for email/webhook fan-out.

---

## 10. Atomy-Q SaaS integration

| Surface | Guidance |
|---------|----------|
| **RFQ quote approval UI/API** | Remains **RFQ domain**; paths and copy refer to **quotes / RFQ** / award. **Do not** present these as “the ApprovalOperations product.” |
| **Generic operational approval** | If exposed in the same app, use **distinct routes** and resource names (e.g. `/approval-instances`, `/operational-approvals`) and OpenAPI tags; regenerate API client (`npm run generate:api`). |
| **Existing `use-approvals` / `/approvals`** | Treat as **RFQ list** until intentionally migrated; any new generic list should be a **separate hook** or clearly separated query key to avoid conceptual merge. |

---

## 11. Approaches considered (summary)

| Approach | Verdict |
|----------|---------|
| **A — Orchestrator-first** (compose Workflow + PolicyEngine + Storage + ports) | **Recommended** — matches research, preserves SRP, testable. |
| **B — Grow Workflow** with approval templates | **Rejected** — blurs generic workflow vs operational approval product. |
| **C — Large new L1 Approval package** | **Deferred** — only if shared primitives are proven across multiple domains. |

---

## 12. Testing strategy

- **L2 unit tests:** Mock Workflow/PolicyEngine/Identity ports; assert coordinator sequencing and tenant handling.
- **L1 regression:** Any Workflow exception change covered by existing + new tests.
- **L3 feature tests:** HTTP + tenant isolation + idempotency replay where enabled.

---

## 13. Documentation updates (implementation phase)

- `docs/project/NEXUS_PACKAGES_REFERENCE.md` — entry for `Nexus\ApprovalOperations`.
- `docs/project/ARCHITECTURE.md` — cross-link if orchestrator pattern is new to the table of orchestrators.
- Package/orchestrator `README.md` + `IMPLEMENTATION_SUMMARY.md` per AGENTS.md.

---

## 14. Risks and mitigations

| Risk | Mitigation |
|------|------------|
| Overlap with Workflow | Coordinator **only** orchestrates; no duplicate `ApprovalEngine`. |
| Orchestrator bloat | Split services by responsibility; **ISP**-friendly interfaces. |
| Confusion with RFQ | Glossary §2 in API docs and developer onboarding; separate routes and hooks. |
| Cross-tenant leakage | Scoped queries; **404** policy; tests for wrong-tenant access. |

---

## 15. Next steps

1. **Stakeholder review** of this spec (especially §2 and §10).  
2. **`writing-plans` skill** → `docs/superpowers/plans/YYYY-MM-DD-nexus-approval-operations.md` with phased tasks (L1 touch-ups → L2 skeleton → L3 → Atomy-Q boundaries).  
3. **Implementation** in a dedicated worktree (per project conventions).

---

*Brainstorm consolidated 2026-03-24; incorporates distinction between RFQ quote approvals (domain) and operational approval mechanism (ApprovalOperations).*
