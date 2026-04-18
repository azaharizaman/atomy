# RFQ Entities

This document defines the core business entities used by the RFQ domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For RFQ state semantics, see [lifecycle.md](./lifecycle.md).

## Entity 001 - RFQ

The RFQ is the parent requisition record that holds the buyer's scope, schedule, and lifecycle status.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique RFQ identity used across the workspace. | ULID; must be unique. |
| `tenantId` | Tenant boundary for the RFQ. | Required; all access must remain tenant-scoped. |
| `rfqNumber` | Human-readable RFQ reference. | Required; unique within the tenant and prefix-based. |
| `title` | RFQ title shown in the workspace. | Required; trimmed; must not be empty. |
| `description` | Optional scope description. | Optional text. |
| `category` | Buyer classification for the requisition. | Optional short label. |
| `department` | Owning department. | Optional short label. |
| `status` | RFQ lifecycle state. | See [lifecycle.md](./lifecycle.md#state-model). |
| `ownerId` | User who owns the RFQ. | Required for creation. |
| `projectId` | Optional linked project. | Optional; when present, project ACL visibility must allow the current user to see it. |
| `estimatedValue` | Estimated commercial value. | Optional numeric amount. |
| `savingsPercentage` | Expected savings target. | Optional numeric percentage between `0` and `100`. |
| `submissionDeadline` | Quote submission deadline. | Required for draft creation and persistence. |
| `closingDate` | Optional close date for the RFQ. | When present, must not be earlier than the submission deadline. |
| `expectedAwardAt` | Expected award date. | Optional date/time. |
| `technicalReviewDueAt` | Technical review deadline. | Optional date/time. |
| `financialReviewDueAt` | Financial review deadline. | Optional date/time. |
| `paymentTerms` | Commercial payment terms. | Optional short label. |
| `evaluationMethod` | Evaluation method descriptor. | Optional short label. |

### Relationships

- An RFQ belongs to one tenant.
- An RFQ belongs to one owner user.
- An RFQ may belong to one project.
- An RFQ has many line items.
- An RFQ has many vendor invitations.
- An RFQ has many quote submissions.
- An RFQ has many comparison runs.
- An RFQ has many approvals.

### Business Rules

- RFQs must stay tenant-scoped at every read and write path.
- Draft RFQs can be edited through the draft workflow.
- An RFQ with a project link must respect project ACL visibility.
- Closing dates cannot precede submission deadlines.
- RFQ duplication creates a new draft record rather than mutating the source record.
- RFQ summary counts should be derived from child records, not from fabricated client-side values.

### Related Docs

- Create and edit workflow: [Workflow 001 - Create a New RFQ Draft](./workflows.md#workflow-001---create-a-new-rfq-draft)
- Transition workflow: [Workflow 002 - Edit, Save, and Transition an RFQ](./workflows.md#workflow-002---edit-save-and-transition-an-rfq)
- Duplicate workflow: [Workflow 003 - Duplicate an RFQ](./workflows.md#workflow-003---duplicate-an-rfq)
- Summary workflow: [Workflow 004 - Read RFQ Workspace Summary](./workflows.md#workflow-004---read-rfq-workspace-summary)
- State model: [State Model](./lifecycle.md#state-model)

## Entity 002 - RFQ Line Item

RFQ line items define the scope the buyer wants vendors to quote against.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique line-item identity. | ULID; must be unique. |
| `tenantId` | Tenant boundary for the line item. | Required; must match the parent RFQ tenant. |
| `rfqId` | Parent RFQ reference. | Required. |
| `description` | Scope description for the item. | Required text. |
| `quantity` | Requested quantity. | Positive numeric value. |
| `uom` | Unit of measure. | Required short code. |
| `unitPrice` | Target or reference unit price. | Optional numeric amount. |
| `currency` | Currency code. | Optional when price is present. |
| `specifications` | Additional technical details. | Optional text. |
| `sortOrder` | Display ordering. | Integer. |

### Relationships

- A line item belongs to one RFQ.
- A line item can be duplicated with the RFQ duplication workflow.

### Business Rules

- Line items are the primary scope input for downstream vendor quotes and comparison.
- Duplicate RFQs should preserve line-item intent through the child copy flow.
- Line-item values should be normalized before comparison and award logic consumes them.

### Related Docs

- Duplicate workflow: [Workflow 003 - Duplicate an RFQ](./workflows.md#workflow-003---duplicate-an-rfq)
- Comparison workflow: [Workflow 004 - Read RFQ Workspace Summary](./workflows.md#workflow-004---read-rfq-workspace-summary)

## Entity 003 - Vendor Invitation

Vendor invitations represent outreach from the buyer to a vendor for a specific RFQ.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique invitation identity. | ULID; must be unique. |
| `tenantId` | Tenant boundary for the invitation. | Required. |
| `rfqId` | Parent RFQ reference. | Required. |
| `vendorId` | Vendor being invited. | Required. |
| `vendorName` | Display name for the vendor. | Required when vendor lookup is unavailable. |
| `invitedAt` | When the invitation was sent. | Optional timestamp. |
| `status` | Invitation state. | Domain-specific invitation state used by the RFQ and vendor surfaces. |

### Relationships

- A vendor invitation belongs to one RFQ.
- Vendor invitations feed the activity feed and vendor counts on the RFQ overview.

### Business Rules

- Invitations are tenant-scoped.
- The RFQ overview may surface invitations as activity entries.
- Vendor invitation state influences downstream quote submission and comparison readiness.
