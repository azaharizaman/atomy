# DataExchangeOperations Implementation Summary

## Contracts
- Primary orchestrator contracts live in `src/Contracts`, including `DataOnboardingCoordinatorInterface`, `DataOffboardingCoordinatorInterface`, `DataImportPortInterface`, `DataExportPortInterface`, `StoragePortInterface`, `NotificationPortInterface`, and `DataExchangeTaskStoreInterface`.

## DTOs And Data Models
- Request/result/state DTOs are in `src/DTOs`, notably `DataOnboardingRequest`, `DataOnboardingResult`, `DataOffboardingRequest`, `DataOffboardingResult`, and `DataExchangeTaskStatus`.

## Coordinators
- `src/Coordinators/DataExchangeCoordinator.php` orchestrates onboarding/offboarding entry points and task status retrieval.

## Workflows And State Progression
- `src/Workflows/OnboardingWorkflow.php` executes validation -> import -> optional cleanup -> completed/failed task persistence.
- `src/Workflows/OffboardingWorkflow.php` executes validation -> export -> store -> notify -> completed/failed task persistence.
- Offboarding is destination-aware and branches based on target destination to select export and storage behavior.
- Task state transitions are persisted through `DataExchangeTaskStatus` (`validating`, `importing`/`exporting`, `completed`, `failed`).

## DataProviders And Rules
- Data providers are in `src/DataProviders` (`OnboardingContextDataProvider`, `OffboardingContextDataProvider`, task-store helpers).
- Rules are in `src/Rules` (`OnboardingPreflightRule`, `OffboardingPreflightRule`) and enforce request invariants before port calls.

## Laravel Adapter Layer
- Laravel adapters are implemented under `adapters/Laravel/DataExchangeOperations/src`, including adapters for import/export/storage/notification/task store and `DataExchangeOperationsAdapterServiceProvider`.

## Services Facades
- Compatibility facades are in `src/Services`, including `src/Services/DataExchangeCoordinator.php`, delegating to orchestrator coordinators.

## Integration Notes And Coverage
- Unit and integration coverage for orchestrator behavior is under `tests/Unit` and `tests/Integration`, including `DataExchangeCoordinatorTest` and `DataExchangeWorkflowIntegrationTest` for workflow/task-state behavior.
