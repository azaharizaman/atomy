# RFQ (Request for Quotation) Screen - Detailed UX Specification

## Overview

The RFQ screen is the **mission-control hub** of Atomy-Q where procurement professionals spend the majority of their time. It's the central workspace for managing the entire procurement lifecycle from creating a requisition to awarding a vendor. Think of it as a command center where users monitor progress, make decisions, and drive RFQs from creation through completion.

---

## 1. RFQ List Screen (Entry Point)

**URL:** `/rfqs`

This is the landing page where users see all their requisitions.

### UI Elements:

**Page Header:**
- Title: "Requisitions"
- Subtitle: Total count (e.g., "24 total requisitions")
- Primary Action: "Create RFQ" button (prominent, primary color)

**Filter Bar:**
- **Search:** Text input to search requisitions by title, ID, or keywords
- **Status Filter:** Dropdown with options: Active, Closed, Awarded, Archived, Draft, Pending
- **Owner Filter:** Dropdown to filter by person responsible (Alex Kumar, Sarah Chen, Marcus Webb, Priya Nair)
- **Category Filter:** Dropdown for IT Hardware, Facilities, Software, Security
- **Project Filter:** (If projects feature enabled) Dropdown to filter by associated project

**Active Filters Display:**
- Shows chips for each active filter with "×" to remove individually
- "Clear all" option when multiple filters active

**Data Table (Main Content):**
| Column | Width | Content |
|--------|-------|---------|
| ID | 90px | RFQ identifier (monospace, e.g., "RFQ-2401") |
| RFQ | Flexible | Title + Owner avatar/initials |
| Status | 110px | Status badge (color-coded) |
| Deadline | 110px | Due date |
| Est. Value | 120px | Estimated procurement value (right-aligned) |

**Table Features:**
- Sortable columns (click header)
- Selectable rows (checkboxes) for bulk actions
- Expandable rows (click to show inline details):
  - Category
  - Deadline
  - Vendors count
  - Quotes count
  - Est. Value
  - Savings %
- Click row to navigate to RFQ Overview

**Bulk Actions (when items selected):**
- Close Selected
- Archive Selected
- Assign Owner
- Export Selected

**Pagination:**
- Page indicator: "Page 1 of 5 (124 total)"
- Previous/Next buttons

---

## 2. RFQ Workspace (After clicking an RFQ)

**URL Pattern:** `/rfqs/{rfqId}/*`

The RFQ workspace has a **three-panel layout**:

### Panel 1: Left Navigation Rail (Collapsible)
- **Width:** 48px collapsed, 200px expanded on hover
- **Contents:**
  - Logo/Brand mark
  - Top-level nav: Dashboard, Requisitions (collapsed by default), Documents, Reporting, Settings

### Panel 2: Active Record Menu (360px fixed width)
This is the **primary navigation** within an RFQ. It stays visible while working on the RFQ.

**Zone A: Record Snippet (top)**
- RFQ ID (monospace, muted)
- RFQ Title (truncated if long)
- Status Badge
- **Quick Metrics:** Vendors count, Quotes count, Est. Value, Savings %
- **Primary Action Button:** Context-aware (e.g., "Close for Submissions", "Award RFQ", "View Details")

**Zone B: Navigation Links**

**RFQ Section:**
1. **Overview** - Dashboard with KPIs and activity
2. **Details** - Edit RFQ metadata and schedule
3. **Line Items** - Products/services being procured
4. **Vendors** - Manage invited vendors
5. **Award** - Final vendor selection

**Child Records Section:**
6. **Quote Intake** - Uploaded vendor quotes (badge shows count)
7. **Comparison Runs** - Price/comparison analyses (badge shows count)
8. **Approvals** - Approval workflow tasks (badge shows pending count)
9. **Negotiations** - Vendor negotiation threads
10. **Documents** - File vault
11. **Risk & Compliance** - Vendor screening results
12. **Decision Trail** - Audit log of decisions

### Panel 3: Main Work Surface
The actual content area where users perform their tasks.

