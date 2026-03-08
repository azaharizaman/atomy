# Research Alignment: Operational Procurement Processes vs Atomy-Q Screen Blueprint

## Objective
Map real-world quotation, bid, and tender operations across organization sizes into the planned Quote Comparison SaaS user experience, then validate the reverse mapping (each major screen has an operational purpose). This document is the updated research baseline (A) aligned to `docs/project/QUOTE_COMPARISON_FRONTEND_SCREEN_BLUEPRINT.md` (B).

## Scope
- Quote comparison and tender evaluation workflows.
- Due diligence, anti-fraud, negotiation controls, and governance approvals.
- Mapping for private sector and public-sector-like tender controls.

## Operating Model by Organization Size

### Small Organizations (ad hoc process becoming standardized)
- Typical pain: spreadsheet comparison, unclear approval ownership, low audit depth.
- SaaS requirement: fast setup templates, clear tasking, baseline controls.
- Priority blueprint coverage: 3 RFQ List, 4 Create RFQ, 7 Intake, 9 Matrix, 14-15 Approvals, 22 Reports.

### Mid-Market Organizations (multi-team, policy-driven)
- Typical pain: inconsistent scoring criteria, delayed approvals, hidden quote terms.
- SaaS requirement: policy-based scoring, risk escalation, negotiation tracking.
- Priority blueprint coverage: 8 Normalization, 10 Scoring Model, 11 Scenario Simulator, 13 Risk Review, 16 Negotiation, 19 Audit Ledger.

### Enterprise and Regulated/Public-like Organizations
- Typical pain: strict segregation of duties, defensible tender decisions, anti-collusion monitoring, protest/debrief requirements.
- SaaS requirement: immutable evidence, explainability, governance checkpoints, exception management.
- Priority blueprint coverage: 12 Recommendation Explainability, 13 Risk & Compliance, 15 Approval Detail, 19 Decision Trail, 21 Evidence Vault, 24 Access Management, 25 Admin Settings.

## End-to-End Process to Screen Mapping (Process -> UX)

1) Intake and sourcing setup
- Business process: define requirement, lot structure, timelines, evaluation method, invited suppliers.
- Screens: 4 Create RFQ, 5 RFQ Detail, 6 Vendor Invitation Management.
- Coverage status: Covered.

2) Bid submission and extraction
- Business process: receive files, validate integrity and completeness, parse structured and unstructured content.
- Screens: 7 Quote Intake Inbox, 21 Documents & Evidence Vault.
- Coverage status: Covered.

3) Normalization and comparability controls
- Business process: map taxonomy, UoM, currency, and terms for true apples-to-apples comparison.
- Screens: 8 Normalization Workspace, 9 Quote Comparison Matrix.
- Coverage status: Covered.

4) Commercial and technical evaluation
- Business process: weighted scoring, scenario testing, recommendation rationale.
- Screens: 10 Scoring Model Builder, 11 Scenario Simulator, 12 Recommendation & Explainability.
- Coverage status: Covered.

5) Due diligence and compliance checks
- Business process: sanctions screening, adverse media, conflict checks, policy compliance.
- Screens: 13 Risk & Compliance Review, 20 Vendor Profile & Performance, 21 Evidence Vault.
- Coverage status: Partially covered (see shortfalls).

6) Governance approvals and decision traceability
- Business process: maker-checker approvals, delegated authority, reasons, immutable trace.
- Screens: 14 Approval Queue, 15 Approval Detail, 19 Decision Trail, 24 User & Access.
- Coverage status: Covered with control refinements.

7) Negotiation and counter-offer cycles
- Business process: controlled rounds, concession tracking, final offer capture.
- Screens: 16 Negotiation Workspace, 17 Award Decision.
- Coverage status: Covered.

8) Award, handoff, and post-award visibility
- Business process: award sign-off, ERP handoff, KPI and compliance reporting.
- Screens: 17 Award Decision, 18 PO/Contract Handoff, 22 Reports, 23 Integration Monitor.
- Coverage status: Covered.

## Reverse Mapping (UX -> Process Accountability)

Each major screen family in (B) has a required process owner and control objective in (A):
- RFQ and intake screens (3-8): owned by Buyer or Sourcing Analyst; objective is data completeness and comparability.
- Evaluation screens (9-12): owned by Category Manager or Evaluation Committee; objective is transparent, policy-compliant selection.
- Governance screens (13-15, 19, 21, 24, 25): owned by Approver, Compliance, Audit, and Admin; objective is controlled decisions and evidence integrity.
- Execution screens (16-18): owned by Buyer and Contract/PO operations; objective is negotiated value capture and downstream execution.

This ensures bidirectional fit: process steps map to screens, and screens map to accountable operations.

## Research Shortfalls Identified and Updated Requirements

The previous research over-emphasized normalization and under-specified governance operations. The following shortfalls are now explicitly added to (A):

1) Pre-award due diligence depth (shortfall)
- Needed: explicit checks for beneficial ownership, sanctions re-check at award time, conflict-of-interest declarations.
- Blueprint fit: implement as required checklists/evidence blocks in 13, 15, and 20.

