# Atomy-Q Current Release Checklist

**Date:** 2026-04-15  
**Plan:** `apps/atomy-q/docs/02-release-management/current-release/release-plan.md`  
**Superseding readiness contract:** `docs/superpowers/specs/2026-04-30-atomy-q-alpha-launch-readiness-design.md`  
**Scope:** Current alpha release evidence ledger for Atomy-Q API, WEB, staging readiness, and final Task 9 release gate.  
**Environment:** Local workspace with project dependencies already installed; PostgreSQL, Redis, and MinIO containers were available per operator note. API tests used the project PHPUnit configuration.

## Executive Status

As of 2026-05-01, the superseding launch readiness design is the controlling go/no-go contract. Older green evidence remains historical until revalidated on the current branch under that contract.

Task 1 rectification is **green locally as of 2026-04-15**. The WEB lint/build/unit gates, API alpha matrix, and API full suite now pass after the rectification pass documented below.

The original baseline captured in this document was **not release-ready**. Those failure details are retained as historical context underneath the current evidence.

The strongest current local regression signal is the 2026-05-03 PostgreSQL-backed Task 9 engineering gate pass on `alpha/feature-document`. The release remains internal alpha only until deployed staging smoke, disclosure, and sign-offs are recorded.

## Latest Task 9 Local Engineering Gate Evidence - 2026-05-03

- Operator: Codex.
- Branch: `alpha/feature-document`.
- Local API environment: `APP_ENV=local`, `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5433`, `DB_DATABASE=atomy_dev`.
- Local WEB environment: `NEXT_PUBLIC_USE_MOCKS=false`, `NEXT_PUBLIC_AI_MODE=provider`.
- `cd apps/atomy-q/API && php artisan migrate:fresh --seed`: PASS against PostgreSQL.
- `cd apps/atomy-q/WEB && npm run lint`: PASS with 8 existing warnings and 0 errors.
- `cd apps/atomy-q/WEB && npm run build`: PASS.
- `cd apps/atomy-q/WEB && npm run test:unit`: PASS. 72 files, 290 tests.
- `cd apps/atomy-q/API && php artisan test --filter "RegisterCompanyTest|AuthTest|RfqLifecycleMutationTest|RfqInvitationReminderTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest|NormalizationReviewWorkflowTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest|VendorWorkflowTest|IdentityGap7Test|OperationalApprovalApiTest|ProjectAclTest|DashboardReportAiSummaryApiTest|RiskComplianceAiInsightsApiTest|VendorGovernanceApiTest|VendorRecommendationApiTest|VendorRecommendationAiGateTest|AiStatusApiTest|EvidenceVaultApiTest"`: PASS. 199 tests, 1316 assertions.
- `cd apps/atomy-q/API && php artisan test`: PASS. 657 tests, 2426 assertions.
- `cd apps/atomy-q/API && DB_CONNECTION=sqlite DB_DATABASE=':memory:' APP_URL=http://localhost:8000 php artisan scramble:export --path=../openapi/openapi.json`: PASS. PostgreSQL-backed Scramble export was not used because Scramble schema generation attempted a DB connection that failed under the export command; runtime migrations and tests were verified against PostgreSQL separately.
- `jq empty apps/atomy-q/openapi/openapi.json`: PASS.
- `cd apps/atomy-q/WEB && npm run generate:api`: PASS.
- `cd apps/atomy-q/API && php artisan atomy:verify-storage-disk --disk=s3 --path-prefix=alpha-storage-smoke`: PASS.
- Release impact: local engineering gates are green for this branch, including the RFQ Evidence Vault scope. This still does not close deployed staging smoke, customer/operator disclosure, or required sign-offs.

## Superseding Readiness Assessment - 2026-04-30

This assessment was captured while drafting the superseding alpha launch readiness design. It does not replace a final Task 9 evidence run, but it identifies current no-go signals that must be closed or superseded by newer evidence.

