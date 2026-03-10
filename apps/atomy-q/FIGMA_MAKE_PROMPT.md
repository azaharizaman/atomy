# Figma Make Prompt -- Atomy-Q Top 10 Screens

Copy the entire prompt below (between the horizontal rules) and paste it into Figma Make.

---

## Prompt

Design a desktop-first SaaS application called **Atomy-Q** -- an enterprise procurement intelligence workspace for quotation comparison. The app is optimized for 1440px wide screens. NO DARK MODE. The visual direction is "Operational Intelligence Console": high density, high signal-to-noise ratio, clean hierarchy, and professional typography. Inspired by Bloomberg Terminal (modernized), Linear (density and polish), Stripe Dashboard (table precision), and Keeyns (hierarchical tabs within tabs). Use a muted, homogenous base color scheme (slate/gray tones for backgrounds and surfaces) with a single accent color (indigo/blue) reserved exclusively for interactive elements (buttons, links, active states). Status badges use semantic colors: green for success/active, amber for warning/pending, red for error/rejected, slate for neutral/draft.

All modals are slide-overs that animate in from the right edge of the screen, overlaying the current view. Slide-overs come in 4 widths: small (30%), medium (40%), large (50%), extra-large (60%).

---

### LAYOUT SYSTEM

This application has two layouts. Design both as reusable frames/components.

**Layout 1: Default Layout**
- Left sidebar (200px wide) containing the main navigation.
- Top bar spanning the full width above the content area. The top bar contains: a search input with a "/" shortcut hint, two quick-action buttons ("New RFQ" primary button and "AI Insights" ghost button), a notification bell icon with a red unread count badge, and a user avatar that opens a dropdown menu.
- Content area fills the remaining space to the right of the sidebar and below the top bar.
- Compact footer at the bottom: version text, environment badge, status link, legal links.

The sidebar navigation structure (accordion -- only one expandable section open at a time):
- Dashboard (icon: grid/layout)
- Requisition (RFQ) (icon: file-text, expandable, with child items):
  - Active (with count badge "12")
  - Closed (with count badge "5")
  - Awarded (with count badge "3")
  - Archived
  - Draft (with count badge "2")
- Document (icon: folder-archive)
- Reporting (icon: bar-chart)
- Settings (icon: settings/gear, expandable, with child items):
  - Users & Roles
  - Scoring Policies
  - Templates
  - Integrations
  - Feature Flags

**Layout 2: Workspace Layout**
- The main sidebar collapses to a narrow icon-only rail (48px wide). Hovering it expands the full sidebar as an overlay.
- To the right of the collapsed rail is the **Active Record Menu** panel (approximately 25% of viewport width, ~360px). This panel is persistent and scrollable. It has two zones:
  - **Zone 1 (Record Snippet)**: Shows the RFQ number and title in bold, a colored status badge, a row of 4 compact metric chips (Vendors: 5, Quotes: 8, Est. Value: $1.2M, Savings: 12%), and a single primary lifecycle action button (e.g., "Close for Submissions").
  - **Zone 2 (Navigation)**: Two groups separated by a subtle divider labeled "RFQ" and "CHILD RECORDS". The RFQ group has 5 links: Overview, Details, Line Items, Vendors, Award. The Child Records group has 7 links each with a right-aligned count badge: Quote Intake (8), Comparison Runs (3), Approvals (2), Negotiations (1), Documents (12), Risk & Compliance (status dot: green/amber/red), Decision Trail. The currently active link is highlighted with the accent color and a left border indicator.
- To the right of the Active Record Menu is the **Work Surface** which fills the remaining width. It has a breadcrumb bar at the top (e.g., "RFQs > Server Infrastructure Refresh > Quote Intake") and the main content below.
- The same top bar from Layout 1 spans the full width at the top.

---

### SCREEN 1: RFQ List (Default Layout)

Show the Default Layout with the sidebar "Requisition (RFQ)" section expanded and "Active" highlighted. The content area displays:

