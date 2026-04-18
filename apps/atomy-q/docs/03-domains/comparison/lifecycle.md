# Comparison Lifecycle

This file describes the comparison run state model itself. The preview/freeze/read workflows are documented in [workflows.md](./workflows.md).

## Purpose

The Comparison lifecycle defines how a comparison run moves from a preview result into a final frozen evidence set, while preserving the payloads needed for matrix rendering, approval gating, award creation, and the decision trail.

The current implementation spans the Atomy-Q API comparison controller, the quote-intake normalization state, and the award/approval surfaces that consume the frozen comparison run.

## State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `preview` | A persisted comparison preview for an RFQ. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`, `apps/atomy-q/WEB/src/hooks/use-comparison-runs.ts` |
| `final` | A persisted frozen comparison that can feed approval and award flows. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/ComparisonRunController.php`, `apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php` |

## Entry Criteria

### preview

- The RFQ exists in the current tenant.
- At least one quote submission with a source document exists.
- The comparison engine can generate a preview result.

### final

- The RFQ exists in the current tenant.
- Enough quote submissions are ready for the RFQ size.
- All quote submissions are in ready state.
- No quote submission has blocking normalization issues.
- Every ready submission has a source document.

## Transitions

### preview -> final

- Trigger: the buyer freezes the comparison.
- Current Atomy path: `ComparisonRunController::final_()`.
- Side effects: frozen snapshot persisted, decision trail updated, and the run becomes the handoff into awards and approvals.

### preview -> preview

- Trigger: the buyer regenerates a preview.
- Current Atomy path: `ComparisonRunController::preview()`.
- Side effects: a new preview run is persisted.

## Deferred Controls

- `lock`, `unlock`, and `scoring-model` are intentionally deferred in alpha.
- These routes return a `422` response with `COMPARISON_CONTROL_DEFERRED`.
- No fake mutation should be inferred from those routes.

## Dependencies

### Other Atomy-Q domains

- **Quote Intake** - comparison readiness is driven by quote submission status and blocking issues.
- **RFQ** - comparison runs are anchored to one RFQ.
- **Approvals** - finalized comparison evidence can feed the approval gate.
- **Awards** - the final comparison snapshot is the award handoff.

### Nexus packages

- `Nexus\QuotationIntelligence`
- `Nexus\Sourcing`

### External dependencies

- Laravel
