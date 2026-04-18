# Atomy-Q Alpha Release Checklist

**Date:** 2026-04-15  
**Plan:** `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`  
**Scope:** Task 1 release evidence baseline for Atomy-Q API, WEB, and alpha-critical test matrix.  
**Environment:** Local workspace with project dependencies already installed; PostgreSQL, Redis, and MinIO containers were available per operator note. API tests used the project PHPUnit configuration.

## Executive Status

Task 1 rectification is **green locally as of 2026-04-15**. The WEB lint/build/unit gates, API alpha matrix, and API full suite now pass after the rectification pass documented below.

The original baseline captured in this document was **not release-ready**. Those failure details are retained as historical context underneath the current evidence.

The strongest rectification signal is that the alpha-critical backend flows pass together in the matrix: company registration, RFQ lifecycle, invitations, comparison, awards, vendor workflow, identity gap tests, normalization review, quote ingestion pipeline, and operational approvals. Remaining non-Task-1 alpha work should continue from the broader release plan before staging smoke.

## Latest Rectification Evidence - 2026-04-15

- `cd apps/atomy-q/WEB && npm run lint`: PASS. Exit 0 with 7 existing warnings:
  `negotiations/page.tsx` unused `SectionCard`, `overview/page.tsx` missing `comparison` dependency in `useMemo`, `rfqs/page.tsx` unused `_ids`, `settings/page.tsx` unused `Settings`, and unused `api` imports in `use-comparison-run-matrix.ts`, `use-quote-submission.ts`, and `use-rfq-counts.ts`.
- `cd apps/atomy-q/WEB && npm run build`: PASS.
- `cd apps/atomy-q/WEB && npm run test:unit`: PASS. 33 files, 87 tests.
- `cd apps/atomy-q/API && php artisan test --filter "RegisterCompanyTest|AuthTest|RfqLifecycleMutationTest|RfqInvitationReminderTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest|NormalizationReviewWorkflowTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest|VendorWorkflowTest|IdentityGap7Test|OperationalApprovalApiTest|ProjectAclTest"`: PASS. 93 tests, 522 assertions.
- `cd apps/atomy-q/API && php artisan test`: PASS. 425 tests, 1131 assertions.

## Latest Award End-To-End Evidence - 2026-04-16

- `cd apps/atomy-q/API && php artisan test --filter AwardWorkflowTest`: PASS. 9 tests, 49 assertions.
- `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts`: PASS. 13 tests.
- `cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`: PASS. 8 tests.
- `cd apps/atomy-q/WEB && PLAYWRIGHT_USE_EXISTING_SERVER=1 PLAYWRIGHT_BASE_URL=http://localhost:3100 NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e -- tests/rfq-lifecycle-e2e.spec.ts`: PASS. 1 passed, 1 skipped. The verified path is the deterministic mocked-API lifecycle flow; the real-API scenario remains skipped unless `E2E_USE_REAL_API=1` is provided. This run intentionally uses `NEXT_PUBLIC_USE_MOCKS=false` because the award create/signoff controls are disabled in mock mode, while determinism is preserved through explicit route stubs for award create, debrief, and signoff.
- Blocker `A2` status: closed locally for the single-winner alpha path. API feature coverage, WEB live-hook/page coverage, and lifecycle Playwright coverage now all prove compare -> create award -> debrief -> signoff on the award screen.

## Latest Task 4 Live-Mode Fail-Loud Evidence - 2026-04-16

- `cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`: PASS. 5 files, 19 tests. These assert explicit unavailable states for vendors, quote intake, normalize, comparison runs, and award flows.
- `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-rfq.live.test.ts src/hooks/use-rfqs.live.test.ts src/hooks/use-rfq-vendors.live.test.ts src/hooks/use-quote-submissions.live.test.ts src/hooks/use-normalization-source-lines.live.test.ts src/hooks/use-normalization-review.live.test.ts src/hooks/use-comparison-runs.live.test.ts src/hooks/use-comparison-run.live.test.ts src/hooks/use-comparison-run-matrix.live.test.ts src/hooks/use-comparison-run-readiness.live.test.ts src/hooks/use-award.live.test.ts`: PASS. 11 files, 57 tests. This is the Task 4 live-hook fail-loud matrix under `NEXT_PUBLIC_USE_MOCKS=false`.
- `cd apps/atomy-q/WEB && npm run build`: PASS.
- Blocker `A3` status: closed locally. Live mode no longer falls back to seed data on the golden path, and both the page unavailable-state tests and hook live-test matrix now pass with mock mode disabled. The earlier quote upload failure (`ready` expected, `failed` received) is no longer part of the WEB-side golden-path closure evidence for Task 4.

