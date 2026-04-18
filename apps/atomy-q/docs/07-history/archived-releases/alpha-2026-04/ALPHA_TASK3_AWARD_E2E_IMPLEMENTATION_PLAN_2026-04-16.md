# Alpha Task 3 Award End-To-End Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prove the alpha single-winner compare-to-award journey end to end by hardening the API award workflow, deriving a valid live create-award payload in WEB, surfacing create/signoff failures honestly, and recording the resulting release evidence.

**Architecture:** Keep the award journey narrow and explicit. The Laravel API remains the system of record for tenant-safe award creation, debrief, signoff, and decision-trail persistence, while the Next.js award screen stays responsible for user interaction and fail-loud behavior in live mode. The WEB create path should derive the currently required `amount` and `currency` from live comparison-run evidence instead of relying on seed data or hidden defaults.

**Tech Stack:** Laravel 12, PHP 8.3, PHPUnit feature tests, Next.js 16, React 19, TanStack Query, Vitest, Playwright.

---

## File Map

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/AwardController.php`
  - Enforce final-run-only create semantics, record decision-trail entries for create/signoff, and preserve tenant-safe failure behavior.
- `apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php`
  - Add explicit award-created and award-signed-off event helpers.
- `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php`
  - Prove the full single-winner workflow, cross-tenant isolation, non-final run rejection, and decision-trail evidence.
- `apps/atomy-q/WEB/src/hooks/use-award.ts`
  - Derive `amount` and `currency` from live comparison-run data before posting the create-award mutation, and keep live reads fail-loud.
- `apps/atomy-q/WEB/src/hooks/use-award.live.test.ts`
  - Verify live award reads, live create payload derivation, and signoff failure behavior.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
  - Surface recoverable create/signoff errors without seed fallback.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`
  - Lock the page behavior for create-award UI and signoff failure messaging.
- `apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts`
  - Extend the lifecycle E2E to exercise create award, debrief, and signoff on the award screen with explicit award routes.
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
  - Record shipped API-side Task 3 behavior.
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
  - Record shipped WEB-side Task 3 behavior if the current summary does not already cover it.
- `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
  - Add the Task 3 verification evidence and blocker A2 status.
- `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`
  - Link this implementation plan from Section 9, Task 3.

### Task 1: Add The Conclusive API Award Workflow Tests

**Files:**
- Modify: `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php`

- [ ] **Step 1: Write the failing full-workflow and isolation tests**

Add focused feature coverage that proves the single-winner path in one narrative and locks the tenant-safety/decision-trail requirements before touching the controller.

```php
public function test_single_winner_award_workflow_records_decision_trail_and_allows_debrief_before_signoff(): void
{
    [$user, $rfq, $run, , $quote] = $this->seedAward($this->createUser());

    $createResponse = $this->postJson(
        '/api/v1/awards',
        [
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $run->id,
            'vendor_id' => $quote->vendor_id,
            'amount' => '1000.00',
            'currency' => 'USD',
        ],
        $this->authHeaders((string) $user->tenant_id, (string) $user->id),
    );

    $createResponse->assertCreated();
    $awardId = (string) $createResponse->json('data.id');

    $loserVendorId = (string) Str::ulid();
    QuoteSubmission::query()->create([
        'tenant_id' => $user->tenant_id,
        'rfq_id' => $rfq->id,
        'vendor_id' => $loserVendorId,
        'vendor_name' => 'Runner Up Vendor',
        'status' => 'ready',
        'submitted_at' => now(),
        'confidence' => 90.0,
        'line_items_count' => 1,
        'warnings_count' => 0,
        'errors_count' => 0,
    ]);

    $debriefResponse = $this->postJson(
        '/api/v1/awards/' . $awardId . '/debrief/' . $loserVendorId,
        ['message' => 'Thanks for participating.'],
        $this->authHeaders((string) $user->tenant_id, (string) $user->id),
    );

    $debriefResponse->assertOk();
    $debriefResponse->assertJsonPath('data.status', 'pending');

    $signoffResponse = $this->postJson(
        '/api/v1/awards/' . $awardId . '/signoff',
        [],
        $this->authHeaders((string) $user->tenant_id, (string) $user->id),
    );

    $signoffResponse->assertOk();
    $signoffResponse->assertJsonPath('data.status', 'signed_off');

    $this->assertDatabaseHas('decision_trail_entries', [
        'tenant_id' => $user->tenant_id,
        'rfq_id' => $rfq->id,
        'comparison_run_id' => $run->id,
        'event_type' => 'award_created',
    ]);
    $this->assertDatabaseHas('decision_trail_entries', [
        'tenant_id' => $user->tenant_id,
        'rfq_id' => $rfq->id,
        'comparison_run_id' => $run->id,
        'event_type' => 'award_debriefed',
    ]);
    $this->assertDatabaseHas('decision_trail_entries', [
        'tenant_id' => $user->tenant_id,
        'rfq_id' => $rfq->id,
        'comparison_run_id' => $run->id,
        'event_type' => 'award_signed_off',
    ]);
}

