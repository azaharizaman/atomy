# Atomy-Q Next Big Ticket Prioritization Report

**Date:** 2026-03-29  
**Scope:** Determine the next highest-value implementation ticket for Atomy-Q SaaS, based on Layer 1/2/3 readiness, API and WEB gaps, dead/stub code, design system maturity, documentation drift, and integration readiness.

## Executive Summary

The next big ticket is **Quote Lifecycle Productionization**: take the existing RFQ -> quote intake -> normalization -> comparison -> award/approval flow from a partially seeded/demo experience to a fully live, tenant-safe, auditable product flow.

**Update 2026-03-31:** the quote lifecycle slice has now been materially productionized in the API and WEB tree. The next highest-value ticket has shifted to **Vendor Management and Invitation Lifecycle**.

This is the highest-leverage item because:

1. The WEB app already exposes the intended buyer journey, but the most valuable screens are still seed-backed or mock-backed.
2. The API already has most of the data model and workflow structure, but several adjacent controllers still carry stub payloads, `501 Not Implemented`, or tenant-scoping TODOs.
3. The first-party L1 packages that should underpin this flow already exist in the package reference guide, which means the work is mostly orchestration, adapter wiring, and productization rather than new primitive creation.
4. This ticket directly pushes Atomy-Q from "structured prototype" toward "pilotable SaaS".

If only one thing gets funded next, fund ticket #1 below.

> Historical note: the ranked list below reflects the 2026-03-29 analysis that produced this report. The 2026-03-31 update above supersedes the “next ticket” recommendation after the quote lifecycle work landed.

## Ranked Tickets

| Rank | Big Ticket | Why it matters | Primary layers | Suggested timing |
|---|---|---|---|---|
| 1 | Quote lifecycle productionization | Converts the main business workflow into a live tenant-safe product | L1, L2, L3 | Immediate next sprint |
| 2 | Vendor management and invitation lifecycle | Removes the last major dependency for real quote collection at scale | L2, L3 | In parallel or immediately after #1 |
| 3 | Design-System-V2 consolidation | Reduces page-level duplication and speeds delivery of remaining screens | L3 | Parallel with workflow work |
| 4 | API stub removal and tenant hardening | Replaces synthetic IDs, `501`s, and tenant-scoping TODOs with production contracts | L3, plus service boundaries | Before external alpha expansion |
| 5 | Connectivity and integration backbone | Enables real email, document exchange, and outbound/inbound third-party flows | L1, L2, L3 | After core workflow is stable |
| 6 | Identity and governance hardening | Removes remaining no-op auth/permissions surfaces and tightens approval controls | L2, L3 | Before broader tenant rollout |
| 7 | Docs, tests, and seed/live parity | Keeps the implementation truthful as the flow matures | All layers | Continuous, but batch with each ticket |

## 1. Quote Lifecycle Productionization

**Why**

This is the core product loop for Atomy-Q. The web app already has RFQ list, quote intake, normalization, comparison runs, award, and approval surfaces, but the most commercially important parts are still partially driven by seeds or mock data:

- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/approvals/page.tsx`

The backend already has the structural pieces for quote submission, normalization, comparison snapshots, decision trails, and approval gating, so the missing value is not concept design. It is end-to-end live wiring.

**How**

Use the existing first-party primitives rather than inventing new ones:

- L1: `Idempotency`, `Outbox`, `PolicyEngine`, `Document`, `Storage`, `Notifier`, `Crypto`, `EventStream`
- L2: `QuotationIntelligence`, `ApprovalOperations`, `DataExchangeOperations`, `ConnectivityOperations`
- L3: `apps/atomy-q/API` controllers, requests, resources, jobs, and Laravel adapters

Implementation should focus on:

1. Making quote ingestion and normalization fully live in the API and WEB layers.
2. Replacing seed-backed comparison and award screens with live API queries.
3. Making approval decisions policy-driven and audit-linked.
4. Emitting durable events and notifications after commit so the workflow can scale beyond a single request cycle.

**Where**

- API controllers with live persistence and response shaping:
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/NormalizationController.php`
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/AwardController.php`
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php`

- WEB surfaces that still need live data and mutation wiring:
  - `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/**`
  - `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/**`
  - `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/**`
  - `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/approvals/**`
  - `apps/atomy-q/WEB/src/hooks/use-quote-submission.ts`
  - `apps/atomy-q/WEB/src/hooks/use-normalization-review.ts`
  - `apps/atomy-q/WEB/src/hooks/use-freeze-comparison.ts`

- Docs that should be updated when the workflow is complete:
  - `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
  - `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
  - `apps/atomy-q/WEB/BACKEND_API_GAPS.md`

**When**

Immediately. This is the best next sprint candidate because it unlocks the most user-visible value and it exercises almost every layer of the stack.

## 2. Vendor Management and Invitation Lifecycle

**Why**

Quote intake does not scale without a real vendor master and invitation lifecycle. The WEB vendor page is present, but the product still needs a fully live roster, invite state, and outreach workflow. This is the adjacent business capability that turns a single RFQ workflow into a repeatable procurement system.

**How**

- Model vendors and invitations as real tenant-scoped records.
- Use `Notifier` and `Connector`-style flows for invitations and follow-up communications.
- Keep tenant filtering explicit at every query boundary.
- Connect invitation status to quote intake so the UI can show who has been invited, responded, or needs follow-up.

**Where**

