# Alpha Task 4 Live-Mode Fail-Loud Spec

## Document Control

- **Task:** Section 9, Task 4 - Eliminate Live-Mode Seed Fallbacks On Golden Path
- **Date:** 2026-04-17
- **Status:** Draft for review
- **Primary plan reference:** [ALPHA_RELEASE_PLAN_2026-04-15.md](./ALPHA_RELEASE_PLAN_2026-04-15.md)

## 1. Purpose

Task 4 closes blocker A3 at the WEB runtime boundary: alpha live-mode validation must not succeed because hooks quietly fell back to seed data, empty arrays, fabricated records, or permissive placeholder states.

Mock mode remains useful for local demos and isolated UI development. Live mode, however, must become strict and honest. If the API is unavailable, returns `undefined`, or returns malformed payloads, golden-path consumers must fail loudly and show explicit recovery-oriented UI instead of silently proceeding with fake success.

This spec defines the live/mock contract, the hook behaviors, the page-level error UX, the required `.live.test.ts` coverage matrix, and the completion criteria for that work.

## 2. Current State

The current codebase already contains a partial separation between live mode and mock mode, but it is inconsistent across the golden path.

Observed patterns in the current WEB code include:

- `useRfqs` already keeps seed access inside explicit mock-mode branching and validates live pagination metadata.
- `useNormalizationSourceLines` and `useComparisonRunReadiness` already fail loudly in live mode when live data is missing.
- several other hooks still import seed helpers at module scope or return seed-backed data when live calls return `undefined`.
- some hooks currently treat `undefined` live responses as equivalent to "no business data exists yet".
- some pages still render normal empty states even when their underlying live hook would be more accurately described as unavailable.
- the comparison-run detail page already uses the correct pattern: loading state, then explicit unavailable state when any required live payload fails.

This means the codebase has the right direction, but Task 4 is still needed to make the golden path consistent and release-safe.

## 3. Scope

### In scope

- Remove live-mode seed fallback behavior from the alpha golden-path hooks.
- Keep seed/mock behavior available only when `NEXT_PUBLIC_USE_MOCKS === 'true'`.
- Make live-mode hooks fail loudly on unavailable, `undefined`, or malformed payloads.
- Add page-level live-mode error UX for the RFQ golden-path screens that consume those hooks.
- Standardize `.live.test.ts` coverage expectations for every hook in scope.
- Update WEB documentation and implementation summary text where needed to describe mock mode as local/demo behavior only.

### Out of scope

- Changing Laravel API behavior or response contracts unless a WEB-side mismatch is discovered and separately approved.
- Hiding non-alpha routes or changing alpha navigation mode.
- Reworking unrelated hooks outside the Task 4 golden path.
- Building a large shared hook abstraction or generic runtime framework.
- Replacing intentional mock-mode-only page scaffolding that is explicitly gated behind `NEXT_PUBLIC_USE_MOCKS === 'true'`.

## 4. Golden-Path Boundary

Task 4 applies only to the RFQ alpha journey that a design-partner user follows in live mode:

1. RFQ list and RFQ detail context
2. invited vendors
3. quote intake list
4. quote normalization workspace
5. comparison runs list
6. comparison run detail
7. award page

The key release rule is:

- if `NEXT_PUBLIC_USE_MOCKS === 'true'`, the hook may use seed or local mock behavior
- if `NEXT_PUBLIC_USE_MOCKS !== 'true'`, the hook must never return seed-backed business data, fabricated business data, or silent empty success in place of a broken live contract

## 5. Runtime Contract

### 5.1 Mock mode contract

Mock mode is a local-only development and demo capability.

In mock mode:

- hooks may dynamically import seed helpers
- pages may render mock-only scaffolding where already intended
- tests may explicitly verify mock-mode behavior in separate non-live test files

Mock mode must remain explicit. Seed data access should be clearly gated so a reader can identify that the behavior is only available when `NEXT_PUBLIC_USE_MOCKS === 'true'`.

### 5.2 Live mode contract

In live mode:

- hooks must call the real API only
- hooks must reject `undefined` live responses unless the endpoint contract explicitly allows absence
- hooks must reject malformed payloads during normalization
- hooks must surface API and normalization failures to React Query consumers
- pages must distinguish between "live empty" and "live unavailable"

