# Atomy-Q Sourcing Recommendation Target-State Design

**Date:** 2026-04-27
**Status:** Approved design
**Scope:** Canonical provider-backed sourcing recommendation for the RFQ vendors workflow, authoritative manual shortlist continuity, removal of non-canonical recommendation surfaces, and real-provider end-to-end proof.

## Purpose

Atomy-Q's sourcing recommendation must become a clear product capability rather than a mix of partially implemented APIs, advisory UI, and long-lived placeholder routes.

This design defines the target state for the sourcing recommendation slice as a selling-point capability:

- one canonical buyer workflow,
- one canonical AI recommendation API path,
- one canonical buyer-authoritative shortlist path,
- no legacy or ambiguous recommendation surfaces,
- explicit real-provider verification before the capability is considered release-ready.

The goal is not to automate vendor choice.

The goal is to let Atomy-Q generate provider-backed shortlist recommendations for an RFQ, explain why, preserve manual buyer control, and remain truthful when AI is unavailable.

## Decision

Alpha sourcing recommendation will use **provider-backed advisory ranking with manual shortlist authority**.

That means:

- the RFQ vendors workspace is the canonical product surface for sourcing recommendation,
- provider-backed recommendation is advisory only,
- buyer shortlist selection remains authoritative,
- recommendation output must never silently mutate the shortlist,
- manual shortlist completion remains fully usable when AI is off, degraded, unavailable, or rejected,
- any endpoint, route, generated client surface, hook, or UI path that represents a non-canonical recommendation workflow and will not be developed further must be removed altogether.

## Product Boundary

### Canonical Product Surface

The sourcing recommendation capability exists in one place:

- RFQ vendors workspace in WEB,
- RFQ-scoped recommendation generation in API,
- RFQ-scoped selected-vendors persistence in API.

The target product claim is:

1. Atomy-Q can generate a provider-backed vendor shortlist recommendation for an RFQ.
2. Buyers can inspect rationale, exclusions, warnings, and provenance.
3. Buyers remain fully in control of the final shortlist.
4. If AI is unavailable, the workflow continues manually without fabricated recommendation output.

### Non-Canonical Surface Removal

The target state must not keep recommendation-specific surfaces that are not part of the canonical RFQ vendors workflow.

This includes removal of:

- legacy `/api/v1/recommendations/*` endpoints,
- controller actions that only return structured unavailability for recommendation flows that will not be developed,
- generated API client operations for removed recommendation routes,
- dormant hooks or components tied to removed recommendation APIs,
- documentation that implies a second recommendation workflow exists,
- tests that preserve removed recommendation surfaces as if they were future-supported.

The system should not preserve ambiguity by keeping stubbed recommendation routes alive indefinitely.

## Product Workflow

### Buyer Journey

The canonical buyer journey is:

1. buyer opens an RFQ vendors workspace,
2. system loads tenant-scoped approved vendor candidates and current selected-vendors state,
3. buyer triggers or loads provider-backed recommendation for that RFQ,
4. system renders ranked candidates, exclusions, rationale, warnings, and provenance,
5. buyer reviews recommendation without any automatic shortlist mutation,
6. buyer manually selects or deselects vendors for the shortlist,
7. buyer saves the shortlist through the authoritative selected-vendors mutation,
8. audit and decision trail preserve the recommendation artifact separately from the buyer shortlist decision.

### Manual Continuity

If sourcing recommendation is disabled, degraded, unavailable, or rejected by deterministic guards:

- the UI must say that clearly at the recommendation capability level,
- no synthetic recommendation output may be shown,
- the buyer must still be able to search approved vendors,
- the buyer must still be able to select vendors manually,
- the buyer must still be able to persist the shortlist,
- authoritative shortlisted vendors must not depend on recommendation success.

The system must not blur:

- `AI unavailable`,
- `AI returned zero eligible candidates`,
- `buyer has not yet selected vendors`.

These are different product states and must stay different in the API and UI.

## Domain Model

### Core Rule

Recommendation generation and shortlist mutation are separate actions with separate ownership.

Recommendation generation produces an advisory artifact.

Shortlist mutation produces authoritative workflow state.

### Recommendation Artifact

The recommendation artifact must represent:

- RFQ identity,
- tenant identity,
- recommendation status,
- eligible candidates,
- excluded candidates,
- provider explanation,
- deterministic reason set,
- provenance.

It must not represent final vendor selection.

### Authoritative Shortlist

The authoritative shortlist is the buyer-confirmed vendor selection for the RFQ.

It must remain:

- tenant-scoped,
- RFQ-scoped,
- explicit,
- separate from provider output,
- fully queryable without replaying recommendation artifacts.

