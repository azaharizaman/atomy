# Alpha Progress Gap 5 Comparison Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the synthetic comparison preview, matrix, and readiness behavior in Atomy-Q with a live tenant-scoped workflow that uses `QuotationIntelligence`, persists real comparison runs, and renders the WEB comparison surfaces from actual API data.

**Architecture:** Keep `Nexus\QuotationIntelligence` as the comparison computation boundary and let the Atomy-Q API bind it into the app layer with tenant-safe request validation and persistence. The `ComparisonRunController` should create and read real preview/final run rows, while `GET /matrix` and `GET /readiness` serve persisted payloads instead of placeholder values. The WEB app should fetch run list, run detail, matrix, and readiness data through small hooks, then remove the static comparison table and fake lock/unlock UX from the alpha surface. Mock/demo behavior stays available behind the existing mock flag, but live mode must never depend on seed-only placeholders.

**Tech Stack:** PHP 8.3, Laravel 12, PHPUnit, Next.js 16, React, Vitest, Nexus `QuotationIntelligence`, Scramble/OpenAPI.

---

## File Structure

### Layer 3: `apps/atomy-q/API`

**Create**
- `apps/atomy-q/API/app/Http/Requests/ComparisonPreviewRequest.php` - validate the preview request payload and keep preview/final input parity.
- `apps/atomy-q/API/tests/Feature/ComparisonRunWorkflowTest.php` - cover preview, matrix, readiness, wrong-tenant access, and alpha-only control responses.

**Modify**
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php` - replace synthetic preview/matrix/readiness/lock/unlock behavior with live tenant-scoped run persistence and honest alpha responses.
- `apps/atomy-q/API/app/Providers/AppServiceProvider.php` - bind the QuotationIntelligence comparison services needed by the controller.
- `apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php` - extend final-freeze assertions to cover persisted matrix/readiness payloads.
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` - document the live comparison workflow and deferred beta controls.

### Layer 3: `apps/atomy-q/WEB`

**Create**
- `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts` - fetch the live run detail payload.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts` - fetch the live matrix payload for a run.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts` - fetch the live readiness payload for a run.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.test.tsx` - verify the live comparison detail page renders matrix and readiness data.

**Modify**
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx` - remove the static vendor table, hardcoded run copy, and fake lock/unlock controls.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx` - tighten the snapshot-frozen banner assertions so it only appears for real final runs.
- `apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts` - only if the live API response shape needs additional normalization fields after the backend change.
- `apps/atomy-q/WEB/src/hooks/use-comparison-readiness.ts` - only if the RFQ-level freeze CTA needs a shared live-readiness adjustment after the run-level hook lands.
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` - document the live comparison run list/detail behavior.
- `apps/atomy-q/WEB/BACKEND_API_GAPS.md` - close the comparison workflow gap and record the remaining beta-only controls.

### Docs and contract artifacts

**Modify**
- `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md` - mark comparison workflow gap 5 as closed and move lock/unlock / richer comparison tooling into the beta backlog.
- `apps/atomy-q/openapi/openapi.json` - regenerate if the comparison run response schema changes.
- `apps/atomy-q/WEB/src/generated/api/*` - regenerate if the OpenAPI export changes the comparison run client contract.

---

## Task 1: Make the API comparison workflow live and tenant-safe

