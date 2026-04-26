# Atomy-Q Sourcing Recommendation Target-State Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver one canonical provider-backed sourcing recommendation workflow in the RFQ vendors workspace, remove all legacy recommendation surfaces, persist recommendation and shortlist audit evidence separately, and prove the flow with real-provider E2E.

**Architecture:** Keep recommendation generation on the existing `VendorRecommendationCoordinator` and RFQ vendors workspace. Remove `/api/v1/recommendations/*` entirely instead of preserving structured-unavailable stubs, add durable recommendation artifact and buyer-shortlist decision-trail events, then align WEB, OpenAPI, generated client, and Playwright around the canonical RFQ-scoped workflow.

**Tech Stack:** PHP 8.3, Laravel, Nexus `ProcurementOperations` and `ProcurementML`, Next.js/React, TypeScript, TanStack Query, OpenAPI, generated client, PHPUnit, Vitest, Playwright.

---

## File Structure

**Core backend**

- Modify: `orchestrators/ProcurementOperations/src/Coordinators/VendorRecommendationCoordinator.php`
  - Keep provider-backed recommendation authoritative for this slice, reject malformed provider output, and preserve zero-candidate valid responses.
- Modify: `orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php`
  - Cover rejected provider output, zero-candidate valid output, and provenance requirements.
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php`
  - Remove compatibility aliases, persist canonical recommendation artifact, and write decision-trail evidence.
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RequisitionVendorSelectionController.php`
  - Write authoritative buyer-shortlist decision-trail evidence on shortlist replace.
- Delete: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RecommendationController.php`
  - Remove the dead legacy surface entirely.
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DecisionTrailController.php`
  - Surface recommendation artifact and shortlist mutation events cleanly.
- Modify: `apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php`
  - Add allowed recommendation artifact and shortlist event writers.
- Create: `apps/atomy-q/API/app/Models/RfqRecommendationArtifact.php`
  - Canonical persisted recommendation artifact per RFQ.
- Create: `apps/atomy-q/API/database/migrations/2026_04_27_000001_create_rfq_recommendation_artifacts_table.php`
  - Storage for the latest canonical recommendation artifact by RFQ.
- Modify: `apps/atomy-q/API/app/Models/DecisionTrailEntry.php`
  - If needed, add relation helpers or casts used by the new event metadata.

**Routing and contract**

- Modify: `apps/atomy-q/API/routes/api.php`
  - Remove `/recommendations/*`, keep only canonical RFQ-scoped recommendation and selected-vendors routes.
- Modify: `apps/atomy-q/openapi/openapi.json`
  - Delete legacy recommendation operations and document the canonical contract only.
- Modify: `apps/atomy-q/WEB/src/generated/api`
  - Regenerate after OpenAPI cleanup.

**Backend tests**

- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationApiTest.php`
  - Assert canonical-only response shape and persisted recommendation evidence.
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationAiGateTest.php`
  - Stop preserving legacy `/recommendations/*` expectations; assert only canonical RFQ recommendation unavailability.
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php`
  - Assert shortlist decision-trail events and continuity when AI is unavailable.
- Create: `apps/atomy-q/API/tests/Feature/Api/V1/RfqRecommendationDecisionTrailTest.php`
  - Dedicated artifact-vs-shortlist evidence separation.

**WEB**

- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.ts`
  - Normalize only the canonical response shape.
- Modify: `apps/atomy-q/WEB/src/hooks/use-requisition-vendor-selection.ts`
  - Keep shortlist read contract aligned with API.
- Modify: `apps/atomy-q/WEB/src/hooks/use-update-requisition-vendor-selection.ts`
  - Keep shortlist mutation invalidation tight to the canonical workflow.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
  - Maintain explicit separation between recommendation artifact and authoritative shortlist.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx`
  - Remove compatibility assumptions and assert canonical advisory behavior.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx`
  - Assert manual shortlist continuity when AI recommendation is unavailable.
