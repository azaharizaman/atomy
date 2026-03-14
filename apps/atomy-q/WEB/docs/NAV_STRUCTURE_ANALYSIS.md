# Main Navigation Structure Analysis — Atomy-Q WEB

**Scope:** Current main nav (sidebar, workspace rail, active record menu) and whether a different approach better fits the application.

---

## 1. Current Structure Summary

### 1.1 Two layout modes

| Mode | When | Nav surface |
|------|------|-------------|
| **Dashboard layout** | `/`, `/rfqs`, `/documents`, `/reporting`, `/approvals`, `/settings/*` | Full sidebar (200px) with expandable groups |
| **Workspace layout** | `/rfqs/[rfqId]/*` (e.g. overview, line-items, approvals) | Collapsible rail (12px → 200px on hover) + **Active Record Menu** (360px) |

### 1.2 Where nav is defined today

| Location | Content | Duplication |
|----------|---------|-------------|
| `(dashboard)/layout.tsx` | Full sidebar: Dashboard, Requisition (6 sub-items), Documents, Reporting, Approval Queue, Settings (5 sub-items) | **Source 1** |
| `rfqs/[rfqId]/layout.tsx` | Rail: same top-level items (Dashboard, Requisition, Documents, Reporting, Settings) but **no sub-items** (empty `NavGroup` children) | **Copy of top-level only** |
| `settings/page.tsx` | Card grid: same 5 settings links (href, label, icon, description) | **Third copy** of settings links |
| `header.tsx` | Breadcrumb = `pathname`; user menu links to `/settings/account` | No shared config; `/settings/account` may not exist |
| `active-record-menu.tsx` | RFQ-specific: `rfqLinks` (5) + `childLinks` (7) for overview, details, line-items, vendors, award, quote-intake, comparison-runs, approvals, etc. | **Separate** structure (context-specific) |

### 1.3 Requisition “nav” is really filter state

- **Sidebar:** Requisition → Active | Pending | Closed | Awarded | Archived | Draft.
- **All point to same route:** `/rfqs` with `?status=...`.
- So the sidebar is acting as **filter tabs** for one page, not as separate destinations. That inflates the sidebar and will not scale if more filters (e.g. owner, category) are added.

---

## 2. Issues With Current Approach

1. **No single source of truth**  
   Adding a new top-level section (e.g. “Vendors”) requires editing both dashboard layout and RFQ workspace layout. Settings links exist in layout sidebar and again on the settings landing page.

2. **Duplication and drift risk**  
   - Dashboard sidebar and workspace rail must stay in sync by hand.  
   - Header “Account settings” goes to `/settings/account`; sidebar has no such item (only Users & Roles, Scoring Policies, etc.). So either the route is missing or the two UIs are inconsistent.

3. **Requisition as six sub-items**  
   Six sub-items for one page (`/rfqs`) with different query params. Better: one “Requisition” link to `/rfqs` and let the RFQs page own status (and other) filters via tabs or a filter bar.

4. **Breadcrumb is not semantic**  
   Header uses raw `pathname` (e.g. `/rfqs/01KK.../overview`). No mapping from path to human-readable labels (e.g. “RFQ Overview”) without repeating route knowledge elsewhere.

5. **Active Record Menu is hardcoded**  
   RFQ section links (overview, details, line-items, quote-intake, approvals, etc.) are defined only in `active-record-menu.tsx`. Fine for context-specific nav, but any permission/feature-flag or “hide section” logic would need to be reimplemented there; a small config would help.

6. **Settings landing vs sidebar**  
   The same five settings are defined twice (sidebar SubNavItems and settings page `links`). Changing label or adding a section means editing two places.

---

## 3. Recommended Approach: Data-Driven Nav Config

A **single, central nav config** used by both dashboard and workspace, plus a **simplified Requisition** entry and **breadcrumbs from config**, is a better fit for this app.

### 3.1 Single source of truth

- **One config** (e.g. `src/config/nav.ts` or `src/config/nav.tsx`) that defines:
  - Top-level items: `id`, `label`, `href`, `icon`, `children?`, optional `permission` or `featureFlag`.
  - For “Requisition”: a **single** entry linking to `/rfqs` (no sidebar sub-items for status).
  - For “Settings”: one entry with `children` listing the 5 sub-routes (same as today), so the sidebar and any settings landing page can both read from this.

