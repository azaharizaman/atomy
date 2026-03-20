# Quote Ingestion And Normalization Pilot Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a pilot-ready quote ingestion and normalization workflow for Atomy-Q so real supplier quote files can be uploaded, reviewed, normalized, frozen into a comparison snapshot, and approved without silent data corruption.

**Architecture:** Extend the existing Atomy-Q API and WEB RFQ flow rather than inventing a parallel subsystem. Keep the candidate-versus-approved boundary explicit: uploaded quote extraction remains reviewable candidate data until a tenant-scoped normalization review flow resolves blocking issues and a frozen comparison snapshot is created.

**Tech Stack:** Laravel 12 / PHP 8.3, PostgreSQL, queued jobs where appropriate, Next.js 16 App Router, TanStack Query, React Hook Form + Zod, PHPUnit, Vitest, Playwright.

---

## File Structure Map

### Existing files to extend

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
  Responsibility: upload, show, status, replace, reparse, assign APIs for quote submissions.
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
  Responsibility: source line listing, overrides, conflict resolution, lock/unlock APIs.
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`
  Responsibility: preview/final comparison creation, readiness, lock/unlock.
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/DecisionTrailController.php`
  Responsibility: immutable tenant-scoped audit trail exposure.
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php`
  Responsibility: RFQ overview and readiness signals shown in the UI.
- `apps/atomy-q/API/app/Models/QuoteSubmission.php`
- `apps/atomy-q/API/app/Models/NormalizationSourceLine.php`
- `apps/atomy-q/API/app/Models/NormalizationConflict.php`
- `apps/atomy-q/API/app/Models/ComparisonRun.php`
- `apps/atomy-q/API/app/Models/DecisionTrailEntry.php`
- `apps/atomy-q/API/routes/api.php`
- `apps/atomy-q/API/app/Console/Commands/SeedRfqFlowCommand.php`
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/page.tsx`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/decision-trail/page.tsx`
- `apps/atomy-q/WEB/src/hooks/use-rfq.ts`
- `apps/atomy-q/WEB/src/hooks/use-rfq-overview.ts`
- `apps/atomy-q/WEB/tests/rfq-workflow.spec.ts`
- `apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts`

### New API files to create

- `apps/atomy-q/API/app/Http/Requests/QuoteSubmissionUploadRequest.php`
  Responsibility: strict validation for upload payloads.
- `apps/atomy-q/API/app/Http/Requests/QuoteSubmissionStatusRequest.php`
  Responsibility: restrict valid submission state transitions.
- `apps/atomy-q/API/app/Http/Requests/NormalizationResolveConflictRequest.php`
  Responsibility: validate resolution actions and required fields.
- `apps/atomy-q/API/app/Http/Requests/NormalizationOverrideRequest.php`
  Responsibility: validate field override payloads.
- `apps/atomy-q/API/app/Http/Requests/ComparisonFinalizeRequest.php`
  Responsibility: validate frozen snapshot creation payload.
- `apps/atomy-q/API/app/Services/QuoteIntake/QuoteSubmissionReadinessService.php`
  Responsibility: compute blocking issues before a submission becomes ready.
- `apps/atomy-q/API/app/Services/QuoteIntake/NormalizationIssueCode.php`
  Responsibility: central issue taxonomy for source lines and conflicts.
- `apps/atomy-q/API/app/Services/QuoteIntake/ComparisonSnapshotService.php`
  Responsibility: freeze approved normalized data into a comparison snapshot.
- `apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php`
  Responsibility: write tenant-scoped decision trail entries for upload/review/freeze events.

### New API tests to create

- `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`
- `apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php`
- `apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php`

### New WEB files to create

- `apps/atomy-q/WEB/src/hooks/use-quote-submission.ts`
- `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-readiness.ts`
- `apps/atomy-q/WEB/src/hooks/use-freeze-comparison.ts`
- `apps/atomy-q/WEB/src/hooks/use-quote-submission.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-normalization-review.test.ts`

### New WEB tests to create

- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx`

