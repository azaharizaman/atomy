# Atomy-Q AI Insights, Governance, And Reporting Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend provider-backed AI to dashboard insighting, RFQ insight surfaces, vendor governance/risk explanation surfaces, and reporting summaries while preserving deterministic source-of-truth data and truthful unavailable behavior.

**Architecture:** Insight summarization belongs in `orchestrators/InsightOperations`. Governance narrative enrichment must sit on top of existing compliance, sanctions, ESG, and vendor-governance data rather than replacing it. Layer 3 exposes provider-backed insight and governance endpoints, and WEB renders AI narratives as assistive interpretation of underlying facts.

**Tech Stack:** PHP 8.3, Laravel, InsightOperations, Compliance-related packages, React/TypeScript, PHPUnit, Vitest.

---

## Scope

- Dashboard AI summaries
- RFQ insights sidebar and overview narratives
- Vendor governance and risk explanation surfaces
- Reporting summaries and generated narratives
- Truthful unavailable states where no manual AI equivalent exists

## Layer Ownership

- **Layer 1**
  - `packages/MachineLearning`: provider-neutral summary/generation contracts.
  - `packages/ProcurementML`: procurement/risk/governance summary DTOs where domain-specific.
  - `packages/Sanctions`, `packages/Compliance`, `packages/ESG`, `packages/Vendor`: authoritative governance and risk data sources. AI may summarize them, not replace them.
  - `packages/Audit` and `packages/AuditLogger`: provenance and evidence for generated narratives when persisted.
- **Layer 2**
  - `orchestrators/InsightOperations`: dashboard and reporting insight coordination.
  - `orchestrators/ProcurementOperations` and any existing governance-related coordinators: provide factual source context for AI summarization.
- **Layer 3**
  - `apps/atomy-q/API`: dashboard, report, risk/compliance, and vendor-governance controllers; provider-specific insight and governance adapters.
  - `apps/atomy-q/WEB`: dashboard page, RFQ overview, RFQ insight sidebar, risk page, vendor governance pages, reporting surfaces.

## File Structure

- Create or modify in `packages/ProcurementML/src/...`:
  - dashboard summary DTOs
  - RFQ insight DTOs
  - governance explanation DTOs
  - reporting summary DTOs
- Modify: `orchestrators/InsightOperations/src/...`
- Modify if needed: `orchestrators/ProcurementOperations/src/...`
- Add unit tests for insight and governance summary coordination

- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DashboardController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ReportController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorGovernanceController.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RiskComplianceController.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/ProviderInsightClient.php`
- Create: `apps/atomy-q/API/app/Adapters/Ai/ProviderGovernanceClient.php`
- Create if needed: provider-specific implementations such as `OpenRouterInsightClient.php`, `OpenRouterGovernanceClient.php`, `HuggingFaceInsightClient.php`, and `HuggingFaceGovernanceClient.php`
- Modify: `apps/atomy-q/API/routes/api.php`
- Modify: `apps/atomy-q/API/openapi/openapi.json`

- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/page.tsx`
- Modify: `apps/atomy-q/WEB/src/components/workspace/rfq-insights-sidebar.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/overview/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx`
- Modify: `apps/atomy-q/WEB/src/hooks/use-vendor-governance.ts`
- Modify reporting-related hooks/components as needed

## Task 1: Define AI Narrative Boundaries

- [ ] Ensure every summary or explanation payload preserves a link to the factual source data it summarizes.
- [ ] Do not allow AI narrative to become the only returned representation for dashboard, governance, or report facts.
- [ ] Model explicit reason codes for unavailable insight or governance summarization.

## Task 2: Add Provider-Backed InsightOperations Flows

- [ ] Extend `InsightOperations` to request provider-backed dashboard and reporting summaries through the Plan 1 runtime contract.
- [ ] Keep dashboard numeric cards and reporting datasets deterministic and available without AI.
- [ ] Persist generated summary provenance where summaries are stored or cached.

## Task 3: Add Governance And Risk Narrative Enrichment

- [ ] Build provider-backed narrative enrichment on top of existing sanctions, compliance, ESG, and vendor governance facts.
- [ ] Never let provider-backed summaries directly change vendor status, risk flags, or compliance state.
- [ ] If governance AI is unavailable, keep the factual governance data visible and remove only the narrative layer.

## Task 4: Update WEB Surfaces

- [ ] Show dashboard and RFQ insight summaries when available.
- [ ] If AI is unavailable, hide or collapse the narrative panel while keeping the factual dashboard and RFQ detail usable.
- [ ] On governance/risk screens, keep evidence, findings, and scores visible even when AI explanation panels are unavailable.
- [ ] Use the shared AI callout/status primitives from Plan 1 rather than inventing new messaging patterns.

## Task 5: Verification And Documentation

- [ ] Add tests for:
  - dashboard summary success and unavailable paths,
  - RFQ insights sidebar fallback behavior,
  - vendor governance narrative unavailable behavior with factual data still present,
  - reporting summary degradation without data-loss.
- [ ] Run:
```bash
./vendor/bin/phpunit orchestrators/InsightOperations/tests apps/atomy-q/API/tests/Feature/FeatureFlagsApiTest.php
cd apps/atomy-q/WEB && npm run test:unit -- src/app/'(dashboard)'/page.test.tsx src/app/'(dashboard)'/rfqs/[rfqId]/overview/page.test.tsx
```

## Exit Criteria

- Insight and governance AI are provider-backed where promised.
- Factual dashboard, RFQ, governance, and report data remain available without AI.
- Governance AI never becomes authoritative source-of-truth data.
- WEB uses the same capability-aware unavailable patterns as the core RFQ chain.
