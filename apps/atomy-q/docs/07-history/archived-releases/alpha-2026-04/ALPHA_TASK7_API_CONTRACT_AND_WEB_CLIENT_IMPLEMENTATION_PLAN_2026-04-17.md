# Alpha Task 7 API Contract And WEB Client Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Regenerate the Atomy-Q OpenAPI contract and WEB client, then remove alpha-critical contract drift so the design-partner path consumes honest generated request and response types without stale local workarounds.

**Architecture:** Keep Task 7 contract-first and alpha-critical only. Laravel + Scramble remain the source of truth, `apps/atomy-q/openapi/openapi.json` remains the committed interchange artifact, and `apps/atomy-q/WEB/src/generated/api/**` remains the generated client. Hand-written WEB hooks continue to normalize payloads for UI use, but they must stop compensating for stale generated route definitions with unsafe casts or invented local request shapes.

**Tech Stack:** Laravel 12, PHP 8.3, Scramble OpenAPI export, Next.js 16, TypeScript, @hey-api/openapi-ts, TanStack Query, Vitest.

---

## File Map

- `apps/atomy-q/openapi/openapi.json`
  - Fresh export from the current Laravel API; the committed contract artifact consumed by WEB codegen.
- `apps/atomy-q/WEB/src/generated/api/types.gen.ts`
  - Regenerated request and response types; inspect alpha-critical route-family diffs here first.
- `apps/atomy-q/WEB/src/generated/api/sdk.gen.ts`
  - Regenerated route helper signatures; use this file to remove stale `options?` and `body?: never` style workarounds when the contract is corrected.
- `apps/atomy-q/WEB/src/hooks/use-users.ts`
  - Existing users/roles hook; remove alpha-critical drift workarounds and keep only runtime-safe normalization.
- `apps/atomy-q/WEB/src/hooks/use-users.test.tsx`
  - Existing users hook tests; extend only if regeneration changes expected request or non-2xx envelope handling.
- `apps/atomy-q/WEB/src/hooks/use-award.ts`
  - Existing award hook; verify create/read/debrief/signoff generated contracts match the real API usage.
- `apps/atomy-q/WEB/src/hooks/use-award.live.test.ts`
  - Existing award live-mode test anchor for Task 7 verification.
- `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts`
  - Existing vendor invitation hook; keep it aligned with the regenerated live contract.
- `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.live.test.ts`
  - Existing vendor live-mode test anchor for Task 7 verification.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`
  - Existing comparison-run detail hook; verify snapshot and run envelope expectations after regeneration.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run.live.test.ts`
  - Existing comparison-run live-mode test anchor for Task 7 verification.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`
  - Existing comparison-matrix hook; verify matrix cluster/offer generated shapes remain accurate.
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.live.test.ts`
  - Existing comparison-matrix live-mode test anchor for Task 7 verification.
- `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
  - Existing normalization-review hook; verify conflict/meta envelope expectations after regeneration.
- `apps/atomy-q/WEB/src/hooks/use-normalization-review.live.test.ts`
  - Existing normalization-review live-mode test anchor for Task 7 verification.
- `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
  - Record Task 7 export/generate/build evidence and any accepted remaining wrapper exceptions.
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
  - Record WEB-side Task 7 behavior if hook expectations or generated-client usage change.
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
  - Record API-side Task 7 contract/export changes if OpenAPI source behavior changes.

## Task 1: Export, Regenerate, And Capture The Baseline Diff

**Files:**
- Modify: `apps/atomy-q/openapi/openapi.json`
- Modify: `apps/atomy-q/WEB/src/generated/api/types.gen.ts`
- Modify: `apps/atomy-q/WEB/src/generated/api/sdk.gen.ts`

- [ ] **Step 1: Export the current Laravel contract**

Run:

```bash
cd apps/atomy-q/API && php artisan scramble:export --path=../openapi/openapi.json
```

Expected: PASS and `apps/atomy-q/openapi/openapi.json` updates if the API export changed.

- [ ] **Step 2: Regenerate the committed WEB client**

Run:

```bash
cd apps/atomy-q/WEB && npm run generate:api
```

Expected: PASS and `apps/atomy-q/WEB/src/generated/api/**` updates from the fresh export.

- [ ] **Step 3: Inspect the alpha-critical generated diff**

Run:

```bash
cd /home/azaharizaman/dev/atomy && git diff -- \
  apps/atomy-q/openapi/openapi.json \
  apps/atomy-q/WEB/src/generated/api/types.gen.ts \
  apps/atomy-q/WEB/src/generated/api/sdk.gen.ts
```

Expected: a focused diff showing whether alpha-critical operations changed in `types.gen.ts` and `sdk.gen.ts`.

- [ ] **Step 4: Capture the route-family audit list before code changes**

Write down the specific alpha-critical routes revealed by the generated diff. At minimum, inspect these operation signatures in `apps/atomy-q/WEB/src/generated/api/sdk.gen.ts` and `apps/atomy-q/WEB/src/generated/api/types.gen.ts`:

```ts
userInvite
userIndex
userRoles
awardStore
awardSignoff
awardDebrief
vendorInvitationIndex
comparisonRunShow
comparisonRunMatrix
normalizationConflicts
```

If the generated names differ slightly, use the actual generated names, but keep the audit limited to the alpha-critical families from the spec.

- [ ] **Step 5: Commit after the task is implemented**

```bash
git add apps/atomy-q/openapi/openapi.json apps/atomy-q/WEB/src/generated/api
git commit -m "chore(atomy-q): refresh alpha task 7 api client baseline"
```

## Task 2: Lock Users And Roles Contract Parity

**Files:**
- Modify: `apps/atomy-q/openapi/openapi.json`
- Modify: `apps/atomy-q/WEB/src/generated/api/types.gen.ts`
- Modify: `apps/atomy-q/WEB/src/generated/api/sdk.gen.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-users.ts`
- Modify if needed: `apps/atomy-q/WEB/src/hooks/use-users.test.tsx`

- [ ] **Step 1: Write or update the failing users hook assertions**

Extend the existing users hook tests only where the regenerated contract exposes drift. Keep the test focused on generated-client behavior, not page rendering.

If the current non-2xx envelope and invite-body assertions are missing or need refresh, add or update a test like:

```ts
it('does not invalidate users query after a non-2xx invite response', async () => {
  vi.mocked(userInvite).mockResolvedValueOnce({
    data: undefined,
    error: { message: 'Invite failed' },
    request: {} as Request,
    response: {} as Response,
  });

  const { queryClient, Wrapper } = createTestWrapper();
  const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
  const { result } = renderHook(() => useInviteUser(), { wrapper: Wrapper });

  await expect(
    result.current.mutateAsync({
      email: 'new@atomy.test',
      name: 'New User',
    }),
  ).rejects.toThrow('Invite failed');

  expect(invalidateSpy).not.toHaveBeenCalled();
});
```

- [ ] **Step 2: Run the focused users hook suite and confirm the current drift**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/hooks/use-users.test.tsx
```

Expected: PASS if the users contract is already correct after regeneration, or FAIL with type/runtime drift that must be fixed in the hook or API export.

- [ ] **Step 3: Remove stale users contract workarounds**

In `apps/atomy-q/WEB/src/hooks/use-users.ts`, keep:

- response-envelope validation
- non-2xx error propagation
- UI-oriented normalization

Remove:

- `as never`
- invented request bodies
- generated-client bypasses that exist only because the contract was stale

Target shape:

```ts
async function invokeUserInvite(payload: InviteUserPayload) {
  return userInvite({ body: payload });
}

function requireSuccessfulResponse<TResponse extends { data: unknown; error?: unknown }>(
  response: TResponse | undefined,
  missingResponseMessage: string,
): TResponse {
  if (response === undefined) {
    throw new Error(missingResponseMessage);
  }

  if ('error' in response && response.error !== undefined) {
    throw new Error(extractResponseErrorMessage(response.error));
  }

  return response;
}
```

- [ ] **Step 4: Re-run the users hook suite**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/hooks/use-users.test.tsx
```

Expected: PASS, with invite-body and server-error behavior aligned to the regenerated client.

- [ ] **Step 5: Commit after the task is implemented**

```bash
git add \
  apps/atomy-q/openapi/openapi.json \
  apps/atomy-q/WEB/src/generated/api/types.gen.ts \
  apps/atomy-q/WEB/src/generated/api/sdk.gen.ts \
  apps/atomy-q/WEB/src/hooks/use-users.ts \
  apps/atomy-q/WEB/src/hooks/use-users.test.tsx
git commit -m "fix(atomy-q): align users roles contract with generated client"
```

## Task 3: Align Awards, Vendors, Comparison, And Normalization Hooks