## Task 1: Lock The API State Model And Validation Contracts

**Files:**
- Create: `apps/atomy-q/API/app/Http/Requests/QuoteSubmissionUploadRequest.php`
- Create: `apps/atomy-q/API/app/Http/Requests/QuoteSubmissionStatusRequest.php`
- Create: `apps/atomy-q/API/app/Http/Requests/NormalizationResolveConflictRequest.php`
- Create: `apps/atomy-q/API/app/Http/Requests/NormalizationOverrideRequest.php`
- Create: `apps/atomy-q/API/app/Http/Requests/ComparisonFinalizeRequest.php`
- Create: `apps/atomy-q/API/app/Services/QuoteIntake/NormalizationIssueCode.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`
- Test: `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`

- [ ] **Step 1: Write the failing feature tests for validation and illegal transitions**

```php
public function test_quote_submission_upload_requires_rfq_vendor_and_file(): void
{
    $response = $this->postJson('/api/v1/quote-submissions/upload', [], $this->authHeaders($user));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['rfq_id', 'vendor_id', 'file']);
}

public function test_quote_submission_status_rejects_unknown_transition(): void
{
    $response = $this->patchJson(
        '/api/v1/quote-submissions/' . $quote->id . '/status',
        ['status' => 'ready'],
        $this->authHeaders($user),
    );

    $response->assertStatus(422);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd apps/atomy-q/API && php artisan test --filter=QuoteSubmissionWorkflowTest`
Expected: FAIL with missing request validation and/or incorrect controller behavior.

- [ ] **Step 3: Implement the request classes and issue code enum-like utility**

```php
final readonly class QuoteSubmissionStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['uploaded', 'extracting', 'extracted', 'normalizing', 'needs_review', 'ready', 'failed'])],
        ];
    }
}
```

```php
final class NormalizationIssueCode
{
    public const MISSING_PRICE = 'missing_price';
    public const MISSING_CURRENCY = 'missing_currency';
    public const AMBIGUOUS_MAPPING = 'ambiguous_mapping';
    public const INVALID_UOM = 'invalid_uom';
}
```

- [ ] **Step 4: Update controllers to use validated payloads only**

Require typed request objects, reject unsupported transitions, and stop accepting raw `Request` payloads for mutation endpoints.

- [ ] **Step 5: Re-run the API tests**

Run: `cd apps/atomy-q/API && php artisan test --filter=QuoteSubmissionWorkflowTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/app/Http/Requests apps/atomy-q/API/app/Services/QuoteIntake/NormalizationIssueCode.php apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php
git commit -m "feat(api): validate quote intake and normalization transitions"
```

## Task 2: Make Quote Submission Persistence And Tenant Scoping Pilot-Safe

**Files:**
- Modify: `apps/atomy-q/API/app/Models/QuoteSubmission.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php`
- Modify: `apps/atomy-q/API/routes/api.php`
- Test: `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`

- [ ] **Step 1: Write failing tests for tenant-scoped upload/show/list behavior**

```php
public function test_quote_submission_upload_persists_uploaded_state_and_tenant_id(): void
{
    $response = $this->postJson('/api/v1/quote-submissions/upload', $payload, $this->authHeaders($user));

    $response->assertCreated()
        ->assertJsonPath('data.status', 'uploaded');

    $this->assertDatabaseHas('quote_submissions', [
        'tenant_id' => $user->tenant_id,
        'rfq_id' => $rfq->id,
        'status' => 'uploaded',
    ]);
}
```

```php
public function test_quote_submission_show_returns_404_for_other_tenant(): void
{
    $response = $this->getJson('/api/v1/quote-submissions/' . $quote->id, $this->authHeaders($otherTenantUser));

    $response->assertNotFound();
}
```

- [ ] **Step 2: Run the feature tests**

