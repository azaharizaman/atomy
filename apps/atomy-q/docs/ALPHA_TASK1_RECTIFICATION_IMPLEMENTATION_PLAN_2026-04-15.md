# Alpha Task 1 Rectification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the Task 1 baseline failures so Atomy-Q has green WEB and API alpha release evidence before proceeding to Task 2.

**Architecture:** This is a narrow rectification pass. WEB fixes stay in hooks/page components and preserve live-mode fail-loud behavior. API fixes stay in Laravel adapters/controllers/models/tests and preserve tenant-scoped behavior, with no Layer 1 or Layer 2 framework coupling changes.

**Tech Stack:** Laravel API, PHPUnit, Next.js App Router, TypeScript, TanStack Query, Vitest.

---

## File Structure

- Modify `apps/atomy-q/WEB/src/lib/api-live.ts`: make live error metadata assignment type-safe.
- Modify `apps/atomy-q/WEB/src/hooks/use-award.ts`: expose award creation mutation if it is not already present on the current branch.
- Modify `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`: render no-award creation UI using final frozen comparison runs and vendors.
- Modify `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`: keep the no-award creation assertion and add click/mutation coverage if missing.
- Modify `apps/atomy-q/API/app/Contracts/MfaChallengeStoreInterface.php`: add a challenge lookup method that does not require client-supplied tenant ID.
- Modify `apps/atomy-q/API/app/Services/Identity/AtomyMfaChallengeStore.php`: implement the lookup method with an ID-only query.
- Modify `apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php`: make `tenant_id` optional for MFA verify and use stored challenge tenant for all downstream operations.
- Modify `apps/atomy-q/API/tests/Feature/Api/AuthTest.php`: assert MFA verify works without `tenant_id` and rejects a mismatched optional `tenant_id` generically.
- Modify `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`: add RFQ line-item fixture data to the upload happy path and keep failure behavior explicit.
- Modify `apps/atomy-q/API/app/Models/Award.php`: add the expected `creator()` relation mapped to the current schema.
- Modify `apps/atomy-q/API/tests/Unit/Services/SourcingOperationsAdaptersTest.php`: add nullable `project_id` to the in-memory RFQ schema.
- Modify `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`: replace Task 1 failure evidence after verification.
- Update the affected package/app `IMPLEMENTATION_SUMMARY.md` after every code change in that package/app.

## Task 1: Fix WEB Build Error Metadata Typing

**Files:**
- Modify: `apps/atomy-q/WEB/src/lib/api-live.ts`

- [ ] **Step 1: Run the failing build gate**

Run:

```bash
cd apps/atomy-q/WEB && npm run build
```

Expected before the fix: TypeScript fails at `src/lib/api-live.ts` because `Error` is cast directly to `Record<string, unknown>`.

- [ ] **Step 2: Replace unsafe error metadata casts**

In `apps/atomy-q/WEB/src/lib/api-live.ts`, use a local decorated error type around the existing error object:

```ts
type LiveApiError = Error & {
  status?: number;
  response?: unknown;
};

const err: LiveApiError = new Error(`Failed to load ${endpoint}: ${message}`);
if (e instanceof Error) {
  err.cause = e;
}
const axiosErr = e as { response?: { status?: number; data?: unknown }; status?: number };
if (axiosErr.response) {
  err.status = axiosErr.response.status;
  err.response = axiosErr.response.data;
} else if (axiosErr.status) {
  err.status = axiosErr.status;
}
throw err;
```

Keep the existing endpoint/message behavior unchanged.

- [ ] **Step 3: Verify the build gate moves past this failure**

Run:

```bash
cd apps/atomy-q/WEB && npm run build
```

Expected after this task: the previous `api-live.ts` type error is gone. If the build exposes a later unrelated error, record it in the checklist and continue with the remaining rectification tasks.

## Task 2: Restore Award Creation Empty State

**Files:**
- Modify: `apps/atomy-q/WEB/src/hooks/use-award.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx`

