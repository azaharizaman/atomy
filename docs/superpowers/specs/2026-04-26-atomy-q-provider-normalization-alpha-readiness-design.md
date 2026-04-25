# Atomy-Q Provider Normalization Alpha Readiness Design

**Date:** 2026-04-26
**Status:** Approved design
**Scope:** Provider-first normalization workflow, audited buyer overrides, and conclusive provider-mode E2E evidence for alpha release.

## Purpose

Atomy-Q alpha is moving from a deterministic normalization posture to a supported `AI_MODE=provider` release posture for the normalization workflow.

This design defines the minimum product, audit, API, WEB, and verification changes required to make provider-backed normalization releasable for alpha without breaking manual buyer continuity.

The goal is not to remove manual handling. The goal is to make provider-backed normalization the supported primary path while keeping manual completion truthful, usable, and fully auditable.

## Decision

Alpha normalization will use **provider-first with manual buyer continuity**.

That means:

- `AI_MODE=provider` is the intended alpha runtime for normalization.
- Provider-generated source lines and mapping suggestions are the primary path.
- Buyers may still add, edit, remap, or override normalization data manually.
- Manual continuity is part of the supported workflow, not a hidden fallback.
- Any buyer override must be logged with actor, timestamp, before/after values, structured justification, and the provider confidence visible at the time of override.
- Alpha release evidence must prove both the provider-backed golden path and the provider-degraded manual continuity path.

## Current Gap

The repo already proves part of the provider path:

- provider-backed quote upload can create source lines,
- quote intake can surface provider-backed extraction state,
- manual normalization controls exist,
- capability-scoped unavailable messaging exists.

The release gap is that normalization is still documented and scoped as deterministic/non-LLM in active domain docs, and the current Playwright provider coverage stops short of full normalization closure.

The missing alpha-safe proof is:

- provider suggestion and confidence visibility inside the normalize workspace,
- mandatory audited override submission,
- readiness recalculation after audited override,
- successful comparison freeze after provider-assisted or manual continuity normalization,
- truthful degraded-provider behavior that still lets buyers finish manually.

## Product Posture

### Supported Alpha Path

The supported normalization journey under `AI_MODE=provider` is:

1. buyer uploads a quote,
2. provider-backed extraction persists source lines and suggestion metadata,
3. buyer opens the normalize workspace,
4. buyer reviews provider-suggested mappings and confidence signals,
5. buyer accepts, remaps, or overrides line data as needed,
6. every manual override is captured as an auditable event,
7. readiness clears only when effective normalized data satisfies comparison requirements,
8. comparison freeze succeeds using the effective normalized state.

### Manual Continuity

If provider normalization is degraded, unavailable, or incomplete:

- the UI must say that clearly at the capability level,
- already persisted source lines remain visible,
- manual source-line CRUD and mapping remain available,
- override audit requirements still apply,
- readiness and comparison behavior remain honest and usable.

The system must not fabricate provider success, synthetic AI recommendations, or hidden deterministic substitutions while presenting itself as provider-backed normalization.

## Workflow Design

### Provider-Origin Flow

Provider-backed normalization must persist enough information for the buyer and operator to answer:

- what did the provider suggest,
- how confident was it,
- when was it generated,
- which provider/model/runtime generated it,
- what value is currently effective after buyer review.

At minimum, provider-origin normalization records must expose:

- source line identity and quote submission identity,
- suggested RFQ line mapping,
- suggested normalized commercial fields when present,
- field-level or line-level confidence,
- provider/model/provenance metadata already available from the provider runtime,
- generated-at timestamp.

### Buyer Override Flow

Buyer overrides are permitted in alpha, but every override must go through a single audited mutation boundary.

An override is any buyer-authored change that alters the effective normalized value used by readiness or comparison, including:

- remapping a source line to a different RFQ line,
- changing buyer-approved normalized quantity, UOM, description, or unit price,
- replacing a provider suggestion with a manual value,
- entering or editing a source line when provider assistance is unavailable or insufficient.