- Modify if needed: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx`
  - Keep page-level unavailable and manual continuity behavior aligned.

**E2E**

- Create: `apps/atomy-q/WEB/tests/provider-sourcing-recommendation-e2e.spec.ts`
  - Fake-provider/browser proof for canonical recommendation workflow.
- Create: `apps/atomy-q/WEB/tests/provider-sourcing-recommendation-live.spec.ts`
  - Real-provider/browser proof for canonical recommendation workflow.
- Modify: `apps/atomy-q/WEB/package.json`
  - Add fake/live sourcing recommendation E2E scripts.
- Modify if needed: `apps/atomy-q/WEB/playwright.config.ts`
  - Reuse API env loading already used for quote live tests.
- Modify if needed: `apps/atomy-q/WEB/tests/playwright-auth-bootstrap.ts`
  - Reuse auth bootstrap with no recommendation-specific branching.

**Docs**

- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/docs/03-domains/vendors/overview.md`
- Modify: `apps/atomy-q/docs/03-domains/vendors/workflows.md`
- Modify if recommendation language appears there: `apps/atomy-q/docs/03-domains/DOMAIN_MAP.md`

## Task 1: Remove Legacy Recommendation Surface From API And Contract

**Files:**
- Delete: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RecommendationController.php`
- Modify: `apps/atomy-q/API/routes/api.php`
- Modify: `apps/atomy-q/openapi/openapi.json`
- Test: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationAiGateTest.php`

- [ ] **Step 1: Write the failing API test that asserts the legacy route set is gone**

```php
public function testLegacyRecommendationRoutesAreNotRegistered(): void
{
    $tenantId = (string) Str::ulid();
    $user = $this->createUser($tenantId);

    $this->getJson(
        '/api/v1/recommendations/run-123',
        $this->authHeaders($tenantId, (string) $user->id),
    )->assertStatus(404);
}
```

- [ ] **Step 2: Run the targeted test to verify it fails against the current stubbed route**

Run:
```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/Api/V1/VendorRecommendationAiGateTest.php --filter LegacyRecommendationRoutesAreNotRegistered
```

Expected: FAIL because the route currently resolves and returns structured unavailable output instead of `404`.

- [ ] **Step 3: Remove the controller and route group**

```php
// apps/atomy-q/API/routes/api.php
Route::prefix('rfqs/{rfqId}')->group(function (): void {
    Route::post('vendor-recommendations', [VendorRecommendationController::class, 'store']);
    Route::get('selected-vendors', [RequisitionVendorSelectionController::class, 'index']);
    Route::put('selected-vendors', [RequisitionVendorSelectionController::class, 'update']);
});

// Delete the old Route::prefix('recommendations')->group(...)
```

- [ ] **Step 4: Remove legacy recommendation operations from OpenAPI**

```json
{
  "/rfqs/{rfqId}/vendor-recommendations": {
    "post": {
      "summary": "Generate provider-backed vendor recommendations for an RFQ"
    }
  }
}
```

Remove:
```json
"/recommendations/{runId}"
"/recommendations/{runId}/mcda"
"/recommendations/{runId}/override"
"/recommendations/{runId}/rerun"
```

- [ ] **Step 5: Run the targeted API test to verify the route is gone**

Run:
```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/Api/V1/VendorRecommendationAiGateTest.php --filter LegacyRecommendationRoutesAreNotRegistered
```

Expected: PASS with `404` on `/api/v1/recommendations/run-123`.

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/routes/api.php apps/atomy-q/openapi/openapi.json apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationAiGateTest.php
git rm apps/atomy-q/API/app/Http/Controllers/Api/V1/RecommendationController.php
git commit -m "Remove legacy recommendation API surface"
```

## Task 2: Persist Canonical Recommendation Artifact And Separate Decision-Trail Evidence

**Files:**
- Create: `apps/atomy-q/API/app/Models/RfqRecommendationArtifact.php`
- Create: `apps/atomy-q/API/database/migrations/2026_04_27_000001_create_rfq_recommendation_artifacts_table.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RequisitionVendorSelectionController.php`
- Modify: `apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DecisionTrailController.php`
- Create: `apps/atomy-q/API/tests/Feature/Api/V1/RfqRecommendationDecisionTrailTest.php`
- Test: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationApiTest.php`
- Test: `apps/atomy-q/API/tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php`