---

## 3. RFQ Overview Screen

**URL:** `/rfqs/{rfqId}/overview`

This is the **dashboard** for each RFQ - the first thing users see after selecting an RFQ.

### UI Components:

**Next Step Card (Hero Area)**
- Prominent card showing the immediate action needed
- Icon + "Next step" label
- Action text (context-aware based on RFQ status):
  - Draft: "Complete details and publish"
  - Active: "Close for submissions"
  - Closed (no comparison): "Run comparison"
  - Closed (preview run): "Finalize comparison run"
  - Pending approvals: "Review pending approvals"
  - Ready: "Proceed to award"
- Deadline countdown if submission deadline set
- Action button linking to relevant page

**Schedule Timeline**
- Horizontal process track showing all milestones
- Milestones: Submission Deadline → Closing Date → Technical Review → Financial Review → Expected Award
- Visual indicators:
  - Completed milestones (green)
  - Current milestone (highlighted)
  - Upcoming milestones (muted)
  - Overdue/late milestones (amber warning)
- "Today" marker on the timeline

**KPI Scorecards (4 cards in grid)**

1. **Quotes Received**
   - Title: "Quotes received"
   - Value: Actual count (e.g., "4")
   - Subtitle: Expected count (e.g., "Expected 5")
   - Progress bar showing % received
   - Click navigates to Quote Intake

2. **Normalization Progress**
   - Title: "Normalization progress"
   - Value: "3/4" (accepted/total)
   - Subtitle: "Accepted quotes ready for comparison"
   - Progress bar showing % normalized
   - Click navigates to Quote Intake

3. **Comparison Status**
   - Title: "Comparison status"
   - Value: Run name (e.g., "RUN-005") or "—"
   - Subtitle: "Preview run" or "Latest run" or "No runs yet"
   - Status badge (Approved/Preview/Draft)
   - Click navigates to Comparison Runs

4. **Approval Status**
   - Title: "Approval status"
   - Value: "2 pending" or "Approved" or "Rejected"
   - Subtitle: "Action required" or "No gates triggered"
   - Status badge
   - Click navigates to Approvals

**Activity Timeline**
- Chronological feed of all RFQ activity
- Each event shows:
  - Timestamp ("2 hours ago", "Yesterday")
  - Actor (person or "System")
  - Action description
  - Icon by type (quote upload, invitation, comparison, approval, creation)
- "No activity yet" state when empty

---

## 4. RFQ Details Screen

**URL:** `/rfqs/{rfqId}/details`

Used to view and edit RFQ metadata.

### View Mode:
**Commercial Metadata Card:**
- RFQ Number
- Category
- Estimated Value
- Savings %
- Project (if assigned)

**Description Card:**
- Free-text description of the RFQ

**Schedule & Deadlines Card:**
- Submission deadline (required)
- Closing date
- Technical review due
- Financial review due
- Expected award date

### Edit Mode:
- Text inputs for all fields
- Datetime pickers for dates
- Textarea for description
- Cancel/Save buttons

---

## 5. Quote Intake Screen

**URL:** `/rfqs/{rfqId}/quote-intake`

Manages vendor quote submissions.

### UI Elements:

**Header:**
- Title: "Quote Intake"
- Subtitle: Count of submissions
- "Upload Quote" button

**Upload Zone:**
- Drag-and-drop area
- "Browse" button
- Compact variant shown

**Filters:**
- Status filter: All, Accepted, Parsed, Processing, Rejected, Error
- Vendor filter: All vendors dropdown

**Quotes Table:**
| Column | Content |
|--------|---------|
| File name | Vendor quote PDF name with icon |
| Vendor | Vendor name |
| Status | Badge (Accepted/Parsed/Processing/Rejected/Error) |
| Parse confidence | Badge (High/Medium/Low) with visual indicator |
| Uploaded at | Timestamp |

**Interactions:**
- Click row to navigate to Quote Detail/Normalize page
- Empty state: "No submissions yet" with upload prompt

---

## 6. Quote Normalization Screen

