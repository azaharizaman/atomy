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

## Requirements

- PHP 8.3+
- PSR-3 Logger implementation
- (Optional) Nexus\Monitoring for metrics
- (Optional) Nexus\AuditLogger for audit trail

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Security

If you discover any security-related issues, please email security@nexus.local instead of using the issue tracker.

## Credits

- [Nexus Team](https://github.com/nexus)
- [All Contributors](../../contributors)
