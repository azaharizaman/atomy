# Atomy-Q Provider Normalization Alpha Readiness Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `AI_MODE=provider` normalization release-ready for alpha by persisting provider suggestion/confidence state, requiring fully audited buyer overrides, and adding conclusive provider-mode normalization E2E coverage.

**Architecture:** This plan stays inside the existing quote-intake/normalization/runtime architecture. API becomes the single audited mutation boundary for provider-origin and buyer-override normalization state; WEB renders provider provenance and reason-coded overrides; Playwright closes the current gap between upload-to-source-lines and full normalization closure.

**Tech Stack:** PHP 8.3, Laravel 12, Eloquent, Next.js App Router, React, TypeScript, PHPUnit, Vitest, Playwright.

---

## Scope

- Provider-first normalization under `AI_MODE=provider`
- Buyer override audit trail with reason code, note, actor, timestamp, before/after value, and provider confidence context
- Normalize workspace rendering of provider suggestion/confidence and buyer-overridden state
- Truthful degraded-provider manual continuity for normalization
- Mocked/fake-provider and live-provider Playwright normalization E2E
- Alpha release doc alignment for normalization release gates

## Non-Goals

- Multi-provider failover
- Durable collaborative normalization lock/session redesign
- New review-queue subsystem
- Reworking comparison/award intelligence beyond what normalization needs to hand off effective values

## File Structure

### API

- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
  - extend source-line serialization with provider suggestion/effective override fields
  - route all manual normalization writes through one audited boundary
- Modify: `apps/atomy-q/API/app/Http/Requests/NormalizationOverrideRequest.php`
  - require structured override reason code
  - require note when reason is `other`
- Modify: `apps/atomy-q/API/app/Models/NormalizationSourceLine.php`
  - expose any new casts/helpers for effective/provenance payloads
- Create or modify: `apps/atomy-q/API/app/Services/QuoteIntake/...`
  - one focused service for audited override write + readiness recalculation
- Modify: `apps/atomy-q/API/routes/api.php`
  - keep manual source-line create/update endpoints pointed at the audited boundary
- Modify: `apps/atomy-q/API/tests/Feature/NormalizationReviewWorkflowTest.php`
- Modify: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`
- Add if needed: `apps/atomy-q/API/tests/Feature/NormalizationOverrideAuditTest.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

### WEB