The workflow for an override is:

1. buyer edits a value or mapping,
2. UI requires a reason code before submission,
3. UI may also accept an optional free-text note,
4. API validates tenant, RFQ, quote submission, and field scope,
5. API records an append-only override event,
6. API updates the effective normalized state,
7. readiness is recalculated,
8. response returns both effective state and audit-ready metadata.

## Audit And Provenance Model

### Core Rule

Normalization must preserve both:

- the **effective value** used by the business workflow,
- the **history of how that value came to be**.

The effective value supports comparison and readiness.
The provenance trail supports audit, operator investigation, and alpha release truthfulness.

### Required Override Audit Payload

Every override event must record:

- `tenant_id`
- `rfq_id`
- `quote_submission_id`
- target entity id
- target field name
- previous effective value
- new effective value
- provider-suggested value, if one existed
- provider confidence visible at the time of override
- provider provenance snapshot sufficient to understand the suggestion context
- override reason code
- optional override note
- actor user id
- actor display name or equivalent human-readable identity
- occurred-at timestamp

### Reason Codes

Alpha must require a structured reason code for each override.

The first release can use a bounded code set such as:

- `supplier_document_mismatch`
- `rfq_mapping_incorrect`
- `quantity_or_uom_correction`
- `price_correction`
- `manual_entry_required`
- `other`

If `other` is selected, the API should require a non-empty note.

### Storage Shape

The design does not require a brand-new domain if the current persistence model can support these rules cleanly, but the shipped implementation must satisfy all of the following:

- override history is append-only,
- current effective values are queryable without replaying the full history in WEB,
- provider-origin values are not destroyed when an override occurs,
- tenant/RFQ/query scoping is enforced at the query root,
- decision-trail or adjacent audit reads can surface normalization override events by RFQ.

## API Requirements

### Mutation Boundary

Manual normalization writes must be routed through a dedicated audited API boundary rather than scattered direct updates.

That boundary must:

- validate override reason code requirements,
- capture actor metadata from the authenticated request,
- capture provider confidence/provenance snapshot from the currently visible suggestion context,
- write the override event and effective value update in one transaction,
- trigger readiness recalculation before success is returned.

### Response Contract

Normalization API responses used by the normalize workspace must expose enough shape for WEB to render:

- provider suggestion state,
- provider confidence,
- whether the effective value is provider-derived or buyer-overridden,
- override reason and timestamp when the current value is overridden,
- readiness impact after the latest mutation.

### Failure Rules

The provider-backed normalization path must fail loudly and truthfully:

- no silent fallback from provider path to synthetic success,
- no raw upstream/provider exception text in user-visible payloads,
- no loss of manual continuity controls when provider capability is unavailable,
- no success response unless effective state and audit writes both commit.

## WEB Requirements

### Normalize Workspace

The normalize workspace must visibly distinguish:

- provider-suggested values,
- buyer-overridden values,
- unavailable/degraded provider state,
- readiness blockers that still prevent comparison freeze.

At minimum, the page must render:

- provider confidence for relevant suggestions,
- a visual marker when a value has been overridden by a buyer,
- override reason capture before submit,
- capability-scoped unavailable messaging when provider normalization is degraded or unavailable,
- manual controls that remain usable in the degraded path.

### Override UX

When a buyer overrides a mapped field or value:

- the form must require a reason code,
- optional note entry must be available,
- the current provider confidence must remain visible at the point of decision,
- the page must confirm that the effective value changed,
- the page must show that the value is now buyer-overridden.

### Truthful Degradation

If provider normalization is unavailable:

- the page must not claim AI assistance is active,
- the page must not disable manual normalization completion,
- the page must not collapse into a generic full-page error unless live normalization data itself is unavailable.

## Conclusive E2E Bar

Alpha requires two normalization E2E suites for `AI_MODE=provider`.

### 1. Provider Golden Path

