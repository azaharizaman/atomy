# Atomy-Q AI Insights And Governance Functional Reality Design

**Date:** 2026-04-30
**Status:** Approved design for alpha corrective implementation
**Scope:** Plan 5 remediation for AI insights, reporting summaries, RFQ risk insighting, and vendor governance narrative surfaces

## Purpose

Atomy-Q currently satisfies the baseline structure of the AI Insights, Governance, and Reporting plan, but the shipped behavior is not alpha-ready. API routes, provider clients, and WEB narrative panels exist, yet several critical facts are still hardcoded or empty, AI summarization lives in Laravel controllers instead of `orchestrators/InsightOperations`, the WEB does not expose generation actions for the user, and governance/risk endpoints still contain stubbed behavior.

This design closes those gaps without expanding Plan 5 into a post-alpha governance platform. The goal is functional reality: provider-backed AI narratives must be generated from real tenant-scoped facts, deterministic governance data must remain authoritative, and users must be able to trigger or review AI narratives without losing manual continuity when AI is unavailable.

## Preceding Decisions

This design follows:

- `docs/superpowers/specs/2026-04-23-atomy-q-global-ai-fallback-design.md`
- `docs/superpowers/plans/2026-04-23-atomy-q-ai-plan-index.md`
- `docs/superpowers/plans/2026-04-23-atomy-q-ai-insights-governance-and-reporting.md`
- `docs/project/ARCHITECTURE.md`
- `docs/project/NEXUS_PACKAGES_REFERENCE.md`
- `docs/project/HOW_TO_USE_PACKAGE_REFERENCE.md`

The governing decisions are:

- Atomy-Q alpha is AI-first, provider-backed, and controlled by the global AI runtime model.
- AI may summarize, explain, and draft. It must not become the authoritative governance source of truth.
- The main RFQ chain and factual governance records remain usable when AI is disabled, degraded, or unavailable.
- Feature-level AI policies decide whether a surface is available, hidden, or unavailable; controllers and WEB components must not invent independent availability rules.
- Layer 1 owns domain truth and provider-neutral vocabulary, Layer 2 owns orchestration, and Layer 3 owns Laravel/Next.js/provider adapters.

## Problem Statement

The current implementation has four launch-blocking gaps:

1. `DashboardController` and `ReportController` build AI prompts from hardcoded zero and empty facts.
2. Plan 5 summarization logic is in controllers, while `InsightOperations` has no AI coordination flow for dashboard, reporting, RFQ risk, or governance narratives.
3. `AiNarrativePanel` displays cached narratives and unavailable messages, but it does not provide a shared way for the user to trigger the existing `POST .../generate` endpoints.
4. Risk and governance behavior is incomplete: RFQ risk items load as an empty list, sanctions screening can report a static completed state, and some governance facts are not connected through the intended deterministic evidence/finding/scoring model.

These gaps create a false readiness signal. A live provider can be called, but the provider receives inaccurate or empty facts. The resulting narrative may look real while being commercially useless or misleading.

## Goals

1. Move dashboard, reporting, RFQ risk, and governance narrative coordination into `orchestrators/InsightOperations`.
2. Replace hardcoded dashboard/report facts with tenant-scoped deterministic snapshots.
3. Build AI prompt contexts only from explicit factual source data and include source-fact hashes in provenance.
4. Keep governance facts, findings, evidence, sanctions records, ESG/compliance/risk scores, and manual reviews authoritative.
5. Add shared WEB generation affordances for AI narrative panels while respecting feature-level AI availability.
6. Replace empty risk and sanctions stubs with truthful deterministic data paths or explicit manual-evidence states.
7. Add verification that proves realistic tenant data flows into facts, AI contexts, cached artifacts, and WEB generation flows.

## Non-Goals

