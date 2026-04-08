# Atomy-Q Alpha Remaining Gaps — Re-evaluated Implementation Spec

Date: 2026-04-09  
Scope: `apps/atomy-q/`  
Primary source: `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`

## 1) Objective

Consolidate and re-evaluate all alpha-analysis gaps using current code + verification evidence, and answer:

1. Which gaps are already closed.
2. Which gaps still require implementation.
3. Which previously-closed gaps have regressed.
4. What exact closure criteria and verification evidence are required before alpha declaration.

## 2) Method and Coverage (Layer 1/2/3 cascade)

This re-evaluation was done by tracing `apps/atomy-q` through dependent Nexus first-party packages and adapters:

1. Layer 1 (`packages/`): `Sourcing`, `Vendor`, `Identity`, `Tenant`, supporting value objects/contracts.
2. Layer 2 (`orchestrators/`): `SourcingOperations`, `QuoteIngestion`, `QuotationIntelligence`, `IdentityOperations`, `TenantOperations`.
3. Layer 3 (`adapters/Laravel/*` + Atomy API/WEB): actual Laravel bindings, controllers, jobs, and frontend hooks/pages.

Verification evidence used:

1. WEB `npm run build` and `npm run lint`.
2. Targeted API test suite:
   - `RegisterCompanyTest`
   - `RfqLifecycleMutationTest`
   - `AwardWorkflowTest`
   - `IdentityGap7Test`
   - `QuoteIngestionPipelineTest`
   - `QuoteIngestionIntelligenceTest`
3. Dependency lock audit (`composer.json` vs `composer.lock`) for Nexus packages.

## 3) Current gap status (re-evaluated on 2026-04-09)

| Gap | Title | Status | Risk | Implementation remaining |
|---|---|---|---|
| 1 | Winner selection / award flow | Open (partially live) | High | Yes |
| 2 | Vendor master data | Open (API baseline now live; OpenAPI/WEB parity pending) | Medium | Yes |
| 3 | RFQ lifecycle mutations | Closed again (runtime wiring restored) | Medium | No (monitor regression) |
| 4 | Quote ingestion + AI normalization | Open (pipeline live, intelligence adapters mock-backed) | High | Yes |
| 5 | Comparison preview/matrix/readiness | Closed for alpha baseline (beta controls deferred intentionally) | Medium | No |
| 6 | Tenant/company lifecycle | Closed for alpha baseline onboarding | Low | No (post-alpha enrichments remain) |
| 7 | Identity/permissions/session | Open (mixed: core auth works; several identity surfaces remain stub/no-op) | High | Yes |
| 8 | Production readiness debt | Open (gates recovered, hardening pending) | High | Yes |
| 9 | Mock fallback leakage | Open (partially reduced) | High | Yes |
| 10 | Fragmented/stale roadmap docs | Open (improved but not fully governed) | Medium | Yes (lightweight) |

## 4) Evidence snapshot (what changed in confidence)

1. WEB build and lint hard gates were red and are now passing after Phase 1 fixes (type mismatch + lint scope/test typing cleanup).
2. API RFQ lifecycle regression caused by missing `nexus/sourcing-operations` lock/vendor installation is now resolved; targeted lifecycle tests pass.
3. Live-mode RFQ list no longer silently falls back to seed data on API failure (`use-rfqs` fail-loud behavior + tests).
5. Awards API and tests are substantially live; however award creation is not wired end-to-end from final comparison flow in UI journey.
6. Vendor endpoints in `VendorController` now return tenant-scoped persisted vendor rows, computed performance metrics, compliance metadata, and award history with feature-test coverage.
7. Quote ingestion pipeline is operational with tests, but Layer 3 still binds `MockContentProcessor` and `MockSemanticMapper` in `AppServiceProvider`.
8. Identity has meaningful progress (Gap 7 tests pass), but token management and MFA enrollment still use stub/no-op services, and user/role management endpoints are stubbed.
9. Frontend still has broad mock-mode surface (`NEXT_PUBLIC_USE_MOCKS` in many hooks/pages; seed/mocked behavior remains intertwined with live paths).

## 5) Layer-cascade findings by open gap

### WS-A: Gap 1 — Awards end-to-end completion

Required outcomes:

