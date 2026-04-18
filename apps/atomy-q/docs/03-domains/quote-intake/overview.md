# Quote Intake Overview

## Purpose

The Quote Intake domain covers how vendors' quote files enter Atomy-Q: upload, persistence, background processing, status progression, and the handoff into normalization and comparison readiness.

It is the ingress path for vendor quote documents and the operational surface that decides whether a submission is ready, needs review, or has failed.

Operational flow for this domain is documented in [workflows.md](./workflows.md).
The quote-submission status model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Core Entities

- `QuoteSubmission`: the tenant-scoped uploaded quote record
- `NormalizationSourceLine`: the extracted line item row used by the normalization workflow
- `NormalizationConflict`: the conflict record used when a source line cannot be cleanly resolved
- `Rfq`: the parent requisition that quote intake belongs to

## Inputs

- Authenticated route entry into `/rfqs/[rfqId]/quote-intake`
- Quote upload payloads
- Quote status transition requests
- Reparse and assignment commands
- Tenant context and RFQ ownership

## Outputs

- Persisted quote-submission rows
- Processed or reparsed quote statuses
- Readiness signals for comparison and RFQ overview
- Normalization source-line data for the normalization workspace
- Empty or stubbed responses for actions that are still incomplete

## Dependencies

### Other Atomy-Q domains

- **RFQ** - quote intake is always attached to an RFQ.
- **Comparison** - comparison readiness consumes ready submissions.
- **Normalization** - extracted source lines and conflicts feed the normalization workspace.
- **Awards** - awarded RFQs depend on ready comparison inputs from this domain.
- **Auth** - tenant and user context determine which quote submissions are visible and mutable.

### Nexus packages

- `packages/Sourcing`
- `packages/Document`
- `packages/Notifier`
- `orchestrators/QuoteIngestion`

### External dependencies

- Laravel
- Next.js
- React Query

## Current Implementation Notes

- `/quote-submissions`, `/quote-submissions/upload`, `/quote-submissions/{id}`, `/quote-submissions/{id}/status`, `/quote-submissions/{id}/replace`, `/quote-submissions/{id}/reparse`, and `/quote-submissions/{id}/assign` are the main API surfaces.
- Upload and status-update behavior are active.
- Reparse is active and restarts the quote ingestion processing flow.
- Replace and assign currently return stubbed responses in the controller.
- The WEB quote-intake pages use live data in live mode and seed data only in explicit mock mode.
