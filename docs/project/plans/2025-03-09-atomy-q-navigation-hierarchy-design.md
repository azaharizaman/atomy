# Atomy-Q Navigation Hierarchy Design

**Date:** 2025-03-09  
**Status:** Design  
**Scope:** apps/atomy-q UI-Design — main navigation vs contextual/record-linked screens

## Problem

The current sidebar places almost every screen in the main navigation. Many screens are **contextual**: they belong to a specific record or flow (e.g. Comparison Matrix for an RFQ’s comparison run, Normalization for a quote). Exposing all of them in the main nav:

- Overwhelms users with too many top-level choices
- Hides the real information hierarchy (RFQ → runs → matrix; Quote → normalization)
- Duplicates entry points that should be record-linked (e.g. “View Normalization” from a quote)

## Principles

1. **Main nav = landing surfaces only** — list/dashboard/inbox/queue and a small set of cross-cutting areas (Governance, Admin, Account).
2. **Record-linked screens = no main nav** — reached by clicking a record, tab, or action (e.g. “Comparison runs” from RFQ Detail → run row → Comparison Matrix).
3. **Breadcrumbs and back** — contextual screens show clear breadcrumbs (e.g. RFQ “X” → Comparison runs → Run “Y” → Matrix) and a logical back path.
4. **Quick actions** — Header actions (e.g. “Run Comparison”) can still exist but resolve in context (e.g. “which RFQ?”) or deep-link when context is clear.

---

## Main navigation (sidebar)

Only these items appear as primary sidebar links. Count is kept small (~6–8 primary + grouped secondary).

### Primary

| Label        | Route / concept      | Description                                      |
|-------------|----------------------|--------------------------------------------------|
| **Dashboard** | `/` or `/dashboard`   | Home; KPIs, tasks, recent runs, alerts.          |
| **RFQs**      | `/rfqs`              | RFQ List only. Create RFQ is an action, not nav.|
| **Quote Intake** | `/quote-intake`   | Inbox of submissions; triage and assign.         |
| **Approvals**  | `/approvals`        | Approval Queue only.                             |
| **Reports**    | `/reports`          | Reports & Analytics.                             |

### Secondary (collapsed or grouped)

- **Collaboration:** Notifications, Tasks, Mentions (optional; can stay as single “Notifications” or be grouped).
- **Governance:** Decision Trail, Evidence Vault. *(Risk & Compliance is removed from here; see below.)*
- **Administration:** Users & Access, Scoring Policies, Templates, Integrations, Feature Flags (or “Admin” with sub-pages).
- **Account:** Profile, Preferences, Help/Docs.

### Removed from main nav

- **Comparison Matrix** — reached only via RFQ → Comparison runs → run.
- **Risk & Compliance** — reached only from RFQ (or Approval) context.
- **Scoring Model Builder** — admin/config; under Administration or from Comparison Matrix “Select scoring model” when needed.
- **Scenario Simulator** — from Comparison Matrix.
- **Recommendation & Explainability** — from Comparison Matrix / run.
- **Normalization Workspace** — from Quote Intake or Quote view (record-linked).
- **Decision Trail** — remains under Governance but can also be opened in context (e.g. from Approval Detail, RFQ).

---

## Contextual entry points (where each screen is reached)

### RFQ-scoped (from RFQ List → RFQ Detail)

| Screen                         | Entry point |
|--------------------------------|------------|
| **RFQ Detail**                  | RFQ List: click row. |
| **Create RFQ**                 | Dashboard or RFQ List: “New RFQ” / “Create RFQ”. |
| **Vendor Invitation Management** | RFQ Detail: “Vendors” tab (or inline section). |
| **RFQ Comparison Runs** (new)  | RFQ Detail: “Comparison runs” tab. Lists runs for this RFQ; each row opens Comparison Matrix for that run. |
| **Comparison Matrix**          | RFQ Detail → Comparison runs tab → click a run. Optional: header “Run Comparison” when an RFQ is in context (e.g. from RFQ Detail) resolves to “create run” then matrix. |
| **Scenario Simulator**         | Comparison Matrix: toolbar or tab “Scenarios”. |
| **Recommendation & Explainability** | Comparison Matrix (after a run) or from a run row in Comparison runs list: “View recommendation”. |
| **Risk & Compliance Review**   | RFQ Detail: “Risk & Compliance” tab or link. (Stems from RFQ, not global nav.) |