## Latest Task 5 Hide Or Defer Non-Alpha Surfaces Evidence - 2026-04-17

- `cd apps/atomy-q/WEB && npx vitest run src/components/alpha/alpha-deferred-screen.test.tsx src/app/(dashboard)/deferred-routes.test.tsx`: PASS. 2 files, 9 tests. This covers the shared deferred copy, alpha policy helpers, and representative hidden-route rendering.
- `cd apps/atomy-q/WEB && npx eslint src/lib/alpha-mode.ts src/components/alpha/alpha-deferred-screen.tsx src/components/alpha/alpha-deferred-screen.test.tsx src/config/nav.ts 'src/app/(dashboard)/layout.tsx' 'src/app/(dashboard)/rfqs/[rfqId]/layout.tsx' src/components/workspace/active-record-menu.tsx tests/dashboard-nav.spec.ts 'src/app/(dashboard)/documents/page.tsx' 'src/app/(dashboard)/reporting/page.tsx' 'src/app/(dashboard)/settings/page.tsx' 'src/app/(dashboard)/settings/users/page.tsx' 'src/app/(dashboard)/rfqs/[rfqId]/negotiations/page.tsx' 'src/app/(dashboard)/rfqs/[rfqId]/documents/page.tsx' 'src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx' 'src/app/(dashboard)/deferred-routes.test.tsx' tests/screen-smoke.spec.ts`: PASS.
- `cd apps/atomy-q/WEB && NEXT_PUBLIC_ALPHA_MODE=true npx playwright test tests/dashboard-nav.spec.ts`: PASS. 1 test.
- `cd apps/atomy-q/WEB && NEXT_PUBLIC_ALPHA_MODE=true npx playwright test tests/screen-smoke.spec.ts`: PASS. 1 test.

## Latest Task 7 API Contract And WEB Client Evidence - 2026-04-17

- Operator/author: Azahari Zaman.
- `cd apps/atomy-q/API && DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan scramble:export --path=../openapi/openapi.json`: PASS (workspace verification used sqlite override because PostgreSQL was unavailable in this run).
- `cd apps/atomy-q/WEB && npm run generate:api`: PASS.
- `cd apps/atomy-q/WEB && npm run build`: PASS.
- `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts src/hooks/use-users.test.tsx src/hooks/use-rfq-vendors.live.test.ts src/hooks/use-comparison-run.live.test.ts src/hooks/use-comparison-run-matrix.live.test.ts src/hooks/use-normalization-review.live.test.ts`: PASS.
- Remaining accepted wrapper exceptions: none.

## Latest Task 8 Staging-Readiness Evidence - 2026-04-18

- Operator: pending.
- WEB URL: pending.
- API URL: pending.
- Runtime posture: pending; expected `NEXT_PUBLIC_USE_MOCKS=false`, `QUEUE_CONNECTION=sync`, `QUOTE_INTELLIGENCE_MODE=deterministic`.
- Storage verification result: pending; expected `php artisan atomy:verify-storage-disk --disk=s3 --path-prefix=alpha-storage-smoke`.
- Queue verification note: pending; not required for the main alpha smoke because the staging posture is `QUEUE_CONNECTION=sync`.
- Golden-path smoke result: pending; requires a true staging mocks-off run against deployed WEB and API origins.

If a true staging mocks-off smoke has not been completed, design-partner readiness is not yet earned and the release remains internal alpha only.

## Historical Baseline Evidence

## Gate Summary

| Gate | Command | Result | Evidence | Blocker |
|---|---|---|---|---|
| WEB lint | `cd apps/atomy-q/WEB && npm run lint` | Pass with warnings | Exit 0; 0 errors, 7 warnings | Quality cleanup |
| WEB build | `cd apps/atomy-q/WEB && npm run build` | Fail | TypeScript error in `src/lib/api-live.ts:40` casting `Error` to `Record<string, unknown>` | A3 / A8 |
| WEB unit | `cd apps/atomy-q/WEB && npm run test:unit` | Fail | 33 files run; 32 passed, 1 failed. 86/87 tests passed. | A2 |
| API full suite | `cd apps/atomy-q/API && php artisan test` | Fail | 418 passed, 5 failed, 1112 assertions | A2 / A3 / A5 / quality cleanup |
| API alpha matrix | `cd apps/atomy-q/API && php artisan test --filter "RegisterCompanyTest|AuthTest|RfqLifecycleMutationTest|RfqInvitationReminderTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest|NormalizationReviewWorkflowTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest|VendorWorkflowTest|IdentityGap7Test|OperationalApprovalApiTest|ProjectAclTest"` | Fail | 89 passed, 2 failed, 505 assertions | A3 / A5 |

