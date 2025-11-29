# Nexus\FeatureFlags

[![Latest Version](https://img.shields.io/packagist/v/nexus/feature-flags.svg?style=flat-square)](https://packagist.org/packages/nexus/feature-flags)
[![Total Downloads](https://img.shields.io/packagist/dt/nexus/feature-flags.svg?style=flat-square)](https://packagist.org/packages/nexus/feature-flags)
[![License](https://img.shields.io/packagist/l/nexus/feature-flags.svg?style=flat-square)](LICENSE)

Production-grade feature flag management with context-based evaluation, percentage rollout, tenant inheritance, and kill switches. Framework-agnostic pure PHP 8.3+ package designed for Laravel, Symfony, Slim, and vanilla PHP applications.

## Features

- 🎯 **Context-Based Evaluation** - 5 strategies: System-Wide, Percentage Rollout, Tenant List, User List, Custom
- 🔐 **Fail-Closed Security** - Flags default to disabled when not found
- 🏢 **Tenant Inheritance** - Tenant-specific flags override global defaults
- 🚦 **Kill Switches** - Force ON/OFF overrides for emergency control
- ⚡ **Performance Optimized** - Request-level memoization, bulk evaluation API
- 🔍 **Checksum Validation** - Prevents stale cache serving
- 📊 **Observability Ready** - Optional monitoring and audit logging integration
- 🧪 **100% Type-Safe** - Strict types, native enums, immutable value objects

## Installation

### For Laravel

```bash
composer require nexus/feature-flags
```

The service provider will be auto-discovered. Publish the migration:

```bash
php artisan vendor:publish --tag=feature-flags-migrations
php artisan migrate
```

### For Symfony

```bash
composer require nexus/feature-flags
```

Register services in `config/services.yaml`:

```yaml
services:
    Nexus\FeatureFlags\Contracts\FlagRepositoryInterface:
        class: Your\Custom\FlagRepository
    
    Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface:
        class: Nexus\FeatureFlags\Services\FeatureFlagManager
        arguments:
            - '@Nexus\FeatureFlags\Contracts\FlagRepositoryInterface'
            - '@Nexus\FeatureFlags\Core\Engine\DefaultFlagEvaluator'
            - '@logger'
```

### For Vanilla PHP

```bash
composer require nexus/feature-flags
```

```php
use Nexus\FeatureFlags\Services\FeatureFlagManager;
use Nexus\FeatureFlags\Core\Engine\DefaultFlagEvaluator;
use Nexus\FeatureFlags\Core\Repository\InMemoryFlagRepository;
use Psr\Log\NullLogger;

$repository = new InMemoryFlagRepository();
$evaluator = new DefaultFlagEvaluator(new PercentageHasher());
$manager = new FeatureFlagManager($repository, $evaluator, new NullLogger());
```

## Quick Start

### Basic Usage

```php
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;

class MyController
{
    public function __construct(
        private readonly FeatureFlagManagerInterface $flags
    ) {}
    
    public function index(): Response
    {
        if ($this->flags->isEnabled('new_dashboard')) {
            return $this->renderNewDashboard();
        }
        
        return $this->renderOldDashboard();
    }
}
```

### Context-Based Evaluation

```php
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;

$context = new EvaluationContext(
    tenantId: 'tenant-123',
    userId: 'user-456',
    customAttributes: ['plan' => 'premium']
);

if ($this->flags->isEnabled('advanced_analytics', $context)) {
    // Show premium feature
}
```

### Bulk Evaluation (Prevents N+1 Queries)

```php
$context = EvaluationContext::fromArray([
    'tenant_id' => 'tenant-123',
    'user_id' => 'user-456',
]);

$flags = $this->flags->evaluateMany([
    'new_dashboard',
    'advanced_analytics',
    'beta_features',
], $context);

// Returns: ['new_dashboard' => true, 'advanced_analytics' => false, 'beta_features' => true]
```

## Flag Strategies

### 1. System-Wide

Enabled/disabled for all users globally.

```php
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;
use Nexus\FeatureFlags\Enums\FlagStrategy;

$flag = new FlagDefinition(
    name: 'maintenance_mode',
    enabled: true,
    strategy: FlagStrategy::SYSTEM_WIDE,
    value: null
);
```

### 2. Percentage Rollout

Gradually roll out to a percentage of users based on stable identifier.

```php
$flag = new FlagDefinition(
    name: 'new_checkout',
    enabled: true,
    strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
    value: 25 // 25% of users
);

// Requires stable identifier in context
$context = new EvaluationContext(userId: 'user-123');
$enabled = $manager->isEnabled('new_checkout', $context);
```

### 3. Tenant List

Enabled only for specific tenants.

```php
$flag = new FlagDefinition(
    name: 'premium_module',
    enabled: true,
    strategy: FlagStrategy::TENANT_LIST,
    value: ['tenant-abc', 'tenant-xyz']
);

$context = new EvaluationContext(tenantId: 'tenant-abc');
$enabled = $manager->isEnabled('premium_module', $context); // true
```

### 4. User List

Enabled only for specific users.

```php
$flag = new FlagDefinition(
    name: 'beta_tester_access',
    enabled: true,
    strategy: FlagStrategy::USER_LIST,
    value: ['user-alice', 'user-bob']
);
```

### 5. Custom Evaluator (Advanced)

Use custom business logic for complex targeting.

```php
use Nexus\FeatureFlags\Contracts\CustomEvaluatorInterface;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;

class PremiumMalaysianUsersEvaluator implements CustomEvaluatorInterface
{
    public function evaluate(EvaluationContext $context): bool
    {
        $plan = $context->customAttributes['plan'] ?? null;
        $country = $context->customAttributes['country'] ?? null;
        
        return $plan === 'premium' && $country === 'MY';
    }
}

$flag = new FlagDefinition(
    name: 'malaysia_premium_features',
    enabled: true,
    strategy: FlagStrategy::CUSTOM,
    value: PremiumMalaysianUsersEvaluator::class
);
```

## Override Precedence (Kill Switches)

Force flags ON or OFF regardless of strategy for emergency control.

```php
use Nexus\FeatureFlags\Enums\FlagOverride;

// Emergency kill switch - disables feature even if enabled=true
$flag = new FlagDefinition(
    name: 'problematic_feature',
    enabled: true,
    strategy: FlagStrategy::SYSTEM_WIDE,
    value: null,
    override: FlagOverride::FORCE_OFF
);

$manager->isEnabled('problematic_feature'); // Always returns false

// Force enable during testing
$flag = new FlagDefinition(
    name: 'test_feature',
    enabled: false,
    strategy: FlagStrategy::SYSTEM_WIDE,
    value: null,
    override: FlagOverride::FORCE_ON
);

$manager->isEnabled('test_feature'); // Always returns true
```

## Tenant Inheritance

Tenant-specific flags automatically override global defaults.

```php
// Global default: disabled for all tenants
$globalFlag = new FlagDefinition(
    name: 'new_reporting',
    enabled: false,
    strategy: FlagStrategy::SYSTEM_WIDE,
    value: null
    // tenant_id: null
);

// Enable only for tenant-123
$tenantFlag = new FlagDefinition(
    name: 'new_reporting',
    enabled: true,
    strategy: FlagStrategy::SYSTEM_WIDE,
    value: null
    // tenant_id: 'tenant-123'
);

// Tenant-123 sees enabled, all others see disabled
$context = new EvaluationContext(tenantId: 'tenant-123');
$manager->isEnabled('new_reporting', $context); // true

$context = new EvaluationContext(tenantId: 'tenant-456');
$manager->isEnabled('new_reporting', $context); // false (uses global)
```

## Name Validation

Flag names must follow strict pattern for consistency and safety:

- **Pattern:** `/^[a-z0-9_\.]{1,100}$/`
- **Valid:** `new_feature`, `module.analytics`, `beta_v2.checkout`
- **Invalid:** `NewFeature` (uppercase), `feature-name` (hyphens), `very_long_name...` (>100 chars)

```php
// Valid
new FlagDefinition(name: 'analytics.dashboard.v2', ...);

// Throws InvalidFlagDefinitionException
new FlagDefinition(name: 'Invalid-Name', ...);
```

## Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test -- --coverage-html coverage

# Run only unit tests
composer test -- --testsuite=Unit

# Run only integration tests
composer test -- --testsuite=Integration
```

## Framework Integration Examples

### Laravel Setup

After installation, the `FeatureFlagServiceProvider` auto-registers. Configure in `config/feature-flags.php`:

```php
return [
    'cache_store' => env('FEATURE_FLAGS_CACHE_STORE', 'redis'),
    'cache_ttl' => env('FEATURE_FLAGS_CACHE_TTL', 300), // 5 minutes
    'default_if_not_found' => env('FEATURE_FLAGS_DEFAULT_IF_NOT_FOUND', false),
    'enable_monitoring' => env('FEATURE_FLAGS_ENABLE_MONITORING', true),
];
```

**API Usage:**

```php
// In controllers
public function __construct(
    private readonly FeatureFlagManagerInterface $flags
) {}

public function show(Request $request): JsonResponse
{
    $context = [
        'tenantId' => $request->user()->tenant_id,
        'userId' => $request->user()->id,
    ];
    
    if ($this->flags->isEnabled('premium.analytics', $context)) {
        return response()->json(['data' => $this->getPremiumAnalytics()]);
    }
    
    return response()->json(['data' => $this->getBasicAnalytics()]);
}
```

**Blade Directives (Optional Custom Helper):**

```php
// In AppServiceProvider
Blade::directive('featureFlag', function ($expression) {
    return "<?php if(app(FeatureFlagManagerInterface::class)->isEnabled($expression)): ?>";
});

Blade::directive('endfeatureFlag', function () {
    return "<?php endif; ?>";
});
```

```blade
@featureFlag('new.ui')
    <div class="new-dashboard">New UI!</div>
@endfeatureFlag
@else
    <div class="old-dashboard">Legacy UI</div>
@endif
```

### Symfony Setup

**services.yaml:**

```yaml
services:
    # Repository (choose one)
    Nexus\FeatureFlags\Contracts\FlagRepositoryInterface:
        class: App\FeatureFlags\DoctrineFlagRepository
        arguments:
            - '@doctrine.orm.entity_manager'
    
    # Cache adapter
    Nexus\FeatureFlags\Contracts\FlagCacheInterface:
        class: App\FeatureFlags\SymfonyCacheAdapter
        arguments:
            - '@cache.app'
    
    # Core services
    Nexus\FeatureFlags\Core\Engine\PercentageHasher: ~
    
    Nexus\FeatureFlags\Core\Engine\DefaultFlagEvaluator:
        arguments:
            - '@Nexus\FeatureFlags\Core\Engine\PercentageHasher'
    
    Nexus\FeatureFlags\Services\FeatureFlagManager:
        arguments:
            - '@Nexus\FeatureFlags\Contracts\FlagRepositoryInterface'
            - '@Nexus\FeatureFlags\Core\Engine\DefaultFlagEvaluator'
            - '@logger'
    
    # Alias for type-hinting
    Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface:
        alias: Nexus\FeatureFlags\Services\FeatureFlagManager
```

**Controller Usage:**

```php
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly FeatureFlagManagerInterface $flags
    ) {}
    
    #[Route('/dashboard')]
    public function index(RequestStack $requestStack): Response
    {
        $context = [
            'userId' => $this->getUser()?->getId(),
            'tenantId' => $requestStack->getCurrentRequest()?->attributes->get('tenant_id'),
        ];
        
        return $this->render('dashboard/index.html.twig', [
            'use_new_ui' => $this->flags->isEnabled('dashboard.v2', $context),
        ]);
    }
}
```

**Twig Extension (Optional):**

```php
namespace App\Twig;

use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeatureFlagExtension extends AbstractExtension
{
    public function __construct(
        private readonly FeatureFlagManagerInterface $flags
    ) {}
    
    public function getFunctions(): array
    {
        return [
            new TwigFunction('feature_enabled', [$this, 'isEnabled']),
        ];
    }
    
    public function isEnabled(string $flagName, array $context = []): bool
    {
        return $this->flags->isEnabled($flagName, $context);
    }
}
```

```twig
{% if feature_enabled('new.checkout') %}
    <div class="new-checkout">Enhanced Checkout</div>
{% else %}
    <div class="old-checkout">Classic Checkout</div>
{% endif %}
```

### Slim Framework Setup

```php
use Nexus\FeatureFlags\Services\FeatureFlagManager;
use Nexus\FeatureFlags\Core\Engine\DefaultFlagEvaluator;
use Nexus\FeatureFlags\Core\Repository\InMemoryFlagRepository;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;

$container = new \DI\Container();

// Register feature flag services
$container->set(FlagRepositoryInterface::class, function() {
    return new InMemoryFlagRepository(); // Or your custom implementation
});

$container->set(FeatureFlagManagerInterface::class, function(ContainerInterface $c) {
    return new FeatureFlagManager(
        $c->get(FlagRepositoryInterface::class),
        new DefaultFlagEvaluator(new PercentageHasher()),
        $c->get(LoggerInterface::class)
    );
});

AppFactory::setContainer($container);
$app = AppFactory::create();

// Use in routes
$app->get('/dashboard', function (Request $request, Response $response) use ($container) {
    $flags = $container->get(FeatureFlagManagerInterface::class);
    
    $context = [
        'userId' => $request->getAttribute('user_id'),
    ];
    
    if ($flags->isEnabled('beta.features', $context)) {
        return $response->withJson(['version' => 'beta']);
    }
    
    return $response->withJson(['version' => 'stable']);
});
```

### Standalone PHP Setup

```php
<?php

require 'vendor/autoload.php';

use Nexus\FeatureFlags\Services\FeatureFlagManager;
use Nexus\FeatureFlags\Core\Engine\DefaultFlagEvaluator;
use Nexus\FeatureFlags\Core\Engine\PercentageHasher;
use Nexus\FeatureFlags\Core\Repository\InMemoryFlagRepository;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Psr\Log\NullLogger;

// Setup repository and add flags
$repository = new InMemoryFlagRepository();

$repository->save(FlagDefinition::create(
    name: 'new.feature',
    enabled: true,
    strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
    value: 50 // 50% rollout
));

// Create manager
$manager = new FeatureFlagManager(
    repository: $repository,
    evaluator: new DefaultFlagEvaluator(new PercentageHasher()),
    logger: new NullLogger()
);

// Evaluate flags
$context = ['userId' => 'user-12345'];

if ($manager->isEnabled('new.feature', $context)) {
    echo "You're in the 50% rollout group!\n";
} else {
    echo "Not yet enabled for you.\n";
}
```

## Compliance & Audit Trail

For regulatory compliance (SOX, GDPR, etc.), the package provides optional audit interfaces that enable complete change tracking and historical state queries.

### Audit Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    FeatureFlagManager                           │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │ FlagRepository  │  │ FlagAuditChange │  │ FlagAuditQuery  │ │
│  │   Interface     │  │   Interface     │  │   Interface     │ │
│  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘ │
└───────────│───────────────────│───────────────────│────────────┘
            │                   │                   │
  ┌─────────▼────────┐ ┌───────▼────────┐ ┌───────▼────────┐
  │ Database/Redis   │ │ Nexus\Audit    │ │ Nexus\Event    │
  │ Implementation   │ │ Logger         │ │ Stream         │
  └──────────────────┘ └────────────────┘ └────────────────┘
```

### FlagAuditChangeInterface (Write Audit)

Records all feature flag modifications using `Nexus\AuditLogger`:

```php
use Nexus\FeatureFlags\Contracts\FlagAuditChangeInterface;
use Nexus\FeatureFlags\Enums\AuditAction;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;

// Application layer implementation
final readonly class FeatureFlagAuditLogger implements FlagAuditChangeInterface
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogger,
        private TenantContextInterface $tenantContext
    ) {}

    public function recordChange(
        string $flagName,
        AuditAction $action,
        ?string $userId,
        ?array $before,
        ?array $after,
        array $metadata = []
    ): void {
        $this->auditLogger->create([
            'log_name' => 'feature_flags',
            'subject_type' => 'feature_flag',
            'subject_id' => $flagName,
            'causer_type' => 'user',
            'causer_id' => $userId,
            'event' => $action->value,
            'description' => $action->getDescription(),
            'properties' => [
                'before' => $before,
                'after' => $after,
                ...$metadata,
            ],
            'level' => $action->isCritical() ? 4 : 2, // Critical = 4, Medium = 2
            'tenant_id' => $this->tenantContext->getCurrentTenantId(),
        ]);
    }

    public function recordBatchChange(
        AuditAction $action,
        ?string $userId,
        array $changes,
        array $metadata = []
    ): void {
        // Generate batch ID using your preferred method:
        // - Symfony: (new \Symfony\Component\Uid\Ulid())->__toString()
        // - ramsey/uuid: (string) \Ramsey\Uuid\Uuid::uuid7()
        // - Laravel: (string) Str::ulid()
        $batchId = $this->generateBatchId();
        
        foreach ($changes as $flagName => $change) {
            $this->recordChange(
                $flagName,
                $action,
                $userId,
                $change['before'],
                $change['after'],
                [...$metadata, 'batch_id' => $batchId]
            );
        }
    }

    private function generateBatchId(): string
    {
        // Implement using your preferred ULID/UUID library
        return (new \Symfony\Component\Uid\Ulid())->__toString();
    }
}
```

### FlagAuditQueryInterface (Read Audit)

Query historical flag states using `Nexus\EventStream` for compliance audits:

```php
use Nexus\FeatureFlags\Contracts\FlagAuditQueryInterface;
use Nexus\FeatureFlags\Contracts\FlagAuditRecordInterface;
use Nexus\FeatureFlags\Enums\AuditAction;
use Nexus\EventStream\Contracts\EventStoreInterface;

// Application layer implementation
final readonly class FeatureFlagAuditQuery implements FlagAuditQueryInterface
{
    public function __construct(
        private EventStoreInterface $eventStore
    ) {}

    public function getHistory(
        string $flagName,
        ?string $tenantId = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        return $this->eventStore->query(
            filters: [
                'aggregate_id' => ['operator' => '=', 'value' => "flag:{$flagName}"],
            ],
            inFilters: [],
            orderByField: 'occurred_at',
            orderDirection: 'desc',
            limit: $limit,
            cursorData: null
        );
    }

    public function getStateAt(
        string $flagName,
        DateTimeImmutable $timestamp,
        ?string $tenantId = null
    ): ?array {
        // Replay events up to timestamp to reconstruct state
        $events = $this->eventStore->query(
            filters: [
                'aggregate_id' => ['operator' => '=', 'value' => "flag:{$flagName}"],
                'occurred_at' => ['operator' => '<=', 'value' => $timestamp->format('Y-m-d H:i:s')],
            ],
            inFilters: [],
            orderByField: 'occurred_at',
            orderDirection: 'asc',
            limit: 10000
        );
        
        if (empty($events)) {
            return null;
        }
        
        // Reconstruct state by replaying events
        return $this->reconstructState($events);
    }

    public function getCriticalChanges(
        ?string $tenantId = null,
        ?DateTimeImmutable $since = null,
        int $limit = 500
    ): array {
        $filters = [];
        
        if ($since !== null) {
            $filters['occurred_at'] = ['operator' => '>=', 'value' => $since->format('Y-m-d H:i:s')];
        }
        
        $criticalActions = [
            AuditAction::FORCE_DISABLED->value,
            AuditAction::FORCE_ENABLED->value,
            AuditAction::DELETED->value,
            AuditAction::OVERRIDE_CHANGED->value,
        ];
        
        return $this->eventStore->query(
            filters: $filters,
            inFilters: ['event_type' => $criticalActions],
            orderByField: 'occurred_at',
            orderDirection: 'desc',
            limit: $limit
        );
    }
    
    // ... other methods
}
```

### Using AuditableFlagRepository

The `AuditableFlagRepository` decorator automatically records all changes:

```php
use Nexus\FeatureFlags\Services\AuditableFlagRepository;

// Wrap your repository for automatic audit logging
$auditableRepo = new AuditableFlagRepository(
    repository: $baseRepository,
    auditChange: $auditChangeLogger,
    userId: $currentUser->getId()
);

// Set tenant context
$auditableRepo = $auditableRepo->withTenantId($tenantId);

// Now all save/delete operations are automatically audited
$auditableRepo->save($flag); // Records CREATED or appropriate action
$auditableRepo->delete('old_flag', $tenantId); // Records DELETED
```

### Audit Actions

The `AuditAction` enum tracks all possible flag modifications:

| Action | Description | Critical |
|--------|-------------|----------|
| `CREATED` | Flag was created | No |
| `UPDATED` | Flag was updated (generic) | No |
| `DELETED` | Flag was deleted | **Yes** |
| `ENABLED_CHANGED` | Flag enabled state toggled | No |
| `STRATEGY_CHANGED` | Evaluation strategy changed | No |
| `OVERRIDE_CHANGED` | Override state changed | **Yes** |
| `FORCE_ENABLED` | FORCE_ON override applied | **Yes** |
| `FORCE_DISABLED` | FORCE_OFF override (kill switch) | **Yes** |
| `OVERRIDE_CLEARED` | Override removed | No |
| `ROLLOUT_CHANGED` | Percentage rollout changed | No |
| `TARGET_LIST_CHANGED` | Tenant/user list changed | No |

### Checking Audit Availability

```php
// Check if audit capabilities are configured
if ($manager->hasAuditChange()) {
    // Audit change logging is available
}

if ($manager->hasAuditQuery()) {
    // Historical queries are available
    $query = $manager->getAuditQuery();
    
    // Get flag history
    $history = $query->getHistory('payment_v2', 'tenant-123');
    
    // Compliance audit: What was the state during an incident?
    $stateAtIncident = $query->getStateAt(
        'payment_v2',
        new DateTimeImmutable('2024-11-15 14:30:00'),
        'tenant-123'
    );
    
    // Get all critical changes this month
    $criticalChanges = $query->getCriticalChanges(
        tenantId: 'tenant-123',
        since: new DateTimeImmutable('first day of this month')
    );
}
```

### Laravel Service Provider Setup

```php
// AppServiceProvider.php
public function register(): void
{
    // Register audit change logger (uses Nexus\AuditLogger)
    $this->app->singleton(FlagAuditChangeInterface::class, function ($app) {
        return new FeatureFlagAuditLogger(
            $app->make(AuditLogRepositoryInterface::class),
            $app->make(TenantContextInterface::class)
        );
    });

    // Register audit query (uses Nexus\EventStream)
    $this->app->singleton(FlagAuditQueryInterface::class, function ($app) {
        return new FeatureFlagAuditQuery(
            $app->make(EventStoreInterface::class)
        );
    });

    // Register manager with audit support
    $this->app->singleton(FeatureFlagManagerInterface::class, function ($app) {
        return new FeatureFlagManager(
            $app->make(FlagRepositoryInterface::class),
            $app->make(FlagEvaluatorInterface::class),
            $app->make(LoggerInterface::class),
            $app->make(FlagAuditChangeInterface::class), // Optional
            $app->make(FlagAuditQueryInterface::class)   // Optional
        );
    });
}
```

## Requirements

- PHP 8.3+
- PSR-3 Logger implementation
- (Optional) Nexus\Monitoring for metrics
- (Optional) Nexus\AuditLogger for change audit trail
- (Optional) Nexus\EventStream for historical state queries

## 📖 Documentation

### Core Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start with prerequisites, 5 strategies, and troubleshooting
- **[API Reference](docs/api-reference.md)** - Complete interface and service documentation
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples

### Implementation Details
- **[Requirements](REQUIREMENTS.md)** - Complete requirements tracking with status
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Progress metrics and design decisions
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - Test coverage and testing strategy
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package valuation metrics ($145K value, 1,364% ROI)

### Code Examples
- **[Basic Usage Examples](docs/examples/basic-usage.php)** - Simple feature flag operations
- **[Advanced Usage Examples](docs/examples/advanced-usage.php)** - Custom evaluators, percentage analysis

## Quick Links

- **Package Reference**: [`docs/NEXUS_PACKAGES_REFERENCE.md`](../../docs/NEXUS_PACKAGES_REFERENCE.md)
- **Architecture Overview**: [`ARCHITECTURE.md`](../../ARCHITECTURE.md)
- **Coding Standards**: [`.github/copilot-instructions.md`](../../.github/copilot-instructions.md)

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Security

If you discover any security-related issues, please email security@nexus.local instead of using the issue tracker.

## Credits

- [Nexus Team](https://github.com/nexus)
- [All Contributors](../../contributors)