Run: `cd apps/atomy-q/API && php artisan test --filter=QuoteSubmissionWorkflowTest`
Expected: FAIL because list/show/upload behavior is incomplete or not tenant-safe.

- [ ] **Step 3: Implement persistence and response shape tightening**

Ensure upload writes:

- `tenant_id`
- `rfq_id`
- `vendor_id`
- `uploaded_by`
- `status = uploaded`
- original filename and stored path

Expose machine-readable response fields for review flow:

```php
return response()->json([
    'data' => [
        'id' => $quote->id,
        'status' => $quote->status,
        'rfq_id' => $quote->rfq_id,
        'vendor_id' => $quote->vendor_id,
        'blocking_issue_count' => 0,
    ],
], 201);
```

- [ ] **Step 4: Verify RFQ overview includes submission readiness counts**

Extend `RfqController::overview()` to return counts for uploaded, needs-review, ready, and frozen-related signals without divide-by-zero errors.

- [ ] **Step 5: Re-run the API tests**

Run: `cd apps/atomy-q/API && php artisan test --filter=QuoteSubmissionWorkflowTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/app/Models/QuoteSubmission.php apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php apps/atomy-q/API/routes/api.php apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php
git commit -m "feat(api): persist tenant-scoped quote submission workflow"
```

## Task 3: Implement Normalization Review And Blocking Issue Resolution