## WEB Gate Details

### WEB Lint

**Status:** Pass with warnings.

Warnings found:

| File | Warning |
|---|---|
| `src/app/(dashboard)/rfqs/[rfqId]/negotiations/page.tsx` | `SectionCard` unused |
| `src/app/(dashboard)/rfqs/[rfqId]/overview/page.tsx` | `React.useMemo` missing `comparison` dependency |
| `src/app/(dashboard)/rfqs/page.tsx` | `_ids` unused |
| `src/app/(dashboard)/settings/page.tsx` | `Settings` unused |
| `src/hooks/use-comparison-run-matrix.ts` | `api` unused |
| `src/hooks/use-quote-submission.ts` | `api` unused |
| `src/hooks/use-rfq-counts.ts` | `api` unused |

**Next action:** clean warnings before release hardening, but this does not block the current baseline because lint exits 0.

### WEB Build

**Status:** Fail.

Failure:

```text
./src/lib/api-live.ts:40:8
Type error: Conversion of type 'Error' to type 'Record<string, unknown>' may be a mistake because neither type sufficiently overlaps with the other. If this was intentional, convert the expression to 'unknown' first.
```

Affected code path: `fetchLiveOrFail` error decoration for live API failures.

**Why it matters:** this is directly related to live-mode fail-loud behavior. The build gate cannot pass until the error metadata decoration is typed safely.

**Next action:** update `src/lib/api-live.ts` to decorate errors through a typed custom error shape or a safe `unknown` intermediate cast, then rerun `npm run build`.

### WEB Unit Tests

**Status:** Fail.

Failure:

```text
FAIL src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx > RfqAwardPage > shows award creation UI when no award exists
Unable to find an element with the text: /Select a vendor to award the contract based on the final comparison run/i.
```

Actual rendered state included:

```text
No award record yet
No award has been created for this RFQ yet.
No award selected
Freeze a comparison run to create an award record.
Finalize Award disabled
Create an award to reveal non-winning vendors.
```

**Why it matters:** this maps to blocker A2. The WEB test expects an award-creation path when no award exists, but the UI currently renders a passive empty state instead of the expected create-award selection flow.

**Next action:** decide whether the test expectation or the UI is correct, then either restore the create-award UI or adjust the test to match the accepted alpha behavior. The alpha plan currently requires proving compare -> award end-to-end, so the likely fix is to implement/restore the create-award UI.

## API Gate Details

### API Full Suite

**Status:** Fail.

Summary:

```text
Tests: 5 failed, 418 passed (1112 assertions)
Duration: 26.08s
```

Failures:

| Test | Failure | Likely blocker |
|---|---|---|
| `Tests\Unit\Models\ModelRelationsTest` | `Call to undefined method App\Models\Award::creator()` | Quality cleanup / award model parity |
| `Tests\Unit\Services\SourcingOperationsAdaptersTest` | SQLite test table `rfqs` has no `project_id` column during duplicate creation | Quality cleanup / test schema drift |
| `Tests\Unit\Services\SourcingOperationsAdaptersTest` | Expected `DuplicateRfqNumberException`, received same SQLite `project_id` column error | Quality cleanup / test schema drift |
| `Tests\Feature\Api\AuthTest` | MFA verify expected 200, received 422 requiring `tenant_id` | A5 |
| `Tests\Feature\QuoteSubmissionWorkflowTest` | Quote upload expected status `ready`, received `failed` | A3 / A1-adjacent ingestion behavior |

**Next action:** prioritize the two failures that also appear in the alpha matrix, then fix full-suite drift items before release branch completion.

### API Alpha Matrix

**Status:** Fail.

Summary:

```text
Tests: 2 failed, 89 passed (505 assertions)
Duration: 7.74s
```

Passing alpha-critical suites in this matrix:

- `RegisterCompanyTest`
- `RfqLifecycleMutationTest`
- `RfqInvitationReminderTest`
- `AwardWorkflowTest`
- `ComparisonRunWorkflowTest`
- `ComparisonSnapshotWorkflowTest`
- `IdentityGap7Test`
- `NormalizationReviewWorkflowTest`
- `OperationalApprovalApiTest`
- `ProjectAclTest`
- `QuoteIngestionIntelligenceTest`
- `QuoteIngestionPipelineTest`
- `VendorWorkflowTest`

Failing alpha-critical tests:

