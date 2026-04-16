# Alpha Task 5 Hide Or Defer Non-Alpha Surfaces Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a single alpha-mode gating layer that hides non-alpha surfaces from navigation, keeps RFQ-scoped approvals visible, renders a shared deferred screen on direct access to hidden routes, and updates smoke coverage to match the alpha proof-of-concept product surface.

**Architecture:** Keep this task WEB-first and policy-driven. One shared alpha-mode helper owns visibility and route-defer rules, while the dashboard layout, RFQ workspace layout, and representative route files consume that helper. Use one reusable deferred-screen component rather than per-route “coming soon” placeholders. Only touch API controllers if a hidden mutable feature remains reachable from alpha UI behavior and currently returns misleading success.

**Tech Stack:** Next.js 16 app router, React 19, TypeScript, Playwright, Vitest, existing design-system components.

---

## File Map

- Create: `apps/atomy-q/WEB/src/lib/alpha-mode.ts`
  - Central source of truth for alpha-mode env gating, visible nav ids, visible RFQ workspace sections, and deferred-route detection.
- Create: `apps/atomy-q/WEB/src/components/alpha/alpha-deferred-screen.tsx`
  - Shared in-app deferred screen with the required alpha copy.
- Create: `apps/atomy-q/WEB/src/components/alpha/alpha-deferred-screen.test.tsx`
  - Unit coverage for shared deferred-screen copy and shell-safe rendering contract.
- Modify: `apps/atomy-q/WEB/src/config/nav.ts`
  - Make exported nav config alpha-aware or expose filtered variants from the shared helper.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/layout.tsx`
  - Gate top-level sidebar links in alpha mode and hide global Approval Queue and Settings.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/layout.tsx`
  - Gate collapsed rail links in alpha mode and keep only alpha-approved RFQ workspace entry points.
- Modify: `apps/atomy-q/WEB/src/components/workspace/active-record-menu.tsx`
  - Hide negotiations, documents, and risk in alpha while keeping RFQ-scoped approvals visible.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/documents/page.tsx`
  - Render deferred screen in alpha mode.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/reporting/page.tsx`
  - Render deferred screen in alpha mode.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/settings/page.tsx`
  - Render deferred screen in alpha mode.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.tsx`
  - Render deferred screen in alpha mode.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/negotiations/page.tsx`
  - Render deferred screen in alpha mode.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/documents/page.tsx`
  - Render deferred screen in alpha mode.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx`
  - Render deferred screen in alpha mode.
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/deferred-routes.test.tsx`
  - Route-level test coverage for representative hidden pages and RFQ workspace pages.
- Modify: `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`
  - Narrow alpha smoke coverage to alpha-visible top-level routes only.
- Modify: `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`
  - Replace old visibility assertions with alpha-mode nav assertions and deferred-route expectations.
- Optional Modify only if required by audit: `apps/atomy-q/API/app/Http/Controllers/Api/V1/*.php`
  - Add explicit deferred responses only for still-reachable hidden mutable surfaces that would otherwise return misleading success from alpha UI.
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
  - Record shipped alpha-surface gating behavior after implementation.
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`
  - Link the Task 5 spec/plan if needed.
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
  - Record Task 5 verification evidence if the branch is used for release gating later.

## Task 1: Add The Shared Alpha-Mode Policy

**Files:**
- Create: `apps/atomy-q/WEB/src/lib/alpha-mode.ts`
- Test: `apps/atomy-q/WEB/src/components/alpha/alpha-deferred-screen.test.tsx`

- [ ] **Step 1: Write the policy helper first**

Create `apps/atomy-q/WEB/src/lib/alpha-mode.ts` with one small focused module:

```ts
export const ALPHA_DEFERRED_COPY = 'This feature will be available in future releases';

export const ALPHA_TOP_LEVEL_VISIBLE = new Set(['dashboard', 'requisition']);
export const ALPHA_RFQ_CHILD_VISIBLE = new Set(['quote-intake', 'comparison-runs', 'approvals', 'decision-trail']);
export const ALPHA_RFQ_MAIN_VISIBLE = new Set(['overview', 'details', 'line-items', 'vendors', 'award']);

export function isAlphaMode(): boolean {
  return process.env.NEXT_PUBLIC_ALPHA_MODE === 'true';
}