**Files:**
- Create: `apps/atomy-q/API/app/Http/Requests/ComparisonPreviewRequest.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Modify: `apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php`
- Create: `apps/atomy-q/API/tests/Feature/ComparisonRunWorkflowTest.php`

- [ ] **Step 1: Write the failing API tests**

  Add feature coverage that proves:
  - `POST /comparison-runs/preview` returns a real persisted preview run ID instead of a synthetic `cr-preview-*` value.
  - Preview, matrix, and readiness responses are populated from live payloads, not empty shells.
  - `GET /comparison-runs/{id}/matrix` and `GET /comparison-runs/{id}/readiness` return the stored live data for the requested run.
  - Final freeze persists the comparison snapshot plus the live matrix/readiness payloads and still writes the decision-trail anchor.
  - Cross-tenant access to a run returns `404`, not `403`.
  - Lock/unlock and scoring-model controls return an explicit alpha-not-supported response instead of toggling fake state.

- [ ] **Step 2: Run the API test file to confirm the current baseline fails**

  Run:
  ```bash
  cd apps/atomy-q/API
  ./vendor/bin/phpunit tests/Feature/ComparisonRunWorkflowTest.php tests/Feature/ComparisonSnapshotWorkflowTest.php
  ```

  Expected: FAIL because preview is still synthetic, matrix/readiness are still placeholder responses, and the controller does not yet bind the live comparison services.

- [ ] **Step 3: Implement the live comparison wiring**

  Update the controller and service bindings so the API uses the existing QuotationIntelligence comparison boundary instead of placeholder responses:
  - bind `BatchQuoteComparisonCoordinatorInterface`, `ComparisonReadinessValidatorInterface`, `QuoteComparisonMatrixServiceInterface`, and `ApprovalGateServiceInterface` to the package implementations in `AppServiceProvider`
  - use tenant-scoped quote submission IDs as the document set passed into preview/final comparison runs
  - persist preview and final comparison rows with real `matrix_payload`, `scoring_payload`, `approval_payload`, `readiness_payload`, and `response_payload` values
  - keep `DecisionTrailRecorder` for the final snapshot-frozen audit event
  - serve `matrix` and `readiness` directly from the stored comparison run payloads so the endpoints stay honest for both preview and final runs
  - return `422` with explicit beta-only messaging for lock/unlock and scoring-model actions rather than mutating fake state

- [ ] **Step 4: Re-run the API tests**

  Run:
  ```bash
  cd apps/atomy-q/API
  ./vendor/bin/phpunit tests/Feature/ComparisonRunWorkflowTest.php tests/Feature/ComparisonSnapshotWorkflowTest.php
  ```

  Expected: PASS.

- [ ] **Step 5: Commit**

  ```bash
  git add apps/atomy-q/API/app/Http/Requests/ComparisonPreviewRequest.php \
          apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php \
          apps/atomy-q/API/app/Providers/AppServiceProvider.php \
          apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php \
          apps/atomy-q/API/tests/Feature/ComparisonRunWorkflowTest.php
  git commit -m "feat(api): make comparison workflow live"
  ```

---

## Task 2: Replace the WEB comparison placeholders with live run hooks and views

**Files:**
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.test.tsx`
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-run.test.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.test.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.test.ts`

- [ ] **Step 1: Write the failing WEB tests**

  Add unit and page coverage that proves:
  - the new hooks read the live `/comparison-runs/{id}`, `/matrix`, and `/readiness` endpoints and normalize the payloads the page needs
  - the comparison detail page renders live matrix rows, readiness state, and the run’s actual identifier
  - the page no longer renders the hardcoded vendor names, hardcoded scoring model, or fake lock/unlock controls
  - the comparison list page only shows the frozen snapshot banner when the run hook reports a real final run

- [ ] **Step 2: Run the targeted WEB tests to confirm the current baseline fails**

  Run:
  ```bash
  cd apps/atomy-q/WEB
  npx vitest run \
    src/hooks/use-comparison-run.test.ts \
    src/hooks/use-comparison-run-matrix.test.ts \
    src/hooks/use-comparison-run-readiness.test.ts \
    src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.test.tsx \
    src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/'[runId]'/page.test.tsx
  ```

  Expected: FAIL because the live hooks and detail page do not exist yet and the page still uses static comparison content.

- [ ] **Step 3: Implement the live hooks and detail page**

  Build the WEB surface around the live comparison run endpoints:
  - add a run-detail hook that fetches the persisted run payload
  - add a matrix hook that consumes the live matrix endpoint
  - add a readiness hook that consumes the live readiness endpoint
  - refactor the comparison detail page to render the live matrix and readiness state
  - remove the static vendor table, fake recommendation copy, and lock/unlock toggle from the alpha surface
  - keep the list page banner logic tied to the actual live `final` run rows returned by the list hook

- [ ] **Step 4: Re-run the WEB tests**

  Run:
  ```bash
  cd apps/atomy-q/WEB
  npx vitest run \
    src/hooks/use-comparison-run.test.ts \
    src/hooks/use-comparison-run-matrix.test.ts \
    src/hooks/use-comparison-run-readiness.test.ts \
    src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.test.tsx \
    src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/'[runId]'/page.test.tsx
  ```

  Expected: PASS.

- [ ] **Step 5: Commit**

  ```bash
  git add apps/atomy-q/WEB/src/hooks/use-comparison-run.ts \
          apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts \
          apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts \
          apps/atomy-q/WEB/src/hooks/use-comparison-run.test.ts \
          apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.test.ts \
          apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.test.ts \
          apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/[runId]/page.tsx \
          apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.test.tsx \
          apps/atomy-q/WEB/src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/[runId]/page.test.tsx
  git commit -m "feat(web): render live comparison runs"
  ```

---

## Task 3: Sync the docs, generated contract artifacts, and final verification

**Files:**
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/BACKEND_API_GAPS.md`
- Modify: `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`
- Modify: `apps/atomy-q/openapi/openapi.json` - only if the comparison response schemas changed.
- Modify: `apps/atomy-q/WEB/src/generated/api/*` - only if the OpenAPI export changed.

