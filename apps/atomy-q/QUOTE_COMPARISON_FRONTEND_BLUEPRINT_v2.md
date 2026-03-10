# Atomy-Q: Agentic Quote Comparison SaaS (Blueprint v2)

## Product Overview

**The Pitch:** A procurement intelligence workspace that ingests vendor quotes, biddings, tender submissions, normalizes line-items and multi-segment tender submissions, compares vendors side-by-side, applies policy-aware scoring, and routes high-risk outcomes through governed approvals with full decision traceability.

**For:** Requesters, Buyers, Approvers, Procurement Managers, Admins, Auditors, and Executives in medium-to-large organizations.

**Device:** Desktop-first (optimized for 1440px+), responsive down to large-size tablet.

**Design Direction:** "Operational Intelligence Console." Moderate to high density (especially on data-rich pages), high-signal interface with clear hierarchy, fast keyboard workflows, and explicit compliance indicators. Prioritize familiar user experience: save-button positioning is consistent throughout, multi-step processes show a summary timeline. Use a homogenous color scheme; contrasting color is reserved for buttons, links, and clickable triggers where attention is demanded. NO DARK MODE. All modals are slide-overs from the right, overlaying the current screen with smart width filling (min 25%-30%).

**Inspired by:** Bloomberg Terminal (modernized), Linear (density), Stripe Dashboard (table precision), Keeyns (whitespace and hierarchical tabs)

**Tenant Context:** Each user is tied to one tenant. No tenant switching UI.

---

## Information Architecture

### Prime Record Concept

The main navigation exposes only **prime record types** -- records that can exist independently without depending on another record's existence. In Atomy-Q, the prime records are:

- **Requisition (RFQ)** -- the central working entity
- **Document** -- files that can exist independently of any single RFQ
- **Report** -- generated analytics and exports

All other entities (Quote Submissions, Comparison Runs, Approvals, Negotiations, Awards, Normalization, Risk Items, etc.) are **child records** of an RFQ and are accessed from within the RFQ workspace.

### Main Navigation (sidebar)

The sidebar is the primary navigation area. **Accordion behavior**: when one expandable menu is opened, any other expanded menu collapses.

```
Main Navigation
├── Dashboard
├── Requisition (RFQ)          [expandable]
│   ├── Active
│   ├── Closed
│   ├── Awarded
│   ├── Archived
│   └── Draft
├── Document
├── Reporting
└── Settings                   [expandable]
    ├── Users & Roles
    ├── Scoring Policies
    ├── Templates
    ├── Integrations
    └── Feature Flags
```

**Rules:**
- Only one expandable section is open at a time. Opening "Requisition" collapses "Settings" and vice versa.
- The Requisition sub-items are status filters. Clicking "Active" shows the RFQ list filtered to active RFQs. The sub-items also show a count badge (e.g., "Active 12").
- "Document" navigates to the global Document/Evidence Vault (all documents across all RFQs).
- "Reporting" navigates to the Reports & Analytics dashboard.
- The RFQ sub-menu remains expanded and highlighted when the user is inside an RFQ workspace, with the current status filter highlighted.

### User Menu (avatar dropdown)

Located in the top bar, the user avatar opens a dropdown with:

- User Settings (profile, preferences)
- Notifications (links to notification feed/panel)
- Logout

### Top Bar

Present in both layouts. Contains:

- Global search input (with `/` keyboard shortcut)
- Quick action buttons: "New RFQ", "AI Insights"
- Notification bell with unread count badge
- User avatar (opens User Menu dropdown)

### Route Hierarchy

```
/dashboard
/rfqs                           RFQ List (default: Active filter)
/rfqs?status=active             RFQ List filtered
/rfqs?status=closed             RFQ List filtered
/rfqs?status=awarded            RFQ List filtered
/rfqs?status=archived           RFQ List filtered
/rfqs?status=draft              RFQ List filtered
/rfqs/create                    Create RFQ (Default Layout)
/rfqs/:rfqId                    RFQ Workspace (Workspace Layout)
/rfqs/:rfqId/overview           RFQ Overview tab (primary)
/rfqs/:rfqId/details            RFQ Details tab (primary)
/rfqs/:rfqId/line-items         RFQ Line Items tab (primary)
/rfqs/:rfqId/vendors            Invited Vendors tab (primary)
/rfqs/:rfqId/award              Award tab (primary, conditional)
/rfqs/:rfqId/intake             Quote Intake list (secondary tab)
/rfqs/:rfqId/intake/:id         Quote Intake detail (workspace content)
/rfqs/:rfqId/intake/:id/normalize   Normalization workspace (workspace content)
/rfqs/:rfqId/runs               Comparison Runs list (secondary tab)
/rfqs/:rfqId/runs/:runId        Comparison Matrix (workspace content)
/rfqs/:rfqId/approvals          Approvals list (secondary tab)
/rfqs/:rfqId/approvals/:id      Approval Detail (workspace content)
/rfqs/:rfqId/negotiations       Negotiations list (secondary tab)
/rfqs/:rfqId/negotiations/:id   Negotiation detail (workspace content)
/rfqs/:rfqId/documents          Documents for this RFQ (secondary tab)
/rfqs/:rfqId/risk               Risk & Compliance (workspace content)
/approvals                      Approval Queue (Default Layout, cross-RFQ aggregation)
/documents                      Global Document/Evidence Vault
/reporting                      Reports & Analytics
/settings/users                 Users & Roles
/settings/scoring-policies      Scoring Policies
/settings/templates             RFQ Templates
/settings/integrations          Integrations Configuration
/settings/feature-flags         Feature Flags
```

---

## Layout System

Atomy-Q uses two distinct layouts.

### Layout 1: Default Layout

Used for: Dashboard, RFQ List, Create RFQ, Approval Queue (cross-RFQ), Global Documents, Reporting, Settings pages, and any non-RFQ-specific views. When a non-RFQ prime record is selected (e.g., a Document from the global Document list), its detail/record view also renders in Default Layout.

