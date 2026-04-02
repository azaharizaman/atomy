# RFQ Lifecycle Gap 3 Design

Date: 2026-04-03
Scope: Atomy-Q alpha gap 3, expanded to cover adjacent RFQ lifecycle integrity actions
Author: Codex
Status: Approved in chat for design write-up

## Context

The alpha audit and follow-up progress analysis both identify RFQ lifecycle mutations as a core release gap:

- `POST /rfqs/{id}/duplicate` still returns a synthetic `stub-duplicate-id`
- `PUT /rfqs/{id}/draft` only echoes the request id/status instead of persisting draft state
- `POST /rfqs/bulk-action` returns `affected: 0` with tenant-scoping TODOs
- RFQ-adjacent integrity actions such as vendor reminder and status transitions are not consistently modeled as part of one lifecycle boundary

Relevant references:

- `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`
- `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md`
- `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_SPEC_SEED_IMPLEMENTATION_GAPS.md`
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php`

## Problem

Atomy-Q currently exposes RFQ lifecycle UI and API surface, but important write paths are still fake or unsafe. That breaks alpha credibility in two ways:

1. Important mutations do not persist real business state.
2. Tenant scoping, idempotency, and lifecycle rules are not expressed as reusable Nexus architecture.

The user explicitly wants a Nexus package-first design because Atomy-Q is intended to test the usefulness of Nexus first-party packages, not just ship app-local fixes.

## Goals

- Replace RFQ lifecycle stubs with real, persisted, tenant-safe mutations.
- Model the lifecycle as a Nexus-first orchestration boundary rather than controller-only Laravel logic.
- Reuse `Nexus\Idempotency` for replay-safe duplicate and bulk write flows.
- Define explicit Alpha-safe lifecycle behavior for duplicate, save draft, bulk actions, reminder integrity, and status transitions.
- Keep the first Alpha slice narrow enough to deliver while still being a real test of the Nexus architecture.

## Non-goals

- Template marketplace or broader RFQ template redesign
- Complex bulk workflow automation
- Export workflow implementation
- Rich owner reassignment workflow unless a real contract already exists
- Quote/comparison/award redesign outside the RFQ lifecycle touchpoints needed for safe transitions

## Options Considered

### Option 1: App-local controller hardening

Replace the stubs directly in Atomy-Q Laravel controllers with Eloquent transactions and request validation.

Pros:

- Fastest path
- Lowest initial architectural overhead

Cons:

- Weak test of Nexus-first design
- Lifecycle rules remain trapped in app controllers
- Harder to reuse in future sourcing SaaS products

### Option 2: Layer 2 orchestration first with minimal Layer 1 additions

Create a real sourcing workflow boundary in `orchestrators/SourcingOperations` for RFQ lifecycle mutations, and add only the reusable lifecycle policy primitives to `packages/Sourcing`.

Pros:

- Best fit for Nexus-first validation
- Preserves three-layer separation
- Lets Atomy-Q act as a real adapter/customer of Nexus sourcing orchestration

Cons:

- More upfront design work than app-local fixes
- Requires new interfaces and adapter wiring before feature completion

### Option 3: Heavy Layer 1 expansion in `packages/Sourcing`

Promote most RFQ lifecycle behavior directly into Layer 1 now and keep Layer 2 thin.

Pros:

- Maximizes package ownership of the domain

Cons:

- Higher risk of speculative package design
- Current `packages/Sourcing` scope is still centered on quotations and normalization, not full RFQ workflow orchestration
- Too large for the Alpha gap timeline

## Recommendation

Choose Option 2.

RFQ lifecycle gap 3 is primarily an orchestration problem. The tricky parts are tenant-safe lookup, child-entity copy rules, idempotent write coordination, and lifecycle transition policy. That belongs in Layer 2. Layer 1 should only take the reusable concepts that future sourcing apps are likely to share.

## Proposed Architecture

### Layer 1: `packages/Sourcing`

Add only reusable sourcing lifecycle primitives:

- `RfqLifecycleAction` enum or value object
- `RfqBulkAction` enum/value object for Alpha-safe commands
- `RfqDuplicationOptions` or `RfqDuplicationPolicy`
- `RfqStatusTransitionPolicyInterface` or equivalent policy service contract
- domain exceptions:
  - invalid transition
  - unsupported bulk action
  - lifecycle precondition failure
- framework-agnostic result DTOs for lifecycle mutations

Layer 1 should not know about Eloquent, HTTP, controllers, or Laravel request semantics.

### Layer 2: `orchestrators/SourcingOperations`

Promote `SourcingOperations` from stub to the RFQ lifecycle orchestration boundary.

Define an interface such as `RfqLifecycleCoordinatorInterface` with methods like:

- `duplicateRfq(...)`
- `saveDraft(...)`
- `applyBulkAction(...)`
- `remindVendorInvitation(...)`
- `transitionRfqStatus(...)`

Define Layer 2 ports for:

- tenant-scoped RFQ reads
- RFQ persistence
- line item reads/writes and copy behavior
- vendor invitation reads/writes
- optional notification dispatch hooks for reminder side effects

The orchestrator owns:

- lifecycle policy enforcement
- tenant-safe existence rules
- which child entities may be copied
- idempotency-aware orchestration boundaries
- coherent mutation results back to the app

### Layer 3: Atomy-Q Laravel adapter

Atomy-Q API becomes the consumer/adapter of the orchestration contracts:

- controllers validate HTTP shape
- controllers extract tenant and user context
- controllers call the Layer 2 coordinator
- Eloquent-backed adapters implement Layer 2 ports
- controllers map orchestrator result DTOs to JSON responses

This keeps the app-specific routing and response shape in Layer 3 while testing real Nexus ownership of lifecycle behavior.

## Lifecycle Semantics

### Duplicate RFQ

Rules:

- Tenant-scoped lookup only
- Wrong-tenant and missing return the same not-found semantics
- Duplicate creates a real new RFQ row with new identifier and timestamps
- Duplicate copies RFQ core fields and line items
- Duplicate does not copy:
  - quote submissions
  - comparison runs
  - awards
  - approval state
  - activity history

Vendor invitations:

- This should be policy-driven, but for Alpha the recommended default is **do not copy invitations**
- Reason: invitation copying can accidentally imply vendor outreach state, reminder history, or partial workflow carry-over that is unsafe for a first real implementation

Idempotency:

- Continue using `Nexus\Idempotency` for the duplicate route
- Same idempotency key plus same request fingerprint should replay the same created RFQ result
- Different key should create a new RFQ

### Save Draft

Rules:

- This becomes a real persistence path, not an echo endpoint
- Save draft persists allowed editable RFQ fields
- It reuses the RFQ validation rules where applicable
- It must reject edits disallowed by lifecycle state

Recommended Alpha behavior:

- Draft-compatible edits are allowed while RFQ is `draft`
- Published or closed RFQs may only use separate status transition paths, not implicit draft rewrites, unless a very small set of fields is explicitly approved

### Bulk Actions

Rules:

- Only explicit allowlisted actions are accepted
- Server resolves RFQs by tenant ownership; request IDs are never trusted on their own
- Return real affected counts and, if useful, per-id outcome data

Recommended Alpha-safe actions:

- `close`
- `archive` only if mapped clearly to an existing persisted state or documented view behavior
- `cancel` if already aligned with list/status semantics

Deferred:

- owner assignment
- export
- arbitrary workflow batching

### Vendor Reminder

Reminder is included in this gap because it materially affects RFQ lifecycle integrity.

Rules:

- Load invitation by `tenant_id + rfq_id + invitation_id`
- Return 404 on wrong-tenant or wrong-RFQ access
- Persist honest reminder metadata, such as `reminded_at`, if the schema supports it
- Optional email/queue behavior should live behind an adapter port, not controller logic

### Status Transitions

The RFQ lifecycle needs one explicit Alpha state machine.

Recommended transitions:

- `draft -> published`
- `published -> closed`
- `closed -> awarded`
- `draft -> cancelled`
- `published -> cancelled`
- `closed -> cancelled`

Notes:

- `archive` should not become an ambiguous new hidden workflow. It should either map to a real persisted state already understood by the app, or remain a deferred UI/listing concern.
- Invalid transitions should fail explicitly through a lifecycle exception/result, not silently coerce state.

## Data and Persistence Rules

- Reuse existing Atomy-Q RFQ tables where possible
- Avoid schema expansion unless lifecycle persistence truly needs new metadata
- Document child-copy rules explicitly so duplicate behavior is deterministic
- Keep tenant filtering mandatory on every read and write path
- Preserve the project’s established 404-for-wrong-tenant rule to avoid existence leakage

## Testing Strategy

### Layer 1 tests

- transition policy allows only approved status changes
- unsupported bulk actions are rejected
- duplication options/policy normalize consistently
- lifecycle exceptions are deterministic and domain-specific

### Layer 2 tests

- duplicate coordinates RFQ copy plus line-item copy correctly
- duplicate excludes forbidden child data
- save draft enforces lifecycle permissions
- bulk actions affect only tenant-owned RFQs
- reminder verifies tenant + RFQ + invitation integrity
- transition workflow rejects invalid moves

### Layer 3 / Atomy-Q tests

- feature tests for:
  - `POST /rfqs/{id}/duplicate`
  - `PUT /rfqs/{id}/draft`
  - `POST /rfqs/bulk-action`
  - `POST /rfqs/{rfqId}/invitations/{invId}/remind`
  - relevant status transition endpoint(s)
- idempotency replay test for duplicate
- idempotency replay test for bulk write path
- 404 non-leakage tests for wrong-tenant access
- assertions that no endpoint returns synthetic placeholder IDs or fake `affected: 0` responses unless no rows truly matched

## Delivery Slice for Alpha

To keep the first implementation narrow but meaningful, the recommended delivery slice is:

1. Create Layer 1 lifecycle policy primitives in `packages/Sourcing`
2. Create Layer 2 RFQ lifecycle coordinator contracts and implementation in `orchestrators/SourcingOperations`
3. Wire Atomy-Q Laravel adapters to those contracts
4. Implement real duplicate
5. Implement real save draft
6. Implement bulk close and bulk cancel or archive, depending on finalized state mapping
7. Harden vendor reminder integrity checks
8. Formalize publish, close, cancel, and award transition rules

Deferred from this slice:

- export bulk action
- owner reassignment
- richer invitation duplication policy
- broader procurement workflow automation

## Risks

- `packages/Sourcing` may grow too quickly if app-specific behavior is pushed down prematurely
- Existing WEB expectations may assume stub response shapes and need contract alignment
- Archive semantics may be ambiguous if the current app treats archive as a filter label rather than a persisted lifecycle state
- Duplicate copy rules may surface hidden coupling to invitations, projects, or deadlines

## Open Questions Resolved for This Design

- Scope includes adjacent integrity actions, not just the three original stub endpoints
- Architecture choice is Option 2: Layer 2 orchestration first, minimal Layer 1 additions
- Nexus-first packaging is a primary objective, not just an implementation detail

## Acceptance Criteria

- No RFQ lifecycle mutation endpoint returns synthetic placeholder responses
- RFQ duplicate creates a real persisted RFQ with copied line items
- Save draft persists real state and field updates
- Bulk actions only affect tenant-owned RFQs and only for allowlisted commands
- Vendor reminder enforces tenant + RFQ + invitation integrity
- RFQ status transitions follow one documented Alpha state machine
- Atomy-Q consumes Nexus lifecycle orchestration instead of embedding the behavior purely in Laravel controllers
