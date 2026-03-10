# Atomy-Q: Agentic Quote Comparison SaaS

## Product Overview

**The Pitch:** A procurement intelligence workspace that ingests vendor quotes, biddings, tender submissions, normalizes line-items and multi segments tender submissions, compares vendors side-by-side, applies policy-aware scoring, and routes high-risk outcomes through governed approvals with full decision traceability.

**For:** Requesters, Buyers, Approvers, Procurement Managers, Admins, Auditors, and Executives in medium-to-large organizations.

**Device:** Desktop-first (optimized for 1440px+), responsive down to large size tablet.

**Design Direction:** "Operational Intelligence Console." Moderate to high density (especially on data rich pages), high-signal interface with clear hierarchy, fast keyboard workflows, and explicit compliance indicators. Prioritize familiar user experience like positioning of save button are same throughout, workflows or multi-step processes are shown as summary timeline so the user aware which steps they are on. As for colors, use homogenous color scheme except for buttons/links/clickable trigger where user attention is demanded, contracting color can be use, but still mentain a good and pleasent color scheme. NO DARK MODE. For Modal design, all modal are slide-over from right and overlay the current screen fropm the right side with smart filling of % to cover the current screen based on Modal contents (min 25%-30% to retain design effectiveness)

**Inspired by:** Bloomberg Terminal (modernize), Linear (density), Stripe Dashboard (table precision), Keeyns (use of whitespace and data hierarchical structuring with tabs within tab)

**Tenant Context:** Each user is tied to one tenant. No tenant switching UI is provided.

## Global Layout Shell

### Sidebar (main navigation only)

The main navigation exposes only **landing surfaces**. Screens that are record-linked or part of a flow (e.g. Comparison Matrix, Normalization, Risk & Compliance) are **not** in the sidebar; they are reached from parent screens (see Navigation hierarchy below).

- **Primary:** Dashboard, RFQs (list), Quote Intake, Approvals (queue), Reports.
- **Collaboration:** Notifications, Tasks, Mentions.
- **Governance:** Decision Trail, Evidence Vault. *(Risk & Compliance is reached from RFQ Detail, not from main nav.)*
- **Administration:** Users & Access, Scoring Policies, Templates, Integrations, Feature Flags.
- **Account:** Profile, Preferences, Help/Docs.

### Navigation hierarchy (main nav vs contextual)

**Main nav (sidebar):** Dashboard, RFQs, Quote Intake, Approvals, Reports, plus Collaboration, Governance, Administration, Account as above.

**Contextual / record-linked (no sidebar entry):**

| Screen | Reached from |
|--------|----------------|
| **RFQ Detail** | RFQ List: click row. |
| **Create RFQ** | Dashboard or RFQ List: "New RFQ" / "Create RFQ". |
| **Vendor Invitation Management** | RFQ Detail: Vendors tab. |
| **RFQ Comparison Runs** | RFQ Detail: "Comparison runs" tab (list of runs for this RFQ). |
| **Comparison Matrix** | RFQ Detail → Comparison runs tab → click a run. |
| **Scenario Simulator** | Comparison Matrix: toolbar or "Scenarios" entry. |
| **Recommendation & Explainability** | Comparison Matrix (after a run) or from a run row: "View recommendation". |
| **Risk & Compliance Review** | RFQ Detail: "Risk & Compliance" tab or link. |
| **Quote Normalization Workspace** | Quote Intake: "Accept & Normalize"; or from a Quote view: "View Normalization". |
| **Approval Detail** | Approval Queue: click item. |
| **Negotiation Workspace** | Approval Detail: after Approve when workflow routes to negotiate. |
| **Award Decision** | Approval Detail or Negotiation: next step in workflow. |
| **PO/Contract Handoff** | Award Decision: after finalize. |
| **Scoring Model Builder** | Administration; or from Comparison Matrix "Select scoring model". |
| **Vendor Profile & Performance** | Any vendor link (RFQ, Comparison, Risk, etc.). |

Breadcrumbs on contextual screens must reflect the hierarchy (e.g. RFQs > [RFQ name] > Comparison runs > [Run] > Matrix). Full design: `docs/project/plans/2025-03-09-atomy-q-navigation-hierarchy-design.md`.

### Header
- Global search input with `/` shortcut.
- Breadcrumb trail.
- Quick action buttons: New RFQ, Run Comparison, Request Approval.
- Notification bell with unread count.
- AI Agent Assistant trigger button.
- User menu (avatar dropdown): Profile, Preferences, Sign Out.

### Footer
- Build/version text.
- Environment tag badge (Production/Staging).
- System status link.
- API and docs links.
- Legal links: Privacy, Terms, Security.

## Screen Specifications

### 1. Sign In

**Purpose:** Authenticate users and enforce access policy before entering the application.

**Layout:** Centered authentication card with optional split-screen brand panel.

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
- Session expired (re-authentication required).

**Components:**
- Auth form.
- Inline error message.
- Security notice text.
- "Remember this device" checkbox (suppresses MFA for trusted devices per policy).
- Session expiry banner (displayed when redirected from an expired session).

**Interactions:**
- Click Forgot Password opens `Reset Password` modal.
- Successful credentials with policy requirement opens `Verify MFA` modal.
- Session timeout triggers redirect to Sign In with `?session_expired=1` query parameter; a banner displays "Your session has expired. Please sign in again." and the user is returned to their previous route after re-authentication.
- Checking "Remember this device" stores a device token; subsequent logins from the same browser skip MFA for the duration configured in Admin Settings (default 30 days).

### 2. Home Dashboard

**Purpose:** Provide role-aware overview of workload, risk, and priority actions.

**Layout:** Smart multi-layout of Dashboard elements prioritising Scorecards/KPI/Aggregated summaries clickable visuals, task/feed area, and right rail for alerts.

**Key Elements:**
- KPI cards (active RFQs, pending approvals, savings trend, alerts).
- My Tasks list or List of recently open records.
- Risk/SLA alert panel.
- Recent comparison runs.

**States:**
- Data loading.
- Empty (no active work).
- Alert-heavy (multiple urgent items).

**Components:**
- KPI card.
- Status badge.
- Priority tag.

**Interactions:**
- Click New RFQ routes to Create RFQ.
- Click alert item deep-links to approval or risk detail.

### 3. RFQ List

**Purpose:** Manage and search the RFQ pipeline.

**Layout:** Filter rail on top, compact data table below with expandable rows.

**Key Elements:**
- Search bar.
- Status/owner/category filters.
- Compact RFQ data table with sortable columns: Checkbox, Expand chevron, ID, RFQ (title with owner avatar and name as subtitle), Status, Deadline, Est. Value, Actions (overflow menu). No separate Owner column — owner is displayed as a subtitle under the RFQ title.
- Expandable rows: clicking the expand chevron reveals a detail area with Category, Deadline, Vendors count, Quotes count, Est. Value, and Savings. This replaces the former hover tooltip.
- Bulk action toolbar (appears when one or more rows are selected via checkbox).

**States:**
- Empty list.
- Filtered no results.
- High-volume paginated list.
- Bulk selection active (one or more rows checked).
- Row expanded (one row at a time shows additional detail fields).

**Components:**
- Filter chips.
- Sortable table headers.
- Pagination controls.
- Row selection checkboxes (header checkbox for select-all on current page).
- Expand/collapse chevron per row.
- Expandable row content (grid of Category, Deadline, Vendors, Quotes, Est. Value, Savings with icons).
- Bulk action toolbar: Close Selected, Archive Selected, Assign Owner, Export Selected.

**Interactions:**
- Click row opens RFQ Detail.
- Click expand chevron toggles the expandable detail area for that row (one at a time).
- Click Create RFQ routes to Create RFQ.
- Select rows via checkbox, then click bulk action: each bulk action opens a `Confirm Bulk Action` modal showing count and action summary; confirm executes; cancel returns to list with selection preserved.
- Right-click row (or overflow menu "...") shows context menu: Open, Duplicate, Archive, Export PDF. "Duplicate" opens `Duplicate RFQ` modal (pre-fills Create RFQ with copied data, user edits and saves as new draft). "Archive" opens `Confirm Archive` modal.

### 4. Create RFQ

**Purpose:** Create sourcing requests with complete commercial and technical context.

**Layout:** Multi-step form with sticky action bar.

**Key Elements:**
- RFQ metadata form.
- Line item editor.
- Terms/deadline section.
- Attachment uploader.

**States:**
- Draft.
- Validation error.
- Saved.

**Components:**
- Stepper.
- Form fields.
- File upload dropzone.

