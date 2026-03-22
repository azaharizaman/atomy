# Atomy-Q — Technical Valuation Report (Initial)

**Audience:** Internal investors, product leadership, engineering finance  
**Scope:** Technical asset assessment — reproduction cost framing and value proposition  
**Date:** 2026-03-22  
**Repository:** `atomy` (Nexus monorepo)  
**Product:** Atomy-Q — quote comparison / RFQ workspace SaaS (`apps/atomy-q/`)

---

## Disclaimer

This document is **not** financial, tax, or legal advice. It does **not** estimate equity value, enterprise value, or fair market value. Those depend on revenue, contracts, market conditions, team, and capital structure.

It **does** provide a **technical** basis for:

- **Reproduction cost** (order-of-magnitude engineering effort to rebuild comparable capability), including **calculation methods** and an **illustrative numeric example** (§5.5–5.6).
- **Dependency and maintenance footprint** (what must be sustained to operate the product).
- **Value proposition** (what the asset enables for a buying organization and what differentiates it technically).

Any **dollar figures** in §5.6 use **hypothetical** loaded-cost assumptions for **template purposes only** — substitute your organization’s finance-approved numbers.

Numbers below are **repo-derived snapshots** unless noted; re-run scripts before major decisions.

---

## 1. Executive summary

**Atomy-Q** is a **multi-tenant B2B SaaS** for **buying organizations** running **RFQ → quote intake → normalization → comparison → approval → award** style workflows. It consists of:

- A **Laravel 12** API (`apps/atomy-q/API`) on **PHP 8.3**, integrated with **46 direct Nexus (`nexus/*`) packages** spanning identity, tenant isolation, procurement operations, compliance hooks, project/task management, and Laravel adapters.
- A **Next.js** desktop-oriented web app (`apps/atomy-q/WEB`) using **React 19**, **TanStack Query**, **Zod**, **OpenAPI-generated** HTTP client, **Vitest**, and **Playwright**.

**Reproduction cost** must include **three layers**: (1) product-specific API + WEB code, (2) **direct Nexus packages** used by the API (not optional if behavior is preserved), and (3) operational concerns (tests, docs, CI, security review). **Nexus platform R&D** beyond this slice may be accounted as **separate CapEx**, but **does not remove** the need to count **direct** Nexus packages in Atomy-Q’s total cost of ownership.

**Maturity:** The repo documents an **alpha / design-partner** posture: core flows are supported; many workspace areas are **scaffolded or partial**. Investor-facing narratives should align with `apps/atomy-q/ALPHA_DESIGN_PARTNER_SUPPORTED_FLOWS.md` and `WEB/IMPLEMENTATION_STATUS.md`.

---

## 2. Product and problem statement

### 2.1 Problem

Enterprise procurement teams need **traceable, comparable, approvable** sourcing decisions across **RFQs, vendor quotes, and internal governance** — not ad-hoc spreadsheets and email.

### 2.2 What Atomy-Q delivers (technical)

- **Tenant-scoped** data and APIs (wrong-tenant access designed to avoid existence leaks — see `AGENTS.md`).
- **RFQ lifecycle** APIs: list/create/detail/overview, line items, templates, vendor touchpoints, quote submissions, normalization, comparison runs (preview/final), scoring models/policies, approvals (including bulk), and related domains (negotiations, awards, documents, reports, integrations, projects/tasks) as exposed in `apps/atomy-q/API/routes/api.php`.
- **Buyer-focused** alpha: **no vendor self-service portal** requirement in alpha; email/password + JWT flows documented.

---

## 3. Technical inventory (measured)

### 3.1 Application code (product-specific)

| Asset | Approximate measure | Notes |
|--------|---------------------|--------|
| WEB `src` (TS/TSX) | **~21,725** lines | Excludes `node_modules`; includes app routes, hooks, components |
| API `app` (PHP) | **~10,990** lines | Laravel controllers, services, models scoped to Atomy-Q API |
| WEB source files | **~132** TS/TSX files under `WEB/src` | Order-of-magnitude |
| API `app` PHP files | **~109** files | Order-of-magnitude |

### 3.2 Tests and quality signals

| Area | Approximate measure |
|------|---------------------|
| PHPUnit tests (`API/tests`) | **~28** test files |
| WEB unit + E2E (`*.test.*`, `*.spec.ts`, excluding `node_modules`) | **~21** files |

**Interpretation:** There is **non-zero** automated coverage and workflow tests for critical paths (e.g. comparison snapshot, quote submission, ACL/projects). Coverage is **not** claimed to be complete; due diligence should map tests to critical user journeys.

### 3.3 Stack highlights

