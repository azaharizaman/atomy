# Package Test Readiness (Adapters Dependency Inventory)

Scope: Nexus packages referenced by Laravel adapters, grouped by layer (L1/L2/L3).
CI coverage values below are from running each package's own PHPUnit config after a local `composer install`.
`Tests Present` indicates whether a `tests/` directory exists with test files; it does not confirm tests are executed in CI.

| Package | Layer | Path | CI Coverage (actual) | CI Notes | Tests Present |
| --- | --- | --- | --- | --- | --- |
| `nexus/audit-logger` | L1 | `packages/AuditLogger` | N/A | no phpunit config | yes |
| `nexus/connector` | L1 | `packages/Connector` | N/A | no tests directory | no |
| `nexus/crypto` | L1 | `packages/Crypto` | 91.27% | phpunit --coverage-text (phpunit.xml) | yes |
| `nexus/export` | L1 | `packages/Export` | N/A | no tests directory | no |
| `nexus/feature-flags` | L1 | `packages/FeatureFlags` | FAILED | phpunit --coverage-text (phpunit.xml) exit 255 ; Fatal error: Class Nexus\FeatureFlags\Core\Decorators\CachedFlagRepository contains 1 abstract method and must therefore be declared abstract or implement the remaining methods (Nexus\FeatureFlags\Contracts\FlagRepositoryInterface::findByName) in /home/azaharizaman/dev/atomy/packages/FeatureFlags/src/Core/Decorators/CachedFlagRepository.php on line 33 | yes |
| `nexus/identity` | L1 | `packages/Identity` | N/A | no phpunit config | yes |
| `nexus/import` | L1 | `packages/Import` | N/A | no tests directory | no |
| `nexus/inventory` | L1 | `packages/Inventory` | N/A | no tests directory | no |
| `nexus/machine-learning` | L1 | `packages/MachineLearning` | 0.00% | phpunit --coverage-text (phpunit.xml) exit 2 | yes |
| `nexus/notifier` | L1 | `packages/Notifier` | N/A | phpunit not installed | yes |
| `nexus/procurement` | L1 | `packages/Procurement` | N/A | phpunit not installed | yes |
| `nexus/query-engine` | L1 | `packages/QueryEngine` | N/A | no tests directory | no |
| `nexus/receivable` | L1 | `packages/Receivable` | N/A | no tests directory | no |
| `nexus/sales` | L1 | `packages/Sales` | N/A | no tests directory | no |
| `nexus/setting` | L1 | `packages/Setting` | N/A | no tests directory | no |
| `nexus/storage` | L1 | `packages/Storage` | N/A | no tests directory | no |
| `nexus/telemetry` | L1 | `packages/Telemetry` | 72.40% | phpunit --coverage-text (phpunit.xml) exit 1 ; tests failed | yes |
| `nexus/tenant` | L1 | `packages/Tenant` | N/A | no tests directory | no |
| `nexus/connectivity-operations` | L2 | `orchestrators/ConnectivityOperations` | N/A | no phpunit config | yes |
| `nexus/data-exchange-operations` | L2 | `orchestrators/DataExchangeOperations` | N/A | no phpunit config | yes |
| `nexus/identity-operations` | L2 | `orchestrators/IdentityOperations` | 0.00% | phpunit --coverage-text (phpunit.xml) exit 2 | yes |
| `nexus/insight-operations` | L2 | `orchestrators/InsightOperations` | N/A | no phpunit config | yes |
| `nexus/intelligence-operations` | L2 | `orchestrators/IntelligenceOperations` | N/A | no phpunit config | yes |
| `nexus/laravel-auditlogger-adapter` | L3 | `adapters/Laravel/AuditLogger` | N/A | no CI run | no |
| `nexus/laravel-connectivity-operations-adapter` | L3 | `adapters/Laravel/ConnectivityOperations` | N/A | no CI run | no |
| `nexus/laravel-data-exchange-operations-adapter` | L3 | `adapters/Laravel/DataExchangeOperations` | N/A | no CI run | no |
| `nexus/laravel-featureflags-adapter` | L3 | `adapters/Laravel/FeatureFlags` | N/A | no CI run | no |
| `nexus/laravel-identity-adapter` | L3 | `adapters/Laravel/Identity` | N/A | no CI run | no |
| `nexus/laravel-insight-operations-adapter` | L3 | `adapters/Laravel/InsightOperations` | N/A | no CI run | no |
| `nexus/laravel-intelligence-operations-adapter` | L3 | `adapters/Laravel/IntelligenceOperations` | N/A | no CI run | no |
| `nexus/laravel-monitoring-adapter` | L3 | `adapters/Laravel/Monitoring` | N/A | no CI run | no |
| `nexus/laravel-sales` | L3 | `adapters/Laravel/Sales` | N/A | no CI run | no |
| `nexus/laravel-setting-adapter` | L3 | `adapters/Laravel/Setting` | N/A | no CI run | no |
| `nexus/laravel-tenant-adapter` | L3 | `adapters/Laravel/Tenant` | N/A | no CI run | no |