**Interactions:**
- Click Create from Template opens `RFQ Template Picker` modal.
- Click Duplicate (when accessed via RFQ List context menu or RFQ Detail action) pre-fills all form steps with the source RFQ data; user can edit any field before saving as a new draft.
- Leaving with unsaved changes opens `Discard Changes?` modal.
- Click Save Draft persists current form state without validation; the RFQ stays in Draft status.
- Click Submit/Publish validates all required fields across steps; on success navigates to RFQ Detail for the new RFQ; on validation error highlights the first failing step in the stepper and scrolls to the error.

### 5. RFQ Detail

**Purpose:** Operate one RFQ lifecycle from creation through quote collection.

**Layout:** Header summary, tabbed content area, right-side activity timeline.

**Key Elements:**
- RFQ summary header with status badge and lifecycle action buttons.
- Vendor participation tab.
- **Comparison runs tab** — list of comparison runs for this RFQ (run ID, date, status, who ran it); "New run" action; click a run row opens Comparison Matrix for that run. *(Comparison Matrix is not in main nav; it is reached only from here.)*
- Documents tab.
- Activity timeline.
- Action dropdown (top-right): Edit, Duplicate, Close for Submissions, Reopen, Archive.

**States:**
- Draft.
- Open for submission.
- Closed.
- Archived.
- Reopened (after a previous close, when policy permits).

**Components:**
- Tab navigation.
- Timeline item.
- Metadata badges.
- Lifecycle action buttons (contextual to current state).

**Interactions:**
- Click Invite Vendors opens `Send Invitations` modal.
- Click Send Reminder opens `Reminder Confirmation` modal.
- Click Close for Submissions opens `Close RFQ` modal with closing date confirmation and reason field; on confirm the RFQ status transitions to Closed and no further vendor submissions are accepted.
- Click Reopen (visible only on Closed RFQs when tenant policy allows) opens `Reopen RFQ` modal with mandatory reason and optional new deadline; on confirm the RFQ transitions back to Open for submission and a timeline event is recorded.
- Click Archive opens `Confirm Archive` modal; on confirm the RFQ is soft-deleted from active lists and moved to the archive filter in RFQ List.
- Click Duplicate opens Create RFQ pre-filled with this RFQ's data (see Screen 4).
- Documents tab links to Quote Intake filtered by this RFQ, or to Evidence Vault filtered by this RFQ's document tags.
- Comparison runs tab: click a run row navigates to Comparison Matrix for that run. Click "New run" starts a new comparison run and navigates to Comparison Matrix in create/preview mode.
- Risk & Compliance tab or link (contextual to this RFQ) navigates to Risk & Compliance Review for this RFQ; that screen is not in the main sidebar.

### 6. Vendor Invitation Management

**Purpose:** Control vendor outreach and participation tracking.

**Layout:** Vendor roster table with outreach controls.

**Key Elements:**
- Vendor status list.
- Invitation channel selector.
- Reminder scheduler.

**States:**
- Not invited.
- Invited.
- Responded.
- Declined.

**Components:**
- Vendor status badge.
- Channel selector.
- Deadline tag.

**Interactions:**
- Click Send Reminder opens `Reminder Confirmation` modal.

### 7. Quote Intake Inbox

**Purpose:** Ingest quote submissions and triage parsing/validation issues.

**Layout:** Full-width data table list with filters. Clicking a row navigates to a separate Quote Detail screen (not a split panel). The detail screen has two tabs: Overview and Parsed Line Items.

