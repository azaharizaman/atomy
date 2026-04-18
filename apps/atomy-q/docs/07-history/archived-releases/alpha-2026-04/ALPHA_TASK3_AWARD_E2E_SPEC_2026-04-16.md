# Alpha Task 3 Award End-To-End Spec

## Document Control

- **Task:** Section 9, Task 3 - Prove Award End-To-End
- **Date:** 2026-04-16
- **Status:** Draft for review
- **Primary plan reference:** [ALPHA_RELEASE_PLAN_2026-04-15.md](./ALPHA_RELEASE_PLAN_2026-04-15.md)

## 1. Purpose

Task 3 closes blocker A2 by proving the final business outcome of the alpha journey: a buyer can move from a frozen final comparison run to a persisted award decision in live mode, then continue the post-decision workflow with debrief and signoff.

The alpha value is not "comparison exists." It is "comparison produced a procurement decision that the tenant can retrieve, review, communicate, and finalize." This task therefore focuses on a single-winner award journey that is narrow, testable, and aligned with the release plan.

## 2. Scope Decision

The primary alpha journey for Task 3 is:

1. a tenant-scoped RFQ has a frozen final comparison run
2. the buyer selects one winning vendor
3. the system creates a persisted award linked to that comparison run
4. the buyer may debrief non-winning vendors as soon as the award exists
5. the buyer may sign off the award
6. the tenant can retrieve the saved award and its decision evidence safely

Split awards are not part of the primary alpha WEB journey for this task. Existing split-award APIs may remain in place, but Task 3 does not require new WEB support or golden-path E2E coverage for line-split workflows.

## 3. Current State

The current codebase already contains substantial award behavior:

- `AwardController` supports list, create, split update, debrief, protest, protest resolve, and signoff endpoints.
- `AwardWorkflowTest` proves several API slices independently.
- `use-award.ts` already exposes award list/query, create, debrief, and signoff mutations.
- `rfqs/[rfqId]/award/page.tsx` already renders a create-award state when no award exists and a final comparison run is available.

What is still missing is conclusive alpha proof that these pieces form one coherent live journey with:

- tenant-safe compare-to-award retrieval semantics
- explicit single-winner WEB behavior as the primary path
- decision-trail persistence for award actions that matter in alpha
- failure handling in live mode instead of passive or mock-only behavior
- an end-to-end verification story spanning API, WEB hook behavior, and E2E

## 4. In Scope

- Prove the single-winner compare-to-award flow from a frozen final comparison run.
- Define the API behavior required to create, retrieve, debrief, and sign off the award safely.
- Define the WEB award page behavior when no award exists but a frozen final comparison run is available.
- Require live-mode failure states for award creation and signoff.
- Require tenant-safe retrieval and cross-tenant non-disclosure behavior.
- Require decision-trail evidence for award actions used in the alpha journey.
- Define the minimum API, hook, and Playwright coverage needed to treat Task 3 as complete.

## 5. Out Of Scope

- Making split awards part of the primary alpha WEB journey.
- Adding new award approval workflows beyond the current signoff semantics.
- Expanding protest handling in WEB for alpha unless already needed by an existing screen outside Task 3.
- Reworking the broader comparison-run feature set beyond what is needed to identify a frozen final run.
- Regenerating the OpenAPI contract unless Task 3 implementation changes request/response shapes.

## 6. Alpha Journey Contract

### 6.1 Prerequisite state

The award creation path is available only when all of the following are true:

- the RFQ belongs to the authenticated tenant
- a final comparison run exists for that RFQ
- the final comparison run is in an alpha-accepted frozen/finalized state
- the selected winning vendor is valid for that tenant and RFQ

If no frozen final comparison run exists, the WEB may explain the missing prerequisite, but it must not fabricate an award-ready state.

### 6.2 Primary create flow

The primary Task 3 journey is a single-vendor award creation flow. The request payload must be anchored on:

- `rfq_id`
- `comparison_run_id`
- `vendor_id`

The API may continue requiring `amount` and `currency` if that is the current contract, but Task 3 implementation must ensure the WEB can supply a valid payload from the final comparison and current RFQ/vendor data without hidden seed behavior.

Task 3 does not require the buyer to allocate line splits in the main journey.

### 6.3 Post-create actions

Once the award exists:

- the award is retrievable through tenant-scoped award reads
- the award page shows the chosen winner and current award status
- the buyer may debrief non-winning vendors immediately
- the buyer may sign off the award

Debrief does not require prior signoff. Signoff finalizes the award status, but it is not a prerequisite for communicating non-winning outcomes in alpha.

## 7. API Requirements

### 7.1 Award creation semantics

`POST /api/v1/awards` must enforce all of the following:

- the RFQ exists in the authenticated tenant
- the referenced comparison run exists in the authenticated tenant
- the comparison run belongs to the same RFQ
- the selected vendor belongs to a quote submission for that RFQ within the same tenant

Cross-tenant or wrong-RFQ references must not leak existence. Task 3 should preserve tenant-safe not-found semantics rather than exposing foreign resource presence.

### 7.2 Award retrieval semantics

`GET /api/v1/awards?rfqId=...` must return only awards from the authenticated tenant. The serialized award must remain sufficient for the WEB alpha page to render:

- RFQ identity
- vendor identity
- award status
- amount and currency
- linked comparison run id
- comparison snapshot vendors needed to identify non-winners
- signoff metadata

Task 3 acceptance requires a fixture that proves tenant A cannot retrieve tenant B's award through RFQ-scoped reads or direct award actions.

### 7.3 Debrief semantics

`POST /api/v1/awards/:id/debrief/:vendorId` must allow debrief as soon as the award exists. It must not require the award to be signed off first.

