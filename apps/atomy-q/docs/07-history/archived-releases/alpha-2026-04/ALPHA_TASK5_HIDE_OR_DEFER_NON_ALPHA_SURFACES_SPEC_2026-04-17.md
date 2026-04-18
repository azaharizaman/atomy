# Alpha Task 5 Hide Or Defer Non-Alpha Surfaces Spec

## Document Control

- **Task:** Section 9, Task 5 - Hide Or Defer Non-Alpha Surfaces
- **Date:** 2026-04-17
- **Status:** Draft for review
- **Primary plan reference:** [ALPHA_RELEASE_PLAN_2026-04-15.md](./ALPHA_RELEASE_PLAN_2026-04-15.md)

## 1. Purpose

Task 5 narrows the Atomy-Q alpha experience to the proof-of-concept procurement journey that design partners are meant to validate.

The current product shell exposes a wider SaaS footprint than the alpha actually supports. Some of those routes are placeholder-heavy, partially wired, or intentionally deferred. Leaving them visible in alpha creates the wrong impression: users can wander into modules that are not part of the alpha promise and mistake deferred functionality for broken functionality.

This task defines a single alpha-mode gating model for the WEB app, the route-level behavior for hidden screens, the limited API implications, and the verification needed to keep the alpha surface honest without deleting future-product surfaces from the codebase.

## 2. Product Intent

Alpha mode is not the default product mode.

Alpha mode is an explicit proof-of-concept mode for a narrow golden path:

1. dashboard entry
2. RFQ list and RFQ workspace
3. vendor invitations
4. quote intake
5. normalization
6. comparison
7. award
8. decision trail
9. RFQ-scoped approvals where relevant

The full Atomy-Q product is intended to be broader and more feature rich. Hidden screens in alpha are not rejected product ideas and are not being removed from the long-term roadmap. They are intentionally hidden because they are not required to validate the alpha journey.

## 3. Scope

### In scope

- Define a single alpha-mode feature flag for WEB navigation and route gating.
- Hide non-alpha top-level surfaces from alpha navigation.
- Hide non-alpha RFQ workspace surfaces from the active-record menu in alpha.
- Render one shared in-app deferred screen when a user directly accesses an alpha-hidden route.
- Keep RFQ-scoped approvals visible in alpha.
- Hide the global Approval Queue in alpha.
- Update smoke coverage so alpha-mode route validation reflects the intended alpha surface only.
- Require explicit deferred API behavior only where alpha-hidden mutable features remain reachable from alpha UI flows.

### Out of scope

- Removing deferred product routes from the codebase.
- Full productionization of `Settings > Users & Roles`; that is handed over to Task 6.
- Reworking backend contracts for every non-alpha controller.
- Hiding or redesigning alpha-core RFQ journey pages.
- Building a broad feature-flag platform or entitlement system.

## 4. High-Level Decisions

Task 5 is based on the following product decisions:

- Alpha mode is controlled by a single explicit feature flag.
- Alpha mode is not the default product mode.
- Hidden alpha screens must show a dedicated in-app deferred screen instead of `404`.
- The deferred screen uses shared copy, not per-feature messaging.
- Shared deferred message:
  - `This feature will be available in future releases`
- Global `Approval Queue` is hidden in alpha.
- RFQ-scoped `Approvals` stays visible in alpha.
- `Settings > Users & Roles` is handed over to Task 6 and therefore hidden in alpha for now.

## 5. Alpha Surface Model

Task 5 defines three route classes.

### 5.1 Alpha-core visible surfaces

These remain visible and navigable in alpha mode because they are part of the proof-of-concept journey.

Top-level alpha-core surfaces:

- `Dashboard`
- `Requisition` / RFQ list

RFQ workspace alpha-core surfaces:

- overview
- details
- line items
- vendors
- quote intake
- comparison runs
- award
- decision trail
- RFQ-scoped approvals

### 5.2 Alpha-hidden surfaces with deferred screen

These remain in the codebase but must not appear in alpha navigation or workspace menus.

Top-level alpha-hidden surfaces:

- `Documents`
- `Reporting`
- global `Approval Queue`
- `Settings`
  - including `Users & Roles`
  - integrations
  - scoring policies
  - templates
  - feature flags

RFQ workspace alpha-hidden surfaces:

- negotiations
- RFQ documents
- risk and compliance
- any other child-record routes not required by the alpha golden path

### 5.3 Non-alpha retained product surfaces

These routes and controllers remain part of the future product and may still exist in the repository, app router, controller layer, and documentation. Task 5 does not remove them. It only changes how alpha mode exposes them.

## 6. Runtime Contract

### 6.1 Alpha mode contract

When the alpha feature flag is enabled:

- navigation must expose only alpha-core surfaces
- hidden surfaces must not appear in sidebar, settings navigation, or RFQ workspace menus
- direct navigation to hidden surfaces must render the shared deferred screen
- alpha mode must not present placeholder-heavy non-core screens as if they were supported product areas

### 6.2 Non-alpha mode contract

When the alpha feature flag is disabled:

- the broader product navigation may remain available
- Task 5 must not delete or hard-disable future-product routes
- deferred-screen behavior defined by Task 5 is alpha-specific unless a route is independently deferred outside alpha mode

## 7. Configuration Model

Task 5 must use one shared alpha-mode flag instead of scattering hard-coded route checks across unrelated components.

Recommended shape:

- one env-backed WEB flag such as `NEXT_PUBLIC_ALPHA_MODE`
- one shared policy/helper module responsible for:
  - whether alpha mode is active
  - which top-level nav items are visible in alpha
  - which settings entries are visible in alpha
  - which RFQ workspace sections are visible in alpha
  - whether a pathname should render the deferred screen in alpha

This policy must be the source of truth for:

- top-level dashboard/sidebar navigation
- settings navigation
- RFQ active-record menu links
- route-level deferred-screen rendering
- smoke-test expectations

The implementation must avoid duplicating alpha visibility logic in individual page files wherever a central policy can be consumed instead.

## 8. Deferred Screen Contract

### 8.1 Shared behavior

All alpha-hidden routes must render one shared in-app deferred experience.

Requirements:

- render inside the normal dashboard/application shell
- clearly communicate intentional deferral, not missing route or runtime failure
- avoid fake business records, fake metrics, or action buttons that imply partial support
- use exactly one shared message for the core user-facing statement

Required shared copy:

`This feature will be available in future releases`

### 8.2 UX expectations

The deferred screen should:

- preserve enough page context that the user knows which feature area they reached
- use a single reusable component or page pattern
- not masquerade as a live empty state

The deferred screen should not:

- show “No data yet” language
- show local mock business tables in alpha mode
- expose mutation buttons that appear functional

## 9. Navigation Requirements

### 9.1 Top-level navigation

In alpha mode, the main navigation must be reduced to the alpha-core surfaces only.

Visible:

- `Dashboard`
- `Requisition`

Hidden:

- `Documents`
- `Reporting`
- global `Approval Queue`
- `Settings`

If the app shell currently requires `Settings` or another hidden section to remain structurally mounted for layout reasons, it must still not be presented as a navigable alpha destination.

### 9.2 RFQ workspace navigation

In alpha mode, the active-record menu must retain only RFQ-core and RFQ-relevant child-record links needed for the alpha story.

Visible RFQ links:

- overview
- details
- line items
- vendors
- award

Visible child-record links:

- quote intake
- comparison runs
- approvals
- decision trail

Hidden child-record links:

- negotiations
- documents
- risk and compliance

Badges or counters for hidden child-record links must not be shown in alpha mode.

## 10. Approvals Policy

Approvals are treated asymmetrically in alpha mode.

### 10.1 Hidden in alpha

- global `Approval Queue`

Reason:

- it behaves like a broad product module rather than a narrow RFQ journey step
- it increases surface area outside the proof-of-concept path

### 10.2 Visible in alpha

- RFQ-scoped `Approvals`

Reason:

- they belong to the active procurement record
- they can remain part of the award and signoff story without exposing the broader inbox-style approvals module

Task 5 must preserve this distinction consistently in nav, route handling, and smoke coverage.

## 11. Settings Policy

All settings surfaces are hidden in alpha mode for Task 5.

