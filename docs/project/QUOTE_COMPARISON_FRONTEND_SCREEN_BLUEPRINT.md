# Atomy-Q: Agentic Quote Comparison SaaS

## Product Overview

**The Pitch:** A procurement intelligence workspace that ingests vendor quotes, normalizes line-items, compares vendors side-by-side, applies policy-aware scoring, and routes high-risk outcomes through governed approvals with full decision traceability.

**For:** Requesters, Buyers, Approvers, Procurement Managers, Admins, Auditors, and Executives in medium-to-large organizations.

**Device:** Desktop-first (optimized for 1366px+), responsive down to tablet.

**Design Direction:** "Operational Intelligence Console." Dense, high-signal interface with clear hierarchy, fast keyboard workflows, and explicit compliance indicators.

**Inspired by:** Bloomberg Terminal (modernize), Linear (density), Stripe Dashboard (table precision)

**Tenant Context:** Each user is tied to one tenant. No tenant switching UI is provided.

## Global Layout Shell

### Sidebar
- Primary navigation links: Dashboard, RFQs, Quote Intake, Comparison Matrix, Approvals, Reports.
- Collaboration links: Notifications, Tasks, Mentions.
- Governance links: Risk & Compliance, Decision Trail, Evidence Vault.
- Administration links: Users & Access, Scoring Policies, Templates, Integrations, Feature Flags.
- Account links: Profile, Preferences, Help/Docs.

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

**Components:**
- Auth form.
- Inline error message.
- Security notice text.

**Interactions:**
- Click Forgot Password opens `Reset Password` modal.
- Successful credentials with policy requirement opens `Verify MFA` modal.

### 2. Home Dashboard

**Purpose:** Provide role-aware overview of workload, risk, and priority actions.

**Layout:** Three-zone layout with KPI strip, task/feed area, and right rail for alerts.

**Key Elements:**
- KPI cards (active RFQs, pending approvals, savings trend).
- My Tasks list.
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

**Layout:** Filter rail on top, data table/list below.

**Key Elements:**
- Search bar.
- Status/owner/date filters.
- RFQ table with sortable columns.
- Bulk action controls.

**States:**
- Empty list.
- Filtered no results.
- High-volume paginated list.

**Components:**
- Filter chips.
- Sortable table headers.
- Pagination controls.

**Interactions:**
- Click row opens RFQ Detail.
- Click Create RFQ routes to Create RFQ.

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
- Leaving with unsaved changes opens `Discard Changes?` modal.

### 5. RFQ Detail

**Purpose:** Operate one RFQ lifecycle from creation through quote collection.

**Layout:** Header summary, tabbed content area, right-side activity timeline.

**Key Elements:**
- RFQ summary header.
- Vendor participation tab.
- Documents tab.
- Activity timeline.

**States:**
- Draft.
- Open for submission.
- Closed.

**Components:**
- Tab navigation.
- Timeline item.
- Metadata badges.

**Interactions:**
- Click Invite Vendors opens `Send Invitations` modal.
- Click Send Reminder opens `Reminder Confirmation` modal.

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

**Layout:** Queue list on left, selected submission detail on right.

**Key Elements:**
- Intake queue.
- Upload zone.
- Parse confidence panel.
- Validation errors list.

**States:**
- Processing.
- Parsed with warnings.
- Accepted.
- Rejected.

**Components:**
- Confidence badge.
- Validation error callout.
- Upload progress indicator.

**Interactions:**
- Upload action opens `Upload & Parse Quote` modal.
- Low-confidence detection opens `Low Confidence Extraction` modal.

### 8. Quote Normalization Workspace

**Purpose:** Resolve mapping to make vendor quotes directly comparable.

**Layout:** Split workspace with source lines and normalized target mapping grid.

**Key Elements:**
- Source line list.
- Mapping editor.
- UOM/currency conversion controls.
- Conflict queue.

**States:**
- Unmapped.
- Partially mapped.
- Fully normalized.

**Components:**
- Mapping dropdown.
- Conversion badge.
- Conflict indicator.

**Interactions:**
- Mapping conflict opens `Resolve Line Mapping Conflict` modal.
- Manual override opens `Normalization Override` modal.

### 9. Quote Comparison Matrix

**Purpose:** Provide detailed side-by-side line and total comparison.

**Layout:** Sticky header vendor columns and sticky first line-item column.

**Key Elements:**
- Summary row with totals and deltas.
- Category-grouped line rows.
- Terms comparison section.
- Normalization view toggle.

**States:**
- Not generated.
- Generated.
- Stale (requires recalculation).

**Components:**
- Best-price highlight.
- Delta badge.
- Missing-value placeholder.

**Interactions:**
- Click Run/Recalculate opens `Run Comparison Engine` modal.
- Click cell opens detail note modal for that line.

### 10. Scoring Model Builder

**Purpose:** Configure weighted criteria and enforce scoring governance.

**Layout:** Criteria list editor with live scoring preview panel.

**Key Elements:**
- Weight sliders/inputs.
- Criteria definitions.
- Constraint/policy rules.
- Version notes.

**States:**
- Draft config.
- Valid config.
- Policy violation.

