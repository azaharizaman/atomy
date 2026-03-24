# Task dashboard widgets — Design-System-v2 alignment & gaps

**Date:** 2026-03-24  
**Branch:** `brainstorm/design-system-dashboard-components`  
**Scope:** `apps/atomy-q/Design-System-v2`  
**Status:** Brainstorm / design guideline (not implemented)

## Decisions

| Topic | Resolution |
|-------|------------|
| Workflow / chart colors | **DS semantics only** — map states to existing token families (`neutral`, `warning`, `accent`/`info`, `success`) via `DS_TOKENS`, `StatusBadge`, and related patterns. **No** parallel “marketing” or chart-only hex palette for pixel parity with the reference image. |

## Reference

Visual guideline: task-management dashboard mockup (status distribution, KPI row, status mini-cards, horizontal task calendar).

**Reference image (repo):** [`docs/superpowers/specs/assets/2026-03-24-task-dashboard-reference.png`](assets/2026-03-24-task-dashboard-reference.png)

## Goals

1. **Reuse** existing Atomy-Q DS foundations: `DS_TOKENS` (`tokens.ts`), `Card` / `SectionCard`, `Badge` / `StatusBadge`, `KPIScorecard`, `ChartContainer` (Recharts), `Timeline` (vertical), `HorizontalProcessTrack`, `DashboardCards`, typography (Inter), slate surfaces, indigo accent.
2. **Translate** the reference layout’s color story (orange / purple / green blocks) into **semantic roles** mapped to DS: e.g. “in progress” → `warning` / amber, “in review” → `accent` / indigo or `info` / blue, “completed” → `success` / green, “to-do” → `neutral` / slate — so we do not introduce one-off hex colors unless added to tokens intentionally.
3. **Specify** a small set of **new composable components** where no first-class DS pattern exists today, and document how they compose with primitives.

## What already exists (map reference → DS)

| Reference region | Intent | Existing DS coverage |
|------------------|--------|----------------------|
| Page background + white cards | Shell | `DS_TOKENS.color.pageBg`, `surface`, `border`, `shadow.card`; `Card` / `SectionCard` |
| Card headers + “…” menu | Chrome | `SectionHeader` / manual header row + `DropdownMenu` / `IconButton` |
| KPI row (large number, trend badge, icon) | Metrics | `KPIScorecard` (`trend`, `progress`, `badge`); `PipelineStatCard` (simpler, pipeline-specific) |
| Horizontal bar / trend | Sparkline feel | `ProgressBar`, `MiniProgress`; `chart.tsx` + Recharts for richer charts |
| Category list with horizontal bars | Distribution | `CategoryBreakdownCard` (indigo bars; generalize colors via props or new thin wrapper) |
| Vertical timeline of events | Activity | `Timeline` (vertical, actor + timestamp) |
| Step / phase track | Process | `HorizontalProcessTrack` (steps, today cursor optional — **not** a day-gridded Gantt) |

## Gaps — proposed new components

These are **not** duplicates of existing exports; they close specific UI contracts the reference shows.

### 1. `StatusDistributionCard` (working name)

**Purpose:** “Task status overview” — segmented **composition** showing share per status (percent + optional counts).

**Behavior:**

- Accepts an ordered list of segments: `{ id, label, pct, count?, tone }` where `tone` maps to a **small fixed palette** derived from DS semantics (`neutral`, `warning`, `accent`/`info`, `success`), not arbitrary hex.
- Renders:
  - **Primary visualization:** either
    - **A)** Stacked horizontal bar (single row, heights implicit — easiest, accessible), or
    - **B)** “Spark column” strip: N thin vertical bars with heights proportional to segment `pct` (closer to reference; slightly more bespoke CSS).
  - **Legend:** reuse `StatusBadge` / pill pattern or compact color swatch + label (align with `Tag` / `DsTag`).

**Compose with:** `Card`, optional `DropdownMenu` for overflow actions.

**Avoid:** Hard-coding four statuses; support 2–6 segments for other domains (RFQ stages, approvals).

### 2. `StatusBreakdownGrid` (working name)

**Purpose:** “Status breakdown” — grid of **mini metric tiles** (one per status): large %, task count, status pill, optional info affordance.