public function test_store_rejects_non_final_comparison_run_for_award_creation(): void
{
    [$user, $rfq, $run, , $quote] = $this->seedAward($this->createUser());
    $run->status = 'draft';
    $run->save();

    $response = $this->postJson(
        '/api/v1/awards',
        [
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $run->id,
            'vendor_id' => $quote->vendor_id,
            'amount' => '1000.00',
            'currency' => 'USD',
        ],
        $this->authHeaders((string) $user->tenant_id, (string) $user->id),
    );

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'Comparison run is not finalized for award creation');
}

public function test_award_actions_return_not_found_for_other_tenant(): void
{
    [$user, $rfq, $run, $award] = $this->seedAward($this->createUser());
    $otherUser = $this->createUser();

    $indexResponse = $this->getJson(
        '/api/v1/awards?rfqId=' . $rfq->id,
        $this->authHeaders((string) $otherUser->tenant_id, (string) $otherUser->id),
    );
    $indexResponse->assertOk();
    $indexResponse->assertJsonCount(0, 'data');

    $signoffResponse = $this->postJson(
        '/api/v1/awards/' . $award->id . '/signoff',
        [],
        $this->authHeaders((string) $otherUser->tenant_id, (string) $otherUser->id),
    );
    $signoffResponse->assertNotFound();

    $debriefResponse = $this->postJson(
        '/api/v1/awards/' . $award->id . '/debrief/' . (string) Str::ulid(),
        ['message' => 'Hidden'],
        $this->authHeaders((string) $otherUser->tenant_id, (string) $otherUser->id),
    );
    $debriefResponse->assertNotFound();
}
```

- [ ] **Step 2: Run the focused API test to confirm the new assertions fail**

Run: `cd apps/atomy-q/API && php artisan test --filter AwardWorkflowTest`

Expected: FAIL because award create/signoff do not yet write decision-trail entries and non-final comparison runs are not yet rejected.

- [ ] **Step 3: Keep the existing helper fixture aligned with the new workflow**

Update `seedAward()` so the comparison run status clearly represents the alpha prerequisite state.

```php
$run = ComparisonRun::query()->create([
    'tenant_id' => $user->tenant_id,
    'rfq_id' => $rfq->id,
    'name' => 'Final comparison',
    'is_preview' => false,
    'status' => 'frozen',
    'response_payload' => [
        'snapshot' => [
            'vendors' => [
                [
                    'vendor_id' => $winnerVendorId,
                    'vendor_name' => 'Winner Vendor',
                    'quote_submission_id' => $quote->id,
                ],
                [
                    'vendor_id' => (string) Str::ulid(),
                    'vendor_name' => 'Runner Up Vendor',
                    'quote_submission_id' => null,
                ],
            ],
        ],
    ],
    // ...
]);
```

- [ ] **Step 4: Re-run the focused API test after fixture cleanup**

Run: `cd apps/atomy-q/API && php artisan test --filter AwardWorkflowTest`

Expected: still FAIL, but now only on the missing controller and decision-trail behavior rather than fixture drift.

- [ ] **Step 5: Commit the failing-test slice once the task is implemented**

```bash
git add apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php
git commit -m "test(api): lock award workflow alpha behavior"
```

### Task 2: Implement API Award Decision-Trail And Final-Run Enforcement

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AwardController.php`
- Modify: `apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php`
- Test: `apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php`

- [ ] **Step 1: Add failing recorder-level expectations in the API test**

