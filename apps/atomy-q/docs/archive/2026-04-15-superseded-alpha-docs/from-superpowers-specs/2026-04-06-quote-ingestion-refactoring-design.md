# Spec: Quote Ingestion AI Normalization Refactoring (2026-04-06)

## Purpose
Address unresolved PR review comments to improve type safety, transactional integrity, and repository contract compliance within the `QuoteIngestion` and `QuotationIntelligence` modules.

## Goals
1.  **Type Safety**: Replace generic `object` type hints with formal contracts in the `QuoteIngestion` orchestrator and its dependencies.
2.  **Transactional Integrity**: Ensure atomic lookups and creation for decision trail entries.
3.  **Contract Compliance**: Align the `InMemoryUomRepository` implementation with the `UomRepositoryInterface` contract (e.g., throwing specified exceptions).
4.  **Test Isolation**: Implement proper cleanup of temporary files in feature tests.

## Architecture & Components

### 1. Type Safety & Contracts
*   **`QuoteSubmissionInterface`**:
    *   `getId(): string`
    *   `getTenantId(): string`
    *   `getRfqId(): string`
    *   `getVendorName(): string`
*   **Interface Refactoring**:
    *   `QuoteSubmissionQueryInterface::find()` returns `?QuoteSubmissionInterface`.
    *   `QuoteSubmissionPersistInterface` methods type-hint `QuoteSubmissionInterface`.
    *   Split `NormalizationSourceLineRepositoryInterface` into `Query` and `Persist` interfaces.

### 2. Implementation Details

#### `QuoteIngestionOrchestrator`
*   Replace all `object` hints for submissions with `QuoteSubmissionInterface`.
*   Update property access to method calls (e.g., `$submission->getId()`).
*   Simplify `calculateAvgConfidence` logic (remove redundant ternary).
*   Add basic logging (e.g., when skipping overrides) to justify `LoggerInterface`.

#### `AtomyDecisionTrailWriter`
*   Ensure the `previousHash` read and `max('sequence')` query use `lockForUpdate()` within the transaction scope to prevent race conditions during concurrent writes.

#### `EloquentNormalizationSourceLineRepository`
*   Filter out scoped keys (`tenant_id`, `quote_submission_id`, `rfq_line_item_id`) from the data array before calling `updateOrCreate`.

#### `QuoteSubmissionController`
*   Stricter validation for `override` and `resolution` fields (using `trim((string)...) !== ''`) to correctly handle empty or whitespace-only inputs.

#### `InMemoryUomRepository`
*   Enforce `UomRepositoryInterface` contract:
    *   `saveUnit()`: Throw `DuplicateUnitCodeException` for existing codes.
    *   `saveDimension()`: Throw `DuplicateDimensionCodeException` for existing codes.
    *   `saveConversion()`: Throw `InvalidConversionRatioException` for ratios ≤ 0 or existing conversion keys.
*   Add a `reset()` method to clear internal state.
*   Add a docblock/suppress warning for the unused parameter in `getUnitsBySystem()`.

#### `QuoteIngestionIntelligenceTest`
*   Implement `tearDown()` to recursively delete the `$testStorageRoot` directory after each test execution.

## Data Flow
The `QuoteIngestionOrchestrator` orchestrates the normalization process, using the `QuoteSubmissionQueryInterface` to retrieve a typed submission and the `QuoteSubmissionPersistInterface` to update its state. Data persists via the `NormalizationSourceLinePersistInterface`, with decision trails recorded atomically by the `AtomyDecisionTrailWriter`.

## Testing & Verification
1.  **Unit Tests**: Verify that `InMemoryUomRepository` throws the correct exceptions for duplicates and invalid ratios.
2.  **Feature Tests**:
    *   Run `QuoteIngestionIntelligenceTest` to ensure all scenarios (auto-mapping, delta reparse) pass and that the temporary directory is correctly removed.
    *   Verify that decision trail entries are correctly recorded and sequential under concurrent load.
3.  **Static Analysis**: Run `phpstan` or equivalent to confirm that all type-hint changes are consistent across the codebase.