- [ ] **Step 1: Write the failing feature test for separate recommendation and shortlist evidence**

```php
public function testRecommendationArtifactAndBuyerShortlistAreRecordedAsSeparateEvents(): void
{
    $response = $this->postJson('/api/v1/rfqs/' . $rfq->id . '/vendor-recommendations', $payload, $headers);
    $response->assertOk();

    $this->putJson('/api/v1/rfqs/' . $rfq->id . '/selected-vendors', [
        'vendor_ids' => [$approvedVendorId],
    ], $headers)->assertOk();

    $this->assertDatabaseHas('rfq_recommendation_artifacts', [
        'tenant_id' => $tenantId,
        'rfq_id' => $rfq->id,
        'feature_key' => 'vendor_ai_ranking',
    ]);

    $this->assertDatabaseHas('decision_trail_entries', [
        'tenant_id' => $tenantId,
        'rfq_id' => $rfq->id,
        'event_type' => 'vendor_recommendation_generated',
    ]);

    $this->assertDatabaseHas('decision_trail_entries', [
        'tenant_id' => $tenantId,
        'rfq_id' => $rfq->id,
        'event_type' => 'selected_vendors_replaced',
    ]);
}
```

- [ ] **Step 2: Run the new and existing API tests to verify the gap**

Run:
```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit \
  tests/Feature/Api/V1/VendorRecommendationApiTest.php \
  tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php \
  tests/Feature/Api/V1/RfqRecommendationDecisionTrailTest.php
```

Expected: FAIL because recommendation artifact persistence and decision-trail separation do not exist yet.

- [ ] **Step 3: Add the canonical recommendation artifact table and model**

```php
Schema::create('rfq_recommendation_artifacts', function (Blueprint $table): void {
    $table->ulid('id')->primary();
    $table->string('tenant_id');
    $table->string('rfq_id');
    $table->string('feature_key');
    $table->string('status');
    $table->json('artifact_payload');
    $table->json('provenance_payload')->nullable();
    $table->timestamp('generated_at');
    $table->timestamps();

    $table->unique(['tenant_id', 'rfq_id', 'feature_key'], 'rfq_recommendation_artifacts_unique');
});
```

- [ ] **Step 4: Persist recommendation artifact and record canonical recommendation event**

```php
// apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php
$artifact = [
    'status' => $result->status->value,
    'eligible_candidates' => ...,
    'excluded_candidates' => ...,
    'provider_explanation' => $result->providerExplanation,
    'deterministic_reason_set' => $result->deterministicReasonSet,
    'provenance' => $result->provenance?->toArray(),
];

DB::transaction(function () use ($tenantId, $rfq, $artifact): void {
    RfqRecommendationArtifact::query()->updateOrCreate(
        [
            'tenant_id' => $tenantId,
            'rfq_id' => (string) $rfq->id,
            'feature_key' => 'vendor_ai_ranking',
        ],
        [
            'status' => (string) $artifact['status'],
            'artifact_payload' => $artifact,
            'provenance_payload' => $artifact['provenance'],
            'generated_at' => now(),
        ],
    );

    $this->decisionTrail->recordAiArtifactGenerated(
        tenantId: $tenantId,
        rfqId: (string) $rfq->id,
        comparisonRunId: (string) $rfq->id,
        eventType: 'vendor_recommendation_generated',
        summary: [
            'artifact_kind' => 'vendor_recommendation',
            'artifact_origin' => $artifact['status'] === 'available' ? 'provider_drafted' : 'manual_continuity',
            'feature_key' => 'vendor_ai_ranking',
            'available' => $artifact['status'] === 'available',
            'artifact' => $artifact,
            'provenance' => $artifact['provenance'],
        ],
    );
});
```

- [ ] **Step 5: Record separate shortlist mutation evidence**

```php
// apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php
public function recordSelectedVendorsReplaced(
    string $tenantId,
    string $rfqId,
    array $summary,
): void {
    $this->record(
        tenantId: $tenantId,
        rfqId: $rfqId,
        comparisonRunId: $rfqId,
        eventType: 'selected_vendors_replaced',
        summary: $summary,
    );
}
```