**Components:**
- Weight control.
- Policy warning banner.
- Version chip.

**Interactions:**
- Editing weights opens `Adjust Scoring Weights` modal.
- Rule breach opens `Policy Violation Warning` modal.

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

**States:**
- High confidence.
- Medium confidence.
- Low confidence requiring review.

**Components:**
- Confidence meter.
- Factor list.
- Rationale panel.

**Interactions:**
- Click Why this opens `Why This Recommendation` modal.

### 13. Risk & Compliance Review

**Purpose:** Consolidate and evaluate non-price risk signals.

**Layout:** Risk panels by category with escalation rail.

**Key Elements:**
- Sanctions/policy/commercial risk cards.
- Risk severity ladder.
- Escalation requirement list.

**States:**
- No critical risk.
- Medium risk.
- High risk requiring escalation.

**Components:**
- Risk badge.
- Escalation flag.
- Compliance checklist.

**Interactions:**
- Escalation trigger opens `Risk Escalation Required` modal.
- Exception request opens `Policy Exception Request` modal.

### 14. Approval Queue

**Purpose:** Prioritize and process all gated decisions.

**Layout:** Queue table with SLA columns and assignment actions.

**Key Elements:**
- Pending approvals list.
- Priority and SLA indicators.
- Assignee/delegation controls.

**States:**
- Empty queue.
- Active queue.
- SLA-breach risk.

**Components:**
- SLA timer badge.
- Priority marker.
- Assignment control.

**Interactions:**
- Click item opens Approval Detail.
- Reassign action opens `Reassign Approval` modal.

### 15. Approval Detail

**Purpose:** Execute approval decisions with full context and auditability.

**Layout:** Decision panel with evidence tabs and decision history.

**Key Elements:**
- Summary of recommended outcome.
- Evidence/doc links.
- Decision reason input.
- Prior approval history.

**States:**
- Pending.
- Approved.
- Rejected.
- Returned for revision.

**Components:**
- Decision buttons.
- Mandatory reason field.
- Evidence panel.

**Interactions:**
- Approve/reject action opens `Confirm Decision` modal.

### 16. Negotiation Workspace

**Purpose:** Manage negotiation rounds and counter-offer changes.

**Layout:** Timeline of rounds with side panel for current offer edits.

**Key Elements:**
- Round history cards.
- Current counter-offer form.
- Concession delta summary.

**States:**
- No negotiation yet.
- Active round.
- Final round complete.

**Components:**
- Timeline rail.
- Delta chip.
- Round status badge.

**Interactions:**
- Start round action opens `Launch Negotiation Round` modal.
- Counter-offer action opens `Submit Counter Offer` modal.

### 17. Award Decision

**Purpose:** Finalize winner or split award with governance checks.

**Layout:** Decision summary panel with allocation controls.

**Key Elements:**
- Winner recommendation.
- Savings impact section.
- Split allocation inputs.
- Decision sign-off area.

**States:**
- Candidate selected.
- Awaiting sign-off.
- Finalized.

**Components:**
- Allocation control.
- Savings badge.
- Sign-off status indicator.

**Interactions:**
- Finalize action opens `Award Confirmation` modal.
- Split toggle opens `Configure Split Award` modal.

### 18. PO/Contract Handoff

**Purpose:** Send approved outcomes to ERP/procurement downstream systems.

**Layout:** Handoff configuration form with payload preview.

**Key Elements:**
- Destination system selector.
- Payload preview panel.
- Handoff status timeline.

**States:**
- Ready to send.
- Sending.
- Sent.
- Failed with retry.

**Components:**
- Integration status badge.
- Payload viewer.
- Retry action button.

**Interactions:**
- Send action opens `Create PO/Contract Handoff` modal.

### 19. Decision Trail / Audit Ledger

**Purpose:** Display immutable decision and system event chain for governance.

**Layout:** Chronological event ledger with filterable side panel.

**Key Elements:**
- Event timeline.
- Actor/action metadata.
- Hash/integrity fields.

**States:**
- Full verified trail.
- Partial trail with warning.

**Components:**
- Event card.
- Integrity badge.
- Filter controls.

**Interactions:**
- Integrity check action opens `Verify Audit Integrity` modal.

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

**States:**
- Empty vault.
- Populated vault.
- Restricted document access.

**Components:**
- File row item.
- Tag chips.
- Version badge.

**Interactions:**
- Open file preview, download, and evidence bundle selection.

### 22. Reports & Analytics

**Purpose:** Provide operational and executive reporting.

**Layout:** KPI dashboard with configurable charts and report table.

**Key Elements:**
- Savings/cycle/compliance KPIs.
- Trend charts.
- Report schedule list.

**States:**
- Default dashboard.
- Filtered custom view.
- No-data interval.

**Components:**
- KPI tiles.
- Chart components.
- Report cards.

**Interactions:**
- Export action opens `Export Options` modal.

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
- Delegation settings.
- Approval authority limits.

**States:**
- Active user.
- Suspended user.
- Pending invite.

**Components:**
- Role chip.
- Permission toggle.
- Invite control.

**Interactions:**
- Save access changes with confirmation prompts for critical role changes.

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
