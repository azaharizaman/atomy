# UI-Design Alignment Gap Analysis (QuotationComparisonSaaS Research)

## Baseline review outcome

The original `UI-Design` app had strong coverage for:
- Create RFQ, intake, normalization, comparison, scoring, scenario simulation, negotiation, risk, reports, and user access.

It was under-aligned for the blueprint and research controls in:
- `RFQ List (3)`, `RFQ Detail (5)`, `Vendor Invitation (6)`.
- `Recommendation & Explainability (12)`.
- `Approval Queue (14)` and `Approval Detail (15)`.
- `Award Decision (17)` and `PO/Contract Handoff (18)`.
- `Decision Trail (19)`, `Vendor Performance (20)`, `Evidence Vault (21)`.
- `Integration Monitor (23)` and `Admin Settings (25)`.

## Engineering discrepancies found

1. **Missing governance flow surfaces**
   - No dedicated approval queue/detail, decision trail, or evidence vault surfaces.
   - Impact: weak visibility for maker-checker and audit progression.

2. **Control fields from research not fully represented**
   - `evaluation_method`, `committee_mode`, `technical_gate` not explicit in scoring UI.
   - `due_diligence_status`, `fraud_signal_count`, `fraud_signal_severity`, `exception_id`, `expiry_date`, `approver_role` not represented together in risk/governance UI.

3. **Post-award operational continuity was incomplete**
   - No clear handoff and integration monitor views.
   - Impact: weak traceability from award to downstream systems.

## Remediation implemented

- Added all missing screen families listed above and wired them into router + navigation.
- Added explicit control-field UI in `ScoringModelBuilder` and `RiskComplianceReview`.
- Added recommendation, approval, trail, evidence, handoff, integration, and admin policy screens with realistic mock values.
- Reused the new `@atomy-q/design-system` components and mock datasets to keep consistency.

## Result

The updated `UI-Design` app now includes complete blueprint-level screen coverage and explicit representation of research-critical governance fields while maintaining a cohesive visual scheme.