export function isTopLevelNavVisibleInAlpha(navId: string): boolean {
  return ALPHA_TOP_LEVEL_VISIBLE.has(navId);
}

export function isRfqSectionVisibleInAlpha(sectionId: string): boolean {
  return ALPHA_RFQ_MAIN_VISIBLE.has(sectionId) || ALPHA_RFQ_CHILD_VISIBLE.has(sectionId);
}

export function isDeferredAlphaPath(pathname: string): boolean {
  return (
    pathname === '/documents' ||
    pathname === '/reporting' ||
    pathname === '/settings' ||
    pathname.startsWith('/settings/') ||
    /\\/rfqs\\/[^/]+\\/(negotiations|documents|risk)$/.test(pathname)
  );
}
```

- [ ] **Step 2: Keep the policy intentionally narrow**

Do not add a generic entitlement system. Limit the helper to:

- alpha-mode env detection
- nav visibility
- RFQ workspace visibility
- deferred route detection
- shared deferred copy

- [ ] **Step 3: Add a minimal regression test for the shared copy constant**

Create a small test in `apps/atomy-q/WEB/src/components/alpha/alpha-deferred-screen.test.tsx` or a sibling alpha helper test file to assert the constant and route contract do not drift.

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/components/alpha/alpha-deferred-screen.test.tsx
```

Expected: PASS once the screen component exists in Task 2.

## Task 2: Build The Shared Deferred Screen

**Files:**
- Create: `apps/atomy-q/WEB/src/components/alpha/alpha-deferred-screen.tsx`
- Modify: `apps/atomy-q/WEB/src/components/alpha/alpha-deferred-screen.test.tsx`

- [ ] **Step 1: Create one reusable deferred-screen component**

Use existing design-system pieces and keep the API tiny:

```tsx
type AlphaDeferredScreenProps = {
  title: string;
  subtitle?: string;
};

export function AlphaDeferredScreen({ title, subtitle }: AlphaDeferredScreenProps) {
  return (
    <div className="space-y-6">
      <PageHeader title={title} subtitle={subtitle ?? ALPHA_DEFERRED_COPY} />
      <SectionCard title={title}>
        <EmptyState
          icon={<Clock3 size={20} />}
          title="Deferred in alpha"
          description={ALPHA_DEFERRED_COPY}
        />
      </SectionCard>
    </div>
  );
}
```

- [ ] **Step 2: Keep the copy exact**

The required primary user-facing statement must remain:

```ts
'This feature will be available in future releases'
```

Do not replace it with route-specific messaging in Task 5.

- [ ] **Step 3: Add focused component assertions**

Test:

- the page title renders
- the exact shared copy renders
- no action button is present by default

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/components/alpha/alpha-deferred-screen.test.tsx
```

Expected: PASS.

## Task 3: Make Top-Level Navigation Alpha-Aware

**Files:**
- Modify: `apps/atomy-q/WEB/src/config/nav.ts`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/layout.tsx`

- [ ] **Step 1: Update nav config to describe ids consistently**

Make sure top-level nav ids are stable and align with the alpha helper:

```ts
export const MAIN_NAV_ITEMS: NavItemConfig[] = [
  { id: 'dashboard', label: 'Dashboard', href: '/', path: '/' },
  { id: 'requisition', label: 'Requisition', href: '/rfqs', path: '/rfqs' },
  { id: 'documents', label: 'Documents', href: '/documents', path: '/documents' },
  { id: 'reporting', label: 'Reporting', href: '/reporting', path: '/reporting' },
  { id: 'approvals', label: 'Approval Queue', href: '/approvals', path: '/approvals' },
];
```

- [ ] **Step 2: Filter the default dashboard sidebar in alpha mode**

In `apps/atomy-q/WEB/src/app/(dashboard)/layout.tsx`, hide these items when `isAlphaMode()` is true:

- `Documents`
- `Reporting`
- `Approval Queue`
- `Settings`

Keep visible:

- `Dashboard`
- feature-flagged `Projects` and `Task Inbox` only if the team intentionally wants them outside alpha scope; otherwise, explicitly hide them in alpha in the same pass
- `Requisition`

Use a helper branch such as:

```tsx
const alphaMode = isAlphaMode();
const showDocuments = !alphaMode;
const showReporting = !alphaMode;
const showApprovalQueue = !alphaMode;
const showSettings = !alphaMode;
```