**Key Elements:**
- Submission data table (columns: File name, Vendor, RFQ, Status, Parse confidence, Uploaded at) with status/RFQ/vendor filters and search bar. Clicking a row navigates to the Quote Detail screen (`/quote-intake/:id`).
- Upload zone (drag-and-drop or click-to-browse; supports multiple files in a single upload session).
- **Tab 1 — Overview:** Quotation document preview (left pane), vendor details + parse confidence bar + validation result (right pane). Validation result shows quote-level errors/warnings as well as overall check items.
- **Tab 2 — Parsed Line Items:** Data table of parsed line items (description, qty, uom, unit price, confidence, status/mapping) with expandable rows. Expanded row shows: currency, line total, mapped-to RFQ line description, RFQ line ID. When the submission is accepted (normalization state), the expanded row shows a "Map to RFQ line" picker dropdown (populated from `rfqLineItems` filtered by the submission's RFQ). Manual override button (per line) opens a modal; overridden lines are marked with an info chip ("Override"). Revert button is shown only for overridden lines.
- Vendor/RFQ assignment controls (when document is not auto-linked).

**States:**
- Processing (document being parsed).
- Parsed with warnings.
- Accepted.
- Rejected.
- Error (ingestion/processing failed — displays error banner with failure details).
- Pending assignment (document uploaded but not yet linked to an RFQ/vendor).

**Components:**
- Status badge (processing, parsed, accepted, rejected, error, pending assignment).
- Confidence bar/badge.
- Validation error callout (quote-level and line-level).
- Upload progress indicator.
- Assignment dropdown (RFQ selector + Vendor selector) for unlinked documents.
- Expandable table rows (Tab 2) with chevron toggle.
- Manual override modal (pick RFQ line, optional reason field).
- Info chip ("Override") on overridden lines.
- Revert button (only visible on overridden lines; clears override and restores AI-suggested mapping).

**Interactions:**
- Upload action opens `Upload & Parse Quote` modal; the modal allows selecting one or more files, optionally pre-selecting the target RFQ and vendor. Multiple files from the same vendor are grouped under a single submission entry with individual parse results per file.
- Low-confidence detection opens `Low Confidence Extraction` modal with options: Accept as-is, Edit extracted values and accept, Reject.
- Click Replace Document (on an existing submission) opens `Replace & Re-Parse` modal: user selects a new file; the old file is archived in Evidence Vault and the new file is parsed against the same RFQ/vendor context. Previous normalization mappings for the replaced document are flagged as stale.
- Click Re-Parse (on an existing submission) triggers a fresh extraction run on the same file, useful after AI model updates or taxonomy changes. A `Confirm Re-Parse` modal warns that existing accepted extraction data will be overwritten.
- Click Assign (on unlinked documents) opens `Assign Document` modal with RFQ and Vendor selectors; on confirm the document enters the standard parse pipeline for that RFQ context.
- Accept action moves the submission to Accepted status and optionally navigates to Normalization Workspace for that quote (via "Accept & Normalize" button variant).
- **Manual override** (per line, Tab 2): opens override modal. User picks an RFQ line item and optionally enters a reason. On confirm, the line is marked overridden and shows an info chip. The override is persisted via PUT `/normalization/source-lines/:id/override`.
- **Revert** (per overridden line, Tab 2): clears the manual override and restores the AI-suggested mapping. Calls DELETE `/normalization/source-lines/:id/override`.
- **Expand row** (Tab 2): toggle chevron to see line details (currency, total, mapped RFQ line) and, for accepted submissions, the RFQ line mapping picker dropdown.

### 8. Quote Normalization Workspace

**Purpose:** Resolve mapping to make vendor quotes directly comparable.

**Layout:** Split workspace with source lines and normalized target mapping grid.

**Key Elements:**
- Source line list.
- Mapping editor.
- UOM/currency conversion controls.
- Conflict queue.
- Bulk action toolbar (select multiple source lines).
- Lock status indicator (locked/unlocked for comparison run).

**States:**
- Unmapped.
- Partially mapped.
- Fully normalized.
- Locked for comparison run (read-only; normalization cannot be edited while a comparison run holds a lock).
- Stale (normalization completed but source document was replaced or re-parsed; needs review).

**Components:**
- Mapping dropdown.
- Conversion badge.
- Conflict indicator.
- Bulk selection checkboxes.
- Lock badge (shows which comparison run holds the lock, if any).

**Interactions:**
- Mapping conflict opens `Resolve Line Mapping Conflict` modal.
- Manual override opens `Normalization Override` modal.
- Click Bulk Apply Mapping (with multiple lines selected) opens `Bulk Mapping` modal: user selects a taxonomy code and UoM target; the mapping is applied to all selected lines. A confirmation step shows how many lines will be affected and any that cannot be bulk-mapped due to incompatible units.
- Click Revert Override (on a manually overridden line) opens `Revert Override` modal confirming the line will return to its AI-suggested normalization values; on confirm the override is removed and the original mapping/conversion is restored.
- Click Lock for Comparison (toolbar action) opens `Lock Normalization` modal: user selects or creates a comparison run context; on confirm all normalization data for this RFQ is locked (read-only) and the lock is recorded with the run ID, user, and timestamp. Only the comparison run owner or an admin can unlock.
- Click Unlock (visible when locked) opens `Unlock Normalization` modal with confirmation; unlocking flags any dependent comparison run as Stale.

### 9. Quote Comparison Matrix

**Purpose:** Provide detailed side-by-side line and total comparison.

**Layout:** Sticky header vendor columns and sticky first line-item column.

**Key Elements:**
- Summary row with totals and deltas.
- Category-grouped line rows.
- Terms comparison section.
- Normalization view toggle (show original vs normalized values).
- Run mode indicator (Preview or Final) and run metadata (timestamp, run ID, locked status).
- Scoring model selector (dropdown showing available model versions for this RFQ).

**States:**
- Not generated.
- Preview generated (transient, not persisted; readiness warnings shown inline).
- Final generated (persisted, locked, audit-trailed).
- Stale (requires recalculation due to normalization changes, new vendor submissions, or scoring model update).
- Locked (a final run is active and the matrix is read-only until unlocked).

**Components:**
- Best-price highlight.
- Delta badge.
- Missing-value placeholder.
- Run mode badge (Preview / Final).
- Readiness warning banner (displayed for preview runs showing what must be resolved before a final run: e.g., RFQ not closed, fewer than 2 vendors, incomplete normalization, low AI confidence lines).
- Scoring model version chip.
- Lock indicator.

**Interactions:**
- Click Run/Recalculate opens `Run Comparison Engine` modal with two options:
  - **Preview Run**: Executes comparison with relaxed validation (minimum 1 vendor, RFQ does not need to be closed). Results are displayed in-place but not persisted. Readiness warnings are shown so the user knows what to fix before a final run.
  - **Final Run**: Executes comparison with full validation gates (RFQ must be closed, minimum 2 vendors, all normalizations complete, AI confidence above threshold). On success: results are persisted, normalization is locked to this run, a decision trail entry is created, and the approval gate is evaluated. If the approval gate requires human approval, a banner appears with a link to the Approval Queue.
- Click cell opens detail note modal for that line.
- Click Select Scoring Model opens a dropdown/modal to choose which scoring model version applies to this RFQ/run. Changing the model flags the current matrix as Stale if a final run was already generated.
- Click Lock Matrix (after a final run) prevents further edits to the comparison data until explicitly unlocked. The lock is tied to the run ID.
- Click Unlock Matrix opens `Unlock Comparison` modal with confirmation; unlocking flags the run as Stale and records an audit event.

### 10. Scoring Model Builder

**Purpose:** Configure weighted criteria and enforce scoring governance.

**Layout:** Criteria list editor with live scoring preview panel.

**Key Elements:**
- Weight sliders/inputs.
- Criteria definitions (Price/LCC, Risk, Delivery, Sustainability, and custom dimensions).
- Constraint/policy rules.
- Version notes.
- RFQ/category assignment panel (which RFQs or categories use this model).
- Live scoring preview (shows ranked vendors when an RFQ context is selected).

**States:**
- Draft config.
- Valid config.
- Policy violation.
- Published (active version used for new comparison runs).

**Components:**
- Weight control.
- Policy warning banner.
- Version chip (shows version history with diff between versions).
- Publish/draft toggle.

**Interactions:**
- Editing weights opens `Adjust Scoring Weights` modal.
- Rule breach opens `Policy Violation Warning` modal.
- Click Save Version saves the current configuration as a new version; the version chip increments and the previous version is preserved in history.
- Click Publish Version (on a draft or saved version) promotes it to the active model used by default for new comparison runs. A `Confirm Publish` modal shows which RFQs/categories will be affected.
- Click Assign to RFQ/Category opens `Model Assignment` modal where the user can associate this model with specific RFQ types or procurement categories.
- Click View Version History opens a slide-over showing all saved versions with diff highlights; the user can restore a previous version (creates a new draft based on that version).

### 11. Scenario Simulator

**Purpose:** Evaluate alternative assumptions before final decision.

**Layout:** Scenario list panel and comparison result canvas.

**Key Elements:**
- Scenario cards.
- Assumption controls.
- Outcome comparison chart/table.

**States:**
- Single baseline.
- Multiple scenarios.
- Unsaved scenario edits.

**Components:**
- Scenario card.
- Delta visualization.
- Save indicator.

**Interactions:**
- Save action opens `Save Scenario` modal.
- Compare action opens `Scenario Diff` modal.

### 12. Recommendation & Explainability

**Purpose:** Present recommendation with confidence and rationale.

**Layout:** Primary recommendation panel with supporting explanation blocks.

**Key Elements:**
- Recommended vendor card.
- Confidence score.
- Top contributing factors.
- Trade-off narrative.
- Override action bar (for authorized users to disagree with the AI recommendation).
- Re-run controls (request a new comparison with different scoring weights).

**States:**
- High confidence.
- Medium confidence.
- Low confidence requiring review.
- Overridden (user has manually selected a different vendor with documented reason).

**Components:**
- Confidence meter.
- Factor list.
- Rationale panel.
- Override badge (displayed when the recommendation has been manually overridden; shows the overriding user and reason).

**Interactions:**
- Click Why this opens `Why This Recommendation` modal showing the full MCDA breakdown: per-dimension scores (Price/LCC, Risk, Delivery, Sustainability), weights applied, and comparative chart across all vendors.
- Click Override Recommendation opens `Override Recommendation` modal: the user selects an alternative vendor from the scored list, provides a mandatory justification reason, and optionally attaches supporting evidence. On confirm the override is recorded in the decision trail and the approval gate is re-evaluated (overrides always require approval regardless of risk score).
- Click Request Re-Run opens `Request Re-Run` modal: the user can adjust scoring weights or select a different scoring model version, then triggers a new comparison run. The current recommendation is preserved until the new run completes. On completion the recommendation panel updates with the new results.

### 13. Risk & Compliance Review

**Purpose:** Consolidate and evaluate non-price risk signals.

**Layout:** Risk panels by category with escalation rail.

**Key Elements:**
- Sanctions/policy/commercial risk cards.
- Risk severity ladder.
- Escalation requirement list.
- Vendor due diligence checklist (per vendor, expandable).
- Sanctions screening trigger and results panel.

**States:**
- No critical risk.
- Medium risk.
- High risk requiring escalation.
- Screening in progress (sanctions/AML check running).
- Due diligence incomplete (checklist items pending).

**Components:**
- Risk badge.
- Escalation flag.
- Compliance checklist.
- Sanctions screening status indicator (Not screened, Clear, Flagged, Error).
- Due diligence checklist (configurable per tenant; items such as: financial stability verified, insurance certificates collected, conflict of interest declaration, reference checks completed, adverse media review).

**Interactions:**
- Escalation trigger opens `Risk Escalation Required` modal.
- Exception request opens `Policy Exception Request` modal.
- Click Screen Vendor (per vendor card) triggers a sanctions and AML screening check. A `Sanctions Screening` modal shows the vendor name and jurisdiction, with a "Run Screening" button. On execution: the system calls the sanctions screening service and the AML risk assessor; results populate the vendor's risk card with a Sanctions status badge (Clear/Flagged) and any matched entries. Flagged results auto-trigger an escalation entry in the Approval Queue.
- Click Due Diligence Checklist (per vendor) expands an inline checklist panel. Each item can be marked as Complete, N/A, or Pending. Items can have attachments (link to Evidence Vault). When all required items are marked Complete or N/A, the vendor's due diligence status changes to "Complete" and a green badge appears on the vendor risk card.
- Click View Screening History opens a slide-over showing all past screening runs for this vendor with dates, results, and the screener used.

### 14. Approval Queue

**Purpose:** Prioritize and process all gated decisions.

**Layout:** Queue table with SLA columns and assignment actions.

**Key Elements:**
- Pending approvals list (filterable by type: comparison approval, risk escalation, policy exception, recommendation override).
- Priority and SLA indicators.
- Assignee/delegation controls.
- Bulk action toolbar (appears when multiple items are selected).
- Delegation rules summary (who can delegate to whom, visible to admins).

**States:**
- Empty queue.
- Active queue.
- SLA-breach risk.
- Bulk selection active.

**Components:**
- SLA timer badge.
- Priority marker.
- Assignment control.
- Row selection checkboxes.
- Bulk action toolbar: Bulk Approve, Bulk Reject, Bulk Reassign.
- Snooze control (per item or bulk).

**Interactions:**
- Click item opens Approval Detail.
- Reassign action opens `Reassign Approval` modal. The modal shows eligible assignees filtered by delegation rules: a user can only reassign to users who hold an equal or higher authority level for the relevant approval type. The delegation rules are configured in User & Access Management (Screen 24).
- Click Bulk Approve (with items selected) opens `Confirm Bulk Approve` modal showing the count and a summary of each item; the user must provide a single reason that applies to all selected items. On confirm, each item is individually approved with the shared reason and separate decision trail entries.
- Click Bulk Reject follows the same pattern as Bulk Approve but with a mandatory rejection reason.
- Click Snooze (per item or bulk) opens `Snooze Approval` modal with a duration picker (1 hour, 4 hours, 1 day, custom). Snoozed items are temporarily hidden from the main queue and reappear after the snooze period. A "Snoozed" filter tab shows all snoozed items.
- Click Request More Evidence (per item) opens `Request Evidence` modal: the user specifies what evidence is needed and from whom; on confirm a notification and task are created for the target user, and the approval item status changes to "Awaiting Evidence" with an SLA pause.

### 15. Approval Detail

**Purpose:** Execute approval decisions with full context and auditability.

**Layout:** Decision panel with evidence tabs and decision history.

**Key Elements:**
- Summary of recommended outcome (with link to Recommendation & Explainability screen for full context).
- Evidence/doc links (linked to Evidence Vault).
- Decision reason input (mandatory for all outcomes).
- Prior approval history (chronological list of all previous decisions on this item, including returns and re-submissions).
- Comparison run summary (embedded matrix excerpt showing the key vendor scores and recommendation).

**States:**
- Pending.
- Approved.
- Rejected.
- Returned for revision.
- Awaiting evidence (SLA paused while additional evidence is gathered).

**Components:**
- Decision buttons (Approve, Reject, Return for Revision).
- Mandatory reason field.
- Evidence panel (tabbed: Documents, Screening Results, Audit Trail, Comparison Summary).
- Return-for-revision controls.

**Interactions:**
- Approve/reject action opens `Confirm Decision` modal with mandatory reason field and optional evidence attachment. On confirm: the decision is recorded in the decision trail, the approval item status updates, and the system determines the next step:
  - If approved and no further gates: routes to Award Decision (Screen 17) or Negotiation Workspace (Screen 16) depending on the workflow configured for the RFQ.
  - If rejected: the item is closed, and a notification is sent to the requester with the rejection reason.
- Click Return for Revision opens `Return for Revision` modal with a mandatory reason and an optional "revision instructions" field. On confirm: the approval item status changes to "Returned for revision," the SLA clock resets, and a task is created for the requester linking back to the relevant screen (e.g., Normalization Workspace or Comparison Matrix) so they can address the feedback and re-submit.
- Click Request More Evidence opens `Request Evidence` modal (same as in Approval Queue).

### 16. Negotiation Workspace

**Purpose:** Manage negotiation rounds and counter-offer changes.

**Layout:** Timeline of rounds with side panel for current offer edits.

**Key Elements:**
- Round history cards.
- Current counter-offer form.
- Concession delta summary.
- Best and Final Offer (BAFO) request controls.

**States:**
- No negotiation yet.
- Active round.
- Final round complete.
- BAFO requested (awaiting vendor responses).

**Components:**
- Timeline rail.
- Delta chip.
- Round status badge.
- BAFO request indicator.

**Interactions:**
- Start round action opens `Launch Negotiation Round` modal: user defines the round scope (which vendors, which line items), the deadline, and negotiation parameters. On confirm a notification is sent to the selected vendors.
- Counter-offer action opens `Submit Counter Offer` modal: user edits price, terms, or conditions per line; a concession delta summary shows what changed vs. the previous round. On submit the round history updates and notifications are sent.
- Click Request BAFO opens `Request Best and Final Offer` modal: user selects vendors and sets a deadline. On confirm a BAFO request is sent to selected vendors and the negotiation status changes to "BAFO requested."
- Click Close Negotiation (after final round or BAFO) opens `Close Negotiation` modal confirming no further rounds will be conducted. On confirm the best offer from the final round is surfaced as the recommended basis for the Award Decision.

### 17. Award Decision

**Purpose:** Finalize winner or split award with governance checks.

**Layout:** Decision summary panel with allocation controls.

**Key Elements:**
- Winner recommendation.
- Savings impact section.
- Split allocation inputs.
- Decision sign-off area.
- Debrief controls (for notifying non-winning vendors).
- Protest/challenge panel (for public-sector-like governance).

**States:**
- Candidate selected.
- Awaiting sign-off.
- Finalized.
- Protest period active (optional, configurable standstill period before the award becomes final).
- Debrief sent.

**Components:**
- Allocation control.
- Savings badge.
- Sign-off status indicator.
- Protest timer badge (countdown for standstill period, if configured).
- Debrief status indicators per vendor.

**Interactions:**
- Finalize action opens `Award Confirmation` modal. If a standstill/protest period is configured in Admin Settings, the modal shows the standstill duration and explains that the award will become final only after the period expires without a protest. On confirm the award enters "Protest period active" state.
- Split toggle opens `Configure Split Award` modal: user allocates percentages or specific line items to multiple vendors; the total must equal 100%. Savings impact updates in real-time as allocation changes.
- Click Send Debrief (per non-winning vendor) opens `Vendor Debrief` modal: the user composes a debrief summary (can auto-generate from the comparison/scoring data), optionally redacting competitor pricing. On confirm the debrief is sent via the configured notification channel and a copy is stored in the Evidence Vault.
- Click Record Protest (during standstill period) opens `Record Protest` modal: the user logs the protesting vendor, the grounds for protest, and attaches supporting documents. The award is paused, an escalation item is created in the Approval Queue, and the decision trail records the protest event. Resolution options: Uphold Award, Re-evaluate, Cancel and Re-tender.

### 18. PO/Contract Handoff

**Purpose:** Send approved outcomes to ERP/procurement downstream systems.

**Layout:** Handoff configuration form with payload preview.

**Key Elements:**
- Destination system selector.
- Payload preview panel (structured JSON/XML view with field-level highlighting).
- Handoff status timeline.
- Payload validation results panel.

**States:**
- Ready to send.
- Validating (schema check in progress).
- Validation failed (schema errors displayed).
- Sending.
- Sent.
- Failed with retry.

**Components:**
- Integration status badge.
- Payload viewer.
- Retry action button.
- Validation result panel (shows pass/fail with field-level error details).

**Interactions:**
- Click Validate Payload (before sending) runs the payload against the destination system's schema. A `Payload Validation` panel appears inline showing field-by-field results: green checks for valid fields, red markers for schema violations with specific error messages. The Send button is disabled until validation passes.
- Send action opens `Create PO/Contract Handoff` modal (only enabled after successful validation). On confirm the handoff is initiated and status transitions through Sending to Sent or Failed.
- Retry (on failed handoff) opens `Retry Handoff` modal showing the failure reason and allowing the user to edit the payload before retrying.

### 19. Decision Trail / Audit Ledger

**Purpose:** Display immutable decision and system event chain for governance.

**Layout:** Chronological event ledger with filterable side panel.

**Key Elements:**
- Event timeline.
- Actor/action metadata.
- Hash/integrity fields.
- Export controls.
- RFQ/approval scope filter (view trail for a specific RFQ, comparison run, or approval item).

**States:**
- Full verified trail.
- Partial trail with warning.
- Export in progress.

**Components:**
- Event card.
- Integrity badge.
- Filter controls.
- Export button (PDF, CSV, JSON formats).
- Scope selector (All, specific RFQ, specific comparison run, specific approval).

**Interactions:**
- Integrity check action opens `Verify Audit Integrity` modal. The modal runs a hash-chain verification against all entries in the current scope and displays: total entries checked, pass/fail status, and the first broken link (if any) with before/after hash values.
- Click Export Trail opens `Export Decision Trail` modal: user selects the scope (current filter or full trail), the format (PDF for auditors with formatted layout, CSV for data analysis, JSON for system integration), and optional date range. On confirm the export is generated and downloaded. For large trails a background job is created and the user is notified when the export is ready.
- Click event card expands to show full event detail including: the before/after state, the actor's role and authority level, the hash chain reference, and links to related evidence in the Evidence Vault.

### 20. Vendor Profile & Performance

**Purpose:** Show vendor historical performance and risk posture.

**Layout:** Profile header with tabbed performance, compliance, and history sections.

**Key Elements:**
- Vendor summary card.
- Performance metrics.
- Compliance history.
- Prior quote outcomes.

**States:**
- Complete profile.
- Partial profile data.

**Components:**
- Scorecard widget.
- Risk trend chart.
- Profile tags.

**Interactions:**
- Click historical quote links back to related RFQ or matrix.

### 21. Documents & Evidence Vault

**Purpose:** Store and retrieve documents for sourcing and compliance evidence.

**Layout:** Searchable document list with metadata side panel.

**Key Elements:**
- Document search.
- Folder/tag filters.
- Version history.
- Retention metadata.
- Evidence bundle management panel.

**States:**
- Empty vault.
- Populated vault.
- Restricted document access.
- Bundle in progress (user is assembling an evidence bundle).

**Components:**
- File row item.
- Tag chips.
- Version badge.
- Evidence bundle card (named collection of documents tied to a specific approval, RFQ, or audit scope).
- Bundle status indicator (Draft, Finalized, Exported).

**Interactions:**
- Open file preview, download, and evidence bundle selection.
- Click Create Evidence Bundle opens `Create Evidence Bundle` modal: user names the bundle, selects the scope (RFQ, comparison run, or approval item), and optionally adds a description. The bundle is created in Draft status.
- Click Add to Bundle (on any document row) opens a dropdown of existing draft bundles; selecting one adds the document to that bundle.
- Click Finalize Bundle (on a draft bundle) locks the bundle contents, generates a manifest (list of all included documents with hashes and metadata), and changes the status to Finalized. Finalized bundles cannot be modified.
- Click Export Bundle (on a finalized bundle) generates a ZIP archive containing all documents and the manifest, suitable for sending to auditors or regulatory bodies.
- Click View Bundle opens a slide-over showing the bundle contents with links to each document preview.

### 22. Reports & Analytics

**Purpose:** Provide operational and executive reporting.

**Layout:** KPI dashboard with configurable charts and report table.

**Key Elements:**
- Savings/cycle/compliance KPIs.
- Trend charts.
- Report schedule list.
- Saved report runs list (history of previously generated reports).
- Schedule configuration panel.

**States:**
- Default dashboard.
- Filtered custom view.
- No-data interval.
- Report generating (background job in progress).

**Components:**
- KPI tiles.
- Chart components.
- Report cards.
- Schedule config card (shows next run, frequency, recipients).
- Saved run row (shows date, parameters, download link, status).

**Interactions:**
- Export action opens `Export Options` modal with format selection (PDF, Excel, CSV), date range, and optional email delivery.
- Click Schedule Report opens `Schedule Report` modal: user selects report type, frequency (daily, weekly, monthly, custom cron), delivery channel (email, in-app notification), recipients, and format. On confirm a schedule entry is created and appears in the schedule list. Scheduled reports run automatically and results appear in the Saved Report Runs list.
- Click Edit Schedule (on existing schedule) opens the same modal pre-filled for editing.
- Click Delete Schedule opens `Confirm Delete Schedule` modal.
- Click Saved Report Runs tab shows a chronological list of all previously generated reports with: date, parameters used, status (Completed, Failed), and a download link. Click a run row to preview or re-download.
- Click Run Now (on a scheduled report or from the dashboard) triggers an immediate report generation; the user is notified when complete.

### 23. Integration & API Monitor

**Purpose:** Monitor connector and API job health.

**Layout:** Health summary cards with failure log table.

**Key Elements:**
- Connector health status.
- Failed job log.
- Retry queue.

**States:**
- Healthy.
- Degraded.
- Outage.

**Components:**
- Health badge.
- Failure row.
- Retry control.

**Interactions:**
- Retry action opens `Retry Integration Job` modal.

### 24. User & Access Management

**Purpose:** Manage user lifecycle, roles, delegation, and authority limits.

**Layout:** User table with detail drawer for role and permission editing.

**Key Elements:**
- User directory.
- Role assignment controls.
- Delegation settings (who can delegate to whom, with scope constraints).
- Approval authority limits (monetary thresholds per user/role).
- Invitation management (invite new users, resend invites, revoke pending invites).

**States:**
- Active user.
- Suspended user.
- Pending invite.
- Delegation active (user is currently delegating to another user).

**Components:**
- Role chip.
- Permission toggle.
- Invite control.
- Authority limit input (monetary threshold per approval type).
- Delegation rule editor.

**Interactions:**
- Save access changes with confirmation prompts for critical role changes.
- Click Invite User opens `Invite User` modal: enter email, select role, set initial authority limits. On confirm an invitation email is sent and the user appears in the directory as "Pending invite."
- Click Suspend User opens `Suspend User` modal with mandatory reason; on confirm the user's sessions are invalidated and they cannot sign in. Active approval items assigned to the suspended user are flagged for reassignment.
- Click Configure Delegation (per user) opens `Delegation Rules` slide-over: define which users this person can delegate to, the scope (all approvals, specific approval types, specific monetary ranges), and the delegation duration (permanent or date-bounded). Delegation rules enforce that a delegate must hold an equal or higher authority level for the delegated scope.
- Click Set Authority Limits (per user) opens a slide-over to configure the maximum monetary value this user can approve per transaction type. Transactions exceeding the limit are automatically escalated to a higher authority.

### 25. Admin Settings

**Purpose:** Configure platform behavior and tenant-level governance controls.

**Layout:** Settings categories in left nav and detailed forms on right.

**Key Elements:**
- Policy thresholds.
- Taxonomy/template config.
- Feature flags.
- Default workflow settings.

**States:**
- Default config.
- Draft config.
- Published config.

**Components:**
- Settings form groups.
- Feature flag toggles.
- Publish controls.

**Interactions:**
- Destructive changes open `Confirm Deletion` modal when applicable.

### 26. Notification Center

**Purpose:** Centralize actionable alerts and reminders.

**Layout:** Grouped notification feed with filters by type and urgency.

**Key Elements:**
- Unread alerts list.
- Mention and assignment notifications.
- Deadline reminders.

**States:**
- No notifications.
- Mixed priority feed.

**Components:**
- Notification item.
- Read/unread badge.
- Bulk mark controls.

**Interactions:**
- Click notification deep-links to source screen.

### 27. RFQ Templates

**Purpose:** Create, manage, and apply reusable RFQ templates to accelerate sourcing setup and enforce organizational standards.

**Layout:** Template list on left with search/filter, template detail/editor on right.

**Key Elements:**
- Template directory (searchable, filterable by category, owner, last used).
- Template editor (mirrors Create RFQ form structure: metadata defaults, line item presets, terms/deadline defaults, attachment placeholders).
- Usage statistics (how many RFQs created from this template, last used date).
- Category/tag assignment.

**States:**
- Empty (no templates yet).
- Active template list.
- Template in edit mode.
- Template published (available for use).
- Template archived.

**Components:**
- Template card (name, category, usage count, last modified).
- Template editor form (same field structure as Create RFQ but with default/placeholder values).
- Publish/archive toggle.
- Category/tag chips.

**Interactions:**
- Click Create Template opens the template editor in a new template context; the user fills in default values for metadata, line items, terms, and deadlines. Fields can be marked as "locked" (cannot be changed when the template is applied) or "editable" (can be overridden during RFQ creation).
- Click Edit Template (on an existing template) opens the template editor pre-filled with the template's current values.
- Click Duplicate Template creates a copy of the selected template in Draft status.
- Click Archive Template opens `Confirm Archive` modal; archived templates are hidden from the template picker but preserved for audit purposes.
- Click Publish Template makes the template available in the `RFQ Template Picker` modal (Screen 4).
- Click Apply Template (from the RFQ Template Picker modal in Create RFQ) pre-fills the Create RFQ form with all template defaults; locked fields are displayed as read-only with a lock icon.

### 28. Scoring Policies

**Purpose:** Define, version, and assign scoring policies that govern how comparison runs are evaluated, ensuring organizational consistency and compliance.

**Layout:** Policy list with detail editor; assignment panel on right.

**Key Elements:**
- Policy directory (searchable, filterable by status, category, assigned RFQ types).
- Policy editor (scoring dimensions with weight ranges, mandatory dimensions, constraint rules).
- Assignment panel (which RFQ types, procurement categories, or specific RFQs use this policy).
- Version history with diff view.
- Compliance rules (e.g., "Price weight must be at least 30%," "Sustainability dimension is mandatory for contracts above threshold").

**States:**
- Draft policy.
- Published policy (active).
- Archived policy.
- Policy violation detected (a scoring model referencing this policy violates its constraints).

**Components:**
- Policy card (name, version, status, assigned categories count).
- Dimension editor (dimension name, weight range min/max, mandatory flag).
- Constraint rule editor (conditional rules with and/or logic).
- Assignment selector (multi-select for RFQ types and procurement categories).
- Version diff viewer.

**Interactions:**
- Click Create Policy opens the policy editor for a new policy. The user defines: policy name, description, scoring dimensions (each with a weight range and mandatory/optional flag), and constraint rules (e.g., "If contract value > $100K then Sustainability weight >= 15%").
- Click Edit Policy opens the editor pre-filled; changes create a new draft version.
- Click Publish Policy opens `Confirm Publish` modal showing which scoring models and RFQs will be affected. Published policies are enforced in the Scoring Model Builder (Screen 10): any model that violates the policy triggers the Policy Violation Warning modal.
- Click Assign to Categories opens `Policy Assignment` modal: user selects RFQ types and/or procurement categories. When a new RFQ is created in an assigned category, the policy's scoring model is automatically applied.
- Click View Version History opens a slide-over showing all versions with inline diffs; the user can restore a previous version (creates a new draft).
- Click Archive Policy opens `Confirm Archive` modal; archived policies are no longer enforced but preserved for audit.

### 29. Integrations Configuration

**Purpose:** Configure, test, and manage connections to external systems (ERP, email, document storage, sanctions databases) beyond just monitoring their health.

**Layout:** Integration cards grid with detail configuration slide-over.

**Key Elements:**
- Integration catalog (available connectors: ERP systems, email providers, document storage, sanctions screening services, SSO providers).
- Configured integrations list (active connections with status and last sync).
- Connection configuration form (endpoint URL, credentials, mapping rules, sync frequency).
- Test connection controls.

**States:**
- No integrations configured.
- Integration active and healthy.
- Integration active but degraded.
- Integration disabled.
- Configuration in progress (new or editing).

**Components:**
- Integration card (name, type, status badge, last sync timestamp).
- Configuration form (endpoint, authentication, field mapping).
- Test button with inline result indicator.
- Enable/disable toggle.

**Interactions:**
- Click Add Integration opens `Configure Integration` slide-over: user selects the integration type from the catalog, fills in connection details (endpoint, API key/OAuth, etc.), configures field mappings (e.g., RFQ fields to ERP fields), and sets sync frequency.
- Click Test Connection runs a connectivity and authentication check; results appear inline (success with response time, or failure with error details).
- Click Save & Enable activates the integration; it begins appearing in the Integration & API Monitor (Screen 23).
- Click Edit (on a configured integration) reopens the configuration slide-over pre-filled.
- Click Disable (on an active integration) opens `Confirm Disable` modal; disabled integrations stop syncing but preserve their configuration.
- Click Delete (on a disabled integration) opens `Confirm Delete` modal; removes the configuration entirely.
- Click View Logs links to the Integration & API Monitor (Screen 23) filtered to this specific integration.

## Build Guide

**Suggested Stack:** React + TypeScript + Tailwind CSS + TanStack Table + Zustand/Redux Toolkit.

**Build Order:**
1. Build persistent shell (sidebar/header/footer) and role-based route guards.
2. Build core workflow screens: RFQ List, Quote Intake, Normalization, Comparison Matrix.
3. Build governance screens: Recommendation, Risk Review, Approval Queue/Detail.
4. Build negotiation, award, and handoff screens.
5. Build admin, audit, and reporting screens.
6. Add complete modal coverage and keyboard accessibility.

**Engineering Notes:**
- Enforce route and component-level access checks.
- Keep approval and compliance actions strongly confirmed.
- Use virtualization in matrix views for large datasets.
- Keep audit timestamps and decision lineage explicit in UI.

## Screen Progression and Decision Points

This section documents the click-by-click screen flows, including decision branches and modal interactions, for every major workflow in Atomy-Q.

### Authentication Flow

| Step | User Action | Outcome | Next Screen |
|------|------------|---------|-------------|
| 1 | Navigate to app URL | App checks session | If valid session: Dashboard. If expired/none: Sign In. |
| 2 | Enter credentials, click Sign In | Validate credentials | If invalid: inline error, stay on Sign In. If account locked: locked state. If valid + MFA required: `Verify MFA` modal. If valid + no MFA: Dashboard. |
| 3 | Complete MFA | Verify MFA code | If valid: Dashboard (returns to original route if session expired). If invalid: retry in modal. |
| 4 | Click Forgot Password | Open modal | `Reset Password` modal. On submit: confirmation message, stay on Sign In. |

### RFQ Lifecycle Flow

| Step | User Action | Screen | Outcome |
|------|------------|--------|---------|
| 1 | Click "New RFQ" (Dashboard/RFQ List) | Dashboard or RFQ List | Navigate to Create RFQ. |
| 2a | Click "Create from Template" | Create RFQ | `RFQ Template Picker` slide-over opens. Select template, form is pre-filled. Stay on Create RFQ. |
| 2b | Fill form manually | Create RFQ | Multi-step form progresses via stepper. |
| 3 | Click "Save Draft" | Create RFQ | RFQ persisted in Draft status. Stay on Create RFQ with "Saved" indicator. |
| 4 | Click "Submit/Publish" | Create RFQ | Validation runs. If errors: first failing step highlighted. If valid: navigate to RFQ Detail for the new RFQ. |
| 5 | Click "Invite Vendors" | RFQ Detail | `Send Invitations` slide-over opens. Select vendors, channels, deadline. On send: stay on RFQ Detail, timeline updated. |
| 6 | Click "Send Reminder" | RFQ Detail | `Reminder Confirmation` slide-over. On confirm: notification sent, stay on RFQ Detail. |
| 7 | Click "Close for Submissions" | RFQ Detail | `Close RFQ` modal with date and reason. On confirm: status changes to Closed. Vendor Invitation Management shows final statuses. |
| 8 | Click "Reopen" (if policy allows) | RFQ Detail | `Reopen RFQ` modal with reason and new deadline. On confirm: status returns to Open. |
| 9 | Click "Duplicate" | RFQ Detail or RFQ List | Navigate to Create RFQ with all fields pre-filled from source. |
| 10 | Click "Archive" | RFQ Detail or RFQ List | `Confirm Archive` modal. On confirm: RFQ moved to archive. |

### Quote Intake and Normalization Flow

| Step | User Action | Screen | Outcome |
|------|------------|--------|---------|
| 1 | Click "Upload" | Quote Intake Inbox | `Upload & Parse Quote` slide-over. Select files, optionally pre-select RFQ/vendor. On upload: queue updated, parsing begins. |
| 2a | Parse succeeds with high confidence | Quote Intake Inbox | Submission enters "Parsed" state. Detail panel shows extracted lines. |
| 2b | Parse succeeds with low confidence | Quote Intake Inbox | `Low Confidence Extraction` slide-over opens automatically. Options: Accept as-is, Edit & accept, Reject. |
| 2c | Parse fails | Quote Intake Inbox | Submission enters "Rejected" state with error details. User can Re-Parse or Replace Document. |
| 3 | Click "Accept" or "Accept & Normalize" | Quote Intake Inbox | If "Accept": status changes, stay on Intake. If "Accept & Normalize": navigate to Normalization Workspace for that quote. |
| 4 | Click "Replace Document" | Quote Intake Inbox | `Replace & Re-Parse` slide-over. Old file archived, new file parsed. |
| 5 | Click "Assign" (unlinked document) | Quote Intake Inbox | `Assign Document` modal. Select RFQ and vendor. On confirm: document enters parse pipeline. |
| 6 | Map lines to taxonomy | Normalization Workspace | Source lines mapped via dropdowns. Conflicts flagged. |
| 7 | Mapping conflict | Normalization Workspace | `Resolve Line Mapping Conflict` slide-over. Choose mapping or create new. |
| 8 | Click "Bulk Apply Mapping" | Normalization Workspace | `Bulk Mapping` modal. Select taxonomy code and UoM. Applied to all selected lines. |
| 9 | Click "Lock for Comparison" | Normalization Workspace | `Lock Normalization` modal. Select comparison run. On confirm: normalization data locked (read-only). |

### Comparison and Scoring Flow

| Step | User Action | Screen | Decision Point | Outcome |
|------|------------|--------|----------------|---------|
| 1 | Click "Run Comparison" | Comparison Matrix | **Preview or Final?** | Modal presents both options. |
| 2a | Select "Preview Run" | Comparison Matrix | | Relaxed validation. Results in-place, not persisted. Readiness warnings shown. |
| 2b | Select "Final Run" | Comparison Matrix | **Readiness check passes?** | Full validation: RFQ closed, 2+ vendors, normalization complete, confidence OK. |
| 2b-i | Readiness check fails | Comparison Matrix | | Error banner shows blocking issues. User must resolve before retrying. |
| 2b-ii | Readiness check passes | Comparison Matrix | **Approval gate triggered?** | Matrix persisted, decision trail entry created, scoring applied. |
| 2b-ii-A | Approval gate: auto-approved | Comparison Matrix | | Banner: "Auto-approved. Proceed to Award or Negotiation." |
| 2b-ii-B | Approval gate: requires approval | Comparison Matrix | | Banner: "Pending approval. Routed to Approval Queue." Link to Approval Queue. |
| 3 | Click cell | Comparison Matrix | | `Line Detail Note` slide-over with extraction evidence, original vs normalized values. |
| 4 | Click "Select Scoring Model" | Comparison Matrix | | Dropdown/modal to choose model version. Changing model flags matrix as Stale. |
| 5 | Navigate to Scoring Model Builder | Scoring Model Builder | | Edit weights, criteria. Live preview shows impact. |
| 6 | Navigate to Scenario Simulator | Scenario Simulator | | Create scenarios, adjust assumptions, compare. |

### Recommendation and Risk Flow

| Step | User Action | Screen | Decision Point | Outcome |
|------|------------|--------|----------------|---------|
| 1 | View recommendation | Recommendation | | Top vendor shown with confidence score and factors. |
| 2 | Click "Why This?" | Recommendation | | `Why This Recommendation` slide-over with full MCDA breakdown. |
| 3a | Click "Override Recommendation" | Recommendation | **Authorized?** | `Override Recommendation` modal: select alternative vendor, provide reason. Override always triggers approval gate. |
| 3b | Click "Request Re-Run" | Recommendation | | `Request Re-Run` modal: adjust weights or select model. New run triggered. |
| 4 | Navigate to Risk & Compliance | Risk Review | | View risk panels per vendor. |
| 5 | Click "Screen Vendor" | Risk Review | | `Sanctions Screening` modal. Run screening. Results populate risk card. Flagged results auto-escalate. |
| 6 | Complete Due Diligence Checklist | Risk Review | | Mark items complete/N/A. Green badge when all required items done. |
| 7 | Click "Escalation" | Risk Review | | `Risk Escalation Required` modal. Confirm creates approval item in queue. |

### Approval Flow

| Step | User Action | Screen | Decision Point | Outcome |
|------|------------|--------|----------------|---------|
| 1 | View queue | Approval Queue | | Filtered list of pending approvals. |
| 2 | Click item | Approval Queue | | Navigate to Approval Detail for that item. |
| 3a | Click "Approve" | Approval Detail | | `Confirm Decision` slide-over with mandatory reason. On confirm: approved. |
| 3a-i | Approved, no further gates | Approval Detail | **Workflow next step?** | Navigate to Award Decision or Negotiation Workspace (per RFQ workflow config). |
| 3b | Click "Reject" | Approval Detail | | `Confirm Decision` slide-over. On confirm: rejected. Notification to requester. |
| 3c | Click "Return for Revision" | Approval Detail | | `Return for Revision` modal with reason and instructions. Task created for requester. SLA resets. |
| 4 | Click "Reassign" | Approval Queue or Detail | | `Reassign Approval` modal. Eligible assignees filtered by delegation rules. |
| 5 | Click "Bulk Approve" | Approval Queue | | `Confirm Bulk Approve` modal with count summary and shared reason. |
| 6 | Click "Snooze" | Approval Queue | | `Snooze Approval` modal with duration picker. Item hidden until snooze expires. |

### Negotiation and Award Flow

| Step | User Action | Screen | Decision Point | Outcome |
|------|------------|--------|----------------|---------|
| 1 | Click "Start Round" | Negotiation Workspace | | `Launch Negotiation Round` slide-over: scope, vendors, deadline. On confirm: round starts, vendors notified. |
| 2 | Click "Counter-Offer" | Negotiation Workspace | | `Submit Counter Offer` slide-over: edit prices/terms per line. On submit: timeline updated, vendors notified. |
| 3 | Click "Request BAFO" | Negotiation Workspace | | `Request BAFO` modal: select vendors, set deadline. Status changes to "BAFO requested." |
| 4 | Click "Close Negotiation" | Negotiation Workspace | | `Close Negotiation` modal. Best offer surfaced for Award Decision. |
| 5 | Click "Finalize Award" | Award Decision | **Standstill period configured?** | `Award Confirmation` modal. |
| 5a | No standstill | Award Decision | | Award finalized. Navigate to PO/Contract Handoff. |
| 5b | Standstill configured | Award Decision | | Award enters "Protest period active." Timer badge counts down. |
| 6 | Click "Split Award" | Award Decision | | `Configure Split Award` slide-over: allocate per vendor. |
| 7 | Click "Send Debrief" | Award Decision | | `Vendor Debrief` modal per non-winning vendor. Auto-generate or compose. Copy stored in Evidence Vault. |
| 8 | Click "Record Protest" (standstill) | Award Decision | **Protest resolution?** | `Record Protest` modal. Award paused. Escalation created. Options: Uphold, Re-evaluate, Cancel & Re-tender. |

### Handoff and Post-Award Flow

| Step | User Action | Screen | Decision Point | Outcome |
|------|------------|--------|----------------|---------|
| 1 | Click "Validate Payload" | PO/Contract Handoff | **Validation passes?** | Inline validation results. Send button enabled on pass. |
| 2 | Click "Send" | PO/Contract Handoff | | `Create PO/Contract Handoff` modal. Status: Sending -> Sent or Failed. |
| 3 | Click "Retry" (on failure) | PO/Contract Handoff | | `Retry Handoff` modal. Edit payload, retry. |

### Audit and Evidence Flow

| Step | User Action | Screen | Outcome |
|------|------------|--------|---------|
| 1 | Filter trail by scope | Decision Trail | Timeline filtered by RFQ, run, or approval item. |
| 2 | Click "Verify Integrity" | Decision Trail | `Verify Audit Integrity` modal. Hash-chain verification result. |
| 3 | Click "Export Trail" | Decision Trail | `Export Decision Trail` modal. Select scope, format (PDF/CSV/JSON), date range. Download or background job. |
| 4 | Click "Create Evidence Bundle" | Evidence Vault | `Create Evidence Bundle` modal. Name, scope, description. Draft bundle created. |
| 5 | Click "Add to Bundle" (on document) | Evidence Vault | Dropdown of draft bundles. Document added. |
| 6 | Click "Finalize Bundle" | Evidence Vault | Bundle locked with manifest. Exportable as ZIP. |

## Nexus Capability Map

This table maps every Atomy-Q user capability to the Nexus package or orchestrator that provides the backend implementation. Implementers should inject the listed contract interfaces; never import concrete classes across layers.

| User Capability | Nexus Layer | Package / Orchestrator | Contract / Interface |
|-----------------|-------------|------------------------|---------------------|
| User authentication (email/password, session, token) | L1 | Identity | `UserManagerInterface`, `AuthenticationManagerInterface` |
| Multi-factor authentication (MFA) | L1 | Identity | `MfaEnrollmentInterface` |
| Single Sign-On (SSO via SAML/OAuth2/OIDC) | L1 | SSO | `SsoProviderInterface`, `SamlHandlerInterface` |
| Role-based and attribute-based access control | L1 | Identity | `PermissionCheckerInterface`, RBAC + ABAC |
| Tenant context and isolation | L1 | Tenant | `TenantContextInterface`, `TenantRepositoryInterface` |
| RFQ/requisition CRUD and lifecycle | L1 | Procurement | `ProcurementManagerInterface` (createRequisition, getRequisition, submitForApproval, approve/reject) |
| Requisition state transitions | L1 | Procurement | `RequisitionInterface` (getClosingDate, isClosedForQuotes), `RequisitionRepositoryInterface` |
| Vendor quote creation and management | L1 | Procurement | `ProcurementManagerInterface` (createVendorQuote, compareVendorQuotes, acceptVendorQuote) |
| Vendor quote locking for comparison runs | L1 | Procurement | `VendorQuoteManager` (lockQuote, unlockQuote, unlockAllForRun), `VendorQuoteRepositoryInterface` |
| Purchase order creation from award | L1 | Procurement | `ProcurementManagerInterface` (convertRequisitionToPO, createDirectPO, releasePO) |
| Goods receipt recording | L1 | Procurement | `ProcurementManagerInterface` (recordGoodsReceipt), `GoodsReceiptNoteInterface` |
| Three-way match (PO/GRN/Invoice) | L1 | Procurement | `ProcurementManagerInterface` (performThreeWayMatch) |
| Quote document upload and storage | L1 | Document, Storage | `OrchestratorDocumentRepositoryInterface` (adapts Nexus\Document + Nexus\Storage) |
| Quote ingestion (upload, validate, dispatch) | L2 | QuotationIntelligence | `QuoteIngestionServiceInterface` |
| Full intelligence pipeline (extract, map, normalize, assess risk) | L2 | QuotationIntelligence | `QuotationIntelligenceCoordinatorInterface::processQuote` |
| Semantic mapping to taxonomy (UNSPSC) | L2 | QuotationIntelligence | `SemanticMapperInterface` (AiSemanticMapper), uses L1 MachineLearning |
| UoM normalization | L2 | QuotationIntelligence | `QuoteNormalizationServiceInterface::normalizeQuantity`, uses L1 Uom (`UomConversionEngine`) |
| Currency normalization (FX lock date aware) | L2 | QuotationIntelligence | `QuoteNormalizationServiceInterface::normalizePrice`, uses L1 Currency (`ExchangeRateProviderInterface`) |
| Commercial terms extraction (incoterm, payment days, lead time, warranty) | L2 | QuotationIntelligence | `CommercialTermsExtractorInterface` (RegexCommercialTermsExtractor) |
| Risk assessment (pricing anomalies, term deviations) | L2 | QuotationIntelligence | `RiskAssessmentServiceInterface` (RuleBasedRiskAssessmentService) |
| Comparison matrix building (cluster, min/max/avg, recommendation) | L2 | QuotationIntelligence | `QuoteComparisonMatrixServiceInterface::buildMatrix` |
| Preview comparison run (relaxed validation) | L2 | QuotationIntelligence | `BatchQuoteComparisonCoordinatorInterface::previewQuotes` |
| Final comparison run (full validation gates) | L2 | QuotationIntelligence | `BatchQuoteComparisonCoordinatorInterface::compareQuotes` |
| Comparison readiness validation (RFQ closed, min vendors, confidence) | L2 | QuotationIntelligence | `ComparisonReadinessValidatorInterface` |
| Weighted vendor scoring (MCDA: Price/LCC, Risk, Delivery, Sustainability) | L2 | QuotationIntelligence | `VendorScoringServiceInterface` (WeightedVendorScoringService) |
| Approval gate evaluation (high-risk / low-score triggers) | L2 | QuotationIntelligence | `ApprovalGateServiceInterface` (HighRiskApprovalGateService) |
| Decision trail writing (hash-chained, tamper-evident) | L2 | QuotationIntelligence | `DecisionTrailWriterInterface` (HashChainedDecisionTrailWriter) |
| Sanctions screening (vendor due diligence) | L1 | Sanctions | `SanctionsScreenerInterface` |
| AML / adverse risk assessment | L1 | AmlCompliance | `AmlRiskAssessorInterface` |
| KYC verification (vendor onboarding) | L1 | KycVerification | `KycVerificationManagerInterface` |
| Audit trail (CRUD operations, user actions, timeline) | L1 | AuditLogger | `AuditLogManagerInterface`, `TimelineProviderInterface` |
| Notifications (email, in-app, push) | L1 | Notifier | `NotificationManagerInterface` |
| Reporting engine (generate, schedule, distribute) | L1 | Reporting | `ReportManager`, `ReportGeneratorInterface`, `ReportDistributorInterface` |
| Report scheduling | L1 | Reporting | `ScheduleManagerInterface` |
| Report export (PDF, Excel, CSV) | L1 | Reporting | `ExportManagerInterface` |
| Reporting pipeline orchestration | L2 | InsightOperations | `ReportingPipelineCoordinatorInterface`, `ReportExportPortInterface` |
| Dashboard snapshots | L2 | InsightOperations | `DashboardSnapshotPortInterface` |
| Feature flags (enable/disable features per tenant) | L1 | FeatureFlags | `FeatureFlagManagerInterface`, `FeatureToggleInterface` |
| Sequence generation (RFQ numbers, run IDs, PO numbers) | L1 | Sequencing | `SequenceManagerInterface`, `SequenceGeneratorInterface` |
| Currency management and exchange rates | L1 | Currency | `CurrencyManagerInterface`, `ExchangeRateProviderInterface` |
| Unit of measure management and conversion | L1 | Uom | `UomManagerInterface`, `UomConverterInterface` |
| File/document storage | L1 | Storage | `StorageInterface` |
| Cryptographic operations (hashing, encryption for audit chain) | L1 | Crypto | `HasherInterface`, `EncryptionServiceInterface` |
| Event streaming (async pipeline triggers) | L1 | EventStream | `EventStoreInterface`, `EventPublisherInterface` |
| Settings management (policy thresholds, defaults) | L1 | Setting | `SettingManagerInterface`, `SettingRepositoryInterface` |
| Telemetry and health monitoring | L1 | Telemetry | `TelemetryTrackerInterface`, `MetricsCollectorInterface` |
| Data privacy and consent (GDPR compliance) | L1 | DataPrivacy | `ConsentManagerInterface` |
| Data exchange (import/export operations) | L2 | DataExchangeOperations | Import/Export pipeline for ERP handoff |
| Integration gateway (3rd-party connector resilience) | L2 | ConnectivityOperations | Resilient integration gateway for PO/Contract handoff |
| Procurement operations (cross-package PO/GRN/payable flow) | L2 | ProcurementOperations | Coordinates Procurement, Inventory, Payable, Tax, Currency |

## Persona-Based Entry Points

This section defines the primary screen flows for each user persona, showing where they typically start and the most common paths they take through the application.

### Requester (creates sourcing needs)

**Primary entry:** Dashboard -> Create RFQ or RFQ List.

**Typical flow:**
1. Dashboard: review "My Tasks" and "My RFQs" KPI cards.
2. Click "New RFQ" or open RFQ List and click "Create RFQ."
3. Create RFQ: fill metadata, line items, terms; save draft or submit.
4. RFQ Detail: invite vendors, monitor responses.
5. Quote Intake: upload vendor responses, triage parse results.
6. Normalization Workspace: map and normalize accepted quotes.
7. Comparison Matrix: run preview, verify data, then run final comparison.
8. Receive notification when approval/award is complete.

### Buyer / Category Manager (evaluates and negotiates)

**Primary entry:** Dashboard -> RFQ List or Comparison Matrix.

**Typical flow:**
1. Dashboard: review active RFQs, recent comparison runs, and alerts.
2. RFQ List: filter by "Closed" status, open RFQ Detail.
3. Comparison Matrix: run or review comparison, select scoring model.
4. Recommendation: review AI recommendation, click "Why This?"
5. Scenario Simulator: test alternative assumptions.
6. Risk & Compliance: review risk panels, run sanctions screening, complete due diligence.
7. Negotiation Workspace: start rounds, submit counter-offers, request BAFO.
8. Award Decision: finalize winner or split, send debriefs.
9. PO/Contract Handoff: validate and send to ERP.

### Approver (governance gatekeeper)

**Primary entry:** Dashboard -> Approval Queue (via KPI card or alert).

**Typical flow:**
1. Dashboard: click "Pending Approvals" KPI card.
2. Approval Queue: filter by SLA urgency, review items.
3. Approval Detail: review evidence, comparison summary, risk assessment.
4. Decision: approve, reject, or return for revision (with mandatory reason).
5. If approved: system routes to next step (Award, Negotiation, or Handoff).
6. Decision Trail: optionally verify audit integrity.

### Procurement Manager (oversight and governance)

**Primary entry:** Dashboard -> Reports or Risk & Compliance.

**Typical flow:**
1. Dashboard: review high-level KPIs (savings trend, cycle time, compliance rate).
2. Reports & Analytics: view trend charts, export or schedule reports.
3. Scoring Policies: review and update scoring governance rules.
4. Risk & Compliance: monitor flagged vendors, review escalations.
5. Decision Trail: audit recent decisions, verify hash chain integrity, export for compliance.
6. Vendor Profile: review vendor performance trends.

### Admin (system configuration)

**Primary entry:** Dashboard -> Admin Settings or Users & Access.

**Typical flow:**
1. Admin Settings: configure policy thresholds, workflow defaults, feature flags.
2. Scoring Policies: create or update scoring policies, assign to categories.
3. RFQ Templates: create or maintain templates for common sourcing types.
4. Users & Access: invite users, assign roles, configure delegation rules, set authority limits.
5. Integrations Configuration: set up or manage ERP/email/sanctions connectors.
6. Integration Monitor: verify connector health.

### Auditor (compliance verification)

**Primary entry:** Dashboard -> Decision Trail or Evidence Vault.

**Typical flow:**
1. Decision Trail: filter by date range or RFQ, verify audit integrity.
2. Export trail as PDF or CSV for external audit.
3. Evidence Vault: search for specific documents, review evidence bundles.
4. Export finalized evidence bundles as ZIP.
5. Vendor Profile: review vendor compliance history and screening results.
6. Reports: generate compliance-focused reports.

### Executive (strategic oversight)

**Primary entry:** Dashboard.

**Typical flow:**
1. Dashboard: review executive KPI cards (total savings, approval cycle times, risk exposure).
2. Reports & Analytics: view trend charts, savings dashboards.
3. Click into specific KPI for drill-down to underlying RFQs or comparisons.
4. Notification Center: review high-priority alerts and escalations.