- **Backend:** Laravel 12, JWT (`firebase/php-jwt`), Redis client (`predis`), OpenAPI/Scramble in dev for contract documentation.
- **Frontend:** Next.js 16, React 19, TanStack Query, Zustand, react-hook-form + Zod, Tailwind 4, Radix slot, Playwright + Vitest.

---

## 4. Nexus dependency accounting

### 4.1 Two different questions

| Question | Answer |
|----------|--------|
| Is **Nexus platform** R&D a **separate CapEx** line from Atomy-Q? | **Yes** — horizontal platform work and unused packages are not “Atomy-Q-only.” |
| Must **direct Nexus packages** used by `atomy-q-api` be **counted into** Atomy-Q reproduction and run cost? | **Yes** — they are **required** dependencies for current behavior. |

### 4.2 Direct `nexus/*` dependencies (46 packages)

Declared in `apps/atomy-q/API/composer.json` `require` block:

**Layer 1 — `packages/` (32):**  
`common`, `identity`, `sso`, `crypto`, `tenant`, `setting`, `feature-flags`, `procurement`, `party`, `document`, `storage`, `notifier`, `audit-logger`, `event-stream`, `sequencing`, `currency`, `uom`, `sanctions`, `aml-compliance`, `compliance`, `reporting`, `query-engine`, `export`, `connector`, `scheduler`, `machine-learning`, `workflow`, `messaging`, `project`, `task`

**Layer 2 — `orchestrators/` (10):**  
`identity-operations`, `settings-management`, `tenant-operations`, `quotation-intelligence`, `procurement-operations`, `compliance-operations`, `connectivity-operations`, `data-exchange-operations`, `insight-operations`, `project-management-operations`

**Layer 3 — `adapters/Laravel/` (4):**  
`laravel-identity-adapter`, `laravel-notifier-adapter`, `laravel-tenant-adapter`, `laravel-setting-adapter`

### 4.3 Size of the direct Nexus footprint (PHP, repo-measured)

For the directory paths corresponding to the above packages only, **`*.php`** excluding `vendor` / `node_modules`:

| Metric | Value |
|--------|--------|
| PHP files | **~2,278** |
| Lines of PHP | **~51,454** |

**Notable concentration:** `orchestrators/ProcurementOperations` is the largest single subtree (hundreds of PHP files) — aligned with RFQ/quoting domain depth.

**Transitive Nexus packages:** Packages may pull additional `nexus/*` dependencies via their own `composer.json`. This report uses **direct** API `require` only. For auditor-grade dependency closure, run Composer’s dependency tree from `apps/atomy-q/API`.

---

## 5. Reproduction cost (technical methodology)

### 5.1 Definition

**Reproduction cost** here means: **engineering effort to deliver a comparable system** — multi-tenant API with similar domain coverage, a modern web client, test hooks, and documentation — **excluding** commercial licensing, sales, and ongoing operations staff.

### 5.2 Components to include

1. **Product layer:** Rebuild `WEB/src` + `API/app` behavior (or equivalent).
2. **Direct Nexus layer:** Re-implement or replace **~51k LOC** of PHP domain/orchestration/adapters **or** continue to treat Nexus as the shared asset (strategic choice).
3. **Integration & hardening:** Auth, tenant isolation, API consistency, OpenAPI client generation, CI, security review (`WEB/docs/SECURITY_REVIEW_AUTH_AND_TENANTS.md` pattern).
4. **Verification:** PHPUnit + Playwright/Vitest parity for risk reduction.

### 5.3 Order-of-magnitude effort (illustrative, not a quote)

Use **person-months** × **loaded cost per FTE-month** for internal budgeting. Ranges depend on team skill mix and whether Nexus is reused.

| Scenario | Narrative range | When it applies |
|----------|------------------|-----------------|
| **A — Reuse Nexus (current model)** | Product-focused rebuild: **many person-months** for WEB + API + tests + gaps | Greenfield team **with** Nexus as dependency |
| **B — No Nexus (full greenfield)** | **Multiple person-years** for domain + multi-tenancy + procurement depth | Spin-out or vendor rebuild **without** monorepo |
| **C — Thin MVP** | Lower bound **only** if scope is **explicitly** cut to a subset of routes and screens | Not equivalent to full Atomy-Q as documented |

**Why ranges are wide:** Domain complexity (normalization, comparison, approvals), security bar (tenant isolation), and **alpha vs production** UI depth drive variance.

### 5.4 What reproduction cost is *not*

- It is **not** the same as **market valuation** or **ARR multiples**.
- It is **not** a guarantee that a third party could rebuild faster (learning curve, undocumented assumptions).

### 5.5 Valuation calculation method (replacement cost)

