# Getting Started

## Installation

```bash
# Getting Started with Nexus\Setting

The `Nexus\Setting` package delivers a framework-agnostic settings engine with hierarchical resolution (User → Tenant → Application), schema-aware validation, and cache-friendly read paths. This guide walks through installation, configuration, and your first integration.

## Prerequisites

- PHP **8.3** or higher
- Composer 2.x
- PSR-compliant cache adapter (PSR-16 or PSR-6 wrapped via `SettingsCacheInterface` implementation)
- Basic understanding of dependency injection within your host framework (Laravel, Symfony, Slim, etc.)
- Access to tenant and user identifiers if you plan to use scoped settings

## Installation

```bash
composer require nexus/setting:"*@dev"
```

Update your application's `composer.json` to point to the local path repository if you are working inside the Nexus monorepo:

```json
{
	"repositories": [
		{
			"type": "path",
			"url": "../../packages/Setting"
		}
	],
	"require": {
		"nexus/setting": "*@dev"
	}
}
```

## When to Use This Package

✅ Multi-tenant applications needing isolated configuration per tenant or user

✅ SaaS dashboards where administrators can override defaults without redeploying code

✅ Systems that require auditability, caching, and validation before persisting settings

❌ Do **not** use this package to store large binary objects or unstructured blobs (use `Nexus\Storage` instead)

❌ Do **not** bypass the provided contracts to talk directly to a database driver—keep framework-specific logic in the application layer

## Core Concepts

| Concept | Description |
| --- | --- |
| **Setting Layers** | `user`, `tenant`, and `application` layers define override priority. Application is read-only and acts as the ultimate default. |
| **Scopes** | A `SettingScope` value object wraps a layer plus identifier (`tenant-123`, `user-456`). It is used for cache keys and write-protection logic. |
| **Repositories** | Implement `SettingRepositoryInterface` for each layer. They abstract persistence and enforce CQRS boundaries. |
| **Cache Manager** | `SettingsCacheManager` wraps a `SettingsCacheInterface` implementation to cache hierarchical lookups and scope invalidation. |
| **Schema Registry** | Register metadata and validation rules via `SettingsSchemaRegistry` + `SettingsValidationService` before persisting changes. |

## Quick Start Checklist

1. **Implement Repositories** for user, tenant, and application layers.
2. **Implement a Cache Adapter** by wrapping your framework cache in `SettingsCacheInterface`.
3. **Bind Interfaces** in your DI container.
4. **Instantiate `SettingsManager`** and expose it to application services/controllers.

### Step 1: Implement Repositories

```php
use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Exceptions\ReadOnlySettingException;

final readonly class TenantSettingRepository implements SettingRepositoryInterface
{
	public function __construct(private TenantSettingModel $model) {}

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->model->forTenant($this->tenantId)->where('key', $key)->value('value') ?? $default;
	}

	public function set(string $key, mixed $value): void
	{
		$this->model->upsert([...]);
	}

	// Implement the remaining methods defined in the contract...
}
```

Create separate classes for user-level and application-level repositories. Application-level implementations should throw `ReadOnlySettingException` when callers attempt to mutate state.

### Step 2: Implement Cache Adapter

```php
use Nexus\Setting\Contracts\SettingsCacheInterface;

final readonly class LaravelSettingsCache implements SettingsCacheInterface
{
	public function __construct(private \Illuminate\Contracts\Cache\Repository $cache) {}

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->cache->get($key, $default);
	}

	public function set(string $key, mixed $value, ?int $ttl = null): void
	{
		$this->cache->put($key, $value, $ttl);
	}

	// Implement forget, has, flush, remember, forgetPattern...
}
```

### Step 3: Bind Interfaces in Your Container

```php
// Laravel service provider
$this->app->bind(SettingRepositoryInterface::class.'$user', UserSettingRepository::class);
$this->app->bind(SettingRepositoryInterface::class.'$tenant', TenantSettingRepository::class);
$this->app->bind(SettingRepositoryInterface::class.'$application', ApplicationSettingRepository::class);
$this->app->singleton(SettingsCacheInterface::class, LaravelSettingsCache::class);
```

### Step 4: Use the Manager

```php
use Nexus\Setting\Services\SettingsManager;

$manager = new SettingsManager(
	userRepository: $userRepo,
	tenantRepository: $tenantRepo,
	applicationRepository: $appRepo,
	cache: $cacheAdapter,
	protectedKeys: ['mail.smtp.password']
);

$currency = $manager->getString('tenant.currency', 'MYR', tenantId: 'tenant-001');
$manager->setTenantSetting('tenant-001', 'tenant.currency', 'USD');
```

## Your First Integration

1. Create a console command or controller action that injects `SettingsManager`.
2. Load the current tenant/user context (from your tenancy middleware).
3. Read/update settings using the helper methods (`getString`, `getBool`, `bulkSet`).
4. Observe caching behavior—settings should be read from cache after the first lookup.
5. Wire audit logging by listening for persistence events in the application layer.

## Troubleshooting

| Symptom | Possible Cause | Fix |
| --- | --- | --- |
| Setting changes do not propagate | Cache not invalidated after `set`/`delete` | Ensure repository implementations throw no exceptions and that `SettingsManager` receives the same cache adapter used in readers |
| `ReadOnlySettingException` thrown for tenant update | Attempting to edit application layer or protected key | Review `protectedKeys` array passed into `SettingsManager` and repository `isWritable` behavior |
| Validation not enforced | No schema registered for key | Register schema via `SettingsSchemaRegistry` and call `SettingsValidationService->validate()` before persisting |

## Next Steps

- Deep dive into the [API Reference](api-reference.md)
- Review framework-specific patterns in the [Integration Guide](integration-guide.md)
- Explore runnable end-to-end snippets in [`docs/examples`](examples)
