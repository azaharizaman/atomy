# Atomy-Q AI Comparison, Award, And Approval Intelligence Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add provider-backed comparison insighting, award guidance, and approval drafting support to the RFQ chain without making deterministic comparison freeze, manual award choice, or approval progression dependent on live AI.

**Architecture:** Procurement-specific comparison and award AI vocabularies belong in Layer 1. `orchestrators/QuotationIntelligence` continues to own comparison-oriented intelligence, while `orchestrators/ProcurementOperations` owns award guidance and recommendation reuse, and `orchestrators/ApprovalOperations` stays authoritative for approval workflow state. Layer 3 must separate deterministic comparison data from AI overlays so unavailable AI does not corrupt core sourcing outputs.

**Tech Stack:** PHP 8.3, Laravel, Nexus orchestrators, React/TypeScript, PHPUnit, Vitest, Playwright.

---

## Scope

- Feature-level policies that separate AI overlays/drafting from deterministic comparison, award, and approval authority
- Provider-backed comparison explanations, anomaly summaries, and recommendation overlays
- Provider-backed award guidance, debrief drafting, and rationale assistance
- Optional AI approval drafting aids for summary/explanation, not approval authority
- Deterministic comparison freeze continuity
- Manual award and approval continuity
- Truthful unavailable behavior on AI-only overlays

## Layer Ownership

- **Layer 1**
  - `packages/ProcurementML`: comparison insight request/result DTOs, anomaly explanation DTOs, award guidance DTOs, debrief draft DTOs.
  - `packages/Audit`, `packages/AuditLogger`, `packages/Outbox`: persisted evidence, publication of decision-trail events.
- **Layer 2**
  - `orchestrators/QuotationIntelligence`: comparison explanation and anomaly/insight coordination over normalized quote data.
  - `orchestrators/ProcurementOperations`: award guidance coordination and recommendation-to-award rationale bridging.
  - `orchestrators/ApprovalOperations`: optional approval drafting aids that never supersede workflow authority.
- **Layer 3**
  - `apps/atomy-q/API`: comparison, award, and approval controllers; provider-specific comparison/award adapter; persistence; routes; OpenAPI.
  - `apps/atomy-q/WEB`: comparison run screens, award workspace, approval pages, decision-trail views.

## File Structure

- Create or modify in `packages/ProcurementML/src/...`:
  - comparison insight DTOs
  - award guidance DTOs
  - approval summary draft DTOs
- Modify: `orchestrators/QuotationIntelligence/src/...`
- Modify: `orchestrators/ProcurementOperations/src/...`
- Modify if needed: `orchestrators/ApprovalOperations/src/...`
- Add orchestrator tests for comparison and award AI behavior

- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AwardController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/OperationalApprovalController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DecisionTrailController.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/ProviderComparisonAwardClient.php`
- Create or reuse a shared provider transport for OpenRouter/Hugging Face endpoint invocation, keeping the comparison/award client responsible only for capability-specific mapping and validation
- Modify: `apps/atomy-q/API/routes/api.php`
- Modify: `apps/atomy-q/API/openapi/openapi.json`
- Add or refine persistence models/migrations if AI overlays and provenance need dedicated storage

- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-comparison-run-matrix.ts`
- Modify: `apps/atomy-q/WEB/src/hooks/use-award.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/approvals/page.tsx`
- Modify tests for comparison, award, and approvals surfaces

## Task 1: Separate Core Deterministic Data From AI Overlay Data

- [ ] Refactor response contracts so deterministic matrix/freeze data is available independently of AI commentary.
- [ ] Do not overload one field with both authoritative comparison facts and optional AI interpretation.
- [ ] Add or update feature-level policies:
  - `comparison_ai_overlay` is AI-derived and may be unavailable.
  - `comparison_deterministic_matrix` remains available from normalized data.
  - `award_ai_guidance` is AI-derived and may be unavailable.
  - `award_manual_submission` remains user-authoritative.
  - `approval_ai_summary` is AI-derived and may be unavailable.
  - `approval_workflow_progression` remains deterministic and user-authoritative.
- [ ] Store AI overlays separately from frozen comparison facts, either as dedicated records or as an explicit nested overlay section keyed to comparison run/version.
- [ ] If needed, add dedicated endpoints or nested payload sections for AI overlays to keep failure semantics explicit.

## Task 2: Add Provider-Backed Comparison Intelligence

- [ ] Coordinate provider-backed anomaly explanations and comparison recommendations from normalized RFQ data.
- [ ] Preserve deterministic ranking, tie-handling, and freeze behavior even when AI overlays are unavailable.
- [ ] Persist overlay provenance and reasoning snapshots against the comparison run/version or decision trail without mutating frozen matrix facts.

## Task 3: Add Award Guidance And Debrief Drafting

- [ ] Use provider-backed AI to draft award rationale and debrief content from frozen comparison evidence.
- [ ] Ensure final award records still come from deterministic application state and explicit user action.
- [ ] When award-intelligence capability is unavailable, keep the award workflow usable through manual rationale entry and selection.

## Task 4: Add Approval Drafting Aids Without Approval Autonomy

- [ ] Limit approval AI to summary/drafting support.
- [ ] Keep routing, status mutation, and approval authority in deterministic workflow code.
- [ ] If approval AI has no manual fallback on a specific component, show a truthful unavailable message without blocking approval progression.

## Task 5: Update WEB Comparison, Award, And Approval UX

- [ ] Render AI overlays as optional interpretive layers over deterministic sourcing data.
- [ ] Hide overlay affordances when manual continuity exists and AI is unavailable.
- [ ] Show unavailable states only on the AI-powered panels or controls, not on the entire comparison or award workflow.
- [ ] Preserve clear provenance so reviewers can tell what was provider-drafted versus user-authored.

## Task 6: Verification And Regression Protection

- [ ] Add tests for:
  - deterministic comparison freeze with AI unavailable,
  - provider-backed comparison overlay success,
  - manual award completion with AI unavailable,
  - award guidance provenance persistence,
  - approval progression unaffected by AI degradation,
  - tenant isolation and not-found behavior.
- [ ] Run:
```bash
./vendor/bin/phpunit orchestrators/QuotationIntelligence/tests orchestrators/ProcurementOperations/tests orchestrators/ApprovalOperations/tests apps/atomy-q/API/tests/Feature/AwardWorkflowTest.php
cd apps/atomy-q/WEB && npm run test:unit -- src/hooks/use-comparison-run.test.ts src/hooks/use-comparison-run-matrix.test.ts src/hooks/use-award.test.tsx src/app/'(dashboard)'/rfqs/[rfqId]/comparison-runs/[runId]/page.test.tsx src/app/'(dashboard)'/rfqs/[rfqId]/award/page.test.tsx
```

## Exit Criteria

- Comparison, award, and approval AI are real provider-backed alpha surfaces.
- Deterministic freeze and manual award/approval flow still work when AI is down.
- AI overlay failures are truthful and scoped.
- Decision-trail evidence distinguishes deterministic facts, provider-assisted drafts, and user-confirmed actions.