**URL:** `/rfqs/{rfqId}/quote-intake/{quoteId}/normalize`

Review and normalize vendor quotes against RFQ line items.

### UI Elements:

**Blocking Issues Alert (conditional)**
- Amber banner when normalization conflicts exist
- Lists blocking issues preventing comparison freeze
- Action: Resolve conflicts before proceeding

**Action Bar:**
- "Bulk Apply Mapping" button
- "Lock for Comparison" toggle
- Status badge: "Fully Normalized: 18/24 lines"
- "Freeze comparison" button (primary)
- "Manual assist" button
- "Decision trail" link

**Split View Layout:**

**Left Panel: Source Lines**
- List of extracted vendor line items
- Each row shows:
  - Checkbox for bulk selection
  - Line number
  - Description
  - Quantity + Unit
  - Unit Price
  - Confidence badge
  - Conflict indicator (if ambiguous mapping)

**Right Panel: Normalized Mapping**
- Mapped RFQ line items
- Shows: RFQ Line #, UNSPSC code, Quantity, Unit Price
- Visual indication of mapping status

---

## 7. Comparison Runs Screen

**URL:** `/rfqs/{rfqId}/comparison-runs`

List of all price comparison analyses.

### UI Elements:

**Frozen Snapshot Banner (conditional)**
- Green banner when comparison is locked
- "Snapshot frozen — comparison inputs are locked for approval"
- "Decision trail" link

**Header:**
- Title: "Comparison Runs"
- Count subtitle
- "New Comparison Run" button

**Runs Table:**
| Column | Content |
|--------|---------|
| Run ID | Monospace ID |
| Date | Creation date |
| Type | Badge (Final/Preview) |
| Status | Badge (Generated/Stale/Locked) |
| Scoring model | Version chip |
| Created by | Avatar + name |

**Interactions:**
- Click row to view comparison matrix

---

## 8. Comparison Matrix Screen

**URL:** `/rfqs/{rfqId}/comparison-runs/{runId}`

Detailed price comparison table.

### UI Elements:

**Action Bar:**
- Status badge (Final/Preview)
- "Snapshot frozen" tag
- Lock/Unlock toggle
- "Scoring model: v2.1" label
- "View original values" checkbox
- "View Recommendation" button
- "Decision trail" link

**Auto-Approval Banner:**
- Green banner: "Auto-approved. Proceed to Award or Negotiation."
- "Go to Award" link