- [ ] **Step 3: Decide projects/tasks explicitly during implementation**

Before coding, resolve whether `Projects` and `Task Inbox` are alpha-visible or also hidden under alpha mode.

Implementation rule:

- if they are outside the alpha golden path, hide them in alpha
- do not leave them visible by accident because they are driven by unrelated feature flags

- [ ] **Step 4: Update dashboard-nav Playwright expectations**

Modify `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts` so the post-login sidebar assertions verify the alpha surface instead of the old full product surface.

Run:

```bash
cd apps/atomy-q/WEB && npx playwright test tests/dashboard-nav.spec.ts
```

Expected: PASS with alpha-mode assertions.

## Task 4: Make RFQ Workspace Navigation Alpha-Aware

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/layout.tsx`
- Modify: `apps/atomy-q/WEB/src/components/workspace/active-record-menu.tsx`

- [ ] **Step 1: Filter the RFQ workspace collapsed rail**

In `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/layout.tsx`, hide the top-level rail items that are globally hidden in alpha:

- `Documents`
- `Reporting`
- `Settings`

The RFQ layout should not leak broader product destinations just because the default dashboard layout is bypassed.

- [ ] **Step 2: Filter child links in the active record menu**

In `apps/atomy-q/WEB/src/components/workspace/active-record-menu.tsx`:

- keep `quote-intake`
- keep `comparison-runs`
- keep `approvals`
- keep `decision-trail`
- remove `negotiations`, `documents`, and `risk` in alpha mode

Recommended pattern:

```tsx
const childLinks = rawChildLinks.filter((link) => !alphaMode || isRfqSectionVisibleInAlpha(link.id));
```

- [ ] **Step 3: Preserve RFQ-scoped approvals**

Do not hide the RFQ `Approvals` entry in alpha mode. This is a required regression assertion for Task 5.

- [ ] **Step 4: Add unit coverage for the active-record menu**

Create or update a focused test file for `ActiveRecordMenu` if one does not exist.

Cover:

- hidden child links absent in alpha
- `Approvals` still visible in alpha
- badges for hidden links not rendered

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/components/workspace/active-record-menu.test.tsx
```

Expected: PASS.

## Task 5: Gate Hidden Pages To The Deferred Screen

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/documents/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/reporting/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/settings/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/negotiations/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/documents/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/deferred-routes.test.tsx`

- [ ] **Step 1: Use one consistent guard shape**

At the top of each representative hidden page, gate on `isAlphaMode()` and render `AlphaDeferredScreen`.

Pattern:

```tsx
if (isAlphaMode()) {
  return <AlphaDeferredScreen title="Reporting" />;
}
```

- [ ] **Step 2: Replace placeholder business UIs in alpha**

Do not leave alpha-hidden routes rendering:

- disabled action buttons
- fake empty states like “No documents yet”
- placeholder descriptions implying near-term support

Those should only remain visible when alpha mode is off.

- [ ] **Step 3: Add representative route tests**

Create `apps/atomy-q/WEB/src/app/(dashboard)/deferred-routes.test.tsx` that renders representative pages in alpha mode and asserts the shared copy.

Minimum route classes:

- `/documents`
- `/reporting`
- `/settings/users`
- `/rfqs/[rfqId]/negotiations`
- `/rfqs/[rfqId]/documents`
- `/rfqs/[rfqId]/risk`

- [ ] **Step 4: Verify copy consistency**

The tests must assert the exact shared string:

```ts
'This feature will be available in future releases'
```

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/app/(dashboard)/deferred-routes.test.tsx
```

Expected: PASS.

## Task 6: Narrow Smoke And Navigation E2E Coverage

**Files:**
- Modify: `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`
- Modify: `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`

- [ ] **Step 1: Rewrite screen-smoke to match alpha**

In `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`, remove expectations for hidden top-level routes in alpha mode.

The alpha-visible smoke sequence should cover only the top-level alpha surface, for example:

```ts
const screens = [
  { sidebarLabel: 'Active', heading: /^Requisitions$/, expandRequisition: true },
];
```

Retain `Dashboard` as the starting assertion.

- [ ] **Step 2: Add deferred-route E2E coverage**

Use `dashboard-nav.spec.ts` to directly visit:

- `/documents`
- `/reporting`
- `/settings/users`

