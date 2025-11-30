# Getting Started with Nexus\FeatureFlags

## Overview

`Nexus\FeatureFlags` is a production-grade feature flag management system that enables safe feature deployments, A/B testing, gradual rollouts, and emergency kill switches. This package provides framework-agnostic contracts and services that integrate seamlessly with Laravel, Symfony, and other PHP frameworks.

---

## Prerequisites

- **PHP 8.3 or higher**
- **Composer**
- **Database** (MySQL, PostgreSQL, SQLite)
- **Cache System** (Redis, Memcached, or file-based - optional but recommended)
- **Nexus\Tenant package** (for multi-tenant applications)

---

## Installation

```bash
composer require nexus/feature-flags:"*@dev"
```

---

## When to Use This Package

This package is designed for:
- ✅ **Gradual Feature Rollouts** - Release features to 10%, 50%, 100% of users
- ✅ **A/B Testing** - Test different features with different user segments
- ✅ **Kill Switches** - Emergency disable features in production
- ✅ **Tenant-Specific Features** - Enable features for specific tenants only
- ✅ **User-Specific Features** - Beta access for specific users
- ✅ **Environment-Specific Features** - Different features per environment

Do NOT use this package for:
- ❌ **Application Configuration** - Use `Nexus\Setting` instead
- ❌ **User Permissions** - Use `Nexus\Identity` authorization instead
- ❌ **Business Rules** - Feature flags should be temporary, not permanent business logic

---

## Core Concepts

### 1. Feature Flag Strategies

The package supports **5 evaluation strategies:**

#### System-Wide (Boolean)
```php
// Enable for everyone OR disable for everyone
$flag = new FlagDefinition(
    key: 'feature.new_ui',
    strategy: FlagStrategy::SystemWide,
    enabled: true // or false
);
```

#### Percentage Rollout
```php
// Enable for 50% of users (consistent hashing)
$flag = new FlagDefinition(
    key: 'feature.beta_dashboard',
    strategy: FlagStrategy::PercentageRollout,
    percentage: 50 // 0-100
);
```

#### Tenant List (Allow List)
```php
// Enable for specific tenants only
$flag = new FlagDefinition(
    key: 'feature.advanced_reporting',
    strategy: FlagStrategy::TenantList,
    tenantIds: ['tenant-001', 'tenant-002', 'tenant-003']
);
```

#### User List (Allow List)
```php
// Enable for specific users (beta testers)
$flag = new FlagDefinition(
    key: 'feature.experimental_export',
    strategy: FlagStrategy::UserList,
    userIds: ['user-123', 'user-456']
);
```

#### Custom Evaluator
```php
// Use custom business logic
$flag = new FlagDefinition(
    key: 'feature.premium_features',
    strategy: FlagStrategy::CustomEvaluator,
    evaluatorName: 'PremiumSubscriptionEvaluator'
);
```

---

### 2. Fail-Closed Security

**By design, unknown flags return `false` (disabled):**

```php
$result = $manager->evaluate('non_existent_flag', $context);
// Returns: false (not enabled)
```

This prevents accidental feature exposure if flag definitions are missing.

---

### 3. Kill Switches (Force Override)

Emergency controls to force flags ON or OFF:

```php
$flag = new FlagDefinition(
    key: 'feature.buggy_feature',
    strategy: FlagStrategy::SystemWide,
    enabled: true,
    override: FlagOverride::ForceOff // Kill switch!
);

$result = $manager->evaluate('feature.buggy_feature', $context);
// Returns: false (forced off despite enabled=true)
```

**Override Options:**
- `FlagOverride::None` - Normal evaluation
- `FlagOverride::ForceOn` - Always enabled (ignores strategy)
- `FlagOverride::ForceOff` - Always disabled (ignores strategy)

---

### 4. Tenant Inheritance

**Tenant-specific flags override global flags:**

```php
// Global flag: enabled for 50%
$globalFlag = new FlagDefinition(
    key: 'feature.new_ui',
    tenantId: null, // global
    strategy: FlagStrategy::PercentageRollout,
    percentage: 50
);

// Tenant-specific override: enabled for 100%
$tenantFlag = new FlagDefinition(
    key: 'feature.new_ui',
    tenantId: 'tenant-premium-001',
    strategy: FlagStrategy::SystemWide,
    enabled: true
);

// For tenant-premium-001: always enabled
// For other tenants: 50% rollout
```

---

### 5. Performance Optimizations

#### Request-Level Memoization
```php
// Same flag + context evaluated twice = only 1 database query
$result1 = $manager->evaluate('feature.new_ui', $context);
$result2 = $manager->evaluate('feature.new_ui', $context); // Cached!
```

#### Bulk Evaluation
```php
// Evaluate multiple flags in one database query
$results = $manager->evaluateBulk([
    'feature.new_ui',
    'feature.advanced_export',
    'feature.beta_dashboard',
], $context);

// Returns: ['feature.new_ui' => true, 'feature.advanced_export' => false, ...]
```

