# Comparison Overview

## Purpose

The Comparison domain covers the buyer-facing comparison workflow in Atomy-Q: previewing comparison runs, freezing a final comparison snapshot, inspecting matrix and readiness data, and handing that frozen evidence into awards and the decision trail.

It is the point where ready quote submissions become a stable comparison artifact for the rest of the procurement workflow.

Operational flow for this domain is documented in [workflows.md](./workflows.md).
The comparison run state model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Core Entities

- `ComparisonRun`: the persisted comparison run for a tenant and RFQ
- `ComparisonSnapshot`: the frozen snapshot stored on final runs
- `DecisionTrailEntry`: the hash-chained audit entry written when a final snapshot is frozen
- `QuoteSubmission`: the ready quote records that comparison consumes
- `Approval`: the approval record that can anchor a finalized comparison

## Inputs

- Authenticated route entry into `/comparison-runs`
- RFQ-linked compare, preview, final, matrix, and readiness requests
- Ready quote submissions and source documents
- Tenant context and RFQ ownership

## Outputs

- Preview comparison run records
- Final comparison run records with frozen snapshots
- Matrix and readiness payloads
- Decision-trail entries for frozen comparison evidence
- Deferred `lock`, `unlock`, and scoring-model responses during alpha

## Dependencies

### Other Atomy-Q domains

- **RFQ** - comparison runs are always created for an RFQ.
- **Quote Intake** - comparison uses only quote submissions that are ready and backed by source documents.
- **Approvals** - final comparison evidence can anchor an approval record.
- **Awards** - the final comparison snapshot is the handoff into award creation and signoff.
- **Auth** - tenant and user context determine which run can be viewed or created.

### Nexus packages

- `packages/Sourcing`
- `orchestrators/QuotationIntelligence`

### External dependencies

- Laravel
- Next.js
- React Query

## Current Implementation Notes

- `/comparison-runs`, `/comparison-runs/preview`, `/comparison-runs/final`, `/comparison-runs/{id}`, `/comparison-runs/{id}/matrix`, and `/comparison-runs/{id}/readiness` are active.
- `lock`, `unlock`, and `scoring-model` controls return deferred `422` responses in alpha.
- Final comparison requires all quote submissions to be ready, blocking issues to be clear, and source documents to be present.
- Preview comparison is still persisted as a comparison-run record rather than a synthetic token.