| Test | Failure | Why it matters | Next action |
|---|---|---|---|
| `Tests\Feature\Api\AuthTest::mfa verify endpoint returns message and verified` | Expected 200, received 422 with `tenant_id` required | Auth/MFA contract mismatch affects alpha login security confidence. `IdentityGap7Test` has a passing MFA challenge flow, so this may be an outdated legacy endpoint test or a real request-contract inconsistency. | Align `AuthTest` request payload with current MFA challenge contract or make endpoint resolve tenant from challenge without requiring client-supplied `tenant_id`. |
| `Tests\Feature\QuoteSubmissionWorkflowTest::quote submission upload persists uploaded state and tenant id` | Expected response `data.status = ready`, actual `failed` | Quote ingestion/normalization is alpha-critical. A newly uploaded quote failing immediately means the ingestion path or test fixture contract is not stable. | Inspect quote upload processing failure reason, then decide whether upload should return `uploaded/processing/needs_review/ready` or whether the processor/test fixture must be fixed. |

## Blocker Ledger

| Blocker | Status | Evidence | Owner |
|---|---|---|---|
| A1: Replace mock quote intelligence binding | Open | Not directly validated by Task 1; related ingestion tests include one quote upload failure while quote ingestion intelligence/pipeline tests pass. | Backend / AI integration |
| A2: Prove award end-to-end | Closed locally | `AwardWorkflowTest`, `use-award.live.test.ts`, `award/page.test.tsx`, and the focused lifecycle Playwright flow now pass, including create award, debrief, and signoff. | WEB + API |
| A3: Eliminate live-mode seed fallback on golden path | Closed locally | WEB build, Task 4 page unavailable-state tests, and the full live-hook matrix all pass with `NEXT_PUBLIC_USE_MOCKS=false` as of 2026-04-16. | WEB + Backend quote intake |
| A4: Hide/defer non-alpha surfaces | Open | Not executed in Task 1. | Product + WEB/API |
| A5: Decide minimal Users/Roles scope | Open | API `AuthTest` MFA legacy path requires `tenant_id`; identity gap suite passes, but endpoint contract remains inconsistent. | Backend identity |
| A6: Regenerate API contract and WEB client | Completed | Task 7 evidence recorded on 2026-04-17: OpenAPI export, WEB client generation, WEB build, and alpha hook verification passed. | API + WEB |
| A7: Staging operations readiness | Not started | Not executed in Task 1. | Platform / DevOps |
| A8: Release evidence control | In progress | This checklist now records baseline commands and failures. | Release owner |

## Recommended Fix Order

1. Fix WEB build in `src/lib/api-live.ts`, then rerun `npm run build`.
2. Restore or align the award creation empty-state UI/test, then rerun `npm run test:unit` or at least `npx vitest run src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`.
3. Resolve the API alpha-matrix failures in `AuthTest` and `QuoteSubmissionWorkflowTest`, then rerun the alpha matrix.
4. Fix full-suite drift in `ModelRelationsTest` and `SourcingOperationsAdaptersTest`.
5. After all local gates pass, proceed to Task 2 from the alpha plan.

## Commands Run

```bash
cd apps/atomy-q/WEB && npm run lint
cd apps/atomy-q/WEB && npm run build
cd apps/atomy-q/WEB && npm run test:unit
cd apps/atomy-q/API && php artisan test
cd apps/atomy-q/API && php artisan test --filter "RegisterCompanyTest|AuthTest|RfqLifecycleMutationTest|RfqInvitationReminderTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest|NormalizationReviewWorkflowTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest|VendorWorkflowTest|IdentityGap7Test|OperationalApprovalApiTest|ProjectAclTest"
cd apps/atomy-q/API && php artisan test --filter AwardWorkflowTest
cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts
cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx
cd apps/atomy-q/WEB && PLAYWRIGHT_USE_EXISTING_SERVER=1 PLAYWRIGHT_BASE_URL=http://localhost:3100 NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e -- tests/rfq-lifecycle-e2e.spec.ts
cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx
cd apps/atomy-q/WEB && npx vitest run src/hooks/use-rfq.live.test.ts src/hooks/use-rfqs.live.test.ts src/hooks/use-rfq-vendors.live.test.ts src/hooks/use-quote-submissions.live.test.ts src/hooks/use-normalization-source-lines.live.test.ts src/hooks/use-normalization-review.live.test.ts src/hooks/use-comparison-runs.live.test.ts src/hooks/use-comparison-run.live.test.ts src/hooks/use-comparison-run-matrix.live.test.ts src/hooks/use-comparison-run-readiness.live.test.ts src/hooks/use-award.live.test.ts
```
