# Quote Intake Lifecycle

This file describes the quote-submission status model itself. The upload and reparse workflows are documented in [workflows.md](./workflows.md).

## Purpose

The Quote Intake lifecycle defines how a vendor quote moves from upload into extraction, normalization, review, and readiness for comparison.

The current implementation spans the Atomy-Q API quote-submission controller, the quote ingestion job, the normalization workspace, and the comparison readiness gate.

## State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `uploaded` | The file has been stored and processing has begun or will begin. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`, `apps/atomy-q/API/app/Adapters/QuoteIngestion/EloquentQuoteSubmissionPersist.php` |
| `extracting` | The file is being parsed into structured content. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`, `apps/atomy-q/API/app/Jobs/ProcessQuoteSubmissionJob.php` |
| `extracted` | Parsing has completed and line extraction data exists. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php` |
| `normalizing` | The extracted data is being normalized. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php` |
| `needs_review` | Manual review is required before the submission can be considered ready. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`, normalization UI |
| `ready` | The submission is ready for comparison. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`, comparison freeze workflow |
| `failed` | Processing failed and needs a retry or manual fix. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`, `apps/atomy-q/API/app/Jobs/ProcessQuoteSubmissionJob.php` |
| `accepted` | Legacy alias accepted by the API and normalized to `ready`. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`, `apps/atomy-q/API/app/Http/Requests/QuoteSubmissionStatusRequest.php` |

## Entry Criteria

### uploaded

- A file upload has been accepted and persisted.
- The quote submission row has been created for the current tenant and RFQ.

### extracting

- The ingestion job has started.
- The submission is in processing and not yet ready for comparison.

### extracted

- The quote file has been parsed into structured content.

### normalizing

- The extracted content is being mapped onto the RFQ's line items.

### needs_review

- Manual review is required before the submission can become ready.

### ready

- The submission has no blocking issues.
- The submission can be used by comparison and approval workflows.

### failed

- The ingestion pipeline could not complete successfully.

### accepted

- A legacy client or older path submitted `accepted`.
- The controller normalizes it to `ready`.

## Transitions

### uploaded -> extracting

- Trigger: the upload controller dispatches processing.
- Current Atomy path: `QuoteSubmissionController::upload()` and `ProcessQuoteSubmissionJob`.

### extracting -> extracted

- Trigger: parsing completes.
- Current Atomy path: the ingestion pipeline.

### extracted -> normalizing

- Trigger: structured line items are ready for mapping.

### normalizing -> needs_review

- Trigger: the parser or normalization logic finds blockers.

### normalizing -> ready

- Trigger: normalization completes without blocking issues.

### needs_review -> normalizing

- Trigger: the buyer resolves issues and requests another normalization pass.

### needs_review -> ready

- Trigger: review has been resolved and the submission is clear.

### any -> failed

- Trigger: ingestion or processing fails.

### failed -> uploaded / extracting

- Trigger: the buyer requests a reparse.
- Current Atomy path: `QuoteSubmissionController::reparse()`.

### uploaded -> ready

- Trigger: a legacy request uses `accepted`.
- Current Atomy path: normalized to `ready`.

## Transition Rules

- Unsupported state transitions must fail with validation errors.
- Live mode must not invent a ready state when required fields or statuses are missing.
- Reparse should reset the processing state and retry the ingestion flow.
- The quote submission must remain tenant-scoped through every transition.

## Dependencies

### Other Atomy-Q domains

- **RFQ** - quote intake always hangs off one requisition.
- **Normalization** - source lines and conflicts are the handoff into the normalization workspace.
- **Comparison** - only ready submissions feed the final freeze.

### Nexus packages

- `Nexus\QuoteIngestion`
- `Nexus\Sourcing`
- `Nexus\Document`
- `Nexus\Notifier`

### External dependencies

- Laravel
