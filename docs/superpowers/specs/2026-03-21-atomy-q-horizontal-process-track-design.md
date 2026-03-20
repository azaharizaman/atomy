# Atomy-Q Horizontal Process Track (Design System v2)

**Date:** 2026-03-21  
**Product / area:** `apps/atomy-q/Design-System-v2`  
**Status:** Approved for implementation planning  

## 1. Summary

Introduce a **horizontal process / milestone track** component for Design System v2: nodes connected by segments, optional titles and descriptions, semantic coloring for **issue (amber)** and **blocked / overdue (red)**, and an optional **“today” cursor** when steps are date-anchored. The existing **Create RFQ `Stepper`** becomes a **thin adapter** over this component so call sites keep `steps` + `activeStepId` without a breaking API change.

Reference imagery (external mockups) informed layout and states; **visual execution must follow Atomy-Q tokens** (slate neutrals, indigo accent, amber/red semantics)—not purple or off-palette colors.

## 2. Problem

- The current `Stepper` in `CreateRFQComponents.tsx` is minimal (numbered dots, green/indigo/slate, separate `ProgressBar`). It does not support richer milestones, validation/issue states, schedule-relative positioning, or a shared primitive with other screens.
- The vertical `Timeline` / `ActivityFeed` serves audit-style events, not a left-to-right process rail.

## 3. Goals

1. One **reusable** horizontal track suitable for:
   - **Wizard-style** flows (e.g. Create RFQ)—compact, derived from `activeStepId`.
   - **Milestone / schedule** views—with optional descriptions, optional dates, optional today cursor.
2. **Replace the Stepper implementation** without breaking `Stepper` props used in showcases and blueprint apps.
3. Align with **DS_TOKENS** and blueprint guidance (desktop-first, no dark mode).

## 4. Non-goals

- Replacing vertical `Timeline` / `ActivityFeed`.
- Backend or RFQ domain logic (only presentation + prop contracts).
- RTL-specific layout in v1 unless trivial with existing utilities (call out if deferred).

## 5. Visual specification

### 5.1 Node states

| State | Appearance (Tailwind / token intent) |
|-------|--------------------------------------|
| Upcoming | Muted node (slate fill/border), subtle index or empty ring |
| Current | White/light surface + **indigo** ring and indigo emphasis (accent) |
| Completed (wizard default) | **Green** family (success)—parity with today’s Create RFQ Stepper |
| Issue / needs rectification | **Amber** node (warning tokens) |
| Blocked / overdue / error | **Red** node (danger tokens) |

Optional prop **`completeAppearance`**: `'success' | 'accent'` with default **`success`** for wizard; **`accent`** allows indigo-filled + check for non-wizard milestone boards.

### 5.2 Segments (connectors)

- Segments sit **between** nodes.
- **Rule (normative):** Segment *i* (between node *i* and *i+1*) reflects the **status of the work leading into step *i+1*** until that step is completed; when step *i+1* is **completed (healthy)**, that segment uses the **complete** color (green or accent per `completeAppearance`).
- If step *i+1* is **issue** or **blocked**, the segment uses **amber** or **red** respectively (same weight as the node so the rail reads as one story).

### 5.3 Labels

- **Title:** `text-slate-800` / `font-medium` (compact wizard may use `text-xs` truncate as today).
- **Description:** optional, `text-slate-500`, max two lines or truncate with tooltip deferred to v2 polish.

### 5.4 Today cursor (schedule mode)

- Opt-in only: e.g. `showTodayCursor` + per-step anchor dates (ISO strings or `Date`; document timezone policy as **caller supplies consistent instant or calendar date**).
- Vertical rule: danger line weight (`red-500` / `dangerDot` intent), full height of the track row (and optional extension into label area).
- Label: short **“Today”** + optional tooltip with formatted date.
- Position: linear interpolation by time between the two bounding anchors; **clamp** to the rail. If “today” is outside the first/last anchor, **hide** the cursor by default (configurable `pinToEnds?: boolean` if product later requires it—default **false** to avoid misleading placement).
- **Accessibility:** meaningful `aria-label` (and/or visually hidden text) describing date and relative position; do not rely on color alone for critical meaning (caption + SR text).

### 5.5 Responsive behavior

- Desktop-first: full layout as designed.
- Narrow viewports: horizontal scroll with optional snap, or hide descriptions—document chosen behavior in showcase.

## 6. Architecture

### 6.1 Primary component

Working name: **`HorizontalProcessTrack`** (final export name may shorten to `ProcessTrack` if preferred).

**Responsibilities:**

- Render nodes, segments, optional descriptions, optional today overlay.
- Accept an explicit list of steps with **visual state** and optional **date** per step (schedule mode).

### 6.2 Stepper adapter

**`Stepper`** in `CreateRFQComponents.tsx` **reimplements** as a wrapper:

- Input: `steps: { id, label, ...optional extensions }[]`, `activeStepId`, `className`.
- Derive `activeIndex` with existing behavior: `Math.max(steps.findIndex(...), 0)` if id missing.
- Map indices to `upcoming | current | complete` for each step.
- Preserve **`ProgressBar`** below the track with default **`showProgressBar={true}`** (or equivalent) for visual parity unless product explicitly removes it.

### 6.3 Forward-compatible step fields (optional on `StepperStep`)

Extend `StepperStep` optionally (no breaking change for callers that only pass `id` + `label`):

- `description?: string`
- `status?: 'default' | 'issue' | 'blocked'` (names TBD in implementation; maps to amber/red segment+node)
- `date?: string` (ISO) for schedule + today cursor when enabled at the adapter level

## 7. API sketch (implementation detail)

Exact TypeScript names are left to the implementer; shape should include:

- `steps: Array<{ id: string; label: string; description?: string; state: ...; date?: string }>`
- `variant?: 'detailed' | 'compact'` (or `density`)
- `completeAppearance?: 'success' | 'accent'`
- `showTodayCursor?: boolean`
- `today?: Date` (for tests/storybook; default `new Date()` in browser when cursor enabled)
- `className?: string`

`Stepper` only forwards a subset and derives `state` from `activeStepId`.

## 8. File / export expectations

- New file(s) under `Design-System-v2/src/app/components/ds/` (e.g. `HorizontalProcessTrack.tsx`), exported from the same barrel pattern as other DS components.
- `CreateRFQComponents.tsx`: `Stepper` implemented via the new primitive + `ProgressBar` as today.
- **Showcase:** Create RFQ section updated to use new visuals; add stories/examples for (1) wizard default, (2) detailed + one issue step, (3) schedule + today cursor.
- **Screen-Blueprint:** If `CreateRFQComponents.tsx` is duplicated there, apply the same change to avoid drift (grep for `Stepper`).

## 9. Testing

- Unit tests for: derived indices from `activeStepId`; segment color selection per step state; today cursor position/clamp/hide-when-out-of-range (pure functions testable without DOM if logic is extracted).
- Visual regression optional; showcase acts as manual acceptance.

## 10. Open decisions (minor, resolve at implementation)

- Final export name: `HorizontalProcessTrack` vs `ProcessTrack`.
- Whether `Stepper` re-exports types only from the new module or keeps `StepperStep` in `CreateRFQComponents.tsx`.

## 11. Approval

Design brainstorm approved by product owner (chat, 2026-03-21): horizontal track + semantic colors + today cursor + **Stepper replacement via adapter**, **completed steps remain green (success) by default** for RFQ parity.

---

**Next step:** Implementation plan (`writing-plans` workflow) after any edits to this spec the owner requests.