This suite must run with `NEXT_PUBLIC_USE_MOCKS=false` and prove:

1. login succeeds,
2. RFQ is created,
3. RFQ line items are created,
4. quote upload succeeds against the provider-backed path,
5. provider-generated source lines become available,
6. normalize workspace shows provider suggestion/confidence state,
7. buyer applies at least one manual override with required reason code,
8. override persists and is visibly marked as buyer-overridden,
9. readiness clears,
10. comparison freeze succeeds.

This is the minimum definition of “conclusive” for provider-backed normalization in alpha.

### 2. Provider-Degraded Manual Continuity Path

This suite must prove:

1. provider normalization is unavailable or degraded in a truthful way,
2. normalize workspace shows capability-scoped unavailable messaging,
3. buyer can still add, edit, or map source lines manually,
4. buyer override reason capture still applies,
5. readiness recalculates from manual/effective values,
6. comparison freeze still succeeds without fabricated provider success.

### Live Evidence Requirement

At least one provider normalization E2E path must exercise the actual provider-backed flow rather than only browser-mocked routes.

Mocked-browser Playwright remains useful for stability and breadth, but release evidence must include a real provider-backed proof that goes beyond upload-to-source-lines and reaches normalization closure.

## Recommended Test Split

### API

- feature tests for audited override writes,
- feature tests for provider suggestion/confidence persistence,
- feature tests for readiness recalculation after override,
- feature tests for degraded-provider manual continuity behavior,
- feature tests for decision-trail or audit retrieval of normalization overrides.

### WEB Unit/Integration

- normalize workspace renders provider confidence and override status,
- override form requires reason code,
- buyer-overridden lines render differently from provider-suggested lines,
- degraded provider state preserves manual controls,
- freeze gating reflects updated readiness after override.

### Playwright

- one mocked/fake-provider normalization journey for stable CI,
- one opt-in real-provider normalization journey for release evidence.

## Alpha Release Impact

This design changes the alpha release standard in three specific ways:

- `normalization_intelligence` cannot be considered release-ready based on upload-to-source-lines alone,
- audited buyer override capability becomes part of the supported workflow, not a hidden operator detail,
- the alpha release handoff and verification matrix must treat provider normalization closure as a blocking gate for `AI_MODE=provider`.

## Out Of Scope

- multi-provider failover,
- long-running human review queues,
- durable collaborative lock/session state for normalization,
- broader comparison or award AI redesign beyond what is needed to carry normalized effective values forward,
- replacing manual continuity with forced provider-only operation.

## Acceptance Gates

This design is satisfied only when all of the following are true:

- active alpha docs no longer describe normalization as deterministic-only when `AI_MODE=provider` is the release posture,
- provider-backed normalization suggestions and confidence are visible in the normalize workspace,
- every buyer override requires a reason code and is appended to an audit trail,
- override records include actor, timestamp, before/after values, and provider confidence context,
- readiness recalculates from effective values after override,
- provider golden-path normalization E2E passes,
- degraded-provider manual continuity normalization E2E passes,
- alpha release docs and handoff gates explicitly include provider-mode normalization closure.

## Source References

- [2026-04-25-atomy-q-provider-quote-e2e-design.md](/home/azaharizaman/dev/atomy/docs/superpowers/specs/2026-04-25-atomy-q-provider-quote-e2e-design.md)
- [2026-04-25-atomy-q-alpha-release-handoff-design.md](/home/azaharizaman/dev/atomy/docs/superpowers/specs/2026-04-25-atomy-q-alpha-release-handoff-design.md)
- [apps/atomy-q/docs/03-domains/normalization/overview.md](/home/azaharizaman/dev/atomy/apps/atomy-q/docs/03-domains/normalization/overview.md)
- [apps/atomy-q/docs/03-domains/normalization/workflows.md](/home/azaharizaman/dev/atomy/apps/atomy-q/docs/03-domains/normalization/workflows.md)