- [ ] **Step 1: Update the docs and regenerate contracts if needed**

  Update the API and WEB implementation summaries so they describe the live comparison preview, matrix, readiness, and frozen-run behavior. Close the comparison workflow entry in `WEB/BACKEND_API_GAPS.md` and move the remaining comparison controls into the documented beta backlog. Update the alpha analysis doc so comparison workflow gap 5 is no longer listed as a blocking alpha issue.

  If the API response schemas changed, regenerate the OpenAPI export first, then refresh the WEB generated client artifacts before touching tests that depend on the new shapes.

- [ ] **Step 2: Run final verification commands**

  Run:
  ```bash
  cd apps/atomy-q/API
  ./vendor/bin/phpunit tests/Feature/ComparisonRunWorkflowTest.php tests/Feature/ComparisonSnapshotWorkflowTest.php

  cd ../WEB
  npx vitest run \
    src/hooks/use-comparison-run.test.ts \
    src/hooks/use-comparison-run-matrix.test.ts \
    src/hooks/use-comparison-run-readiness.test.ts \
    src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.test.tsx \
    src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/'[runId]'/page.test.tsx

  npm run lint
  ```

  Expected: all targeted tests pass and lint stays green.

- [ ] **Step 3: Commit**

  ```bash
  git add apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md \
          apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md \
          apps/atomy-q/WEB/BACKEND_API_GAPS.md \
          apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md \
          apps/atomy-q/openapi/openapi.json \
          apps/atomy-q/WEB/src/generated/api
  git commit -m "docs: close alpha comparison workflow gap"
  ```

---

## Acceptance Criteria

- `POST /comparison-runs/preview` returns a real persisted preview result or an explicit readiness-blocked error, never a synthetic `uniqid()` response.
- `GET /comparison-runs/{id}/matrix` and `GET /comparison-runs/{id}/readiness` return live data from the stored run payloads.
- `GET /comparison-runs/{id}` and the comparison-run list page show only real run state.
- The comparison detail page renders the live matrix and readiness state instead of the hardcoded vendor table.
- The alpha surface no longer exposes fake lock/unlock behavior, and beta-only controls are called out explicitly in the backend and docs.
- The alpha docs, implementation summaries, and generated contracts stay aligned with the live comparison workflow.
