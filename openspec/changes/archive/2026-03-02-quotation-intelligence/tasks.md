## 1. Scaffold QuotationIntelligence Orchestrator

- [x] 1.1 Create `orchestrators/QuotationIntelligence` directory structure (src, tests, docs).
- [x] 1.2 Create `composer.json` for `nexus/quotation-intelligence` with dependencies (`nexus/procurement`, `nexus/machine-learning`, `nexus/document`, `nexus/uom`, `nexus/currency`).
- [x] 1.3 Create `README.md` and `IMPLEMENTATION_SUMMARY.md`.
- [x] 1.4 Register new orchestrator in root `composer.json` and run `composer dump-autoload`.

## 2. Define Core Contracts & Value Objects

- [x] 2.1 Create `src/ValueObjects/ExtractionEvidence.php` (Immutable VO with page, bbox, text).
- [x] 2.2 Create `src/ValueObjects/QuoteSnippet.php` (Immutable VO linking evidence to a field).
- [x] 2.3 Create `src/DTOs/NormalizedQuoteLine.php` (DTO with raw + normalized values + snippets).
- [x] 2.4 Define `src/Contracts/QuoteIngestionServiceInterface.php`.
- [x] 2.5 Define `src/Contracts/SemanticMapperInterface.php`.
- [x] 2.6 Define `src/Contracts/QuoteNormalizationServiceInterface.php`.
- [x] 2.7 Define `src/Contracts/RiskAssessmentServiceInterface.php`.

## 3. Implement Ingestion & Event Pipeline

- [x] 3.1 Create `src/Events/QuoteUploaded.php` event.
- [x] 3.2 Implement `src/Services/QuoteIngestionService.php` (validate file, store, dispatch event).
- [x] 3.3 Create `src/Listeners/ProcessQuoteUploadListener.php` (handles `QuoteUploaded`).
- [x] 3.4 Write unit tests for `QuoteIngestionService`.

## 4. Implement Semantic Mapping (AI Integration)

- [x] 4.1 Implement `src/Services/AiSemanticMapper.php` (implements `SemanticMapperInterface`).
- [x] 4.2 Integrate `Nexus\MachineLearning` to call classification model (mockable).
- [x] 4.3 Implement taxonomy validation logic (ensure code exists).
- [x] 4.4 Write unit tests for `AiSemanticMapper` with mocked ML responses.

## 5. Implement Deep Normalization

- [x] 5.1 Implement `src/Services/QuoteNormalizationService.php`.
- [x] 5.2 Integrate `Nexus\Uom` for unit conversion (e.g., "Box of 12" -> "Each").
- [x] 5.3 Integrate `Nexus\Currency` for price normalization.
- [x] 5.4 Write unit tests covering complex UoM and currency scenarios.

## 6. Implement Risk & Anomaly Detection

- [x] 6.1 Implement `src/Services/RuleBasedRiskAssessmentService.php`.
- [x] 6.2 Implement outlier detection logic (price deviation > 50%).
- [x] 6.3 Implement terms deviation check (regex/keyword search for "EXW", "Net 30").
- [x] 6.4 Write unit tests for risk flagging.

## 7. Coordinator & Integration

- [x] 7.1 Implement `src/Coordinators/QuotationIntelligenceCoordinator.php` (orchestrates the full flow).
- [x] 7.2 Register services in a ServiceProvider (if using Laravel adapter) or wiring config.
- [x] 7.3 Create integration test `tests/Feature/EndToEndQuoteProcessingTest.php`.

## 8. Documentation & Final Polish

- [x] 8.1 Update `orchestrators/QuotationIntelligence/IMPLEMENTATION_SUMMARY.md` with details of all created components.
- [x] 8.2 Run `composer test` to ensure green suite.
- [x] 8.3 Verify `composer.json` dependencies are correct.
