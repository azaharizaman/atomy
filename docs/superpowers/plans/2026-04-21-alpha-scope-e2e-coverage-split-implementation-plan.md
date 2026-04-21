# Alpha-Scope E2E Coverage Split Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reorganize the Playwright suite around the refined alpha-scope browser journeys and add missing RFQ browser coverage without expanding into deferred routes. Keep project, task, and settings/users checks as adjacent non-alpha smoke coverage because those routes are hidden when `NEXT_PUBLIC_ALPHA_MODE=true`.

**Architecture:** Keep the existing auth and CORS bootstrap helpers, then split browser coverage into alpha journey owners and adjacent smoke owners. The RFQ alpha journeys become the main ownership boundary for procurement flows; projects/tasks and settings/users remain separate non-alpha smoke specs unless the alpha policy changes. The existing smoke and lifecycle files should be trimmed so they no longer duplicate journey ownership.

**Tech Stack:** Next.js App Router, Playwright, TypeScript, React, local test API at `http://localhost:8000/api/v1`, Vitest only for any helper-level regressions.

---

## File Map

- `apps/atomy-q/WEB/tests/playwright-auth-bootstrap.ts`: keep as the shared auth-session seeding primitive.
- `apps/atomy-q/WEB/tests/playwright-cors-helpers.ts`: keep as the shared CORS/origin fulfillment primitive.
- `apps/atomy-q/WEB/tests/alpha-playwright-bootstrap.ts`: new shared alpha bootstrap helper for feature flags, counts, and authenticated shell setup.
- `apps/atomy-q/WEB/tests/auth.spec.ts`: owns login and password reset entry points.
- `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`: owns the alpha shell and top-level nav assertions only.
- `apps/atomy-q/WEB/tests/rfq-alpha-journeys.spec.ts`: new owner for alpha RFQ browser journeys.
- `apps/atomy-q/WEB/tests/rfq-workflow.spec.ts`: migrate away from this file after RFQ alpha journeys exist, then delete or reduce to a temporary compatibility smoke if needed during rollout.
- `apps/atomy-q/WEB/tests/rfq-line-items.spec.ts`: migrate the line-item refresh coverage into the new RFQ alpha journeys spec, then delete or reduce if no unique coverage remains.
- `apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts`: keep only the narrow real-API create-then-overview smoke path.
- `apps/atomy-q/WEB/tests/projects-tasks-smoke.spec.ts`: adjacent non-alpha owner for project list/detail and task inbox/drawer.
- `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`: trim any project/task overlap after the new projects/tasks spec lands.
- `apps/atomy-q/WEB/tests/settings-users-smoke.spec.ts`: adjacent non-alpha owner for settings landing and Users & Roles.
- `apps/atomy-q/WEB/tests/smoke.spec.ts`: keep as a minimal dashboard/RFQ list sanity layer after ownership is moved out.
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`: record the split and any file deletions or narrowed smoke coverage.
- `apps/atomy-q/WEB/README.md`: update the e2e command notes if the suite layout or recommended run order changes.

---

## Task 1: Add shared alpha bootstrap helpers and wire them into the shell specs

**Files:**
- Create: `apps/atomy-q/WEB/tests/alpha-playwright-bootstrap.ts`
- Modify: `apps/atomy-q/WEB/tests/auth.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/smoke.spec.ts`

- [ ] **Step 1: Create the new alpha bootstrap helper**

Create `apps/atomy-q/WEB/tests/alpha-playwright-bootstrap.ts` with:

- `alphaMockUser` matching the existing QA buyer identity used across the suite.
- `stubAlphaSession(page)` that seeds the auth session via `seedAuthSession`, routes `**/api/v1/feature-flags` to `{ data: { projects: true, tasks: true } }`, routes `**/api/v1/rfqs/counts` to a zero-count payload, and navigates to `/` before the spec starts.
Keep the helper small. Do not move RFQ/project/task/settings fixtures into this file; those should stay local to the journey family that owns them. The helper name is about shared app bootstrap, not a claim that projects/tasks/settings are visible in strict alpha mode.

- [ ] **Step 2: Update the auth and dashboard specs to use the helper**

Replace the repeated `mockUser`, feature-flag route, and RFQ-count route boilerplate in `auth.spec.ts`, `dashboard-nav.spec.ts`, and `smoke.spec.ts` with `stubAlphaSession(page)`.

`auth.spec.ts` should still own:

- mock login redirect
- mock forgot-password success
- mock reset-password success
- real test-API login smoke

`dashboard-nav.spec.ts` should still own:

- dashboard heading visibility
- alpha sidebar visibility
- top-level `Dashboard` and `Requisition` navigation

`smoke.spec.ts` should keep only the minimal shell sanity checks that are still useful after the split.

- [ ] **Step 3: Verify the helper does not regress shell coverage**

Run:

```bash
cd apps/atomy-q/WEB
npx playwright test tests/auth.spec.ts tests/dashboard-nav.spec.ts tests/smoke.spec.ts
```

Expected: pass with the same auth and shell assertions as before, but with less duplicated bootstrap code.

- [ ] **Step 4: Commit the helper extraction**

```bash
git add apps/atomy-q/WEB/tests/alpha-playwright-bootstrap.ts apps/atomy-q/WEB/tests/auth.spec.ts apps/atomy-q/WEB/tests/dashboard-nav.spec.ts apps/atomy-q/WEB/tests/smoke.spec.ts
git commit -m "test(web): centralize alpha e2e bootstrap"
```

## Task 2: Build the RFQ alpha journey spec and move the alpha-visible RFQ flows into it

**Files:**
- Create: `apps/atomy-q/WEB/tests/rfq-alpha-journeys.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/rfq-workflow.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/rfq-line-items.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts`

- [ ] **Step 1: Write the new RFQ alpha journey test file**

Create `apps/atomy-q/WEB/tests/rfq-alpha-journeys.spec.ts` as the browser-facing owner for the alpha RFQ surface.

It should cover these journeys as separate tests or tightly grouped `describe` blocks:

- RFQ list rendering
- RFQ creation through `/rfqs/new`, including filling the title and submission deadline, stubbing `POST /api/v1/rfqs`, clicking `Create RFQ`, and asserting navigation to `/rfqs/{createdId}/overview` or a refreshed RFQ list when the API returns no id
- RFQ overview
- RFQ details
- RFQ line items, including add-item and visible refresh after save
- RFQ vendors
- RFQ quote intake
- RFQ comparison runs
- RFQ approvals
- RFQ award
- RFQ decision trail

Use local RFQ fixtures in the file so the journey data is obvious when a failure happens. Keep the selectors centered on headings, roles, and visible route text. Do not depend on deferred paths such as `documents`, `risk`, or `negotiations`.

- [ ] **Step 2: Move the line-item refresh assertions into the new RFQ alpha journey spec**

Port the behavior currently owned by `rfq-line-items.spec.ts` into `rfq-alpha-journeys.spec.ts` so the alpha RFQ owner covers the visible add-item flow and the refresh after a successful save.

The test should still assert both parts of the mutation path:

- the save mutation was called with the typed quantity, unit price, UOM, and currency
- the line-items section updated visibly after the mutation completes

Do not leave the line-item flow duplicated in the old file once the new owner passes.

- [ ] **Step 3: Narrow or remove the old RFQ workflow file after migration**

Update `rfq-workflow.spec.ts` so it no longer owns alpha RFQ journeys that now live in `rfq-alpha-journeys.spec.ts`.

If the file still contains any unique smoke value after migration, keep only a lightweight list-to-overview sanity check. If it has no unique coverage left, delete it after the new RFQ alpha journey spec is green.

- [ ] **Step 4: Keep the real-API lifecycle file narrow**

Keep `rfq-lifecycle-e2e.spec.ts` focused on the live backend smoke path only:

- create an RFQ through the local test API
- navigate to its overview page
- confirm the created record renders in the browser

Do not expand the live path back into the full mocked lifecycle once the new alpha RFQ spec exists.

- [ ] **Step 5: Verify the RFQ journeys**

Run:

```bash
cd apps/atomy-q/WEB
npx playwright test tests/rfq-alpha-journeys.spec.ts tests/rfq-lifecycle-e2e.spec.ts
```

Expected: the new alpha RFQ spec passes all mocked browser journeys, and the live API smoke path still passes.

- [ ] **Step 6: Commit the RFQ split**

```bash
git add apps/atomy-q/WEB/tests/rfq-alpha-journeys.spec.ts apps/atomy-q/WEB/tests/rfq-workflow.spec.ts apps/atomy-q/WEB/tests/rfq-line-items.spec.ts apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts
git commit -m "test(web): split alpha RFQ e2e journeys"
```

## Task 3: Add the projects/tasks smoke spec and move the browser-visible work flows into it

**Files:**
- Create: `apps/atomy-q/WEB/tests/projects-tasks-smoke.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/smoke.spec.ts`

- [ ] **Step 1: Write the new projects/tasks smoke tests**

Create `apps/atomy-q/WEB/tests/projects-tasks-smoke.spec.ts` with dedicated coverage for:

- `/projects`
- `/projects/[projectId]`
- `/tasks`
- task drawer open/close and the rendered detail state

These tests must run outside strict alpha mode because the current alpha dashboard shell hides `Projects` and `Task Inbox`.

Use project-specific and task-specific fixtures local to the file. Include a project detail path that exercises:

- projects list render
- navigation into the detail page
- project health summary
- project RFQs list
- project tasks list

Include a task path that exercises:

- task inbox render
- clicking a task row
- drawer/detail visibility for the selected task

- [ ] **Step 2: Remove project/task ownership from the smoke files**

Trim `smoke.spec.ts` and `screen-smoke.spec.ts` so they no longer own the browser-visible project and task journeys once `projects-tasks-smoke.spec.ts` is in place.

Keep only whichever generic smoke assertions still help detect a broken shell quickly. Do not leave the same project/task detail assertions duplicated in multiple specs.

- [ ] **Step 3: Verify the new project/task journeys**

Run:

```bash
cd apps/atomy-q/WEB
npx playwright test tests/projects-tasks-smoke.spec.ts
```

Expected: pass with the project list/detail and task drawer/browser-flow assertions intact.

- [ ] **Step 4: Commit the project/task split**

```bash
git add apps/atomy-q/WEB/tests/projects-tasks-smoke.spec.ts apps/atomy-q/WEB/tests/screen-smoke.spec.ts apps/atomy-q/WEB/tests/smoke.spec.ts
git commit -m "test(web): add projects and tasks smoke coverage"
```

## Task 4: Add the settings/users smoke spec and reduce dashboard-nav to shell-level assertions

**Files:**
- Create: `apps/atomy-q/WEB/tests/settings-users-smoke.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`

- [ ] **Step 1: Create the settings/users smoke spec**

Create `apps/atomy-q/WEB/tests/settings-users-smoke.spec.ts` to own the settings/users browser smoke path outside strict alpha mode.

It should cover:

- `/settings`
- `/settings/users`

The page should assert:

- the settings landing page loads
- the `Users & Roles` entry is visible and usable
- the users/roles page renders tenant data through the expected users and roles routes

Use local users and roles fixtures in the spec so the page remains self-contained and easy to debug.

These tests must not be counted as alpha acceptance criteria while `isSettingsNavVisible()` returns `false` in alpha mode and `/settings/*` is treated as deferred.

- [ ] **Step 2: Narrow the dashboard-nav spec to shell only**

Remove the detailed `Users & Roles` page assertions from `dashboard-nav.spec.ts`.

That file should only verify the shell contract:

- dashboard heading
- alpha sidebar visibility
- `Dashboard` top-level navigation
- `Requisition` top-level navigation

The page-content assertions for `/settings/users` should live only in `settings-users-smoke.spec.ts`.

- [ ] **Step 3: Verify the settings surface**

Run:

```bash
cd apps/atomy-q/WEB
npx playwright test tests/settings-users-smoke.spec.ts tests/dashboard-nav.spec.ts
```

Expected: the shell test still passes, and the new settings/users spec renders tenant data and the users page correctly.

- [ ] **Step 4: Commit the settings split**

```bash
git add apps/atomy-q/WEB/tests/settings-users-smoke.spec.ts apps/atomy-q/WEB/tests/dashboard-nav.spec.ts
git commit -m "test(web): add settings users smoke coverage"
```

## Task 5: Final suite cleanup, documentation sync, and full verification

**Files:**
- Modify: `apps/atomy-q/WEB/tests/smoke.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/WEB/README.md`

- [ ] **Step 1: Trim the remaining smoke tests so they do not duplicate alpha journey ownership**

After the new alpha journey specs land, make `smoke.spec.ts` a minimal sanity layer only. It should not keep project/task/RFQ journey assertions that are already owned elsewhere.

Keep `rfq-lifecycle-e2e.spec.ts` as the narrow live API smoke path. Do not let the live path grow back into a second copy of the mocked RFQ alpha suite.

- [ ] **Step 2: Update the web package docs**

Add a short note to `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` describing:

- the new alpha e2e file split
- which files now own RFQ alpha coverage
- which files retain adjacent projects/tasks and settings/users smoke coverage
- which old smoke assertions were removed or narrowed

If the e2e command guidance in `apps/atomy-q/WEB/README.md` now implies a preferred run order or a recommended live-vs-mock split, update that text so it matches the new layout.

- [ ] **Step 3: Run the full default e2e suite**

Run:

```bash
cd apps/atomy-q/WEB
npm run test:e2e
```

Expected: all alpha-scope browser journeys pass under the default suite.

- [ ] **Step 4: Run the live test-api suite explicitly**

Run:

```bash
cd apps/atomy-q/WEB
NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e
```

Expected: the live test API still supports the narrow real login and RFQ create/overview smoke path.

- [ ] **Step 5: Run the strict alpha policy subset**

Run:

```bash
cd apps/atomy-q/WEB
NEXT_PUBLIC_ALPHA_MODE=true npx playwright test tests/dashboard-nav.spec.ts tests/rfq-alpha-journeys.spec.ts
```

Expected: dashboard/requisition/RFQ alpha journeys pass, while hidden project/task/settings routes are not part of this subset.

- [ ] **Step 6: Commit the cleanup and docs**

```bash
git add apps/atomy-q/WEB/tests/smoke.spec.ts apps/atomy-q/WEB/tests/rfq-lifecycle-e2e.spec.ts apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md apps/atomy-q/WEB/README.md
git commit -m "test(web): finalize alpha e2e coverage split"
```

## Acceptance Criteria

- The alpha-visible browser surface is split into the proposed spec families.
- RFQ alpha coverage includes create, list, overview, details, line items, vendors, quote intake, comparison runs, approvals, award, and decision trail.
- Projects/tasks and settings/users each have a dedicated adjacent smoke spec or remain covered by narrowed existing smoke specs.
- Deferred routes stay out of the alpha e2e suite.
- The default e2e run stays green.
- The live test API smoke path still passes when run explicitly.
- The strict alpha subset passes with `NEXT_PUBLIC_ALPHA_MODE=true`.
