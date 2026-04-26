# Implementation Plan: Consolidate API Mutation Boundary

## Task 1: Consolidate API Mutation Boundary

### Context
Currently, `QuoteSubmissionController` handles `storeSourceLine` and `updateSourceLine`. We need to route these mutations to `NormalizationController@override` (or similar audited flow) to ensure all manual normalization writes are tracked by the `NormalizationOverrideService` audit trail and readiness service.

### Plan
1. **Audit `QuoteSubmissionController`** (`storeSourceLine`, `updateSourceLine`):
    - Identify logic that can be offloaded.
    - `storeSourceLine` currently creates `NormalizationSourceLine` and triggers `refreshManualReadiness`.
    - `updateSourceLine` currently updates `NormalizationSourceLine` and triggers `refreshManualReadiness`.
    - Both interact with `DecisionTrailRecorder`.
2. **Standardize on `NormalizationController@override`**:
    - The `NormalizationOverrideService` handles the audit trail and readiness check in a transaction.
    - We should consolidate manual source line creation/update into this audit-enabled service.
3. **Execution**:
    - Implement `NormalizationController@override` (or a similar auditing method if `override` is insufficient) for creation/updates.
    - Redirect routes in `api.php`.
    - Update `QuoteSubmissionController` to use these services.

### Validation
- Verify `NormalizationOverrideService` atomicity.
- Ensure all normalization writes use the new consolidated path.
- Check regression tests in `NormalizationReviewWorkflowTest` and `QuoteSubmissionWorkflowTest`.
