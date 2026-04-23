# Atomy-Q AI Sourcing Recommendation And Vendor Decision Support Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver real provider-backed vendor recommendation and shortlist reasoning in alpha while ensuring vendor selection remains manually operable when recommendation intelligence is degraded or offline.

**Architecture:** Procurement recommendation vocabulary lives in Layer 1. `orchestrators/ProcurementOperations` remains the orchestration home because the existing recommendation contracts already live there and the alpha scope is tightly tied to RFQ vendor decision support. Layer 3 provides the Hugging Face sourcing recommendation adapter, API gating, provenance persistence, and vendor workspace UX.

**Tech Stack:** PHP 8.3, Laravel, Nexus packages, ProcurementOperations, React/TypeScript, PHPUnit, Vitest.

---

## Scope

- Provider-backed vendor recommendation and explanation
- Recommendation provenance and decision-trail persistence
- Truthful unavailable state for AI-only ranking behavior
- Manual vendor selection continuity
- Explicit separation between recommendation and authoritative vendor selection

## Layer Ownership

- **Layer 1**
  - `packages/MachineLearning`: provider-neutral inference request/response and health semantics.
  - `packages/ProcurementML`: vendor recommendation request/result DTOs, rationale fragments, exclusion reason vocabularies, ranking explanation metadata.
  - `packages/Audit` and `packages/AuditLogger`: shortlist recommendation evidence and final-selection audit trail.
- **Layer 2**
  - `orchestrators/ProcurementOperations`: recommendation orchestration, deterministic guards, candidate eligibility enforcement, bounded acceptance of provider output.
  - Keep recommendation in `ProcurementOperations` for alpha. Do not introduce a second orchestration home unless post-alpha reuse forces it.
- **Layer 3**
  - `apps/atomy-q/API`: Hugging Face sourcing recommendation adapter, controller/API contract, persistence and decision-trail integration.
  - `apps/atomy-q/WEB`: vendors page, recommendation display, shortlist editing UX, unavailable-state messaging.

## File Structure

- Create or modify in `packages/ProcurementML/src/...`:
  - vendor recommendation request/result DTOs
  - exclusion reason types
  - explanation/rationale fragments
- Modify: `orchestrators/ProcurementOperations/src/Contracts/VendorRecommendation*`
- Modify: `orchestrators/ProcurementOperations/src/Coordinators/VendorRecommendationCoordinator.php`
- Modify: `orchestrators/ProcurementOperations/src/Services/DeterministicVendorScorer.php`
- Add: orchestrator tests for provider-backed recommendation and manual fallback semantics

- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorRecommendationController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RecommendationController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RequisitionVendorSelectionController.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/HuggingFaceSourcingRecommendationClient.php`
- Modify: `apps/atomy-q/API/routes/api.php`
- Modify: `apps/atomy-q/API/openapi/openapi.json`
- Create or modify: API feature tests for recommendation AI gating and provenance

- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-recommendations.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx`
- Modify if needed: vendor selection panel tests and components

## Task 1: Harden Procurement Recommendation Contracts

- [ ] Move procurement-specific recommendation vocabulary that should outlive the current app adapter into `packages/ProcurementML`.
- [ ] Keep deterministic eligibility checks authoritative even when provider-backed ranking is active.
- [ ] Model recommendation results so they distinguish:
  - eligible candidates,
  - excluded candidates,
  - provider explanation,
  - deterministic reason set,
  - provenance metadata.

## Task 2: Upgrade ProcurementOperations Recommendation Flow

- [ ] Refactor `VendorRecommendationCoordinator` so provider-backed inference is the alpha default when sourcing recommendation capability is available.
- [ ] Keep deterministic scoring as a validation layer, not a fake substitute for provider recommendation in normal alpha operation.
- [ ] Reject provider output that introduces ineligible vendors, crosses tenant boundaries, or lacks sufficient context.
- [ ] Persist recommendation provenance and expose it to the decision trail.

## Task 3: Add API Gating, Provenance, And Truthful Failure Behavior

- [ ] Ensure vendor recommendation endpoints consult the Plan 1 AI status contract before invoking provider logic.
- [ ] For AI-only ranking functions, return explicit unavailable responses when the sourcing recommendation capability is disabled or degraded beyond policy.
- [ ] Preserve manual vendor selection APIs and pages even when recommendation is unavailable.
- [ ] Ensure controller responses do not blur “no recommendation because AI is unavailable” with “recommendation returned zero candidates”.

## Task 4: Update WEB Vendor Decision Support UX

- [ ] Show recommendation rationale, warnings, and provenance when AI is healthy.
- [ ] Hide recommendation affordances when a manual selection path exists and AI is unavailable.
- [ ] Preserve the ability to manually add, remove, and confirm vendors even when AI ranking is offline.
- [ ] If the current UX conflates ranking with final vendor choice, split those concerns in the vendors workspace.

## Task 5: Verification And Documentation

- [ ] Add tests for:
  - provider-backed recommendation success,
  - unavailable-state responses,
  - manual vendor selection continuity,
  - deterministic guard rejection of invalid provider output,
  - audit trail persistence of recommendation provenance.
- [ ] Run:
```bash
./vendor/bin/phpunit orchestrators/ProcurementOperations/tests apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationApiTest.php apps/atomy-q/API/tests/Feature/Api/V1/VendorRecommendationAiGateTest.php
cd apps/atomy-q/WEB && npm run test:unit -- src/app/'(dashboard)'/rfqs/[rfqId]/vendors/vendor-recommendations.test.tsx src/app/'(dashboard)'/rfqs/[rfqId]/vendors/vendor-selection-panel.test.tsx
```

## Exit Criteria

- Vendor recommendation is genuinely provider-backed in alpha.
- Vendor ranking is unavailable rather than fabricated when AI is down.
- Users can still complete vendor selection manually.
- Final vendor choice remains a human-authoritative action with clear provenance.
