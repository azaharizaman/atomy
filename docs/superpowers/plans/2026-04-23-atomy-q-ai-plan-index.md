# Atomy-Q AI-First Delivery Plan Index

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement these plans task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Decompose the approved AI-first Atomy-Q operating architecture into dependency-ordered implementation plans that can be delivered, verified, and reviewed in manageable slices.

**Architecture:** The program is split into six plans. The sequence is intentional: establish the AI runtime contract first, then wire provider-backed quote intake and normalization, then recommendation, then comparison/award/approvals, then secondary AI surfaces, then operational launch hardening. This keeps the main RFQ chain moving without forcing one oversized branch to carry every change at once.

**Tech Stack:** PHP 8.3, Laravel, Nexus Layer 1 packages, Nexus Layer 2 orchestrators, Next.js/React, TypeScript, TanStack Query, generated API client, PHPUnit, Vitest, Playwright.

---

## Plan Sequence

1. `docs/superpowers/plans/2026-04-23-atomy-q-ai-foundation-and-runtime-governance.md`
   - Establishes the global AI runtime model, Hugging Face endpoint topology, capability registry, public status contract, WEB gating primitives, and truthful failure semantics.
   - Must land first because every later plan depends on stable AI capability and health contracts.

2. `docs/superpowers/plans/2026-04-23-atomy-q-ai-quote-intake-and-normalization.md`
   - Adds provider-backed document intelligence and normalization intelligence while preserving manual continuity for quote intake and line mapping.
   - Depends on Plan 1.

3. `docs/superpowers/plans/2026-04-23-atomy-q-ai-sourcing-recommendation-and-vendor-decision-support.md`
   - Makes vendor recommendation truly provider-backed in alpha and integrates recommendation provenance into vendor decision support while preserving manual vendor selection continuity.
   - Depends on Plans 1 and 2.

4. `docs/superpowers/plans/2026-04-23-atomy-q-ai-comparison-award-and-approval-intelligence.md`
   - Adds provider-backed comparison overlays, award guidance, approval drafting aids, and truthful degraded behavior while keeping deterministic comparison freeze and manual award flow usable.
   - Depends on Plans 1, 2, and 3.

5. `docs/superpowers/plans/2026-04-23-atomy-q-ai-insights-governance-and-reporting.md`
   - Extends AI-first behavior to dashboard insights, RFQ insight surfaces, governance/risk explanation surfaces, and reporting summaries without turning AI narrative into source-of-truth governance decisions.
   - Depends on Plan 1 and should start after the status/gating primitives are stable. It can overlap late stages of Plans 3 and 4 if interfaces stay stable.

6. `docs/superpowers/plans/2026-04-23-atomy-q-ai-launch-readiness-and-operational-hardening.md`
   - Closes the alpha program with observability, alerts, runbooks, operator-responsibility handoff, AI-off and degraded drills, release verification, and documentation alignment.
   - Depends on all previous plans.

## Dependency Rules

- `AI_MODE` is the alpha global control plane. No per-tenant AI toggle exists in alpha.
- Alpha default must be `provider`, not `deterministic`.
- The main RFQ chain must remain manually operable when provider-backed AI is disabled, degraded, or offline.
- AI-only surfaces must return truthful unavailable states. No controller, hook, or page may fabricate AI output.
- Layer 1 stays provider-agnostic. Hugging Face specifics belong in Layer 3 adapters and configuration.
- Tenant isolation, not-found semantics, idempotency, and audit provenance rules apply to every plan.
- Do not let narrative AI mutate authoritative governance, approval, or award records without deterministic validation and explicit human action.

## Cross-Plan Responsibilities

- `packages/MachineLearning` owns provider-neutral AI vocabulary, endpoint health, provider request/response contracts, and runtime capability state.
- `packages/ProcurementML` owns procurement-specific AI request/result vocabularies that should not live in framework code.
- `packages/Document`, `packages/Storage`, `packages/Audit`, `packages/AuditLogger`, `packages/Notifier`, `packages/Idempotency`, and `packages/Outbox` remain the source of truth for document handling, persistence references, evidence, notifications, safe retries, and post-commit event publication.
- `orchestrators/IntelligenceOperations` owns global runtime AI status aggregation.
- `orchestrators/QuotationIntelligence`, `orchestrators/QuoteIngestion`, `orchestrators/ProcurementOperations`, `orchestrators/ApprovalOperations`, and `orchestrators/InsightOperations` own workflow orchestration for their slices.
- `apps/atomy-q/API` owns env parsing, Hugging Face HTTP adapters, persistence models, routes, controllers, jobs, migrations, and OpenAPI exposure.
- `apps/atomy-q/WEB` owns capability-aware rendering, manual continuity UX, unavailable states, and screen-level AI affordances.

## Verification Gates

After each plan:

- run the plan-specific package and orchestrator tests,
- run affected API feature tests,
- run affected WEB unit tests,
- run targeted E2E coverage when the plan changes a full RFQ path,
- update the relevant `IMPLEMENTATION_SUMMARY.md` files,
- request review before starting the next dependency plan.

Before alpha release:

- prove provider-backed AI is live in staging for every alpha endpoint group,
- prove `AI_MODE=off` manual continuity for the main RFQ chain,
- prove degraded endpoint behavior yields truthful unavailable states,
- prove audit/provenance is persisted and reviewable for every AI-assisted action,
- prove no AI-only endpoint returns synthetic success when the provider path is unavailable.
