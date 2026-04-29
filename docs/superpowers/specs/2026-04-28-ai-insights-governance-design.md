# Atomy-Q AI Insights & Governance Design

**Date:** 2026-04-28
**Audience:** Engineering lead, release coordinator
**Status:** Approved — ready for implementation plan
**Scope:** Plan 5 slice — insights, governance narratives, reporting summaries for Atomy-Q alpha release

---

## Purpose

Design the next slice of Atomy-Q AI intelligence and governance capabilities. This is the Plan 5 implementation that extends provider-backed AI to dashboard insights, RFQ insight surfaces, vendor governance/risk explanation surfaces, and reporting summaries — while preserving deterministic source-of-truth data and truthful unavailable behavior.

This design sets the scope of the Atomy-Q alpha release and showcases the main selling point of the SaaS: AI that explains and summarizes, never replaces, authoritative business data.

---

## Architecture Overview

### Key Principles

- **InsightOperations** owns 4 per-surface coordinators, each maps to one capability group
- **MachineLearning** owns provider-neutral narrative contracts; **ProcurementML** owns procurement-specific DTOs
- **API adapters** own provider-specific HTTP clients for insight/governance calls
- **WEB** renders narratives via existing `ai-narrative-panel` and `ai-unavailable-callout` primitives
- **Cache** uses InsightStoragePort with TTL + event-driven invalidation + manual refresh

### Capability Group → Coordinator Mapping

| Capability Group | Coordinator | WEB Surface |
|---|---|---|
| `insight_intelligence` | `DashboardInsightCoordinator` | Dashboard AI summary panel |
| `insight_intelligence` | `RfqInsightCoordinator` | RFQ overview insights sidebar |
| `governance_intelligence` | `GovernanceNarrativeCoordinator` | Risk/governance narrative panel |
| `insight_intelligence` | `ReportingSummaryCoordinator` | Report AI narrative summary |

### Data Flow (Example: RFQ Insights)

1. WEB calls `GET /api/v1/rfqs/{id}/insights`
2. API Controller → `RfqInsightCoordinator` (InsightOperations)
3. Coordinator checks cache via `InsightStoragePort`
   - HIT: return cached narrative (check TTL + freshness)
   - MISS: call `ProviderInsightClient` (API adapter)
4. Provider client uses Plan-1 runtime contract (capability group, feature key)
5. Response validated, mapped to `RfqInsightDto`, cached, returned
6. WEB renders in `ai-narrative-panel` or `ai-unavailable-callout`
7. Event listeners invalidate cache on RFQ status/vendor/data changes

---

## Layer 1 Changes

### MachineLearning Package

Extend `packages/MachineLearning` with narrative generation contracts. ML already owns provider-neutral AI vocabulary, endpoint health, and runtime capability state.

**New Contracts:**
- `NarrativeRequestInterface` — prompt context, narrative type, max tokens, temperature
- `NarrativeResultInterface` — narrative text, confidence, provenance metadata, token usage
- `NarrativeGeneratorInterface` — `generateNarrative(NarrativeRequest): NarrativeResult`

**Narrative Types (constants):**
- `TYPE_DASHBOARD_SUMMARY`
- `TYPE_RFQ_INSIGHTS`
- `TYPE_GOVERNANCE_NARRATIVE`
- `TYPE_REPORTING_SUMMARY`

### ProcurementML Package

Add procurement/risk/governance-specific DTOs. These are domain-specific, not generic ML contracts.

**RFQ Insight DTOs:**
- `RfqInsightDto` — rfqId, vendorSpread, timingRisk, recommendationContext, narrative
- `RfqInsightRequest` — rfqId, tenantId, sourceData (vendors, quotes, timeline)
- `RfqInsightResult` — dto + provenance (generatedAt, provider, model, tokenUsage)

