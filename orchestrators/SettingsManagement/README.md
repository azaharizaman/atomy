# SettingsManagement Orchestrator

> Non-operational orchestrator for system-wide configuration management - coordinates Setting, FeatureFlags, Period, and Backoffice packages

## Overview

The **SettingsManagement** orchestrator provides centralized management of settings, feature flags, fiscal periods, and organizational configurations across the Nexus ERP system. As a Layer-2 orchestrator, it coordinates workflows across multiple foundation packages while maintaining interface segregation.

## Architecture

This package follows the **Advanced Orchestrator Pattern** as defined in the main [`ARCHITECTURE.md`](../../ARCHITECTURE.md#4-the-advanced-orchestrator-pattern).

### Layer Positioning

```
┌─────────────────────────────────────────────────────────┐
│                    Adapters (L3)                        │
│   Implements orchestrator interfaces using atomic pkgs   │
└─────────────────────────────────────────────────────────┘
                            ▲ implements
┌─────────────────────────────────────────────────────────┐
│               SettingsManagement (L2)                   │
│   - Defines own interfaces in Contracts/                │
│   - Depends only on: php, psr/log, psr/event-dispatcher│
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

## Features

### Settings Management
- Single and bulk setting updates with validation
- Settings hierarchy resolution (application → tenant → user)
- Dependency and conflict detection
- Full audit logging

### Feature Flag Management
- Boolean, percentage, user-list, IP-based, and custom rule flags
- Gradual feature rollout with targeting rules
- Kill switch functionality
- High-performance evaluation with caching

### Fiscal Period Management
- Fiscal calendar configuration
- Automatic period generation
- Period validation for various operation types
- Period closure coordination

### Configuration Synchronization
- JSON-based export/import
- Configuration versioning and rollback
- Schema validation

## Directory Structure

```
src/
├── Contracts/                    # Own interfaces
│   ├── SettingsCoordinatorInterface.php
│   ├── FeatureFlagCoordinatorInterface.php
│   ├── FiscalPeriodCoordinatorInterface.php
│   ├── ConfigurationSyncCoordinatorInterface.php
│   └── Provider interfaces
│
├── Coordinators/                 # Traffic management
│   ├── SettingsCoordinator.php
│   ├── FeatureFlagCoordinator.php
│   ├── FiscalPeriodCoordinator.php
│   └── ConfigurationSyncCoordinator.php
│
├── DataProviders/                # Cross-package aggregation
│   ├── SettingsDataProvider.php
│   ├── FeatureFlagDataProvider.php
│   ├── FiscalPeriodDataProvider.php
│   └── ConfigurationDataProvider.php
│
├── DTOs/                         # Request/Response objects
│   ├── Settings/
│   ├── FeatureFlags/
│   ├── FiscalPeriod/
│   └── Configuration/
│
├── Rules/                        # Business validation
│   ├── SettingValueValidRule.php
│   ├── SettingDependencyRule.php
│   ├── SettingConflictRule.php
│   ├── FeatureFlagConflictRule.php
│   ├── FeatureFlagTargetingRule.php
│   ├── FiscalPeriodOverlapRule.php
│   ├── FiscalPeriodLockedRule.php
│   └── ConfigurationImportRule.php
│
├── Services/                     # Complex orchestration logic
│   ├── SettingsUpdateService.php
│   ├── FeatureFlagService.php
│   ├── FiscalPeriodService.php
│   ├── ConfigurationSyncService.php
│   └── ConfigurationCacheService.php
│
├── Workflows/                    # Stateful processes
│   ├── FeatureRollout/
│   └── PeriodClosure/
│
├── Listeners/                    # Event handlers
│   ├── OnSettingChanged.php
│   ├── OnFeatureFlagCreated.php
│   ├── OnFeatureFlagDisabled.php
│   ├── OnFiscalPeriodClosed.php
│   └── OnConfigurationImported.php
│
└── Exceptions/                   # Domain errors
    ├── SettingsManagementException.php
    ├── SettingNotFoundException.php
    ├── SettingValidationException.php
    ├── FeatureFlagException.php
    ├── FiscalPeriodException.php
    └── ConfigurationSyncException.php
```

## Usage

### Settings Coordinator

```php
use Nexus\SettingsManagement\Coordinators\SettingsCoordinator;
use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateRequest;

// Update a single setting
$result = $settingsCoordinator->updateSetting(new SettingUpdateRequest(
    key: 'app.timezone',
    value: 'Asia/Kuala_Lumpur',
    tenantId: 'tenant-123',
    reason: 'Update timezone for new region',
));

// Bulk update settings
$result = $settingsCoordinator->bulkUpdateSettings(new BulkSettingUpdateRequest(
    settings: [
        'app.timezone' => 'Asia/Kuala_Lumpur',
        'app.locale' => 'en-MY',
    ],
    tenantId: 'tenant-123',
));
```

### Feature Flag Coordinator

```php
use Nexus\SettingsManagement\Coordinators\FeatureFlagCoordinator;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagCreateRequest;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagType;

// Create a feature flag
$result = $featureFlagCoordinator->createFlag(new FlagCreateRequest(
    key: 'new_dashboard',
    name: 'New Dashboard',
    description: 'Enable the new dashboard UI',
    type: FlagType::BOOLEAN,
    defaultValue: false,
    tenantId: 'tenant-123',
));

// Evaluate feature flags
$result = $featureFlagCoordinator->evaluateFlags(new FlagEvaluationRequest(
    tenantId: 'tenant-123',
    flagKeys: ['new_dashboard', 'beta_features'],
    userId: 'user-456',
));
```

### Fiscal Period Coordinator

```php
use Nexus\SettingsManagement\Coordinators\FiscalPeriodCoordinator;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\CalendarConfigRequest;
use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodType;

// Configure fiscal calendar
$result = $fiscalPeriodCoordinator->configureCalendar(new CalendarConfigRequest(
    tenantId: 'tenant-123',
    fiscalYearStart: new \DateTime('2026-01-01'),
    periodType: PeriodType::MONTHLY,
    namingConvention: 'FY{{year}}-P{{period}}',
    yearEndClosing: true,
));
```

## Dependencies

This package depends only on:
- PHP 8.3+
- `psr/log` (PSR-3 Logger)
- `psr/event-dispatcher` (PSR-14 Event Dispatcher)
- Atomic package interfaces (via adapters in Layer 3)

## Integration with Other Orchestrators

| Orchestrator | Value Provided |
|--------------|----------------|
| FinanceOperations | Fiscal period configuration, validates period status |
| HumanResourceOperations | HR-specific settings, feature flags for HR modules |
| AccountingOperations | Fiscal calendar, period status validation |
| SalesOperations | Sales-specific settings, feature flags for pricing |
| ProcurementOperations | Procurement settings, approval thresholds |
| SupplyChainOperations | Inventory settings, warehouse configurations |
| CRMOperations | CRM-specific settings, pipeline configurations |
| ComplianceOperations | Compliance settings, regulatory configurations |

## Testing

```bash
# Run unit tests
composer test

# Run with coverage
composer test-coverage
```

## License

MIT