### Quote-scoped (from Quote Intake or quote record)

| Screen                     | Entry point |
|----------------------------|------------|
| **Quote Normalization Workspace** | Quote Intake: “Accept & Normalize” for a submission; or from a Quote view (e.g. in RFQ context or Intake detail) “View Normalization”. Always tied to a single quote. |

### Approval-scoped

| Screen            | Entry point |
|-------------------|------------|
| **Approval Queue** | Sidebar: Approvals. |
| **Approval Detail** | Approval Queue: click item. |

### Post-approval (workflow-driven)

| Screen                  | Entry point |
|-------------------------|------------|
| **Negotiation Workspace** | Approval Detail: after “Approve” when workflow says negotiate. |
| **Award Decision**      | Approval Detail: after “Approve” when no further gates; or after Negotiation. |
| **PO/Contract Handoff** | Award Decision: after finalize. |

### Governance / audit (main nav or contextual)

| Screen           | Entry point |
|------------------|------------|
| **Decision Trail** | Sidebar Governance; or from Approval Detail / RFQ / run “View decision trail”. |
| **Evidence Vault**  | Sidebar Governance; or from Approval Detail / RFQ “Documents” / evidence links. |

### Admin / config

| Screen                  | Entry point |
|-------------------------|------------|
| **Scoring Model Builder** | Administration; or from Comparison Matrix “Select scoring model” (link to admin or modal). |
| **Scoring Policies**    | Administration. |
| **RFQ Templates**       | Administration. |
| **User & Access Management** | Administration. |
| **Integrations Configuration** | Administration. |
| **Integration & API Monitor** | Administration (or under Integrations). |
| **Admin Settings**      | Administration. |

### Other

| Screen                     | Entry point |
|----------------------------|------------|
| **Vendor Profile & Performance** | Any vendor link (RFQ, Comparison, Risk, etc.): open vendor profile. |
| **Notification Center**    | Sidebar Collaboration or header bell. |
| **Sign In**                 | Unauthenticated route. |

---

## RFQ Detail: “Comparison runs” tab

To support “Comparison Matrix stems from RFQ Comparison Runs”:

- **RFQ Detail** gets a **“Comparison runs”** tab (in addition to e.g. Vendors, Documents, Activity).
- Tab shows a **list of comparison runs** for this RFQ (run ID, date, status, who ran it, link to recommendation).
- **Actions:** “New run” (opens flow that lands on Comparison Matrix for a new run), “Open” on a row → Comparison Matrix for that run.
- Breadcrumb on Comparison Matrix: **RFQs > [RFQ name] > Comparison runs > [Run label]**.

This keeps the hierarchy explicit: RFQ → runs → matrix, and avoids a top-level “Comparison Matrix” in the sidebar.

---

## Summary: main nav only

- **Dashboard**
- **RFQs** (list)
- **Quote Intake**
- **Approvals** (queue)
- **Reports**
- **Collaboration** (Notifications, Tasks, Mentions)
- **Governance** (Decision Trail, Evidence Vault)
- **Administration** (Users & Access, Scoring Policies, Templates, Integrations, Feature Flags)
- **Account** (Profile, Preferences, Help/Docs)

All other screens are reached by record or flow: from a list row, a tab, or an action (e.g. “Accept & Normalize”, “View Normalization”, “Risk & Compliance” from RFQ, “Scenarios” from Comparison Matrix).

---

## Implementation notes

1. **Routing:** Contextual screens use routes that encode context, e.g. `/rfqs/:rfqId/comparison-runs`, `/rfqs/:rfqId/comparison-runs/:runId/matrix`, `/rfqs/:rfqId/risk-compliance`, `/quotes/:quoteId/normalization`, so breadcrumbs and back navigation are consistent.
2. **Header “Run Comparison”:** If implemented, either open a modal “Select RFQ” then go to that RFQ’s Comparison runs (or create run and open matrix), or deep-link to RFQ Detail Comparison runs tab when context is known.
3. **Blueprint update:** In `QUOTE_COMPARISON_FRONTEND_BLUEPRINT.md`, replace the current Sidebar section with this hierarchy and add a short “Navigation hierarchy” section that references this design and the contextual entry table.
