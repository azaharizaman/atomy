# Approvals Entities

This document defines the core business entities used by the Approvals domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For approval status semantics, see [lifecycle.md](./lifecycle.md).

## Entity 001 - Approval

The approval is the tenant-scoped work item that asks a buyer to approve or reject a requisition-related action.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique approval identity used in the queue and detail screen. | ULID; must be unique. |
| `tenantId` | Tenant boundary for the approval. | Required; all reads and writes must stay inside this tenant. |
| `rfqId` | Parent RFQ for the approval. | Required; links the approval back to the requisition workspace. |
| `comparisonRunId` | Optional frozen comparison run that the approval references. | Optional; when present, the approve action expects a finalized run. |
| `type` | Business category for the approval. | Required; current seeded values include `quote_approval` and `comparison_approval`. |
| `status` | Current decision state. | See [lifecycle.md](./lifecycle.md#state-model). |
| `requestedBy` | User who requested the approval. | Required; used to show the assignee/requester line in the queue. |
| `requestedAt` | When the approval request was created. | Optional in storage, but shown in the detail view when available. |
| `amount` | Monetary value associated with the approval. | Optional; decimal value. |
| `currency` | Currency code for the approval amount. | Optional; three-letter code. |
| `level` | Priority or escalation tier. | Integer; higher values surface as higher priority in the queue. |
| `notes` | Free-form approval context or rationale. | Optional text. |
| `approvedAt` | Decision timestamp for approve or reject actions. | Optional; set when a terminal decision is recorded. |
| `approvedBy` | User who took the terminal decision. | Optional; populated for approve or reject. |
| `snoozedUntil` | Deferred review time. | Optional; currently exposed by the model but not fully implemented in the controller. |
| `createdAt` | Record creation time. | Timestamps use the application timezone conventions. |
| `updatedAt` | Last update time. | Timestamp. |

### Relationships

- An approval belongs to one RFQ.
- An approval may belong to one comparison run.
- An approval belongs to one tenant.
- An approval has one requester and optionally one approving user.
- An approval has many approval history entries.

### Business Rules

- An approval must never leak across tenant boundaries.
- The queue view should be derived from stored approval rows, not from synthetic client-side placeholders.
- A pending approval may be approved or rejected.
- A terminal decision should record the approver metadata and a decision timestamp.
- A linked comparison run must be finalized before the approve path succeeds.
- An approval that is not linked to a finalized and ready comparison snapshot should fail closed.
- The RFQ workspace may surface approval counts, but the detailed decision still belongs to the approval record.

### Related Docs

- Queue workflow: [Workflow 001 - Review a Pending Approval From the Global Queue](./workflows.md#workflow-001---review-a-pending-approval-from-the-global-queue)
- RFQ workflow: [Workflow 002 - Review Approvals From an RFQ Workspace](./workflows.md#workflow-002---review-approvals-from-an-rfq-workspace)
- State model: [State Model](./lifecycle.md#state-model)

## Entity 002 - ApprovalHistory

Approval history is the audit trail for actions taken against an approval record.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique audit entry identity. | ULID; must be unique. |
| `tenantId` | Tenant boundary for the audit entry. | Required; must match the parent approval tenant. |
| `approvalId` | Parent approval reference. | Required; cascades when the approval is deleted. |
| `action` | Recorded action name. | Required; seed data uses `requested` and `approved`, while the model supports other action names as approval history grows. |
| `actorId` | User who performed the action. | Optional in storage; may be null for system-generated events. |
| `reason` | Optional decision rationale. | Text or null. |
| `metadata` | Additional audit context. | Optional JSON object for event-specific details. |
| `createdAt` | When the audit event was recorded. | Timestamp. |

### Relationships

- An approval history entry belongs to one approval.
- An approval history entry belongs to one actor user when the action was taken by a person.

### Business Rules

- History rows should be tenant-scoped and traceable to the approval they describe.
- Approval history is append-only in business terms.
- Approval history exists to preserve decision context even when the queue row changes status.
- If the audit trail is incomplete, the approval record can still exist, but the decision story is weaker.

### Related Docs

- Queue workflow: [Workflow 001 - Review a Pending Approval From the Global Queue](./workflows.md#workflow-001---review-a-pending-approval-from-the-global-queue)
- Upstream creation: [Workflow 003 - Upstream Approval Creation](./workflows.md#workflow-003---upstream-approval-creation)
- State model: [State Model](./lifecycle.md#state-model)
