# Vendor Management Plan Index

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Decompose the approved vendor-management spec into independently shippable implementation plans.

**Architecture:** The vendor-management scope is split into four slices so each can be implemented, verified, and reviewed without holding the entire alpha program in one branch. The sequence is intentionally dependency-ordered: vendor master foundation first, then requisition/RFQ selection constraints, then AI recommendation, then ESG/compliance/risk monitoring.

**Tech Stack:** PHP 8.3, Laravel, Next.js/React, TypeScript, TanStack Query, generated API client, Playwright, PHPUnit/Vitest.

---

## Plan Sequence

1. `docs/superpowers/plans/2026-04-21-vendor-master-foundation.md`
   - Adds the top-level vendor domain and buyer-side management surface.
   - Introduces vendor statuses, approval recording, vendor CRUD/list/detail APIs, and WEB vendor workspace.
   - Must land first because all later plans depend on an approved vendor master.

2. `docs/superpowers/plans/2026-04-21-vendor-selection-and-rfq-handoff.md`
   - Wires approved-vendor-only selection into requisitions and uses that selection to constrain RFQ invitations.
   - Depends on Plan 1.

3. `docs/superpowers/plans/2026-04-21-vendor-recommendation-engine.md`
   - Adds deterministic scoring plus bounded LLM enrichment for draft shortlist generation.
   - Depends on Plans 1 and 2.

4. `docs/superpowers/plans/2026-04-21-vendor-governance-monitoring.md`
   - Adds ESG/compliance/risk evidence, findings, scores, and warning surfaces.
   - Depends on Plan 1 and can be built in parallel with late stages of Plan 3 if interfaces stay stable.

## Dependency Rules

- Do not start requisition or RFQ vendor selection work until Plan 1 vendor status and listing APIs are stable.
- Do not start recommendation ranking work until Plan 2 selected-vendor persistence contract is stable.
- Do not let governance scores automatically mutate vendor eligibility in alpha; that remains a manual status action even after Plan 4.
- Keep tenant-scoped not-found semantics and approved-only selection guardrails consistent across all plans.

## Verification Gates

After each plan:

- run the plan-specific backend tests,
- run the affected WEB unit tests,
- update `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` and any affected package/adaptor implementation summaries,
- request review before starting the next plan.