**Governance Narrative DTOs:**
- `GovernanceNarrativeDto` — vendorId, riskFlags, complianceGaps, esgSummary, narrative
- `GovernanceNarrativeRequest` — vendorId, tenantId, facts (sanctions, compliance, ESG)
- `GovernanceNarrativeResult` — dto + provenance

**Reporting Summary DTOs:**
- `ReportingSummaryDto` — reportId, trends, anomalies, narrative
- `ReportingSummaryRequest` — reportId, tenantId, factualData (metrics, dimensions)
- `ReportingSummaryResult` — dto + provenance

### Dashboard Insight DTOs

Dashboard summaries are not procurement-specific, so they live in MachineLearning (not ProcurementML).

- `DashboardSummaryDto` — tenantId, rfqHealth, vendorRiskHighlights, sourcingStatus, narrative
- `DashboardSummaryRequest` — tenantId, factualContext (RFQ counts, vendor stats, spend)
- `DashboardSummaryResult` — dto + provenance

### What Does NOT Change

- `Sanctions`, `Compliance`, `ESG`, `Vendor` — authoritative data unchanged; AI only summarizes them
- `QueryEngine`, `Reporting` — factual query/reporting unchanged; AI narrative is a layer on top
- No Layer 1 package gains provider-specific HTTP code (that stays in Layer 3)

---

## InsightOperations Orchestrator Changes

### New Contracts in `orchestrators/InsightOperations/src/Contracts/`

**DashboardInsightCoordinatorInterface:**
- `generateSummary(DashboardSummaryRequest): DashboardSummaryResult`
- `getCachedSummary(tenantId): ?DashboardSummaryDto`
- `invalidateCache(tenantId): void`

**RfqInsightCoordinatorInterface:**
- `generateInsights(RfqInsightRequest): RfqInsightResult`
- `getCachedInsights(rfqId): ?RfqInsightDto`
- `invalidateCache(rfqId): void`

**GovernanceNarrativeCoordinatorInterface:**
- `generateNarrative(GovernanceNarrativeRequest): GovernanceNarrativeResult`
- `getCachedNarrative(vendorId): ?GovernanceNarrativeDto`
- `invalidateCache(vendorId): void`

**ReportingSummaryCoordinatorInterface:**
- `generateSummary(ReportingSummaryRequest): ReportingSummaryResult`
- `getCachedSummary(reportId): ?ReportingSummaryDto`
- `invalidateCache(reportId): void`

### Cache Strategy (InsightStoragePort)

**Cache key format:** `insight:{type}:{tenantId|entityId}`

**TTL by surface:**
- Dashboard summary: `3600s (1hr)`
- RFQ insights: `1800s (30min)` or until RFQ status changes
- Governance narrative: `1800s (30min)` or until vendor facts change
- Reporting summary: `3600s (1hr)` or until report data changes

**Manual refresh:** POST to `POST /api/v1/insights/{type}/{id}/refresh` clears cache and triggers regeneration

### Event-Driven Cache Invalidation

New listeners in InsightOperations that invalidate cache when underlying facts change:

- `RfqStatusChangedListener` → invalidates RFQ insights cache for that rfqId
- `VendorSanctionUpdatedListener` → invalidates governance narrative for that vendorId
- `ComplianceFlagChangedListener` → invalidates governance narrative for affected vendorId
- `ReportDataChangedListener` → invalidates reporting summary for that reportId
- `TenantDashboardDataChangedListener` → invalidates dashboard summary for that tenantId

### Provider Call Path (Layer 2 → Layer 3)

**Important:** InsightOperations (L2) does NOT call providers directly.

1. Coordinator receives request
2. Checks cache via `InsightStoragePort`
3. On cache MISS → calls its own contract interface (e.g., `DashboardInsightCoordinatorInterface`)
4. **API adapter** (L3) implements that interface, calls `ProviderInsightClient`
5. Provider client uses Plan-1 runtime contract (capability group, feature key)
6. Response validated → mapped to DTO → cached via `InsightStoragePort`

### Existing Files to Extend