```php
// apps/atomy-q/API/app/Http/Controllers/Api/V1/RequisitionVendorSelectionController.php
$this->decisionTrail->recordSelectedVendorsReplaced(
    tenantId: $tenantId,
    rfqId: (string) $rfq->id,
    summary: [
        'feature_key' => 'vendor_manual_selection',
        'selected_vendor_ids' => $vendorIds,
        'selected_by_user_id' => $userId,
        'selected_count' => count($vendorIds),
    ],
);
```

- [ ] **Step 6: Extend decision-trail metadata rendering for the new events**

```php
// apps/atomy-q/API/app/Http/Controllers/Api/V1/DecisionTrailController.php
'vendor_recommendation_generated' => fn (DecisionTrailEntry $entry): array => $this->artifactMetadata(
    artifactKind: 'vendor_recommendation',
    artifactOrigin: 'provider_drafted',
    summary: $entry->summary_payload ?? [],
    artifact: $this->artifactFromSummary($entry),
),
'selected_vendors_replaced' => fn (DecisionTrailEntry $entry): array => [
    'feature_key' => 'vendor_manual_selection',
    'selected_vendor_ids' => $entry->summary_payload['selected_vendor_ids'] ?? [],
    'selected_count' => $entry->summary_payload['selected_count'] ?? 0,
],
```

- [ ] **Step 7: Run the recommendation and shortlist feature tests to verify persistence and evidence**

Run:
```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit \
  tests/Feature/Api/V1/VendorRecommendationApiTest.php \
  tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php \
  tests/Feature/Api/V1/RfqRecommendationDecisionTrailTest.php
```

Expected: PASS with persisted recommendation artifact and separate decision-trail events.

- [ ] **Step 8: Commit**

```bash
git add \
  apps/atomy-q/API/app/Models/RfqRecommendationArtifact.php \
  apps/atomy-q/API/database/migrations/2026_04_27_000001_create_rfq_recommendation_artifacts_table.php \
  apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php \
  apps/atomy-q/API/app/Http/Controllers/Api/V1/RequisitionVendorSelectionController.php \
  apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php \
  apps/atomy-q/API/app/Http/Controllers/Api/V1/DecisionTrailController.php \
  apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationApiTest.php \
  apps/atomy-q/API/tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php \
  apps/atomy-q/API/tests/Feature/Api/V1/RfqRecommendationDecisionTrailTest.php
git commit -m "Persist canonical sourcing recommendation artifacts"
```

## Task 3: Tighten Canonical Recommendation Contract And Remove Compatibility Aliases

**Files:**
- Modify: `orchestrators/ProcurementOperations/src/Coordinators/VendorRecommendationCoordinator.php`
- Modify: `orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationApiTest.php`
- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.test.ts`

- [ ] **Step 1: Write the failing tests for canonical-only response shape**

```php
$response->assertJsonMissingPath('data.candidates');
$response->assertJsonMissingPath('data.excluded_reasons');
```

```ts
expect(() => normalizeVendorRecommendationPayload(payload)).toThrow(
  'Invalid vendor recommendation payload',
);
```

Use a payload that only includes the removed alias keys to prove the hook no longer preserves the legacy shape.

- [ ] **Step 2: Run the API and hook tests to verify they fail before cleanup**

Run:
```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/Api/V1/VendorRecommendationApiTest.php
cd /home/azaharizaman/dev/atomy/apps/atomy-q/WEB && npm run test:unit -- src/hooks/use-vendor-recommendations.test.ts
```

Expected: FAIL because the API still returns alias fields and the hook still accepts them.

- [ ] **Step 3: Remove the legacy alias fields from the controller response**

```php
return response()->json([
    'data' => [
        'tenant_id' => $result->tenantId,
        'rfq_id' => $result->rfqId,
        'status' => $result->status->value,
        'eligible_candidates' => ...,
        'excluded_candidates' => $excludedCandidates,
        'provider_explanation' => $result->providerExplanation,
        'deterministic_reason_set' => $result->deterministicReasonSet,
        'provenance' => $result->provenance?->toArray(),
    ],
]);
```

- [ ] **Step 4: Remove alias fallback parsing from the WEB hook**

```ts
const eligibleCandidates = normalizeResultList(
  pickField(payloadObject, 'eligible_candidates', 'eligibleCandidates'),
  normalizeCandidate,
);
const excludedCandidates = normalizeExcludedList(
  pickField(payloadObject, 'excluded_candidates', 'excludedCandidates'),
);
```

Delete support for:
```ts
'candidates'
'excluded_reasons'
'excludedReasons'
'recommended_reason_summary' as the canonical top-level contract
```

- [ ] **Step 5: Keep zero-candidate and rejected-output coordinator behavior explicit**

```php
if (!is_array($eligibleCandidates) || !array_is_list($eligibleCandidates)) {
    return null;
}