### Provenance Boundary

The design requires durable separation between:

- provider-generated recommendation evidence,
- buyer-authored shortlist changes.

The system must be able to answer:

- what the provider recommended,
- why the provider recommended it,
- which deterministic guards influenced the result,
- what the buyer ultimately selected,
- when those two actions occurred,
- who performed the authoritative shortlist change.

## API Design

### Canonical Endpoints

The canonical recommendation API surface is:

- `POST /api/v1/rfqs/{rfqId}/vendor-recommendations`
- `GET /api/v1/rfqs/{rfqId}/selected-vendors`
- `PUT /api/v1/rfqs/{rfqId}/selected-vendors`

These are the only recommendation-related alpha endpoints that should remain after cleanup for this slice.

### Removed Endpoints

The following endpoints must be removed rather than preserved as future stubs:

- `GET /api/v1/recommendations/{runId}`
- `GET /api/v1/recommendations/{runId}/mcda`
- `POST /api/v1/recommendations/{runId}/override`
- `POST /api/v1/recommendations/{runId}/rerun`

Removing them is part of the target state, not optional cleanup.

### Recommendation Request Flow

Recommendation generation must be:

- tenant-scoped at the query root,
- RFQ-scoped,
- based on deterministic candidate gathering before provider invocation,
- bounded to approved same-tenant vendor candidates only,
- validated through deterministic post-provider guards before response.

The provider request payload should include bounded context such as:

- `tenant_id`
- `rfq_id`
- categories
- description
- geography
- spend band
- line-item summary
- eligible candidates with deterministic metadata

The design does not require exposing provider-specific payload shape above the API adapter boundary.

### Recommendation Response Contract

The canonical response contract should be one stable shape:

- `status`
- `eligible_candidates`
- `excluded_candidates`
- `provider_explanation`
- `deterministic_reason_set`
- `provenance`

Once the WEB client is aligned, duplicate compatibility aliases should be removed.

The response contract must keep these states distinct:

- recommendation available with ranked candidates,
- recommendation available with zero eligible candidates,
- recommendation unavailable.

### Deterministic Guard Rules

Deterministic eligibility and validation remain authoritative even when provider ranking is enabled.

The system must reject or suppress provider output that:

- introduces unknown vendors,
- crosses tenant boundaries,
- includes vendors not eligible under business rules,
- omits required structure,
- cannot be trusted as a well-formed ranked result.

The system must not silently replace invalid provider output with a fake deterministic recommendation while presenting the feature as provider-backed AI.

### Shortlist Mutation Boundary

The shortlist mutation boundary remains `PUT /selected-vendors`.

That boundary must:

- require a non-empty, distinct set of same-tenant approved vendor ids,
- replace the shortlist atomically,
- remain usable regardless of recommendation capability status,
- write authoritative audit evidence for buyer selection separately from AI recommendation generation.

### Failure Semantics

The API must be truthful:

- unavailable AI returns structured unavailable response for the recommendation feature,
- zero candidates returns a valid recommendation response with provenance,
- malformed or rejected provider output returns unavailable or rejected recommendation state, not partial corrupt success,
- shortlist APIs remain available even when recommendation is unavailable.

## Persistence And Audit

### Recommendation Evidence

Recommendation provenance should be durably persisted in a way that remains reviewable after response-time availability changes.

At minimum, persisted recommendation evidence must allow operators and reviewers to inspect:

- feature key,
- artifact origin,
- provider name,
- endpoint group,
- generated-at timestamp,
- request/output trace metadata that is safe to persist,
- ranked recommendation artifact or explicit zero-candidate artifact,
- reason codes for unavailable or rejected generation when relevant.

### Buyer Selection Evidence

Buyer shortlist changes must write distinct authoritative evidence from the recommendation artifact.

The persisted record for buyer selection must not be inferred from recommendation data.

Recommendation evidence and shortlist evidence must be independently queryable in the decision trail.

### Current-Value Queryability

The system must allow the WEB app to read:

- current recommendation result for an RFQ when available,
- current selected-vendors state for an RFQ,

without forcing WEB to reconstruct business state from mixed historical events.

## WEB Design

### Canonical Surface

The RFQ vendors page is the only buyer-facing sourcing recommendation workspace in alpha.

There should not be a second recommendation screen, alternate recommendation route, or hidden recommendation workflow for this slice.

### Screen Structure

The page should separate:

- recommendation summary,
- recommended candidate details,
- exclusions and warnings,
- manual shortlist state,
- shortlist save action.

Recommendation must remain visually advisory.

