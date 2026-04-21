# Alpha-Scope E2E Coverage Split Design

- Date: 2026-04-21
- Area: `apps/atomy-q/WEB`
- Goal: expand Playwright coverage so the refined alpha-scope browser journeys are exercised by dedicated, stable e2e specs.

## Problem Statement

The current Playwright suite already proves several important flows, but the coverage is organized around mixed smoke/lifecycle tests rather than a clear alpha-scope journey map. That makes it harder to tell whether the user-facing alpha surface is actually covered end to end.

The intended alpha surface in this app is narrower than the full route tree:

- Alpha top-level navigation only exposes `dashboard` and `requisition`.
- Alpha RFQ navigation exposes `overview`, `details`, `line-items`, `vendors`, `award`, `quote-intake`, `comparison-runs`, `approvals`, and `decision-trail`.
- Deferred routes such as `/documents`, `/reporting`, `/settings/*` other than the alpha-visible user management path, and RFQ sections `negotiations`, `documents`, and `risk` remain out of scope for this e2e effort.

The test suite should reflect that alpha boundary instead of trying to cover the entire application.

## Goals

1. Cover the full alpha-visible browser surface with deterministic Playwright tests.
2. Keep each e2e spec focused on one journey family so failures are easy to localize.
3. Preserve a small real-API smoke path for the parts of the app that must prove live backend integration.
4. Avoid adding coverage for deferred alpha routes or hidden sections that are not part of the alpha product surface.

## Non-Goals

- No attempt to cover every route in the app.
- No attempt to automate deferred RFQ sections such as negotiations, documents, or risk.
- No attempt to make every backend endpoint reachable from Playwright.
- No attempt to replace unit or integration tests with e2e tests.

## Proposed Split

The suite should be organized into five journey families:

### 1. `auth.spec.ts`

Owns authentication and onboarding entry points.

Coverage:

- Mocked login redirect to dashboard.
- Mocked forgot-password success state.
- Mocked reset-password completion state.
- Real test-API login smoke path, if the local test backend is available.

Why this stays separate:

- Auth has its own session bootstrap and does not depend on RFQ/project fixtures.
- It is the fastest place to verify the app can start a user session.

### 2. `dashboard-nav.spec.ts`

Owns the alpha navigation shell.

Coverage:

- Dashboard landing after auth.
- Alpha sidebar visibility and top-level nav behavior.
- Entry points into requisition and settings/users from the shell.

Why this stays separate:

- This spec verifies the alpha layout contract without coupling it to a specific domain workflow.
- It should fail when shell navigation breaks, even if a feature page still renders in isolation.

### 3. `rfq-alpha-journeys.spec.ts`

Owns the browser-visible alpha RFQ journeys.

Coverage:

- RFQ list rendering.
- RFQ creation entry at `/rfqs/new`.
- RFQ overview.
- RFQ details.
- RFQ line items, including add-item and refresh behavior.
- RFQ vendors.
- RFQ quote intake.
- RFQ comparison runs.
- RFQ approvals.
- RFQ award.
- RFQ decision trail, where the alpha UI exposes it.

Why this becomes the main alpha e2e spec:

- These pages represent the core alpha procurement journey.
- They are the most coupled user flow in the app, so keeping them in one family reduces fixture duplication while still allowing focused subtests.

Design note:

- The spec should use subtests or grouped `describe` blocks, not one giant linear test.
- Shared setup should stub the alpha shell and RFQ data once per group, then each subtest should assert one journey segment.
- The real-API path should stay narrow: create an RFQ through the live backend, then verify at least the RFQ list and overview render from that created record. The deep workflow remains mocked for stability.

### 4. `projects-tasks-alpha.spec.ts`

Owns the alpha-visible operational work surfaces.

Coverage:

- Projects list.
- Project detail.
- Tasks inbox.
- Task drawer/detail interaction.

Why this stays separate:

- Projects and tasks share the dashboard shell but not the RFQ data model.
- Their API fixtures and assertions are different enough that combining them with RFQ flows would make the suite harder to reason about.

### 5. `settings-users-alpha.spec.ts`

Owns the alpha-visible settings journey.

Coverage:

- Settings landing.
- Users & Roles navigation.

Why this stays separate:

- The alpha settings surface is small and should remain a lightweight navigation/render check.
- Hidden settings routes remain out of scope unless the alpha nav changes later.

## Coverage Contract

The alpha e2e suite should satisfy these rules:

1. Every alpha-visible top-level navigation item must have at least one e2e assertion.
2. Every alpha-visible RFQ section must have at least one e2e assertion for navigation or content rendering.
3. The RFQ create path must be covered as a browser journey, not only as an API call.
4. The line-item add flow must continue to verify that saving an item refreshes the visible section.
5. Project detail and task drawer behavior must be covered in browser form, not just by list rendering.
6. Deferred routes must not become accidental coverage targets.

## Shared Test Infrastructure

The split should reuse the existing helper pattern rather than inventing new test plumbing.

Use the current shared helpers for:

- auth session seeding
- CORS-aware route fulfillment
- request-origin detection for mocked API responses
- alpha feature-flag stubbing

Shared fixtures should be centralized by journey family:

- RFQ fixtures should live with RFQ tests.
- Project fixtures should live with project/task tests.
- Settings fixtures should live with settings tests.
- Auth fixtures should remain minimal and isolated.

## Stability Rules

The alpha e2e suite should be written to avoid brittle false positives.

- Prefer route-level navigation and visible headings over deep CSS selectors where possible.
- Use explicit, locally-scoped stubs for the data a spec owns.
- Keep real-API coverage narrow and deterministic.
- Avoid depending on deferred routes to “hop” into alpha-visible pages.
- Do not assert live data fields that the local test API does not guarantee.

## Expected File Layout

Target Playwright layout:

- `apps/atomy-q/WEB/tests/auth.spec.ts`
- `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`
- `apps/atomy-q/WEB/tests/rfq-alpha-journeys.spec.ts`
- `apps/atomy-q/WEB/tests/projects-tasks-alpha.spec.ts`
- `apps/atomy-q/WEB/tests/settings-users-alpha.spec.ts`

Existing support files should stay shared:

- `apps/atomy-q/WEB/tests/playwright-auth-bootstrap.ts`
- `apps/atomy-q/WEB/tests/playwright-cors-helpers.ts`

## Success Criteria

The work is complete when:

- The alpha-visible browser journeys are split into the proposed spec families.
- The RFQ journey coverage includes create, list, overview, details, line items, vendors, quote intake, comparison runs, approvals, award, and decision trail.
- The project/task/settings journeys each have at least one stable e2e path.
- The test suite still passes in the default test mode.
- The real test API path still has at least one live smoke check.

## Suggested Implementation Order

1. Lock down the RFQ alpha journey spec first, because it is the biggest and most critical alpha workflow.
2. Add the projects/tasks alpha spec next.
3. Add the settings/users alpha spec last.
4. Re-run the full Playwright suite and trim any overlapping assertions from smoke tests if needed.

## Notes on Scope Boundaries

The following are intentionally not part of this alpha e2e pass:

- `/documents`
- `/reporting`
- `/settings/scoring-policies`
- `/settings/templates`
- `/settings/integrations`
- `/settings/feature-flags`
- RFQ sections `negotiations`, `documents`, and `risk`

If any of those routes later become alpha-visible, they should be added in a separate follow-up spec update rather than being folded into the initial alpha coverage split.
