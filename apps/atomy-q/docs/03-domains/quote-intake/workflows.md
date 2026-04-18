# Quote Intake Workflows

This file covers the operational business flow for the Quote Intake domain. It does not describe the quote-submission state model itself; that belongs in [lifecycle.md](./lifecycle.md).

## Workflow Access

- The RFQ-scoped quote intake surface lives at `/rfqs/[rfqId]/quote-intake`.
- The quote detail surface lives at `/rfqs/[rfqId]/quote-intake/[quoteId]`.
- The normalization surface lives at `/rfqs/[rfqId]/quote-intake/[quoteId]/normalize`.
- The API is tenant-scoped and only accepts RFQs in the current tenant.

## Workflow 001 - Upload a Quote Submission

### Trigger

A buyer uploads a vendor quote file for a specific RFQ.

### Steps

1. The WEB upload form validates the RFQ id, vendor id, and file payload.
2. The client sends the upload request to the API.
3. The API resolves the tenant and RFQ, stores the file, and creates the quote-submission row.
4. The controller sets the submission to `uploaded` and dispatches processing.
5. The background job or sync queue processes the uploaded document into extracted line data.
6. The WEB UI refreshes the quote list and detail surfaces.

### Outputs

- Quote submission created
- File stored on disk
- Processing job dispatched
- Initial quote status visible in the workspace

### Failure Handling

- If the RFQ is not found in the current tenant, the API returns `404`.
- If the file is missing, validation fails before persistence.
- If processing fails later, the submission can move into `failed` and be retried through the reparse flow.

### Domains Involved

- Quote Intake
- RFQ
- Auth

## Workflow 002 - Progress a Submission Through Processing

### Trigger

The ingestion job advances a quote after upload.

### Steps

1. The processing job starts or resumes the ingestion pipeline.
2. The submission moves through extraction, normalization, and review states.
3. The controller or pipeline updates the submission status.
4. The workspace reads the current status, confidence, blocking issue count, and timestamps.

### Outputs

- Updated submission status
- Readable progress indicators for the buyer
- Normalization source-line data for the next workflow

### Failure Handling

- If the queue driver is synchronous, processing runs inline.
- If processing fails, the status can move to `failed`.
- If the API receives an unsupported status transition, it returns `422`.

### Domains Involved

- Quote Intake
- Normalization
- RFQ

## Workflow 003 - Reparse a Submission

### Trigger

A buyer requests a fresh parse after changing source data or resolving a conflict.

### Steps

1. The API resolves the submission in the current tenant.
2. If the submission is already processing, the API returns a `202` response with the current state.
3. The controller resets processing fields and clears old error metadata.
4. The controller removes unmapped or unresolved source lines that should be recalculated.
5. The processing job is dispatched again.

### Outputs

- Submission reset to the upload/reprocess path
- Cleared error metadata
- Reprocessing job dispatched

### Failure Handling

- If the submission cannot be found, the API returns `404`.
- If the submission is already being processed, the API returns a non-fatal `202` response.
- If the tenant does not own the submission, no cross-tenant access is exposed.

### Domains Involved

- Quote Intake
- Normalization
- RFQ

## Workflow 004 - Review Readiness for Comparison

### Trigger

A buyer opens the quote-intake detail or normalization screen to prepare for comparison.

### Steps

1. The WEB quote-submissions hook loads the tenant-scoped submission list.
2. The detail view shows file metadata, confidence, and blocking issue counts.
3. The normalization screen shows line-level status and blockers.
4. The comparison workflow uses only submissions in `ready` state.

### Outputs

- Ready vs needs-review signal
- Comparison readiness signal
- Normalization handoff into the comparison workflow

### Failure Handling

- If the live payload is malformed, the hook fails loudly.
- If required fields are missing, the live-mode client rejects the payload instead of fabricating values.

### Domains Involved

- Quote Intake
- Normalization
- Comparison
- RFQ