Shortlist state must remain visually authoritative.

### Interaction Rules

The page must not:

- auto-select recommended vendors,
- silently copy recommendation results into shortlist state,
- imply that recommended means already shortlisted,
- fabricate recommendation UI when recommendation is unavailable.

The page must allow:

- reading recommendation rationale,
- inspecting deterministic reasons and warnings,
- manual shortlist selection,
- shortlist save without recommendation availability.

### Unavailable And Zero-Candidate UX

If recommendation is unavailable:

- show one capability-scoped unavailable state,
- keep shortlist controls available,
- remove or disable recommendation-only affordances,
- avoid raw transport/provider error leakage in buyer-facing copy.

If recommendation returns zero eligible candidates:

- show that as a valid recommendation outcome,
- do not present it as outage,
- keep shortlist controls available.

### Provenance UX

WEB should expose concise recommendation provenance in-page and deeper reviewability through decision trail.

Buyers should be able to tell:

- what came from provider-backed AI,
- what came from deterministic screening,
- what they themselves selected.

## Documentation And Surface Cleanup

The target state requires synchronized cleanup across:

- API routes,
- controller actions,
- OpenAPI,
- generated WEB client,
- hooks,
- page components,
- implementation summaries,
- domain docs,
- test suites.

After cleanup, docs must not imply:

- a second recommendation workflow exists,
- removed endpoints remain part of the alpha surface,
- recommendation authority and shortlist authority are the same thing.

## Verification Strategy

### Required Test Layers

This slice is not selling-point ready unless it is proven across all of the following:

1. unit tests,
2. API feature tests,
3. WEB unit tests,
4. real-provider end-to-end tests.

### Unit Coverage

Unit coverage must prove:

- deterministic scoring bounds,
- provider ranking integration rules,
- rejection of invalid provider output,
- zero-candidate valid output handling,
- payload normalization and provenance handling.

### API Feature Coverage

API feature coverage must prove:

- tenant scoping,
- RFQ scoping,
- approved-vendor filtering,
- truthful unavailable response behavior,
- valid zero-candidate response behavior,
- shortlist continuity under AI unavailability,
- decision-trail separation between recommendation generation and buyer shortlist change.

### WEB Unit Coverage

WEB coverage must prove:

- recommendation rendering,
- unavailable-state rendering,
- zero-candidate rendering,
- manual shortlist continuity,
- no auto-selection from AI recommendation,
- clear separation between advisory AI output and authoritative shortlist state.

### Required Real-Provider E2E

Real-provider E2E is mandatory for this slice.

At minimum, the suite must prove:

1. **recommendation success path**
   - live API mode,
   - real provider-backed recommendation generation,
   - visible recommendation output on RFQ vendors page,
   - no automatic shortlist mutation,
   - manual shortlist save succeeds.

2. **recommendation unavailable continuity path**
   - truthful unavailable recommendation state,
   - no fabricated recommendation output,
   - manual shortlist remains fully usable.

3. **zero-candidate valid result path**
   - recommendation returns valid empty candidate result,
   - UI distinguishes that from outage,
   - manual shortlist remains fully usable.

4. **decision-trail proof path**
   - recommendation artifact is durably reviewable,
   - buyer shortlist change is durably reviewable,
   - they are stored as distinct events.

### Removal Verification

Verification must also prove:

- `/recommendations/*` routes are gone,
- removed recommendation operations are absent from OpenAPI and generated client output,
- removed recommendation hooks/components are gone,
- no tests preserve removed recommendation workflows as if they were still supported.

## Out Of Scope

This design does not cover:

- comparison overlay,
- award guidance,
- award debrief drafting,
- approval AI summary,
- multi-provider failover,
- per-tenant provider selection,
- autonomous shortlist mutation,
- post-alpha recommendation reuse outside the RFQ vendors workflow.

## Acceptance Gates

- one canonical sourcing recommendation workflow exists: RFQ vendors workspace,
- one canonical AI generation endpoint exists: `POST /rfqs/{rfqId}/vendor-recommendations`,
- one canonical authoritative shortlist workflow exists: `selected-vendors`,
- legacy `/recommendations/*` endpoints and adjacent dead surfaces are removed,
- recommendation is genuinely provider-backed when available,
- recommendation never mutates shortlist automatically,
- manual shortlist remains fully usable when AI is off, degraded, unavailable, or rejected,
- zero-candidate recommendation and unavailable recommendation remain distinct states,
- recommendation provenance and buyer shortlist action are durably auditable as separate events,
- at least one real-provider E2E test proves the sourcing recommendation flow end-to-end in live mode.
