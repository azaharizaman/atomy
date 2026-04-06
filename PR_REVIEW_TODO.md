# PR Review Resolution: Quote Ingestion & AI Normalization (#345)

## Actionable Comments

### AtomyDecisionTrailWriter.php
- [x] **Task 1: Add DB transactions/locking**
  - Verify lines 48-110 and 167-192.
  - Wrap comparison run resolution and sequence/hash computation in a transaction.

### InMemoryUomRepository.php
- [x] **Task 2: Enforce interface contract & fix state management**
  - Update `saveUnit`, `saveDimension`, `saveConversion` to throw `DuplicateUnitCodeException`, `DuplicateDimensionCodeException`, `InvalidConversionRatioException`.
  - Address singleton mutable state (reset or non-singleton).
  - Add `@SuppressWarnings` for unused `$systemCode` in `getUnitsBySystem`.

### EloquentNormalizationSourceLineRepository.php
- [x] **Task 3: Filter keys in updateOrCreate**
  - Verify lines 30-37.
  - Remove `tenant_id`, `quote_submission_id`, `rfq_line_item_id` from `$data` before calling `updateOrCreate`.

### QuoteSubmissionController.php
- [x] **Task 4: Fix blank/whitespace detection**
  - Verify lines 258-271.
  - Update `$hasOverride`, `$hasResolvedConflict`, and `$hasUnresolvedConflict` to handle blank strings.

### Interfaces & Type Safety
- [x] **Task 5: Split NormalizationSourceLineRepositoryInterface**
  - Split into `Query` and `Persist` interfaces.
  - Update implementations and DI sites.
- [x] **Task 6: Type-safe QuoteSubmission handles**
  - Define `QuoteSubmissionReadInterface` or DTO.
  - Update `QuoteSubmissionQueryInterface::find` to return it.
  - Update `QuoteSubmissionPersistInterface` methods to accept it.
  - Update `QuoteIngestionOrchestrator` to use it instead of generic `object`.

### QuoteIngestionOrchestrator.php (Nits)
- [x] **Task 7: Clean up Orchestrator**
  - Remove unused `$logger` from constructor. (Verified as used, but cleaned up other parts).
  - Remove redundant ternary guard in average computation (lines 145-152).

### Testing
- [x] **Task 8: Add test cleanup**
  - Implement `tearDown()` in `QuoteIngestionIntelligenceTest` to remove `$testStorageRoot`.

## Verification & Finalization
- [x] Run all tests.
- [x] Lint check.
- [x] Commit and push to GitHub.