This report uses **replacement cost** as the quantified “technical valuation” figure: the estimated cost to recreate **comparable** capability, not fair market value of the business.

#### Primary method: person-month build-up (recommended)

1. **Decompose** the work into streams (examples): product WEB, product API, integration/hardening, automated tests, documentation, migration/data model alignment.
2. **Estimate person-months (PM)** per stream using engineering judgment, historical velocity, or phased estimates. Prefer **ranges** (low / base / high).
3. **Attribute Nexus** explicitly:
   - **Reuse Nexus (current model):** attribute **0 PM** to re-writing the **direct Nexus footprint** (it is already built); include only integration PM as needed.
   - **Greenfield without Nexus:** add PM to replace **~51,454** PHP LOC of direct Nexus packages (or license/acquire equivalent capability — not modeled here).
4. **Apply uncertainty:** optional multipliers (e.g. **1.0 / 1.15 / 1.30**) for alpha gaps, undocumented behavior, or schedule risk.
5. **Convert to currency:**

   **Replacement cost estimate = (Σ PM_i) × C_loaded,month**

   where **C_loaded,month** is the **fully loaded** cost per engineer-month (salary + benefits + overhead + tools). Finance should supply **C**; do not treat public salary guesses as authoritative.

6. **Optional — contingency:** add a fixed **%** or **PM** for security review, compliance, and production hardening if not already in the streams.

#### Secondary method: LOC cross-check (sanity only)

Use when you lack bottom-up PM estimates but need a **ballpark**. **Do not** treat this as precise: domain complexity, test code, and glue logic break naive LOC/month rules.

Let **L** be measured lines of code in scope (product-only or product + Nexus). Pick a **net productivity** **ρ** (rho) = effective new/changed production LOC per engineer-month for your stack and maturity (typical planning bands for complex B2B SaaS often fall in **~200–600** LOC/month once reviews, tests, and rework are included — calibrate internally).

   **PM_LOC ≈ L ÷ ρ**  
   **Replacement cost (LOC) ≈ PM_LOC × C_loaded,month**

**Cross-check using this report’s snapshots (product layer only):**

| Bucket | Approx. LOC (see §3.1, §4.3) |
|--------|------------------------------|
| WEB `src` | ~21,725 |
| API `app` | ~10,990 |
| **Product subtotal** | **~32,715** |
| Direct Nexus PHP (if rebuilt) | ~51,454 |
| **Full greenfield subtotal (product + direct Nexus)** | **~84,169** |

Example cross-check at **ρ = 400** LOC/month: product-only **PM_LOC ≈ 32,715 / 400 ≈ 82 PM**; product + direct Nexus **≈ 210 PM**. These are **upper-bound style** checks if you assumed everything is typed from scratch with no reuse—actual programs are lower when reusing frameworks and Nexus.

---

### 5.6 Worked example (illustrative — hypothetical inputs)

**Purpose:** Show how the formulas in §5.5 combine into a **single number** for discussion. **Replace every assumption** with numbers approved by your finance and engineering leads.

**Assumed loaded cost (illustrative only):**  
**C_loaded,month = USD 15,000** per fully loaded engineer-month (fictitious round number for arithmetic).

#### Example A — Reuse Nexus; rebuild Atomy-Q product layer + hardening only

Engineering workshop assumes **base** effort:

| Stream | PM (base) | Notes |
|--------|-----------|--------|
| WEB (Next.js) | 14 | Screens, hooks, integration with API |
| API (Laravel surface) | 9 | Controllers, tenant wiring, domain calls into Nexus |
| Integration, CI, OpenAPI client | 3 | Pipelines, codegen, env parity |
| Tests (PHPUnit + Playwright/Vitest uplift) | 5 | Journey coverage, not exhaustive |
| Docs + security review prep | 2 | Internal runbooks, checklist alignment |
| **Subtotal** | **33** | Nexus libraries **not** rewritten |

Apply **schedule/alpha uncertainty** multiplier **m = 1.15**:

   **PM_adj = 33 × 1.15 ≈ 37.95 → 38 PM** (rounded)  
   **Replacement cost_A ≈ 38 × USD 15,000 = USD 570,000**

**Interpretation:** Under these **illustrative** assumptions, the **order of magnitude** for replacing the **Atomy-Q application code path** while **keeping Nexus as-is** is **~USD 0.5M** loaded engineering spend. Sensitivity: at **C = USD 12k/month**, same PM → **~USD 456k**; at **C = USD 20k/month** → **~USD 760k**.

#### Example B — Full greenfield (no Nexus); rebuild product + direct Nexus footprint

Two ways to combine (use one, not both):

