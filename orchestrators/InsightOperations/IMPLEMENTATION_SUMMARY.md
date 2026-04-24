# InsightOperations Implementation Summary

## Contracts
- Orchestrator-owned contracts are in `src/Contracts`, including `ReportingPipelineCoordinatorInterface`, `ReportDataQueryPortInterface`, `ForecastPortInterface`, `ReportExportPortInterface`, `InsightStoragePortInterface`, `InsightNotificationPortInterface`, and `DashboardSnapshotPortInterface`.

## DTOs And Data Models
- DTOs in `src/DTOs` include `ReportingPipelineRequest`, `ReportingPipelineResult`, `DashboardSnapshotDto`, and `DashboardSnapshotResult`.

## Coordinators
- `src/Coordinators/ReportingCoordinator.php` coordinates reporting pipeline execution and dashboard snapshot capture.

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

## Integration Notes And Coverage
- Tests in `tests/Unit` and `tests/Integration` cover pipeline/snapshot flows, including `ReportingCoordinatorTest` and `ReportingPipelineIntegrationTest`.
- Added package-local PHPUnit wiring via `phpunit.xml.dist` and a Composer `test` script that run from the package directory while bootstrapping the monorepo root autoloader, so `InsightOperations` verification matches the actual workspace layout.
- The package now follows the monorepo-root PHPUnit pattern explicitly: `phpunit.xml.dist` boots `../../vendor/autoload.php`, and package verification delegates to the root PHPUnit binary instead of carrying a redundant package-local PHPUnit dependency.
