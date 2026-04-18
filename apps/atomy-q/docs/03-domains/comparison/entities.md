# Comparison Entities

This document defines the core business entities used by the Comparison domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For comparison run state semantics, see [lifecycle.md](./lifecycle.md).

## Entity 001 - ComparisonRun

The comparison run is the persisted result of a preview or final comparison for a specific RFQ.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique comparison-run identity. | ULID; must be unique. |
| `tenantId` | Tenant boundary for the run. | Required. |
| `rfqId` | Parent RFQ reference. | Required. |
| `name` | Human-readable run label. | Required; current values include `Preview comparison` and `Final comparison`. |
| `description` | Optional run description. | Optional text. |
| `idempotencyKey` | Optional dedupe key for replay protection. | Optional. |
| `isPreview` | Whether the run is a preview rather than a final freeze. | Boolean. |
| `createdBy` | User who initiated the run. | Required. |
| `requestPayload` | Input payload captured for traceability. | JSON object. |
| `matrixPayload` | Comparison matrix output. | JSON object or array structure used by the matrix view. |
| `scoringPayload` | Comparison scoring output. | JSON object. |
| `approvalPayload` | Approval-related comparison output. | JSON object. |
| `responsePayload` | Persisted response payload, including the frozen snapshot on final runs. | JSON object. |
| `readinessPayload` | Readiness result payload. | JSON object. |
| `status` | Run state. | See [lifecycle.md](./lifecycle.md). |
| `version` | Incremental version for the run. | Integer. |
| `expiresAt` | Optional expiry time for preview runs. | Optional timestamp. |
| `discardedAt` | Optional discard timestamp. | Optional timestamp. |
| `discardedBy` | Optional user who discarded the run. | Optional. |

### Relationships

- A comparison run belongs to one RFQ.
- A comparison run belongs to one tenant.
- A comparison run belongs to one creator user.
- A comparison run has many approvals.
- A comparison run has many awards.

### Business Rules

- Comparison runs must be tenant-scoped.
- Preview runs are persisted instead of being returned as synthetic tokens.
- Final runs must store the frozen snapshot.
- Final comparison should only succeed when the underlying quote submissions are ready and free of blocking issues.
- A comparison run is the evidence boundary for downstream approval and award flows.

### Related Docs

- Preview workflow: [Workflow 001 - Create a Preview Comparison Run](./workflows.md#workflow-001---create-a-preview-comparison-run)
- Final freeze workflow: [Workflow 002 - Freeze the Final Comparison](./workflows.md#workflow-002---freeze-the-final-comparison)
- Read workflow: [Workflow 003 - Inspect Matrix or Readiness](./workflows.md#workflow-003---inspect-matrix-or-readiness)
- Deferred controls: [Workflow 004 - Deferred Control Surfaces](./workflows.md#workflow-004---deferred-control-surfaces)
- State model: [State Model](./lifecycle.md#state-model)

## Entity 002 - ComparisonSnapshot

The comparison snapshot is the frozen output stored on a final comparison run.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `rfqId` | Parent RFQ reference. | Required. |
| `normalizedLines` | Frozen normalized line-item set. | Required array. |
| `vendors` | Frozen vendor snapshot. | Required array. |
| `documentsProcessed` | Number of source documents frozen into the snapshot. | Numeric count. |

### Relationships

- The snapshot belongs to one final comparison run.
- The award workflow consumes this snapshot.
- The approval detail view may surface it indirectly through comparison-run linkage.

### Business Rules

- The snapshot must not change after the final run is recorded.
- Frozen snapshot data is the business evidence for award and approval steps.

### Related Docs

- Final freeze workflow: [Workflow 002 - Freeze the Final Comparison](./workflows.md#workflow-002---freeze-the-final-comparison)
- Award domain: `../awards/overview.md`

## Entity 003 - DecisionTrailEntry

Decision-trail entries are the audit rows written when a comparison snapshot is frozen.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique trail entry identity. | ULID; must be unique. |
| `tenantId` | Tenant boundary for the entry. | Required. |
| `comparisonRunId` | Parent comparison run reference. | Required. |
| `rfqId` | Parent RFQ reference. | Required. |
| `sequence` | Position in the hash chain. | Positive integer. |
| `eventType` | Business event type. | Required. |
| `payloadHash` | Hash of the frozen payload. | Required. |
| `previousHash` | Previous entry hash in the chain. | Required. |
| `entryHash` | Hash of the full audit entry. | Required. |
| `occurredAt` | When the event was recorded. | Timestamp. |

### Relationships

- A decision-trail entry belongs to one comparison run.
- A decision-trail entry belongs to one RFQ.

### Business Rules

- Decision-trail rows are append-only.
- The hash chain is part of the audit evidence.
- A frozen comparison should always create a corresponding audit trace.