1. Award flow is fully live in API + WEB with tenant-safe winner selection and persistence.
2. No seed-only dependency in award finalization path.
3. End-to-end user path: compare -> select winner -> finalize -> persisted retrieval.

Layer-cascade finding:

1. Layer 3 awards endpoints are implemented and tested.
2. Comparison finalization does not create/select an award automatically; UI award page expects an existing record.
3. No orchestrator-managed award lifecycle boundary currently closes this path in one flow.

Closure evidence:

1. API feature tests for success + tenant-isolation negative paths.
2. WEB unit/e2e for finalization behavior and failure handling.
3. Manual smoke script and screenshot/log evidence.

### WS-B: Gap 2 — Vendor master data productionization

Required outcomes:

1. Vendor endpoints return persisted tenant-scoped records (not placeholders).
2. Vendor history/performance payloads are stable for dashboard usage.
3. Contract is reflected in OpenAPI and generated WEB client types.

Layer-cascade finding:

1. Layer 1 `Vendor` contracts/value objects exist.
2. Layer 3 Laravel Vendor adapter/repository exists and is installable.
3. Atomy API `VendorController` now serves tenant-scoped data from `vendors`, `quote_submissions`, and `awards` with explicit 404 isolation behavior.
4. Remaining closure work is contract parity for OpenAPI/client regeneration and any WEB surface that consumes `/vendors/*`.

Closure evidence:

1. API tests for tenant-scoped browse/detail/performance/compliance/history responses.
2. Seeder or fixture strategy for deterministic test data.
3. OpenAPI/client regeneration committed with no drift.

### WS-C: Gap 3 + Gap 4 — RFQ lifecycle regression + quote intelligence completion

Gap 3 required outcomes (reopened):

1. Restore RFQ lifecycle orchestrator runtime wiring in API app.
2. Ensure `nexus/sourcing-operations` is present in lock/vendor and resolvable in container.
3. Make `RfqLifecycleMutationTest` green again.

Gap 4 required outcomes:

Required outcomes:

1. Replace remaining mock-backed extraction/mapping with production processor after final model decision.
2. Preserve existing normalization persistence, decision trail, and tenant boundaries.
3. Reparse and normalization retry/error behavior is explicit and observable.

Deferred-control note:

1. Model/provider selection is a decision gate.
2. Once selected, implementation proceeds with no mock fallback in live mode.

Layer-cascade finding:

1. Layer 2 quote ingestion + quotation intelligence coordinators are wired and tested.
2. Layer 3 bindings still use `MockContentProcessor` and `MockSemanticMapper`; AI/env model contract is not productionized.
3. API env docs do not expose a production AI model/provider contract yet.

Closure evidence:

1. Gap 3: `RfqLifecycleMutationTest` passes.
2. Gap 4: API integration tests with deterministic processor fakes for CI.
2. Live-mode staging smoke proving end-to-end ingestion and normalization outputs.
3. Updated runbook for AI env variables and failure modes.

### WS-D: Gap 7 — Identity, RBAC, and session hardening

Required outcomes:

1. Session validation and permission checks are tenant-safe and non-no-op.
2. Role/permission and MFA/auth paths avoid stub behavior.
3. Controller/orchestrator boundaries remain interface-first.

Layer-cascade finding:

1. Login/session/MFA verification have meaningful feature-test coverage and pass.
2. `AtomyIdentityTokenManagerStub` and `AtomyNoopMfaEnrollmentService` are still bound in production container.
3. User-management APIs (users/roles/delegation endpoints) remain mostly stubbed.

Closure evidence:

1. Feature tests for session expiry, RBAC allow/deny, and cross-tenant isolation.
2. Adapter/repository tests for tenant-scoped reads/writes.
3. Explicit review checklist pass for Atomy-Q guideline requirements.

### WS-E: Gap 8 — Production readiness hardening

Required outcomes:

1. WEB build/lint/test and API test gates are reproducible in CI + staging.
2. Queue worker and storage contracts are operationally verified.
3. API/WEB env/port contract is documented and fail-fast.

Current blockers observed:

1. Hard build/lint/test gates are currently green for the targeted Phase 1 matrix, but warning cleanup and wider suite coverage are still pending.
2. Queue/storage/env operational runbook validation is still pending.

Closure evidence:

1. CI command matrix output attached to release checklist.
2. Staging queue/storage smoke logs.
3. Updated deployment/environment docs in repo.