Extend the assertions to prove create is recorded before debrief/signoff and that repeated signoff does not duplicate the first signoff state.

```php
$events = \App\Models\DecisionTrailEntry::query()
    ->where('comparison_run_id', $run->id)
    ->orderBy('sequence')
    ->pluck('event_type')
    ->all();

$this->assertSame(
    ['award_created', 'award_debriefed', 'award_signed_off'],
    $events,
);
```

- [ ] **Step 2: Extend `DecisionTrailRecorder` with award create/signoff helpers**

Add explicit methods instead of inlining `record()` calls in the controller so the event naming stays centralized.

```php
public function recordAwardCreated(
    string $tenantId,
    string $rfqId,
    string $comparisonRunId,
    array $summary,
): void {
    $this->record(
        tenantId: $tenantId,
        rfqId: $rfqId,
        comparisonRunId: $comparisonRunId,
        eventType: 'award_created',
        summary: $summary,
    );
}

public function recordAwardSignedOff(
    string $tenantId,
    string $rfqId,
    string $comparisonRunId,
    array $summary,
): void {
    $this->record(
        tenantId: $tenantId,
        rfqId: $rfqId,
        comparisonRunId: $comparisonRunId,
        eventType: 'award_signed_off',
        summary: $summary,
    );
}
```

- [ ] **Step 3: Inject the recorder into award create and signoff**

Update `AwardController::store()` and `AwardController::signoff()` to record the alpha decision events only when the award is tied to a comparison run.

```php
public function store(Request $request, DecisionTrailRecorder $decisionTrail): JsonResponse
{
    // existing validation and tenant/RFQ/vendor lookup...

    if (! empty($validated['comparison_run_id'])) {
        $run = ComparisonRun::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $validated['comparison_run_id'])
            ->where('rfq_id', $rfq->id)
            ->first();

        if ($run === null) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        if (! in_array($run->status, ['frozen', 'final', 'completed'], true)) {
            return response()->json(['message' => 'Comparison run is not finalized for award creation'], 422);
        }
    }

    $award = Award::query()->create([
        // existing fields...
    ]);

    if ($award->comparison_run_id !== null) {
        $decisionTrail->recordAwardCreated(
            $tenantId,
            $award->rfq_id,
            $award->comparison_run_id,
            [
                'award_id' => $award->id,
                'vendor_id' => $award->vendor_id,
                'status' => $award->status,
            ],
        );
    }

    return response()->json(['data' => $this->serializeAward($award)], 201);
}
```

- [ ] **Step 4: Record signoff only on the first successful state transition**

Keep signoff idempotent: the first successful signoff records the decision-trail event, repeated signoff calls only return the current award state.

```php
public function signoff(Request $request, string $id, DecisionTrailRecorder $decisionTrail): JsonResponse
{
    $tenantId = $this->tenantId($request);
    $award = $this->findAward($tenantId, $id);

    if ($award === null) {
        return response()->json(['message' => 'Award not found'], 404);
    }

    if ($award->status === 'signed_off' && $award->signoff_at !== null) {
        return response()->json(['data' => $this->serializeAward($award)]);
    }

    $award->status = 'signed_off';
    $award->signoff_at = now();
    $award->signed_off_by = $this->userId($request);
    $award->save();

    if ($award->comparison_run_id !== null) {
        $decisionTrail->recordAwardSignedOff(
            $tenantId,
            $award->rfq_id,
            $award->comparison_run_id,
            [
                'award_id' => $award->id,
                'signed_off_by' => $award->signed_off_by,
            ],
        );
    }

    return response()->json(['data' => $this->serializeAward($award)]);
}
```

- [ ] **Step 5: Re-run the focused API test**

Run: `cd apps/atomy-q/API && php artisan test --filter AwardWorkflowTest`

Expected: PASS with the new `award_created`, `award_debriefed`, and `award_signed_off` entries present and non-final comparison runs rejected with `422`.

- [ ] **Step 6: Commit the API implementation slice**

```bash
git add apps/atomy-q/API/app/Http/Controllers/Api/V1/AwardController.php \
  apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php \
  apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php
git commit -m "feat(api): record award decision trail"
```

