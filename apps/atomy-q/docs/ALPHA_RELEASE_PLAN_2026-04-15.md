# Atomy-Q Alpha Release Plan - 2026-04-15

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` or `superpowers:executing-plans` to execute this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a design-partner alpha where a buying-organization user can register/login, create an RFQ, add line items, invite vendors, ingest quotes, normalize quote lines, compare vendors, select/save an award, and verify the decision trail using the live Laravel API and live database with mock mode disabled.

**Architecture:** Keep the alpha path narrow and honest. Layer 1 owns pure domain contracts and value objects, Layer 2 owns cross-package orchestration through its own interfaces, and Layer 3 owns Laravel/Next.js integration, persistence, queues, storage, and UI. Non-alpha surfaces remain hidden, explicitly deferred, or fail-loud rather than returning fake success.

**Tech Stack:** Laravel 12, PHP 8.3, PostgreSQL, Redis queues/cache, Next.js 16, React 19, TanStack Query, Axios, Zustand, Tailwind CSS v4, PHPUnit, Vitest, Playwright, Composer path repositories for Nexus packages.

---

## 1. Current Alpha Definition

Alpha is not the full 203-endpoint procurement platform. It is one live quote-comparison journey:

1. Register company or login as a tenant user.
2. Create/list/open RFQs.
3. Add RFQ line items and schedule fields.
4. Invite vendors.
5. Upload or record vendor quote submissions.
6. Normalize quote source lines with production-grade intelligence or an explicitly approved deterministic alpha processor.
7. Resolve normalization conflicts and readiness blockers.
8. Freeze a comparison run.
9. Review comparison matrix and recommendation evidence.
10. Create/sign off an award.
11. Read decision trail evidence for the frozen comparison and award/debrief actions.

Alpha is blocked if any of these require `NEXT_PUBLIC_USE_MOCKS=true`, seed fallback in live mode, hardcoded success responses, or manual database edits.

## 2. Study Coverage

This plan was produced after tracing the Atomy-Q stack through the active codebase:

- API routes: `apps/atomy-q/API/routes/api.php` exposes the full `/api/v1` surface, including auth, RFQs, quote submissions, normalization, comparison runs, awards, decision trail, projects/tasks, settings, users, reports, integrations, and operational approvals.
- API controllers/services: `apps/atomy-q/API/app/Http/Controllers/Api/V1`, `apps/atomy-q/API/app/Services`, and `apps/atomy-q/API/app/Adapters` show the core RFQ/quote/comparison/award path is partially live, while several broad product surfaces still return placeholders or deferred responses.
- WEB app: `apps/atomy-q/WEB/src/app`, `src/hooks`, `src/lib`, `src/data/seed.ts`, and Playwright/Vitest tests show a broad UI with live-mode hooks and explicit mock-mode branches.
- Layer 1 packages: `packages/Sourcing`, `Vendor`, `Identity`, `Tenant`, `Idempotency`, `PolicyEngine`, `Outbox`, `Notifier`, `Project`, `Task`, `Procurement`, `MachineLearning`, `Document`, and related packages.
- Layer 2 orchestrators: `orchestrators/SourcingOperations`, `QuoteIngestion`, `QuotationIntelligence`, `IdentityOperations`, `TenantOperations`, `ApprovalOperations`, `ProjectManagementOperations`, `SettingsManagement`, and `ProcurementOperations`.
- Layer 3 adapters: `adapters/Laravel/Sourcing`, `Vendor`, `Identity`, `Tenant`, `Idempotency`, `Notifier`, `ApprovalOperations`, `ProjectManagementOperations`, and app-local Laravel adapters in `apps/atomy-q/API/app`.
- Existing alpha docs and previous gap plans were used as baseline only. They have been archived because this file replaces them as the active alpha source of truth.

## 3. What Is Already Alpha-Useful

| Area | Current state | Alpha relevance |
|---|---|---|
| Company onboarding | `POST /auth/register-company` creates tenant + owner through `TenantOperations` and returns bootstrap auth data. | Keep in alpha. |
| Login/session | JWT login, refresh, logout, session-backed `sid` checks, lockout, MFA challenge verification path. | Keep, but harden exposed user/RBAC surfaces. |
| Tenant isolation | Core tables use `tenant_id`; middleware resolves tenant from JWT; tests cover several cross-tenant cases. | Mandatory release gate. |
| RFQ lifecycle | Create/list/detail/update/line-items/status/duplicate/draft/bulk/reminder paths are live through `SourcingOperations`. | Keep in golden path. |
| Vendor invitations | RFQ-scoped invitations are live and tenant scoped. | Keep in golden path. |
| Vendor master | `VendorController` now returns persisted tenant-scoped vendors and derived performance/compliance/history. | Keep only if OpenAPI/WEB parity is verified. |
| Quote submissions | Upload/list/show/status/reparse path persists submissions and can invoke `QuoteIngestionOrchestrator`. | Keep, but replace mock-backed intelligence. |
| Normalization review | Source lines, normalized items, conflicts, mapping, overrides, lock/unlock are DB backed. | Keep in golden path. |
| Comparison runs | Preview/final/matrix/readiness are live for alpha; lock/unlock/scoring-model controls are intentionally deferred. | Keep final, matrix, readiness. |
| Awards | Award create/read/split/debrief/protest/signoff are implemented with feature tests. | Must complete end-to-end UI journey and evidence trail. |
| Decision trail | Tenant-scoped reads and comparison snapshot entries exist. | Must be part of final smoke. |
| Projects/tasks | Implemented with feature flags, ACL, project-linked RFQ/task views. | Optional alpha context; not a blocker unless enabled. |
| Operational approvals | Separate `ApprovalOperations` path exists. | Optional unless used by the alpha award approval story. |

## 4. Known Alpha Blockers

| ID | Blocker | Risk | Required closure |
|---|---|---|---|
| A1 | Quote intelligence still needed an honestly named deterministic alpha binding in `AppServiceProvider`. | High | Keep the deterministic default, leave `llm` dormant until a production provider exists, and avoid hidden mock behavior in live mode. |
| A2 | Award journey is implemented in pieces but not proven as compare -> select winner -> persist award -> signoff -> decision trail from WEB. | High | Add API + WEB + E2E coverage for the complete user journey. |
| A3 | WEB still has many `NEXT_PUBLIC_USE_MOCKS` branches and seed imports. Several have live tests, but the full golden path needs fail-loud verification. | High | Add a live-mode no-seed regression matrix for RFQ, vendors, submissions, normalization, comparison, awards, approvals, dashboard shell. |
| A4 | Broad non-alpha controllers still return placeholder data or 501/deferred responses: negotiations, settings writes, account subscription/payment, reports, integrations, recommendations, scenarios, risk, documents/handoff. | Medium | Hide from alpha navigation or return explicit deferred responses with no golden-path dependency. |
| A5 | User/role management surfaces are still mostly thin/stubbed while auth/RBAC internals are stronger. | High | Either productionize minimal Users & Roles for alpha admin or hide/gate those routes. |
| A6 | OpenAPI/generated WEB client drift is likely after recent API hardening. | Medium | Export Scramble spec, regenerate WEB client, and verify no generated/consumer drift. |
| A7 | Queue/storage/AI/env runbook is incomplete for real quote ingestion. | High | Document and smoke-test worker, storage disk, file upload, AI env, CORS, and API URL contract. |
| A8 | Alpha evidence is fragmented across tests, docs, and historic plans. | Medium | Use this plan plus a release checklist only; archive historical alpha docs. |

## 5. Layer 1 Findings

Layer 1 is strong enough for alpha if the app stays narrow.

- `packages/Sourcing` provides RFQ lifecycle primitives, status transition policy, sourcing exceptions, quotation/award contracts, and normalized line value objects.
- `packages/Vendor` provides vendor profile contracts and status value object; it is intentionally small and depends on Layer 3 for persistence.
- `packages/Identity` is broad and production-grade at the package level: auth, sessions, RBAC, MFA, token contracts, and domain exceptions. Atomy-Q still has adapter/binding gaps, not L1 gaps.
- `packages/Tenant` provides tenant context, lifecycle, validation, status, events, and impersonation primitives. Alpha onboarding uses a focused subset.
- `packages/Idempotency` is suitable for RFQ/project/task mutating endpoints and uses split query/persist contracts plus attempt tokens.
- `packages/PolicyEngine` supports operational approval policy decisions and is already wired into the approval operations path.
- `packages/Notifier`, `Outbox`, `Document`, `MachineLearning`, and `Procurement` contain reusable primitives, but alpha should only pull them where the golden path requires them.

Layer 1 action: do not expand domain scope before alpha. Only add contracts if needed to keep quote intelligence honestly named and deterministic or to formalize award lifecycle semantics.

## 6. Layer 2 Findings

Layer 2 has the right alpha boundaries, with one critical productionization gap.

- `SourcingOperations` is the correct RFQ lifecycle boundary for duplicate, save draft, bulk status, transition, and invitation reminders.
- `QuoteIngestion` now hardens malformed intelligence output, persists only valid normalized lines, sanitizes failures, and tracks completion from persisted results.
- `QuotationIntelligence` is feature-rich: content processing, semantic mapping, normalization, comparison matrix, readiness, risk, vendor scoring, commercial terms, and decision trail contracts. The production gap is Atomy-Q binding, not lack of L2 capability.
- `TenantOperations` provides the focused company onboarding path used by alpha registration.
- `IdentityOperations` provides user lifecycle/auth/RBAC/MFA orchestration. Atomy-Q uses parts of it, while public users/roles screens still need a product decision.
- `ApprovalOperations` is separate from RFQ quote approvals and can support operational approvals if alpha needs it.
- `ProjectManagementOperations` supports project health and project-linked RFQ/task context, but should remain optional for alpha.

Layer 2 action: keep orchestration interface-first. Do not let Atomy-Q controllers call Layer 1 services directly when a Layer 2 boundary exists for the workflow.

## 7. Layer 3 Findings

Layer 3 is where most alpha risk remains.

- `apps/atomy-q/API/app/Providers/AppServiceProvider.php` correctly wires many live adapters, and quote intelligence is now bound through honestly named deterministic and dormant LLM adapters while MFA enrollment still uses `AtomyNoopMfaEnrollmentService`.
- Laravel API controllers have mixed maturity. RFQ, quote submission, normalization, comparison, awards, vendors, projects/tasks, auth, tenant onboarding, and operational approvals have real behavior. Reports, risk, integrations, documents, handoffs, recommendations, scenarios, settings writes, negotiation, account subscription/payment, and user-management endpoints are placeholder/deferred surfaces.
- `apps/atomy-q/WEB/src/lib/api-live.ts` deliberately returns `undefined` in mock mode and throws in live mode. Hooks must preserve that contract and must not catch live failures into seed success.
- `apps/atomy-q/WEB/src/data/seed.ts` is still useful for local demo mode, but it must be impossible to confuse seed-backed screens with alpha-ready live behavior.
- Navigation currently exposes surfaces that are broader than alpha. Alpha should prefer a core-only nav/profile for design partners.

Layer 3 action: harden live-mode behavior first, then hide or explicitly defer non-alpha surfaces.

## 8. Alpha Scope Decisions

| Decision | Rationale |
|---|---|
| Alpha uses `tenant_id`, not `company_id`, as the isolation key. | This matches all current tables, middleware, packages, and docs. |
| Alpha does not require SSO, WebAuthn, full MFA enrollment, subscription billing, integrations, reporting schedules, negotiations, risk workflows, or vendor portal login. | They increase surface area without proving the core quote-comparison value. |
| Alpha may include projects/tasks only as contextual organization if `FEATURE_PROJECTS_ENABLED` and `FEATURE_TASKS_ENABLED` are enabled in staging. | They are not required for RFQ -> quote -> award. |
| Alpha comparison lock/unlock/scoring-model changes remain beta controls. | Current implementation intentionally defers them; final comparison snapshot is enough. |
| Mock mode is local development only. | Design-partner alpha validation must run with `NEXT_PUBLIC_USE_MOCKS=false`. |
| Production AI is preferred, but a deterministic alpha processor can be accepted only if named honestly and documented as non-LLM. | The original alpha goal said real AI, but release risk is lower if the team explicitly chooses deterministic alpha normalization rather than hiding mocks. |

## 9. Execution Plan

### Task 1: Establish Release Evidence Baseline

This task turns the release plan into an evidence ledger. It captures the current state of the WEB and API gates before implementation begins, so the team can distinguish known failures from regressions introduced during alpha work.

After this task, stakeholders should have a simple release checklist showing which gates pass, which fail, which blocker each failure maps to, and what needs to happen next.

**Files:**
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`
- Create: `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
- Follow-up spec: [`ALPHA_TASK1_RECTIFICATION_SPEC_2026-04-15.md`](./ALPHA_TASK1_RECTIFICATION_SPEC_2026-04-15.md)
- Follow-up plan: [`ALPHA_TASK1_RECTIFICATION_IMPLEMENTATION_PLAN_2026-04-15.md`](./ALPHA_TASK1_RECTIFICATION_IMPLEMENTATION_PLAN_2026-04-15.md)

- [x] Run WEB gates: `cd apps/atomy-q/WEB && npm run lint && npm run build && npm run test:unit`.
- [x] Run API gates: `cd apps/atomy-q/API && php artisan test`.
- [x] If full API tests are too slow or blocked by local DB, run and record the alpha matrix: `RegisterCompanyTest`, `AuthTest`, `RfqLifecycleMutationTest`, `RfqInvitationReminderTest`, `QuoteSubmissionWorkflowTest`, `QuoteIngestionPipelineTest`, `QuoteIngestionIntelligenceTest`, `NormalizationReviewWorkflowTest`, `ComparisonRunWorkflowTest`, `ComparisonSnapshotWorkflowTest`, `AwardWorkflowTest`, `VendorWorkflowTest`, `IdentityGap7Test`, `OperationalApprovalApiTest`, `ProjectAclTest`.
- [x] Create `ALPHA_RELEASE_CHECKLIST.md` with command output summaries, blocker status, staging URLs, and sign-off owners.

### Task 2: Replace Mock Quote Intelligence Binding

This task addresses the highest-risk product credibility gap: quote normalization must not look live while still depending on hidden mock intelligence classes. The shipped follow-up docs now describe the supported deterministic alpha default, the dormant `llm` contract, and the env wiring that keeps the mode honest.

After this task, quote ingestion should produce persisted normalization source lines through an honest, supportable processor path, and failures should be visible, sanitized, and operationally traceable.

**Files:**
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Modify or add: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/*`
- Modify: `apps/atomy-q/API/.env.example`
- Modify: `apps/atomy-q/API/README.md`
- Test: `apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php`
- Test: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`
- Follow-up spec: [`ALPHA_TASK2_QUOTE_INTELLIGENCE_SPEC_2026-04-16.md`](./ALPHA_TASK2_QUOTE_INTELLIGENCE_SPEC_2026-04-16.md)
- Follow-up plan: [`ALPHA_TASK2_QUOTE_INTELLIGENCE_IMPLEMENTATION_PLAN_2026-04-16.md`](./ALPHA_TASK2_QUOTE_INTELLIGENCE_IMPLEMENTATION_PLAN_2026-04-16.md)

- [x] Decide the alpha processor mode: alpha now ships with the deterministic processor path, while `llm` remains a documented dormant mode until provider budget and runtime config are available.
- [x] Define the production LLM env contract now so a future adapter can be wired in without changing the ingestion boundary or operator docs.
- [x] Use honestly named deterministic adapters, document the limitation, and keep CI deterministic.
- [x] Ensure live ingestion failures mark quote submissions failed with sanitized error messages.
- [x] Verify normalization source lines are persisted with tenant, RFQ, quote submission, vendor, mapping confidence, and evidence metadata.

### Task 3: Prove Award End-To-End

This task proves the final business outcome of the alpha journey: a buyer can move from a frozen comparison to a saved award decision. It matters because comparison without award persistence is only analysis, not a completed procurement decision.

After this task, the API and WEB should support the full compare-to-award path, including signoff, failure handling, tenant-safe retrieval, and decision-trail evidence.

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AwardController.php`
- Modify: `apps/atomy-q/WEB/src/hooks/use-award.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Test: `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php`
- Test: `apps/atomy-q/WEB/src/hooks/use-award.live.test.ts`
- Test: `apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts`
- Follow-up spec: [`ALPHA_TASK3_AWARD_E2E_SPEC_2026-04-16.md`](./ALPHA_TASK3_AWARD_E2E_SPEC_2026-04-16.md)
- Follow-up plan: [`ALPHA_TASK3_AWARD_E2E_IMPLEMENTATION_PLAN_2026-04-16.md`](./ALPHA_TASK3_AWARD_E2E_IMPLEMENTATION_PLAN_2026-04-16.md)
- Verification update (2026-04-16): focused API, Vitest, and Playwright evidence for the single-winner award path is recorded in [`ALPHA_RELEASE_CHECKLIST.md`](./ALPHA_RELEASE_CHECKLIST.md); the Task 3 spec/plan links above remain the active references.

- [ ] Add a test fixture that freezes a comparison, creates a single-winner award from the selected vendor, signs it off, and verifies tenant-scoped retrieval.
- [ ] Ensure the WEB award page can create an award when no award exists but a final comparison run is available.
- [ ] Persist debrief/decision-trail evidence for award actions.
- [ ] Add live-mode UI failure states for award create/signoff.

### Task 4: Eliminate Live-Mode Seed Fallbacks On Golden Path

This task removes the risk that live alpha validation quietly succeeds because the WEB fell back to seed data. Mock mode remains useful for local demos, but live mode must fail loudly when the API is unavailable or returns malformed data.

After this task, the golden-path hooks should have clear separation between local seed mode and live API mode, with tests proving API failures are surfaced instead of hidden.

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-rfq.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-rfqs.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-quote-submissions.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-award.ts`
- Follow-up spec: [`ALPHA_TASK4_LIVE_MODE_FAIL_LOUD_SPEC_2026-04-17.md`](./ALPHA_TASK4_LIVE_MODE_FAIL_LOUD_SPEC_2026-04-17.md)
- Follow-up plan: [`ALPHA_TASK4_LIVE_MODE_FAIL_LOUD_IMPLEMENTATION_PLAN_2026-04-17.md`](./ALPHA_TASK4_LIVE_MODE_FAIL_LOUD_IMPLEMENTATION_PLAN_2026-04-17.md)