```
┌──────────────────────────────────────────────────────────────┐
│                         Top Bar                              │
│  [Search /]  [New RFQ] [AI Insights]     [Bell] [Avatar ▼]  │
├────────────┬─────────────────────────────────────────────────┤
│            │                                                 │
│   Main     │              Content Area                       │
│   Nav      │                                                 │
│            │   Shows either:                                 │
│  Dashboard │   - Record list (data table, filters, bulk      │
│  RFQ  ▼    │     actions)                                    │
│   Active   │   - Record view (when clicking into a record    │
│   Closed   │     that does NOT open workspace layout)        │
│   ...      │   - Form view (Create RFQ)                      │
│  Document  │   - Dashboard widgets                           │
│  Reporting │   - Settings forms                              │
│  Settings ▼│                                                 │
│   Users    │                                                 │
│   ...      │                                                 │
│            │                                                 │
├────────────┴─────────────────────────────────────────────────┤
│  Footer: Version | Environment | Status | Privacy | Terms    │
└──────────────────────────────────────────────────────────────┘
```

**Dimensions:**
- Main Nav: ~200px wide when expanded.
- Content Area: fills remaining width.

### Layout 2: Workspace Layout

Used for: Any RFQ that is being actively worked on. Activated when a user clicks into a specific RFQ from the RFQ List. The RFQ is the only entity that uses Workspace Layout -- it is the primary working entity in this application. Entering an RFQ always means entering Workspace Layout; there is no separate "view-only" mode for an RFQ in Default Layout.

```
┌──────────────────────────────────────────────────────────────┐
│                         Top Bar                              │
│  [Search /]  [New RFQ] [AI Insights]     [Bell] [Avatar ▼]  │
├──┬─────────────────┬─────────────────────────────────────────┤
│  │                 │  Breadcrumbs: RFQs > RFQ-2401 > Intake  │
│  │  Active Record  ├─────────────────────────────────────────┤
│  │  Menu           │                                         │
│  │                 │           Work Surface                   │
│  │  ┌───────────┐  │                                         │
│  │  │ RFQ-2401  │  │  Displays:                              │
│  │  │ Server HW │  │  - Tab content (primary or secondary)   │
│  │  │ ● Active  │  │  - Child record list (data table)       │
│  │  │ 5 vendors │  │  - Child record detail                  │
│  │  │ $1.2M est │  │  - Specialized workspace (matrix,       │
│  │  └───────────┘  │    normalization)                        │
│CN│                 │                                         │
│  │  PRIMARY TABS   │                                         │
│  │  Overview       │                                         │
│  │  Details        │                                         │
│  │  Line Items     │                                         │
│  │  Vendors        │                                         │
│  │  Award          │                                         │
│  │                 │                                         │
│  │  CHILD RECORDS  │                                         │
│  │  Quote Intake 8 │                                         │
│  │  Comp. Runs   3 │                                         │
│  │  Approvals    2 │                                         │
│  │  Negotiations 1 │                                         │
│  │  Documents   12 │                                         │
│  │  Risk & Compl.  │                                         │
│  │  Decision Trail │                                         │
│  │                 │                                         │
├──┴─────────────────┴─────────────────────────────────────────┤
│  Footer (minimal)                                            │
└──────────────────────────────────────────────────────────────┘

CN = Collapsed Nav (icon-only, ~48px). Hovering expands it as
     an overlay on top of the Active Record Menu.
```

**Dimensions:**
- Collapsed Nav (CN): ~48px, icon-only. Hover or click expands the full nav as an overlay (does not push content).
- Active Record Menu: ~25% of viewport width (~360px at 1440px). Fixed position, scrollable.
- Work Surface: fills remaining width. Contains a breadcrumb bar at the top.

### Active Record Menu Anatomy

The Active Record Menu is the left panel in Workspace Layout. It has two zones:

**Zone 1: Record Snippet (top)**
- RFQ identifier (number + title)
- Status badge (Active, Closed, Awarded, etc.)
- Key metrics: vendor count, quote count, estimated value, savings %
- Lifecycle action button (context-sensitive: "Close for Submissions", "Reopen", etc.)
- Expand/collapse toggle to show or hide the full snippet

**Zone 2: Navigation (below snippet)**
Two groups separated by a subtle divider:

- **Primary Record Tabs** (links that navigate the Work Surface to RFQ's own data):
  - Overview (KPI scorecards, activity feed)
  - Details (metadata, terms, deadlines)
  - Line Items (RFQ line items table)
  - Vendors (invited vendors roster)
  - Award (conditional -- only visible when award is in-progress or completed)

- **Child Record Navigation** (links that navigate the Work Surface to child record lists):
  - Quote Intake (count badge)
  - Comparison Runs (count badge)
  - Approvals (count badge, urgent indicator if SLA at risk)
  - Negotiations (count badge)
  - Documents (count badge)
  - Risk & Compliance (status indicator: clear / flagged / pending)
  - Decision Trail

Clicking a **primary tab** loads that tab content in the Work Surface.
Clicking a **child record link** loads the child record list in the Work Surface. Clicking a specific child record from that list loads the child record detail/workspace in the Work Surface (the Active Record Menu stays visible).

### Breadcrumb Rules

Breadcrumbs appear at the top of the Work Surface in Workspace Layout.

**Pattern:** `RFQs > [RFQ Title] > [Child Type] > [Child Record]`

Examples:
- `RFQs > Server Infrastructure Refresh > Overview`
- `RFQs > Server Infrastructure Refresh > Quote Intake`
- `RFQs > Server Infrastructure Refresh > Quote Intake > Dell Quote #3`
- `RFQs > Server Infrastructure Refresh > Comparison Runs > Run #5 > Matrix`
- `RFQs > Server Infrastructure Refresh > Approvals > APR-00412`

Clicking any breadcrumb segment navigates to that level. The "RFQs" segment returns to the RFQ List (Default Layout).

### Slide-Over Modals

All modals in Atomy-Q are slide-overs from the right edge. They overlay the current screen.

- **Small** (sm): ~30% width. For confirmations, simple forms.
- **Medium** (md): ~40% width. For detail forms, evidence views.
- **Large** (lg): ~50% width. For complex editors, comparison views.
- **Extra-large** (xl): ~60% width. For document previews, full MCDA breakdowns.

Slide-overs can stack (a slide-over can open another slide-over). Maximum stack depth: 2.

---

## Screen Specifications

### 1. Sign In

**Purpose:** Authenticate users and enforce access policy before entering the application.

**Layout:** Standalone centered authentication card with optional split-screen brand panel. No navigation shell.

**Key Elements:**
- Email/username input.
- Password input.
- SSO sign-in button.
- Forgot password link.
- MFA step prompt container.

**States:**
- Idle.
- Invalid credentials.
- Account locked.
- MFA required.
- Session expired (re-authentication).

**Components:**
- Auth form.
- Inline error message.
- Security notice text.
- "Remember this device" checkbox.
- Session expiry banner.

**Interactions:**
- Click Forgot Password opens `Reset Password` slide-over.
- Successful credentials + MFA policy opens `Verify MFA` slide-over.
- Session timeout redirects to Sign In with `?session_expired=1` and a banner; after re-auth, user returns to their previous route.
- "Remember this device" stores a device token; skips MFA for the configured duration (default 30 days).

### 2. Home Dashboard

**Purpose:** Provide role-aware overview of workload, risk, and priority actions.

**Layout:** Default Layout. Content area contains a smart multi-layout of widgets.

**Route:** `/dashboard`

**Key Elements:**
- KPI scorecards (active RFQs, pending approvals, savings trend, alerts).
- My Tasks list or recently opened records.
- Risk/SLA alert panel.
- Recent comparison runs.

**States:**
- Data loading.
- Empty (no active work).
- Alert-heavy (multiple urgent items).

**Components:**
- KPI card (clickable; navigates to filtered RFQ list or Approval Queue).
- Status badge.
- Priority tag.

**Interactions:**
- Click "New RFQ" navigates to Create RFQ (`/rfqs/create`).
- Click an alert deep-links to the relevant RFQ workspace or approval.
- Click KPI "Active RFQs" navigates to `/rfqs?status=active`.
- Click KPI "Pending Approvals" navigates into the relevant RFQ's approvals tab.

### 3. RFQ List

**Purpose:** Browse and manage the RFQ pipeline by status.

**Layout:** Default Layout.

**Route:** `/rfqs` (with `?status=` query param for filtering)

**How to get here:** Main Nav > "Requisition (RFQ)" > click any status sub-item (Active, Closed, etc.) or click "Requisition" itself for the default view (Active).

**Key Elements:**
- Status sub-navigation is reflected in the sidebar (Active, Closed, Awarded, Archived, Draft). The currently active filter is highlighted in the sidebar.
- Search bar.
- Additional filters: owner, category, date range.
- Compact data table with sortable columns: Checkbox, Expand chevron, ID, RFQ (title with owner avatar and name as subtitle), Status, Deadline, Est. Value, Actions overflow menu.
- Expandable rows: chevron reveals detail area with Category, Deadline, Vendors count, Quotes count, Est. Value, and Savings.
- Bulk action toolbar (appears on row selection).

**States:**
- Empty list (no RFQs match current status filter).
- Filtered no results.
- Paginated list.
- Bulk selection active.
- Row expanded.

**Components:**
- Filter chips.
- Sortable table headers.
- Pagination controls.
- Row selection checkboxes.
- Expand/collapse chevron per row.
- Bulk action toolbar: Close Selected, Archive Selected, Assign Owner, Export Selected.

**Interactions:**
- Click row (anywhere except checkbox/chevron/overflow) opens the **RFQ Workspace** (Workspace Layout, `/rfqs/:rfqId`).
- Click expand chevron toggles the expandable detail area for that row (one at a time).
- Click "Create RFQ" (or top-bar "New RFQ") navigates to `/rfqs/create`.
- Select rows via checkbox, then click bulk action: confirmation slide-over.
- Right-click row (or overflow "..."): Open, Duplicate, Archive, Export PDF. "Duplicate" pre-fills Create RFQ.

### 4. Create RFQ

**Purpose:** Create sourcing requests with complete commercial and technical context.

**Layout:** Default Layout. Multi-step form with sticky action bar.

**Route:** `/rfqs/create`

**How to get here:** Dashboard "New RFQ" button, Top Bar "New RFQ" shortcut, or RFQ List "Create RFQ" button.

**Key Elements:**
- RFQ metadata form (title, category, department, description).
- Line item editor (add/edit/remove line items).
- Terms/deadline section (submission deadline, evaluation method, payment terms).
- Attachment uploader (supporting documents).
- Stepper showing progress across form steps.

**States:**
- Draft (unsaved).
- Validation error.
- Saved draft.

**Components:**
- Stepper (horizontal progress bar with step labels).
- Form fields.
- File upload dropzone.

**Interactions:**
- Click "Create from Template" opens `RFQ Template Picker` slide-over.
- Leaving with unsaved changes opens `Discard Changes?` slide-over.
- Click "Save Draft" persists without validation; stays on form.
- Click "Submit/Publish" validates all steps; on success navigates to the new RFQ in Workspace Layout (`/rfqs/:newId`); on error highlights the first failing step.

### 5. RFQ Workspace (Detail View)

**Purpose:** Central workspace for operating one RFQ throughout its entire lifecycle -- from quote collection through comparison, approval, negotiation, and award.

**Layout:** Workspace Layout.

**Route:** `/rfqs/:rfqId` (defaults to `/rfqs/:rfqId/overview`)

**How to get here:** RFQ List > click a row. Or any deep link / breadcrumb back to the RFQ.

This is the most important screen in Atomy-Q. The user spends the majority of their time here. It consolidates all RFQ-related information and child records into one navigable workspace.

**Active Record Menu content for this RFQ:**

Zone 1 (Snippet):
- RFQ number and title (e.g., "RFQ-2401 Server Infrastructure Refresh")
- Status badge (Draft / Active / Closed / Awarded / Archived)
- Key metrics row: Vendors (count), Quotes (count), Est. Value, Savings %
- Primary lifecycle action button:
  - Draft: "Submit/Publish"
  - Active: "Close for Submissions"
  - Closed: "Reopen" (if policy permits)
  - Awarded: "View Award"

Zone 2 (Navigation):

Primary Record Tabs:
- **Overview** -- KPI scorecards (total quotes, normalization progress, comparison status, approval status, savings), activity timeline/feed.
- **Details** -- RFQ metadata, terms, deadlines (editable if Draft; read-only otherwise).
- **Line Items** -- Data table of RFQ line items. Add/edit/remove when Draft.
- **Vendors** -- Invited vendors roster with invitation status (Not invited, Invited, Responded, Declined). "Invite Vendors" action. "Send Reminder" action.
- **Award** -- (Conditional: only visible when at least one comparison run has been approved or when an award is in progress/completed.) Winner recommendation, split allocation, debrief controls, sign-off area.

Child Record Navigation:
- **Quote Intake** (count badge) -- list of quote submissions for this RFQ.
- **Comparison Runs** (count badge) -- list of comparison runs for this RFQ.
- **Approvals** (count badge, urgent badge if SLA at risk) -- list of approval items for this RFQ.
- **Negotiations** (count badge) -- list of negotiations for this RFQ.
- **Documents** (count badge) -- documents tagged to this RFQ.
- **Risk & Compliance** (status indicator) -- consolidated risk view for this RFQ.
- **Decision Trail** -- audit ledger filtered to this RFQ.

**Work Surface (default: Overview tab):**

The Overview tab shows:
- 4 KPI scorecards:
  - Quotes Received (count vs expected)
  - Normalization Progress (% complete)
  - Comparison Status (latest run status: Not run / Preview / Final / Stale)
  - Approval Status (Pending / Approved / Rejected / None)
- Activity timeline: chronological feed of events (quote uploaded, vendor responded, run completed, approval decision, etc.).

**States:**
- Draft (all tabs editable; child records may be empty).
- Active (submissions open; intake begins populating).
- Closed (no new submissions; comparison runs can be executed).
- Awarded (read-only workflow; award tab is prominent).
- Archived (read-only, greyed out lifecycle actions).

**Interactions (lifecycle actions from Active Record Menu snippet):**
- "Close for Submissions" opens `Close RFQ` slide-over with closing date and reason.
- "Reopen" opens `Reopen RFQ` slide-over with mandatory reason and optional new deadline.
- "Submit/Publish" (when Draft) validates and publishes; status changes to Active.
- Click Edit (on Details tab) opens inline editing or a slide-over for metadata fields.
- "Invite Vendors" (on Vendors tab) opens `Send Invitations` slide-over.
- Clicking any child record navigation link loads that child list into the Work Surface.

### 6. Quote Intake List (RFQ child)

**Purpose:** View and triage all quote submissions for one RFQ.

**Layout:** Workspace Layout. Displayed in the Work Surface when "Quote Intake" is selected from the Active Record Menu.

**Route:** `/rfqs/:rfqId/intake`

**How to get here:** RFQ Workspace > Active Record Menu > "Quote Intake" child link.

**Key Elements:**
- Data table: File name, Vendor, Status, Parse confidence, Uploaded at.
- Upload zone (drag-and-drop bar above the table; supports multi-file upload).
- Filters: status (Processing, Parsed, Accepted, Rejected, Error), vendor.
- "Upload Quote" button.

**States:**
- Empty (no submissions yet; prominent upload CTA).
- Processing (documents being parsed, spinner indicators).
- Mixed statuses (some accepted, some pending triage).
- All accepted (normalization ready banner).

**Components:**
- Status badge (processing, parsed, accepted, rejected, error, pending assignment).
- Confidence badge (high / medium / low with color coding).
- Upload progress indicator.
- Bulk actions: Accept Selected, Reject Selected.

**Interactions:**
- Click "Upload Quote" opens `Upload & Parse Quote` slide-over. Pre-selects the current RFQ; user selects vendor and files.
- Click a row navigates to Quote Intake Detail (`/rfqs/:rfqId/intake/:id`). Work Surface updates; Active Record Menu stays.
- Click "Accept" (per row action or bulk) transitions status. Variant "Accept & Normalize" navigates to Normalization.
- Click "Reject" (per row action) opens `Confirm Reject` slide-over with reason.

**Breadcrumb:** `RFQs > [RFQ Title] > Quote Intake`

### 7. Quote Intake Detail (RFQ child record)

**Purpose:** Review a specific quote submission's parsed data, validation results, and line-item mappings.

**Layout:** Workspace Layout. Displayed in the Work Surface.

**Route:** `/rfqs/:rfqId/intake/:id`

**How to get here:** Quote Intake List > click a row.

**Key Elements:**
- Two tabs within the Work Surface:
  - **Overview Tab:** Document preview (left pane), vendor details + parse confidence bar + validation results (right pane).
  - **Parsed Line Items Tab:** Data table of extracted line items with expandable rows (currency, total, mapped RFQ line). Manual override and revert controls.
- Action bar: Accept, Reject, Accept & Normalize, Replace Document, Re-Parse.

**States:**
- Processing (parsing in progress; read-only).
- Parsed with warnings (low confidence lines highlighted).
- Accepted (ready for normalization; line mapping editable).
- Rejected (read-only with rejection reason displayed).
- Error (ingestion failed; error banner with details).

**Components:**
- Document preview panel.
- Confidence bar.
- Validation error callout.
- Expandable table rows with chevron toggle.
- Manual override modal (pick RFQ line, optional reason).
- Info chip ("Override") on overridden lines.
- Revert button (only on overridden lines).

**Interactions:**
- Click "Accept & Normalize" transitions status to Accepted and navigates to Normalization Workspace (`/rfqs/:rfqId/intake/:id/normalize`).
- Click "Replace Document" opens `Replace & Re-Parse` slide-over.
- Click "Re-Parse" opens `Confirm Re-Parse` slide-over.
- Expand a line item row to see detail and, for accepted submissions, the "Map to RFQ line" picker dropdown.
- Manual override (per line): opens override slide-over. Persisted via PUT endpoint. Override lines show info chip.
- Revert (per overridden line): clears override, restores AI mapping.

**Breadcrumb:** `RFQs > [RFQ Title] > Quote Intake > [Vendor Name / File Name]`

### 8. Quote Normalization Workspace (RFQ child)

**Purpose:** Resolve UoM, currency, and taxonomy mappings to make vendor quotes directly comparable.

**Layout:** Workspace Layout. Displayed in the Work Surface.

**Route:** `/rfqs/:rfqId/intake/:id/normalize`

**How to get here:** Quote Intake Detail > "Accept & Normalize". Or from a Comparison Run readiness warning linking to incomplete normalization.

**Key Elements:**
- Split workspace: source lines (left) and normalized target mapping grid (right).
- Mapping editor (dropdown per line to map to RFQ line item / taxonomy).
- UoM/currency conversion controls.
- Conflict queue (flagged lines with mapping conflicts).
- Bulk action toolbar.
- Lock status indicator.

**States:**
- Unmapped (freshly accepted; no mappings yet).
- Partially mapped.
- Fully normalized (all lines mapped; ready for comparison).
- Locked for comparison run (read-only).
- Stale (source document was replaced or re-parsed; mappings need review).

**Components:**
- Mapping dropdown (taxonomy codes, RFQ line items).
- Conversion badge (shows original -> normalized values).
- Conflict indicator (warning icon on lines with mapping conflicts).
- Bulk selection checkboxes.
- Lock badge.

**Interactions:**
- Mapping conflict opens `Resolve Line Mapping Conflict` slide-over.
- Manual override opens `Normalization Override` slide-over.
- "Bulk Apply Mapping" (with selection) opens `Bulk Mapping` slide-over.
- "Revert Override" opens confirmation slide-over.
- "Lock for Comparison" opens `Lock Normalization` slide-over (select/create comparison run).
- "Unlock" opens confirmation slide-over; flags dependent runs as Stale.

**Breadcrumb:** `RFQs > [RFQ Title] > Quote Intake > [Vendor] > Normalize`

### 9. Comparison Runs List (RFQ child)

**Purpose:** View all comparison runs for one RFQ; create new runs.

**Layout:** Workspace Layout. Displayed in the Work Surface when "Comparison Runs" is selected.

**Route:** `/rfqs/:rfqId/runs`

**How to get here:** RFQ Workspace > Active Record Menu > "Comparison Runs" child link.

**Key Elements:**
- Data table: Run ID, Date, Type (Preview/Final), Status (Generated / Stale / Locked), Scoring Model, Created By, Actions.
- "New Comparison Run" button.
- Status indicators per run.

**States:**
- Empty (no runs yet; CTA to create first comparison).
- List with mixed statuses.
- Latest run highlighted.

**Components:**
- Run status badge (Preview, Final, Stale, Locked).
- Scoring model version chip.
- Lock indicator.

**Interactions:**
- Click a run row navigates to the Comparison Matrix for that run (`/rfqs/:rfqId/runs/:runId`).
- Click "New Comparison Run" opens `Run Comparison Engine` slide-over with Preview / Final options. On success navigates to the new run's Comparison Matrix.

**Breadcrumb:** `RFQs > [RFQ Title] > Comparison Runs`

### 10. Comparison Matrix (RFQ child record)

**Purpose:** Detailed side-by-side vendor line and total comparison for a specific run.

**Layout:** Workspace Layout. Displayed in the Work Surface.

**Route:** `/rfqs/:rfqId/runs/:runId`

**How to get here:** Comparison Runs List > click a run row. Or "New Comparison Run" after a successful run.

**Key Elements:**
- Summary row with totals and deltas across vendors.
- Category-grouped line rows.
- Terms comparison section.
- Normalization view toggle (original vs normalized values).
- Run mode indicator (Preview / Final) and run metadata (timestamp, run ID, locked status).
- Scoring model selector.
- Recommendation panel (embedded or linked): recommended vendor, confidence, top factors.
- Approval gate result banner (auto-approved / pending approval / rejected).
- Readiness warning banner (for Preview runs: what must be resolved for Final).

**States:**
- Preview generated (transient, not persisted; readiness warnings inline).
- Final generated (persisted, locked, audit-trailed).
- Stale (requires recalculation).
- Locked (read-only).

**Components:**
- Best-price highlight per row.
- Delta badge (% difference from best).
- Missing-value placeholder.
- Run mode badge.
- Readiness warning banner.
- Scoring model version chip.
- Lock indicator.
- Recommendation card (vendor name, score, confidence meter).
- Approval gate banner.

**Interactions:**
- "Run/Recalculate" opens `Run Comparison Engine` slide-over (Preview or Final options).
- Click cell opens `Line Detail Note` slide-over with extraction evidence, original vs normalized.
- "Select Scoring Model" opens dropdown to choose model version. Changing model flags matrix as Stale.
- "Lock Matrix" (after Final run) prevents edits until unlocked.
- "Unlock Matrix" opens confirmation slide-over; flags run as Stale.
- "View Full Recommendation" navigates to or opens a `Recommendation & Explainability` slide-over (lg) with full MCDA breakdown.
- "Override Recommendation" opens `Override Recommendation` slide-over (mandatory reason, always triggers approval gate).
- Click "View in Approval Queue" (when approval is pending) deep-links to the relevant approval.

**Breadcrumb:** `RFQs > [RFQ Title] > Comparison Runs > Run #[ID]`

### 11. Approval Queue

**Purpose:** Prioritize and process all gated decisions across all RFQs.

**Layout:** Default Layout (this is a global cross-RFQ aggregation view, not RFQ-specific).

**Route:** `/approvals` -- there is no main nav entry for Approvals (Approvals are child records, not prime records). Users reach this page via:
- Dashboard KPI "Pending Approvals" card.
- Comparison Matrix approval gate banner "View in Approval Queue".
- Notification deep-link.

Alternatively, users can access RFQ-specific approvals from the RFQ Workspace > Active Record Menu > "Approvals" child link (`/rfqs/:rfqId/approvals`), which shows approvals filtered to that RFQ in Workspace Layout.

**Key Elements:**
- Pending approvals data table (filterable by type: comparison approval, risk escalation, policy exception, recommendation override).
- Columns: Checkbox, RFQ (linked), Type, Summary, Priority, SLA Timer, Assignee, Actions.
- Priority and SLA indicators.
- Assignee/delegation controls.
- Bulk action toolbar.

**States:**
- Empty queue.
- Active queue.
- SLA-breach risk (items highlighted with urgent styling).
- Bulk selection active.

**Components:**
- SLA timer badge (green / amber / red).
- Priority marker.
- Assignment control.
- Row selection checkboxes.
- Bulk action toolbar: Bulk Approve, Bulk Reject, Bulk Reassign.
- Snooze control.

**Interactions:**
- Click an approval row navigates to Approval Detail. If accessed from a global context, navigates to `/rfqs/:rfqId/approvals/:id` (enters Workspace Layout for that RFQ). If already in an RFQ workspace, stays in workspace.
- "Reassign" opens `Reassign Approval` slide-over filtered by delegation rules.
- "Bulk Approve" opens `Confirm Bulk Approve` slide-over with count and mandatory shared reason.
- "Bulk Reject" follows same pattern.
- "Snooze" opens `Snooze Approval` slide-over with duration picker.
- "Request More Evidence" opens `Request Evidence` slide-over; item status changes to "Awaiting Evidence", SLA pauses.

### 12. Approval Detail (RFQ child record)

**Purpose:** Execute an approval decision with full context and auditability.

**Layout:** Workspace Layout (the RFQ is the active record).

**Route:** `/rfqs/:rfqId/approvals/:id`

**How to get here:** Approval Queue > click item. Or RFQ Workspace > Approvals list > click item.

**Key Elements:**
- Summary of recommended outcome (linked to the Comparison Matrix run).
- Evidence/doc links (linked to Evidence Vault).
- Decision reason input (mandatory for all outcomes).
- Prior approval history (chronological list of all decisions on this item).
- Comparison run summary (embedded excerpt: key vendor scores and recommendation).
- Approval gate reasons panel (why this run requires human review).

**States:**
- Pending.
- Approved.
- Rejected.
- Returned for revision.
- Awaiting evidence (SLA paused).

**Components:**
- Decision buttons: Approve (green), Reject (red), Return for Revision (amber).
- Mandatory reason field.
- Evidence panel (tabbed: Documents, Screening Results, Audit Trail, Comparison Summary).
- Return-for-revision controls.
- Prior decision timeline.

**Interactions:**
- "Approve" opens `Confirm Decision` slide-over with mandatory reason and optional evidence attachment. On confirm: decision trail entry created, status updates, system routes to next step (Award or Negotiation depending on RFQ workflow).
- "Reject" opens `Confirm Decision` slide-over. On confirm: item closed, requester notified.
- "Return for Revision" opens `Return for Revision` slide-over with mandatory reason and revision instructions. SLA resets, task created for requester.
- "Request More Evidence" opens `Request Evidence` slide-over.

**Breadcrumb:** `RFQs > [RFQ Title] > Approvals > [Approval ID]`

### 13. Award Decision (RFQ primary tab)

**Purpose:** Finalize the winner or configure a split award with governance checks.

**Layout:** Workspace Layout. Displayed in the Work Surface when the "Award" primary tab is selected, or navigated to after an approval is approved.

**Route:** `/rfqs/:rfqId/award`

**How to get here:** RFQ Workspace > Active Record Menu > "Award" primary tab. Or after approving an approval item that routes to award.

**Key Elements:**
- Winner recommendation (from the latest approved comparison run).
- Savings impact section.
- Split allocation inputs.
- Decision sign-off area.
- Debrief controls (per non-winning vendor).
- Protest/challenge panel (for public-sector-like governance).
- PO/Contract handoff section (destination selector, payload preview, send controls).

**States:**
- Candidate selected (comparison approved, award not yet finalized).
- Awaiting sign-off.
- Finalized (award confirmed).
- Protest period active (standstill countdown).
- Debrief sent.
- Handoff ready / sent / failed.

**Components:**
- Allocation control (percentage sliders or line-item assignment per vendor).
- Savings badge.
- Sign-off status indicator.
- Protest timer badge.
- Debrief status indicators per vendor.
- Handoff status timeline.
- Payload viewer (for PO/contract handoff).

**Interactions:**
- "Finalize Award" opens `Award Confirmation` slide-over. If standstill is configured, shows standstill duration; award enters protest period.
- "Split Award" opens `Configure Split Award` slide-over (allocation controls, must total 100%).
- "Send Debrief" (per non-winning vendor) opens `Vendor Debrief` slide-over.
- "Record Protest" (during standstill) opens `Record Protest` slide-over. Award paused, escalation created.
- "Validate Payload" runs schema check inline; "Send to ERP" opens `Create PO/Contract Handoff` slide-over.
- "Retry" (on failed handoff) opens `Retry Handoff` slide-over.

**Breadcrumb:** `RFQs > [RFQ Title] > Award`

---

## Additional Screens (not in top 10, retained from v1 with layout annotations)

The following screens retain their v1 specifications but are updated to specify which layout they use and how they connect to the workspace model.

### Negotiation Workspace
- **Layout:** Workspace Layout (RFQ is active record).
- **Route:** `/rfqs/:rfqId/negotiations/:id`
- **How to get here:** RFQ Workspace > Negotiations list > click a negotiation.
- Retains all v1 elements: round timeline, counter-offer form, BAFO controls.
- Negotiation Rounds are child records of a Negotiation; listed within the negotiation detail (no separate tab needed since rounds are a single child type displayed as a timeline).

### Risk & Compliance Review
- **Layout:** Workspace Layout (RFQ is active record).
- **Route:** `/rfqs/:rfqId/risk`
- **How to get here:** RFQ Workspace > Active Record Menu > "Risk & Compliance" child link.
- Retains all v1 elements: risk panels, sanctions screening, due diligence checklists.

### Decision Trail / Audit Ledger
- **Layout:** Workspace Layout when scoped to an RFQ (`/rfqs/:rfqId` context). Can also be a standalone Default Layout view for global audit.
- Retains v1 elements: event timeline, hash verification, export.

### Documents & Evidence Vault
- **Layout:** Default Layout when accessed from main nav ("Document"). Workspace Layout when accessed as RFQ child ("Documents" in Active Record Menu, filtered to that RFQ).
- Retains v1 elements: search, tag filters, evidence bundles.

### Vendor Profile & Performance
- **Layout:** Slide-over (lg or xl) from any vendor link in any screen.
- Retains v1 elements: scorecard, risk trend, history.

### PO/Contract Handoff
- **Layout:** Workspace Layout (integrated into the Award tab).
- Not a separate screen; handoff controls are embedded in the Award Decision screen.

### Scoring Model Builder
- **Layout:** Default Layout (accessed from Settings > Scoring Policies, or from Comparison Matrix "Select Scoring Model").
- Retains v1 elements.

### Reports & Analytics
- **Layout:** Default Layout.
- **Route:** `/reporting`
- Retains v1 elements.

### Settings screens (Users & Roles, Scoring Policies, Templates, Integrations, Feature Flags)
- **Layout:** Default Layout.
- **Route:** `/settings/*`
- Each retains v1 elements with appropriate sub-navigation within the settings content area.

### Notification Center
- **Layout:** Slide-over from the notification bell in the Top Bar, or a dedicated view accessible from User Menu > Notifications.
- Retains v1 elements.

---

## Screen Progression and Decision Points

### RFQ Lifecycle Flow (updated for v2 navigation)

| Step | User Action | Screen / Layout | Outcome |
|------|------------|-----------------|---------|
| 1 | Click "New RFQ" | Dashboard or Top Bar (Default) | Navigate to Create RFQ (`/rfqs/create`). |
| 2 | Fill form, click Submit | Create RFQ (Default) | Navigate to RFQ Workspace (`/rfqs/:id`), Workspace Layout activates. |
| 3 | Click "Invite Vendors" | RFQ Workspace > Vendors tab | `Send Invitations` slide-over. |
| 4 | Click "Quote Intake" in Active Record Menu | RFQ Workspace | Work Surface shows Quote Intake list. |
| 5 | Click "Upload Quote" | Quote Intake List (Workspace) | `Upload & Parse` slide-over. |
| 6 | Click a submission row | Quote Intake List (Workspace) | Work Surface shows Quote Intake Detail. |
| 7 | Click "Accept & Normalize" | Quote Intake Detail (Workspace) | Work Surface shows Normalization Workspace. |
| 8 | Complete mappings, click "Lock for Comparison" | Normalization (Workspace) | Normalization locked. |
| 9 | Click "Comparison Runs" in Active Record Menu | RFQ Workspace | Work Surface shows Comparison Runs list. |
| 10 | Click "New Comparison Run" | Comparison Runs List (Workspace) | `Run Comparison Engine` slide-over > navigate to Matrix. |
| 11 | Review matrix; run Final | Comparison Matrix (Workspace) | Matrix persisted, approval gate evaluated. |
| 12a | Auto-approved | Comparison Matrix (Workspace) | Banner: "Proceed to Award." Click "Award" in Active Record Menu. |
| 12b | Requires approval | Comparison Matrix (Workspace) | Banner: "Pending approval." User or approver navigates to Approvals. |
| 13 | Click "Approvals" in Active Record Menu | RFQ Workspace | Work Surface shows Approvals list. |
| 14 | Click approval item | Approvals list (Workspace) | Work Surface shows Approval Detail. |
| 15 | Approve | Approval Detail (Workspace) | Routes to Award tab. |
| 16 | Finalize award | Award tab (Workspace) | Award confirmed; handoff controls appear. |

### Approval Flow

| Step | User Action | Screen / Layout | Outcome |
|------|------------|-----------------|---------|
| 1 | Click "Pending Approvals" KPI | Dashboard (Default) | Navigates into the relevant RFQ workspace, Approvals child. |
| 2 | Click approval item | RFQ Workspace > Approvals (Workspace) | Work Surface shows Approval Detail. |
| 3a | Approve | Approval Detail (Workspace) | `Confirm Decision` slide-over. Routes to Award or Negotiation. |
| 3b | Reject | Approval Detail (Workspace) | `Confirm Decision` slide-over. Item closed, requester notified. |
| 3c | Return for Revision | Approval Detail (Workspace) | `Return for Revision` slide-over. Task created. |

### Negotiation and Award Flow

| Step | User Action | Screen / Layout | Outcome |
|------|------------|-----------------|---------|
| 1 | Click "Negotiations" in Active Record Menu | RFQ Workspace | Work Surface shows Negotiations list. |
| 2 | Click "Start Negotiation" | Negotiations List (Workspace) | `Launch Negotiation` slide-over. New negotiation created. |
| 3 | Click negotiation row | Negotiations List (Workspace) | Work Surface shows Negotiation detail (timeline, counter-offers). |
| 4 | Close negotiation | Negotiation Detail (Workspace) | Best offer surfaced. Navigate to Award tab. |
| 5 | Click "Award" in Active Record Menu | RFQ Workspace | Work Surface shows Award Decision. |
| 6 | Finalize, send debrief, handoff | Award (Workspace) | Award confirmed. PO/Contract sent. |

---

## Component Contracts (UI-level)

### MainNav
- Accordion sidebar with expand/collapse behavior.
- Props: `items` (array of nav items with optional children), `activeItem` (current route), `onNavigate`.
- Only one expandable group open at a time.

### TopBar
- Fixed top bar across both layouts.
- Props: `onSearch`, `onNewRfq`, `onAiInsights`, `notificationCount`, `user`.

### ActiveRecordMenu
- Left panel in Workspace Layout (~25% width).
- Props: `rfq` (snippet data), `primaryTabs`, `childRecordLinks` (with counts and indicators), `activePath`.
- Zone 1: Record snippet (collapsible).
- Zone 2: Navigation links.

### WorkspaceBreadcrumbs
- Breadcrumb bar at top of Work Surface.
- Props: `segments` (array of `{label, path}`).
- "RFQs" segment always exits Workspace Layout back to Default Layout.

### RecordHeader
- Header component for child record detail views within the Work Surface.
- Props: `title`, `status`, `actions` (array of action buttons), `metadata`.

### DataTable
- Reusable data table with sorting, filtering, pagination, row selection, expandable rows.
- Used in RFQ List, Quote Intake List, Comparison Runs List, Approvals, etc.

### SlideOver
- Right-edge slide-over modal.
- Props: `open`, `onClose`, `title`, `description`, `width` (sm/md/lg/xl), `footer`.
- Supports stacking (max depth 2).

---

## Build Guide

**Suggested Stack:** React + TypeScript + Tailwind CSS + TanStack Table + Zustand/Redux Toolkit.

**Build Order:**
1. Build the layout shell: MainNav (accordion), TopBar, Default Layout, Workspace Layout (with ActiveRecordMenu), SlideOver system.
2. Build RFQ List (Default Layout) and RFQ Workspace shell (Workspace Layout with ActiveRecordMenu).
3. Build RFQ primary tabs: Overview, Details, Line Items, Vendors, Award.
4. Build child record lists: Quote Intake List, Comparison Runs List, Approvals List, Negotiations List.
5. Build child record details: Quote Intake Detail, Normalization Workspace, Comparison Matrix, Approval Detail.
6. Build global screens: Dashboard, Document/Evidence Vault, Reporting, Settings.
7. Add complete slide-over coverage and keyboard accessibility.

**Engineering Notes:**
- Enforce route and component-level access checks.
- Workspace Layout should persist the ActiveRecordMenu state (collapsed snippet, active child) in URL params or route state.
- Use virtualization in matrix views for large datasets.
- Keep audit timestamps and decision lineage explicit in UI.
- The ActiveRecordMenu's child record count badges should be real-time (via polling or WebSocket).

---

## Nexus Capability Map

(Retained from v1 -- the backend capability mapping is unchanged.)

| User Capability | Nexus Layer | Package / Orchestrator | Contract / Interface |
|-----------------|-------------|------------------------|---------------------|
| User authentication (email/password, session, token) | L1 | Identity | `UserManagerInterface`, `AuthenticationManagerInterface` |
| Multi-factor authentication (MFA) | L1 | Identity | `MfaEnrollmentInterface` |
| Single Sign-On (SSO via SAML/OAuth2/OIDC) | L1 | SSO | `SsoProviderInterface`, `SamlHandlerInterface` |
| Role-based and attribute-based access control | L1 | Identity | `PermissionCheckerInterface`, RBAC + ABAC |
| Tenant context and isolation | L1 | Tenant | `TenantContextInterface`, `TenantRepositoryInterface` |
| RFQ/requisition CRUD and lifecycle | L1 | Procurement | `ProcurementManagerInterface` |
| Requisition state transitions | L1 | Procurement | `RequisitionInterface`, `RequisitionRepositoryInterface` |
| Vendor quote creation and management | L1 | Procurement | `ProcurementManagerInterface` |
| Vendor quote locking for comparison runs | L1 | Procurement | `VendorQuoteManager`, `VendorQuoteRepositoryInterface` |
| Purchase order creation from award | L1 | Procurement | `ProcurementManagerInterface` |
| Quote document upload and storage | L1 | Document, Storage | `OrchestratorDocumentRepositoryInterface` |
| Quote ingestion (upload, validate, dispatch) | L2 | QuotationIntelligence | `QuoteIngestionServiceInterface` |
| Full intelligence pipeline | L2 | QuotationIntelligence | `QuotationIntelligenceCoordinatorInterface::processQuote` |
| Semantic mapping to taxonomy | L2 | QuotationIntelligence | `SemanticMapperInterface` |
| UoM normalization | L2 | QuotationIntelligence | `QuoteNormalizationServiceInterface` |
| Currency normalization | L2 | QuotationIntelligence | `QuoteNormalizationServiceInterface` |
| Commercial terms extraction | L2 | QuotationIntelligence | `CommercialTermsExtractorInterface` |
| Risk assessment | L2 | QuotationIntelligence | `RiskAssessmentServiceInterface` |
| Comparison matrix building | L2 | QuotationIntelligence | `QuoteComparisonMatrixServiceInterface` |
| Comparison runs (preview and final) | L2 | QuotationIntelligence | `BatchQuoteComparisonCoordinatorInterface` |
| Weighted vendor scoring (MCDA) | L2 | QuotationIntelligence | `VendorScoringServiceInterface` |
| Approval gate evaluation | L2 | QuotationIntelligence | `ApprovalGateServiceInterface` |
| Decision trail writing | L2 | QuotationIntelligence | `DecisionTrailWriterInterface` |
| Sanctions screening | L1 | Sanctions | `SanctionsScreenerInterface` |
| AML / adverse risk assessment | L1 | AmlCompliance | `AmlRiskAssessorInterface` |
| Audit trail | L1 | AuditLogger | `AuditLogManagerInterface` |
| Notifications | L1 | Notifier | `NotificationManagerInterface` |
| Reporting engine | L1 | Reporting | `ReportManager`, `ReportGeneratorInterface` |
| Feature flags | L1 | FeatureFlags | `FeatureFlagManagerInterface` |
| Sequence generation | L1 | Sequencing | `SequenceManagerInterface` |
| Currency management | L1 | Currency | `CurrencyManagerInterface` |
| UoM management | L1 | Uom | `UomManagerInterface` |
| File/document storage | L1 | Storage | `StorageInterface` |
| Cryptographic operations | L1 | Crypto | `HasherInterface`, `EncryptionServiceInterface` |
| Event streaming | L1 | EventStream | `EventStoreInterface`, `EventPublisherInterface` |
| Settings management | L1 | Setting | `SettingManagerInterface` |
| Data exchange (import/export) | L2 | DataExchangeOperations | Import/Export pipeline |
| Integration gateway | L2 | ConnectivityOperations | Resilient integration gateway |
| Procurement operations | L2 | ProcurementOperations | Coordinates Procurement, Inventory, Payable, Tax, Currency |

---

## Persona-Based Entry Points (updated for v2)

### Requester
1. Dashboard > "New RFQ" > Create RFQ form.
2. After submit: lands in RFQ Workspace.
3. Invite vendors (Vendors tab), upload quotes (Quote Intake child).
4. Triage and normalize (Quote Intake Detail > Normalization).
5. Run comparison (Comparison Runs child > Matrix).
6. Notified when approval/award completes.

### Buyer / Category Manager
1. Dashboard > RFQ List > click an RFQ > Workspace.
2. Review Comparison Matrix (Comparison Runs child).
3. Scenario Simulator (from Matrix toolbar).
4. Risk & Compliance (child link).
5. Negotiations (child link).
6. Award Decision (primary tab).

### Approver
1. Dashboard > "Pending Approvals" KPI > enters relevant RFQ Workspace > Approvals.
2. Approval Detail > decide (approve / reject / return).
3. If approved: Award tab activates.

### Procurement Manager
1. Dashboard > Reporting (main nav).
2. RFQ List > filter by status > dive into individual RFQs as needed.
3. Decision Trail (from RFQ workspace or standalone).

### Admin
1. Settings > Users & Roles, Scoring Policies, Templates, Integrations, Feature Flags.
2. All settings screens use Default Layout.

### Auditor
1. Document (main nav) > Evidence Vault.
2. Enter specific RFQ > Decision Trail (child link).
3. Export trails and evidence bundles.