- `cd apps/atomy-q/WEB && npm run lint`: FAIL. Three lint errors were observed: `no-explicit-any` in the project detail page, React compiler memoization preservation failure in the award page, and `no-explicit-any` in quote intake. Eleven warnings were also observed.
- `cd apps/atomy-q/WEB && npm run build`: FAIL. TypeScript rejected `generateGovernanceMutation.mutate()` in `src/app/(dashboard)/vendors/[vendorId]/esg-compliance/page.tsx` because required mutation arguments were missing.
- `cd apps/atomy-q/API && php artisan test --filter "RegisterCompanyTest|AuthTest|RfqLifecycleMutationTest|RfqInvitationReminderTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest|NormalizationReviewWorkflowTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest|VendorWorkflowTest|IdentityGap7Test|OperationalApprovalApiTest|ProjectAclTest|DashboardReportAiSummaryApiTest|RiskComplianceAiInsightsApiTest|VendorGovernanceApiTest|VendorRecommendationApiTest|VendorRecommendationAiGateTest|AiStatusApiTest"`: FAIL in the default local DB posture because PostgreSQL on `127.0.0.1:5433` was unavailable, and also exposed real failures in RFQ duplication, quote ingestion, comparison snapshot/freeze, dashboard AI failure handling, and vendor recommendation artifact persistence.
- `cd apps/atomy-q/API && DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan test --filter "DashboardReportAiSummaryApiTest|RiskComplianceAiInsightsApiTest|VendorGovernanceApiTest|VendorRecommendationApiTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|QuoteIngestionIntelligenceTest|QuoteSubmissionWorkflowTest|RfqLifecycleMutationTest"`: FAIL. 14 failed, 51 passed. Passing slices included risk compliance and vendor governance. Failing slices included RFQ duplication, dashboard provider-failure behavior, vendor recommendation artifact persistence, comparison preview/final missing-file behavior, and quote ingestion readiness.
- `cd orchestrators/InsightOperations && ./vendor/bin/phpunit`: PASS. 16 tests, 84 assertions.
- `cd orchestrators/IntelligenceOperations && ./vendor/bin/phpunit`: NOT RUN. No package-local `vendor/bin/phpunit` exists in that directory.

Current status from this assessment: **internal alpha only / no external design-partner launch** until a newer Task 9 run closes these no-go signals under the superseding spec.

## Latest Superseding Task 5 API Matrix Posture - 2026-05-01

