# Quote Lifecycle Productionization Implementation Plan

**Status:** Implemented and completed on 2026-03-31.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the existing RFQ -> quote intake -> normalization -> comparison -> award/approval flow into a fully live, tenant-safe, auditable Atomy-Q product path.

**Architecture:** Keep the current three-layer split intact. Layer 1 stays the source of reusable primitives, Layer 2 coordinates quote lifecycle policies and snapshots, and Layer 3 owns Laravel controllers, requests, jobs, and React pages. The implementation should remove seed-only behavior from the production flow while preserving mock mode for local/demo use.

**Tech Stack:** PHP 8.3, Laravel 12, PostgreSQL, PHPUnit, Next.js, React Query, DS-V2 components, Nexus `QuoteIngestion`, `PolicyEngine`, `Idempotency`, `Outbox`, `Document`, `Storage`, `Notifier`, `EventStream`.

---

## File Structure

### Files to Modify

| File | Responsibility |
|---|---|
| `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php` | Make upload/show/reparse/status flows fully consistent with live quote data and processing states |
| `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php` | Keep normalization mapping, override, conflict resolution, and readiness metadata aligned with live submissions |
| `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php` | Freeze live comparison runs, expose live readiness data, and return real run state |
| `apps/atomy-q/API/app/Http/Controllers/Api/V1/AwardController.php` | Replace `501`/stub award responses with the live award/debrief/signoff flow used by the RFQ workspace |
| `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php` | Ensure approval actions are driven by the final comparison snapshot and tenant-safe state |
| `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php` | Keep RFQ overview/detail payloads aligned with the live quote lifecycle and remove remaining workflow-specific stubs in touched paths only |
| `apps/atomy-q/API/app/Services/QuoteIntake/ComparisonSnapshotService.php` | Preserve deterministic freeze payloads and shape changes needed by live award/approval screens |
| `apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php` | Emit decision-trail entries for comparison freeze and award actions |
| `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php` | Extend coverage for live upload/reparse behavior and processing status transitions |
| `apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php` | Cover live mapping, override, and conflict-resolution readiness updates |
| `apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php` | Cover final freeze, live readiness, and decision-trail behavior |
| `apps/atomy-q/API/tests/Feature/RfqOverviewActivityTest.php` | Keep overview activity and live workflow metadata in sync |
| `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` | Document what is now live versus still stubbed |
| `apps/atomy-q/WEB/src/hooks/use-quote-submission.ts` | Fetch live quote submission records and normalize the API payload |
| `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts` | Keep live conflict metadata, blocking counts, and mutation invalidation correct |
| `apps/atomy-q/WEB/src/hooks/use-comparison-readiness.ts` | Compute freeze eligibility from live readiness plus conflict state |
| `apps/atomy-q/WEB/src/hooks/use-freeze-comparison.ts` | Freeze comparison runs against the live API contract |
| `apps/atomy-q/WEB/src/hooks/use-rfq-overview.ts` | Keep overview counts, comparison state, and activity fallback rules aligned with live API data |
| `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx` | Remove seed-only quote row assumptions and keep the intake list live when mocks are off |
| `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/page.tsx` | Show real quote submission state, parse metadata, and normalization entrypoints |
| `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx` | Drive normalization from real conflict rows and freeze controls |
| `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx` | Replace seed-only run listing with live comparison runs |
| `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx` | Replace award placeholders with live award/signoff/debrief behavior |
| `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/approvals/page.tsx` | Connect RFQ approvals to real comparison and award outcomes |
| `apps/atomy-q/WEB/BACKEND_API_GAPS.md` | Mark which gaps are now closed and which remain intentionally open |
| `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` | Record the live workflow milestone and remaining work |
| `docs/atomy-q/STAGING.md` | Update staging notes for the now-live quote lifecycle slice |

### Files to Create