Live mode must not:

- import or return seed data as fallback
- fabricate partial records to keep the UI moving
- coerce missing required payload sections into success states
- silently substitute `[]`, `{}`, or placeholder records where the user would interpret the result as real business state

## 6. Hook Requirements

### 6.1 Hooks in scope

Task 4 applies to the following hooks:

- `apps/atomy-q/WEB/src/hooks/use-rfq.ts`
- `apps/atomy-q/WEB/src/hooks/use-rfqs.ts`
- `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts`
- `apps/atomy-q/WEB/src/hooks/use-quote-submissions.ts`
- `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.ts`
- `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.ts`
- `apps/atomy-q/WEB/src/hooks/use-award.ts`

### 6.2 Seed import rule

For every hook in scope:

- seed imports must stay inside explicit mock-only branches
- module-scope seed imports are not allowed for hooks that participate in the live golden path
- if a hook needs mock data, use dynamic import or an equivalent explicit mock-only branch

This is required both for correctness and for code readability: it must be obvious from the hook itself that live mode and mock mode are separated intentionally.

### 6.3 `undefined` live response rule

For every hook in scope:

- `undefined` from `fetchLiveOrFail(...)` or equivalent live fetch behavior must throw in live mode
- the error should be specific enough for page-level UI to present an actionable message

Task 4 assumes that the golden-path endpoints are required live dependencies. For this reason, `undefined` should generally be interpreted as an unavailable live contract, not as a legitimate empty business state.

The only acceptable "empty" success case in live mode is when the API returns a valid payload shape that intentionally contains no business data, such as:

- an envelope with an empty `data` array for a list endpoint
- a readiness payload with empty blockers and warnings
- an awards list with no award yet, if the API returns the correct envelope

### 6.4 Malformed payload rule

For every hook in scope:

- normalization functions must remain authoritative for runtime payload validation
- missing required fields must throw
- wrong structural shapes must throw
- wrong primitive types for required fields must throw unless the hook already intentionally normalizes a small set of equivalent contract shapes

Task 4 is not trying to make the hooks brittle for harmless aliases. It is trying to prevent broken live contracts from being interpreted as successful alpha data.

### 6.5 Hook-specific expectations

#### `use-rfqs`

`use-rfqs` is already close to the target state and should serve as the reference for list-style live/mock separation.

Task 4 requires it to continue enforcing:

- no seed path in live mode
- pagination meta validation
- malformed pagination meta fails loudly

#### `use-rfq`

`use-rfq` must stop falling back to seed RFQ detail in live mode when the live fetch returns `undefined`.

In live mode:

- missing RFQ detail must throw
- malformed RFQ detail payload must throw

In mock mode:

- seed RFQ detail may still be used

#### `use-rfq-vendors`

`use-rfq-vendors` must stop returning seed invitation data in live mode.

In live mode:

- invitation list fetch errors must surface
- `undefined` responses must throw
- malformed invitation rows must throw

In mock mode:

- seed invitation rows may still be used

#### `use-quote-submissions`

`use-quote-submissions` must stop substituting seed quote rows in live mode.

In live mode:

- API failures must surface
- `undefined` responses must throw
- malformed quote submission rows must throw

In mock mode:

- seed quote rows may still be used

#### `use-normalization-source-lines`

This hook already behaves correctly in principle and should remain fail-loud in live mode.

Task 4 requires:

- keep live-mode `undefined` as an error
- keep malformed row rejection
- add/maintain `.live.test.ts` coverage matching the full Task 4 matrix

#### `use-normalization-review`

`use-normalization-review` must stop treating `undefined` live responses as an empty conflict list.

In live mode:

- API failures must surface
- `undefined` must throw
- malformed conflict or meta payloads must throw if required runtime assumptions are not satisfied

In mock mode:

- page-level mock scaffolding may still bypass this hook through explicit `enabled: !useMocks` logic where already intended

#### `use-comparison-runs`

`use-comparison-runs` must stop returning seed runs in live mode.

In live mode:

- API failures must surface
- `undefined` responses must throw
- malformed run rows must throw