- No autonomous governance decisions.
- No AI-driven vendor approval, restriction, suspension, evidence acceptance, or due-diligence completion.
- No per-tenant AI provider controls.
- No multi-provider fallback.
- No post-alpha governance command center, model-quality console, or advanced exception queue.
- No fake sanctions match clearing when a real sanctions source is not configured.
- No controller-only shortcut that bypasses `InsightOperations`.

## Definitions

### Governance

In Atomy-Q, governance is an interpretive oversight layer over four deterministic pillars:

- Compliance
- Sanctions
- ESG
- Risk assessment

Governance combines three elements:

- **Authoritative evidence:** due-diligence records, sanctions screening records, certifications, ESG reports, compliance artifacts, and findings.
- **Quantitative health model:** deterministic scores such as ESG score, compliance health, risk watch, and evidence freshness.
- **AI narrative:** non-authoritative explanation of why the facts and scores matter.

AI narrative must always be read as assistive interpretation. It cannot create or approve evidence, close findings, clear sanctions, or change vendor status.

### Functional Reality

Functional reality means the feature works with real tenant data and honest failure semantics. A route or component is not alpha-ready merely because it exists. It must use tenant-scoped facts, preserve provenance, respect AI availability, and be covered by realistic tests.

## Architecture

### Layer 1: Domain Truth And Vocabulary

Use existing packages before adding app-local services:

| Package | Responsibility |
|---|---|
| `Nexus\MachineLearning` | Provider-neutral AI request/result vocabulary, feature availability, health, endpoint group semantics, provider trace metadata. |
| `Nexus\ProcurementML` | Procurement-specific summary DTOs for dashboard, reporting, RFQ risk, and governance narrative contexts when the vocabulary should outlive the Laravel app. |
| `Nexus\QueryEngine` | Analytical query abstraction for dashboard and reporting facts where cross-domain aggregation is needed. |
| `Nexus\Reporting` | Report definitions, report run concepts, scheduled report presentation, and export-facing report behavior. |
| `Nexus\Vendor` | Vendor master truth and status rules. |
| `Nexus\Sanctions` | Sanctions and watchlist screening contracts. |
| `Nexus\Compliance` | Compliance controls and compliance findings. |
| `Nexus\ESG` | ESG scoring and sustainability truth where available. |
| `Nexus\Audit` and `Nexus\AuditLogger` | Tamper-evident and human-readable evidence for generated AI artifacts and manual review actions. |
| `Nexus\Telemetry` | Metrics and observability for insight generation outcomes. |

Layer 1 must not import Laravel, Eloquent, provider SDK classes, or Atomy-Q HTTP request types.

### Layer 2: InsightOperations

`orchestrators/InsightOperations` becomes the coordination home for Plan 5. It should expose small use cases through interfaces so Laravel controllers can delegate instead of constructing facts and calling provider clients directly.

Required orchestrator responsibilities:

- Build dashboard KPI snapshots from a `DashboardFactsPortInterface`.
- Build reporting facts from a `ReportingFactsPortInterface`.
- Build RFQ risk context from a `RiskInsightFactsPortInterface`.
- Build vendor governance context from a `GovernanceFactsPortInterface`.
- Check feature-level AI availability through an AI runtime/status port.
- Invoke provider-backed narrative generation through insight/governance narrative ports.
- Persist or return generated artifact envelopes with provenance and source-fact hashes.
- Return truthful unavailable artifacts when AI is disabled, degraded, or unavailable.
- Keep deterministic facts available even when the narrative cannot be generated.

Recommended interface shape:

```php
interface DashboardInsightCoordinatorInterface
{
    public function show(string $tenantId): DashboardInsightResult;

    public function generate(string $tenantId, string $actorId): DashboardInsightResult;
}
```

Equivalent interfaces should exist for reporting summaries, RFQ risk insights, and governance narratives. The exact names may vary, but the boundary must hold: controllers do not assemble provider prompts or provider provenance.

### Layer 3: API

Laravel owns:

- Tenant/user extraction.
- Authorization and not-found semantics.
- Eloquent adapters for orchestrator fact ports.
- Provider-specific insight and governance clients.
- Cache/persistence implementation for generated artifacts.
- Route and OpenAPI contracts.
- HTTP response envelopes.

Controllers should become thin:

1. Resolve tenant/user/request parameters.
2. Delegate to the relevant `InsightOperations` coordinator.
3. Return the coordinator result as JSON.

Controllers should not:

- Hardcode factual dashboards or report datasets.
- Call provider clients directly for Plan 5 narratives.
- Decide AI availability independently.
- Return a successful AI artifact when source facts are empty because implementation is missing.

### Layer 3: WEB

The WEB app owns rendering and interaction:

- Fetch factual dashboard/report/RFQ/governance data.
- Render AI narrative panels as optional interpretation.
- Expose generation actions where the API has `POST .../generate` routes.
- Disable or hide generate controls according to shared AI status helpers.
- Keep factual cards, tables, findings, evidence, scores, and manual review actions visible when AI is unavailable.

`AiNarrativePanel` should support generation through props rather than one-off page implementations:

- `onGenerate?: () => void`
- `isGenerating?: boolean`
- `canGenerate?: boolean`
- `generateLabel?: string`
- `lastGeneratedAt?: string | null`

Pages remain responsible for calling the correct mutation hook. The shared component owns button placement, disabled state, loading copy, and unavailable callout consistency.

## Data Model And Facts

### Dashboard Facts

Dashboard AI context must come from real tenant-scoped queries. Minimum alpha facts:

- Active RFQs count.
- Pending approvals count.
- Quote intake count.
- Awards in flight count.
- Total savings from completed/awarded comparison or award records where available.
- Average cycle time in days from RFQ creation to award or current stage where available.
- Recent activity summary.
- Risk alert count.

If a metric cannot yet be computed because the underlying domain is not implemented, the API must return an explicit `not_available` metric with a reason, not a hardcoded zero that looks factual.

Example fact item:

```json
{
  "key": "avg_cycle_time_days",
  "value": null,
  "status": "not_available",
  "reason_code": "source_domain_not_implemented"
}
```

### Reporting Facts

Reporting AI context must be based on actual report datasets:

- KPI report facts.
- Spend trend series.
- Spend by category.
- Report schedules/runs where implemented.

Empty result sets are valid only when the tenant truly has no matching data. They must be distinguishable from unimplemented data paths.

### RFQ Risk Facts

RFQ risk insights must summarize deterministic risk records derived from:

- RFQ status and deadlines.
- Vendor participation and quote readiness.
- Comparison/award blockers.
- Open findings tied to vendors in the RFQ.
- Compliance/sanctions/evidence issues tied to selected vendors.

`loadRiskItems()` must be replaced by a real tenant-scoped query/adaptation path. If no risk model exists for a specific signal, the result should omit that signal or mark it unavailable; it must not make the whole risk list silently empty.

### Governance Facts

Vendor governance facts must include:

- Evidence records.
- Findings.
- Summary scores.
- Warning flags.
- Sanctions screening history.
- Due-diligence review status.
- Evidence freshness.

The provider-facing prompt context should be sanitized. It may include categories, severities, dates, score values, statuses, and hashed actor references. It should not send raw notes, personal contact details, or unnecessary free text unless a data-handling review explicitly approves it.

## AI Artifact Contract

Every generated Plan 5 AI artifact must include:

- `feature_key`
- `capability_group`
- `available`
- `status`
- `payload`
- `provenance`
- `source_facts`
- `source_facts_hash`
- `reason_codes`

Provenance must include:

- Provider name.
- Endpoint group.
- Model identifier or route identifier when available.
- Prompt/template version.
- Provider request id or trace id when available.
- Input hash.
- Output hash.
- Latency.
- Generated timestamp.
- Actor id or actor hash for user-triggered generation.

Cached artifacts must be invalidated or bypassed when source facts change. A short TTL alone is not sufficient if the cache key does not include the source-facts hash.

## API Behavior

