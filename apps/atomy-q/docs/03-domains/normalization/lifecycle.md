# Normalization Lifecycle

This file describes the normalization state model and operational states itself. The operational mapping, override, and conflict-resolution flows are documented in [workflows.md](./workflows.md).

## Purpose

Normalization does not currently have a single durable aggregate lifecycle in the same way a project or award does.

Instead, the domain is modeled as a set of operational states:

- source-line mapping state
- conflict resolution state
- quote-submission readiness state
- RFQ lock/unlock signal state

The controller currently treats lock/unlock as active responses, but it does not persist a formal lock record.

## Source-Line State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `unmapped` | The source line has not yet been linked to an RFQ line. | `NormalizationController::sourceLines()`, `NormalizationController::updateMapping()` |
| `mapped` | The source line is linked to an RFQ line. | `NormalizationController::normalizedItems()`, `NormalizationController::updateMapping()` |
| `overridden` | Manual override data is present on the source line. | `NormalizationController::override()` |

### Source-Line Transition Notes

- Mapping and override changes do not create a separate persisted lifecycle object.
- Reverting an override moves the line back to the previous non-overridden state.

## Conflict State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `open` | The conflict has no resolution. | `NormalizationController::conflicts()` |
| `resolved` | The conflict has a resolution payload and resolver metadata. | `NormalizationController::resolveConflict()` |

### Conflict Transition Notes

- A resolved conflict remains part of the history and should be re-evaluated for readiness after resolution.

## Submission Readiness State Model

The readiness service drives the quote-submission state machine used by normalization.

| State | Meaning in code | Where it appears |
|---|---|---|
| `uploaded` | File received and queued for extraction. | `QuoteSubmissionController::upload()`, `QuoteSubmissionController::reparse()` |
| `extracting` | Extraction is in progress. | `QuoteSubmissionController::reparse()` |
| `extracted` | Raw extraction completed. | `QuoteSubmissionController::updateStatus()` |
| `normalizing` | Mapping and normalization are underway. | `QuoteSubmissionController::updateStatus()` |
| `needs_review` | Human review is required before comparison can proceed. | `QuoteSubmissionController::updateStatus()`, readiness service |
| `ready` | Submission is ready for comparison. | `QuoteSubmissionController::updateStatus()`, readiness service |
| `failed` | The submission hit an unrecoverable or retryable failure. | `QuoteSubmissionController::updateStatus()` |

### Readiness Notes

- `accepted` is normalized to `ready` as a legacy alias.
- Mappings, overrides, and conflict resolution can move a submission toward `ready` or back toward `needs_review`.
- Reparse resets the submission to `uploaded` and clears transient processing data before re-running ingestion.

## Lock Signal Rules

- Lock and unlock are workflow signals, not durable storage states.
- The controller returns `locked = true` or `locked = false` for the current request cycle only.

## Related Docs

- Normalization workflows: [workflows.md](./workflows.md)
- Normalization entities: [entities.md](./entities.md)