**Files:**
- Modify as needed: `apps/atomy-q/openapi/openapi.json`
- Modify as needed: `apps/atomy-q/WEB/src/generated/api/types.gen.ts`
- Modify as needed: `apps/atomy-q/WEB/src/generated/api/sdk.gen.ts`
- Modify as needed: `apps/atomy-q/WEB/src/hooks/use-award.ts`
- Modify as needed: `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts`
- Modify as needed: `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`
- Modify as needed: `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`
- Modify as needed: `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
- Test: `apps/atomy-q/WEB/src/hooks/use-award.live.test.ts`
- Test: `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.live.test.ts`
- Test: `apps/atomy-q/WEB/src/hooks/use-comparison-run.live.test.ts`
- Test: `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.live.test.ts`
- Test: `apps/atomy-q/WEB/src/hooks/use-normalization-review.live.test.ts`

- [ ] **Step 1: Run the alpha-critical hook matrix before edits**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/hooks/use-award.live.test.ts \
  src/hooks/use-rfq-vendors.live.test.ts \
  src/hooks/use-comparison-run.live.test.ts \
  src/hooks/use-comparison-run-matrix.live.test.ts \
  src/hooks/use-normalization-review.live.test.ts
```

Expected: PASS if regeneration introduced no alpha-critical drift, or FAIL in the specific slice that still depends on stale generated request/response expectations.

- [ ] **Step 2: Fix contract drift by route family, not by random hook order**

Work route-family by route-family:

1. Awards
2. Vendors
3. Comparison detail
4. Comparison matrix
5. Normalization review

For each failing slice:

- inspect the generated operation type in `types.gen.ts`
- inspect the generated helper in `sdk.gen.ts`
- compare that contract to the real hook usage
- fix the OpenAPI source if the generated contract is wrong
- otherwise simplify the hook to consume the regenerated contract directly

Do not rewrite hook normalization wholesale. Keep only the UI-facing normalizers and remove stale contract compensations.

- [ ] **Step 3: Keep fail-loud behavior intact while removing stale type shims**

For any hook touched in this task, preserve the existing live-mode rules:

```ts
if (data === undefined) {
  throw new Error(`Comparison run "${runId}" is unavailable from the live API.`);
}

return normalizeAndValidateComparisonRun(data, options?.rfqId);
```

Task 7 is not allowed to weaken live-mode fail-loud behavior in order to make codegen integration easier.

- [ ] **Step 4: Re-run the hook matrix until it passes**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/hooks/use-award.live.test.ts \
  src/hooks/use-rfq-vendors.live.test.ts \
  src/hooks/use-comparison-run.live.test.ts \
  src/hooks/use-comparison-run-matrix.live.test.ts \
  src/hooks/use-normalization-review.live.test.ts
```

Expected: PASS, with all touched alpha-critical hooks consuming the regenerated contract honestly.

- [ ] **Step 5: Commit after the task is implemented**

```bash
git add \
  apps/atomy-q/openapi/openapi.json \
  apps/atomy-q/WEB/src/generated/api/types.gen.ts \
  apps/atomy-q/WEB/src/generated/api/sdk.gen.ts \
  apps/atomy-q/WEB/src/hooks/use-award.ts \
  apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts \
  apps/atomy-q/WEB/src/hooks/use-comparison-run.ts \
  apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts \
  apps/atomy-q/WEB/src/hooks/use-normalization-review.ts
git commit -m "fix(atomy-q): align alpha hooks with regenerated api contract"
```

## Task 4: Disposition The Remaining Alpha-Critical Route Families

**Files:**
- Modify only if needed: `apps/atomy-q/openapi/openapi.json`
- Modify only if needed: `apps/atomy-q/WEB/src/generated/api/types.gen.ts`
- Modify only if needed: `apps/atomy-q/WEB/src/generated/api/sdk.gen.ts`
- Modify only if needed: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify only if needed: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Audit auth, RFQs, quote submissions, and decision trail for undispositioned alpha drift**

Run:

```bash
cd /home/azaharizaman/dev/atomy && rg -n \
  "auth|rfq|quoteSubmission|decisionTrail|decision-trail|quote-submission" \
  apps/atomy-q/WEB/src/generated/api/types.gen.ts \
  apps/atomy-q/WEB/src/generated/api/sdk.gen.ts
