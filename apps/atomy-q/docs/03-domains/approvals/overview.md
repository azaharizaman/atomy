# Approvals Overview

## Purpose

The Approvals domain covers the buyer-facing approval queue in Atomy-Q: listing approval items, drilling into a single approval, and recording approve/reject decisions against RFQ-linked approval records.

It is the RFQ-facing approval surface, not the generic operational approval engine. The generic engine lives in `orchestrators/ApprovalOperations`; this domain documents the app behavior that exposes approval work to buyers through the dashboard and RFQ workspace.

Operational flow for this domain is documented in [workflows.md](./workflows.md).
The approval status model is documented in [lifecycle.md](./lifecycle.md).
Core approval entity definitions are documented in [entities.md](./entities.md).

## Core Entities

- `Approval`: the tenant-scoped approval record tied to an RFQ and, optionally, a comparison run
- `ApprovalHistory`: the audit trail row for approval actions and related metadata
- `RFQ`: the parent requisition record that the approval belongs to
- `ComparisonRun`: the frozen comparison snapshot that can anchor an approval
- `User`: the requester, approver, and audit actor

## Inputs

- Authenticated route entry into `/approvals`
- RFQ-scoped entry into `/rfqs/[rfqId]/approvals`
- Approval filters: `rfq_id`, `status`, `type`, `priority`, `page`, and `per_page`
- Decision payloads for approval actions, including optional `reason`
- Tenant context carried from the authenticated session

## Outputs

- Paginated approval queue rows
- Approval detail payloads with RFQ and comparison-run context
- Approve or reject mutations for pending approvals
- RFQ workspace pending-count badges derived from approval data
- Empty, not-found, or error states when data is unavailable or the approval does not belong to the current tenant

## Dependencies

### Other Atomy-Q domains

- **RFQ** - approvals are linked to requisitions and the RFQ overview derives approval counts from this domain.
- **Comparison** - comparison runs can anchor an approval record and appear in the detail view.
- **Auth** - the authenticated tenant and user context determine which approvals are visible and who can act on them.
- **Dashboard shell** - the global approval queue is exposed from the workspace navigation.

### Nexus packages

- `packages/PolicyEngine`
- `orchestrators/ApprovalOperations`

### External dependencies

- Laravel
- Next.js
- React Query
- Sonner

## Current Implementation Notes

- `/approvals` and `/approvals/[id]` are active.
- `/rfqs/[rfqId]/approvals` is a tenant-scoped RFQ shortcut that reuses the same approval records.
- The approval queue is tenant-scoped in the API and returns 404 when a record does not exist for the current tenant.
- `approve` and `reject` are implemented.
- `return`, `reassign`, `snooze`, `request-evidence`, bulk actions, and history currently return stub or empty responses in the controller and should be treated as incomplete behavior in the docs and UI.
