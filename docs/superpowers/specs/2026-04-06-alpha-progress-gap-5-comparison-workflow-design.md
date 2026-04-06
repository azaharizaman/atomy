# Alpha Progress Gap 5 Design

Date: 2026-04-06
Scope: Atomy-Q alpha gap 5, comparison preview / matrix / readiness workflow
Author: Codex
Status: Draft

## Context

The alpha progress analysis identifies comparison preview, matrix, and readiness as one of the remaining blockers to a credible Alpha release.

Relevant current state:

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`
  - `POST /comparison-runs/preview` still returns a synthetic `uniqid()` response
  - `GET /comparison-runs/{id}/matrix` returns an empty matrix shell
  - `GET /comparison-runs/{id}/readiness` returns a hardcoded `ready: false`
  - `POST /comparison-runs/{id}/lock` and `unlock` are placeholder toggles
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx`
  - renders a static comparison matrix and recommendation
  - does not read live run state
- `apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts`
  - already reads live comparison run list data
- `apps/atomy-q/WEB/src/hooks/use-comparison-readiness.ts`
  - already computes readiness from live normalization and overview state
  - `orchestrators/Nexus\QuotationIntelligence`
  - already contains the right domain abstractions:
    - `Nexus\QuotationIntelligence\Coordinators\BatchQuoteComparisonCoordinator`
    - `Nexus\QuotationIntelligence\Contracts\ComparisonReadinessValidatorInterface`
    - `Nexus\QuotationIntelligence\Contracts\QuoteComparisonMatrixServiceInterface`
    - `Nexus\QuotationIntelligence\Contracts\ComparisonReadinessResultInterface`

This means the gap is not "invent comparison logic." The gap is to promote the existing Nexus comparison stack into the Atomy-Q alpha surface in a way that is honest, tenant-safe, and useful.

References:

- `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`
- `apps/atomy-q/docs/alpha-audit-v1.0.0/ALPHA_RELEASE_AUDIT.md`
- `orchestrators/Nexus\QuotationIntelligence/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

## Problem

Atomy-Q can already freeze a final comparison snapshot, but the surrounding preview/readiness/matrix workflow still looks more complete than it is.

That creates three alpha risks:

1. Users can enter a comparison area and see fake state.
2. The app layer is not clearly demonstrating the value of first-party Nexus comparison packages.
3. Later beta controls are likely to be forgotten if they are not documented now.

## Goals

- Replace the fake comparison preview/readiness/matrix surfaces with live, tenant-scoped behavior.
- Keep the alpha path minimal and honest, not feature-rich for its own sake.
- Reuse `Nexus\QuotationIntelligence` as the comparison computation boundary.
- Keep Layer 3 responsible for Laravel request handling, persistence, and JSON shaping.
- Preserve a visible backlog of beta-only comparison enhancements so they are not lost.
- Demonstrate the usefulness of Nexus packages in a real SaaS path, not just in isolated package tests.

## Non-goals

- Full editor-grade comparison design tools
- Advanced manual scoring-model management
- Editable lock/unlock UX beyond what alpha requires
- Recommendation override workflows
- Multi-run diffing and replay UX
- Comparison clustering visualization beyond a useful matrix view
- Building a new comparison engine in the app layer

## Options Considered

### Option 1: Keep comparison logic app-local

Make the Laravel controller and React pages responsible for preview, matrix, and readiness without using the orchestrator contracts directly.

Pros:

- Fastest to implement
- Least coordination with package code

Cons:

- Weak demonstration of Nexus package value
- Encourages duplicated business logic
- Makes beta evolution harder because the comparison boundary stays embedded in the app

### Option 2: Minimal honest alpha on top of `Nexus\QuotationIntelligence`

Use the existing orchestrator stack for preview and readiness, then let the app persist and render the result in a simple, live workflow.

Pros:

- Best fit for the Alpha goal
- Demonstrates first-party Nexus packages in a real SaaS flow
- Keeps comparison rules reusable across future products
- Lets us defer richer beta controls without hiding them

Cons:

- Requires adapter cleanup and response-shape alignment
- Some UI polish stays intentionally out of scope

### Option 3: Full comparison workstation now

Build the entire comparison experience, including editable scoring, lock state management, matrix drilldowns, and comparison versioning in the same pass.

Pros:

- Richest UX
- Reduces the need for follow-up comparison work later

Cons:

- Over-scopes alpha
- Risks diluting the package-first demonstration with too much UI polish
- Slows delivery of the honest live path

## Recommendation

Choose Option 2.

The alpha release should show a real comparison workflow that is live enough to trust, while keeping all richer comparison controls documented for beta. This keeps the work aligned with the stated purpose of the project:

- release Alpha SaaS
- prioritize first-party Nexus packages
- improve the packages while using them in a real app

## Proposed Architecture

### Layer 1

Keep comparison calculation and readiness semantics in `Nexus\QuotationIntelligence`.

The comparison engine should remain the source of truth for:

- preview comparison execution
- final comparison validation
- readiness blockers and warnings
- matrix generation

If a missing contract or result type blocks proper app integration, add it in the package rather than reimplementing logic in the app.

### Layer 2

Use the orchestrator boundary to coordinate comparison execution and normalize results for the app.

The coordinator should expose or already expose:

- preview comparison
- final comparison
- readiness result
- matrix payload
- optional decision-trail write behavior for final freeze

If current package contracts are too narrow for the alpha path, extend them in a package-first way instead of hardcoding behavior in the Laravel controller.

### Layer 3

Atomy-Q Laravel should:

- validate HTTP input
- extract tenant/user context
- call the comparison orchestrator
- persist the frozen run state when needed
- map live results into stable JSON responses
- render the React surface from live API data

## Alpha Scope

The alpha scope is intentionally narrow.

### Live comparison preview

- `POST /comparison-runs/preview` must stop returning a synthetic ID.
- The endpoint should call the Nexus comparison stack and return a real preview result or a clear not-ready response.
- If preview is not yet fully persisted, the response must still be honest about that state.

### Live readiness

- `GET /comparison-runs/{id}/readiness` must return actual readiness information, not a placeholder.
- Readiness should reflect:
  - RFQ state
  - quote submission readiness
  - normalization blocking issues
  - any package-level blockers from the comparison engine

### Live matrix read path

- `GET /comparison-runs/{id}/matrix` should expose the matrix data associated with the run or frozen snapshot.
- The response should not fabricate vendor rows or comparison values.

### Run detail page

- The comparison-run detail page should render the live matrix and live readiness state.
- Any recommendation shown on the page must come from real run data or be explicitly labeled as unavailable.

### Run list page

- The comparison list page already has enough live data to stay simple.
- Keep the snapshot-frozen banner only when a real final run exists.

## Deferred Beta Work

This section is required so the alpha implementation does not erase future comparison work.

The following features are intentionally deferred and must remain documented in this spec and in the implementation summaries:

- editable scoring-model selection from the comparison UI
- lock and unlock controls with meaningful persisted state
- manual scoring overrides
- recommendation override workflow
- richer matrix drilldowns by vendor and line item
- comparison clustering and grouping visualization
- preview-to-final diffing between runs
- run versioning and comparison replay UX
- comparison-specific audit trail exploration
- bulk comparison regeneration controls

Beta rule:

- if a feature changes comparison interpretation, it must either be part of the orchestrator contract or explicitly documented as deferred
- if a feature is only visual polish, it should not block alpha

## Data Flow

1. User opens the RFQ comparison runs area.
2. The UI fetches run list, readiness, and run detail data.
3. The Laravel controller resolves tenant-scoped run/RFQ state.
4. The controller delegates preview or readiness work to `Nexus\QuotationIntelligence`.
5. The orchestrator returns a real readiness result and matrix structure.
6. For final freeze, the app persists the run and decision-trail state.
7. The UI renders live data and disables fake controls.

## Error Handling

- Respond with `404` when the RFQ or comparison run is missing or belongs to another tenant.
- Use `422` when readiness blocks preview or freeze.
- Use `500` for unexpected package or persistence failures.
- Do not synthesize a preview id, matrix rows, or readiness state when the underlying comparison cannot run.

The response should always make it obvious whether a failure is:

- missing resource
- readiness blocker
- unsupported beta-only control
- infrastructure/runtime failure

## Persistence Rules

- Final comparison runs should continue to be persisted as the audit/freeze anchor.
- Preview behavior should remain honest about whether it is persisted.
- The app should not persist fake data just to satisfy a UI widget.
- Tenant scoping is mandatory on all reads and writes.
- Existing freeze and decision-trail persistence should remain the stable alpha anchor for downstream award and approval screens.

## Testing Strategy

### API tests

Cover:

- preview response shape is real and not synthetic
- readiness blocks on incomplete normalization
- matrix endpoint returns live data for an existing run
- final freeze persists snapshot state and decision trail
- wrong-tenant access returns `404`

### WEB tests

Cover:

- comparison list shows real frozen-run status when present
- comparison detail no longer renders a static vendor table
- readiness and freeze states reflect live API data
- mock-mode branches stay available, but live mode no longer depends on seed-only data

### Package regression tests

If the orchestrator contracts need extension, add tests there first or in parallel:

- `Nexus\QuotationIntelligence\Coordinators\BatchQuoteComparisonCoordinator`
- `Nexus\QuotationIntelligence\Contracts\ComparisonReadinessValidatorInterface`
- matrix generation
- preview/final distinction

## Documentation Updates

This spec should stay cross-linked with:

- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/WEB/BACKEND_API_GAPS.md`
- `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`

Each of those docs should be updated to:

- mark gap 5 as closed once the live path is done
- record the beta comparison backlog section
- avoid reintroducing static placeholder matrix/readiness behavior

## Rollout Notes

- Preserve mock mode for local/demo use.
- Keep the live comparison path tenant-safe and explicit.
- If a live preview is not ready yet, fail honestly instead of echoing a fake success.
- Prefer package-first fixes over controller-local shortcuts whenever the comparison stack needs new behavior.

## Acceptance Criteria

- The comparison preview path is live and no longer synthetic.
- Matrix and readiness paths return real data or explicit not-ready errors.
- The comparison detail page shows live state.
- The alpha docs record the deferred beta comparison work.
- The implementation demonstrates the value of first-party Nexus packages in a real SaaS workflow.