**Files:**
- Create: `apps/atomy-q/API/app/Services/QuoteIntake/QuoteSubmissionReadinessService.php`
- Modify: `apps/atomy-q/API/app/Models/NormalizationSourceLine.php`
- Modify: `apps/atomy-q/API/app/Models/NormalizationConflict.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
- Test: `apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php`

- [x] **Step 1: Write failing tests for source-line issue states and conflict resolution**

```php
public function test_normalization_conflict_resolution_marks_submission_ready_only_when_no_blockers_remain(): void
{
    $this->putJson("/api/v1/normalization/conflicts/{$conflict->id}/resolve", [
        'resolution' => 'accept_extracted_value',
    ], $this->authHeaders($user))->assertOk();

    $quote->refresh();

    $this->assertSame('ready', $quote->status);
}
```

```php
public function test_submission_stays_in_needs_review_when_required_line_is_unmapped(): void
{
    $response = $this->getJson("/api/v1/normalization/{$rfq->id}/conflicts", $this->authHeaders($user));

    $response->assertOk()
        ->assertJsonPath('meta.has_blocking_issues', true);
}
```

- [x] **Step 2: Run the tests**

Run: `cd apps/atomy-q/API && php artisan test --filter=NormalizationReviewWorkflowTest`
Expected: FAIL

- [x] **Step 3: Implement a single readiness service**

```php
final readonly class QuoteSubmissionReadinessService
{
    public function evaluate(QuoteSubmission $submission): array
    {
        return [
            'has_blocking_issues' => $hasBlockingIssues,
            'blocking_issue_count' => $blockingIssueCount,
            'next_status' => $hasBlockingIssues ? 'needs_review' : 'ready',
        ];
    }
}
```

Centralize all blocking rules here:

- missing required RFQ line mappings
- missing prices
- missing currencies
- invalid UOM overrides
- unresolved conflicts

- [x] **Step 4: Update `NormalizationController` to use the readiness service after every mutation**

After override, bulk mapping, revert, or conflict resolution:

1. recalculate submission readiness
2. persist the correct status
3. return machine-readable metadata

- [x] **Step 5: Re-run the tests**

Run: `cd apps/atomy-q/API && php artisan test --filter=NormalizationReviewWorkflowTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/app/Services/QuoteIntake/QuoteSubmissionReadinessService.php apps/atomy-q/API/app/Models/NormalizationSourceLine.php apps/atomy-q/API/app/Models/NormalizationConflict.php apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php apps/atomy-q/API/tests/Unit/Models/ModelRelationsTest.php apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md docs/superpowers/plans/2026-03-19-quote-ingestion-normalization-pilot-implementation-plan.md
git commit -m "feat(api): add normalization review readiness gating"
```

## Task 4: Freeze Comparison Snapshots Instead Of Comparing Live Mutable Data

**Files:**
- Create: `apps/atomy-q/API/app/Services/QuoteIntake/ComparisonSnapshotService.php`
- Modify: `apps/atomy-q/API/app/Models/ComparisonRun.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php`
- Test: `apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php`

- [x] **Step 1: Write failing tests for frozen snapshot behavior**

```php
public function test_final_comparison_run_captures_snapshot_inputs(): void
{
    $response = $this->postJson('/api/v1/comparison-runs/final', [
        'rfq_id' => $rfq->id,
    ], $this->authHeaders($user));

    $response->assertCreated()
        ->assertJsonPath('data.status', 'final');

    $this->assertNotNull($response->json('data.snapshot.normalized_lines'));
}
```

```php
public function test_approval_cannot_proceed_when_submission_has_blocking_issues(): void
{
    $response = $this->postJson("/api/v1/approvals/{$approvalId}/approve", [], $this->authHeaders($user));

    $response->assertStatus(422);
}
```

- [x] **Step 2: Run the tests**

Run: `cd apps/atomy-q/API && php artisan test --filter=ComparisonSnapshotWorkflowTest`
Expected: FAIL

- [x] **Step 3: Implement snapshot freezing service**

```php
final readonly class ComparisonSnapshotService
{
    public function freezeForRfq(string $tenantId, string $rfqId): array
    {
        return [
            'rfq_version' => $rfqVersion,
            'normalized_lines' => $normalizedLines,
            'resolutions' => $resolutions,
            'currency_meta' => $currencyMeta,
        ];
    }
}
```

Persist immutable snapshot payload on the comparison run record or a dedicated JSON column already available in schema. Do not reuse mutable source-line tables as the approval source of truth.

- [ ] **Step 4: Gate final comparison and approval on readiness**

`ComparisonRunController::final_()` and any approval action must reject when:

- any included quote submission is not `ready`
- normalization remains unlocked with blocking issues
- required vendor minimums are not met

- [x] **Step 5: Re-run the tests**

Run: `cd apps/atomy-q/API && php artisan test --filter=ComparisonSnapshotWorkflowTest`
Expected: PASS

- [x] **Step 6: Commit** (see [Pilot batch commit](#pilot-batch-commit-2026-03-20) — single squashed commit for tasks 4–8)

```bash
git add apps/atomy-q/API/app/Services/QuoteIntake/ComparisonSnapshotService.php apps/atomy-q/API/app/Models/ComparisonRun.php apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php
git commit -m "feat(api): freeze comparison snapshots before approval"
```

## Task 5: Record Decision Trail Events For Upload, Review, Freeze, And Manual Assist

> **Implemented:** `comparison_snapshot_frozen` on final freeze + decision trail index/show. Other event types from the plan remain **deferred** (schema requires `comparison_run_id`; no nullable migration added).

**Files:**
- Create: `apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DecisionTrailController.php`
- Test: `apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php`

- [ ] **Step 1: Write failing tests for decision trail entries**

```php
public function test_freezing_a_snapshot_writes_decision_trail_entry(): void
{
    $this->postJson('/api/v1/comparison-runs/final', ['rfq_id' => $rfq->id], $this->authHeaders($user))->assertCreated();

    $this->assertDatabaseHas('decision_trail_entries', [
        'tenant_id' => $user->tenant_id,
        'rfq_id' => $rfq->id,
        'event_type' => 'comparison_snapshot_frozen',
    ]);
}
```

- [x] **Step 2: Run the tests**

Run: `cd apps/atomy-q/API && php artisan test --filter=ComparisonSnapshotWorkflowTest`
Expected: FAIL

- [x] **Step 3: Implement a small recorder service and call it from mutation points**

Write entries for:

- quote uploaded
- quote reparsed
- conflict resolved
- override applied/reverted
- manual-assist selected
- comparison snapshot frozen

Keep payloads tenant-safe and machine-readable.

- [x] **Step 4: Verify decision trail read APIs expose the new events**

Extend the read response shape only as needed. Do not create a second audit endpoint.

- [x] **Step 5: Re-run the tests**

Run: `cd apps/atomy-q/API && php artisan test --filter=ComparisonSnapshotWorkflowTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/DecisionTrailController.php apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php
git commit -m "feat(api): audit quote intake review and freeze events"
```

## Task 6: Build The WEB Exception-First Review Flow

**Files:**
- Create: `apps/atomy-q/WEB/src/hooks/use-quote-submission.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-comparison-readiness.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-freeze-comparison.ts`
- Create: `apps/atomy-q/WEB/src/hooks/use-quote-submission.test.tsx`
- Create: `apps/atomy-q/WEB/src/hooks/use-normalization-review.test.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
- Test: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx`

- [x] **Step 1: Write failing hook and page tests**

```tsx
it('shows blocking issues before allowing freeze', async () => {
  render(<NormalizeQuotePage />);

  expect(await screen.findByText(/blocking issues/i)).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /freeze comparison/i })).toBeDisabled();
});
```

- [x] **Step 2: Run the WEB tests**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- normalize/page.test.tsx use-normalization-review.test.ts`
Expected: FAIL

