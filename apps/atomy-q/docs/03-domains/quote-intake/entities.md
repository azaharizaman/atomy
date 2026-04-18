# Quote Intake Entities

This document defines the core business entities used by the Quote Intake domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For quote-submission state semantics, see [lifecycle.md](./lifecycle.md).

## Entity 001 - Quote Submission

The quote submission is the uploaded vendor response for an RFQ.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique quote-submission identity. | ULID; must be unique. |
| `tenantId` | Tenant boundary for the submission. | Required. |
| `rfqId` | Parent RFQ reference. | Required. |
| `vendorId` | Vendor reference. | Required. |
| `vendorName` | Display name for the vendor. | Required. |
| `uploadedBy` | User who uploaded the file. | Required. |
| `filePath` | Stored file location. | Required once uploaded. |
| `fileType` | MIME type or file type description. | Optional. |
| `originalFilename` | Source file name from the upload. | Optional. |
| `status` | Processing state. | See [lifecycle.md](./lifecycle.md#state-model). |
| `submittedAt` | Upload timestamp. | Required once uploaded. |
| `confidence` | Processing confidence signal. | Numeric or normalized confidence label in the UI. |
| `lineItemsCount` | Number of parsed line items. | Optional numeric count. |
| `warningsCount` | Number of non-blocking issues. | Optional numeric count. |
| `errorsCount` | Number of blocking issues. | Optional numeric count. |
| `errorCode` | Processing error code. | Optional text. |
| `errorMessage` | Processing error description. | Optional text. |
| `processingStartedAt` | Processing start time. | Optional timestamp. |
| `processingCompletedAt` | Processing completion time. | Optional timestamp. |
| `parsedAt` | Parsing completion time. | Optional timestamp. |
| `retryCount` | Number of reprocess attempts. | Non-negative integer. |

### Relationships

- A quote submission belongs to one RFQ.
- A quote submission belongs to one tenant.
- A quote submission belongs to one uploader user.
- A quote submission has many normalization source lines.

### Business Rules

- Quote submissions must stay tenant-scoped.
- Ready submissions are the input to the final comparison workflow.
- Blocking issues should be counted from the submission's processing state, not fabricated in the client.
- Live mode should fail loudly on malformed quote-submission payloads.

### Related Docs

- Upload workflow: [Workflow 001 - Upload a Quote Submission](./workflows.md#workflow-001---upload-a-quote-submission)
- Processing workflow: [Workflow 002 - Progress a Submission Through Processing](./workflows.md#workflow-002---progress-a-submission-through-processing)
- Reparse workflow: [Workflow 003 - Reparse a Submission](./workflows.md#workflow-003---reparse-a-submission)
- Readiness workflow: [Workflow 004 - Review Readiness for Comparison](./workflows.md#workflow-004---review-readiness-for-comparison)
- State model: [State Model](./lifecycle.md#state-model)

## Entity 002 - Normalization Source Line

Normalization source lines represent the extracted source data that a quote submission contributes to normalization and comparison.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique source-line identity. | ULID; must be unique. |
| `tenantId` | Tenant boundary for the source line. | Required. |
| `quoteSubmissionId` | Parent quote submission reference. | Required. |
| `rfqLineItemId` | Linked RFQ line item when mapped. | Optional. |
| `rawData` | Extracted source payload. | JSON structure with normalized and override fields. |
| `hasBlockingIssue` | Whether the line blocks readiness. | Boolean. |
| `blockingIssueCount` | Number of blocking issues on the line. | Numeric count. |

### Relationships

- A source line belongs to one quote submission.
- A source line may belong to one RFQ line item.
- A source line can have conflicts.

### Business Rules

- Source lines feed the normalization workspace and comparison readiness gate.
- Manual overrides and resolved conflicts should survive reparse when appropriate.

### Related Docs

- Reparse workflow: [Workflow 003 - Reparse a Submission](./workflows.md#workflow-003---reparse-a-submission)
- Readiness workflow: [Workflow 004 - Review Readiness for Comparison](./workflows.md#workflow-004---review-readiness-for-comparison)

## Entity 003 - Normalization Conflict

Normalization conflicts capture unresolved or disputed source-line mappings.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique conflict identity. | ULID; must be unique. |
| `tenantId` | Tenant boundary for the conflict. | Required. |
| `sourceLineId` | Parent source-line reference. | Required. |
| `resolution` | Human or system resolution text. | Optional until resolved. |
| `metadata` | Additional conflict context. | Optional JSON object. |

### Relationships

- A conflict belongs to one source line.
- Conflicts determine whether a submission can be considered ready.

### Business Rules

- Unresolved conflicts should block final comparison.
- Reparse should not discard valid resolved context.