- [ ] **Step 1: Run the focused failing unit test**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run 'src/app/(dashboard)/rfqs/[rfqId]/award/page.test.tsx'
```

Expected before the fix: `shows award creation UI when no award exists` fails because the page renders passive empty-state text instead of `Select a vendor...` and `Create Award`.

- [ ] **Step 2: Ensure `useAward` exposes a create mutation**

If `useAward` does not already return `store`, add it in `apps/atomy-q/WEB/src/hooks/use-award.ts`:

```ts
const store = useMutation({
  mutationFn: async (input: { rfqId: string; comparisonRunId: string; vendorId: string }) => {
    const { data } = await api.post('/awards', {
      rfq_id: input.rfqId,
      comparison_run_id: input.comparisonRunId,
      vendor_id: input.vendorId,
    });
    return data;
  },
  onSuccess: () => {
    qc.invalidateQueries({ queryKey: ['awards', rfqId] });
  },
});
```

Return `store` with `award`, `awards`, `signoff`, and `debrief`.

- [ ] **Step 3: Use comparison runs and vendors in the award page**

In `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`, import the hooks:

```ts
import { useComparisonRuns } from '@/hooks/use-comparison-runs';
import { useRfqVendors } from '@/hooks/use-rfq-vendors';
```

Inside `RfqAwardPageContent`, derive the creation state:

```ts
const { award, debrief, signoff, store } = useAward(rfqId);
const { data: comparisonRuns = [] } = useComparisonRuns(rfqId);
const { data: vendors = [] } = useRfqVendors(rfqId);
const finalRun = comparisonRuns.find((run) => run.type === 'final' && ['frozen', 'final', 'completed'].includes(run.status));
const awardCandidates = vendors.filter((vendor) => vendor.vendor_id !== null && vendor.vendor_id.trim() !== '');
const [selectedVendorId, setSelectedVendorId] = React.useState('');

React.useEffect(() => {
  if (selectedVendorId === '' && awardCandidates[0]?.vendor_id) {
    setSelectedVendorId(awardCandidates[0].vendor_id);
  }
}, [awardCandidates, selectedVendorId]);
```

- [ ] **Step 4: Render the no-award creation panel**

When `displayAward` is null and `finalRun` exists, render concise guidance and a create action near the recommended winner card:

```tsx
{!displayAward && finalRun ? (
  <SectionCard title="Create award">
    <div className="space-y-3">
      <p className="text-sm text-slate-600">
        Select a vendor to award the contract based on the final comparison run.
      </p>
      <label className="block space-y-1">
        <span className="text-xs font-medium text-slate-600">Award vendor</span>
        <select
          className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          value={selectedVendorId}
          onChange={(event) => setSelectedVendorId(event.target.value)}
        >
          {awardCandidates.map((vendor) => (
            <option key={vendor.id} value={vendor.vendor_id ?? ''}>
              {vendor.name}
            </option>
          ))}
        </select>
      </label>
      <Button
        size="sm"
        variant="primary"
        disabled={selectedVendorId === '' || store.isPending || useMocks}
        onClick={() => {
          if (finalRun && selectedVendorId !== '') {
            store.mutate({ rfqId, comparisonRunId: finalRun.id, vendorId: selectedVendorId });
          }
        }}
      >
        Create Award
      </Button>
    </div>
  </SectionCard>
) : null}
```

If no final run exists, keep the current `Freeze a comparison run to create an award record.` guidance.

- [ ] **Step 5: Add mutation assertion to the page test**

In `page.test.tsx`, store the mocked mutation in a variable and assert the click payload:

```ts
const storeMutate = vi.fn();
vi.mocked(useAward).mockReturnValue({
  award: null,
  awards: [],
  signoff: { mutate: vi.fn(), isPending: false, isError: false },
  debrief: { mutate: vi.fn(), isPending: false, isError: false },
  store: { mutate: storeMutate, isPending: false, isError: false },
} as unknown as UseAwardReturn);

renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

fireEvent.click(await screen.findByRole('button', { name: /create award/i }));
expect(storeMutate).toHaveBeenCalledWith({ rfqId: 'rfq-1', comparisonRunId: 'run-1', vendorId: 'vendor-1' });
```

- [ ] **Step 6: Verify WEB unit tests**

Run:

```bash
cd apps/atomy-q/WEB && npm run test:unit
```

Expected: all Vitest tests pass.

## Task 3: Fix MFA Verify Tenant Contract

**Files:**
- Modify: `apps/atomy-q/API/app/Contracts/MfaChallengeStoreInterface.php`
- Modify: `apps/atomy-q/API/app/Services/Identity/AtomyMfaChallengeStore.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php`
- Modify: `apps/atomy-q/API/tests/Feature/Api/AuthTest.php`

- [ ] **Step 1: Run the focused failing test**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter AuthTest::test_mfa_verify_endpoint_returns_message_and_verified
```