2) Fraud and collusion prevention (shortfall)
- Needed: detection signals for bid rotation, identical pricing patterns, suspicious metadata overlap, unusual last-minute revisions.
- Blueprint fit: surface alerts in 13 Risk Review, route to 14/15 approvals, preserve proof in 19 and 21.

3) Tender governance patterns (shortfall)
- Needed: support two-envelope-style evaluation (technical pass/fail before commercial opening), opening-time locks, and committee log.
- Blueprint fit: represent via policy flags and workflow gating in 25 Admin Settings, 10 Scoring Model constraints, and 14-15 decision flow.

4) Exception and waiver governance (shortfall)
- Needed: formal policy exception register with mandatory rationale, approver level, expiry, and compensating controls.
- Blueprint fit: handled via 13 exception request, 15 mandatory reason capture, and 19 immutable log.

5) Supplier debrief and challenge readiness (shortfall)
- Needed: defensible award narrative and evidence packet for debrief, audit, or protest response.
- Blueprint fit: 12 explainability narrative + 21 evidence bundle + 22 reporting export.

## Updated Functional Control Requirements for Mockup Validation

To keep active mockups aligned with real operations, validate that screen designs can represent the following control fields:
- `evaluation_method` (lowest cost, weighted score, best value).
- `committee_mode` (single approver, sequential, parallel, quorum).
- `technical_gate` (pass/fail gate before price comparison).
- `due_diligence_status` (pending, pass, conditional pass, fail).
- `fraud_signal_count` and `fraud_signal_severity`.
- `exception_id` with `expiry_date` and `approver_role`.
- `award_rationale_summary` and evidence bundle link.

If any mockup cannot represent these fields, it is a design shortfall to be corrected in the screen blueprint backlog.

## Technical Strategy for Atomy (Nexus) - Confirmed
- **Backend Architecture**: Enforce strict tenant-aware scoping across all repositories and services to prevent cross-tenant data leakage.
- **CQRS Pattern**: Maintain clean separation between read and write operations via `Query` and `Persist` interface segregation.
- **Transactional Integrity**: Utilize atomic database transactions for multi-step procurement operations (e.g., PO creation and requisition conversion).
- **Hardened Contracts**: Standardize on ULID-based identifiers and interface-first service definitions.
- **Advanced Orchestration**: Continue with `QuotationIntelligence` orchestrator for normalization, scoring orchestration, and evidence traceability.
- **Traceability**: Keep evidence-first design: every adjustment and recommendation must link to source quote text/doc page.
- **Governance**: Preserve strict audit posture via immutable decision events, specialized exception classes, and robust unit test coverage (>95%).

## Appendix: Screen Reference Quick List
The following screens are referenced by number throughout this document. For complete technical and UI specifications, see [QUOTE_COMPARISON_FRONTEND_SCREEN_BLUEPRINT.md](../QUOTE_COMPARISON_FRONTEND_SCREEN_BLUEPRINT.md).

- **3 RFQ List**: Overview of all active and historical Request for Quotations.
- **4 Create RFQ**: Guided workflow for defining new procurement requirements.
- **5 RFQ Detail**: Comprehensive view of RFQ status, lots, and timelines.
- **6 Vendor Invitation**: Management of supplier participation and contact status.
- **7 Quote Intake**: Centralized inbox for receiving and extracting bid data.
- **8 Normalization**: Workspace for mapping units, currencies, and taxonomy.
- **9 Quote Matrix**: Side-by-side commercial and technical comparison view.
- **10 Scoring Model**: Builder for weighted evaluation criteria and formulas.
- **11 Scenario Simulator**: What-if tool for testing different award logic impacts.
- **12 Recommendation**: Auto-generated rationale and explainability narrative.
- **13 Risk Review**: Compliance dashboard for sanctions, fraud signals, and exceptions.
- **14 Approval Queue**: Triage view for authorized governance reviewers.
- **15 Approval Detail**: Deep-dive view for specific award decision sign-offs.
- **16 Negotiation**: Controlled workspace for counter-offers and concessions.
- **17 Award Decision**: Final selection capture and formal award notification.
- **18 PO/Contract Handoff**: Integration bridge to ERP execution modules.
- **19 Decision Trail**: Immutable audit ledger of all governance actions.
- **20 Vendor Performance**: Historical profile and reliability metrics.
- **21 Evidence Vault**: Central repository for original bid docs and proof.
- **22 Reports**: Exportable analytics and debriefing documentation.
- **23 Integration Monitor**: Status dashboard for cross-system data flows.
- **24 Access Management**: Role-based permissions and user authority settings.
- **25 Admin Settings**: Global policy flags, workflow gating, and opening locks.

## Source References (Research Basis)
- ISO 20400 sustainable procurement principles and life cycle costing guidance.
- Multi-criteria decision analysis practice for bid evaluation.
- Enterprise procurement auditability practices (decision reasons, immutable logs, evidence bundles).
- Market observations from incumbent and AI-native sourcing platforms.