In mock mode:

- seed comparison runs may still be used

#### `use-comparison-run`

`use-comparison-run` must stop returning a fabricated mock comparison run in live mode.

In live mode:

- API failures must surface
- `undefined` responses must throw
- malformed snapshot or run payloads must throw
- RFQ/run mismatch validation must remain active

In mock mode:

- a mock comparison run may still be built if explicitly needed

#### `use-comparison-run-matrix`

`use-comparison-run-matrix` must stop retrying live fetches into a mock matrix in live mode.

In live mode:

- missing matrix payload must throw
- malformed matrix payload must throw
- no fabricated empty matrix may be returned

In mock mode:

- a mock matrix may still be built if explicitly needed

#### `use-comparison-run-readiness`

This hook is already close to the intended behavior.

Task 4 requires:

- keep live-mode fail-loud behavior
- keep mock-mode query disabling
- add/maintain `.live.test.ts` coverage matching the full Task 4 matrix

#### `use-award`

`use-award` must stop returning seed-backed award records in live mode.

In live mode:

- awards fetch failures must surface
- `undefined` responses must throw
- malformed award list payloads must throw
- award pages must rely on real live award absence represented by a valid empty list payload, not by local seed fallback

In mock mode:

- seed award behavior may still be used where explicitly desired

## 7. Page-Level UX Requirements

Task 4 must cover not only hook correctness, but also the page states users see after hooks become strict.

The core UX rule is:

- successful empty live payloads render true empty states
- failed live payloads render unavailable/error states

Pages must not collapse those two cases into the same visual result.

### 7.1 RFQ-level dependency rule

Pages that depend on `useRfq` for context must not continue rendering normal downstream business sections as if RFQ data loaded successfully when the RFQ query is in error.

Task 4 may implement this by:

- adding a small shared RFQ-unavailable pattern, or
- handling RFQ error state explicitly in each page

Either approach is acceptable as long as the result is clear and consistent.

### 7.2 Vendors page

`rfqs/[rfqId]/vendors/page.tsx` must:

- keep the current empty state only for valid empty live responses
- show a dedicated error state when `useRfqVendors` fails
- avoid presenting "No vendors invited yet" when the real problem is that the live vendor roster could not be loaded

### 7.3 Quote intake list page

`rfqs/[rfqId]/quote-intake/page.tsx` must:

- handle `useQuoteSubmissions` error state explicitly
- handle `useNormalizationReview` error state explicitly in live mode
- avoid rendering a normal quote-intake table with empty rows when live quote submissions failed to load
- avoid suppressing normalization blocker visibility by treating review metadata failure as "no blockers"

### 7.4 Normalize workspace page

`rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx` must:

- keep mock-mode local scaffolding behind explicit mock gating
- render a dedicated live unavailable state if normalization conflicts or source lines fail
- avoid showing "No source lines yet" when the actual live normalization workspace failed to load
- preserve freeze-button gating semantics once live payloads load successfully

### 7.5 Comparison runs list page

`rfqs/[rfqId]/comparison-runs/page.tsx` must:

- handle `useComparisonRuns` error state explicitly
- avoid rendering an empty runs table when the live comparison-run list failed to load
- continue showing a true empty or zero-run state only when a valid live payload says there are no runs

### 7.6 Comparison run detail page

`rfqs/[rfqId]/comparison-runs/[runId]/page.tsx` already follows the desired pattern.

Task 4 should treat it as the reference implementation for:

- loading state
- explicit unavailable state
- surfacing the underlying error message when safe to show

The page may still need small wording or consistency updates, but no major UX redesign is required.

### 7.7 Award page

`rfqs/[rfqId]/award/page.tsx` already has explicit load-error handling.

Task 4 requires:

- keep that explicit error state
- remove upstream live seed fallback from `useAward`, `useComparisonRuns`, `useComparisonRun`, and `useComparisonRunMatrix`
- ensure the page's existing create-award and signoff flows are backed by honest live data absence and live failures

### 7.8 Empty-state integrity rule

Across all affected pages:

- empty state copy must describe true business emptiness only
- unavailable/error state copy must describe live load failure or malformed data
- the user must be able to tell whether they should create data, refresh, or fix the environment

