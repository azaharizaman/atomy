## 1. Atomic Package: Nexus\SustainabilityData (Layer 1)

- [x] 1.1 Scaffold `packages/SustainabilityData` directory structure.
- [x] 1.2 Define `src/Contracts/SustainabilityEventInterface.php` (timestamp, source, value, unit).
- [x] 1.3 Implement `src/ValueObjects/SourceMetadata.php` (IoT, Manual, Utility).
- [x] 1.4 Implement `src/Services/EventSampler.php` (Aggregation logic for high-frequency IoT).
- [x] 1.5 Register package in monorepo and run `composer dump-autoload`.

## 2. Atomic Package: Nexus\ESGRegulatory (Layer 1)

- [x] 2.1 Scaffold `packages/ESGRegulatory` directory structure.
- [x] 2.2 Define `src/Contracts/RegulatoryRegistryInterface.php`.
- [x] 2.3 Implement `src/Services/FrameworkMapper.php` (MetricID -> FrameworkTag registry).
- [x] 2.4 Seed basic mapping sets for `CSRD:2025` and `NSRF:v1.0`.
- [x] 2.5 Register package in monorepo.

## 3. Layer 2 Orchestrator: ESGOperations

- [x] 3.1 Scaffold `orchestrators/ESGOperations` directory structure.
- [x] 3.2 Define `src/Contracts/ESGOperationsCoordinatorInterface.php`.
- [x] 3.3 Implement `src/Coordinators/ESGOperationsCoordinator.php`.
- [x] 3.4 Create `src/Listeners/SustainabilityEventProcessor.php` (moves data from Lakehouse to ESG package).

## 4. AI & Unstructured Ingestion (Layer 2)

- [x] 4.1 Define `src/Contracts/ESGExtractionServiceInterface.php`.
- [x] 4.2 Implement `src/Services/LlmEsgExtractor.php` (integrates with `Nexus\Document` and `Nexus\MachineLearning`).
- [x] 4.3 Create `src/Events/ExtractionFailed.php` for low-confidence HITL triggers.
- [x] 4.4 Implement `src/ValueObjects/CarbonPriceSimulation.php` in `Nexus\ESG`.

## 5. Scenario Planning & AI Analytics (Layer 2)

- [x] 5.1 Implement `src/Services/ScenarioPlanningService.php` (Forecasting logic).
- [x] 5.2 Integrate `Nexus\MachineLearning` forecasting models.
- [x] 5.3 Write unit tests for simulation math and confidence intervals.

## 6. Verification & Documentation

- [x] 6.1 Update `IMPLEMENTATION_SUMMARY.md` across all 3 new modules.
- [x] 6.2 Run `composer test` for the full ESG stack.
- [x] 6.3 Verify zero-framework dependency compliance in Layer 1 packages.