- [x] For each hook, keep seed imports inside explicit `NEXT_PUBLIC_USE_MOCKS === 'true'` branches only.
- [x] Add or keep `.live.test.ts` coverage that rejects API failures instead of falling back.
- [x] Ensure live `undefined` responses fail loudly unless the API contract explicitly allows `null`/empty data.
- [x] Keep local mock mode usable for demos, but label it local-only in docs and test names.
- [x] Update RFQ golden-path pages to render explicit unavailable/recovery states when the live hooks error (instead of collapsing to empty states).
- [x] Record Task 4 verification evidence in [`ALPHA_RELEASE_CHECKLIST.md`](./ALPHA_RELEASE_CHECKLIST.md) and close blocker A3 only when the full live-hook matrix, page tests, and `npm run build` pass with mock mode disabled.

### Task 5: Hide Or Defer Non-Alpha Surfaces

This task narrows the product surface that design partners see. The current app exposes more modules than alpha needs, and several of those routes are placeholders; leaving them visible creates confusion and makes the product look less mature than the core flow actually is.

After this task, alpha navigation should focus on the supported quote-comparison journey, while non-alpha areas are hidden, feature-gated, or explicitly deferred without fake success responses.

**Files:**
- Modify: `apps/atomy-q/WEB/src/config/nav.ts`
- Modify: `apps/atomy-q/WEB/src/components/workspace/active-record-menu.tsx`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/*` only where alpha routes need explicit deferred responses
- Test: `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`

- [ ] Add an alpha-core navigation mode or feature flag that exposes only Dashboard, RFQs/Requisition, Approvals if used, and Settings only if users/roles is productionized.
- [ ] Hide documents, reporting, integrations, negotiations, risk, scenarios, recommendation, handoff, billing/subscription, and settings subroutes unless explicitly signed into alpha scope.
- [ ] For API endpoints left registered, return honest deferred responses where applicable and avoid synthetic success payloads on mutable routes.

Implementation note, 2026-04-17:

- `apps/atomy-q/WEB/src/lib/alpha-mode.ts` now owns the alpha-mode flag, top-level nav visibility, RFQ workspace visibility, and deferred-route detection.
- `apps/atomy-q/WEB/src/components/alpha/alpha-deferred-screen.tsx` provides the shared deferred screen copy for alpha-hidden routes.
- `apps/atomy-q/WEB/src/app/(dashboard)/layout.tsx` and `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/layout.tsx` consume the shared alpha policy to hide non-alpha nav items.
- Representative hidden routes now render the shared deferred screen in alpha mode instead of placeholder-heavy page content.
- Verification coverage for this task lives in the WEB Vitest and Playwright checks recorded alongside the implementation summary.

### Task 6: Decide Minimal Users/Roles Scope

This task forces a product decision on tenant administration for alpha. Either Users & Roles becomes a minimal, real tenant-scoped admin surface, or it is hidden so alpha does not expose partially implemented identity management.

After this task, stakeholders should know whether alpha includes user administration, and the app should reflect that decision consistently in API behavior, navigation, and tests.

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/UserController.php`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.tsx`
- Test: `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php`

- [ ] Choose one: productionize minimal tenant-scoped users/roles list/invite/suspend/reactivate, or hide Settings/Users from alpha.
- [ ] If productionizing, wire controller methods to real identity query/persist interfaces and enforce tenant-scoped 404 semantics.
- [ ] If hiding, remove the nav route from alpha mode and document post-alpha ownership.

### Task 7: Regenerate API Contract And WEB Client

This task realigns the API contract and generated frontend client after recent backend changes. It matters because alpha readiness depends on the WEB consuming the same shapes the Laravel API actually exposes, especially around vendors, awards, comparison, and normalization.

After this task, `openapi.json`, generated client code, and manual hook mappings should be synchronized, and build failures from contract drift should be resolved before staging validation.

**Files:**
- Modify: `apps/atomy-q/openapi/openapi.json`
- Modify: `apps/atomy-q/WEB/src/generated/api/**`
- Modify as needed after generation drift is visible: `apps/atomy-q/WEB/src/hooks/use-award.ts`, `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts`, `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`, `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`, `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`

- [ ] Run `cd apps/atomy-q/API && php artisan scramble:export --path=../openapi/openapi.json`.
- [ ] Run `cd apps/atomy-q/WEB && npm run generate:api`.
- [ ] Run `cd apps/atomy-q/WEB && npm run build`.
- [ ] Fix any drift in award/vendor/comparison/normalization consumers.

### Task 8: Staging Operations Readiness

This task makes the release deployable, not just coded. It documents and verifies the environment, queue, storage, AI/provider, CORS, and URL contracts needed for a design-partner staging environment.

After this task, a new operator should be able to configure staging, run the golden-path smoke test with mocks off, and understand which external services are required for quote ingestion and notifications.

**Files:**
- Modify: `apps/atomy-q/API/README.md`
- Modify: `apps/atomy-q/WEB/README.md`
- Create: `apps/atomy-q/docs/STAGING_ALPHA_RUNBOOK.md`

- [ ] Document required API env: `JWT_SECRET`, DB, Redis, CORS, storage disk, mail/notification settings, quote-intelligence provider settings, feature flags.
- [ ] Document required WEB env: `NEXT_PUBLIC_API_URL`, `NEXT_PUBLIC_USE_MOCKS=false`, Playwright base URL.
- [ ] Smoke upload storage with real configured disk.
- [ ] Smoke queue worker for quote ingestion if ingestion is async.
- [ ] Smoke tenant registration, login, RFQ creation, quote upload, normalization, comparison final, award signoff.

### Task 9: Final Alpha Release Gate

This task is the final go/no-go checkpoint. It consolidates blocker status, verification evidence, staging smoke results, and accepted deferments so alpha is declared from evidence rather than optimism.

After this task, the team should have a release-ready checklist that clearly states whether alpha can ship, what was intentionally deferred, and what must be addressed immediately after partner onboarding.

**Files:**
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
- Modify: package/app `IMPLEMENTATION_SUMMARY.md` files only where code changed

- [ ] All blockers A1-A8 are closed or explicitly accepted as design-partner constraints.
- [ ] WEB lint/build/unit gates pass.
- [ ] API alpha matrix passes against a clean test database.
- [ ] At least one staging golden-path smoke passes with mocks off.
- [ ] No active alpha instruction points to archived docs.
- [ ] Any intentionally deferred feature has owner, rationale, and post-alpha target.

## 10. Verification Matrix

| Gate | Command | Required for alpha |
|---|---|---|
| WEB lint | `cd apps/atomy-q/WEB && npm run lint` | Yes |
| WEB build | `cd apps/atomy-q/WEB && npm run build` | Yes |
| WEB unit | `cd apps/atomy-q/WEB && npm run test:unit` | Yes |
| WEB E2E mock smoke | `cd apps/atomy-q/WEB && npm run test:e2e -- tests/screen-smoke.spec.ts` | Useful but not sufficient |
| WEB E2E live smoke | `E2E_USE_REAL_API=1 PLAYWRIGHT_USE_EXISTING_SERVER=1 npm run test:e2e -- tests/rfq-lifecycle-e2e.spec.ts` | Yes before release |
| API full suite | `cd apps/atomy-q/API && php artisan test` | Preferred |
| API alpha suite | `cd apps/atomy-q/API && php artisan test --filter "RegisterCompanyTest|AuthTest|RfqLifecycleMutationTest|RfqInvitationReminderTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest|NormalizationReviewWorkflowTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest|VendorWorkflowTest|IdentityGap7Test|OperationalApprovalApiTest|ProjectAclTest"` | Minimum |
| OpenAPI export | `cd apps/atomy-q/API && php artisan scramble:export --path=../openapi/openapi.json` | Yes after API changes |
| Client generation | `cd apps/atomy-q/WEB && npm run generate:api` | Yes after OpenAPI export |

## 11. Documentation Governance

- This file is the active alpha release plan.
- `apps/atomy-q/docs/README.md` is the active Atomy-Q docs index.
- Archived docs under `apps/atomy-q/docs/archive/2026-04-15-superseded-alpha-docs/` are historical references only.
- New Atomy-Q alpha docs must be placed in `apps/atomy-q/docs/`.
- Package-local implementation summaries remain next to the code they summarize.
- General architecture standards remain under `docs/project/` and still apply.