```

Expected: a route-family inventory to compare with the generated diff from Task 1.

- [ ] **Step 2: Record an explicit disposition for each remaining alpha-critical diff**

For each route-family diff not already fixed in Tasks 2 and 3, decide one of:

- `fixed in Task 7`
- `no consumer change required; generated contract already matches runtime`
- `accepted non-alpha/shared collateral fix required to unblock build`

Do not leave any alpha-critical generated diff without a disposition.

- [ ] **Step 3: If a route family still blocks build or shared-client correctness, fix the minimum necessary**

Only touch additional code if the generated drift:

- breaks `npm run build`
- breaks shared generated-client code used by alpha hooks
- proves the exported OpenAPI is still wrong for an alpha-critical route

If no such issue remains, make no extra code changes in this step.

- [ ] **Step 4: Run the WEB build against the regenerated client**

Run:

```bash
cd apps/atomy-q/WEB && npm run build
```

Expected: PASS. If this fails, the failure is still part of Task 7 and must be fixed before moving on.

- [ ] **Step 5: Commit after the task is implemented**

```bash
git add \
  apps/atomy-q/openapi/openapi.json \
  apps/atomy-q/WEB/src/generated/api \
  apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md
git commit -m "chore(atomy-q): disposition alpha-critical contract drift"
```

## Task 5: Update Release Evidence And Run Final Task 7 Verification

**Files:**
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
- Modify if changed: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify if changed: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Record Task 7 evidence in the release checklist**

Update `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md` with a Task 7 entry that includes:

- export command
- generate command
- build command
- targeted hook-test command
- date
- operator/author
- accepted remaining wrapper exceptions, if any

Use an evidence block like:

```md
## Latest Task 7 API Contract And WEB Client Evidence - 2026-04-17

- `cd apps/atomy-q/API && php artisan scramble:export --path=../openapi/openapi.json`: PASS.
- `cd apps/atomy-q/WEB && npm run generate:api`: PASS.
- `cd apps/atomy-q/WEB && npm run build`: PASS.
- `cd apps/atomy-q/WEB && npx vitest run ...`: PASS.
- Remaining accepted wrapper exceptions: none.
```

- [ ] **Step 2: Update implementation summaries only if the final Task 7 behavior changed**

If Task 7 changed hook expectations or export behavior, update the summaries with short bullets. If the summaries are already accurate after the earlier commits, do not add noise.

- [ ] **Step 3: Run the full required Task 7 verification set**

Run:

```bash
cd apps/atomy-q/API && php artisan scramble:export --path=../openapi/openapi.json
cd apps/atomy-q/WEB && npm run generate:api
cd apps/atomy-q/WEB && npm run build
cd apps/atomy-q/WEB && npx vitest run \
  src/hooks/use-award.live.test.ts \
  src/hooks/use-users.test.tsx \
  src/hooks/use-rfq-vendors.live.test.ts \
  src/hooks/use-comparison-run.live.test.ts \
  src/hooks/use-comparison-run-matrix.live.test.ts \
  src/hooks/use-normalization-review.live.test.ts
```

Expected: all commands PASS. Task 7 is not complete until all of them do.

- [ ] **Step 4: Do the final git diff review before closing the task**

Run:

```bash
cd /home/azaharizaman/dev/atomy && git diff --stat
```

Expected: only Task 7 contract, generated-client, hook, and evidence files are in the final diff.

- [ ] **Step 5: Commit the checklist and final verification state**

```bash
git add \
  apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md \
  apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md
git commit -m "docs(atomy-q): record alpha task 7 verification"
```

## Spec Coverage Check

- Purpose and source-of-truth chain: covered by Tasks 1 through 4.
- Alpha-critical route-family boundary: covered by Tasks 2 through 4.
- Removal of unsafe generated-client escape hatches: covered by Tasks 2 and 3.
- Build-blocking collateral drift rule: covered by Task 4.
- Checklist evidence and accepted exceptions: covered by Task 5.
- Required verification commands: covered by Tasks 1, 3, 4, and 5.

## Placeholder Scan

- No `TODO`, `TBD`, or deferred “figure it out later” steps remain in the plan.
- Every task names exact files and exact commands.
- Every code-change step names the concrete route families or helpers to update.

## Type Consistency Check

- The plan consistently uses the same alpha-critical route families as the approved spec.
- The generated-contract fix path is consistent everywhere: export -> regenerate -> inspect -> fix schema or hook -> verify.
- The users invite example uses the approved payload shape `{ email, name }` throughout.
