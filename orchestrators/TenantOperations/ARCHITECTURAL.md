# TenantOperations Orchestrator - Architecture

> **Status:** Architecture Design  
> **Version:** 1.0.0  
> **Date:** 2026-02-22

---

## 1. Architecture Overview

### 1.1 Design Philosophy

The TenantOperations orchestrator follows the **Advanced Orchestrator Pattern** as defined in [`ARCHITECTURE.md`](../../ARCHITECTURE.md#4-the-advanced-orchestrator-pattern). It coordinates cross-cutting concerns related to multi-tenancy, serving as the backbone for all other operational orchestrators in the Nexus ERP system.

### 1.2 Layer Positioning

```
┌─────────────────────────────────────────────────────────┐
│                    Adapters (L3)                        │
│   Implements orchestrator interfaces using atomic pkgs   │
│   - Laravel adapters for TenantOperations              │
└─────────────────────────────────────────────────────────┘
                            ▲ implements
┌─────────────────────────────────────────────────────────┐
│                 TenantOperations (L2)                   │
│   - Defines own interfaces in Contracts/                │
│   - Depends only on: php, psr/log, psr/event-dispatcher │
│   - Coordinates multi-package tenant workflows         │
│   - Publishable as standalone composer package          │
└─────────────────────────────────────────────────────────┘
                            ▲ uses via interfaces
┌─────────────────────────────────────────────────────────┐
│                Atomic Packages (L1)                     │
│   - Tenant, Setting, FeatureFlags, Backoffice           │
│   - AuditLogger, Identity                               │
└─────────────────────────────────────────────────────────┘
```

---

## 2. Directory Structure

```
orchestrators/TenantOperations/
├── composer.json
├── README.md
├── REQUIREMENTS.md
├── ARCHITECTURAL.md
├── phpunit.xml
├── .gitignore
├── src/
│   ├── Contracts/                    # Own interfaces
│   │   ├── TenantOnboardingCoordinatorInterface.php
│   │   ├── TenantLifecycleCoordinatorInterface.php
│   │   ├── TenantImpersonationCoordinatorInterface.php
│   │   ├── TenantValidationCoordinatorInterface.php
│   │   ├── TenantContextProviderInterface.php
│   │   ├── TenantSettingsProviderInterface.php
│   │   └── TenantFeatureProviderInterface.php
│   │
│   ├── Coordinators/                 # Traffic management
│   │   ├── TenantOnboardingCoordinator.php
│   │   ├── TenantLifecycleCoordinator.php
│   │   ├── TenantImpersonationCoordinator.php
│   │   └── TenantValidationCoordinator.php
│   │
│   ├── DataProviders/                # Cross-package aggregation
│   │   ├── TenantContextDataProvider.php
│   │   ├── TenantSettingsDataProvider.php
│   │   ├── TenantFeatureDataProvider.php
│   │   ├── TenantAuditDataProvider.php
│   │   └── TenantValidationDataProvider.php
│   │
│   ├── DTOs/                         # Request/Response objects
│   │   ├── TenantOnboardingRequest.php
│   │   ├── TenantOnboardingResult.php
│   │   ├── TenantSuspendRequest.php
│   │   ├── TenantSuspendResult.php
│   │   ├── TenantActivateRequest.php
│   │   ├── TenantActivateResult.php
│   │   ├── TenantArchiveRequest.php
│   │   ├── TenantArchiveResult.php
│   │   ├── TenantDeleteRequest.php
│   │   ├── TenantDeleteResult.php
│   │   ├── ImpersonationStartRequest.php
│   │   ├── ImpersonationStartResult.php
│   │   ├── ImpersonationEndRequest.php
│   │   ├── ImpersonationEndResult.php
│   │   ├── TenantValidationRequest.php
│   │   ├── TenantValidationResult.php
│   │   ├── ModulesValidationRequest.php
│   │   ├── ConfigurationValidationRequest.php
│   │   └── ValidationResult.php
│   │
│   ├── Rules/                        # Business validation
│   │   ├── TenantCodeUniqueRule.php
│   │   ├── TenantDomainUniqueRule.php
│   │   ├── TenantActiveRule.php
│   │   ├── TenantModulesEnabledRule.php
│   │   ├── TenantConfigurationExistsRule.php
│   │   ├── ImpersonationAllowedRule.php
│   │   └── TenantSubscriptionValidRule.php
│   │
│   ├── Services/                     # Complex orchestration logic
│   │   ├── TenantOnboardingService.php
│   │   ├── TenantLifecycleService.php
│   │   ├── TenantImpersonationService.php
│   │   ├── TenantValidationService.php
│   │   └── TenantContextService.php
│   │
│   ├── Workflows/                    # Stateful processes
│   │   └── Onboarding/
│   │       ├── OnboardingWorkflow.php
│   │       └── States/
│   │           ├── OnboardingInitiated.php
│   │           ├── TenantCreated.php
│   │           ├── SettingsInitialized.php
│   │           ├── FeaturesConfigured.php
│   │           ├── CompanyCreated.php
│   │           └── OnboardingCompleted.php
│   │
│   ├── Listeners/                    # Event handlers
│   │   ├── OnTenantCreated.php
│   │   ├── OnTenantSuspended.php
│   │   ├── OnTenantActivated.php
│   │   ├── OnImpersonationStarted.php
│   │   └── OnImpersonationEnded.php
│   │
│   └── Exceptions/                   # Domain errors
│       ├── TenantOnboardingException.php
│       ├── TenantLifecycleException.php
│       ├── TenantImpersonationException.php
│       ├── TenantValidationException.php
│       ├── TenantNotActiveException.php
│       ├── TenantModulesNotEnabledException.php
│       ├── TenantConfigurationMissingException.php
│       └── ImpersonationNotAllowedException.php
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

Following [`ARCHITECTURE.md`](../../ARCHITECTURE.md#5-orchestrator-interface-segregation), TenantOperations:

1. **Defines its own interfaces** in `Contracts/` - never depend on atomic package interfaces directly
2. **Depends only on PSR interfaces** (PSR-3 Logger, PSR-14 EventDispatcher)
3. **Allows adapters** to bridge to atomic package implementations
4. **Enables publishing** as a standalone Composer package

### 3.2 Core Interface Hierarchy

```php
// Base coordinator interface
interface TenantCoordinatorInterface
{
    public function getName(): string;
    public function hasRequiredData(string $tenantId): bool;
}

// Onboarding extends base
interface TenantOnboardingCoordinatorInterface extends TenantCoordinatorInterface
{
    public function onboard(TenantOnboardingRequest $request): TenantOnboardingResult;
    public function validatePrerequisites(TenantOnboardingRequest $request): ValidationResult;
}

// Lifecycle extends base
interface TenantLifecycleCoordinatorInterface extends TenantCoordinatorInterface
{
    public function suspend(TenantSuspendRequest $request): TenantSuspendResult;
    public function activate(TenantActivateRequest $request): TenantActivateResult;
    public function archive(TenantArchiveRequest $request): TenantArchiveResult;
    public function delete(TenantDeleteRequest $request): TenantDeleteResult;
}
```

---

## 4. Coordinator Design

### 4.1 Coordinator Responsibilities

Each coordinator follows the principle: **"Coordinators are Traffic Cops, Not Workers"**

| Coordinator | Responsibility | Boundaries |
|-------------|----------------|-------------|
| `TenantOnboardingCoordinator` | Orchestrates the onboarding workflow | Does not create tenant; delegates to services |
| `TenantLifecycleCoordinator` | Manages state transitions | Does not modify state; delegates to lifecycle service |
| `TenantImpersonationCoordinator` | Manages impersonation sessions | Does not authenticate; delegates to identity |
| `TenantValidationCoordinator` | Validates tenant state for other orchestrators | Does not query databases; uses data providers |

### 4.2 Coordinator Flow Example: Onboarding

```
TenantOnboardingCoordinator::onboard()
    │
    ├──► validatePrerequisites()
    │       └──► TenantCodeUniqueRule
    │       └──► TenantDomainUniqueRule
    │
    ├──► DataProvider: TenantContextDataProvider
    │       └──► Aggregates existing tenant data
    │
    └──► Service: TenantOnboardingService
            │
            ├──► 1. Create Tenant (via TenantAdapter)
            ├──► 2. Initialize Settings (via SettingAdapter)
            ├──► 3. Configure Features (via FeatureFlagsAdapter)
            ├──► 4. Create Company (via BackofficeAdapter)
            ├──► 5. Create Admin User (via IdentityAdapter)
            └──► 6. Log Audit (via AuditLoggerAdapter)
```

---

## 5. DataProvider Design

### 5.1 Data Aggregation Pattern

DataProviders aggregate cross-package data into context DTOs that coordinators can consume.

```php
// Example: TenantContextDataProvider aggregates from multiple packages
class TenantContextDataProvider implements TenantContextProviderInterface
{
    public function __construct(
        private TenantQueryInterface $tenantQuery,
        private TenantSettingsQueryInterface $settingsQuery,
        private FeatureFlagEvaluatorInterface $featureEvaluator,
    ) {}

    public function getContext(string $tenantId): TenantContext
    {
        $tenant = $this->tenantQuery->findById($tenantId);
        $settings = $this->settingsQuery->getSettings($tenantId);
        $features = $this->featureEvaluator->evaluateForTenant($tenantId);

        return new TenantContext(
            tenant: $tenant,
            settings: $settings,
            features: $features,
        );
    }
}
```

### 5.2 DataProvider Interfaces

| DataProvider | Aggregates From |
|--------------|-----------------|
| `TenantContextDataProvider` | Tenant, Settings, FeatureFlags |
| `TenantSettingsDataProvider` | Setting package |
| `TenantFeatureDataProvider` | FeatureFlags package |
| `TenantAuditDataProvider` | AuditLogger package |
| `TenantValidationDataProvider` | Tenant, Settings, Backoffice |

---

## 6. Rule Design

### 6.1 Composable Validation

Rules are single-responsibility classes that can be composed into validation pipelines.

```php
// Example: Composable validation in coordinator
class TenantOnboardingCoordinator implements TenantOnboardingCoordinatorInterface
{
    public function validatePrerequisites(TenantOnboardingRequest $request): ValidationResult
    {
        $rules = [
            new TenantCodeUniqueRule($this->tenantQuery),
            new TenantDomainUniqueRule($this->tenantQuery),
        ];

        $errors = [];
        foreach ($rules as $rule) {
            $result = $rule->evaluate($request);
            if (!$result->passed()) {
                $errors[] = $result->error();
            }
        }

        return new ValidationResult(
            passed: empty($errors),
            errors: $errors
        );
    }
}
```

### 6.2 Rule Interface

```php
interface TenantValidationRuleInterface
{
    public function evaluate(mixed $subject): RuleEvaluationResult;
}
```

---

## 7. Service Design

### 7.1 Orchestration Services

Services contain the complex orchestration logic that coordinates multiple atomic packages.

```php
class TenantOnboardingService implements TenantOnboardingServiceInterface
{
    public function __construct(
        private TenantPersistenceInterface $tenantRepository,
        private SettingManagerInterface $settingManager,
        private FeatureFlagManagerInterface $featureManager,
        private CompanyManagerInterface $companyManager,
        private UserManagerInterface $userManager,
        private AuditLogManagerInterface $auditLogger,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function onboard(TenantOnboardingRequest $request): TenantOnboardingResult
    {
        // Transactional onboarding
        return DB::transaction(function () use ($request) {
            // Step 1: Create tenant
            $tenant = $this->createTenant($request);

            // Step 2: Initialize settings
            $this->initializeSettings($tenant, $request);

            // Step 3: Configure features
            $this->configureFeatures($tenant, $request);

            // Step 4: Create company structure
            $company = $this->createCompanyStructure($tenant, $request);

            // Step 5: Create admin user
            $adminUser = $this->createAdminUser($tenant, $request);

            // Step 6: Log audit
            $this->auditLogger->log(
                event: 'tenant.onboarded',
                tenantId: $tenant->getId(),
                data: ['tenant' => $tenant->toArray()]
            );

            // Dispatch event
            $this->eventDispatcher->dispatch(new TenantCreatedEvent($tenant));

            return new TenantOnboardingResult(
                success: true,
                tenantId: $tenant->getId(),
                adminUserId: $adminUser->getId(),
                companyId: $company->getId(),
            );
        });
    }
}
```

---

## 8. Dependency Injection

### 8.1 Constructor Injection Pattern

All dependencies are injected via constructors following the **Dependency Inversion Principle**.

```php
final readonly class TenantOnboardingCoordinator implements TenantOnboardingCoordinatorInterface
{
    public function __construct(
        private TenantOnboardingServiceInterface $service,
        private TenantValidationServiceInterface $validationService,
        private TenantContextDataProviderInterface $dataProvider,
    ) {}
}
```

### 8.2 Interface Segregation in Dependencies

Coordinators depend on **orchestrator interfaces**, not atomic package interfaces:

```php
// ✅ CORRECT: Depends on own interface
class TenantOnboardingCoordinator
{
    public function __construct(
        private TenantOnboardingServiceInterface $service,  // Own interface
    ) {}
}

// ❌ WRONG: Would depend on atomic package directly
class TenantOnboardingCoordinator
{
    public function __construct(
        private TenantLifecycleService $service,  // Atomic package - NOT ALLOWED
    ) {}
}
```

---

## 9. Event-Driven Architecture

### 9.1 Domain Events

TenantOperations emits domain events for reactive workflows:

| Event | Payload | Listeners |
|-------|---------|-----------|
| `TenantCreatedEvent` | Tenant data | OnTenantCreated (notify admin) |
| `TenantSuspendedEvent` | Tenant ID | OnTenantSuspended (disable access) |
| `TenantActivatedEvent` | Tenant ID | OnTenantActivated (enable access) |
| `TenantArchivedEvent` | Tenant ID | OnTenantArchived (archive data) |
| `ImpersonationStartedEvent` | Admin, Tenant | OnImpersonationStarted (log) |
| `ImpersonationEndedEvent` | Admin, Tenant | OnImpersonationEnded (log) |

---

## 10. Error Handling

### 10.1 Domain-Specific Exceptions

All exceptions are domain-specific and extend base orchestrator exceptions:

```php
class TenantOnboardingException extends \RuntimeException
{
    private array $context;

    public function __construct(string $message, array $context = [], \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
```

### 10.2 Exception Hierarchy

```
TenantOperationsException (base)
├── TenantOnboardingException
│   ├── TenantCodeNotUniqueException
│   └── TenantDomainNotUniqueException
├── TenantLifecycleException
│   ├── TenantAlreadySuspendedException
│   └── TenantAlreadyActivatedException
├── TenantImpersonationException
│   └── ImpersonationNotAllowedException
└── TenantValidationException
    ├── TenantNotActiveException
    └── TenantModulesNotEnabledException
```

---

## 11. Testing Strategy

### 11.1 Unit Tests

- **Rules**: Test each rule in isolation with mock dependencies
- **Services**: Test service logic with mocked repositories
- **DTOs**: Test serialization/deserialization

### 11.2 Integration Tests

- **Coordinators**: Test full workflow with in-memory implementations
- **DataProviders**: Test aggregation from multiple sources

### 11.3 Test Example

```php
final class TenantCodeUniqueRuleTest extends TestCase
{
    private TenantCodeUniqueRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new TenantCodeUniqueRule(
            $this->createMock(TenantQueryInterface::class)
        );
    }

    public function test_passes_when_tenant_code_is_unique(): void
    {
        // Arrange
        $request = new TenantOnboardingRequest(
            tenantCode: 'NEW_CODE',
            // ...
        );

        $this->mock->expects($this->once())
            ->method('findByCode')
            ->with('NEW_CODE')
            ->willReturn(null);

        // Act
        $result = $this->rule->evaluate($request);

        // Assert
        $this->assertTrue($result->passed());
    }
}
```

---

## 12. Framework Integration

### 12.1 Laravel Service Provider

```php
<?php

namespace Nexus\Laravel\TenantOperations;

use Illuminate\Support\ServiceProvider;
use Nexus\TenantOperations\Contracts\TenantOnboardingCoordinatorInterface;
use Nexus\TenantOperations\Contracts\TenantLifecycleCoordinatorInterface;
use Nexus\TenantOperations\Coordinators\TenantOnboardingCoordinator;
use Nexus\TenantOperations\Coordinators\TenantLifecycleCoordinator;

class TenantOperationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register coordinators
        $this->app->singleton(
            TenantOnboardingCoordinatorInterface::class,
            TenantOnboardingCoordinator::class
        );
        $this->app->singleton(
            TenantLifecycleCoordinatorInterface::class,
            TenantLifecycleCoordinator::class
        );

        // Register data providers
        // Register services
        // Register rules
    }
}
```

---

## 13. Monitoring and Observability

### 13.1 Logging Strategy

- All coordinator operations log at `INFO` level
- Validation failures log at `WARNING` level
- Exceptions log at `ERROR` level with full context
- Impersonation events always logged

### 13.2 Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `tenant.onboarding.duration` | Histogram | Onboarding workflow duration |
| `tenant.lifecycle.transitions` | Counter | Lifecycle state changes |
| `tenant.impersonation.count` | Counter | Impersonation sessions |
| `tenant.validation.failures` | Counter | Validation failures |

---

## 14. Security Considerations

### 14.1 Tenant Isolation

- Tenant context MUST be set for every operation
- Queries MUST always include tenant filter
- Tenant data MUST be isolated at database level
- Cross-tenant access MUST be explicitly blocked

### 14.2 Impersonation Security

- Impersonation requires `tenant.impersonate` permission
- All impersonation actions are audit-logged
- Impersonation sessions have timeout (default: 30 minutes)
- Original admin identity preserved in all logs

---

## Appendix A: Package Dependencies

```
nexus/tenant-operations
├── nexus/common (^1.0)
├── nexus/tenant (^1.0)
├── nexus/setting (^1.0)
├── nexus/feature-flags (^1.0)
├── nexus/backoffice (^1.0)
├── nexus/audit-logger (^1.0)
├── nexus/identity (^1.0)
├── psr/log (^3.0)
└── psr/event-dispatcher (^3.0)
```

---

*Document Version: 1.0.0*  
*Last Updated: 2026-02-22*