**Behavior:**

- Props: same segment model as above (shared type recommended).
- Layout: responsive grid (`grid-cols-2` on narrow, optional `grid-cols-4` on wide).
- Optional `onInfoClick` per tile (or single handler with `id`) for tooltips / help.

**Compose with:** `Card` (flat inner cells), `StatusBadge` / semantic background from tokens (`successBadge`, `warningBadge`, etc.).

### 3. `TrendMetricCard` (working name) — optional thin wrapper

**Purpose:** KPI card matching reference: **icon** (leading), **primary value**, **trend chip** (e.g. “+10% from last month”), **link/arrow** affordance to drill down.

**Relation to `KPIScorecard`:** Extend `KPIScorecard` with optional `leadingIcon` and `footerAction` / `href` **or** add a thin `TrendMetricCard` that delegates body layout to `KPIScorecard` internals to avoid duplication.

**Recommendation:** Prefer **extending `KPIScorecard`** first; only split if prop surface becomes unwieldy.

### 4. `ScheduleStrip` / `TaskCalendarStrip` (working name)

**Purpose:** Horizontal **date axis** (e.g. Mon–Sun or custom range) with **tasks as pills** positioned by start/end or single day; **today** vertical marker; stacked rows to reduce overlap (Gantt-lite).

**Not the same as:** `HorizontalProcessTrack` (fixed steps, process health) or vertical `Timeline` (event list).

**Behavior:**

- Inputs: `range: { start: Date; end: Date }`, `today?: Date`, `items: { id, label, start: Date, end?: Date, lane?: number, emphasis?: 'default' | 'today' }[]`.
- Accessibility: list fallback or `role="img"` with `aria-label` summarizing count; keyboard focus on items if interactive.
- Styling: pills use `surface`, `border`, `textPrimary`; **today** highlight uses `accent` / `textInverse` on dark pill per DS (not pure `#000` unless we add a `surfaceInverse` token).

**Compose with:** `ScrollArea` if overflow; `Tooltip` for truncated labels.

**Dependency note:** Pure CSS + date math first; optional later enhancement with virtualization for many items.

## Token considerations

- Express workflow lanes **only** through DS semantics, e.g. `workflowStatus: { todo: 'neutral', inProgress: 'warning', inReview: 'accent', done: 'success' }` mapping to existing Tailwind classes in `DS_TOKENS` / `StatusBadge` variants.
- Implementations **must not** introduce ad hoc hex colors for these widgets; extend `DS_TOKENS` only if a genuinely new semantic role is approved for the whole system (not a one-off chart exception).

## Showcase & docs

- Add a **“Task / workflow dashboard”** subsection on `ShowcasePage.tsx` with live examples once implemented: `StatusDistributionCard`, `StatusBreakdownGrid`, extended KPI, `ScheduleStrip`.
- Update `Design-System-v2/IMPLEMENTATION_SUMMARY.md` with new components and any token additions.

## Approaches (trade-offs)

| Approach | Pros | Cons |
|----------|------|------|
| **A — Minimal new files** | Extend `KPIScorecard`, one `StatusDistributionCard`, reuse `CategoryBreakdownCard` patterns | Schedule strip still needs new file |
| **B — Full parity with reference** | Pixel-close to mockup | More custom CSS; risks drifting from DS semantics |
| **C — Charts-first** | Use Recharts for distribution + strip | Heavier bundle; strip may feel “chart-like” vs. bespoke pills |

**Recommendation:** **A** for first delivery: semantic mapping, two new composites (`StatusDistributionCard`, `ScheduleStrip`), `StatusBreakdownGrid`, and `KPIScorecard` extension.

## Testing (when implemented)

- Visual regression on `ShowcasePage` section.
- Unit tests for date layout math in `ScheduleStrip` (pure functions extracted to `*.ts`).
- A11y spot-check: contrast on pills and today marker; screen reader summary for chart-like regions.

## Out of scope (this brainstorm)

- Wiring to real task APIs in `WEB` app.
- Full calendar month view (use `ui/calendar.tsx` separately if needed).

---

**Next step:** Review this spec; then use the implementation-plan skill to break down file-level work and ordering.
