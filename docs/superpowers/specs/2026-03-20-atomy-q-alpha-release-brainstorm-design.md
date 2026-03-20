# Atomy-Q SaaS — Alpha Release Brainstorm (Strategic Design)

**Date:** 2026-03-20  
**Branch:** `docs/atomy-q-alpha-brainstorm-2026-03-20`  
**Status:** Product decisions in §3 and §8 are **complete**; scope locked for **design-partner alpha**. Implementation plan: [`../plans/2026-03-20-atomy-q-design-partner-alpha-implementation-plan.md`](../plans/2026-03-20-atomy-q-design-partner-alpha-implementation-plan.md).

---

## 1. Purpose

Align engineering and product on what **“alpha”** means for **Atomy-Q** (Laravel API + Next.js WEB) and which workstreams most reduce risk before inviting first users. This document synthesizes existing repo signals (`PLAN.md`, `WEB/IMPLEMENTATION_STATUS.md`, `API/IMPLEMENTATION_SUMMARY.md`, `WEB/BACKEND_API_GAPS.md`, `PROJECTS_AND_TASKS_ROLLOUT_PLAN.md`, `PACKAGE_TEST_READINESS.md`).

---

## 2. Current state (as of 2026-03-20)

Snapshot for planning; individual files may move faster than this table—verify in-repo docs for release gates.

| Area | State |
|------|--------|
| **API** | Large route surface with many stubs outside the alpha slice. **Email/password:** JWT login + **forgot-password / reset-password** (200 responses, tenant-scoped reset tokens). SSO adapters present. OpenAPI exposed via **Scramble** in dev (`config/scramble.php`, `/docs/api`). |
| **WEB** | Next.js app: JWT + refresh, RFQ list/workspace (**overview**, activity merge), global **approvals** queue/detail, dashboard shell; Axios client in `src/lib/api.ts` with optional generated types from `../openapi/openapi.json`. |
| **Contract drift** | Use `BACKEND_API_GAPS.md` as the living checklist for WEB/API field and endpoint parity. |
| **Broader monorepo** | Nexus package health summarized in `PACKAGE_TEST_READINESS.md` (CI gaps vary by package). |

**Implication:** Alpha is not “ship all 203 endpoints”—it is **a credible, tenant-safe vertical slice** plus **minimum operability** (signup/login, data isolation, recoverability, basic support).

---

## 3. What alpha could mean (working definitions)

Pick one primary bar; they imply different ordering:

1. **Internal alpha** — Engineering + design can run full scripted demos on staging; no SLAs; known gaps documented.
2. **Design-partner alpha** — 1–3 external orgs on real data; strict tenant isolation; **forgot password**, audit-friendly actions, and a **short list** of supported flows only.
3. **Feature-complete alpha (risky)** — Broad surface (vendors, normalization, comparison, awards) before depth—likely slips on quality and security review.

**Locked decision (product):** **Design-partner alpha (definition 2)** — 1–3 external orgs on real data.

**Locked decision (product):** **Buyer-only alpha** — partner users are **buying-org** accounts only. **Vendor self-service** (invites, vendor login, external submission portal) is **out of scope** for this alpha; quotes are captured via **buyer-side** intake (manual entry, upload, or internal tooling), documented in the supported-flows guide.

**Sequencing:** Still **internal proof first** (end-to-end slice on staging with the same security bar), then **onboard partners** only after forgot-password, tenant-isolation tests, and the security checklist pass. No “soft launch” without that gate.

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
- **Defer by default:** Projects/tasks Phase 2+ UI breadth, **vendor portal / vendor auth**, advanced normalization/scoring. (Buyer-only alpha explicitly excludes vendor-facing flows.)

**Recommendation:** **Approach C** — it matches the repo’s own frontend plan (OpenAPI + Query + auth first) while forcing a **thin end-to-end** story for alpha narrative.

---

## 5. Proposed alpha scope (engineering-facing)

### 5.1 Must-have (design-partner alpha)