### WS-F: Gap 9 — Mock fallback elimination for live paths

Required outcomes:

1. Live-mode golden path never silently falls back to seed/mock data.
2. Mock mode is explicit local-dev behavior only.
3. Live API failures surface as user-visible errors (not hidden success UI).

Current blockers observed:

1. `use-rfqs` fail-loud behavior is now implemented and tested in live mode.
2. Extensive mock flag usage still remains across other hooks/pages.
3. Dashboard and several RFQ flows still carry mixed mock/live behavior.

Closure evidence:

1. WEB tests asserting fail-loud behavior in live mode.
2. Documentation updates clarifying `NEXT_PUBLIC_USE_MOCKS` policy.
3. Staging smoke with mocks disabled and live API only.

### WS-G: Gap 10 — Documentation consolidation and execution governance

Required outcomes:

1. One active alpha execution ledger is maintained.
2. Removed weekly-task artifacts stay retired.
3. Every alpha-impacting PR updates status in active mitigation docs.

Current state:

1. Weekly task artifacts were retired and active index/spec were introduced.
2. Several foundational alpha docs remain stale by date and need synchronized status wording with actual code/test state.

Closure evidence:

1. `PLAN-INDEX.md` references active docs only.
2. No lingering references to deleted weekly files as active instructions.
3. PR template/checklist step added or enforced in review practice.

## 6) Execution order (critical path, updated)

1. WS-E first: recover broken gates (`build`, `lint`, dependency lock/runtime wiring for RFQ lifecycle).
2. WS-F second: remove silent live-path seed fallback so failures are honest.
3. WS-C Gap 3 portion third: make RFQ lifecycle mutation path green and stable.
4. WS-A + WS-B + WS-D next: close remaining core product trust boundaries.
5. WS-C Gap 4 AI productionization after model decision gate is closed.
6. WS-G continuously as control plane.

## 7) Alpha release gate (final)

Alpha is release-eligible only when:

1. All open gaps above are marked closed with evidence links.
2. Reopened gaps are re-closed and evidenced in CI/staging.
3. Golden path runs on live API + DB + queue + storage, with mocks off.
4. Final docs reflect actual behavior and deferments without ambiguity.

## 8) Single-source operational docs

This spec should be used together with:

1. `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_GAP_8_9_10_MITIGATION_PLAN_2026-04-09.md`
2. `apps/atomy-q/docs/alpha-audit-v1.0.0/PLAN-INDEX.md`
3. `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`

## 9) Verification run summary (2026-04-09)

1. `cd apps/atomy-q/WEB && npm run build` -> **Fail**
   - Network fetch for Google fonts fails in sandbox mode.
2. `cd apps/atomy-q/WEB && npm run build` (network-enabled rerun) -> **Fail**
   - TypeScript compile error in comparison run detail page (`snapshot` type mismatch).
3. `cd apps/atomy-q/WEB && npm run lint` -> **Fail**
   - 12 errors, 8 warnings.
4. `cd apps/atomy-q/API && php artisan test --filter "...selected gap tests..."` -> **Partial pass**
   - RegisterCompany / Awards / IdentityGap7 / QuoteIngestion tests pass.
   - RFQ lifecycle mutation tests fail due missing `nexus/sourcing-operations` runtime package wiring.
5. Composer lock parity check (`composer.json` vs `composer.lock`) -> **Mismatch**
   - `nexus/sourcing-operations` required but not present in lock/vendor.

## 10) Execution checkpoint (2026-04-09, later pass)

Completed in this session:

1. `cd apps/atomy-q/API && composer update nexus/sourcing-operations` -> lock/vendor repaired.
2. `cd apps/atomy-q/API && php artisan test --filter "RfqLifecycleMutationTest|AwardWorkflowTest|IdentityGap7Test"` -> **Pass**.
3. `cd apps/atomy-q/API && php artisan test --filter "VendorWorkflowTest|RfqLifecycleMutationTest|AwardWorkflowTest|IdentityGap7Test"` -> **Pass**.
4. `cd apps/atomy-q/WEB && npm run build` -> **Pass**.
5. `cd apps/atomy-q/WEB && npm run lint` -> **Pass with warnings only**.
6. `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-rfqs.test.ts src/hooks/use-rfqs.live.test.ts` -> **Pass**.
