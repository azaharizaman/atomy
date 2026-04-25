# App-Wide Breadcrumb And Sticky Form Actions

**Date:** 2026-04-25  
**Status:** Draft for review  
**Audience:** Atomy-Q WEB maintainers

## Purpose

Standardize two interaction rules across `apps/atomy-q/WEB`:

- breadcrumbs appear only once and only at the top application header,
- editable screens keep save-oriented actions reachable through a shared floating action dock when the primary action row scrolls out of view.

This spec removes duplicated breadcrumb treatment on RFQ workspace pages and establishes a reusable form-action pattern for the rest of the application.

## Problem Statement

Current Atomy-Q screens mix two breadcrumb systems:

- the shared `Header` renders a raw-path breadcrumb string,
- many workspace pages also render `WorkspaceBreadcrumbs` inside page content.

This creates duplicate breadcrumbs on screens such as RFQ Details and makes breadcrumb placement inconsistent between workspace and non-workspace routes.

Form actions are also inconsistent:

- editable pages place actions inline in `PageHeader`,
- long forms require users to scroll back to the top to save,
- some action groups are allowed to wrap, which makes primary actions feel unstable and visually cramped.

The result is a layout that feels less intentional than the rest of the shell and slows data-entry workflows.

## Goals

- Enforce a single breadcrumb location at the top header across the application.
- Replace raw pathname text with semantic breadcrumbs derived from route context.
- Prevent action-button labels from wrapping onto multiple lines.
- Make save-oriented actions reachable while scrolling on editable screens.
- Introduce shared primitives so future screens follow the same pattern by default.

## Non-Goals

- Redesign the main navigation tree.
- Change route structure or URL naming.
- Add sticky action docks to read-only pages.
- Invent a fully centralized route-metadata registry for every screen in this change.

## User Experience Rules

### Breadcrumb Rule

- Breadcrumbs are allowed only in the top header region.
- Page-body breadcrumbs are not allowed on application screens.
- Breadcrumb labels must be human-readable and route-aware, not raw pathname fragments.
- On RFQ workspace routes, breadcrumbs must reflect the record context when available:
  - `RFQs / <RFQ title> / <section>`
- On standard dashboard routes, breadcrumbs should resolve from the existing route-label configuration where possible.
- If record data is still loading, the header may show a stable placeholder label for the current record segment until data arrives.

### Sticky Action Rule

- Editable screens with primary persistence actions must expose those actions in a shared sticky bottom dock when the main action row is no longer visible.
- The dock must mirror the active action set, not introduce a different workflow.
- The dock should appear only for screens that are currently editable.
- The dock should not cover essential page content; page content must reserve enough bottom space when the pattern is active.
- Buttons inside both the header action row and floating dock must not wrap to two lines.
- The primary save action remains visible near the page title when in view; the dock is a continuity aid, not a replacement.

## Proposed Approach

### 1. Make Header The Single Breadcrumb Surface

Upgrade the shared header so it renders semantic breadcrumb items instead of a raw pathname string.

Implementation direction:

- introduce a small breadcrumb-building utility for app routes,
- source generic route labels from existing nav helpers and route config,
- allow workspace routes to supply record-aware labels such as RFQ title and section label,
- render the breadcrumb in the shared `Header` component with consistent truncation and current-page semantics.

This keeps the breadcrumb rule enforceable at the shell level instead of relying on each page to behave correctly.

### 2. Retire Page-Body Workspace Breadcrumbs

Pages that currently render `WorkspaceBreadcrumbs` in body content will stop doing so once the header breadcrumb is semantic and route-aware.

This primarily affects RFQ workspace pages today, including overview, details, line items, vendors, quote intake, comparison runs, approvals, negotiations, award, risk, documents, and decision trail flows.

The `WorkspaceBreadcrumbs` component may be removed entirely if no longer needed after adoption. If any non-screen usage remains, it should not be used as a page-level breadcrumb row.

### 3. Introduce A Shared Sticky Form Action Dock

Create one reusable component for edit-mode action persistence, for example:

- `StickyPageActions`
- or `FloatingFormActions`

Behavior:

- takes the canonical action set as children or structured props,
- observes the visibility of the primary inline action row,
- remains hidden while the inline actions are visible,
- appears as a bottom-floating dock when the inline actions scroll out of view,
- supports common actions such as `Cancel`, `Save`, `Save changes`, `Submit`, or similar form-primary actions.

Layout expectations:

- desktop: bottom-right floating card or dock aligned within the content shell,
- smaller screens: full-width or near-full-width bottom dock inside safe page margins,
- strong elevation and border treatment so it reads as a transient utility, not a modal.

### 4. Tighten Shared Button And Header Behavior

Shared UI adjustments should support the new pattern:

- enforce non-wrapping button labels in shared button styles,
- apply minimum-width treatment for primary action buttons where needed,
- ensure `PageHeader` action rows do not collapse into awkward multi-line wrapping for standard edit actions,
- preserve accessibility labels and disabled/loading states in both inline and sticky action surfaces.

## Scope Of Initial Adoption

The first implementation pass should cover:

- shared header breadcrumb rendering,
- removal of duplicate page-body breadcrumbs from RFQ workspace screens,
- shared sticky form action dock primitive,
- adoption on RFQ Details first,
- adoption on any other currently editable screens already using top-right save actions where the change is low-risk.

If other pages use drawers, modal-local actions, or non-form workflows, they can remain unchanged unless they clearly match the new persistence pattern.

## Component And Data Design

### Breadcrumb Data Model

Use a simple item structure in the shared header renderer:

```ts
type HeaderBreadcrumbItem = {
  label: string;
  href?: string;
  current?: boolean;
};
```

A route-aware helper should build these items from:

- current pathname,
- known route-label mapping,
- optional loaded record context for dynamic segments.

RFQ workspace route handling should normalize known section segments to user-facing labels so the header never exposes implementation-shaped segment names.

### Sticky Action Dock API

Use a reusable API that keeps the action definition in one place and renders it twice when needed:

```tsx
<StickyPageActions
  targetRef={headerActionsRef}
  active={isEditing}
  insetClassName="max-w-7xl"
>
  {actions}
</StickyPageActions>
```

Key behaviors:

- `targetRef` points to the inline action row being observed,
- `active` gates the dock to edit mode or similar writable state,
- children are the canonical action content reused in the floating dock.

The implementation should avoid divergent inline-vs-floating button logic.

## Error Handling And Edge Cases

- If dynamic record data has not loaded yet, breadcrumbs should show a stable placeholder instead of flashing raw IDs where possible.
- If record loading fails, the breadcrumb should remain truthful and fall back to a safe label such as `RFQ` or `Unavailable`, without breaking layout.
- If JavaScript observation APIs are unavailable, screens should still work with inline actions only.
- If a page has no editable state, no floating dock should render.
- If a form is in edit mode but save is disabled, the dock may still appear and reflect the disabled state to preserve continuity.

## Accessibility

- Header breadcrumb navigation must use semantic `nav` and `aria-label="Breadcrumb"`.
- The current breadcrumb item must use `aria-current="page"`.
- Floating action dock buttons must remain ordinary focusable buttons and preserve existing labels.
- The dock must not trap focus or hide inline actions from screen readers in a misleading way.
- Mobile behavior must preserve tap targets and avoid overlap with browser UI chrome.

## Testing Strategy

Add or update tests for:

- header breadcrumb rendering on standard dashboard routes,
- header breadcrumb rendering on RFQ workspace routes,
- no page-level breadcrumb row on migrated screens,
- sticky action dock hidden while inline actions are visible,
- sticky action dock appears when inline actions scroll out of view,
- button labels remain single-line under standard action text.

Preferred test levels:

- unit/component tests for breadcrumb builder and sticky dock visibility logic,
- targeted page/component tests for RFQ Details adoption,
- optional Playwright coverage for one end-to-end scroll/save continuity scenario if an existing form-flow suite already fits.

## Rollout Plan

1. Add route-aware header breadcrumb support.
2. Remove duplicate body breadcrumbs from RFQ workspace pages.
3. Add the shared sticky form-action dock primitive.
4. Adopt the dock on RFQ Details.
5. Extend adoption to other matching editable screens in the same branch if they already use top-right form actions and do not require bespoke workflow changes.

## Risks And Mitigations

### Risk: Breadcrumb regressions on dynamic routes

Mitigation:

- use existing route-label config where possible,
- cover RFQ workspace routes with explicit tests,
- fall back to safe labels instead of raw path fragments.

### Risk: Sticky dock duplicates submit side effects

Mitigation:

- render the same action handlers and button state in both surfaces,
- avoid separate save logic for the dock,
- verify pending/loading state is shared.

### Risk: Floating dock obscures content

Mitigation:

- reserve bottom spacing on adopting pages,
- keep dock height compact,
- test on desktop and narrow viewport layouts.

## Acceptance Criteria

- No application screen shows a second breadcrumb row inside page content once migrated.
- The shared header shows semantic breadcrumbs instead of a raw pathname string.
- RFQ Details no longer shows duplicate breadcrumbs.
- RFQ Details exposes a floating save-action dock when edit actions scroll out of view.
- Save-related buttons remain single-line and visually stable.
- Shared primitives are reusable for future editable screens.

## Open Implementation Choices

The implementation may choose either of these internal approaches as long as the user-facing rules above are met:

- keep route-to-breadcrumb logic inside the shared header module,
- or extract it into a route helper under shared layout/lib code.

The simpler option that best matches the existing codebase should be preferred.