### Task 3: Derive The Live Create-Award Payload And Show Recoverable WEB Errors

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-award.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-award.live.test.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`

- [ ] **Step 1: Write failing live-hook tests for create payload derivation and signoff failure**

Extend `use-award.live.test.ts` so it covers the mutation paths instead of only the query path.

```ts
const getMock = vi.hoisted(() => vi.fn());
const postMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
    post: postMock,
  },
}));

it('derives amount and currency from live comparison data before creating an award', async () => {
  getMock
    .mockResolvedValueOnce({ data: { data: [] } })
    .mockResolvedValueOnce({
      data: {
        data: {
          id: 'run-1',
          rfq_id: 'rfq-1',
          response_payload: {
            snapshot: {
              currency_meta: { 'line-1': 'USD' },
            },
          },
        },
      },
    })
    .mockResolvedValueOnce({
      data: {
        data: {
          id: 'run-1',
          clusters: [
            {
              cluster_key: 'cluster-1',
              basis: 'price',
              offers: [
                {
                  vendor_id: 'vendor-1',
                  rfq_line_id: 'line-1',
                  taxonomy_code: 'CAT-1',
                  normalized_unit_price: 125,
                  normalized_quantity: 4,
                  ai_confidence: 0.9,
                },
              ],
              statistics: {
                min_normalized_unit_price: 125,
                max_normalized_unit_price: 125,
                avg_normalized_unit_price: 125,
              },
              recommendation: {
                recommended_vendor_id: 'vendor-1',
                reason: 'lowest',
              },
            },
          ],
        },
      },
    });
  postMock.mockResolvedValueOnce({ data: { data: { id: 'award-1' } } });

  const { useAward } = await import('@/hooks/use-award');
  const { Wrapper } = createTestWrapper();
  const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.isSuccess).toBe(true));

  await act(async () => {
    await result.current.store.mutateAsync({
      rfqId: 'rfq-1',
      comparisonRunId: 'run-1',
      vendorId: 'vendor-1',
    });
  });

  expect(postMock).toHaveBeenCalledWith('/awards', {
    rfq_id: 'rfq-1',
    comparison_run_id: 'run-1',
    vendor_id: 'vendor-1',
    amount: 500,
    currency: 'USD',
  });
});

it('keeps signoff mutation errors visible to consumers', async () => {
  getMock.mockResolvedValueOnce({
    data: {
      data: [
        {
          id: 'award-1',
          rfq_id: 'rfq-1',
          vendor_id: 'vendor-1',
          vendor_name: 'Live Vendor',
          status: 'pending',
          amount: 1000,
          currency: 'USD',
        },
      ],
    },
  });
  postMock.mockRejectedValueOnce(new Error('Signoff rejected by API'));

  const { useAward } = await import('@/hooks/use-award');
  const { Wrapper } = createTestWrapper();
  const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

  await waitFor(() => expect(result.current.award?.id).toBe('award-1'));

  await expect(result.current.signoff.mutateAsync('award-1')).rejects.toThrow('Signoff rejected by API');
});
```

- [ ] **Step 2: Run the focused hook test to verify the new assertions fail**

Run: `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts`

Expected: FAIL because the hook does not yet derive `amount`/`currency` and the live mutation mocks are not fully exercised.

- [ ] **Step 3: Implement live create-award payload derivation in `use-award.ts`**

Keep the logic inside the award hook so the page only passes the RFQ, comparison run, and selected vendor. Derive `amount` by summing `normalized_unit_price * normalized_quantity` for the selected vendor and require a single non-empty currency from the snapshot `currency_meta`.

```ts
async function buildAwardCreatePayload(input: { rfqId: string; comparisonRunId: string; vendorId: string }) {
  const runResponse = await api.get(`/comparison-runs/${encodeURIComponent(input.comparisonRunId)}`);
  const matrixResponse = await api.get(`/comparison-runs/${encodeURIComponent(input.comparisonRunId)}/matrix`);

  const run = normalizeComparisonRun(runResponse.data);
  const matrix = normalizeComparisonRunMatrix(matrixResponse.data);

  const matchingOffers = matrix.clusters
    .flatMap((cluster) => cluster.offers)
    .filter((offer) => offer.vendorId === input.vendorId);

  if (matchingOffers.length === 0) {
    throw new Error('Selected vendor has no priced offers in the final comparison run.');
  }

  const amount = matchingOffers.reduce(
    (sum, offer) => sum + (offer.normalizedUnitPrice * offer.normalizedQuantity),
    0,
  );

  const currencies = new Set(
    matchingOffers
      .map((offer) => run.snapshot?.currencyMeta[offer.rfqLineId] ?? '')
      .filter((currency) => currency.trim() !== ''),
  );

  if (currencies.size !== 1) {
    throw new Error('Award creation requires one resolved comparison currency.');
  }

  return {
    rfq_id: input.rfqId,
    comparison_run_id: input.comparisonRunId,
    vendor_id: input.vendorId,
    amount,
    currency: [...currencies][0],
  };
}
```

- [ ] **Step 4: Add visible signoff error state to the award page**

Match the existing create-award error pattern so the UI stays recoverable and explicit in live mode.

```tsx
const [signoffError, setSignoffError] = React.useState('');