- [x] **Step 3: Implement focused hooks around the API contract**

Expose:

- submission summary
- blocking issue count
- conflict list
- override mutation
- resolve-conflict mutation
- freeze readiness

Prefer thin hooks over in-page fetch logic.

- [x] **Step 4: Update the quote-intake UI to be exception-first**

Required UX changes:

- unresolved issues at top
- conflict rows grouped by RFQ line or vendor line
- explicit issue badges like `Missing price`, `Ambiguous mapping`, `Missing currency`
- freeze CTA disabled until blockers are resolved
- manual-assist action visible only when automation is blocked

- [x] **Step 5: Re-run the WEB tests**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- normalize/page.test.tsx use-normalization-review.test.ts`
Expected: PASS

- [x] **Step 6: Commit** (see [Pilot batch commit](#pilot-batch-commit-2026-03-20))

```bash
git add apps/atomy-q/WEB/src/hooks/use-quote-submission.ts apps/atomy-q/WEB/src/hooks/use-normalization-review.ts apps/atomy-q/WEB/src/hooks/use-comparison-readiness.ts apps/atomy-q/WEB/src/hooks/use-freeze-comparison.ts apps/atomy-q/WEB/src/hooks/use-quote-submission.test.tsx apps/atomy-q/WEB/src/hooks/use-normalization-review.test.ts "apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx" "apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/page.tsx" "apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx" "apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx"
git commit -m "feat(web): add exception-first normalization review flow"
```

## Task 7: Show Frozen Snapshot And Readiness In Comparison Views

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/decision-trail/page.tsx`
- Modify: `apps/atomy-q/WEB/src/hooks/use-rfq-overview.ts`
- Test: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx`
- Test: `apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts`

- [x] **Step 1: Write failing tests for snapshot status and readiness visibility**

```tsx
it('shows snapshot frozen status and decision trail link', async () => {
  render(<ComparisonRunsPage />);

  expect(await screen.findByText(/snapshot frozen/i)).toBeInTheDocument();
  expect(screen.getByRole('link', { name: /decision trail/i })).toBeInTheDocument();
});
```

- [x] **Step 2: Run the WEB tests**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- comparison-runs/page.test.tsx`
Expected: FAIL

- [x] **Step 3: Implement comparison run summary and frozen-state UI**

Show:

- whether all submissions are ready
- whether normalization is locked
- snapshot frozen timestamp
- included vendor count
- direct path to decision trail evidence

- [x] **Step 4: Add or update an end-to-end workflow test**

Drive a mocked flow:

1. open quote intake
2. resolve blockers
3. freeze comparison
4. confirm frozen state appears in comparison runs

- [ ] **Step 5: Re-run the tests**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- comparison-runs/page.test.tsx && npm run test:e2e -- tests/rfq-lifecycle-e2e.spec.ts`
Expected: PASS

- [x] **Step 6: Commit** (see [Pilot batch commit](#pilot-batch-commit-2026-03-20))

```bash
git add "apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx" "apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx" "apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/decision-trail/page.tsx" apps/atomy-q/WEB/src/hooks/use-rfq-overview.ts "apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.test.tsx" apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts
git commit -m "feat(web): surface frozen comparison readiness and audit state"
```

## Task 8: Update Seed Flow, Docs, And Regression Coverage

**Files:**
- Modify: `apps/atomy-q/API/app/Console/Commands/SeedRfqFlowCommand.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/README.md`
- Modify: `apps/atomy-q/API/README.md`
- Test: `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`
- Test: `apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php`
- Test: `apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php`
- Test: `apps/atomy-q/WEB/tests/rfq-workflow.spec.ts`

- [x] **Step 1: Write a failing regression expectation for seeded ready-to-freeze flow** (covered by existing feature tests + seed sync)

Extend the existing seed and/or feature coverage so the happy path requires:

- uploaded quote
- resolved blockers
- frozen comparison run

- [x] **Step 2: Update the seed flow command**

Make `atomy:seed-rfq-flow` drive the new readiness path instead of skipping directly from upload to accepted/finalized assumptions.

- [x] **Step 3: Update documentation**

Document:

- new state model
- blocking issues and review actions
- freeze-before-approval rule
- manual-assist fallback

Also update `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` because AGENTS.md requires summary updates after functional changes.

- [x] **Step 4: Run the regression suite** (API filter + full WEB `test:unit`; E2E optional)

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter='QuoteSubmissionWorkflowTest|NormalizationReviewWorkflowTest|ComparisonSnapshotWorkflowTest'
cd apps/atomy-q/WEB && npm run test:unit -- use-normalization-review.test.ts comparison-runs/page.test.tsx && npm run test:e2e -- tests/rfq-workflow.spec.ts tests/rfq-lifecycle-e2e.spec.ts
```

Expected: PASS

- [x] **Step 5: Commit** (see [Pilot batch commit](#pilot-batch-commit-2026-03-20))

```bash
git add apps/atomy-q/API/app/Console/Commands/SeedRfqFlowCommand.php apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md apps/atomy-q/API/README.md apps/atomy-q/WEB/README.md apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php apps/atomy-q/API/tests/Feature/ComparisonSnapshotWorkflowTest.php apps/atomy-q/WEB/tests/rfq-workflow.spec.ts
git commit -m "docs: align seed flow and docs with quote intake pilot workflow"
```

## Execution Notes

### Pilot batch commit (2026-03-20)

Tasks **4–8** commit steps were recorded as **one squashed commit** at the tip of `feat/atomy-q-delivery` because the same PHP/TS files span snapshot freeze, decision trail wiring, normalization controller changes, WEB hooks/pages, seed, and docs. The per-task `git add` blocks above remain the reference if you later split history for cherry-picks.

- Keep all tenant checks tenant-scoped and return `404` for inaccessible tenant-owned resources.
- Do not return synthetic success data when readiness or snapshot creation fails; return validation or domain errors explicitly.
- Guard all division and percentage calculations against zero values.
- Prefer extending existing controllers and pages over broad refactors.
- If an existing controller becomes too large, split logic into the new `app/Services/QuoteIntake/*` services rather than adding more controller conditionals.
- Update `IMPLEMENTATION_SUMMARY.md` in the affected app after each functional milestone, not only at the end.

## Suggested Execution Order

1. Task 1
2. Task 2
3. Task 3
4. Task 4
5. Task 5
6. Task 6
7. Task 7
8. Task 8
