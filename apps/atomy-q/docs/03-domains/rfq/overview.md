# RFQ Overview

## Purpose

The RFQ domain covers the buyer-side requisition record in Atomy-Q: creating and editing RFQs, managing line items and vendor invitations, tracking schedule fields, and driving the downstream quote-intake, comparison, approval, and award workflow.

It is the core workspace record behind the RFQ route tree in the dashboard and the summary surface that feeds the workspace overview and activity feed.

Operational behavior for this domain is documented in [workflows.md](./workflows.md).
The RFQ state model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Core Entities

- `Rfq`: the tenant-scoped requisition record
- `RfqLineItem`: the buyer-defined scope line attached to an RFQ
- `VendorInvitation`: the vendor outreach record tied to the RFQ
- `QuoteSubmission`: the submitted quote record that feeds downstream normalization and comparison
- `ComparisonRun`: the frozen comparison artifact generated from RFQ-ready quotes
- `Approval`: the approval record derived from RFQ comparison or risk gates

## Inputs

- Authenticated route entry into `/rfqs` and `/rfqs/[rfqId]/*`
- RFQ create/update payloads
- RFQ draft/save payloads
- Status transition and bulk-action commands
- Tenant context and optional project access checks

## Outputs

- Paginated RFQ list rows
- RFQ detail and overview payloads
- RFQ activity feed rows
- Draft, duplicate, transition, and bulk-close/cancel mutations
- Downstream counts for vendors, quotes, normalization progress, comparison runs, and approvals

## Dependencies

### Other Atomy-Q domains

- **Projects** - RFQs may link to a project and the workspace enforces project ACL visibility when one is present.
- **Quote Intake** - RFQ records own the quote submission surface and readiness counts.
- **Comparison** - finalized comparison runs are derived from RFQ-ready quotes.
- **Approvals** - approval counts and approval queue links are derived from RFQ data.
- **Awards** - awards consume the frozen comparison and RFQ context.
- **Auth** - tenant and user context determine who can read and mutate RFQs.

### Nexus packages

- `packages/Sourcing`
- `packages/Idempotency`
- `orchestrators/SourcingOperations`

### External dependencies

- Laravel
- Next.js
- React Query

## Current Implementation Notes

- `/rfqs`, `/rfqs/new`, `/rfqs/[rfqId]/overview`, `/details`, `/line-items`, `/vendors`, `/quote-intake`, `/comparison-runs`, `/award`, `/approvals`, and `/decision-trail` are active workspace routes.
- RFQ reads and writes are tenant-scoped.
- Project-linked RFQs require project ACL visibility checks; when the user cannot see the linked project, the API returns 404 rather than leaking the project relationship.
- Draft save, duplicate, status transition, activity, and summary endpoints are implemented.
- Line items, vendor invitations, quote submissions, comparison runs, approvals, and awards are separate child workflows that reuse the RFQ as their parent record.