### Read Endpoints

Read endpoints return deterministic facts plus the latest cached narrative if one exists.

If no cached narrative exists:

- Factual data remains available.
- The AI artifact returns an unavailable envelope with reason `no_cached_ai_artifact`.
- The WEB may show a generate button when the feature policy allows generation.

### Generate Endpoints

Generate endpoints:

- Validate tenant and subject access first.
- Build deterministic facts.
- Return `422` or an unavailable artifact when required source facts are absent for a real reason.
- Check feature-level AI status before provider invocation.
- Invoke the provider through `InsightOperations`.
- Persist/cache the artifact with provenance.
- Return deterministic facts plus the generated artifact.

Generate endpoints must not report success if:

- AI is disabled.
- The provider times out.
- The provider payload is invalid.
- The factual context is a placeholder.
- The selected tenant/subject does not exist.

## WEB Behavior

### Shared Narrative Panel

`AiNarrativePanel` should render:

- Existing available narrative.
- Loading state.
- Scoped unavailable callout.
- Generate button when allowed and useful.
- Provenance summary.

The generate button should:

- Use the page mutation hook.
- Show a pending state while the request is running.
- Be disabled when the AI status says the feature is unavailable.
- Avoid blocking factual content.

### Dashboard

The dashboard page must show real factual cards independently of AI. The AI panel interprets those facts. If the narrative is missing, the user can generate it when `dashboard_ai_summary` is available.

### Reporting

Reporting pages must show report data independently of AI. Reporting AI summarizes the selected report scope, not a global placeholder dataset.

### RFQ Risk

The RFQ risk page must show deterministic risk items and manual review state. AI insight is an optional interpretation of those risk items.

### Vendor Governance

Vendor detail and ESG/compliance pages must show evidence, findings, scores, warning flags, sanctions history, and due-diligence status regardless of AI state. AI narrative explains the governance profile but does not replace the factual record.

## Sanctions And Manual Evidence

Alpha must distinguish three cases:

1. **Real sanctions screening available:** use `Nexus\Sanctions` through an adapter and persist results as evidence.
2. **Manual sanctions evidence captured:** allow a user to record manual screening evidence with source, review status, expiration, and reviewer.
3. **Screening not configured:** return an explicit unavailable/manual-required state.

The API must not return static `screening_status: completed` with empty matches unless a real screening action or manual evidence record was created.

## Error Handling

Use explicit reason codes:

- `ai_disabled`
- `ai_unavailable`
- `provider_timeout`
- `provider_invalid_response`
- `no_cached_ai_artifact`
- `source_facts_unavailable`
- `source_subject_not_found`
- `source_domain_not_implemented`
- `manual_review_required`
- `sanctions_provider_not_configured`

Wrong-tenant and missing resources return `404` without leaking existence.

Provider failures return unavailable AI artifacts or appropriate non-2xx generate responses. They must not erase deterministic facts.

## Observability

Each generation attempt should log structured fields:

- `tenant_id`
- `actor_id` or actor hash
- `feature_key`
- `capability_group`
- `endpoint_group`
- `provider`
- `subject_type`
- `subject_id`
- `source_facts_hash`
- `outcome`
- `reason_code`
- `latency_ms`

Metrics should count:

- Successful generations.
- Unavailable generations by reason.
- Provider invalid payloads.
- Provider timeouts.
- Empty or unavailable fact contexts.
- Cache hits and misses.

This is not a full Plan 6 operations expansion, but Plan 5 must emit enough evidence for launch hardening to verify it.

## Security And Privacy

- All fact queries must be tenant-scoped at the query root.
- Wrong-tenant access must return `404`.
- Provider prompt context must be minimized and sanitized.
- Free-text notes, user names, emails, phone numbers, and internal comments should not be sent to providers unless explicitly required and approved.
- AI output must be stored separately from authoritative evidence and findings.
- Manual review actions must record the human actor and timestamp.

## Testing Strategy

