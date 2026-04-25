# Agent Learnings

This document replaces the old append-only learning journal from `AGENTS.md`.
Use it on demand during PR review closure or when a repeated mistake appears.

## Loopback Rule

When resolving PR review comments, record reusable patterns here instead of appending full incident narratives to `AGENTS.md`.

Prefer updating an existing pattern over adding a new one. Add full PR history only when the exact sequence is required for future work.

## Entry Format

Use compact pattern entries:

```markdown
### Pattern: Short name

- Trigger: When this situation appears.
- Risk: What can break or leak.
- Guardrail: What future agents must check.
- Source: PR/issue link or ID.
```

## Consolidated Historical Patterns

### Pattern: Tenant-scoped resource existence

- Trigger: API reads, writes, deletes, eager loads, ACL checks, or fallback filters for tenant-owned records.
- Risk: 403 responses, unscoped relation loads, or secondary queries can leak cross-tenant resource existence.
- Guardrail: Query by `tenant_id` and resource id together; return 404 for both missing and wrong-tenant resources.
- Source: #296, Projects/Tasks Phase 2, PolicyEngine, Identity, Quote Intelligence, AI approval summary reviews.

### Pattern: Live-mode synthetic fallback

- Trigger: Frontend hooks normalize optional numbers, missing responses, seed data, or mock/live mode gates.
- Risk: Missing API fields become `0`, live failures are hidden by seed data, or mock mode accidentally calls live endpoints.
- Guardrail: Parse optional values strictly, keep seed fallbacks mock-only, and disable live queries in mock mode.
- Source: #298, WEB fallback hygiene, AI review hardening.

### Pattern: Transaction and idempotency boundary

- Trigger: Duplicate RFQs, source-line mutations, AI artifacts, persisted POST routes, sanctions screening, and decision-trail writes.
- Risk: Partial writes, duplicate audit events, stale attempts, or retry-created records.
- Guardrail: Use one transaction for related writes and wire idempotency middleware plus completion for persisted POST routes.
- Source: Idempotency package, RFQ lifecycle, #378, #381, Plan 4 and Plan 5 AI reviews.

### Pattern: DTO and contract parity

- Trigger: UI forms, provider payloads, adapters, validation rules, and persisted JSON schemas change.
- Risk: Required backend fields are omitted, unsupported controls leak into payloads, lossy casts hide invalid provider data, or docs drift.
- Guardrail: Validate DTOs strictly, reject malformed live/provider payloads, and update docs/tests with contract changes.
- Source: #369, #381, Plan 4 and Plan 5 AI reviews.

### Pattern: Interface-first boundaries

- Trigger: Services, adapters, provider clients, alert publishers, challenge stores, and Layer 2 contracts.
- Risk: Concrete dependencies, service location, or Layer 1 types leak across architectural boundaries.
- Guardrail: depend on specific interfaces, inject constructors, and keep Layer 2 types scalar or orchestrator-owned.
- Source: PolicyEngine, RFQ lifecycle, #349, Plan 4, AI launch readiness.

### Pattern: Read/write HTTP semantics

- Trigger: AI artifact generation, dashboard/reporting summaries, vendor governance, approval/award intelligence, and cached reads.
- Risk: GET endpoints create persistent artifacts or read paths trigger provider calls unexpectedly.
- Guardrail: Keep GET read-only; expose explicit POST generation routes for writes and provider-backed creation.
- Source: Plan 4 and Plan 5 AI reviews.

### Pattern: Side-effect state ordering

- Trigger: Reminder sends, notification dispatch, operational alerts, outbox publishing, and status transitions.
- Risk: State indicates success before delivery or recovery/clear lifecycle is incomplete.
- Guardrail: dispatch before success-state persistence or use an outbox; test degrade, suppress, and clear transitions.
- Source: RFQ lifecycle, launch readiness reviews.

### Pattern: Canonical identifiers and numeric ordering

- Trigger: Vendor IDs, governance identifiers, AI DTO ids, RFQ numbers, line numbers, and blank recipient identifiers.
- Risk: case-insensitive queries become unsargable, lexicographic sorting misorders suffixes, blank IDs pass validation, or decimals truncate.
- Guardrail: canonicalize on write/construction, validate non-empty IDs, and parse numeric segments numerically.
- Source: RFQ lifecycle, #378, #381, Plan 4, launch readiness.

### Pattern: Review-thread state hygiene

- Trigger: Closing PR review batches or verifying already-fixed comments.
- Risk: fixed comments remain unresolved, unresolved comments are assumed handled, or deferred work lacks rationale.
- Guardrail: sweep unresolved threads; resolve fixed items; leave intentional deferrals open with rationale and follow-up.
- Source: #340, #349.