<Button
  size="sm"
  variant="primary"
  disabled={!displayAward || displayAward.status === 'signed_off' || signoff.isPending || useMocks}
  onClick={() => {
    if (displayAward) {
      setSignoffError('');
      signoff.mutate(displayAward.id, {
        onError: (error) => {
          setSignoffError(error instanceof Error ? error.message : 'Award signoff failed.');
        },
        onSuccess: () => {
          setSignoffError('');
        },
      });
    }
  }}
>
  Finalize Award
</Button>
{signoffError !== '' ? <p className="text-xs text-red-600">{signoffError}</p> : null}
```

- [ ] **Step 5: Extend the page test for signoff failure visibility**

Add a unit test alongside the existing create-award test so the page contract covers both failure states from the spec.

```ts
it('shows a signoff error when finalization fails', async () => {
  const signoffMutate = vi.fn();

  vi.mocked(useAward).mockReturnValue({
    award: mockAward,
    awards: [mockAward],
    signoff: { mutate: signoffMutate, isPending: false, isError: false },
    debrief: { mutate: vi.fn(), isPending: false, isError: false },
    store: { mutate: vi.fn(), isPending: false, isError: false },
  } as unknown as UseAwardReturn);

  renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

  fireEvent.click(await screen.findByRole('button', { name: /finalize award/i }));

  const callbacks = signoffMutate.mock.calls[0]?.[1] as { onError: (error: Error) => void };
  act(() => {
    callbacks.onError(new Error('Award signoff rejected'));
  });

  expect(screen.getByText('Award signoff rejected')).toBeInTheDocument();
});
```

- [ ] **Step 6: Re-run the focused WEB tests**

Run:
- `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts`
- `cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`

Expected: PASS with live create payload derivation and visible create/signoff failures locked in.

- [ ] **Step 7: Commit the WEB slice**

```bash
git add apps/atomy-q/WEB/src/hooks/use-award.ts \
  apps/atomy-q/WEB/src/hooks/use-award.live.test.ts \
  apps/atomy-q/WEB/src/app/\(dashboard\)/rfqs/\[rfqId\]/award/page.tsx \
  apps/atomy-q/WEB/src/app/\(dashboard\)/rfqs/\[rfqId\]/award/page.test.tsx
git commit -m "feat(web): harden live award workflow"
```

### Task 4: Extend The Award E2E And Update Release Evidence

**Files:**
- Modify: `apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`

- [ ] **Step 1: Add explicit mocked award routes to the lifecycle E2E**

Keep the E2E flow local and deterministic by stubbing award endpoints directly instead of relying on seed UI state.

```ts
let currentAward: null | {
  id: string;
  vendor_id: string;
  vendor_name: string;
  status: 'pending' | 'signed_off';
  amount: number;
  currency: string;
} = null;

await page.route('**/api/v1/awards**', async (route) => {
  const origin = new URL(page.url()).origin;
  const cors = buildCorsHeaders(origin);

  if (route.request().method() === 'GET') {
    await route.fulfill({
      status: 200,
      headers: cors,
      contentType: 'application/json',
      body: JSON.stringify({ data: currentAward ? [currentAward] : [] }),
    });
    return;
  }

  if (route.request().method() === 'POST') {
    const payload = route.request().postDataJSON();
    currentAward = {
      id: 'award-e2e-1',
      vendor_id: payload.vendor_id,
      vendor_name: 'E2E Vendor',
      status: 'pending',
      amount: payload.amount,
      currency: payload.currency,
    };
    await route.fulfill({
      status: 201,
      headers: cors,
      contentType: 'application/json',
      body: JSON.stringify({ data: currentAward }),
    });
  }
});

