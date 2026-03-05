# Enterprise Clarity: SaaS Quote Comparator

## Product Overview

**The Pitch:** A high-precision procurement tool that normalizes and compares complex SaaS quotations side-by-side. It transforms unstructured PDF quotes into a strict, actionable matrix of line items, terms, and total cost of ownership.

**For:** Procurement Managers and CTOs who manage six-figure software budgets and require absolute financial accuracy without the fluff.

**Device:** Desktop (optimized for 1440px+ wide screens)

**Design Direction:** "Data-Dense Utility." A rigorous reliance on information hierarchy over decoration. High-contrast typography, structural borders, and a monochromatic slate palette accented by "Trust Blue." Every pixel is dedicated to clarity.

**Inspired by:** Bloomberg Terminal (modernized), Linear (density), Stripe Dashboard (table precision).

---

## Screens

- **Dashboard:** High-level view of active comparisons, pending approvals, and budget utilization.
- **Quote Import:** Drag-and-drop ingestion zone with AI-assisted parsing verification.
- **Comparison Matrix:** The core workspace—side-by-side analysis of 2-4 vendors with granular line-item breakdown.
- **Negotiation Ledger:** A chronological log of versioned quotes and negotiated term changes per vendor.
- **Export & Report:** Print-ready PDF generation and CSV export configuration for ERP integration.

---

## Key Flows

**Flow: Analyze a New Vendor Set**

1. User is on **Dashboard** -> sees "Active Projects" list.
2. User clicks **"New Comparison"** -> modal opens for project naming.
3. User lands on **Quote Import** -> drags 3 PDF quotes (e.g., Salesforce, HubSpot, Zoho).
4. System parses -> User verifies extracted data on **Quote Import**.
5. User clicks **"Generate Matrix"** -> lands on **Comparison Matrix**.
6. User highlights "Seat Price" row -> sees variance calculation.

---

<details>
<summary>Design System</summary>

## Color Palette

- **Primary:** `#0F172A` - Main navigation, active states (Slate 900)
- **Primary Action:** `#0ea5e9` - Primary buttons, key interactive links (Sky 500)
- **Background:** `#F8FAFC` - Global application background (Slate 50)
- **Surface:** `#FFFFFF` - Cards, table rows, panels
- **Border:** `#E2E8F0` - Structural dividers (Slate 200)
- **Text Primary:** `#334155` - Headings, primary data (Slate 700)
- **Text Secondary:** `#64748B` - Metadata, labels (Slate 500)
- **Success:** `#10B981` - Positive variance (Emerald 500)
- **Warning:** `#F59E0B` - Missing data, near expiry (Amber 500)
- **Danger:** `#EF4444` - Negative variance, budget overrun (Red 500)

## Typography

**Font Family:** **Public Sans**. A strong, neutral grotesque that excels in UI interfaces and tabular figures.

- **Display:** Public Sans, 600, 20px (Tracking -0.01em)
- **Heading:** Public Sans, 600, 16px
- **Body:** Public Sans, 400, 14px (Line height 1.5)
- **Table Data:** Public Sans, 400, 13px (Monospaced numerals enabled `font-feature-settings: 'tnum'`)
- **Micro:** Public Sans, 500, 11px (Uppercase, Tracking +0.05em)

**Style Notes:**
- **Borders:** 1px solid `#E2E8F0` everywhere. No soft shadows.
- **Corners:** 4px border-radius. Tight, professional.
- **Density:** Compact padding (8px/12px). Maximizes screen real estate.
- **Atmosphere:** Clinical, financial, trustworthy.

## Design Tokens

```css
:root {
  --color-primary: #0F172A;
  --color-action: #0ea5e9;
  --color-bg: #F8FAFC;
  --color-surface: #FFFFFF;
  --color-border: #E2E8F0;
  --color-text-main: #334155;
  --font-main: 'Public Sans', system-ui, sans-serif;
  --radius-sm: 4px;
  --radius-md: 6px;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}
```

</details>

---

<details>
<summary>Screen Specifications</summary>

### Dashboard

**Purpose:** Executive summary of ongoing procurement activities and budget health.

**Layout:** Three-column grid. Left sidebar (nav), Main content area (tables), Right sidebar (notifications/approvals).

**Key Elements:**
- **Sidebar Nav:** Fixed width `240px`. Dark theme (`#0F172A`). Links: Dashboard, Projects, Vendor Directory, Settings.
- **"Active Comparisons" Table:**
    - **Headers:** Project Name, Vendors, Total Value, Status, Owner.
    - **Style:** Zebra-striped rows (`#F8FAFC` alt), 1px border bottom.
    - **Action:** Row click navigates to Matrix.