Then assert:

- shared deferred copy appears
- hidden nav items are not present in the sidebar

- [ ] **Step 3: Keep the tests alpha-specific**

Set the alpha env in the Playwright run or document the assumption in the test file header. Do not mix alpha and non-alpha expectations in the same assertions.

- [ ] **Step 4: Run the narrowed E2E files**

Run:

```bash
cd apps/atomy-q/WEB && npx playwright test tests/screen-smoke.spec.ts tests/dashboard-nav.spec.ts
```

Expected: PASS with alpha-mode route expectations only.

## Task 7: Audit Still-Reachable Hidden Mutable Flows

**Files:**
- Inspect: `apps/atomy-q/API/app/Http/Controllers/Api/V1/*.php`
- Modify only if needed: specific controller files found by the audit

- [ ] **Step 1: Perform a narrow reachability audit**

Check whether any alpha-hidden surface still exposes a mutation path through alpha-visible UI behavior or smoke setup.

Focus on hidden areas only:

- documents uploads/actions
- reporting exports
- settings writes
- negotiations mutations

- [ ] **Step 2: Decide if backend work is required**

If no hidden mutable feature is reachable from alpha UI after the WEB gating lands:

- do not change API controllers

If a hidden mutable feature is still reachable and currently returns misleading success:

- add an explicit deferred response

Preferred contract:

```php
return response()->json([
    'message' => 'This feature is deferred in alpha.',
], 501);
```

- [ ] **Step 3: Add backend tests only if the audit triggers backend changes**

If any controller changes are made, add or update feature tests in the corresponding API test suite.

Run only if touched:

```bash
cd apps/atomy-q/API && php artisan test --filter Deferred
```

Expected: PASS for any new deferred-endpoint coverage.

## Task 8: Document The Shipped Alpha-Surface Policy

**Files:**
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- Modify if needed: `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`
- Modify if needed: `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`

- [ ] **Step 1: Record the behavior plainly**

Update `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` with:

- alpha mode is explicit and not the default product mode
- hidden routes render a shared deferred screen
- global Approval Queue hidden
- RFQ-scoped Approvals retained

- [ ] **Step 2: Avoid roadmap drift**

Do not describe hidden routes as removed or deprecated. Describe them as intentionally hidden for alpha.

- [ ] **Step 3: Record verification evidence if this branch is used operationally**

If the branch becomes part of release gating later, add the relevant test/build evidence to the release checklist.

## Task 9: Final Verification

**Files:**
- Test: `apps/atomy-q/WEB/src/components/alpha/alpha-deferred-screen.test.tsx`
- Test: `apps/atomy-q/WEB/src/app/(dashboard)/deferred-routes.test.tsx`
- Test: `apps/atomy-q/WEB/src/components/workspace/active-record-menu.test.tsx`
- Test: `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`
- Test: `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`

- [ ] **Step 1: Run focused unit tests**

```bash
cd apps/atomy-q/WEB && npx vitest run \
  src/components/alpha/alpha-deferred-screen.test.tsx \
  src/app/(dashboard)/deferred-routes.test.tsx \
  src/components/workspace/active-record-menu.test.tsx
```

Expected: PASS.

- [ ] **Step 2: Run alpha navigation Playwright coverage**

```bash
cd apps/atomy-q/WEB && npx playwright test tests/screen-smoke.spec.ts tests/dashboard-nav.spec.ts
```

Expected: PASS.

- [ ] **Step 3: Run build if route composition changed materially**

```bash
cd apps/atomy-q/WEB && npm run build
```

Expected: PASS.

- [ ] **Step 4: Commit only when the user explicitly asks**

This request specifically says not to commit the changes on the branch. Do not run `git commit` as part of Task 5 implementation unless the user later changes that instruction.

## Self-Review Notes

- The release-plan file list is incomplete relative to the current codebase. The real enforcement points are `src/app/(dashboard)/layout.tsx` and `src/app/(dashboard)/rfqs/[rfqId]/layout.tsx`, so this plan includes them explicitly.
- The plan intentionally defers broad API changes unless the WEB audit proves a hidden mutable feature is still reachable after alpha gating.
- `Projects` and `Task Inbox` need an explicit implementation-time product call because they are currently controlled by feature flags rather than alpha-mode policy. Do not let them remain visible by accident.