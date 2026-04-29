# InsightOperations Implementation Summary

## Contracts
- Orchestrator-owned contracts are in `src/Contracts`, including `ReportingPipelineCoordinatorInterface`, `ReportDataQueryPortInterface`, `ForecastPortInterface`, `ReportExportPortInterface`, `InsightStoragePortInterface`, `InsightNotificationPortInterface`, and `DashboardSnapshotPortInterface`.
- Plan 5 AI insight contracts now include `DashboardFactsPortInterface`, `ReportingFactsPortInterface`, `RiskInsightFactsPortInterface`, `GovernanceFactsPortInterface`, `AiAvailabilityPortInterface`, `InsightNarrativePortInterface`, and `AiArtifactCachePortInterface`. These keep dashboard/report/risk/governance fact loading, AI availability, provider narrative generation, and artifact caching behind Layer 2 ports.

## DTOs And Data Models
- DTOs in `src/DTOs` include `ReportingPipelineRequest`, `ReportingPipelineResult`, `DashboardSnapshotDto`, and `DashboardSnapshotResult`.
- Plan 5 AI insight DTOs include `MetricFactDto`, `DashboardFactsDto`, `ReportingFactsDto`, `RiskInsightFactsDto`, `GovernanceFactsDto`, `AiArtifactDto`, `AiArtifactProvenanceDto`, and `InsightResultDto`. AI artifacts serialize `feature_key`, `capability_group`, `available`, `status`, `payload`, `provenance`, `source_facts`, `source_facts_hash`, and `reason_codes`.

## Coordinators
- `src/Coordinators/ReportingCoordinator.php` coordinates reporting pipeline execution and dashboard snapshot capture.
- `DashboardInsightCoordinator`, `ReportingInsightCoordinator`, `RiskInsightCoordinator`, and `GovernanceNarrativeCoordinator` coordinate Plan 5 read/generate flows. Read flows load deterministic facts and cached artifacts only. Generate flows load deterministic facts, check feature-level AI availability, invoke narrative ports only when allowed, and cache artifacts by subject plus source-facts hash.
- Governance narrative coordination sanitizes provider context by hashing actor fields and excluding raw evidence IDs, finding IDs, notes, emails, phone numbers, and plain actor names before invoking AI narrative generation.

## Workflows And State Progression
- `src/Workflows/ReportingPipelineWorkflow.php` applies rules, queries report data, optionally runs forecast, exports, stores, notifies, and returns metadata.
- `src/Workflows/DashboardSnapshotWorkflow.php` validates IDs, captures dashboard snapshot payload, stores artifact, and returns snapshot metadata.

## DataProviders And Rules
- `src/DataProviders/PipelineContextDataProvider.php` builds normalized report context payloads.
- `src/Rules/ReportingPipelineRule.php` and `src/Rules/DashboardSnapshotRule.php` validate request inputs before workflow execution.

## Laravel Adapter Layer
- Laravel adapters are under `adapters/Laravel/InsightOperations/src`, including `ReportDataQueryPortAdapter`, `ForecastPortAdapter`, `ReportExportPortAdapter`, `InsightStoragePortAdapter`, `InsightNotificationPortAdapter`, `DashboardSnapshotPortAdapter`, and provider wiring in `InsightOperationsAdapterServiceProvider`.

## Services Facades
- `src/Services/ReportingCoordinator.php` is the compatibility facade that delegates to `Coordinators/ReportingCoordinator`.
- `src/Services/FactHasher.php` recursively sorts associative array keys before JSON encoding and hashing so cache keys are stable across equivalent fact payload ordering.

## Integration Notes And Coverage
- Tests in `tests/Unit` and `tests/Integration` cover pipeline/snapshot flows, including `ReportingCoordinatorTest` and `ReportingPipelineIntegrationTest`.
- Added package-local PHPUnit wiring via `phpunit.xml.dist` and a Composer `test` script that run from the package directory while bootstrapping the monorepo root autoloader, so `InsightOperations` verification matches the actual workspace layout.
- The package now follows the monorepo-root PHPUnit pattern explicitly: `phpunit.xml.dist` boots `../../vendor/autoload.php`, and package verification delegates to the root PHPUnit binary instead of carrying a redundant package-local PHPUnit dependency.
- Added focused unit coverage for dashboard, reporting, RFQ risk, and governance narrative coordinators. Verification command: `cd orchestrators/InsightOperations && composer test`. Result on 2026-04-30: PASS, 15 tests and 90 assertions.
