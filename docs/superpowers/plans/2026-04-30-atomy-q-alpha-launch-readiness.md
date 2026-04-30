# Atomy-Q Alpha Launch Readiness Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the final alpha release blockers defined by the superseding alpha launch readiness design so Atomy-Q can reach either AI-enabled alpha or explicitly disclosed manual-continuity alpha.

**Architecture:** The work is split by release gate: WEB build health, API golden path, AI truthfulness/provenance, route-surface control, and staging evidence. Each slice has its own tests and evidence update so failures remain attributable and do not blur into a single release task.

**Tech Stack:** Laravel 12, PHP 8.3, PHPUnit, Next.js 16, React, TypeScript, ESLint, Vitest, Playwright, Scramble OpenAPI, generated API client.

---

## Source Documents

- Design: `docs/superpowers/specs/2026-04-30-atomy-q-alpha-launch-readiness-design.md`
- Current release plan: `apps/atomy-q/docs/02-release-management/current-release/release-plan.md`
- Evidence ledger: `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`
- Blocker ledger: `apps/atomy-q/docs/02-release-management/current-release/blockers.md`
- Staging runbook: `apps/atomy-q/docs/02-release-management/current-release/staging-runbook.md`
- Package reference: `docs/project/NEXUS_PACKAGES_REFERENCE.md`

## Scope Split

This plan intentionally decomposes the readiness design into independently testable remediation tracks:

1. WEB release gates: lint, build, unit coverage, generated-client safety.
2. API golden path: RFQ duplication, quote ingestion/reparse, comparison freeze, decision trail, award signoff.
3. AI truthfulness and artifacts: dashboard provider failure, unavailable artifacts, vendor recommendation artifact persistence.
4. Route and surface classification: alpha-supported, supporting internal, deferred, hidden, experimental.
5. Staging evidence and disclosure: mocks-off smoke, environment posture, customer/operator disclosure, sign-off.

Do not weaken manual continuity to make AI-enabled gates pass. Manual continuity is a permanent product requirement.

## File Structure

### WEB Release Gate Repair

- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/projects/[projectId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/esg-compliance/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/esg-compliance/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

### API Golden Path Repair

- Modify: `apps/atomy-q/API/app/Models/RfqLineItem.php`
- Modify: `apps/atomy-q/API/database/factories/RfqLineItemFactory.php`
- Modify: `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLineItemPersist.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php`
- Modify: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/ProviderQuoteContentProcessor.php`
- Modify: `apps/atomy-q/API/app/Adapters/Ai/DTOs/DocumentExtractionRequest.php`
- Modify comparison services found by `rg -n "absolutePath|DocumentExtractionRequest|decision-trail|snapshot" apps/atomy-q/API/app`.
- Modify: `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/ComparisonRunWorkflowTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

### AI Truthfulness And Artifact Repair

- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DashboardController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php`
- Modify AI artifact persistence services found by `rg -n "AiArtifact|feature_key|vendor_ai_ranking" apps/atomy-q/API/app`.
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/DashboardReportAiSummaryApiTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationApiTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationAiGateTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/RfqRecommendationDecisionTrailTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

### Route And Surface Control

- Modify: `apps/atomy-q/WEB/src/components/layout/main-sidebar-nav.tsx`
- Modify affected route pages under `apps/atomy-q/WEB/src/app/(dashboard)/`.
- Modify affected Laravel API routes/controllers under `apps/atomy-q/API/routes/` and `apps/atomy-q/API/app/Http/Controllers/Api/V1/`.
- Modify: `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/blockers.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`

### Staging Evidence And Disclosure

- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/staging-runbook.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-overview.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/exit-criteria.md`
- Modify: `apps/atomy-q/docs/01-product/journeys/supported-flows.md`

## Task 1: Repair WEB Local Gates

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/projects/[projectId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/esg-compliance/page.tsx`
- Test: `apps/atomy-q/WEB/src/app/(dashboard)/vendors/[vendorId]/esg-compliance/page.test.tsx`
- Test: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Reproduce the WEB failures**

Run:

```bash
cd apps/atomy-q/WEB
npm run lint
npm run build
```

Expected before repair: lint reports the current `no-explicit-any` and React compiler memoization failures, and build rejects the governance generation call in `vendors/[vendorId]/esg-compliance/page.tsx`.

- [ ] **Step 2: Replace explicit `any` in project detail and quote intake pages**

Open the exact lint output files. Replace `any` with generated API types from `apps/atomy-q/WEB/src/generated/api/types.gen.ts` or a page-local narrow interface. Do not use `unknown as` to silence the rule.

Run:

```bash
cd apps/atomy-q/WEB
npx eslint "src/app/(dashboard)/projects/[projectId]/page.tsx" "src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx"
```

Expected: the two files no longer report `@typescript-eslint/no-explicit-any`.

- [ ] **Step 3: Fix award page memoization without removing state safety**

Open `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`. Move unstable derived objects/functions into `useMemo` or `useCallback` according to the lint diagnostic. Keep dependency arrays complete; do not suppress React compiler rules.

Run:

```bash
cd apps/atomy-q/WEB
npx eslint "src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx"
```

Expected: no React compiler memoization preservation error remains.

- [ ] **Step 4: Pass required mutation arguments for governance generation**

Open `apps/atomy-q/WEB/src/hooks/use-vendor-governance.ts` and read the mutation input shape for `useGenerateVendorGovernanceNarrative`. Then update:

```tsx
onGenerate={() => generateGovernanceMutation.mutate({
  vendorId,
})}
```

If `useGenerateVendorGovernanceNarrative` requires a payload field beyond `vendorId`, update the hook so the page-level mutation accepts `{ vendorId: string }` and the hook owns the generated-client request shape. Do not invent placeholder request values in the page.

Run:

```bash
cd apps/atomy-q/WEB
npx vitest run "src/app/(dashboard)/vendors/[vendorId]/esg-compliance/page.test.tsx"
npm run build
```

Expected: the ESG compliance page test passes and production build exits 0.

- [ ] **Step 5: Run full WEB verification**

Run:

```bash
cd apps/atomy-q/WEB
npm run lint
npm run test:unit
npm run build
```

Expected: all commands exit 0. Record warnings that remain non-blocking in `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`.

- [ ] **Step 6: Commit WEB gate repair**

Run:

```bash
git add apps/atomy-q/WEB
git commit -m "fix: restore atomy q web release gates"
```

Expected: commit succeeds with only WEB files and related summary updates.

## Task 2: Repair RFQ Duplication And Quote Intake Status Transitions

**Files:**
- Modify: `apps/atomy-q/API/app/Models/RfqLineItem.php`
- Modify: `apps/atomy-q/API/database/factories/RfqLineItemFactory.php`
- Modify: `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLineItemPersist.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php`
- Modify: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/ProviderQuoteContentProcessor.php`
- Test: `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php`
- Test: `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`
- Test: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`
- Test: `apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Reproduce the targeted API failures**

Run:

```bash
cd apps/atomy-q/API
DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan test --filter "RfqLifecycleMutationTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest"
```

Expected before repair: RFQ duplication and quote intake readiness failures reproduce without requiring local PostgreSQL.

- [ ] **Step 2: Fix RFQ line-item `specifications` storage shape**

Make `specifications` consistently string-or-null across model, factory, duplication, and controller response paths. The database column is text, so do not cast it as JSON.

Required model outcome in `apps/atomy-q/API/app/Models/RfqLineItem.php`:

```php
protected $casts = [
    'quantity' => 'decimal:4',
    'unit_price' => 'decimal:4',
];
```

Required factory outcome in `apps/atomy-q/API/database/factories/RfqLineItemFactory.php`:

```php
'specifications' => fake()->optional()->sentence(),
```

Required duplication behavior in `AtomyRfqLineItemPersist`: copied line items preserve the exact string or `null` value without JSON encoding.

- [ ] **Step 3: Add a regression assertion for duplicated specifications**

In `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php`, extend the duplicate RFQ test so the copied line item asserts:

```php
$this->assertSame('Rack units', $duplicatedLineItem->specifications);
```

Run:

```bash
cd apps/atomy-q/API
DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan test --filter RfqLifecycleMutationTest
```

Expected: duplicated line-item specifications are not JSON quoted.

- [ ] **Step 4: Make quote upload and reparse transitions deterministic**

Trace the failing status path through `QuoteSubmissionWorkflowTest`, `QuoteIngestionPipelineTest`, and `QuoteIngestionIntelligenceTest`. A valid inline deterministic processing result must end in `ready`; provider or parser failure must end in a domain-safe failed/unavailable state with `INTELLIGENCE_FAILED` only when the provider intelligence path genuinely fails.

Run:

```bash
cd apps/atomy-q/API
DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan test --filter "QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest"
```

Expected: valid inline quote processing reaches `ready`, reparse transitions are explainable, and failure responses use existing domain error envelopes instead of raw exceptions.

- [ ] **Step 5: Commit RFQ and quote-intake repair**

Run:

```bash
git add apps/atomy-q/API/app/Models/RfqLineItem.php \
  apps/atomy-q/API/database/factories/RfqLineItemFactory.php \
  apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLineItemPersist.php \
  apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php \
  apps/atomy-q/API/app/Adapters/QuotationIntelligence/ProviderQuoteContentProcessor.php \
  apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php \
  apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php \
  apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php \
  apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php \
  apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md
git commit -m "fix: repair rfq duplication and quote intake readiness"
```

Expected: commit contains only RFQ/quote-intake repair files and summary updates.

## Task 3: Repair Comparison File Handling, Freeze, And Decision Trail

**Files:**
- Modify: `apps/atomy-q/API/app/Adapters/Ai/DTOs/DocumentExtractionRequest.php`
- Modify comparison services found by `rg -n "absolutePath|DocumentExtractionRequest|decision-trail|snapshot" apps/atomy-q/API/app`
- Test: `apps/atomy-q/API/tests/Feature/ComparisonRunWorkflowTest.php`
- Test: `apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php`
- Test: `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Reproduce comparison and award failures**

Run:

```bash
cd apps/atomy-q/API
DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan test --filter "ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest"
```

Expected before repair: missing local files can surface as `absolutePath must resolve to an existing filesystem path`, blocking comparison preview/freeze or downstream decision trail.

- [ ] **Step 2: Convert missing-file conditions into domain-safe unavailable/failure states**

Keep `DocumentExtractionRequest` strict for provider payload construction. Fix callers so they check file existence before constructing the DTO and return a quote/comparison domain failure where the business flow can continue or explain the block.

Required behavior:

- valid persisted normalized data can create preview and final comparison without requiring the original uploaded file to still exist,
- missing quote files never bubble raw `InvalidArgumentException` to HTTP 500,
- decision-trail write paths record the comparison/award facts that exist and expose missing evidence as a structured reason.

- [ ] **Step 3: Add regression coverage for missing-file comparison behavior**

In `ComparisonRunWorkflowTest` or `ComparisonSnapshotWorkflowTest`, add a test that deletes or hides the uploaded quote file after normalized data exists, then requests comparison preview/freeze.

Expected assertion shape:

```php
$response->assertStatus(200);
$response->assertJsonPath('data.status', 'ready');
```

For comparison preview and final comparison, the expected alpha behavior is success from persisted normalized data after the original quote file is missing. For any provider-only evidence regeneration endpoint that still requires the file, assert a 422 with a domain error code rather than a raw 500.

- [ ] **Step 4: Verify comparison through award signoff**

Run:

```bash
cd apps/atomy-q/API
DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan test --filter "ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest"
```

Expected: comparison preview, final freeze, decision-trail evidence, award creation, and award signoff pass.

- [ ] **Step 5: Commit comparison repair**

Run:

```bash
git add apps/atomy-q/API/app \
  apps/atomy-q/API/tests/Feature/ComparisonRunWorkflowTest.php \
  apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php \
  apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php \
  apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md
git commit -m "fix: handle comparison evidence files safely"
```

Expected: commit contains comparison, decision-trail, award test, and summary updates only.

## Task 4: Repair AI Truthfulness And Artifact Persistence

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DashboardController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php`
- Modify AI artifact persistence services found by `rg -n "AiArtifact|feature_key|vendor_ai_ranking" apps/atomy-q/API/app`
- Test: `apps/atomy-q/API/tests/Feature/Api/V1/DashboardReportAiSummaryApiTest.php`
- Test: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationApiTest.php`
- Test: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationAiGateTest.php`
- Test: `apps/atomy-q/API/tests/Feature/Api/V1/RfqRecommendationDecisionTrailTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Reproduce AI truthfulness failures**

Run:

```bash
cd apps/atomy-q/API
DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan test --filter "DashboardReportAiSummaryApiTest|VendorRecommendationApiTest|VendorRecommendationAiGateTest|RfqRecommendationDecisionTrailTest"
```

Expected before repair: dashboard provider failure and vendor recommendation artifact persistence failures reproduce.

- [ ] **Step 2: Return unavailable dashboard artifacts on provider failure**

Update dashboard/report AI summary handling so provider exceptions become structured unavailable artifacts while deterministic dashboard/report facts remain present.

Required response properties:

```json
{
  "available": false,
  "status": "unavailable",
  "reason_code": "provider_unavailable"
}
```

Do not return fake summary text. Do not drop factual dashboard/report metrics.

- [ ] **Step 3: Persist `vendor_ai_ranking` artifacts when recommendation succeeds**

Update the vendor recommendation path so successful provider-backed recommendation writes a durable artifact with:

- `feature_key` = `vendor_ai_ranking`,
- capability group for sourcing recommendation intelligence,
- tenant scope,
- source fact hash or equivalent source-fact fingerprint,
- provider/model provenance when available,
- reason codes and generated timestamp.

Run:

```bash
cd apps/atomy-q/API
DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan test --filter "VendorRecommendationApiTest|RfqRecommendationDecisionTrailTest"
```

Expected: artifact lookup by `feature_key = vendor_ai_ranking` succeeds and decision-trail review can surface the artifact.

- [ ] **Step 4: Verify unavailable AI-only behavior**

Run:

```bash
cd apps/atomy-q/API
DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan test --filter VendorRecommendationAiGateTest
```

Expected: unavailable/degraded AI-only ranking returns an unavailable response, not synthetic ranking success. Manual vendor selection remains outside the AI-only ranking contract.

- [ ] **Step 5: Commit AI truthfulness repair**

Run:

```bash
git add apps/atomy-q/API/app \
  apps/atomy-q/API/tests/Feature/Api/V1/DashboardReportAiSummaryApiTest.php \
  apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationApiTest.php \
  apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationAiGateTest.php \
  apps/atomy-q/API/tests/Feature/Api/V1/RfqRecommendationDecisionTrailTest.php \
  apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md
git commit -m "fix: make ai readiness failures truthful"
```

Expected: commit contains AI truthfulness, artifact persistence, tests, and summary updates only.

## Task 5: Run The Alpha API Matrix And Generated Contract Gates

**Files:**
- Modify: `apps/atomy-q/API/openapi/openapi.json`
- Modify generated files under: `apps/atomy-q/WEB/src/generated/api/`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Run the alpha API matrix on the intended local database posture**

Run:

```bash
cd apps/atomy-q/API
php artisan migrate:fresh --seed
php artisan test --filter "RegisterCompanyTest|AuthTest|RfqLifecycleMutationTest|RfqInvitationReminderTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest|NormalizationReviewWorkflowTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest|VendorWorkflowTest|IdentityGap7Test|OperationalApprovalApiTest|ProjectAclTest|DashboardReportAiSummaryApiTest|RiskComplianceAiInsightsApiTest|VendorGovernanceApiTest|VendorRecommendationApiTest|VendorRecommendationAiGateTest|AiStatusApiTest"
```

Expected: migrations and the alpha matrix exit 0 on PostgreSQL. If PostgreSQL on the developer machine is unavailable, stop this task and record the environment blocker in `release-checklist.md`; SQLite may be run as extra contract evidence, but it does not satisfy this step.

- [ ] **Step 2: Run full API suite**

Run:

```bash
cd apps/atomy-q/API
php artisan test
```

Expected: full suite exits 0 or any split is documented with exact failing tests, owner, and release decision impact in `release-checklist.md`.

- [ ] **Step 3: Export OpenAPI and regenerate the WEB client**

Run:

```bash
cd apps/atomy-q/API
php artisan scramble:export --path=../openapi/openapi.json
cd ../WEB
npm run generate:api
npm run lint
npm run test:unit
npm run build
```

Expected: OpenAPI export succeeds, generated client is aligned, and WEB gates remain green after generation.

- [ ] **Step 4: Commit contract closure**

Run:

```bash
git add apps/atomy-q/API/openapi/openapi.json \
  apps/atomy-q/WEB/src/generated/api \
  apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md
git commit -m "chore: refresh alpha api contract evidence"
```

Expected: if generated files changed, the commit contains only generated contract and summary updates. If generated files did not change, create a docs-only evidence commit that records the unchanged export/client result in `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`.

## Task 6: Classify Routes And Surfaces For Alpha

**Files:**
- Modify: `apps/atomy-q/WEB/src/components/layout/main-sidebar-nav.tsx`
- Modify affected route pages under `apps/atomy-q/WEB/src/app/(dashboard)/`
- Modify affected Laravel API routes/controllers under `apps/atomy-q/API/routes/` and `apps/atomy-q/API/app/Http/Controllers/Api/V1/`
- Modify: `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/blockers.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`

- [ ] **Step 1: Inventory exposed WEB and API surfaces**

Run:

```bash
rg -n "href=|router\\.push|routes|Route::" apps/atomy-q/WEB/src apps/atomy-q/API/routes apps/atomy-q/API/app/Http/Controllers/Api/V1
```

Expected: each exposed page/API route can be classified as alpha-supported, alpha-supporting internal, deferred, hidden, or experimental.

- [ ] **Step 2: Hide or defer out-of-scope navigation**

Apply the design classifications:

- Alpha-supported routes remain visible and tested.
- Hidden routes are removed from alpha navigation.
- Deferred routes show explicit deferred behavior and do not fetch hidden live data from WEB.
- Experimental routes are not reachable in external design-partner alpha.

Run:

```bash
cd apps/atomy-q/WEB
NEXT_PUBLIC_ALPHA_MODE=true npx playwright test tests/dashboard-nav.spec.ts tests/screen-smoke.spec.ts
```

Expected: alpha navigation exposes only supported or supporting surfaces, and hidden/deferred surfaces do not produce misleading live behavior.

- [ ] **Step 3: Record classification evidence**

Update `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md` with the route/surface classification result, command, date, executor, commit SHA, and whether any surface is intentionally deferred.

- [ ] **Step 4: Commit surface classification**

Run:

```bash
git add apps/atomy-q/WEB/src \
  apps/atomy-q/WEB/tests/screen-smoke.spec.ts \
  apps/atomy-q/WEB/tests/dashboard-nav.spec.ts \
  apps/atomy-q/API/routes \
  apps/atomy-q/API/app/Http/Controllers/Api/V1 \
  apps/atomy-q/docs/02-release-management/current-release/blockers.md \
  apps/atomy-q/docs/02-release-management/current-release/release-checklist.md
git commit -m "chore: classify alpha route surfaces"
```

Expected: commit contains only route visibility/deferred behavior changes and release evidence docs.

## Task 7: Capture Staging Evidence And Release Disclosure

**Files:**
- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/staging-runbook.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-overview.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/exit-criteria.md`

- [ ] **Step 1: Fill staging environment facts**

Record these fields in `release-checklist.md`:

- staging WEB URL,
- staging API URL,
- release branch,
- commit SHA,
- deployed database posture,
- storage disk,
- queue posture,
- `NEXT_PUBLIC_USE_MOCKS=false`,
- AI posture: `AI_MODE=provider`, `AI_MODE=off`, or explicitly documented equivalent.

- [ ] **Step 2: Run mocks-off staging smoke**

Using `staging-runbook.md`, execute the supported alpha journey:

1. tenant registration or login,
2. RFQ creation,
3. line-item setup,
4. approved vendor selection,
5. vendor invitation,
6. quote upload,
7. source-line review,
8. normalization,
9. comparison freeze,
10. award creation,
11. award signoff,
12. decision-trail review,
13. additional user invite and pending activation verification.

Expected: journey completes against deployed WEB/API with `NEXT_PUBLIC_USE_MOCKS=false`. Attach logs/screenshots or record paths to artifacts in `release-checklist.md`.

- [ ] **Step 3: Select and disclose release posture**

Choose exactly one posture:

- AI-enabled alpha,
- manual-continuity alpha,
- internal alpha only.

For manual-continuity alpha, record customer-facing disclosure text that states which AI-assisted features are unavailable, what manual workflow remains available, and how support is contacted.

- [ ] **Step 4: Capture sign-offs**

Record engineering, product, and operator/staging sign-offs in `release-checklist.md`. No external design-partner launch is valid without all required sign-offs.

- [ ] **Step 5: Commit release evidence**

Run:

```bash
git add apps/atomy-q/docs/02-release-management/current-release/release-checklist.md \
  apps/atomy-q/docs/02-release-management/current-release/staging-runbook.md \
  apps/atomy-q/docs/02-release-management/current-release/release-overview.md \
  apps/atomy-q/docs/02-release-management/current-release/exit-criteria.md
git commit -m "docs: record alpha launch readiness evidence"
```

Expected: commit contains release evidence and disclosure docs only.

## Final Verification

Run all final gates after Tasks 1-7:

```bash
cd apps/atomy-q/WEB
npm run lint
npm run test:unit
npm run build
npm run test:e2e
```

```bash
cd apps/atomy-q/API
php artisan migrate:fresh --seed
php artisan test
php artisan scramble:export --path=../openapi/openapi.json
```

```bash
npm run test:e2e
npm run test:e2e:laravel
npm run test:e2e:api
```

Expected: every command exits 0 or the release remains internal alpha only with explicit blocker records.

## Self-Review Notes

- Spec coverage: the plan maps to the design sections for WEB local gates, API golden path, AI truthfulness/artifacts, route classification, staging evidence, disclosure, and sign-off.
- Scope boundary: this plan does not add beta high-availability AI work beyond preserving the backlog requirement in the design.
- Manual continuity: every AI-related task preserves manual workflow behavior and rejects synthetic AI success.
- Evidence: release evidence is recorded only in current-release docs, while design authority remains in `docs/superpowers/specs/2026-04-30-atomy-q-alpha-launch-readiness-design.md`.