// zero candidates stays valid because [] passes the list check
```

Add test coverage for malformed provider payload with missing `vendor_name` or invalid provenance to guarantee `provider_output_rejected`.

- [ ] **Step 6: Run coordinator, API, and hook tests**

Run:
```bash
./vendor/bin/phpunit orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/Api/V1/VendorRecommendationApiTest.php
cd /home/azaharizaman/dev/atomy/apps/atomy-q/WEB && npm run test:unit -- src/hooks/use-vendor-recommendations.test.ts
```

Expected: PASS with canonical-only response shape and explicit rejected-output behavior.

- [ ] **Step 7: Commit**

```bash
git add \
  orchestrators/ProcurementOperations/src/Coordinators/VendorRecommendationCoordinator.php \
  orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php \
  apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php \
  apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationApiTest.php \
  apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.ts \
  apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.test.ts
git commit -m "Tighten canonical sourcing recommendation contract"
```

## Task 4: Finish Canonical WEB Workflow And Remove Ambiguous UI Assumptions

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx`
- Modify if needed: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx`

- [ ] **Step 1: Write the failing UI tests for canonical advisory-only behavior**

```ts
expect(screen.getByText('AI recommendation summary')).toBeInTheDocument();
expect(screen.getByRole('checkbox', { name: /alpha procurement/i })).not.toBeChecked();
expect(screen.queryByText(/saved by ai/i)).not.toBeInTheDocument();
```

Add one page-level test for zero-candidate valid state:

```ts
expect(screen.getByText(/no recommendation candidates/i)).toBeInTheDocument();
expect(screen.getByRole('button', { name: /save selection/i })).toBeEnabled();
```

- [ ] **Step 2: Run the focused WEB tests to verify the missing zero-candidate and wording behavior**

Run:
```bash
cd apps/atomy-q/WEB && npm run test:unit -- \
  'src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx' \
  'src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx' \
  'src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx'
```

Expected: FAIL until the page distinguishes zero-candidate valid result from unavailable state.

- [ ] **Step 3: Add explicit zero-candidate valid UI copy and keep advisory/manual separation**

```tsx
{!shouldShowRecommendationUnavailable && recommendationsQuery.data?.status === 'available' && recommendedById.size === 0 ? (
  <div className="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
    <p className="font-semibold">No recommendation candidates</p>
    <p className="mt-1">The provider returned no eligible shortlist candidates for this RFQ. You can still select vendors manually.</p>
  </div>
) : null}
```

- [ ] **Step 4: Keep the shortlist controls authoritative and recommendation controls scoped**

```tsx
const shouldShowRecommendationUnavailable =
  aiStatus.shouldShowUnavailableMessage('vendor_ai_ranking') || recommendationsQuery.data?.status === 'unavailable';

const shouldHideRecommendationAffordances =
  aiStatus.shouldHideAiControls('vendor_ai_ranking') || shouldShowRecommendationUnavailable;
```

Do not let this branch disable:

- vendor search,
- checkbox selection,
- shortlist save.

- [ ] **Step 5: Run the focused WEB tests again**

Run:
```bash
cd apps/atomy-q/WEB && npm run test:unit -- \
  'src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx' \
  'src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx' \
  'src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx'