### Orchestrator Tests

Add `InsightOperations` tests for:

- Dashboard read with real facts and no cached artifact.
- Dashboard generate with provider success.
- Dashboard generate with AI unavailable.
- Reporting generate with real dataset.
- RFQ risk insights with realistic risk items.
- Governance narrative with evidence, findings, scores, and sanitized provider context.
- Provider invalid payload mapped to unavailable artifact.

### API Tests

Add or update API feature tests for:

- Dashboard KPIs are not hardcoded zeros when seeded tenant data exists.
- Report KPIs and trend facts reflect seeded data.
- Generate endpoints use real source facts.
- Wrong tenant returns `404`.
- No cached narrative returns factual data plus `no_cached_ai_artifact`.
- Sanctions screening does not return fake completed status when no real or manual screening occurred.

### WEB Tests

Add or update WEB tests for:

- `AiNarrativePanel` renders a generate button when configured.
- Generate button disables when AI feature is unavailable.
- Dashboard generation mutation refreshes the narrative.
- Reporting generation mutation refreshes the narrative.
- Vendor governance factual sections remain visible when AI narrative is unavailable.
- RFQ risk factual items remain visible when AI insight is unavailable.

### Verification Commands

Expected focused gates:

```bash
./vendor/bin/phpunit orchestrators/InsightOperations/tests
cd apps/atomy-q/API && php artisan test --filter Dashboard
cd apps/atomy-q/API && php artisan test --filter Report
cd apps/atomy-q/API && php artisan test --filter RiskCompliance
cd apps/atomy-q/API && php artisan test --filter VendorGovernance
cd apps/atomy-q/WEB && npm run test:unit -- src/components/ai/ai-narrative-panel.test.tsx
cd apps/atomy-q/WEB && npm run test:unit -- src/app/'(dashboard)'/page.test.tsx src/app/'(dashboard)'/reporting/page.test.tsx
```

Before alpha release, the broader Plan 5 and Plan 6 gates still apply.

## Acceptance Criteria

- Dashboard and report AI prompts are built from real tenant-scoped facts or explicit unavailable fact items.
- `InsightOperations` owns dashboard, reporting, RFQ risk, and governance narrative orchestration.
- Controllers no longer call Plan 5 provider clients directly for business coordination.
- WEB users can trigger generation where `POST .../generate` endpoints exist and AI status permits it.
- Factual dashboard, reporting, RFQ risk, and governance data remain visible when AI is unavailable.
- Sanctions and due-diligence endpoints do not claim completed deterministic outcomes without real or manual evidence.
- AI artifacts preserve provenance and source-fact hashes.
- Tests prove realistic seeded scenarios, unavailable states, and tenant isolation.

## Implementation Slices

1. **InsightOperations contracts and DTOs**
   Add coordinator interfaces, fact DTOs, artifact DTOs, and ports for dashboard, reporting, RFQ risk, governance facts, provider narrative generation, AI availability, and artifact cache/storage.

2. **API fact adapters and controller refactor**
   Implement Laravel/Eloquent adapters for the fact ports and refactor dashboard/report/risk/governance controllers to delegate to orchestrators.

3. **Governance and sanctions truthfulness**
   Replace static sanctions and empty risk behavior with real evidence/finding/scoring paths or explicit manual-required/unconfigured states.

4. **WEB generation UX**
   Extend `AiNarrativePanel`, add mutation hooks for dashboard/report/RFQ/governance generation, and wire page-level generation actions.

5. **Verification and documentation**
   Add realistic tests, update implementation summaries, regenerate OpenAPI/client code if response contracts change, and run focused gates.

## Out Of Scope Follow-Up

These are useful but should not block this alpha corrective spec:

- Governance exception queue.
- Model quality review dashboard.
- Governance trend monitoring over time.
- Board-ready governance report packs.
- Per-tenant AI policy controls.
- Advanced sanctions provider onboarding workflows.
- Continuous background regeneration of narratives.
