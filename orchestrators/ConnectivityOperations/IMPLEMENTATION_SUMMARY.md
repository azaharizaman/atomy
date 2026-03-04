# ConnectivityOperations Implementation Summary

## Contracts
- Contracts in `src/Contracts` include `ConnectivityOperationsInterface`, `ProviderCallPortInterface`, `ProviderCatalogPortInterface`, `FeatureFlagPortInterface`, `ProviderHealthStoreInterface`, `SecretRotationPortInterface`, and `ConnectivityTelemetryPortInterface`.

## DTOs And Data Models
- DTOs and workflow request models are in `src/DTOs`, including `ProviderCallRequest`.
- Health snapshots are persisted through `ProviderHealthStoreInterface` using normalized status arrays.

## Coordinators
- `src/Coordinators/IntegrationGateway.php` is the orchestrator coordinator for provider calls, secret rotation, and integration health checks.

## Workflows And State Progression
- `src/Workflows/ProviderCallWorkflow.php` validates calls, enforces feature flags, executes provider requests, records health snapshots, and emits telemetry.
- `src/Workflows/SecretRotationWorkflow.php` executes provider secret rotation and emits non-blocking telemetry.
- `src/Workflows/IntegrationHealthWorkflow.php` runs catalog-driven health probes and stores per-provider health state.

## DataProviders And Rules
- `src/DataProviders/ProviderHealthDataProvider.php` and `src/DataProviders/InMemoryProviderHealthStore.php` provide status aggregation/storage helpers.
- `src/Rules/ProviderCallRule.php` and `src/Rules/ProviderFeatureFlagRule.php` enforce preconditions before provider execution.

## Laravel Adapter Layer
- Laravel adapters are under `adapters/Laravel/ConnectivityOperations/src`, including provider-call/catalog/feature-flag/telemetry/secret-rotation/health-store adapters and wiring in `ConnectivityOperationsAdapterServiceProvider`.

## Services Facades
- The package is coordinator-first (`IntegrationGateway`) and consumed directly via orchestrator contracts.

## Integration Notes And Coverage
- Unit/integration tests under `tests/Unit` and `tests/Integration` cover provider call health tracking, secret rotation behavior, and health-check workflow outcomes.