```

Expected: PASS with advisory-only recommendation UX, unavailable continuity, and zero-candidate valid-state rendering.

- [ ] **Step 6: Commit**

```bash
git add \
  'apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx' \
  'apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx' \
  'apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx' \
  'apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx'
git commit -m "Clarify sourcing recommendation advisory workflow"
```

## Task 5: Add Fake-Provider And Real-Provider E2E For Canonical Sourcing Recommendation

**Files:**
- Create: `apps/atomy-q/WEB/tests/provider-sourcing-recommendation-e2e.spec.ts`
- Create: `apps/atomy-q/WEB/tests/provider-sourcing-recommendation-live.spec.ts`
- Modify: `apps/atomy-q/WEB/package.json`
- Modify if needed: `apps/atomy-q/WEB/playwright.config.ts`

- [ ] **Step 1: Write the fake-provider E2E first**

```ts
test('renders recommendation, keeps shortlist manual, and saves selected vendors', async ({ page }) => {
  await seedAuthSession(page, ...);
  await page.route('**/api/v1/ai/status', ...provider healthy...);
  await page.route('**/api/v1/rfqs/rfq-1/vendor-recommendations', ...canonical recommendation payload...);
  await page.route('**/api/v1/rfqs/rfq-1/selected-vendors', ...empty GET and PUT persistence assertion...);

  await page.goto('/rfqs/rfq-1/vendors');
  await expect(page.getByText('AI recommendation summary')).toBeVisible();
  await expect(page.getByRole('checkbox', { name: /alpha procurement/i })).not.toBeChecked();

  await page.getByRole('checkbox', { name: /beta supply/i }).click();
  await page.getByRole('button', { name: /save selection/i }).click();

  await expect(page.getByText(/selection saved/i)).toBeVisible();
});
```

- [ ] **Step 2: Run the fake-provider Playwright test to verify it fails before implementation**

Run:
```bash
cd apps/atomy-q/WEB && npx playwright test tests/provider-sourcing-recommendation-e2e.spec.ts --workers=1
```

Expected: FAIL because the new test file and script do not exist yet.

- [ ] **Step 3: Add fake-provider and live-provider scripts**

```json
"test:e2e:provider-sourcing:fake": "cross-env NEXT_PUBLIC_AI_MODE=provider playwright test tests/provider-sourcing-recommendation-e2e.spec.ts --workers=1",
"test:e2e:provider-sourcing:live": "cross-env AI_PROVIDER_E2E=true AI_MODE=provider NEXT_PUBLIC_AI_MODE=provider NEXT_PUBLIC_USE_MOCKS=false playwright test tests/provider-sourcing-recommendation-live.spec.ts --workers=1"
```

- [ ] **Step 4: Implement the real-provider Playwright flow**

```ts
test('real provider recommendation does not auto-mutate shortlist and manual save still works', async ({ page, request }) => {
  const login = await request.post(`${apiBase}/auth/login`, { data: { email, password } });
  const token = ...;

  const rfqId = await createRfqWithApprovedVendors(request, token);

  await seedAuthSession(page, user, { token });
  await page.goto(`/rfqs/${encodeURIComponent(rfqId)}/vendors`);

  await expect.poll(async () => {
    const response = await request.post(`${apiBase}/rfqs/${encodeURIComponent(rfqId)}/vendor-recommendations`, {
      headers: { Authorization: `Bearer ${token}` },
      data: { categories: ['services'] },
    });
    return response.ok();
  }).toBe(true);

  await expect(page.getByText(/AI recommendation summary/i)).toBeVisible();
  await expect(page.getByRole('checkbox', { name: /recommended vendor/i })).not.toBeChecked();

  await page.getByRole('checkbox', { name: /recommended vendor/i }).click();
  await page.getByRole('button', { name: /save selection/i }).click();
});
```

- [ ] **Step 5: Add unavailable and zero-candidate E2E coverage in the fake-provider suite**

```ts
test('shows unavailable banner but keeps manual shortlist usable', async ({ page }) => { ... });
test('shows zero-candidate valid state but keeps manual shortlist usable', async ({ page }) => { ... });
```

- [ ] **Step 6: Run fake-provider E2E and, when environment is ready, real-provider E2E**

Run:
```bash
cd apps/atomy-q/WEB && npm run test:e2e:provider-sourcing:fake
cd apps/atomy-q/WEB && npm run test:e2e:provider-sourcing:live
```

Expected:
- fake suite PASS locally without network stubs beyond the page routes,
- live suite PASS in provider-ready environment with `NEXT_PUBLIC_USE_MOCKS=false`.

- [ ] **Step 7: Commit**

```bash
git add \
  apps/atomy-q/WEB/tests/provider-sourcing-recommendation-e2e.spec.ts \
  apps/atomy-q/WEB/tests/provider-sourcing-recommendation-live.spec.ts \
  apps/atomy-q/WEB/package.json \
  apps/atomy-q/WEB/playwright.config.ts