1. **LOC cross-check (§5.5 secondary):** **L ≈ 84,169**, **ρ = 400** → **PM_LOC ≈ 210** → at **USD 15k/month** → **≈ USD 3.15M**.
2. **Build-up:** workshop adds **~120 PM** for re-implementing procurement orchestration + shared packages (fictitious single-line rollup — real plans need a WBS).

Using **PM = 210** and **no multiplier**:

   **Replacement cost_B ≈ 210 × USD 15,000 = USD 3,150,000**

**Interpretation:** Spin-out or “no Nexus” rebuild is **multi-million dollar** loaded engineering **before** sales, infra, or ongoing ops — consistent with “multiple person-years” in §5.3.

#### Summary table (same illustrative C = USD 15k/month)

| Scenario | PM used | Formula | Illustrative result |
|----------|---------|---------|---------------------|
| **A** Reuse Nexus | 38 (after m=1.15) | PM × C | **~USD 570k** |
| **B** Greenfield (LOC check) | 210 | PM × C | **~USD 3.15M** |

**Not double-counting:** Example A **excludes** Nexus reproduction; Example B **includes** a Nexus-sized LOC allowance. Do **not** add A + B.

---

## 6. Value proposition (technical and strategic)

### 6.1 For the buying organization (customer value)

- **Governed sourcing:** Structured RFQ data, comparison runs, and approval artifacts reduce ad-hoc risk.
- **Traceability:** Event/audit-oriented packages and decision trails support **compliance-minded** buyers (implementation depth varies by screen/API).
- **Multi-tenancy:** Designed for **SaaS isolation** rather than single-tenant scripts.

### 6.2 For the enterprise / investor (asset value)

- **Depth in procurement vertical:** Large API surface and `ProcurementOperations` footprint are **hard to replicate quickly** without domain investment.
- **Architecture discipline:** Three-layer Nexus model and strict PHP standards reduce long-term entropy **if** maintained.
- **Composable platform:** Shared packages accelerate **other** products; Atomy-Q benefits from cross-product investment **when** prioritized.
- **Contract-first client:** OpenAPI generation supports **API stability** and faster UI iteration.

### 6.3 Differentiators vs generic “CRUD SaaS”

- **Quote intelligence and comparison workflows** (not just document storage).
- **Normalization and scoring** paths in API design.
- **Compliance-related** package integration (sanctions/AML/compliance packages in dependency set — actual product exposure depends on UI/API wiring).

---

## 7. Risks and gaps (technical)

| Risk | Impact | Mitigation direction |
|------|--------|---------------------|
| **Alpha / partial UI** | Demo vs reality gap | Roadmap tied to `ALPHA_DESIGN_PARTNER_SUPPORTED_FLOWS.md` |
| **Stubbed workspace sections** | User confusion if oversold | Scope labeling in sales/investor decks |
| **ESLint / generated client** | CI quality gate friction | Exclude or tune rules for `generated/`; track generator upgrades |
| **Nexus coupling** | Spin-out or acquirer needs package rights | Legal/IP clarity on Nexus reuse |
| **Transitive Nexus deps** | Under-estimated footprint | Composer lockfile / `composer why` analysis |

---

## 8. Recommended next steps (for a stronger valuation pack)

1. **Transitive closure:** Export `composer show -t` from `apps/atomy-q/API` and attach **full** `nexus/*` tree.
2. **Test-to-journey map:** Table of **critical user journeys** × **automated test** × **manual** gap.
3. **Production readiness scorecard:** Security review completion, observability, SLOs, DR, on-call.
4. **Finance bridge:** Pair this document with **revenue, pipeline, and cost of goods** for any **equity** discussion.

---

## 9. References (in-repo)

| Document | Purpose |
|----------|---------|
| `AGENTS.md` | Agent mandates; tenant isolation expectations |
| `docs/project/ARCHITECTURE.md` | Three-layer architecture |
| `apps/atomy-q/ALPHA_DESIGN_PARTNER_SUPPORTED_FLOWS.md` | Alpha scope |
| `apps/atomy-q/API/README.md` | API / OpenAPI |
| `apps/atomy-q/WEB/README.md` | WEB client generation |
| `apps/atomy-q/WEB/IMPLEMENTATION_STATUS.md` | UI depth |
| `apps/atomy-q/WEB/BACKEND_API_GAPS.md` | API/UI gaps |

---

## Document history

| Version | Date | Notes |
|---------|------|--------|
| 1.0 | 2026-03-22 | Initial technical valuation report (reproduction cost + value proposition); lives under `docs/investor/`. |
| 1.1 | 2026-03-22 | Added §5.5 calculation method (build-up + LOC cross-check) and §5.6 illustrative worked examples with formulas. |
