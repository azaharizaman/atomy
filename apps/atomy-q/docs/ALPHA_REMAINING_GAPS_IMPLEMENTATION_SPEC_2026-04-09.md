# Atomy-Q Alpha Remaining Gaps — Final Implementation Spec

Date: 2026-04-09  
Scope: `apps/atomy-q/`  
Primary source: `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`

## 1) Objective

Consolidate all alpha-analysis gaps into one execution spec that answers:

1. Which gaps are already closed.
2. Which gaps still require implementation.
3. What exact closure criteria and verification evidence are required before alpha declaration.

## 2) Current gap status (as of 2026-04-09)

| Gap | Title | Status | Implementation remaining |
|---|---|---|---|
| 1 | Winner selection / award flow not fully productized | Open | Yes |
| 2 | Vendor master data stubbed | Open | Yes |
| 3 | RFQ lifecycle mutations | Closed | No |
| 4 | Quote ingestion + AI normalization incomplete | Open (partially deferred by model decision) | Yes |
| 5 | Comparison preview/matrix/readiness | Closed for alpha path | No |
| 6 | Tenant/company lifecycle | Closed for alpha baseline onboarding | No (post-alpha enrichments remain) |
| 7 | Identity/permissions/session still stubbed | Open | Yes |
| 8 | Production readiness debt | Open | Yes |
| 9 | Mock fallback leakage | Open | Yes |
| 10 | Fragmented/stale roadmap docs | In remediation | Minimal (governance + sync only) |

## 3) Closed gaps (no further implementation in alpha scope)

Gaps 3, 5, and 6 are treated as closed for alpha baseline and should only receive regression protection:

1. Keep existing tests green and add coverage only when adjacent changes touch those flows.
2. Do not reopen scope unless a regression is detected.
3. Track only validation evidence in release readiness checks.

## 4) Remaining implementation workstreams

### WS-A: Gap 1 — Awards end-to-end completion

Required outcomes:

1. Award flow is fully live in API + WEB with tenant-safe winner selection and persistence.
2. No seed-only dependency in award finalization path.
3. End-to-end user path: compare -> select winner -> finalize -> persisted retrieval.

Closure evidence:

1. API feature tests for success + tenant-isolation negative paths.
2. WEB unit/e2e for finalization behavior and failure handling.
3. Manual smoke script and screenshot/log evidence.

### WS-B: Gap 2 — Vendor master data productionization

Required outcomes:

1. Vendor endpoints return persisted tenant-scoped records (not placeholders).
2. Vendor history/performance payloads are stable for dashboard usage.
3. Contract is reflected in OpenAPI and generated WEB client types.

Closure evidence:

1. API tests for tenant-scoped browse/detail responses.
2. Seeder or fixture strategy for deterministic test data.
3. OpenAPI/client regeneration committed with no drift.

### WS-C: Gap 4 — Quote ingestion + AI normalization completion

Required outcomes:

1. Replace remaining mock-backed extraction/mapping with production processor after final model decision.
2. Preserve existing normalization persistence, decision trail, and tenant boundaries.
3. Reparse and normalization retry/error behavior is explicit and observable.

Deferred-control note:

1. Model/provider selection is a decision gate.
2. Once selected, implementation proceeds with no mock fallback in live mode.

Closure evidence:

1. API integration tests with deterministic processor fakes for CI.
2. Live-mode staging smoke proving end-to-end ingestion and normalization outputs.
3. Updated runbook for AI env variables and failure modes.

### WS-D: Gap 7 — Identity, RBAC, and session hardening

Required outcomes:

1. Session validation and permission checks are tenant-safe and non-no-op.
2. Role/permission and MFA/auth paths avoid stub behavior.
3. Controller/orchestrator boundaries remain interface-first.

Closure evidence:

1. Feature tests for session expiry, RBAC allow/deny, and cross-tenant isolation.
2. Adapter/repository tests for tenant-scoped reads/writes.
3. Explicit review checklist pass for Atomy-Q guideline requirements.

### WS-E: Gap 8 — Production readiness hardening

Required outcomes:

1. WEB build/lint/test and API test gates are reproducible in CI + staging.
2. Queue worker and storage contracts are operationally verified.
3. API/WEB env/port contract is documented and fail-fast.

Closure evidence:

1. CI command matrix output attached to release checklist.
2. Staging queue/storage smoke logs.
3. Updated deployment/environment docs in repo.

### WS-F: Gap 9 — Mock fallback elimination for live paths

Required outcomes:

1. Live-mode golden path never silently falls back to seed/mock data.
2. Mock mode is explicit local-dev behavior only.
3. Live API failures surface as user-visible errors (not hidden success UI).

Closure evidence:

1. WEB tests asserting fail-loud behavior in live mode.
2. Documentation updates clarifying `NEXT_PUBLIC_USE_MOCKS` policy.
3. Staging smoke with mocks disabled and live API only.

### WS-G: Gap 10 — Documentation consolidation and execution governance

Required outcomes:

1. One active alpha execution ledger is maintained.
2. Removed weekly-task artifacts stay retired.
3. Every alpha-impacting PR updates status in active mitigation docs.

Closure evidence:

1. `PLAN-INDEX.md` references active docs only.
2. No lingering references to deleted weekly files as active instructions.
3. PR template/checklist step added or enforced in review practice.

## 5) Execution order (critical path)

1. WS-F (mock fallback policy) + WS-E (build/env readiness) first to restore honest signal quality.
2. WS-A (awards) and WS-B (vendors) next to close core product trust gaps.
3. WS-D (identity/session/RBAC) in parallel with WS-A/WS-B where ownership is independent.
4. WS-C (AI normalization final wiring) after model decision gate is closed.
5. WS-G continuously to keep status and scope unambiguous.

## 6) Alpha release gate (final)

Alpha is release-eligible only when:

1. All open gaps above are marked closed with evidence links.
2. Closed gaps (3,5,6) remain regression-safe in the release candidate.
3. Golden path runs on live API + DB + queue + storage, with mocks off.
4. Final docs reflect actual behavior and deferments without ambiguity.

## 7) Single-source operational docs

This spec should be used together with:

1. `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_GAP_8_9_10_MITIGATION_PLAN_2026-04-09.md`
2. `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-INDEX.md`
3. `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`
