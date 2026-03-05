# Sprint 4 Agentic Execution Board (QuotationIntelligence)

**Sprint Window:** March 25-31, 2026  
**Goal:** MCDA + LCC vendor scoring integrated into batch quote comparison.

## Ticket List

1. `QI-S4-T01` MCDA scoring contract and implementation  
- Owner: Architect + Developer  
- Status: `Completed`  
- Scope:
  - Add `VendorScoringServiceInterface`.
  - Implement `WeightedVendorScoringService` with weighted dimensions.

2. `QI-S4-T02` Lifecycle cost integration into price dimension  
- Owner: Developer + QA  
- Status: `Completed`  
- Scope:
  - Compute lifecycle-cost-adjusted total using per-line multiplier metadata.
  - Use LCC-based normalization for price scoring.

3. `QI-S4-T03` Batch coordinator scoring integration  
- Owner: Architect + Developer  
- Status: `Completed`  
- Scope:
  - Extend `BatchQuoteComparisonCoordinator` to emit `scoring` output.
  - Keep existing peer risk logic unchanged while reusing merged risk set in scoring.

4. `QI-S4-T04` Regression tests for scoring and integration  
- Owner: QA  
- Status: `Completed`  
- Scope:
  - Add unit tests for scoring/ranking behavior.
  - Update batch coordinator tests for new scoring dependency/payload.

5. `QI-S4-T05` Documentation sync  
- Owner: Maintenance  
- Status: `Completed`  
- Scope:
  - Update implementation summary for Sprint 4 deliverables.

## Sprint Exit Criteria

1. Batch comparison output includes deterministic weighted ranking.
2. Price dimension is lifecycle-cost-aware.
3. Risk dimension consumes merged base + peer risks.
4. Full QuotationIntelligence suite passes.

