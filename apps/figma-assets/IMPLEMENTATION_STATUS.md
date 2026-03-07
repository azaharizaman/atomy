# Atomy-Q: Implementation Status & Roadmap

This document tracks the parity between the Figma designs, the actual React implementation, and the backend orchestrator capabilities.

## 🏗️ Global Design System (Tokens)
- [x] **Primary Navigation**: Dark slate theme (`#0f172a` / `#020617`).
- [x] **Header**: Slightly darkened light gray (`#f1f5f9`) / Deep black (`#010409`).
- [x] **Semantic Tokens**: Integrated `--app-nav-*` and `--app-header-*` for independent scaling.
- [ ] **Tabular Numerals**: Enforce `font-variant-numeric: tabular-nums` across all financial tables.
- [ ] **Keyboard Shortcuts**: Implement `/` for search focus and `Esc` for drawer closing.

---

## 📺 Screen Implementation Tracker

### 1. Sign In
**Status**: `COMPLETED`
- [x] Left brand panel darkened to match sidebar.
- [x] 2FA/MFA step logic included.
- [x] Security notice footer added.

### 2. Home Dashboard
**Status**: `STABLE (UI ONLY)`
- [x] KPI Strip with trend indicators.
- [x] Risk/SLA Alerts rail.
- [ ] **TODO**: Real-time websocket hook for "AI Agent Summary" updates.

### 3. RFQ List
**Status**: `STABLE`
- [x] Multi-status filtering (Draft, Open, Awarded).
- [x] Batch action toolbar (Delete/Export).
- [ ] **TODO**: Implement "Advanced Filter" popover for date ranges and categories.

### 4. Create RFQ
**Status**: `BETA`
- [x] Multi-step workflow (Stepper).
- [x] Line item grid with UoM selector.
- [ ] **TODO**: Implement "Import from Excel" logic for line items.

### 5. RFQ Detail
**Status**: `BETA`
- [x] Tabbed navigation (Overview, Vendors, Documents, Activity).
- [x] Activity timeline with actor metadata.
- [ ] **TODO**: Link "Manage Vendors" to the active recruitment flow.

### 6. Vendor Invitation Management
**Status**: `BETA`
- [x] Invitation channel selector (Email, Portal, SMS).
- [x] Reminder scheduler UI.
- [ ] **TODO**: Integration with `ConnectivityOperations` orchestrator for real-time status.

### 7. Quote Intake Inbox
**Status**: `ALIGNED WITH BACKEND`
- [x] **Average AI Confidence**: Detailed breakdown by extraction stage.
- [x] **Off-canvas Details**: Replaced modal with `max-w-xl` Sheet.
- [x] **Backend Fields**: Added UNSPSC Codes, Pricing Anomalies, and Incoterms.
- [ ] **TODO**: Implement the "Map Now" semantic search modal for unmapped items.

### 8. Quote Normalization Workspace
**Status**: `COMPLETED (UI ONLY)`
- [x] Split-pane source vs target mapping workspace.
- [x] Conflict queue + right slide-over resolution panel.
- [x] UOM/currency conversion control visualization.

### 9. Quote Comparison Matrix
**Status**: `COMPLETED (UI ONLY)`
- [x] Full quote comparison matrix and summary sections in `QuoteComparison.tsx`.
- [x] Recommendation highlights, score and variance views.
- [x] Human oversight side panel and decision-support context.

### 10-26. Blueprint Coverage Expansion
**Status**: `COMPLETED (UI ONLY)`
- [x] Scenario Simulator
- [x] Recommendation & Explainability
- [x] Explicit Approval Detail screen
- [x] Negotiation Workspace
- [x] Award Decision
- [x] PO/Contract Handoff
- [x] Vendor Profile & Performance
- [x] Integration & API Monitor
- [x] User & Access Management
- [x] Admin Settings
- [x] Notification Center
- [x] Replaced all Dashboard placeholder routes for nav-linked screens

---

## 🛠️ Technical Debt & Global Tasks

### Backend Integration (Nexus Layers)
- [ ] **Auth Layer**: Move from mock credentials to `IdentityOperations` tokens.
- [ ] **Storage Layer**: Connect file uploads to `Nexus\Storage` via `QuoteIngestionService`.
- [ ] **Intelligence Layer**: Wire up `QuotationIntelligenceCoordinator` to the "Run Comparison" buttons.

### UI/UX Refinement
- [ ] **Density Toggle**: Add a setting to switch between "Compact" (Stripe-like) and "Standard" (Linear-like) spacing.
- [ ] **Theme Persistence**: Save dark/light preference to `SettingsManagement` orchestrator.
- [ ] **Empty States**: Create "No Data" illustrations for all list views.

### Documentation
- [ ] Sync all package changes to `IMPLEMENTATION_SUMMARY.md` in respective orchestrators.
- [ ] Update `AGENTS.md` with new screen-specific prompt context.