- API:
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorController.php`
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorInvitationController.php`
- WEB:
  - `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
  - `apps/atomy-q/WEB/src/hooks/use-rfq-vendors.ts`

**When**

Parallel with ticket #1 if there is separate ownership; otherwise immediately after the core quote lifecycle work lands.

## 3. Design-System-V2 Consolidation

**Why**

The app already uses the DS-V2 primitives, but delivery is still slowed by page-level composition and repeated layout logic. The existing RFQ and workspace screens are already visually coherent, so the next step is not a redesign. It is consolidation:

- reduce duplicated page shells,
- standardize empty, loading, and error states,
- extract repeatable record layouts and timeline patterns,
- make the workspace surfaces easier to extend.

This will matter more as the quote lifecycle grows to include richer normalization, comparison, and award UIs.

**How**

- Treat `src/components/ds/*` as the only approved primitive surface.
- Move repeated patterns from pages into reusable DS/workspace components.
- Unify breadcrumb and rail behavior so the dashboard and workspace layouts stay in sync.
- Finish the components that support dense enterprise screens first: tables, record headers, badges, tabs, timelines, and filter bars.

**Where**

- `apps/atomy-q/WEB/src/components/ds/*`
- `apps/atomy-q/WEB/src/components/workspace/*`
- `apps/atomy-q/WEB/src/app/(dashboard)/*`
- `docs/design_system/IMPLEMENTATION_GUIDE.md`

**When**

In parallel with ticket #1. It is a force multiplier, not a blocker, but it will reduce the cost of every remaining screen.

## 4. API Stub Removal and Tenant Hardening

**Why**

The API still contains stub IDs, `501 Not Implemented` responses, and tenant-scoping TODOs on critical adjacent controllers. That is acceptable for a design partner alpha, but it is the wrong foundation to extend without cleanup.

Concrete examples include:

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/AwardController.php` returning `501`
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorController.php` containing tenant-scoping TODOs
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/IntegrationController.php` still emitting stub IDs / empty responses
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php` still has tenant-scoping TODOs in mutation paths

**How**

- Replace synthetic payloads with real Eloquent-backed records and API resources.
- Add form request validation where write paths still trust raw input.
- Enforce tenant-scoped existence checks instead of load-then-compare patterns.
- Delete or isolate dead stub paths so the app surface matches the supported product scope.

**Where**

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/*`
- `apps/atomy-q/API/app/Services/Tenant/*`
- `apps/atomy-q/API/app/Http/Requests/*`
- `apps/atomy-q/API/app/Http/Resources/*`

**When**

Before widening access beyond a constrained alpha. This is a safety and reliability gate, not just cleanup.

## 5. Connectivity and Integration Backbone

**Why**

Atomy-Q eventually needs real outbound communication and third-party integration behavior: vendor notifications, document exchange, workflow events, and possibly external intake channels. The current integration surface is not yet the backbone it needs to be.

This is where the platform should stop thinking in page-by-page interactions and start thinking in durable business events.

**How**

- Use `DataExchangeOperations` and `ConnectivityOperations` as the orchestration spine.
- Use `Outbox` for post-commit delivery guarantees.
- Use `Notifier` for email and operational messages.
- Use `Document` and `Storage` for file handling and quote evidence.
- Use `EventStream` if a dual-write event trail is required.

**Where**

- L1 packages:
  - `DataExchangeOperations`
  - `ConnectivityOperations`
  - `Outbox`
  - `Notifier`
  - `Document`
  - `Storage`
  - `EventStream`
- L3:
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/IntegrationController.php`
  - queue jobs, notification adapters, and any document ingestion adapters

**When**

After the core quote lifecycle is stable, or earlier if vendor communication is a gating requirement for pilot onboarding.

## 6. Identity and Governance Hardening

**Why**

The app has a working auth core, but several identity surfaces are still intentionally stubbed or no-op. That is fine for internal progress, but it becomes a liability as soon as the product broadens to more tenants or more operators.

The remaining weak points are predictable:

- no-op audit and MFA services,
- permission/role stubs,
- approval and policy enforcement that should not rely on page logic,
- settings and operational control surfaces that need clearer contracts.

**How**

- Replace `AtomyPermissionQueryStub`, `AtomyRoleQueryStub`, and no-op identity services with explicit backing or feature-gated behavior.
- Keep `PolicyEngine` and `ApprovalOperations` as the policy gate for sensitive actions.
- Ensure tenant isolation remains a first-class rule in every query path.

**Where**

- `apps/atomy-q/API/app/Services/Identity/*`
- `apps/atomy-q/API/app/Services/ApprovalOperations/*`
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/AccountController.php`
- `apps/atomy-q/WEB/src/app/(dashboard)/settings/*`

**When**

Before broader operator rollout or any compliance-sensitive customer expansion.

## 7. Docs, Tests, and Seed/Live Parity

**Why**

The repo already has a large amount of documentation and implementation summaries. That is useful only if it stays accurate. As the live workflow expands, the gap between docs, seed data, mock mode, and production behavior will widen unless it is managed intentionally.

**How**

- Update implementation summaries whenever behavior changes.
- Keep seed data aligned with the live API contract.
- Add regression tests for tenant isolation, live-vs-mock switches, and API response mapping.
- Explicitly mark what is seed-only versus live so the pilot scope remains trustworthy.

**Where**

- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/WEB/BACKEND_API_GAPS.md`
- `docs/atomy-q/STAGING.md`
- `docs/project/NEXUS_PACKAGES_REFERENCE.md`

**When**

Continuously, but especially at the end of each big-ticket implementation slice.

## Bottom Line

The product is already past the point where "more scaffolding" is the right next move. The highest-value step is to **finish the live quote lifecycle** and make the current RFQ journey production-grade.

After that, the next most useful investments are:

1. Vendor management and invitation workflow
2. Design-system consolidation
3. API hardening and tenant safety cleanup
4. Connectivity / integration backbone

Those items are the shortest path from a working Atomy-Q shell to a credible SaaS platform.