git commit -m "Add sourcing recommendation end-to-end coverage"
```

## Task 6: Regenerate Client, Sync Docs, And Run Full Verification

**Files:**
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/docs/03-domains/vendors/overview.md`
- Modify: `apps/atomy-q/docs/03-domains/vendors/workflows.md`
- Modify if needed: `apps/atomy-q/docs/03-domains/DOMAIN_MAP.md`
- Modify: `apps/atomy-q/WEB/src/generated/api`

- [ ] **Step 1: Regenerate the WEB client after OpenAPI cleanup**

Run:
```bash
cd apps/atomy-q/WEB && npm run generate:api
```

Expected: generated client no longer contains `/recommendations/*` operations.

- [ ] **Step 2: Update implementation summaries and domain docs**

```md
- RFQ vendors workspace is now the only canonical sourcing recommendation surface.
- Legacy `/recommendations/*` routes were removed rather than preserved as structured-unavailable stubs.
- Recommendation artifact and buyer shortlist changes are durably recorded as separate decision-trail events.
- Real-provider E2E now covers provider-backed recommendation plus manual shortlist continuity.
```

- [ ] **Step 3: Run the full verification stack**

Run:
```bash
./vendor/bin/phpunit orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php
cd apps/atomy-q/API && ./vendor/bin/phpunit \
  tests/Feature/Api/V1/VendorRecommendationApiTest.php \
  tests/Feature/Api/V1/VendorRecommendationAiGateTest.php \
  tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php \
  tests/Feature/Api/V1/RfqRecommendationDecisionTrailTest.php
cd /home/azaharizaman/dev/atomy/apps/atomy-q/WEB && npm run test:unit -- \
  src/hooks/use-vendor-recommendations.test.ts \
  'src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx' \
  'src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx' \
  'src/app/(dashboard)/rfqs/[rfqId]/vendors/page.test.tsx'
cd /home/azaharizaman/dev/atomy/apps/atomy-q/WEB && npm run test:e2e:provider-sourcing:fake
```

Expected: PASS on unit, API, WEB unit, and fake-provider E2E. Run the live-provider E2E separately once env and quota are ready.

- [ ] **Step 4: Run live-provider verification when environment is ready**

Run:
```bash
cd apps/atomy-q/WEB && npm run test:e2e:provider-sourcing:live
```

Expected: PASS with real provider-backed recommendation visible in the vendors workflow and manual shortlist saved independently.

- [ ] **Step 5: Commit**

```bash
git add \
  apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/docs/03-domains/vendors/overview.md \
  apps/atomy-q/docs/03-domains/vendors/workflows.md \
  apps/atomy-q/docs/03-domains/DOMAIN_MAP.md \
  apps/atomy-q/WEB/src/generated/api
git commit -m "Document canonical sourcing recommendation workflow"
```

## Spec Coverage Check

- Canonical RFQ vendors workflow: Task 4 and Task 5.
- Canonical API surface only: Task 1.
- Removal of legacy recommendation endpoints and dangling surfaces: Task 1 and Task 6.
- Advisory AI versus authoritative shortlist separation: Task 2, Task 3, and Task 4.
- Durable separate audit evidence for recommendation and shortlist mutation: Task 2.
- Truthful unavailable and zero-candidate distinction: Task 3, Task 4, and Task 5.
- Real-provider E2E proof: Task 5 and Task 6.