- `src/Contracts/DashboardSnapshotPortInterface.php` — pattern to follow for new contracts
- `src/Workflows/DashboardSnapshotWorkflow.php` — pattern for coordinator workflow logic
- `src/Coordinators/ReportingCoordinator.php` — existing coordinator to extend, not replace

---

## Layer 3: API Adapters, Controllers, Routes

### Provider Clients (`apps/atomy-q/API/app/Adapters/Ai/`)

**ProviderInsightClient:**
- Implements `InsightOperations` coordinator interfaces
- Uses Plan-1 runtime contract for capability group routing
- Calls provider (OpenRouter/Hugging Face) with narrative prompt
- Validates response, maps to DTO, returns `NarrativeResult`
- Handles timeout, auth failure, quota exhaustion → returns truthful unavailable

**ProviderGovernanceClient:**
- Implements `GovernanceNarrativeCoordinatorInterface`
- Builds governance prompt from sanctions, compliance, ESG facts
- Never lets response mutate authoritative governance state
- Returns narrative + provenance metadata

### Controllers & Routes

| Endpoint | Controller | Capability Group |
|---|---|---|
| `GET /api/v1/dashboard/insights` | DashboardController | `insight_intelligence` |
| `GET /api/v1/rfqs/{id}/insights` | RfqInsightsController (new) | `insight_intelligence` |
| `GET /api/v1/vendors/{id}/governance-narrative` | VendorGovernanceController | `governance_intelligence` |
| `GET /api/v1/reports/{id}/summary` | ReportController | `insight_intelligence` |
| `POST /api/v1/insights/{type}/{id}/refresh` | InsightRefreshController (new) | (manual cache invalidation) |

### Feature-Level Policies (Plan 1 runtime contract)

- `dashboard_ai_summary` → feature key for dashboard insights
- `rfq_ai_insights` → feature key for RFQ insights sidebar
- `governance_ai_narrative` → feature key for governance narrative panel
- `reporting_ai_summary` → feature key for reporting summary

### WEB Integration

**Dashboard Page:**
- Deterministic KPI cards (unchanged)
- AI Summary Panel (`ai-narrative-panel`) — e.g., "3 RFQs pending, vendor spread widening, $47K savings opportunity"

**RFQ Overview Page:**
- Facts: status, vendors, quotes (unchanged)
- Insights Sidebar (`ai-narrative-panel`) — e.g., "Timing risk: 5 days left, vendor A 12% above market"

**Risk/Governance Page:**
- Sanctions flags, compliance gaps, ESG scores (facts, unchanged)
- Governance Narrative (`ai-narrative-panel`) — e.g., "Vendor B has 2 active sanctions flags, compliance gap in ISO cert"

**Report Page:**
- Deterministic report data (unchanged)
- AI Summary (`ai-narrative-panel`) — e.g., "Spend up 12% QoQ, 3 vendors >$50K, renegotiate contracts A,B"

### Unavailable Behavior (Truthful Degradation)

- When AI_MODE=off or capability group degraded: panels collapse to `ai-unavailable-callout`
- Factual data (KPIs, RFQ facts, governance flags, report data) always visible
- `ai-status-chip` shows capability group health on each page
- No synthetic success payloads — controllers return `ai_disabled` or `ai_unavailable` with 200 status

---

## Testing & Verification

### Unit Tests — Layer 1

**MachineLearning:**
- `NarrativeRequestTest` — DTO construction, validation
- `NarrativeResultTest` — provenance fields, confidence
- `NarrativeGeneratorTest` — mock interface, verify contract

**ProcurementML:**
- `RfqInsightDtoTest` — fields, serialization
- `GovernanceNarrativeDtoTest` — facts vs narrative separation
- `ReportingSummaryDtoTest` — DTO invariants

### Unit & Integration Tests — InsightOperations (L2)