- **"Budget Utilization" Card:** Top right. Bar chart (simple CSS bars) showing Committed vs. Projected spend.

**States:**
- **Empty:** "No active comparisons. Start a new project." button centered.

**Components:**
- **Status Badge:** `20px` height, pill shape. Green bg/text for "Approved", Blue for "Draft".
- **Mini-Sparkline:** SVG line graph in table row showing price trend over 3 years.

---

### Quote Import

**Purpose:** Ingest raw PDF/Excel files and verify the structured data extraction before comparison.

**Layout:** Split screen. Left: Document Preview (PDF viewer). Right: Data Extraction Form.

**Key Elements:**
- **Upload Zone:** Top banner. Dashed border `#E2E8F0`. "Drag Quote PDF here".
- **Parsed Data Fields:**
    - **Vendor Name:** Input field.
    - **Currency:** Dropdown (USD, EUR, GBP).
    - **Term Length:** Input (Months).
- **Line Item Table (Editable):**
    - Columns: Item Name, Quantity, Unit Price, Discount %, Net Total.
    - Interaction: Users can click any cell to manually correct OCR errors.

**States:**
- **Processing:** "Parsing document..." with progress bar (`#0ea5e9`).
- **Confidence Score:** Warning icon next to fields with low OCR confidence.

**Interactions:**
- **Click PDF Text:** Highlights text in PDF and auto-fills currently selected form field.
- **Click Save Draft button** Open a modal to enter draft name, description and visibility

---

### Comparison Matrix

**Purpose:** The "Money Screen." Detailed side-by-side analysis of normalized data.

**Layout:**
- **Sticky Header:** Contains Vendor Names at the top (Column headers). Scrolls horizontally.
- **Sticky First Column:** Contains Line Item labels (Row headers). Scrolls vertically.

**Key Elements:**
- **Summary Row (Top):**
    - **Total Contract Value (TCV):** Large text (20px), bold.
    - **Annual Recurring Revenue (ARR):** Secondary text.
    - **Delta Badge:** `+12%` (Red) or `-5%` (Green) relative to average.
- **Feature Rows:**
    - Grouped by category (e.g., "Core Platform", "Add-ons", "Support").
    - **Collapsible Groups:** Chevron icon to expand/collapse sections.
- **Normalization Toggle:** Switch to view pricing as "Per User/Month" vs "Total Contract".

**Components:**
- **Best Price Highlight:** The lowest price in a row has a subtle green background (`#F0FDF4`).
- **Missing Feature:** Represented by `—` in muted gray.

**Interactions:**
- **Hover Row:** Highlights entire row across all columns for readability.
- **Click Cell:** Opens detailed note modal (e.g., specific terms for that line item).

---

### Negotiation Ledger

**Purpose:** Track the history of quotes to visualize savings achieved during negotiation.

**Layout:** Vertical timeline view.

**Key Elements:**
- **Timeline Rail:** Left side, solid line `#E2E8F0`.
- **Version Card:**
    - **Header:** "Quote v3 - Oct 24, 2023".
    - **Change Log:** List of changes vs previous version (e.g., "Discount increased 10% -> 15%").
    - **Savings:** calculated amount displayed in Green.
- **Compare Versions Button:** Select two points in time to see a diff.

**States:**
- **Final:** Topmost card marked with "Signed" badge.

---

### Export & Report

**Purpose:** Configure output for stakeholders.

**Layout:** Single column, centered, max-width 800px.

**Key Elements:**
- **Column Selector:** Checkboxes to include/exclude specific vendors from the PDF.
- **Section Selector:** Toggle visibility of "Technical Specs", "Financial Terms", "Legal Clauses".
- **Preview Pane:** Live preview of the generated PDF page structure.
- **Action Bar:** Bottom sticky. "Download PDF", "Export CSV", "Send via Email".

</details>

---

<details>
<summary>Build Guide</summary>

**Stack:** React + TypeScript + Tailwind CSS v3

**Build Order:**
1. **Design System Setup:** Define `colors`, `fonts`, and `borders` in `tailwind.config.js`. Create the `Table` component base (crucial for this app).
2. **Comparison Matrix:** This is the most complex UI with sticky headers/columns. Build this first to validate the layout engine.
3. **Quote Import:** Build the form logic and data structure for line items.
4. **Dashboard:** Assemble the navigation shell and list views.
5. **Negotiation Ledger & Export:** Secondary features.

**Tailwind Config Nuances:**
- Enable `tnum` (tabular nums) utility for all financial data.
- Use `divide-y` and `divide-slate-200` heavily for list items.
- strictly use `border-collapse` for tables.

</details>