The debrief target must:

- belong to the same tenant
- be a vendor tied to the RFQ
- be safe to resolve without cross-tenant leakage

Task 3 should preserve idempotent debrief persistence behavior where repeated requests do not create duplicate debrief rows.

### 7.4 Signoff semantics

`POST /api/v1/awards/:id/signoff` must:

- resolve the award within the authenticated tenant
- mark it signed off only once
- preserve the first signoff timestamp and actor on repeated calls
- return a live response the WEB can use after success or safe failure

### 7.5 Failure semantics

Task 3 must define explicit live failure behavior for at least:

- missing or non-final comparison run
- invalid vendor selection
- missing award
- cross-tenant resource access
- award create failure
- award signoff failure

Failures must be surfaced honestly to WEB consumers. Live-mode failures must not degrade into seed success, silent no-op behavior, or generic empty states that imply progress.

## 8. WEB Requirements

### 8.1 No-award state

If no award exists and a frozen final comparison run is available, the award page must present the create-award path. The screen must:

- explain that the buyer can create an award from the final comparison run
- allow selection of one winning vendor
- submit the create-award mutation with the current RFQ and final comparison run
- show a live failure message if creation fails

If no final comparison run exists, the screen may show prerequisite guidance only.

### 8.2 Candidate vendor source

The primary candidate list is derived from the comparison snapshot vendors (vendors with priced offers in the frozen final comparison run). This provides a better user experience by showing only vendors who are eligible for award selection, rather than the full RFQ vendor roster which may include vendors who have not yet submitted quotes.

The linked comparison snapshot remains the evidence context for the decision and the source for identifying non-winning vendors after creation.

> **Design note (2026-04-17):** Intentionally deriving the candidate list from the comparison snapshot rather than the RFQ vendor roster was made to improve user experience. Users see only vendors who have submitted quotes and can be awarded, avoiding confusion from seeing invited vendors who have not yet participated.

Task 3 does not require a new split-allocation picker or per-line winner assignment UI.

### 8.3 Award-present state

Once an award exists, the page must show:

- winning vendor identity
- amount and currency
- award status
- signoff status
- non-winning vendors derived from the comparison snapshot
- debrief actions for those non-winning vendors

The page should remain truthful in live mode. If live data is malformed or missing where the contract requires it, the user should see an error path rather than fabricated content.

### 8.4 Live-mode failure states

Task 3 must explicitly cover failure UI for:

- create award mutation failure
- signoff mutation failure

At minimum, the page must show a visible error message scoped to the failed action and keep the user in a recoverable state.

## 9. Decision-Trail Requirements

Task 3 requires award-related decision evidence to be persisted for the alpha journey, not only rendered transiently.

Minimum evidence expectations:

- award creation must be traceable to the frozen comparison run used for the decision
- award debrief actions must persist decision-trail evidence
- signoff should be captured as decision evidence if the current recorder and schema support it; if not, Task 3 implementation must either add it or document the exact accepted gap and follow-up owner before completion

The goal is that the alpha trail can answer: which comparison run produced this award, which vendor won, and which post-decision communications were recorded.

## 10. Test And Verification Requirements

### 10.1 API feature coverage

`apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php` should include a conclusive fixture path that proves:

1. a frozen final comparison run exists for the RFQ
2. an award can be created for the selected winning vendor
3. the created award is tenant-scoped on retrieval
4. a non-winning vendor can be debriefed immediately after award creation
5. the award can be signed off
6. decision-trail evidence exists for the award journey actions covered by alpha

This may extend the current fixture rather than creating a separate test class, but the final API evidence must read as one coherent workflow, not only isolated endpoint checks.

### 10.2 WEB hook coverage

`apps/atomy-q/WEB/src/hooks/use-award.live.test.ts` should prove:

- live award reads fail loudly on invalid API responses
- create award mutation uses live API shape, not seed fallback
- signoff mutation failure is surfaced to consumers

If the hook contract already exposes the needed mutation state, the test should lock that behavior rather than adding alternate paths.

### 10.3 Playwright coverage

`apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts` should prove the alpha golden path through award:

1. reach an RFQ with a frozen final comparison run
2. open the award page with no pre-existing award
3. create the award for one selected vendor
4. debrief a non-winning vendor
5. sign off the award
6. confirm the resulting award state is visible in the UI

The Task 3 E2E path should stay single-winner and live-mode oriented. It does not need to branch into split-award or protest workflows.

## 11. Documentation Requirements

Task 3 implementation must update:

- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` if WEB award behavior changes materially
- `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md` with the new award proof evidence once verification is run

The active alpha plan should remain linked to this Task 3 spec so future work does not lose the approved scope boundary.

## 12. Acceptance Criteria

Task 3 is complete only when all of the following are true:

1. The primary alpha award journey is single-winner from a frozen final comparison run.
2. The WEB award page supports create-award behavior when no award exists and a frozen final comparison run is available.
3. Buyers can debrief non-winning vendors as soon as the award exists.
4. Buyers can sign off the award after creation.
5. Award retrieval and actions remain tenant-safe and do not leak cross-tenant resource existence.
6. Decision-trail evidence is persisted for the award journey actions required by alpha.
7. Live-mode create/signoff failures are surfaced in the WEB UI.
8. API, WEB live hook, and Playwright coverage together prove the compare-to-award alpha journey end to end.

## 13. Deferred Items

The following are explicitly deferred beyond Task 3 unless later release-plan tasks pull them forward:

- split-award WEB journey
- richer award approval chains beyond current signoff
- protest/resolve WEB closure
- broader procurement downstream artifacts not needed for alpha proof