**Comparison Matrix Table:**
- Rows: Line items grouped by category
- Columns: Vendors (ranked #1, #2, #3...)
- Cells: Unit price (best price highlighted in green)
- Below each price: Original/unnormalized price

**Recommendation Panel:**
- "Recommended: [Vendor Name]"
- Confidence score + level
- Bullet points of reasoning:
  - Best total cost
  - Strong compliance score
  - Lead time within target
- "View Full Breakdown" button
- "Override" button (for manual selection)

---

## 9. Vendors Screen

**URL:** `/rfqs/{rfqId}/vendors`

Manage invited vendors.

### UI Elements:

**Header:**
- Title: "Invited vendors"
- Subtitle: "Roster, invitation state, and quick outreach actions"
- "Invite vendors" button

**Vendor List:**
- Each vendor card shows:
  - Vendor name
  - Contact info
  - Status badge (Responded/Invited)

**Empty State:**
- "No vendors invited yet"
- "Invite vendors to receive and compare quotes."

---

## 10. Approvals Screen

**URL:** `/rfqs/{rfqId}/approvals`

Workflow approvals required for the RFQ.

### UI Elements:

**Header:**
- Title: "Approvals"
- Subtitle: Count (e.g., "3 approval(s) for this RFQ")

**Approvals Table:**
| Column | Content |
|--------|---------|
| ID | Clickable link to detail |
| Type | Badge with approval type |
| Summary | Description |
| Priority | Badge (High/Medium/Low) |
| Assignee | Name |

**Empty State:**
- "No pending approvals"
- "Approval gates will appear here when comparison or risk triggers require sign-off."

---

## 11. Award Screen

**URL:** `/rfqs/{rfqId}/award`

Final vendor selection and award process.

### UI Elements:

**Status Banner:**
- Amber banner showing award status
- "Awarded to [Vendor]. Total: $X · Savings: Y%"

**Two-Column Layout:**

**Left Column:**
1. **Winner Card:**
   - Winner name
   - Total amount + Savings %
   - Status badge

2. **Sign-off Card:**
   - Checkboxes:
     - Financial review complete
     - Legal review complete
     - Procurement lead sign-off
   - "Finalize Award" button (disabled until checked)

**Right Column:**
- **Vendor Debrief:**
  - "Non-winning vendors" list
  - Each has "Send debrief" button

---

## 12. Line Items Screen

**URL:** `/rfqs/{rfqId}/line-items`

Products/services being procured.

### UI Elements:

**Header:**
- Title: "Line items"
- Subtitle: "Editable because the RFQ is still in draft" or "Read-only operational view"
- View toggle: Table view / Grid view

**Table View:**
| Column | Content |
|--------|---------|
| Type | Section (heading) or Line item |
| Description | Item description |
| Category | Category tag |
| Qty | Quantity (right-aligned) |
| Unit | Unit of measure |
| Unit price | Target price |
| Total | Qty × Unit price |

**Grid View:**
- Cards for each line item
- Shows description, category, qty, unit, price, total

---

## 13. Other Screens (Lesser Used)

**Risk & Compliance** (`/rfqs/{rfqId}/risk`)
- Screening summary
- Vendor compliance status

**Negotiations** (`/rfqs/{rfqId}/negotiations`)
- Vendor negotiation threads
- Empty state: "Launch a negotiation from the Award or Comparison view"

**Documents** (`/rfqs/{rfqId}/documents`)
- Document vault
- Empty state: "Uploaded quotes and generated reports will appear here."

**Decision Trail** (`/rfqs/{rfqId}/decision-trail`)
- Audit timeline of key decisions
- Actor, action, timestamp

---

## RFQ Status Lifecycle

```
DRAFT → ACTIVE → CLOSED → AWARDED → ARCHIVED
                ↓
              PENDING
```

- **Draft:** Initial creation, editable
- **Active:** Open for vendor submissions
- **Closed:** Submissions closed, ready for comparison
- **Pending:** Waiting for approvals
- **Awarded:** Vendor selected
- **Archived:** Completed/archived

---

## Key User Interactions & Flows

1. **Create RFQ:** List page → "Create RFQ" → Fill details → Publish
2. **Invite Vendors:** RFQ → Vendors → Invite vendors
3. **Collect Quotes:** Vendors respond → Quote Intake tracks submissions
4. **Normalize Quotes:** Quote Intake → Click quote → Normalize → Resolve conflicts
5. **Run Comparison:** Comparison Runs → New Run → View matrix
6. **Get Approvals:** Approvals → Review/approve pending items
7. **Award:** Award → Select winner → Complete sign-offs → Finalize

---

## Metrics Summary

At a glance on any RFQ, users see:
- **Vendors:** How many invited
- **Quotes:** How many received
- **Est. Value:** Budget estimate
- **Savings:** Projected savings %

These metrics appear in:
- The list view (expandable)
- The Active Record Menu (always visible)
- The Overview dashboard (as KPIs)

---

## Design Opportunities for Figma Make

This specification should provide everything needed to generate a user-friendly, UX-driven redesign of the RFQ screen. Key opportunities for improvement include:

1. **Unified dashboard** - Consolidate Overview into a more actionable command center
2. **Better visual hierarchy** - Make the "next action" more prominent
3. **Progress indicators** - More visual progress bars and completion indicators
4. **Quick actions** - Faster access to common tasks
5. **Better data density** - Show more relevant info without scrolling
6. **Improved navigation** - Reduce clicks to common destinations
7. **Better empty states** - Guide users on what to do next
8. **Visual timeline** - More intuitive schedule visualization