Expected before the fix: response status is 422 because `tenant_id` is required.

- [ ] **Step 2: Add ID-only challenge lookup to the interface**

Add to `MfaChallengeStoreInterface`:

```php
public function findByChallengeId(string $challengeId): ?MfaChallenge;
```

- [ ] **Step 3: Implement the lookup in `AtomyMfaChallengeStore`**

Add:

```php
public function findByChallengeId(string $challengeId): ?MfaChallenge
{
    return MfaChallenge::query()
        ->whereKey($challengeId)
        ->first();
}
```

Do not remove the existing tenant-scoped `find`, `consume`, or `incrementAttempts` methods.

- [ ] **Step 4: Make `tenant_id` optional in controller validation**

In `AuthController::mfaVerify`, change validation to:

```php
$validator = Validator::make($request->all(), [
    'challenge_id' => ['required', 'string'],
    'tenant_id' => ['sometimes', 'string'],
    'otp' => ['required', 'string'],
]);
```

Load the challenge before choosing tenant context:

```php
$challengeId = (string) $request->input('challenge_id');
$otp = trim((string) $request->input('otp'));
$suppliedTenantId = $request->input('tenant_id');

$challenge = $this->mfaChallenges->findByChallengeId($challengeId);
$tenantId = $challenge !== null ? (string) $challenge->tenant_id : '';

if (is_string($suppliedTenantId) && trim($suppliedTenantId) !== '' && $tenantId !== '' && trim($suppliedTenantId) !== $tenantId) {
    $challenge = null;
}
```

Keep the existing invalid/expired challenge response. All later calls to `incrementAttempts`, `consume`, audit context, and `completeMfaLogin` must use `$tenantId` derived from the stored challenge.

- [ ] **Step 5: Add mismatch coverage**

In `AuthTest`, add a test that submits a valid challenge with a different `tenant_id` and expects a generic failure:

```php
public function test_mfa_verify_rejects_mismatched_optional_tenant_id(): void
{
    $user = $this->createTestUser(['mfa_enabled' => true]);

    $challengeResponse = $this->postJson('/api/v1/auth/login', [
        'email' => $user['email'],
        'password' => $user['password'],
    ]);

    $challengeResponse->assertStatus(401);
    $challengeId = (string) $challengeResponse->json('challenge_id');

    $this->postJson('/api/v1/auth/mfa/verify', [
        'challenge_id' => $challengeId,
        'tenant_id' => 'wrong-tenant',
        'otp' => '123456',
    ])->assertStatus(401)
        ->assertJsonPath('message', 'Invalid or expired MFA challenge');
}
```

- [ ] **Step 6: Verify focused and matrix tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter AuthTest
```

Expected: Auth feature tests pass.

## Task 4: Fix Quote Upload Happy-Path Fixture

**Files:**
- Modify: `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`

- [ ] **Step 1: Run the focused failing test**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter QuoteSubmissionWorkflowTest::test_quote_submission_upload_persists_uploaded_state_and_tenant_id
```

Expected before the fix: response status is created, but `data.status` is `failed` instead of `ready`.

- [ ] **Step 2: Add at least one RFQ line item to the test fixture**

After creating the RFQ in `test_quote_submission_upload_persists_uploaded_state_and_tenant_id`, create a matching line item:

```php
RfqLineItem::query()->create([
    'tenant_id' => $user->tenant_id,
    'rfq_id' => $rfq->id,
    'line_number' => 1,
    'description' => 'Pump seal kit',
    'quantity' => 2,
    'uom' => 'EA',
    'estimated_unit_price' => 100,
    'currency' => 'USD',
    'sort_order' => 1,
]);
```

Add `use App\Models\RfqLineItem;` if it is not already imported.

- [ ] **Step 3: Keep the ready assertions**

Do not weaken these assertions:

```php
$response->assertCreated();
$response->assertJsonPath('data.status', 'ready');
$response->assertJsonPath('data.blocking_issue_count', 0);
```

The fixture should now satisfy the mock processor and prove the alpha upload happy path.

