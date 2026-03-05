# Sprint 2 Agentic Execution Board (QuotationIntelligence)

**Sprint Window:** March 11-17, 2026  
**Goal:** Cross-vendor line alignment and lock-date normalization context.

## Ticket List

1. `QI-S2-T01` Lock-date normalization context in processing pipeline  
- Owner: Architect + Developer  
- Status: `Completed`  
- Scope:
  - Introduce typed normalization context VO.
  - Parse and validate `fx_lock_date` from document metadata.
  - Pass lock date to currency normalization.

2. `QI-S2-T02` Cross-vendor comparison matrix service  
- Owner: Developer + QA  
- Status: `Completed`  
- Scope:
  - Add contract and service for comparison matrix generation.
  - Cluster by RFQ line, fallback to taxonomy.
  - Emit min/max/avg statistics and lowest-price recommendation.

3. `QI-S2-T03` Regression tests for context/date handling and matrix behavior  
- Owner: QA  
- Status: `Completed`  
- Scope:
  - Add invalid `fx_lock_date` exception test.
  - Add matrix clustering and recommendation tests.

4. `QI-S2-T04` Wire matrix service into external orchestration workflow  
- Owner: Architect + Developer  
- Status: `Completed`  
- Scope:
  - Integrate matrix builder into higher-level batch comparison workflow across vendor quotes.
  - Ensure risk engine receives peer line context per cluster.

## Sprint Exit Criteria

1. Quote normalization supports deterministic FX lock dates.
2. Cross-vendor matrix service exists with test coverage.
3. Invalid normalization context fails fast with domain exceptions.
4. Batch-level matrix orchestration is scheduled as next integration step.