#### Checksum Validation
```php
// Cached flags include checksums to detect stale data
$cachedFlag = $cache->get('flag:feature.new_ui');

if ($cachedFlag->checksum !== $freshFlag->checksum) {
    throw new StaleCacheException(); // Force cache refresh
}
```

---

## Basic Configuration

### Step 1: Create Database Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 26)->nullable()->index(); // null = global
            $table->string('key')->index(); // e.g., 'feature.new_ui'
            $table->string('strategy'); // SystemWide, PercentageRollout, etc.
            $table->boolean('enabled')->default(false);
            $table->integer('percentage')->nullable(); // 0-100 for PercentageRollout
            $table->json('tenant_ids')->nullable(); // For TenantList strategy
            $table->json('user_ids')->nullable(); // For UserList strategy
            $table->string('evaluator_name')->nullable(); // For CustomEvaluator
            $table->string('override')->default('none'); // none, force_on, force_off
            $table->string('checksum', 64); // SHA-256 hash for cache validation
            $table->timestamps();
            
            $table->unique(['tenant_id', 'key']); // One flag per tenant per key
        });
    }
};
```

---

### Step 2: Create Eloquent Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;

class FeatureFlag extends Model implements FlagDefinitionInterface
{
    protected $fillable = [
        'tenant_id',
        'key',
        'strategy',
        'enabled',
        'percentage',
        'tenant_ids',
        'user_ids',
        'evaluator_name',
        'override',
        'checksum',
    ];
    
    protected $casts = [
        'enabled' => 'boolean',
        'percentage' => 'integer',
        'tenant_ids' => 'array',
        'user_ids' => 'array',
        'strategy' => FlagStrategy::class,
        'override' => FlagOverride::class,
    ];
    
    protected static function booted()
    {
        static::saving(function (FeatureFlag $flag) {
            // Auto-generate checksum on save
            $flag->checksum = hash('sha256', json_encode([
                $flag->key,
                $flag->strategy->value,
                $flag->enabled,
                $flag->percentage,
                $flag->tenant_ids,
                $flag->user_ids,
                $flag->override->value,
            ]));
        });
    }
    
    public function toFlagDefinition(): FlagDefinition
    {
        return new FlagDefinition(
            key: $this->key,
            tenantId: $this->tenant_id,
            strategy: $this->strategy,
            enabled: $this->enabled,
            percentage: $this->percentage,
            tenantIds: $this->tenant_ids ?? [],
            userIds: $this->user_ids ?? [],
            evaluatorName: $this->evaluator_name,
            override: $this->override,
            checksum: $this->checksum
        );
    }
}
```

---

### Step 3: Create Repository Implementation

```php
<?php

namespace App\Repositories;

use App\Models\FeatureFlag;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;
use Nexus\FeatureFlags\Exceptions\FlagNotFoundException;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class EloquentFlagRepository implements FlagRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}
    
    public function findByKey(string $key, ?string $tenantId = null): FlagDefinition
    {
        // Try tenant-specific flag first
        $tenantId = $tenantId ?? $this->tenantContext->getCurrentTenantId();
        
        $flag = FeatureFlag::query()
            ->where('key', $key)
            ->where('tenant_id', $tenantId)
            ->first();
        
        // Fallback to global flag
        if (!$flag) {
            $flag = FeatureFlag::query()
                ->where('key', $key)
                ->whereNull('tenant_id')
                ->first();
        }
        
        if (!$flag) {
            throw FlagNotFoundException::forKey($key);
        }
        
        return $flag->toFlagDefinition();
    }
    
    public function findBulk(array $keys, ?string $tenantId = null): array
    {
        $tenantId = $tenantId ?? $this->tenantContext->getCurrentTenantId();
        
        $flags = FeatureFlag::query()
            ->whereIn('key', $keys)
            ->where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                      ->orWhereNull('tenant_id');
            })
            ->get();
        
        $result = [];
        foreach ($keys as $key) {
            // Prefer tenant-specific over global
            $tenantFlag = $flags->first(fn($f) => $f->key === $key && $f->tenant_id === $tenantId);
            $globalFlag = $flags->first(fn($f) => $f->key === $key && $f->tenant_id === null);
            
            $flag = $tenantFlag ?? $globalFlag;
            
            if ($flag) {
                $result[$key] = $flag->toFlagDefinition();
            }
        }
        
        return $result;
    }
}
```

---

### Step 4: Bind Interfaces in Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\Services\FeatureFlagManager;
use App\Repositories\EloquentFlagRepository;

class FeatureFlagServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            FlagRepositoryInterface::class,
            EloquentFlagRepository::class
        );
        
        // Bind manager
        $this->app->singleton(
            FeatureFlagManagerInterface::class,
            FeatureFlagManager::class
        );
    }
}
```

---

### Step 5: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\FeatureFlagServiceProvider::class,
],
```

---

## Your First Integration