- [ ] **Step 4: Verify quote workflow tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter QuoteSubmissionWorkflowTest
```

Expected: all quote submission workflow tests pass.

## Task 5: Fix Award Relation Drift

**Files:**
- Modify: `apps/atomy-q/API/app/Models/Award.php`

- [ ] **Step 1: Run the focused relation failure**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter ModelRelationsTest
```

Expected before the fix: `Call to undefined method App\Models\Award::creator()`.

- [ ] **Step 2: Add `creator()` relation to `Award`**

In `Award.php`, add:

```php
/**
 * @return BelongsTo<User, $this>
 */
public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'signed_off_by');
}
```

Keep `signedOffByUser()` unchanged for existing call sites.

- [ ] **Step 3: Verify model relations**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter ModelRelationsTest
```

Expected: model relation tests pass.

## Task 6: Fix Sourcing Adapter Test Schema Drift

**Files:**
- Modify: `apps/atomy-q/API/tests/Unit/Services/SourcingOperationsAdaptersTest.php`

- [ ] **Step 1: Run the focused adapter tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter SourcingOperationsAdaptersTest
```

Expected before the fix: duplicate RFQ tests fail with SQLite missing `rfqs.project_id`.

- [ ] **Step 2: Add `project_id` to the in-memory schema**

Inside the `Schema::create('rfqs', ...)` callback, add this after `tenant_id`:

```php
$table->ulid('project_id')->nullable()->index();
```

This mirrors the production RFQ schema and lets `AtomyRfqLifecyclePersist::createDuplicate()` copy source project context.

- [ ] **Step 3: Verify adapter tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter SourcingOperationsAdaptersTest
```

Expected: adapter tests pass, including numeric suffix and duplicate exception behavior.

## Task 7: Run Release Gates And Update Evidence

**Files:**
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
- Modify: relevant `IMPLEMENTATION_SUMMARY.md` files only if behavior changed and the package/app already tracks that behavior.

- [ ] **Step 1: Run WEB lint**

Run:

```bash
cd apps/atomy-q/WEB && npm run lint
```

Expected: exit 0. Existing warnings may remain only if they are non-blocking and documented.

- [ ] **Step 2: Run WEB build**

Run:

```bash
cd apps/atomy-q/WEB && npm run build
```

Expected: exit 0.

- [ ] **Step 3: Run WEB unit tests**

Run:

```bash
cd apps/atomy-q/WEB && npm run test:unit
```

Expected: exit 0.

- [ ] **Step 4: Run API alpha matrix**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter "RegisterCompanyTest|AuthTest|RfqLifecycleMutationTest|RfqInvitationReminderTest|QuoteSubmissionWorkflowTest|QuoteIngestionPipelineTest|QuoteIngestionIntelligenceTest|NormalizationReviewWorkflowTest|ComparisonRunWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest|VendorWorkflowTest|IdentityGap7Test|OperationalApprovalApiTest|ProjectAclTest"
```

Expected: exit 0.

- [ ] **Step 5: Run API full suite**

Run:

```bash
cd apps/atomy-q/API && php artisan test
```

Expected: exit 0. If an unrelated failure appears, record exact class, test, status, and why it is unrelated before deciding whether Task 1 can remain closed.

- [ ] **Step 6: Update checklist evidence**

Update `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md` with:

```md
### Latest Rectification Evidence - 2026-04-15

- `cd apps/atomy-q/WEB && npm run lint`: PASS, with any remaining warnings listed.
- `cd apps/atomy-q/WEB && npm run build`: PASS.
- `cd apps/atomy-q/WEB && npm run test:unit`: PASS.
- `cd apps/atomy-q/API && php artisan test --filter "...alpha matrix..."`: PASS.
- `cd apps/atomy-q/API && php artisan test`: PASS or documented residual risk.
```

Do not delete the original baseline failure evidence; keep it as historical context and append the rectification evidence above or below it.

## Self-Review Checklist

- Every failure from `ALPHA_RELEASE_CHECKLIST.md` has a corresponding implementation task.
- No task disables or weakens an alpha gate.
- MFA tenant behavior resolves from stored challenge state and avoids cross-tenant existence leaks.
- WEB award creation does not fabricate live-mode data.
- Quote upload happy path uses a valid RFQ line-item fixture instead of treating no extracted lines as ready.