- **Dashboard layout** and **RFQ workspace layout** both consume this config:
  - Dashboard: full sidebar with groups and sub-items.
  - Workspace: same config, rendered as collapsed rail (icon-only, expand on hover) with no sub-items, or with sub-items in the expanded state for consistency.

- **Settings page** uses the same config for the card grid (or at least the same list of `{ href, label, icon, description }`), so there is only one place to add/remove/rename a settings section.

### 3.2 Requisition: one link + page-level filters

- Sidebar: **one** “Requisition” link → `/rfqs`.
- On `/rfqs`, the page owns status (and future filters) via:
  - Tabs (Active, Pending, Closed, Awarded, Archived, Draft), or
  - A filter bar with status dropdown.
- Benefits: less sidebar clutter, one place to change filter behavior, and room for more filters (owner, category, date range) without touching nav.

### 3.3 Breadcrumbs from config

- **Path → label** mapping derived from the nav config (and optionally from an RFQ section config):
  - e.g. `/` → “Dashboard”, `/rfqs` → “Requisition”, `/settings/users` → “Users & Roles”.
  - For `/rfqs/[rfqId]/overview`, combine “Requisition” + RFQ title (or “RFQ”) + “Overview” from the Active Record Menu config.
- Header (and any future breadcrumb component) uses this mapping so breadcrumbs stay consistent with nav labels and don’t show raw paths.

### 3.4 Active Record Menu (RFQ workspace)

- **Option A (minimal):** Keep the current hardcoded arrays in `active-record-menu.tsx`. No change.
- **Option B (recommended if you add permissions/feature flags):** Move RFQ section definitions to a small **RFQ section config** (e.g. `rfqSections` in `nav.ts` or a separate `rfq-nav.ts`): `id`, `label`, `pathSuffix`, `icon`, optional `badgeKey`, `permission`. The component then maps over this config and builds `href` from `rfqBase + pathSuffix`. Same UX, single place to add/remove/hide sections.

### 3.5 Fix header user menu

- Point “Account settings” to an existing route: e.g. `/settings` (settings landing) or `/settings/users` (if that’s the intended “account” scope). Alternatively add `/settings/account` and list it in the nav config so it stays in sync.

---

## 4. Conclusion

| Aspect | Current | Recommended |
|--------|--------|-------------|
| **Main nav definition** | Inline in two layouts + settings page | **Central nav config** used by dashboard, workspace rail, and settings page |
| **Requisition** | 6 sidebar sub-items (status filters) | **One** “Requisition” link; filters on `/rfqs` page |
| **Breadcrumb** | Raw pathname | **Derived from nav (and RFQ section) config** |
| **Settings** | Defined in layout + settings page | **Single list in config**; sidebar and settings landing both use it |
| **RFQ workspace sections** | Hardcoded in Active Record Menu | **Optional RFQ section config** for consistency and future permissions |

A **data-driven main nav** (single config, one Requisition entry, breadcrumbs from config, optional RFQ section config) is better suited to this application: it reduces duplication, keeps dashboard and workspace in sync, simplifies the sidebar, and makes it easier to add permissions or feature flags later.

---

## 5. Implementation: Nav config added

A minimal **central config** is in place to adopt incrementally:

- **`src/config/nav.ts`**
  - `MAIN_NAV_ITEMS`: Dashboard, Requisition (single link to `/rfqs`), Documents, Reporting, Approval Queue.
  - `SETTINGS_NAV_ITEMS` / `SETTINGS_NAV_GROUP`: same five settings sections used by sidebar and (optionally) settings page.
  - `getLabelForPath(pathname)`: returns a label for breadcrumbs.

**Suggested next steps:**

1. **Dashboard layout:** Replace hardcoded `NavItem` / `NavGroup` / `SubNavItem` with a loop over `MAIN_NAV_ITEMS` and `SETTINGS_NAV_GROUP`, mapping `id` to icon in the layout. Keep Requisition as a single link (no status sub-items); move status to `/rfqs` page (tabs or filter bar).
2. **Header:** Use `getLabelForPath(pathname)` for the breadcrumb instead of raw `pathname`.
3. **Settings page:** Import `SETTINGS_NAV_ITEMS` (or a list that includes `description`) for the card grid so settings links are defined once.
4. **Workspace layout:** Use the same `MAIN_NAV_ITEMS` and `SETTINGS_NAV_GROUP` for the rail (collapsed rendering) so new top-level items only need to be added in the config.
5. **User menu:** Change “Account settings” to `/settings` or add `/settings/account` and include it in the nav config.