| File | Responsibility |
|---|---|
| `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php` | Cover award creation, debrief, and signoff behavior on the live RFQ flow |
| `apps/atomy-q/WEB/src/hooks/use-award.ts` | Fetch and mutate award state for the RFQ workspace |
| `apps/atomy-q/WEB/src/hooks/use-award.test.tsx` | Verify award hook payload normalization and mutation invalidation |
| `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx` | Verify award page uses live hook data and disables seed-only assumptions |

---

## Task 1: Make the quote submission and normalization APIs fully live

**Files:**
- Modify `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
- Modify `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
- Modify `apps/atomy-q/API/app/Services/QuoteIntake/QuoteSubmissionReadinessService.php`
- Modify `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`
- Modify `apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php`

- [x] **Step 1: Write the failing tests**

Add or extend feature tests that prove:
- uploading a quote persists the live row and returns a non-seed payload,
- reparse resets processing state and requeues work,
- mapping/override/revert operations update readiness metadata,
- blocking issues are reflected in `meta.has_blocking_issues` and `meta.blocking_issue_count`.

- [x] **Step 2: Run the tests to confirm the current baseline**

Run:
```bash
./vendor/bin/phpunit tests/Feature/QuoteIngestionPipelineTest.php tests/Feature/NormalizationReviewWorkflowTest.php
```

Expected: pass on the current baseline, then fail once any new live-only assertions are added.

- [x] **Step 3: Implement the API changes**

Wire the controllers so the upload, show, reparse, mapping, override, and conflict-resolution flows all use the real `QuoteSubmission`, `NormalizationSourceLine`, and `NormalizationConflict` state already stored in the database. Keep tenant filtering explicit and preserve the existing mock-mode branch only where the UI intentionally needs it.

- [x] **Step 4: Re-run the tests**

Run:
```bash
./vendor/bin/phpunit tests/Feature/QuoteIngestionPipelineTest.php tests/Feature/NormalizationReviewWorkflowTest.php
```

Expected: PASS.

- [x] **Step 5: Commit**

```bash
git add apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php apps/atomy-q/API/app/Services/QuoteIntake/QuoteSubmissionReadinessService.php apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php
git commit -m "feat(atomy-q-api): live quote intake and normalization"
```

---

## Task 2: Freeze comparison runs and make award/approval live

**Files:**
- Modify `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`
- Modify `apps/atomy-q/API/app/Http/Controllers/Api/V1/AwardController.php`
- Modify `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php`
- Modify `apps/atomy-q/API/app/Services/QuoteIntake/ComparisonSnapshotService.php`
- Modify `apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php`
- Create `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php`
- Modify `apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php`

- [x] **Step 1: Write the failing tests**

Cover:
- final comparison freezes only when all quote submissions are ready and unblocked,
- the freeze response returns real snapshot payload data,
- a decision-trail entry is written on freeze,
- award signoff/debrief actions operate on a real award record instead of `501` responses.

- [x] **Step 2: Run the comparison and award tests**

Run:
```bash
./vendor/bin/phpunit tests/Feature/ComparisonSnapshotWorkflowTest.php tests/Feature/AwardWorkflowTest.php
```

Expected: comparison tests pass on baseline, award tests fail until the stub controller is replaced.

- [x] **Step 3: Implement the API changes**

Make the comparison finalization path the canonical freeze point, then wire award creation, debrief, protest, and signoff to live tenant-scoped records. Keep the comparison snapshot and approval trail deterministic so the WEB screens can depend on the same response shapes every time.

- [x] **Step 4: Re-run the tests**

Run:
```bash
./vendor/bin/phpunit tests/Feature/ComparisonSnapshotWorkflowTest.php tests/Feature/AwardWorkflowTest.php
```

Expected: PASS.

- [x] **Step 5: Commit**

```bash
git add apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/AwardController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php apps/atomy-q/API/app/Services/QuoteIntake/ComparisonSnapshotService.php apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php
git commit -m "feat(atomy-q-api): live comparison freeze and award flow"
```

---

## Task 3: Replace seed-only WEB behavior with live quote lifecycle hooks