This includes:

- `Users & Roles`
- integrations
- scoring policies
- templates
- feature flags

Rationale:

- `Users & Roles` is explicitly handed to Task 6
- the rest are broader product-administration surfaces that do not serve the alpha golden path

If a user directly accesses a settings route in alpha mode, the shared deferred screen must render.

## 12. API Boundary

Task 5 is primarily a WEB-surface narrowing task.

The default backend rule is:

- do not change hidden controllers simply because the UI hides them in alpha mode

However, explicit deferred backend behavior is required when all of the following are true:

1. the feature is alpha-hidden
2. the route is still reachable from alpha UI behavior, smoke scaffolding, or user actions
3. the current backend behavior could produce synthetic success or misleading placeholder mutations

In that case, the backend should return an honest deferred contract such as:

- `501 Not Implemented`
- or another explicit deferred response already consistent with the API conventions

Task 5 must not expand into a full audit of every non-alpha controller. The backend changes must remain narrowly targeted to still-reachable alpha-hidden mutable paths only.

## 13. Route Handling Requirements

For representative alpha-hidden routes, the app must render the deferred screen on direct navigation.

Representative route classes include:

- top-level hidden routes such as `/documents` and `/reporting`
- hidden settings routes such as `/settings/users`
- hidden RFQ workspace routes such as `/rfqs/[rfqId]/negotiations`, `/rfqs/[rfqId]/documents`, and `/rfqs/[rfqId]/risk`

The implementation may use either:

- direct page-level gating inside those route files
- or a shared route wrapper/pattern

But the behavior must remain consistent across route classes and must continue to use the central alpha-mode policy.

## 14. Testing Requirements

### 14.1 Smoke coverage

Update `apps/atomy-q/WEB/tests/screen-smoke.spec.ts` so alpha-mode smoke reflects only the intended alpha-visible top-level surfaces.

In alpha mode, smoke coverage must include:

- dashboard
- requisition / RFQ list

It must not require:

- documents
- reporting
- global approval queue
- settings pages

### 14.2 Focused UI tests

Task 5 should add focused tests that verify:

- hidden top-level nav items do not render in alpha mode
- hidden RFQ workspace child links do not render in alpha mode
- RFQ-scoped approvals still render in alpha mode
- direct access to representative hidden routes renders the shared deferred screen
- the shared deferred copy matches the required text

### 14.3 Non-regression expectations

Task 5 must not break:

- alpha-core RFQ navigation
- RFQ-scoped approvals visibility
- existing alpha-core page behavior

## 15. Documentation Requirements

Task 5 must update alpha-facing documentation so the narrowing is explicit and intentional.

Required updates:

- record the Task 5 spec and plan references in the release-plan trail as needed
- document that alpha mode is an explicit proof-of-concept mode, not the default product mode
- document that hidden surfaces are intentionally deferred for alpha, not removed from the future product

If implementation summary files are updated, they must describe the alpha-surface policy accurately and must not imply that hidden modules are deprecated from the long-term product.

## 16. Completion Criteria

Task 5 is complete when all of the following are true:

- one shared alpha-mode flag controls surface narrowing
- top-level alpha nav shows only the approved alpha-core surfaces
- RFQ workspace nav hides negotiations, documents, and risk in alpha
- RFQ-scoped approvals remain visible in alpha
- global Approval Queue is hidden in alpha
- settings routes are hidden in alpha
- direct access to representative hidden routes shows the shared deferred screen
- alpha smoke tests reflect the narrowed top-level alpha surface
- any still-reachable hidden mutable endpoints use explicit deferred behavior instead of misleading success

## 17. Explicit Non-Goals

Task 5 does not:

- productionize settings
- remove deferred routes from the product
- rewrite broad backend modules
- change the long-term information architecture of Atomy-Q
- introduce per-feature custom deferred messaging

## 18. Open Dependencies

Task 5 depends on one adjacent release decision:

- Task 6 owns the future decision and implementation for `Users & Roles`

Task 5 should therefore assume:

- `Users & Roles` is hidden in alpha now
- any future alpha exposure of settings must happen through a later task, not through exceptions in this one