- **Identity:** **Email/password + JWT** (existing) plus **forgot-password** (email, token, reset) — **blocking** for external users (`501` is not acceptable). **SSO/OIDC is not an alpha gate** (decision **B**): defer productizing SSO for design partners; keep or hide SSO entry points in WEB per UX choice, and state in the partner guide that alpha accounts are **email/password only**. Post-alpha: enterprise SSO when required.
- **Contract:** OpenAPI as source of truth; regenerate TS client in CI or documented gate; normalize error envelope in WEB (`PLAN.md` §3.4).
- **RFQ core:** List + detail + create/edit aligned with implemented filters/pagination; overview KPIs and **activity** per `BACKEND_API_GAPS.md` (prefer dedicated `.../activity` if payload size matters).
- **Quotes & comparison (minimal):** **Buyer-side** quote capture (intake UI and/or API used only by authenticated tenant users) plus a **comparison matrix** run—even if scoring is simplified.
- **Approvals (locked in-scope):** At least **one** end-to-end path: create or surface an approval request → act (approve/reject or equivalent) → persist state — **real API + WEB**, tenant-scoped. Full approval matrix / all 12 `ApprovalController` scenarios is **not** required; document the **supported** approval variant in the partner guide.
- **Quality:** Playwright flows: login, RFQ list, create RFQ, open detail, plus **one approval** path aligned with the supported variant; API feature tests on tenant isolation for touched endpoints (including approvals).
- **Ops:** Staging deploy story, env template, health check; no production promises.
- **Partner readiness:** Short **“supported flows”** doc for partners (what works, what is stubbed); lightweight **audit** on mutating actions where the product promises traceability (align with existing audit-adapter posture in API).

### 5.2 Explicit non-goals for alpha

- Full **203-endpoint** parity with rich stubs.
- **Vendor self-service** portal, vendor invitations as an external-facing workflow, and **vendor-authenticated** submission — **out of scope** for this alpha (buyer-only decision).
- **Projects/tasks** Gantt and full ACL matrix (follow `PROJECTS_AND_TASKS_ROLLOUT_PLAN.md` for later phases).
- Billing/subscription (unless alpha is sales-led and required).
- **SSO as required onboarding** for design partners — **out of scope** for alpha (email/password + forgot-password only).

### 5.3 Package / CI hygiene (parallel, time-boxed)

- Fix or quarantine **blocking** package test failures called out in `PACKAGE_TEST_READINESS.md` that sit on **Atomy-Q critical paths** (e.g. FeatureFlags fatal if used in app paths).
- Do **not** boil the ocean on all L1 packages before alpha slice lands.

---

## 6. Sequencing (rough phases)

| Phase | Focus | Exit signal |
|-------|--------|-------------|
| **P0** | OpenAPI publish + generated client + error normalization | WEB hooks use generated types; less manual DTO drift |
| **P1** | RFQ vertical slice (API real + WEB) + activity/overview gaps closed | Scripted demo without stubs on core reads/writes |
| **P2** | Quote intake + comparison minimal + **approvals** (one path) | One complete “sourcing event” narrative incl. approval |
| **P3** | Forgot-password + security checklist + staging (**partner gate**) | Safe for first external tenant; run before inviting orgs |
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
| Approvals scope ballooning | Ship **one** documented variant; defer multi-step policies and edge cases to post-alpha |
| Partner expects SSO | Partner guide states **email/password + forgot-password**; schedule SSO for **post-alpha** enterprise track |

---

## 8. Product decisions (complete)

| # | Topic | Choice |
|---|--------|--------|
| 1 | Alpha bar | **Design-partner alpha** — 1–3 external orgs on real data (original option **B**). |
| 2 | Persona | **Buyer-only** — no vendor portal / vendor auth for alpha (option **A**). |
| 3 | Approvals | **In-scope** — one end-to-end path, API + UI, documented variant (option **A**). |
| 4 | SSO | **Deferred** — alpha onboarding is email/password + forgot-password; SSO not required before first external tenant (option **B**). |

---

## 9. Decision log

| Date | Decision |
|------|----------|
| 2026-03-20 | Alpha bar: **B** — design-partner alpha (1–3 external orgs). |
| 2026-03-20 | Persona: **A** — buyer-only; vendor self-service out of scope for alpha. |
| 2026-03-20 | Approvals: **A** — in-scope; one end-to-end path (API + UI), documented variant only. |
| 2026-03-20 | SSO: **B** — deferred for alpha; email/password + forgot-password for design partners. |

---

## 10. Next step (process)

**Done:** [`../plans/2026-03-20-atomy-q-design-partner-alpha-implementation-plan.md`](../plans/2026-03-20-atomy-q-design-partner-alpha-implementation-plan.md). Execute that plan task-by-task; this brainstorm spec remains the **product** input only.

---

## 11. References (in-repo)

- `apps/atomy-q/PLAN.md`
- `apps/atomy-q/WEB/IMPLEMENTATION_STATUS.md`
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/WEB/BACKEND_API_GAPS.md`
- `apps/atomy-q/PROJECTS_AND_TASKS_ROLLOUT_PLAN.md`
- `apps/atomy-q/PACKAGE_TEST_READINESS.md`
- `apps/atomy-q/WEB/docs/SECURITY_REVIEW_AUTH_AND_TENANTS.md`