- A page header: title "Requisitions" with a subtitle "12 active requisitions", and a "Create RFQ" primary button on the right.
- A filter bar below the header: a search input, dropdown filters for Status, Owner, and Category, and filter chip pills for active filters.
- A data table with these columns: a checkbox column, an expand chevron column, ID (e.g., "RFQ-2401"), RFQ (title text in semibold with the owner's avatar and name as a smaller subtitle below), Status (colored badge), Deadline (date), Est. Value (currency), and an Actions overflow menu ("...").
- Show 8 rows of realistic procurement data. Example RFQ titles: "Server Infrastructure Refresh", "Office Furniture Q2", "Cloud Software Licenses", "Network Security Audit", "Marketing Print Materials", "Fleet Vehicle Leasing", "Catering Services Contract", "IT Support Annual".
- One row should be expanded (the expand chevron rotated 90 degrees) showing an inline detail area below the row with a 3x2 grid of small labeled values: Category, Deadline, Vendors (count), Quotes (count), Est. Value, Savings %.
- Show 2 rows with checkboxes selected and a bulk action toolbar visible at the top of the table: "2 selected" text, buttons for "Close Selected", "Archive Selected", "Assign Owner", "Export Selected".
- Pagination at the bottom: "Page 1 of 3" with Previous/Next buttons.

---

### SCREEN 2: RFQ Workspace -- Overview Tab (Workspace Layout)

Show the Workspace Layout. The Active Record Menu shows:
- Snippet: "RFQ-2401 Server Infrastructure Refresh", badge "Active" (green), metrics: Vendors 5, Quotes 8, Est. Value $1.2M, Savings 12%. Action button: "Close for Submissions".
- Navigation: "Overview" is the active/highlighted link.

The Work Surface shows the Overview tab content:
- Breadcrumb: "RFQs > Server Infrastructure Refresh > Overview"
- 4 KPI scorecards in a row:
  - "Quotes Received": "8 / 10 expected" with a mini progress bar.
  - "Normalization": "75% complete" with a circular progress indicator.
  - "Comparison Status": "Preview" with an amber badge.
  - "Approval Status": "None" with a neutral/slate badge.
- Below the scorecards, an "Activity Timeline" section showing 6 chronological event entries. Each entry has: a timestamp (relative, e.g., "2 hours ago"), an actor name, an action description (e.g., "uploaded quote from Dell Technologies"), and a small icon. Example events: quote uploaded, vendor responded, invitation sent, normalization completed, comparison preview run, RFQ published.

---

### SCREEN 3: Quote Intake List (Workspace Layout)

Show the Workspace Layout. Active Record Menu has "Quote Intake" highlighted in the Child Records section (with badge "8").

Work Surface:
- Breadcrumb: "RFQs > Server Infrastructure Refresh > Quote Intake"
- Page header: "Quote Intake" title, "8 submissions" subtitle. Right side: "Upload Quote" primary button.
- A drag-and-drop upload zone bar (dashed border, icon, "Drop files here or click to browse" text).
- Filter bar: status dropdown, vendor dropdown.
- Data table columns: File name (with file-type icon), Vendor, Status (badge), Parse Confidence (colored bar or badge: High/Medium/Low), Uploaded At, Actions.
- Show 8 rows of realistic data. Mix of statuses: 3 "Accepted" (green), 2 "Parsed" (blue), 1 "Processing" (amber with spinner icon), 1 "Rejected" (red), 1 "Error" (red with error icon). Vendor names: Dell Technologies, HP Enterprise, Lenovo, Cisco Systems, IBM, Fujitsu, Supermicro, Juniper Networks. Confidence levels vary: mostly High, two Medium, one Low.

---

### SCREEN 4: Quote Intake Detail (Workspace Layout)

Show the Workspace Layout. Active Record Menu still shows the RFQ context.

Work Surface:
- Breadcrumb: "RFQs > Server Infrastructure Refresh > Quote Intake > Dell Technologies Quote"
- Two tabs at the top of the Work Surface content: "Overview" (active) and "Parsed Line Items".
- **Overview tab content:**
  - Left pane (~55% width): A document preview placeholder (gray rectangle with a PDF icon and filename "Dell_Quote_RFQ2401.pdf", page indicator "Page 1 of 4").
  - Right pane (~45% width): Vendor card (Dell Technologies logo placeholder, contact info), Parse Confidence bar (85% - High, green), Validation Results panel (a checklist: 4 items checked green "Pass", 1 item amber "Warning: lead time not detected").
- Action bar at the bottom: "Reject" (ghost/outline button), "Accept" (secondary button), "Accept & Normalize" (primary button), "Replace Document" (ghost), "Re-Parse" (ghost).

---

### SCREEN 5: Normalization Workspace (Workspace Layout)

Show the Workspace Layout.

Work Surface:
- Breadcrumb: "RFQs > Server Infrastructure Refresh > Quote Intake > Dell Technologies > Normalize"
- A toolbar at the top with: "Bulk Apply Mapping" button (disabled until rows selected), "Lock for Comparison" button, a lock status indicator showing "Unlocked", and a "Fully Normalized: 18/24 lines" progress badge.
- Split workspace below the toolbar:
  - **Left panel (~45%)**: "Source Lines" header. A list/table of extracted vendor line items. Columns: Checkbox, Line #, Vendor Description, Qty, Unit, Unit Price, Confidence badge. Show 8 lines. Two lines have a yellow warning icon (mapping conflict). Three lines have checkboxes selected.
  - **Right panel (~55%)**: "Normalized Mapping" header. A mapping grid showing for each source line: the mapped RFQ Line Item (dropdown selector), Taxonomy Code (UNSPSC code chip), Normalized Qty, Normalized Unit, Normalized Unit Price, Currency (all showing converted values). Lines with conflicts show an amber conflict indicator. One line shows an "Override" info chip (blue) indicating manual override.

---

### SCREEN 6: Comparison Runs List (Workspace Layout)

Show the Workspace Layout. Active Record Menu has "Comparison Runs" highlighted (badge "3").

Work Surface:
- Breadcrumb: "RFQs > Server Infrastructure Refresh > Comparison Runs"
- Page header: "Comparison Runs" title, "3 runs" subtitle. Right side: "New Comparison Run" primary button.
- Data table columns: Run ID (e.g., "RUN-005"), Date, Type (badge: "Preview" amber or "Final" green), Status (badge: "Generated" / "Stale" / "Locked"), Scoring Model (version chip, e.g., "v2.1"), Created By (avatar + name), Actions.
- Show 3 rows:
  - Row 1: RUN-005, today, Final, Locked (with lock icon), v2.1, Alex Kumar. (highlighted as latest)
  - Row 2: RUN-004, yesterday, Preview, Stale (amber), v2.0, Sarah Chen.
  - Row 3: RUN-003, 3 days ago, Final, Generated, v1.8, Alex Kumar.

---

### SCREEN 7: Comparison Matrix (Workspace Layout)

Show the Workspace Layout. This is the most data-dense screen.

Work Surface:
- Breadcrumb: "RFQs > Server Infrastructure Refresh > Comparison Runs > Run #005"
- A toolbar: Run mode badge ("Final" green), Lock indicator (locked icon), "Unlock" ghost button, "Select Scoring Model" dropdown showing "v2.1", "View Original Values" toggle switch, "View Recommendation" button.
- A readiness/approval banner below the toolbar: green background, text "Auto-approved. Proceed to Award or Negotiation." with a "Go to Award" link.
- The comparison matrix table:
  - Sticky first column: Line Item description (grouped by category, e.g., "Servers", "Storage", "Networking").
  - Sticky top row: Vendor names as column headers (Dell Technologies, HP Enterprise, Lenovo, Cisco, IBM). Each header shows the vendor's total quoted amount and a rank badge (#1, #2, etc.).
  - A summary row at the top showing: Total per vendor, Delta from best (green highlight on the best, red percentage for others, e.g., "+8.3%").
  - Category group rows (collapsible) with 3-4 line items each. Each cell shows: normalized unit price, original unit price in smaller gray text below. Best price per row is highlighted with a green background tint. Missing values show a dash placeholder.
  - Below the matrix: a "Terms Comparison" section as a simple table (Incoterm, Payment Days, Lead Time, Warranty per vendor).
- Bottom of Work Surface: a "Recommendation" card. Shows: "Recommended: Dell Technologies", confidence meter (82% - High), top 3 contributing factors as bullet points, "View Full Breakdown" link, "Override" ghost button.

---

### SCREEN 8: Approval Queue (Default Layout, contextual entry)

Show the Default Layout. The sidebar shows "Requisition" expanded with no specific child highlighted (this is a cross-RFQ view).

Content area:
- Page header: "Approval Queue" title, "7 pending approvals" subtitle.
- Filter bar: Type dropdown (All, Comparison Approval, Risk Escalation, Policy Exception, Override), Status dropdown (Pending, Awaiting Evidence, Snoozed).
- Data table columns: Checkbox, RFQ (linked text, e.g., "RFQ-2401"), Type (badge), Summary (one-line description), Priority (High/Medium/Low badge), SLA Timer (countdown badge: green "2d 4h", amber "6h", red "OVERDUE"), Assignee (avatar + name), Actions.
- Show 7 rows with mixed types and SLA states. One row has the SLA timer in red "OVERDUE" with an urgent icon. Two rows are selected with checkboxes, and a bulk action toolbar shows: "2 selected", "Bulk Approve", "Bulk Reject", "Bulk Reassign" buttons.

---

### SCREEN 9: Approval Detail (Workspace Layout)

Show the Workspace Layout. The Active Record Menu shows the parent RFQ context. "Approvals" is highlighted in Child Records navigation.

Work Surface:
- Breadcrumb: "RFQs > Server Infrastructure Refresh > Approvals > APR-00412"
- A record header: Approval ID "APR-00412", Type badge "Comparison Approval" (blue), Status badge "Pending" (amber), SLA timer "1d 18h" (green), Assigned to "Marcus Webb" (avatar).
- Below the header, a two-column layout:
  - **Left column (~60%)**: "Approval Gate Reasons" alert panel (amber background, list: "High risk detected for vendor Cisco Systems", "Top vendor score 68.5 is below auto-approval threshold 70.0"). Below that: "Comparison Run Summary" embedded card showing a mini matrix excerpt (3 vendors, 3 key metrics, recommended vendor highlighted). Below that: "Prior Decision History" timeline (empty for first-time; or showing one "Returned for revision" entry if needed for illustration).
  - **Right column (~40%)**: "Evidence" tabbed panel with tabs: Documents (3), Screening Results (1), Audit Trail (5). The Documents tab is active showing 3 document rows (file name, type badge, date). Below the evidence panel: "Decision" section with 3 buttons stacked: "Approve" (green primary), "Reject" (red outline), "Return for Revision" (amber outline). A mandatory "Reason" textarea below the buttons with placeholder text "Provide your decision rationale...".

---

### SCREEN 10: Award Decision (Workspace Layout)

Show the Workspace Layout. The Active Record Menu has "Award" highlighted in the Primary RFQ Tabs section.

Work Surface:
- Breadcrumb: "RFQs > Server Infrastructure Refresh > Award"
- Status banner at top: "Awaiting Sign-off" (amber badge). "Based on Comparison Run #005, approved on Mar 8, 2026."
- Two-column layout:
  - **Left column (~55%)**:
    - "Recommended Winner" card: Dell Technologies, Total: $1,043,250, Savings: $156,487 (13.1%), Confidence: 82%. A green "Recommended" badge.
    - "Split Award" toggle (off by default). If toggled on, allocation sliders per vendor.
    - "Sign-off" section: a sign-off checklist (3 items: "Financial review complete", "Legal review complete", "Procurement lead sign-off"), with checkboxes. "Finalize Award" primary button (disabled until all sign-offs checked).
  - **Right column (~45%)**:
    - "Vendor Debrief" panel: list of 4 non-winning vendors (HP Enterprise, Lenovo, Cisco, IBM) each with a "Send Debrief" button and a status indicator (Not sent / Sent / N/A).
    - "PO / Contract Handoff" panel: Destination dropdown ("SAP ERP"), "Validate Payload" button, status indicator ("Not validated"). Below: a placeholder for the payload preview panel (collapsed).
    - "Protest Period" panel (if configured): Timer badge "Standstill: 10 days remaining". "Record Protest" ghost button.

---

### DESIGN SYSTEM NOTES

Create these as reusable Figma components:
1. **StatusBadge**: variants for Active (green), Closed (slate), Awarded (indigo), Draft (gray), Pending (amber), Approved (green), Rejected (red), Processing (amber), Locked (slate with lock icon), Stale (amber with refresh icon), Error (red with alert icon).
2. **SLATimerBadge**: variants for Safe (green), Warning (amber), Overdue (red).
3. **ConfidenceBadge**: variants for High (green), Medium (amber), Low (red).
4. **MetricChip**: small compact chip with label + value (used in Active Record Menu snippet).
5. **DataTable**: with sortable headers, checkbox column, expand chevron, pagination footer.
6. **SlideOver**: right-edge modal with sm/md/lg/xl width variants, header with title + close button, scrollable body, sticky footer with action buttons.
7. **KPIScorecard**: card with title, primary value, optional progress bar or indicator, clickable.
8. **BreadcrumbBar**: segmented breadcrumb with clickable segments and chevron separators.
9. **ActiveRecordSnippet**: the Zone 1 component of the Active Record Menu.
10. **NavigationLink**: for the Active Record Menu Zone 2, with icon, label, optional count badge, optional status dot, active state styling.

Typography: Use a professional sans-serif system font stack (Inter or similar). Headings: 600/700 weight. Body: 400. Captions/labels: 500, smaller size. Monospace for IDs and codes.

Spacing: 4px base unit. Content padding: 20-24px. Table row height: 44-48px. Card padding: 16-20px.

---
