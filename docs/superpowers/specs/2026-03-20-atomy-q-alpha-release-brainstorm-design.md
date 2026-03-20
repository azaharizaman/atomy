# Atomy-Q SaaS — Alpha Release Brainstorm (Strategic Design)

**Date:** 2026-03-20  
**Branch:** `docs/atomy-q-alpha-brainstorm-2026-03-20`  
**Status:** Brainstorm / pre-alpha scope definition (not implementation-approved)

---

## 1. Purpose

Align engineering and product on what **“alpha”** means for **Atomy-Q** (Laravel API + Next.js WEB) and which workstreams most reduce risk before inviting first users. This document synthesizes existing repo signals (`PLAN.md`, `WEB/IMPLEMENTATION_STATUS.md`, `API/IMPLEMENTATION_SUMMARY.md`, `WEB/BACKEND_API_GAPS.md`, `PROJECTS_AND_TASKS_ROLLOUT_PLAN.md`, `PACKAGE_TEST_READINESS.md`).

---

## 2. Current state (facts from the codebase)

| Area | State |
|------|--------|
| **API** | 203 endpoints registered; many responses are **stubs** with correct status/shape. Auth: JWT login; forgot-password **501**; SSO path exists with adapters; RBAC catalog **stubbed**. |
| **WEB** | Auth + dashboard shell + settings placeholders; **RFQ** list/detail **partial**; vendor, quote intake, approvals **not started**; API client **manual Axios**; **OpenAPI generation pending**. |
| **Contract drift** | `BACKEND_API_GAPS.md` still lists overview KPIs, activity feed, optional sidebar counts as **open or partial**. |
| **Broader monorepo** | Several Nexus packages used by adapters lack CI confidence (`PACKAGE_TEST_READINESS.md` notes failures/gaps). |

**Implication:** Alpha is not “ship all 203 endpoints”—it is **a credible, tenant-safe vertical slice** plus **minimum operability** (signup/login, data isolation, recoverability, basic support).

---

## 3. What alpha could mean (working definitions)

Pick one primary bar; they imply different ordering:

1. **Internal alpha** — Engineering + design can run full scripted demos on staging; no SLAs; known gaps documented.
2. **Design-partner alpha** — 1–3 external orgs on real data; strict tenant isolation; **forgot password**, audit-friendly actions, and a **short list** of supported flows only.
3. **Feature-complete alpha (risky)** — Broad surface (vendors, normalization, comparison, awards) before depth—likely slips on quality and security review.

**Recommendation:** Target **design-partner alpha** as the north star but **sequence** work as **internal alpha first** (prove the slice end-to-end, then widen).

---

## 4. Three strategic approaches

### Approach A — **Vertical slice: “Happy-path RFQ → quote → compare → approve → award”**

- **Idea:** Implement **real** persistence and WEB UX for one procurement journey; leave peripheral domains stubbed or hidden in nav.
- **Pros:** Fastest path to **demoable product**; forces API + WEB contract alignment; surfaces real multi-tenancy bugs early.
- **Cons:** Leaves **settings, billing, integrations** thin; may need feature flags to hide unfinished areas.

### Approach B — **Platform-first: identity, tenancy, OpenAPI, observability, CI**

- **Idea:** Harden **login/session**, password reset, RBAC minimum, published OpenAPI, generated client, metrics/logs, and adapter test health **before** deep domain UI.
- **Pros:** Safer for **any** external user; reduces contract drift; easier onboarding for more developers.
- **Cons:** Slower visible product progress; risk of “infrastructure forever” without a forcing function from Approach A.

### Approach C — **Balanced dual-track (recommended)**

- **Track 1 (product):** Approach A slice with explicit **MVP screen list** from Screen Blueprint / `QUOTE_COMPARISON_FRONTEND_BLUEPRINT_v2.md` (RFQ list → detail → workspace tabs that matter for alpha only).
- **Track 2 (platform):** Non-negotiables for external alpha: **tenant-scoped queries everywhere**, **forgot-password + email**, **OpenAPI + codegen** (per `PLAN.md`), **Playwright smoke** on that slice, and **security pass** on auth/tenants (`WEB/docs/SECURITY_REVIEW_AUTH_AND_TENANTS.md` as checklist).
- **Defer by default:** Projects/tasks Phase 2+ UI breadth, full vendor portal, advanced normalization/scoring, unless a design partner requires them.