await page.route('**/api/v1/awards/award-e2e-1/signoff', async (route) => {
  const origin = new URL(page.url()).origin;
  const cors = buildCorsHeaders(origin);
  currentAward = { ...currentAward!, status: 'signed_off' };
  await route.fulfill({
    status: 200,
    headers: cors,
    contentType: 'application/json',
    body: JSON.stringify({ data: currentAward }),
  });
});
```

- [ ] **Step 2: Assert the award UI path inside the mocked lifecycle test**

Extend the existing mocked scenario so it creates the award, debriefs a loser, signs off, and verifies the signed-off state on screen.

```ts
await workspaceNavLink(page, 'award').click();
await expect(page).toHaveURL(/\/rfqs\/.+\/award/, { timeout: 15000 });

await expect(page.getByText(/select a vendor to award the contract/i)).toBeVisible();
await page.getByRole('button', { name: /create award/i }).click();

await page.getByLabel(/debrief message/i).fill('Thanks for your proposal.');
await page.getByRole('button', { name: /send debrief/i }).click();

await page.getByRole('button', { name: /finalize award/i }).click();
await expect(page.getByText(/signed off/i)).toBeVisible();
await expect(page.getByText(/awarded to/i)).toBeVisible();
```

- [ ] **Step 3: Run the award-focused verification matrix**

Run:
- `cd apps/atomy-q/API && php artisan test --filter AwardWorkflowTest`
- `cd apps/atomy-q/WEB && npx vitest run src/hooks/use-award.live.test.ts`
- `cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`
- `cd apps/atomy-q/WEB && npm run test:e2e -- tests/rfq-lifecycle-e2e.spec.ts`

Expected:
- PHPUnit: PASS
- Vitest hook test: PASS
- Vitest page test: PASS
- Playwright lifecycle test: PASS with the award path executed, not just the award page opened

- [ ] **Step 4: Update implementation summaries and release evidence**

Record what shipped and the exact verification evidence.

```md
## 2026-04-16 - Alpha Task 3 Award End-To-End

- enforced final-comparison-only award creation in `AwardController`
- recorded `award_created` and `award_signed_off` decision-trail events
- kept debrief available immediately after award creation
- derived live `amount` and `currency` in `use-award.ts` from comparison-run evidence
- added visible create/signoff failure states on the WEB award page
- extended `AwardWorkflowTest`, `use-award.live.test.ts`, `award/page.test.tsx`, and `rfq-lifecycle-e2e.spec.ts`
```

- [ ] **Step 5: Link the plan from the release plan**

Add the Task 3 follow-up plan line beside the spec link.

```md
- Follow-up spec: [`ALPHA_TASK3_AWARD_E2E_SPEC_2026-04-16.md`](./ALPHA_TASK3_AWARD_E2E_SPEC_2026-04-16.md)
- Follow-up plan: [`ALPHA_TASK3_AWARD_E2E_IMPLEMENTATION_PLAN_2026-04-16.md`](./ALPHA_TASK3_AWARD_E2E_IMPLEMENTATION_PLAN_2026-04-16.md)
```

- [ ] **Step 6: Commit the verification-and-docs slice**

```bash
git add apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts \
  apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md \
  apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md
git commit -m "docs(alpha): record award end-to-end proof"
```

## Self-Review

- **Spec coverage:** Task 1 and Task 2 cover API-side create/debrief/signoff workflow, tenant safety, final-run-only behavior, and decision-trail evidence. Task 3 covers live payload derivation and visible create/signoff failures in WEB. Task 4 covers E2E proof plus release-doc evidence.
- **Placeholder scan:** No `TODO`, `TBD`, or "implement later" markers remain. Each task names exact files, commands, and the behavior it is meant to lock.
- **Type consistency:** The plan consistently uses `rfq_id`, `comparison_run_id`, `vendor_id`, `amount`, and `currency` for create-award payloads and `award_created` / `award_debriefed` / `award_signed_off` for decision-trail events.