**Files:**
- Modify `apps/atomy-q/WEB/src/hooks/use-quote-submission.ts`
- Modify `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
- Modify `apps/atomy-q/WEB/src/hooks/use-comparison-readiness.ts`
- Modify `apps/atomy-q/WEB/src/hooks/use-freeze-comparison.ts`
- Modify `apps/atomy-q/WEB/src/hooks/use-rfq-overview.ts`
- Create `apps/atomy-q/WEB/src/hooks/use-award.ts`
- Create `apps/atomy-q/WEB/src/hooks/use-award.test.tsx`
- Modify `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
- Modify `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/page.tsx`
- Modify `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
- Modify `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx`
- Modify `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Modify `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/approvals/page.tsx`
- Modify `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx`
- Create `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`

- [x] **Step 1: Write the failing tests**

Add tests that prove:
- live hooks normalize API payloads when mocks are disabled,
- comparison readiness is derived from live readiness plus blocking conflicts,
- the award page reads live award state instead of hard-coded vendor names,
- the quote intake and comparison pages no longer rely on seed-only data when `NEXT_PUBLIC_USE_MOCKS=false`.

- [x] **Step 2: Run the WEB unit tests**

Run:
```bash
npm run test:unit -- --run src/hooks/use-award.test.tsx src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx
```

Expected: tests fail until the live hooks and pages are wired.

- [x] **Step 3: Implement the WEB changes**

Update the hooks first, then wire the pages to those hooks. Keep mock-mode branches only where they are explicitly useful for local demo work; remove hidden seed assumptions from live-mode render paths.

- [x] **Step 4: Re-run the WEB unit tests**

Run:
```bash
npm run test:unit -- --run src/hooks/use-award.test.tsx src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx
```

Expected: PASS.

- [x] **Step 5: Commit**

```bash
git add apps/atomy-q/WEB/src/hooks/use-quote-submission.ts apps/atomy-q/WEB/src/hooks/use-normalization-review.ts apps/atomy-q/WEB/src/hooks/use-comparison-readiness.ts apps/atomy-q/WEB/src/hooks/use-freeze-comparison.ts apps/atomy-q/WEB/src/hooks/use-rfq-overview.ts apps/atomy-q/WEB/src/hooks/use-award.ts apps/atomy-q/WEB/src/hooks/use-award.test.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/page.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/approvals/page.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx
git commit -m "feat(atomy-q-web): live quote lifecycle screens"
```

---

## Task 4: Update docs and close the remaining visibility gaps

**Files:**
- Modify `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify `apps/atomy-q/WEB/BACKEND_API_GAPS.md`
- Modify `docs/atomy-q/STAGING.md`
- Modify `docs/atomy-q/NEXT_BIG_TICKET_PRIORITIZATION_REPORT.md` if the implementation meaningfully changes the ranking

- [x] **Step 1: Write the documentation diffs**

Capture:
- what is now live in the quote lifecycle,
- which screens still fall back to mocks,
- which API gaps remain intentionally open for ticket #2 or later work,
- any staging prerequisites that changed.

- [x] **Step 2: Run the full relevant verification**

Run:
```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/QuoteIngestionPipelineTest.php tests/Feature/NormalizationReviewWorkflowTest.php tests/Feature/ComparisonSnapshotWorkflowTest.php tests/Feature/AwardWorkflowTest.php
cd apps/atomy-q/WEB && npm run test:unit
```

Expected: all tests pass.

- [x] **Step 3: Commit**

```bash
git add apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md apps/atomy-q/WEB/BACKEND_API_GAPS.md docs/atomy-q/STAGING.md docs/atomy-q/NEXT_BIG_TICKET_PRIORITIZATION_REPORT.md
git commit -m "docs(atomy-q): update quote lifecycle implementation status"
```

---

## Execution Notes

1. Keep tenant filtering explicit in every query touched by the quote lifecycle.
2. Do not replace live failures with synthetic fallback IDs or success payloads.
3. Preserve mock mode for demos, but keep the live code path authoritative.
4. Prefer small commits after each task so regressions are easy to isolate.