## 8. Required Test Matrix

Every hook in scope must have a corresponding `.live.test.ts` file. Each `.live.test.ts` file must cover all four of the following assertions:

1. **API errors are surfaced to consumers**
   - no silent fallback
   - no fake success state
   - React Query error state is observable

2. **Live data returned on success**
   - the hook accepts the valid live payload shape
   - the hook returns the normalized runtime shape expected by the consuming page

3. **Undefined responses throw errors**
   - live `undefined` must fail loudly
   - the test must prove no empty or seed fallback occurred

4. **Malformed payloads handled**
   - malformed required fields or envelopes cause the hook to reject
   - the error reaches consumers as an error state

### 8.1 Required live test files

Task 4 requires the following live test files:

- `apps/atomy-q/WEB/src/hooks/use-rfq.live.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-rfqs.live.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.live.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-quote-submissions.live.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.live.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-normalization-review.live.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-runs.live.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-run.live.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.live.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-comparison-run-readiness.live.test.ts`
- `apps/atomy-q/WEB/src/hooks/use-award.live.test.ts`

`use-rfqs.live.test.ts` already exists and should be updated if needed so it conforms to the same four-case contract as the newly added or extended files.

### 8.2 Page-level test expectations

Task 4 should also add or update component/page tests where needed so the UX distinction is locked in:

- vendors page shows unavailable state on hook failure
- quote-intake list shows unavailable state on submissions or normalization-review failure
- normalize page shows unavailable state on source-lines or normalization-review failure
- comparison-runs list shows unavailable state on hook failure
- award page keeps explicit unavailable state when upstream live hooks fail

These do not replace the hook `.live.test.ts` matrix. They verify the user-visible consequence of the stricter hook contract.

## 9. Documentation Requirements

Task 4 must update the affected WEB documentation after the implementation lands.

Required doc updates:

- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`
- `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md` if verification evidence is recorded there for the release gate

Documentation must clearly communicate:

- mock mode is local/demo behavior only
- live alpha validation requires `NEXT_PUBLIC_USE_MOCKS=false`
- golden-path hooks now fail loudly in live mode by design
- page-level unavailable states are expected and intentional when live dependencies break

## 10. Acceptance Criteria

Task 4 is complete only when all of the following are true:

1. All hooks in scope keep seed/mock behavior behind explicit mock-only branches.
2. No hook in scope returns seed-backed golden-path business data in live mode.
3. All hooks in scope reject `undefined` live responses unless the endpoint contract explicitly allows valid empty data in a proper payload envelope.
4. All hooks in scope reject malformed payloads at normalization time.
5. Every required `.live.test.ts` file exists and covers the four mandatory assertions.
6. The affected RFQ golden-path pages distinguish unavailable live data from successful empty business states.
7. The comparison-run detail page remains the reference fail-loud implementation pattern.
8. Mock mode remains usable where intentionally supported.
9. WEB documentation and alpha release docs describe the new live/mock behavior truthfully.

## 11. Verification

Minimum verification evidence for Task 4:

- `cd apps/atomy-q/WEB && npm run test:unit -- --runInBand`
- targeted Vitest runs for all Task 4 `.live.test.ts` files if the full unit suite is too slow
- targeted page/component tests covering the new unavailable states
- `cd apps/atomy-q/WEB && npm run build`

If Task 4 changes behavior visible in the alpha journey flow, update the release checklist with:

- commands run
- pass/fail status
- any intentionally deferred non-golden-path gaps

## 12. Risks And Non-Goals

### Risks accepted for this task

- live mode will feel stricter and may expose backend or contract gaps that were previously hidden by fallback behavior
- some existing tests or page assumptions may need to be rewritten because they treated fallback behavior as normal

Those risks are acceptable because alpha readiness requires honest failure over silent false confidence.

### Non-goals

- Task 4 does not promise perfect shared abstraction across all hooks.
- Task 4 does not promise that every page in the entire app adopts the same fail-loud pattern immediately.
- Task 4 does not remove mock mode from the repo.
- Task 4 does not change backend availability requirements; it only ensures the WEB reports them truthfully.