- Modify: `apps/atomy-q/WEB/src/hooks/use-normalization-source-lines.ts`
  - normalize provider suggestion/confidence/effective override metadata
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
  - render provider confidence + buyer-overridden markers
  - require reason code on override submit
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx`
- Add if needed: focused hook tests under `apps/atomy-q/WEB/src/hooks/`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

### E2E

- Modify: `apps/atomy-q/WEB/tests/provider-quote-e2e.spec.ts`
  - extend from quote-intake-only proof to normalization golden path with audited override
- Modify: `apps/atomy-q/WEB/tests/provider-quote-live.spec.ts`
  - extend from upload/source-lines proof to normalization closure and comparison freeze
- Add if needed: `apps/atomy-q/WEB/tests/provider-normalization-degraded.spec.ts`
  - truthful degraded-provider manual continuity path
- Modify fixture support only if existing `sample/**/metadata.json` shape needs extra normalization assertions:
  - `apps/atomy-q/WEB/tests/support/providerQuoteFixtures.ts`

### Docs

- Modify: `apps/atomy-q/docs/03-domains/normalization/overview.md`
- Modify: `apps/atomy-q/docs/03-domains/normalization/workflows.md`
- Modify: `docs/superpowers/specs/2026-04-25-atomy-q-alpha-release-handoff-design.md` only if the implementation materially changes release evidence wording

## Task 1: Add API Audit And Effective-State Contract

- [ ] Extend the normalization source-line API contract so WEB can distinguish:
  - provider-suggested values
  - current effective values
  - buyer-overridden state
  - provider confidence visible at override time
  - latest override reason/timestamp/actor
- [ ] Keep provider-origin values append-only in provenance payloads instead of overwriting them in place.
- [ ] Introduce or refine one focused service behind `NormalizationController` that:
  - receives the authenticated actor context
  - writes override audit metadata
  - updates effective normalized state
  - recalculates readiness in one transaction
- [ ] Require structured override reasons at the request layer:
  - `supplier_document_mismatch`
  - `rfq_mapping_incorrect`
  - `quantity_or_uom_correction`
  - `price_correction`
  - `manual_entry_required`
  - `other`
- [ ] Enforce `note` when reason is `other`.
- [ ] Keep tenant scoping at the query root for every audited override mutation and read.
- [ ] Add or extend API feature coverage for:
  - provider-origin suggestion serialization
  - buyer override event persistence
  - actor/timestamp/before-after capture
  - readiness recalculation after override
  - `other` reason validation

## Task 2: Upgrade Normalize Workspace For Provider Provenance And Reason-Coded Overrides

- [ ] Extend `use-normalization-source-lines.ts` to normalize the new API shape without guessing from ad hoc `raw_data`.
- [ ] Preserve compatibility with existing manual rows where provider provenance is absent.
- [ ] Update the normalize page to show:
  - provider confidence near the editable decision point
  - provider-suggested vs buyer-overridden visual distinction
  - latest override metadata where useful for buyer review
- [ ] Replace the current free-text-only manual note flow with:
  - required reason code select
  - optional note textarea
  - note required only when reason is `other`
- [ ] Keep degraded provider messaging scoped:
  - provider unavailable banner stays visible
  - manual source-line entry and mapping remain usable
  - page does not collapse into a full failure unless live normalization payloads actually fail
- [ ] Add WEB tests for:
  - reason code is required before override submit
  - buyer-overridden lines render differently from provider-suggested lines
  - provider confidence is visible
  - degraded provider state keeps manual controls active

## Task 3: Close API And WEB Regression Gaps Around Freeze Readiness

- [ ] Verify the effective normalized value, not the provider-suggested value, is what drives readiness and freeze comparison.
- [ ] Add regression coverage proving:
  - override clears blockers when the effective mapping/value becomes valid
  - freeze remains blocked when effective state is still invalid
  - provider suggestion presence alone never fabricates readiness
- [ ] Recheck the current normalize page and freeze mutation tests so they assert the new audited override flow instead of the older free-text note-only behavior.
- [ ] Update API and WEB implementation summaries to describe the shipped provider-normalization override contract honestly.

## Task 4: Add Stable Mocked/Fake-Provider Normalization E2E

- [ ] Expand `apps/atomy-q/WEB/tests/provider-quote-e2e.spec.ts` from quote-intake rendering to full normalization golden path.
- [ ] The mocked/fake-provider E2E must cover:
  - provider AI status healthy
  - source lines returned with provider suggestion/confidence metadata
  - normalize workspace visible
  - buyer override with required reason code
  - buyer-overridden marker visible after submit
  - readiness clear state
  - successful comparison freeze
- [ ] If the current spec becomes unwieldy, split out a dedicated `provider-normalization-e2e.spec.ts` rather than bloating the quote-intake-only file.
- [ ] Add a stable degraded-provider E2E that proves:
  - normalization capability unavailable/degraded
  - truthful unavailable messaging
  - manual entry/mapping path still works
  - reason-coded override still applies
  - comparison freeze succeeds without fake provider success

## Task 5: Add Live Provider Normalization E2E For Release Evidence

- [ ] Extend `apps/atomy-q/WEB/tests/provider-quote-live.spec.ts` so the live suite proves normalization closure, not just upload and source-line creation.
- [ ] The live suite must prove:
  - provider-backed quote upload
  - source lines created
  - normalize workspace renders
  - at least one override with required reason code is submitted
  - readiness clears
  - comparison freeze succeeds
- [ ] Keep cost/quota pressure bounded:
  - one fixture by default
  - opt-in env gate remains required
  - no broad CI enablement
- [ ] If fixture metadata needs new assertions for expected normalization behavior, extend `providerQuoteFixtures.ts` conservatively instead of inventing a second fixture format.

## Task 6: Align Domain And Release Docs

- [ ] Update active normalization domain docs to remove deterministic-only wording where it now conflicts with the shipped `AI_MODE=provider` release posture.
- [ ] Make the workflows doc explicit that:
  - provider-first normalization is supported in alpha
  - buyer override is supported
  - override reason capture is mandatory
  - provider degradation preserves manual continuity
- [ ] Recheck the alpha release handoff doc so `normalization_intelligence` gate explicitly requires:
  - provider golden-path normalization closure
  - degraded-provider manual continuity proof
  - audited override evidence

## Verification Matrix

- [ ] API feature tests:
```bash
cd apps/atomy-q/API && php artisan test --filter NormalizationReviewWorkflowTest
cd apps/atomy-q/API && php artisan test --filter QuoteIngestionPipelineTest
```
- [ ] WEB unit tests:
```bash
cd apps/atomy-q/WEB && npm run test:unit -- src/app/'(dashboard)'/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.test.tsx src/hooks/use-normalization-source-lines.live.test.ts src/hooks/use-normalization-review.live.test.ts
```
- [ ] Stable mocked/fake-provider Playwright:
```bash
cd apps/atomy-q/WEB && npm run test:e2e:provider-quote:fake
```
- [ ] Live provider normalization Playwright:
```bash
cd apps/atomy-q/WEB && npm run test:e2e:provider-quote:live
```
- [ ] If a dedicated degraded-provider spec is added, include it in the verification command list and alpha release evidence.

## Self-Review Checklist

- [ ] Spec coverage confirmed:
  - provider-first normalization
  - manual continuity
  - full override audit payload
  - required reason code
  - provider confidence visible at override time
  - provider golden-path E2E
  - degraded-provider manual continuity E2E
- [ ] No placeholder steps remain.
- [ ] API field names and WEB normalized property names match exactly.
- [ ] Release docs reflect the final verification commands actually used by the branch.

## Exit Criteria

- `AI_MODE=provider` normalization is no longer release-ready based on upload-to-source-lines alone.
- API persists provider suggestion/effective override state with auditable buyer override metadata.
- WEB normalize workspace truthfully renders provider confidence and buyer-overridden state.
- Mocked/fake-provider normalization E2E passes.
- Live provider normalization E2E passes.
- Degraded-provider manual continuity path is proven and documented for alpha.
