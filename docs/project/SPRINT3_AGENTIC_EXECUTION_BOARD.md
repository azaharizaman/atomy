# Sprint 3 Agentic Execution Board (QuotationIntelligence)

**Sprint Window:** March 18-24, 2026  
**Goal:** Commercial terms extraction and peer-aware term risk scoring.

## Ticket List

1. `QI-S3-T01` Commercial terms extraction contract and implementation  
- Owner: Architect + Developer  
- Status: `Completed`  
- Scope:
  - Add `CommercialTermsExtractorInterface`.
  - Implement `RegexCommercialTermsExtractor` for Incoterm/payment/lead-time/warranty extraction.

2. `QI-S3-T02` Wire normalized commercial terms into quote pipeline  
- Owner: Developer + QA  
- Status: `Completed`  
- Scope:
  - Integrate extractor into `QuotationIntelligenceCoordinator`.
  - Persist normalized terms under `metadata.commercial_terms`.

3. `QI-S3-T03` Term-aware rule-based risk checks  
- Owner: Developer + QA  
- Status: `Completed`  
- Scope:
  - Evaluate extracted commercial terms in `RuleBasedRiskAssessmentService`.
  - Add risk checks for EXW, long payment terms, long lead times, short warranties.

4. `QI-S3-T04` Peer-aware commercial term deviation in batch comparison  
- Owner: Architect + Developer  
- Status: `Completed`  
- Scope:
  - Extend `BatchQuoteComparisonCoordinator` with peer term deviation detection.
  - Emit term deviation risks alongside peer pricing anomaly risks.

5. `QI-S3-T05` Regression and documentation sync  
- Owner: QA + Maintenance  
- Status: `Completed`  
- Scope:
  - Add extractor, risk, and peer-term regression tests.
  - Sync implementation summary.

## Sprint Exit Criteria

1. All normalized lines include machine-parsed commercial terms.
2. Risk engine evaluates commercial terms from normalized metadata.
3. Batch peer comparison flags materially different terms across vendors.
4. Full QuotationIntelligence suite passes.