- Operator: Codex.
- Branch: `alpha/launch-readiness`.
- Commit under test: `b388eaf8`.
- Intended database posture from `apps/atomy-q/API/.env`: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5433`, `DB_DATABASE=atomy_dev`.
- `cd apps/atomy-q/API && php artisan migrate:fresh --seed`: BLOCKED. PostgreSQL on `127.0.0.1:5433` refused the connection (`SQLSTATE[08006] [7] connection refused`).
- Extra non-closing regression signal: `cd apps/atomy-q/API && DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan test --filter "RegisterCompanyTest|AuthTest|RfqLifecycleMutationTest|RfqInvitationReminderTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest|NormalizationReviewWorkflowTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest|VendorWorkflowTest|IdentityGap7Test|OperationalApprovalApiTest|ProjectAclTest|DashboardReportAiSummaryApiTest|RiskComplianceAiInsightsApiTest|VendorGovernanceApiTest|VendorRecommendationApiTest|VendorRecommendationAiGateTest|AiStatusApiTest"`: PASS. 177 tests, 1184 assertions.
- Release impact: the superseding Task 5 alpha API matrix is **not satisfied** in the required PostgreSQL posture. SQLite-only evidence may be collected as extra regression signal, but it does not close this gate.
- Next action: start or provision the intended PostgreSQL service, rerun `php artisan migrate:fresh --seed`, then rerun the superseding alpha API matrix before API contract closure is considered release evidence.

## Latest Superseding Task 6 Route Surface Classification - 2026-05-01

- Operator: Codex.
- Branch: `alpha/launch-readiness`.
- Commit under test: `8b726d36`.
- Inventory command: `rg -n "href=|router\.push|routes|Route::" apps/atomy-q/WEB/src apps/atomy-q/API/routes apps/atomy-q/API/app/Http/Controllers/Api/V1`: PASS. The inventory confirms exposed WEB navigation, RFQ workspace links, and API route groups remain classifiable under the alpha surface policy.
- Classification result:
  - Alpha-supported visible top-level WEB surfaces: Dashboard, Requisitions, Vendors.
  - Alpha-supported RFQ workspace surfaces: overview, details, line items, vendors, award, quote intake, comparison runs, approvals, decision trail, Evidence Vault.
  - Intentionally hidden/deferred WEB surfaces: top-level Documents, Reporting, Settings shell, RFQ negotiations, RFQ risk. Settings Users & Roles remains reachable directly as the minimal A5 supporting surface.
  - Supporting API surfaces remain authenticated and tenant-scoped; non-navigation API groups are not external design-partner navigation surfaces.
- Verification: `cd apps/atomy-q/WEB && NEXT_PUBLIC_ALPHA_MODE=true npx playwright test tests/dashboard-nav.spec.ts tests/screen-smoke.spec.ts`: PASS. 3 tests passed.
- Release impact: A4 surface classification remains closed locally under the superseding spec. This does not close staging evidence or PostgreSQL API matrix gates.

## Latest Superseding Task 7 Staging Evidence And Disclosure - 2026-05-01

- Operator: Codex.
- Branch: `alpha/launch-readiness`.
- Commit under review: `dbf8c4e0`.
- Staging WEB URL: not provided.
- Staging API URL: not provided.
- Deployed database posture: not provided.
- Storage disk: not provided.
- Queue posture: not provided.
- `NEXT_PUBLIC_USE_MOCKS=false`: not verified on a deployed WEB environment.
- AI posture: not verified on a deployed API environment.
- Mocks-off staging smoke: NOT RUN. Deployed WEB/API origins and operator-accessible staging environment details are not available in this workspace.
- Release posture selected from the superseding spec options: **internal alpha only** until the PostgreSQL API matrix, deployed mocks-off staging smoke, release disclosure, and required sign-offs are recorded.
- Customer/operator disclosure status: not publishable for design partners yet. Current disclosure is internal only: Atomy-Q remains in internal alpha; AI-assisted availability and manual-continuity claims must not be represented externally until staging evidence and sign-offs are complete.
- Required sign-offs: Engineering pending; Product pending; Operator/Staging pending.
- Release impact: no external design-partner launch is valid from the evidence currently present.

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
- Runtime posture: pending; expected `NEXT_PUBLIC_USE_MOCKS=false`, `AI_MODE=provider`, `NEXT_PUBLIC_AI_MODE=provider`, `QUEUE_CONNECTION=sync`, `AI_DOCUMENT_PARSER_PLUGIN=file-parser`, `AI_DOCUMENT_PDF_ENGINE=mistral-ocr`.
- Storage verification result: pending; expected `php artisan atomy:verify-storage-disk --disk=s3 --path-prefix=alpha-storage-smoke`.
- Queue verification note: pending; not required for the main alpha smoke because the staging posture is `QUEUE_CONNECTION=sync`.
- Golden-path smoke result: pending; requires a true staging mocks-off run against deployed WEB and API origins.

If a true staging mocks-off smoke has not been completed, design-partner readiness is not yet earned and the release remains internal alpha only.

## Latest RFQ Evidence Vault Support Evidence - 2026-05-03

This section records local implementation evidence for the RFQ-local Evidence Vault alpha scope inclusion. It does not replace Task 9 PostgreSQL matrix, deployed mocks-off staging smoke, disclosure, or sign-off evidence.

- Scope: RFQ workspace only. Top-level Documents and generic document API endpoints are removed from the product contract; `/rfqs/{rfqId}/documents` now renders the RFQ Evidence Vault workspace.
- API contract: `GET /rfqs/{rfqId}/evidence-vault`, `POST /rfqs/{rfqId}/evidence-vault/supporting-evidence`, `POST /rfqs/{rfqId}/evidence-vault/award-pack/finalize`, and `GET /rfqs/{rfqId}/evidence-vault/award-pack/export`.
- Local verification:
  - `cd apps/atomy-q/API && php artisan test --filter EvidenceVaultApiTest`: PASS.
  - `cd apps/atomy-q/API && DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan scramble:export --path=../openapi/openapi.json`: PASS.
  - `cd apps/atomy-q/WEB && npm run generate:api`: PASS.
  - `cd apps/atomy-q/WEB && npx vitest run src/components/alpha/alpha-deferred-screen.test.tsx 'src/app/(dashboard)/deferred-routes.test.tsx' 'src/app/(dashboard)/rfqs/[rfqId]/documents/page.test.tsx'`: PASS.
  - `cd apps/atomy-q/WEB && npm run build`: PASS.
- Release impact: RFQ Evidence Vault is locally supported for alpha scope, but external design-partner readiness is still governed by the superseding final release gate.

## Latest Provider Quote Fixture Support Evidence - 2026-04-25

This section is preparatory local-support evidence for the provider-backed quote-upload release gate. It does not replace staging verification or a live-provider smoke run.

- `cd apps/atomy-q/WEB && npm run test:unit:provider-quote-fixtures`: PASS. 1 file, 2 tests. This proves the WEB support loader reads the current `sample/*/metadata.json` folders, ignores `sample/metadata.example.json`, and resolves quotation PDF paths from each requisition folder.
- `cd apps/atomy-q/WEB && npx playwright test tests/provider-quote-e2e.spec.ts --list`: PASS. Fixture-backed fake-provider Playwright entrypoint loads successfully.
- `cd apps/atomy-q/WEB && npx playwright test tests/provider-quote-live.spec.ts --list`: PASS. Live-provider Playwright entrypoint loads successfully with the `AI_PROVIDER_E2E=true` gate still enforced inside the spec.
- Local blocker: end-to-end browser/API verification for provider-backed quote upload and normalization was not run in this slice because no confirmed local API/provider stack was exercised here. The support layer is ready for that run once the target services are available.

## Alpha Change Control Ledger (Post-Task-8 Remediation)

Use this ledger for every remediation/update made during manual verification and pre-release stabilization.

### Required fields per change

- Change ID and PR link.
- Change class: `C1`, `C2`, or `C3` (per `02-release-management/change-management/alpha-change-control.md`).
- Scope statement: explicitly confirm `No scope expansion` or document approved expansion.
- Blocker/task mapping: affected A1 to A8 and Task 1 to 8 references.
- Required gate commands and outcomes.
- Non-regression statement.
- Sign-off owners: Engineering, QA, Alpha Release Owner.

### Ledger table

| Change ID / PR | Class | Scope check | Blocker + task mapping | Verification evidence | Non-regression result | Sign-offs | Status |
|---|---|---|---|---|---|---|---|
| `alpha/remidiation/rfq-line-items` ([#369](https://github.com/azaharizaman/atomy/pull/369)) | C2 | No scope expansion. Limited to RFQ line-items UI/hook remediation, seed fallback hardening, and hook-test standard documentation. | A3 + A8; Task 4 live-mode fail-loud and A8 release-evidence discipline. | 2026-04-18: `npm run test:unit -- src/hooks/use-rfq-line-items.test.ts src/hooks/use-rfq-line-items.live.test.ts 'src/app/(dashboard)/rfqs/[rfqId]/line-items/page.test.tsx' src/data/seed.test.ts` PASS (4 files, 8 tests). `npm run build` PASS. | Live mode now reads RFQ line items via `useRfqLineItems` + `fetchLiveOrFail`; no embedded canned line-item dataset on RFQ line-items page; line-item unavailable state is explicit on live errors; mock fallback remains explicit under `NEXT_PUBLIC_USE_MOCKS=true` only. | Engineering: pending. QA: pending. Alpha Release Owner: pending. | Ready for review |
| pending | pending | pending | pending | pending | pending | pending | pending |

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