### Example 1: Simple Feature Toggle

```php
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;

class DashboardController
{
    public function __construct(
        private readonly FeatureFlagManagerInterface $flags
    ) {}
    
    public function index()
    {
        $context = new EvaluationContext(
            tenantId: auth()->user()->tenant_id,
            userId: auth()->id(),
            attributes: []
        );
        
        if ($this->flags->evaluate('feature.new_dashboard', $context)) {
            return view('dashboard.new'); // New UI
        }
        
        return view('dashboard.legacy'); // Old UI
    }
}
```

### Example 2: Percentage Rollout

```php
// In database seeder or admin panel
FeatureFlag::create([
    'key' => 'feature.beta_export',
    'strategy' => FlagStrategy::PercentageRollout,
    'percentage' => 25, // 25% of users
    'enabled' => true,
]);

// In controller
if ($this->flags->evaluate('feature.beta_export', $context)) {
    return $this->exportUsingNewEngine(); // 25% get new engine
}

return $this->exportUsingLegacyEngine(); // 75% get old engine
```

### Example 3: Kill Switch

```php
// Emergency: disable buggy feature
FeatureFlag::where('key', 'feature.buggy_feature')
    ->update(['override' => FlagOverride::ForceOff]);

// Now all evaluations return false regardless of strategy
$result = $this->flags->evaluate('feature.buggy_feature', $context);
// Returns: false
```

---

## Troubleshooting

### Issue 1: Flag Not Found Exception

**Error:** `FlagNotFoundException: Flag with key "feature.new_ui" not found`

**Cause:** Flag not created in database.

**Solution:**
```php
// Create the flag
FeatureFlag::create([
    'key' => 'feature.new_ui',
    'strategy' => FlagStrategy::SystemWide,
    'enabled' => false, // Start disabled
]);
```

---

### Issue 2: Percentage Rollout Not Consistent

**Problem:** Same user gets different results on each request.

**Cause:** Hashing depends on tenant_id or user_id in context.

**Solution:**
```php
// Always provide tenant_id OR user_id for consistent hashing
$context = new EvaluationContext(
    tenantId: $tenantId, // Required for consistency
    userId: $userId,      // Or this
    attributes: []
);
```

---

### Issue 3: Stale Cache Exception

**Error:** `StaleCacheException: Cached flag checksum mismatch`

**Cause:** Flag updated in database but cache not invalidated.

**Solution:** Cache decorator automatically refetches. If using custom cache:
```php
// Invalidate cache after update
Cache::forget("flag:{$flagKey}");
```

---

### Issue 4: Tenant-Specific Flag Not Working

**Problem:** Tenant-specific flag not overriding global flag.

**Cause:** Repository returns global flag instead of tenant flag.

**Solution:** Ensure repository prioritizes tenant-specific flags:
```php
// Correct order: tenant-specific FIRST, then global
$tenantFlag = FeatureFlag::where('key', $key)->where('tenant_id', $tenantId)->first();
$globalFlag = FeatureFlag::where('key', $key)->whereNull('tenant_id')->first();

return $tenantFlag ?? $globalFlag; // Prefer tenant-specific
```

---

### Issue 5: Custom Evaluator Not Called

**Error:** Custom evaluator strategy returns false.

**Cause:** Custom evaluator not registered.

**Solution:**
```php
// Register custom evaluator in service provider
$this->app->bind('PremiumSubscriptionEvaluator', function () {
    return new PremiumSubscriptionEvaluator($this->app->make(SubscriptionRepository::class));
});
```

---

## Performance Tips

### 1. Use Bulk Evaluation for Multiple Flags

```php
// ❌ SLOW: 3 database queries
$flag1 = $manager->evaluate('feature.export', $context);
$flag2 = $manager->evaluate('feature.import', $context);
$flag3 = $manager->evaluate('feature.analytics', $context);

// ✅ FAST: 1 database query
$flags = $manager->evaluateBulk([
    'feature.export',
    'feature.import',
    'feature.analytics',
], $context);
```

### 2. Enable Request-Level Memoization

Memoization is built-in and automatic. Same flag + context = cached result within request.

### 3. Use Repository Caching Decorator

```php
use Nexus\FeatureFlags\Core\Decorators\CachedFlagRepository;

$this->app->singleton(FlagRepositoryInterface::class, function ($app) {
    $baseRepository = new EloquentFlagRepository($app->make(TenantContextInterface::class));
    
    return new CachedFlagRepository(
        repository: $baseRepository,
        cache: $app->make('cache.store')
    );
});
```

---

## Next Steps

- **[API Reference](api-reference.md)** - Complete documentation of all interfaces and services
- **[Integration Guide](integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Examples](examples/basic-usage.php)** - 10+ practical code examples
- **[Advanced Usage Examples](examples/advanced-usage.php)** - Advanced patterns and optimizations

---

**Last Updated:** November 24, 2025  
**Package Version:** 1.0.0