**Recommendation:** **Approach C** — it matches the repo’s own frontend plan (OpenAPI + Query + auth first) while forcing a **thin end-to-end** story for alpha narrative.

---

## 5. Proposed alpha scope (engineering-facing)

### 5.1 Must-have (suggested)

- **Identity:** Forgot-password flow (end email, token, reset); document SSO as beta if not fully productized.
- **Contract:** OpenAPI as source of truth; regenerate TS client in CI or documented gate; normalize error envelope in WEB (`PLAN.md` §3.4).
- **RFQ core:** List + detail + create/edit aligned with implemented filters/pagination; overview KPIs and **activity** per `BACKEND_API_GAPS.md` (prefer dedicated `.../activity` if payload size matters).
- **Quotes & comparison (minimal):** Enough real data to show **quote intake** and a **comparison matrix** run—even if scoring is simplified.
- **Approvals:** At least one approval path wired (API + UI) or explicitly **out of alpha** with UI hidden and docs updated.
- **Quality:** Playwright flows: login, RFQ list, create RFQ, open detail; API feature tests on tenant isolation for touched endpoints.
- **Ops:** Staging deploy story, env template, health check; no production promises.

### 5.2 Explicit non-goals for alpha (unless partner demands)

- Full **203-endpoint** parity with rich stubs.
- Complete **vendor self-service** portal.
- **Projects/tasks** Gantt and full ACL matrix (follow `PROJECTS_AND_TASKS_ROLLOUT_PLAN.md` for later phases).
- Billing/subscription (unless alpha is sales-led and required).

### 5.3 Package / CI hygiene (parallel, time-boxed)

- Fix or quarantine **blocking** package test failures called out in `PACKAGE_TEST_READINESS.md` that sit on **Atomy-Q critical paths** (e.g. FeatureFlags fatal if used in app paths).
- Do **not** boil the ocean on all L1 packages before alpha slice lands.

---

## 6. Sequencing (rough phases)

| Phase | Focus | Exit signal |
|-------|--------|-------------|
| **P0** | OpenAPI publish + generated client + error normalization | WEB hooks use generated types; less manual DTO drift |
| **P1** | RFQ vertical slice (API real + WEB) + activity/overview gaps closed | Scripted demo without stubs on core reads/writes |
| **P2** | Quote intake + comparison minimal + approvals (or cut) | One complete “sourcing event” narrative |
| **P3** | Forgot-password + security checklist + staging | Safe for first external tenant |
| **P4** | Optional: sidebar RFQ counts, reporting placeholders → real charts | Polish for narrative, not gate |

Phases can overlap; **P0 and P1** should start in parallel if two contributors are available.

---

## 7. Risks & mitigations

| Risk | Mitigation |
|------|------------|
| Stub endpoints masquerade as “done” | Feature flags + nav hiding + alpha “supported flows” doc |
| Cross-tenant leakage | Tenant-scoped existence checks (404 pattern per AGENTS.md); tests per resource type |
| Contract drift | OpenAPI CI diff; MSW fixtures from schema |
| Scope creep (projects/tasks) | Keep Phase 2 rollout plan as **post-alpha** unless explicitly pulled in |

---

## 8. Open decisions (need product input)

1. **Primary alpha persona:** Buyer-only org vs vendors submitting quotes in the same alpha?
2. **Approvals:** In-scope for alpha or explicitly deferred?
3. **SSO:** Required for first external tenant or enterprise beta later?

---

## 9. Next step (process)

After product answers §8, run **`writing-plans`** skill to produce a dated implementation plan with file-level tasks and test gates—this brainstorm doc does **not** authorize implementation by itself.

---

## References (in-repo)

- `apps/atomy-q/PLAN.md`
- `apps/atomy-q/WEB/IMPLEMENTATION_STATUS.md`
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/WEB/BACKEND_API_GAPS.md`
- `apps/atomy-q/PROJECTS_AND_TASKS_ROLLOUT_PLAN.md`
- `apps/atomy-q/PACKAGE_TEST_READINESS.md`
- `apps/atomy-q/WEB/docs/SECURITY_REVIEW_AUTH_AND_TENANTS.md`
