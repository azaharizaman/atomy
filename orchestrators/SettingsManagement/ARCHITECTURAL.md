# SettingsManagement Orchestrator - Architecture

> **Status:** Architecture Design  
> **Version:** 1.0.0  
> **Date:** 2026-02-22

---

## 1. Architecture Overview

### 1.1 Design Philosophy

The SettingsManagement orchestrator follows the **Advanced Orchestrator Pattern** as defined in [`ARCHITECTURE.md`](../../ARCHITECTURE.md#4-the-advanced-orchestrator-pattern). It coordinates cross-cutting concerns related to configuration management, serving as the central system for all settings, feature flags, and fiscal periods in the Nexus ERP system.

### 1.2 Layer Positioning

```
┌─────────────────────────────────────────────────────────┐
│                    Adapters (L3)                        │
│   Implements orchestrator interfaces using atomic pkgs   │
│   - Laravel adapters for SettingsManagement            │
└─────────────────────────────────────────────────────────┘
                            ▲ implements
┌─────────────────────────────────────────────────────────┐
│               SettingsManagement (L2)                   │
│   - Defines own interfaces in Contracts/                │
│   - Depends only on: php, psr/log, psr/event-dispatcher │
│   - Coordinates multi-package configuration workflows   │
│   - Publishable as standalone composer package          │
└─────────────────────────────────────────────────────────┘
                            ▲ uses via interfaces
┌─────────────────────────────────────────────────────────┐
│                Atomic Packages (L1)                     │
│   - Setting, FeatureFlags, Period, Backoffice           │
│   - AuditLogger, Identity                               │
└─────────────────────────────────────────────────────────┘
```

---

## 2. Directory Structure

```
orchestrators/SettingsManagement/
├── composer.json
├── README.md
├── REQUIREMENTS.md
├── ARCHITECTURAL.md
├── phpunit.xml
├── .gitignore
├── src/
│   ├── Contracts/                    # Own interfaces
│   │   ├── SettingsCoordinatorInterface.php
│   │   ├── FeatureFlagCoordinatorInterface.php
│   │   ├── FiscalPeriodCoordinatorInterface.php
│   │   ├── ConfigurationSyncCoordinatorInterface.php
│   │   ├── SettingsProviderInterface.php
│   │   ├── FeatureFlagProviderInterface.php
│   │   └── FiscalPeriodProviderInterface.php
│   │
│   ├── Coordinators/                 # Traffic management
│   │   ├── SettingsCoordinator.php
│   │   ├── FeatureFlagCoordinator.php
│   │   ├── FiscalPeriodCoordinator.php
│   │   └── ConfigurationSyncCoordinator.php
│   │
│   ├── DataProviders/                # Cross-package aggregation
│   │   ├── SettingsDataProvider.php
│   │   ├── FeatureFlagDataProvider.php
│   │   ├── FiscalPeriodDataProvider.php
│   │   └── ConfigurationDataProvider.php
│   │
│   ├── DTOs/                         # Request/Response objects
│   │   ├── Settings/
│   │   │   ├── SettingUpdateRequest.php
│   │   │   ├── SettingUpdateResult.php
│   │   │   ├── BulkSettingUpdateRequest.php
│   │   │   ├── BulkSettingUpdateResult.php
│   │   │   └── SettingValidationRequest.php
│   │   ├── FeatureFlags/
│   │   │   ├── FlagCreateRequest.php
│   │   │   ├── FlagCreateResult.php
│   │   │   ├── FlagUpdateRequest.php
│   │   │   ├── FlagUpdateResult.php
│   │   │   ├── FeatureRolloutRequest.php
│   │   │   ├── FeatureRolloutResult.php
│   │   │   ├── FlagDisableRequest.php
│   │   │   ├── FlagDisableResult.php
│   │   │   └── EvaluationContext.php
│   │   ├── FiscalPeriod/
│   │   │   ├── CalendarConfigRequest.php
│   │   │   ├── CalendarConfigResult.php
│   │   │   ├── PeriodGenerationRequest.php
│   │   │   ├── PeriodGenerationResult.php
│   │   │   ├── PeriodValidationRequest.php
│   │   │   ├── PeriodValidationResult.php
│   │   │   ├── PeriodCloseRequest.php
│   │   │   └── PeriodCloseResult.php
│   │   └── Configuration/
│   │       ├── ConfigurationExportRequest.php
│   │       ├── ConfigurationExportResult.php
│   │       ├── ConfigurationImportRequest.php
│   │       ├── ConfigurationImportResult.php
│   │       └── ConfigurationRollbackRequest.php
│   │
│   ├── Rules/                        # Business validation
│   │   ├── SettingValueValidRule.php
│   │   ├── SettingDependencyRule.php
│   │   ├── SettingConflictRule.php
│   │   ├── FeatureFlagConflictRule.php
│   │   ├── FeatureFlagTargetingRule.php
│   │   ├── FiscalPeriodOverlapRule.php
│   │   ├── FiscalPeriodLockedRule.php
│   │   └── ConfigurationImportRule.php
│   │
│   ├── Services/                     # Complex orchestration logic
│   │   ├── SettingsUpdateService.php
│   │   ├── FeatureFlagService.php
│   │   ├── FiscalPeriodService.php
│   │   ├── ConfigurationSyncService.php
│   │   └── ConfigurationCacheService.php
│   │
│   ├── Workflows/                    # Stateful processes
│   │   ├── FeatureRollout/
│   │   │   ├── FeatureRolloutWorkflow.php
│   │   │   └── States/
│   │   │       ├── RolloutInitiated.php
│   │   │       ├── RolloutProgressing.php
│   │   │       └── RolloutCompleted.php
│   │   └── PeriodClosure/
│   │       ├── PeriodClosureWorkflow.php
│   │       └── States/
│   │
│   ├── Listeners/                    # Event handlers
│   │   ├── OnSettingChanged.php
│   │   ├── OnFeatureFlagCreated.php
│   │   ├── OnFeatureFlagDisabled.php
│   │   ├── OnFiscalPeriodClosed.php
│   │   └── OnConfigurationImported.php
│   │
│   └── Exceptions/                   # Domain errors
│       ├── SettingsManagementException.php
│       ├── SettingNotFoundException.php
│       ├── SettingValidationException.php
│       ├── FeatureFlagException.php
│       ├── FeatureFlagNotFoundException.php
│       ├── FiscalPeriodException.php
│       ├── FiscalPeriodOverlapException.php
│       ├── ConfigurationSyncException.php
│       └── ConfigurationImportException.php
│
└── tests/
    ├── Unit/
    │   ├── Rules/
    │   └── Services/
    └── Integration/
        └── Coordinators/
```

---

## 3. Interface Segregation

### 3.1 Contract Design Principles

Following [`ARCHITECTURE.md`](../../ARCHITECTURE.md#5-orchestrator-interface-segregation), SettingsManagement:

1. **Defines its own interfaces** in `Contracts/` - never depend on atomic package interfaces directly
2. **Depends only on PSR interfaces** (PSR-3 Logger, PSR-14 EventDispatcher)
3. **Allows adapters** to bridge to atomic package implementations
4. **Enables publishing** as a standalone Composer package

### 3.2 Core Interface Hierarchy

```php
// Base coordinator interface
interface SettingsCoordinatorInterfaceBase
{
    public function getName(): string;
}

// Settings extends base
interface SettingsCoordinatorInterface extends SettingsCoordinatorInterfaceBase
{
    public function updateSetting(SettingUpdateRequest $request): SettingUpdateResult;
    public function bulkUpdateSettings(BulkSettingUpdateRequest $request): BulkSettingUpdateResult;
    public function validateSettingChange(SettingChangeRequest $request): ValidationResult;
}

// FeatureFlag extends base
interface FeatureFlagCoordinatorInterface extends SettingsCoordinatorInterfaceBase
{
    public function createFlag(FlagCreateRequest $request): FlagCreateResult;
    public function updateFlag(FlagUpdateRequest $request): FlagUpdateResult;
    public function rolloutFeature(FeatureRolloutRequest $request): FeatureRolloutResult;
    public function evaluateFlags(EvaluationContext $context): FlagEvaluationResult;
    public function disableFlag(FlagDisableRequest $request): FlagDisableResult;
}
```

---

## 4. Coordinator Design

### 4.1 Coordinator Responsibilities

Each coordinator follows the principle: **"Coordinators are Traffic Cops, Not Workers"**

| Coordinator | Responsibility | Boundaries |
|------------|----------------|-------------|
| `SettingsCoordinator` | Orchestrates settings updates | Does not validate; delegates to rules |
| `FeatureFlagCoordinator` | Manages feature flag lifecycle | Does not evaluate; delegates to evaluator |
| `FiscalPeriodCoordinator` | Manages fiscal periods | Does not calculate; delegates to period service |
| `ConfigurationSyncCoordinator` | Manages config import/export | Does not transform; delegates to transformer |

### 4.2 Coordinator Flow Example: Feature Rollout

```
FeatureFlagCoordinator::rolloutFeature()
    │
    ├──► validateRollout()
    │       └──► FeatureFlagConflictRule
    │       └──► FeatureFlagTargetingRule
    │
    └──► Service: FeatureFlagService
            │
            ├──► 1. Update flag targeting (via FeatureFlagsAdapter)
            ├──► 2. Clear flag cache
            ├──► 3. Log audit event (via AuditLoggerAdapter)
            └──► 4. Dispatch progress event
```

---

## 5. DataProvider Design

### 5.1 Data Aggregation Pattern

DataProviders aggregate cross-package data into context DTOs that coordinators can consume.

```php
// Example: ConfigurationDataProvider aggregates all config
class ConfigurationDataProvider implements ConfigurationProviderInterface
{
    public function __construct(
        private SettingQueryInterface $settingQuery,
        private FeatureFlagQueryInterface $flagQuery,
        private FiscalPeriodQueryInterface $periodQuery,
    ) {}

    public function getFullConfiguration(string $tenantId): Configuration
    {
        return new Configuration(
            settings: $this->settingQuery->getAllForTenant($tenantId),
            featureFlags: $this->flagQuery->getAllForTenant($tenantId),
            fiscalPeriods: $this->periodQuery->getAllForTenant($tenantId),
        );
    }
}
```

### 5.2 DataProvider Interfaces

| DataProvider | Aggregates From |
|--------------|-----------------|
| `SettingsDataProvider` | Setting package |
| `FeatureFlagDataProvider` | FeatureFlags package |
| `FiscalPeriodDataProvider` | Period package |
| `ConfigurationDataProvider` | All above + Backoffice |

---

## 6. Rule Design

### 6.1 Composable Validation

Rules are single-responsibility classes that can be composed into validation pipelines.

```php
// Example: Composable validation in coordinator
class FeatureFlagCoordinator implements FeatureFlagCoordinatorInterface
{
    public function validateRollout(FeatureRolloutRequest $request): ValidationResult
    {
        $rules = [
            new FeatureFlagConflictRule($this->flagQuery),
            new FeatureFlagTargetingRule(),
        ];

        $errors = [];
        foreach ($rules as $rule) {
            $result = $rule->evaluate($request);
            if (!$result->passed()) {
                $errors[] = $result->error();
            }
        }

        return new ValidationResult(passed: empty($errors), errors: $errors);
    }
}
```

---

## 7. Service Design

### 7.1 Orchestration Services

Services contain the complex orchestration logic that coordinates multiple atomic packages.

```php
class FeatureFlagService implements FeatureFlagServiceInterface
{
    public function __construct(
        private FeatureFlagManagerInterface $flagManager,
        private FeatureFlagEvaluatorInterface $flagEvaluator,
        private AuditLogManagerInterface $auditLogger,
        private EventDispatcherInterface $eventDispatcher,
        private ConfigurationCacheServiceInterface $cache,
    ) {}

    public function rolloutFeature(FeatureRolloutRequest $request): FeatureRolloutResult
    {
        // Update flag targeting
        $this->flagManager->updateTargeting(
            flagId: $request->flagId,
            targeting: $request->targeting
        );

        // Clear cache for this flag
        $this->cache->invalidate($request->flagKey);

        // Audit log
        $this->auditLogger->log(
            event: 'feature_flag.rolled_out',
            data: ['flag' => $request->flagKey, 'targeting' => $request->targeting]
        );

        // Dispatch event
        $this->eventDispatcher->dispatch(new FeatureFlagRolledOutEvent(
            flagKey: $request->flagKey,
            targeting: $request->targeting
        ));

        return new FeatureRolloutResult(success: true);
    }
}
```

---

## 8. Dependency Injection

### 8.1 Constructor Injection Pattern

All dependencies are injected via constructors following the **Dependency Inversion Principle**.

```php
final readonly class FeatureFlagCoordinator implements FeatureFlagCoordinatorInterface
{
    public function __construct(
        private FeatureFlagServiceInterface $service,
        private FeatureFlagDataProviderInterface $dataProvider,
    ) {}
}
```

### 8.2 Interface Segregation in Dependencies

Coordinators depend on **orchestrator interfaces**, not atomic package interfaces.

---

## 9. Event-Driven Architecture

### 9.1 Domain Events

SettingsManagement emits domain events for reactive workflows:

| Event | Payload | Listeners |
|-------|---------|-----------|
| `SettingChangedEvent` | Setting key, old/new value | OnSettingChanged (notify deps) |
| `FeatureFlagCreatedEvent` | Flag data | OnFeatureFlagCreated (cache init) |
| `FeatureFlagDisabledEvent` | Flag key | OnFeatureFlagDisabled (kill switch) |
| `FiscalPeriodClosedEvent` | Period ID | OnFiscalPeriodClosed (notify modules) |
| `ConfigurationImportedEvent` | Import details | OnConfigurationImported (validate) |

---

## 10. Caching Strategy

### 10.1 Configuration Cache

SettingsManagement implements aggressive caching for performance:

```php
class ConfigurationCacheService implements ConfigurationCacheServiceInterface
{
    private const SETTINGS_TTL = 3600; // 1 hour
    private const FLAGS_TTL = 60; // 1 minute

    public function getSettings(string $tenantId, string $key): ?mixed
    {
        return $this->cache->get("settings:{$tenantId}:{$key}");
    }

    public function invalidateSettings(string $tenantId, string $key): void
    {
        $this->cache->forget("settings:{$tenantId}:{$key}");
    }

    public function invalidateAllFlags(string $tenantId): void
    {
        // Use cache tags for bulk invalidation
        $this->cache->forget("flags:{$tenantId}:*");
    }
}
```

---

## 11. Error Handling

### 11.1 Domain-Specific Exceptions

```php
class SettingsManagementException extends \RuntimeException
{
    private array $context;

    public function __construct(string $message, array $context = [], \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->context = $context;
    }
}
```

### 11.2 Exception Hierarchy

```
SettingsManagementException (base)
├── SettingException
│   ├── SettingNotFoundException
│   ├── SettingValidationException
│   └── SettingDependencyException
├── FeatureFlagException
│   ├── FeatureFlagNotFoundException
│   ├── FeatureFlagConflictException
│   └── FeatureFlagEvaluationException
├── FiscalPeriodException
│   ├── FiscalPeriodNotFoundException
│   ├── FiscalPeriodOverlapException
│   └── FiscalPeriodLockedException
└── ConfigurationSyncException
    ├── ConfigurationImportException
    └── ConfigurationExportException
```

---

## 12. Testing Strategy

### 12.1 Unit Tests

- **Rules**: Test each rule in isolation
- **Services**: Test service logic with mocked repositories
- **DTOs**: Test serialization/deserialization

### 12.2 Integration Tests

- **Coordinators**: Test full workflow with in-memory implementations
- **DataProviders**: Test aggregation from multiple sources

### 12.3 Performance Tests

- Feature flag evaluation latency
- Bulk settings update throughput
- Configuration export performance

---

## 13. Framework Integration

### 13.1 Laravel Service Provider

```php
<?php

namespace Nexus\Laravel\SettingsManagement;

use Illuminate\Support\ServiceProvider;
use Nexus\SettingsManagement\Contracts\SettingsCoordinatorInterface;
use Nexus\SettingsManagement\Contracts\FeatureFlagCoordinatorInterface;
use Nexus\SettingsManagement\Coordinators\SettingsCoordinator;
use Nexus\SettingsManagement\Coordinators\FeatureFlagCoordinator;

class SettingsManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register coordinators
        $this->app->singleton(SettingsCoordinatorInterface::class, SettingsCoordinator::class);
        $this->app->singleton(FeatureFlagCoordinatorInterface::class, FeatureFlagCoordinator::class);

        // Register data providers
        // Register services
        // Register rules
    }
}
```

---

## 14. Monitoring and Observability

### 14.1 Logging Strategy

- Settings changes: INFO level
- Feature flag rollouts: INFO level
- Validation failures: WARNING level
- Exceptions: ERROR level with full context

### 14.2 Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `settings.update.latency` | Histogram | Settings update duration |
| `feature_flag.evaluation.latency` | Histogram | Flag evaluation time |
| `feature_flag.rollout.duration` | Histogram | Rollout workflow duration |
| `fiscal_period.validation.count` | Counter | Period validations |
| `config.import.duration` | Histogram | Import process duration |

---

## 15. Security Considerations

### 15.1 Settings Security

- Sensitive settings encrypted at rest
- Settings changes require `settings.manage` permission
- Bulk changes require elevated permissions
- All changes audit-logged

### 15.2 Feature Flag Security

- Flag creation requires `features.manage` permission
- Production rollouts require approval workflow
- Kill switch can be triggered by any admin
- Flag history retained for compliance

---

## Appendix A: Package Dependencies

```
nexus/settings-management
├── nexus/common (^1.0)
├── nexus/setting (^1.0)
├── nexus/feature-flags (^1.0)
├── nexus/period (^1.0)
├── nexus/backoffice (^1.0)
├── nexus/audit-logger (^1.0)
├── nexus/identity (^1.0)
├── psr/log (^3.0)
└── psr/event-dispatcher (^3.0)
```

---

*Document Version: 1.0.0*  
*Last Updated: 2026-02-22*