- `DashboardInsightCoordinatorTest` — cache hit/miss, provider call, TTL invalidation
- `RfqInsightCoordinatorTest` — builds prompt from RFQ facts, caches result, invalidates on status change
- `GovernanceNarrativeCoordinatorTest` — narrative on top of sanctions/compliance/ESG facts, facts unchanged by AI
- `ReportingSummaryCoordinatorTest` — summary generated from report data, cache keyed by reportId
- `CacheInvalidationListenerTest` — event listeners clear correct cache keys

### API Feature Tests — Layer 3

- `DashboardInsightsApiTest` — GET /api/v1/dashboard/insights, feature flag off → unavailable, on → narrative or 200 with ai_unavailable
- `RfqInsightsApiTest` — GET /api/v1/rfqs/{id}/insights, cache hit returns same narrative, manual refresh clears cache
- `VendorGovernanceApiTest` — GET /api/v1/vendors/{id}/governance-narrative, facts still present when AI unavailable
- `ReportSummaryApiTest` — GET /api/v1/reports/{id}/summary, deterministic data unchanged by AI
- `InsightRefreshApiTest` — POST /api/v1/insights/{type}/{id}/refresh, clears cache, returns 202

### WEB Unit Tests

- `dashboard-page.test.tsx` — ai-narrative-panel renders when available, ai-unavailable-callout when not
- `rfq-overview-page.test.tsx` — insights sidebar shows/hides based on capability group health
- `risk-page.test.tsx` — governance narrative panel, facts visible without AI
- `reporting-page.test.tsx` — AI summary renders, degrades truthfully
- `use-ai-insights.test.ts` — hook: caches, refreshes, handles unavailable state

### Verification Commands

```bash
# Layer 1 + InsightOperations (monorepo root PHPUnit)
composer verify:atomy-q-ai-insights-governance-reporting

# WEB unit tests
cd apps/atomy-q/WEB && npm run test:unit --
  src/app/\(dashboard\)/page.test.tsx
  src/app/\(dashboard\)/rfqs/[rfqId]/overview/page.test.tsx
  src/app/\(dashboard\)/rfqs/[rfqId]/risk/page.test.tsx
  src/app/\(dashboard\)/reporting/page.test.tsx
  src/hooks/use-ai-insights.test.ts`

# E2E (alpha journeys)
cd apps/atomy-q/WEB && npm run test:e2e -- tests/ai-insights-e2e.spec.ts
```

---

## Alpha Release Gate — Insights & Governance

| Gate | Required Evidence |
|---|---|
| `insight_intelligence` | Staging provider contract test passes; dashboard & RFQ insights show provenance; clearly non-authoritative labeling |
| `governance_intelligence` | Staging provider contract test passes; narrative is clearly separated from authoritative governance facts; audit trail present |
| Truthful degradation | All 4 surfaces show ai-unavailable-callout when capability group degraded; no fake summaries |
| Cache invalidation | TTL expires → regenerate; event fires → cache cleared; manual refresh → new narrative |

---

## Key Constraint

**Governance AI never becomes source-of-truth.**
Sanctions flags, compliance gaps, ESG scores — these are authoritative facts from Layer 1 packages.
AI narrative only *explains* them. If governance AI is unavailable, facts remain visible and actionable.

---

## Source References

- [2026-04-23-atomy-q-ai-plan-index.md](/home/azaharizaman/dev/atomy/docs/superpowers/plans/2026-04-23-atomy-q-ai-plan-index.md)
- [2026-04-23-atomy-q-ai-insights-governance-and-reporting.md](/home/azaharizaman/dev/atomy/docs/superpowers/plans/2026-04-23-atomy-q-ai-insights-governance-and-reporting.md)
- [2026-04-25-atomy-q-alpha-release-handoff-design.md](/home/azaharizaman/dev/atomy/docs/superpowers/specs/2026-04-25-atomy-q-alpha-release-handoff-design.md)
- [2026-04-24-atomy-q-ai-verification-context-design.md](/home/azaharizaman/dev/atomy/docs/superpowers/specs/2026-04-24-atomy-q-ai-verification-context-design.md)
