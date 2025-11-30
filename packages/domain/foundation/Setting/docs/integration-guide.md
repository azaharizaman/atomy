# Integration Guide: Nexus\Setting

This guide illustrates how to wire `Nexus\Setting` into popular PHP frameworks while honoring the Nexus architecture guidelines (framework logic lives outside packages, only interfaces cross boundaries).

## 1. Laravel Integration

### 1.1 Service Provider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Contracts\SettingsCacheInterface;
use Nexus\Setting\Services\SettingsManager;
use App\Settings\Repositories\{UserSettingRepository, TenantSettingRepository, ApplicationSettingRepository};
use App\Settings\Cache\LaravelSettingsCache;

class SettingServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->app->singleton(SettingsCacheInterface::class, LaravelSettingsCache::class);

		$this->app->when(SettingsManager::class)
			->needs('$userRepository')
			->give(fn () => $this->app->make(UserSettingRepository::class));

		$this->app->when(SettingsManager::class)
			->needs('$tenantRepository')
			->give(fn () => $this->app->make(TenantSettingRepository::class));

		$this->app->when(SettingsManager::class)
			->needs('$applicationRepository')
			->give(fn () => $this->app->make(ApplicationSettingRepository::class));
	}

	public function boot(): void
	{
		// Optional: publish config, seed application defaults, etc.
	}
}
```

> **Tip:** Use contextual bindings or tagged services to differentiate between the three repository implementations. Avoid using named facades inside the package—keep them in `apps/` only.

### 1.2 Example Controller

```php
use Nexus\Setting\Services\SettingsManager;

final class TenantSettingController
{
	public function __construct(private readonly SettingsManager $settings) {}

	public function update(string $tenantId, Request $request)
	{
		$validated = $request->validate([
			'timezone' => ['required', 'string'],
			'currency' => ['required', 'string'],
		]);

		$this->settings->bulkSet($validated, SettingLayer::TENANT, $tenantId);

		return response()->json(['status' => 'ok']);
	}
}
```

### 1.3 Queue + Cache Considerations

- Use tenancy middleware to push tenant/user IDs onto the job payload so background workers resolve the same settings.
- Keep cache prefixes unique per environment (e.g., `app(env('APP_ENV')).":setting:"`).
- Configure `SettingsCacheInterface::forgetPattern()` to leverage Redis `SCAN` or Laravel cache tags for efficient invalidation.

## 2. Symfony Integration

### 2.1 services.yaml

```yaml
services:
	App\Settings\Cache\SymfonySettingsCache:
		arguments: ['@cache.app']

	App\Settings\Repository\UserSettingRepository:
		arguments: ['@doctrine.orm.entity_manager']

	App\Settings\Repository\TenantSettingRepository:
		arguments: ['@doctrine.orm.entity_manager']

	App\Settings\Repository\ApplicationSettingRepository:
		arguments: ['@doctrine.orm.entity_manager']

	Nexus\Setting\Services\SettingsManager:
		arguments:
			$userRepository: '@App\Settings\Repository\UserSettingRepository'
			$tenantRepository: '@App\Settings\Repository\TenantSettingRepository'
			$applicationRepository: '@App\Settings\Repository\ApplicationSettingRepository'
			$cache: '@App\Settings\Cache\SymfonySettingsCache'
			$protectedKeys: ['mail.smtp.password', 'billing.processor.secret']
```

### 2.2 Console Command Example

```php
#[AsCommand(name: 'settings:sync-defaults')]
final class SyncDefaultsCommand extends Command
{
	public function __construct(private readonly SettingsManager $manager)
	{
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->manager->bulkSet([
			'ui.language' => 'en',
			'ui.timezone' => 'UTC',
		], SettingLayer::APPLICATION, 'application');

		$output->writeln('<info>Application defaults synced.</info>');

		return Command::SUCCESS;
	}
}
```

## 3. Common Patterns

### 3.1 Multi-Layer Repository Separation

- **User Repository:** Store personal overrides in a `user_settings` table keyed by ULID.
- **Tenant Repository:** Use `tenant_settings` with `tenant_id + key` composite index.
- **Application Repository:** Read from configuration files or environment variables; mark as read-only by throwing `ReadOnlySettingException` inside `set/delete` methods.

### 3.2 Schema Registration at Boot

```php
app()->resolving(SettingsSchemaRegistryInterface::class, function ($registry) {
	$registry->register('mail.smtp.port', [
		'type' => 'int',
		'group' => 'mail',
		'validation_rules' => ['min' => 1, 'max' => 65535],
		'description' => 'SMTP port used by outbound mailer',
		'default_value' => 587,
	]);
});
```

### 3.3 Validation Pipeline

```php
$validator->validate('mail.smtp.port', $request->integer('port'));
$settings->setTenantSetting($tenantId, 'mail.smtp.port', $request->integer('port'));
```

### 3.4 Encryption Flow

Store secrets as `EncryptedSetting` instances and let your repository call the application crypto service before persistence.

```php
$password = EncryptedSetting::fromPlaintext($request->input('password'));
$secret = $encrypter->encrypt($password->getValue());
$tenantRepository->set('mail.smtp.password', $secret);
```

## 4. Testing Recommendations

- Mock all `SettingRepositoryInterface` dependencies when unit-testing services. Use PHPUnit mocks or Prophecy to assert method calls.
- Provide in-memory repositories for feature tests to avoid database noise.
- Simulate cache behavior with a fake `SettingsCacheInterface` that records `remember` usage and ensures invalidation is triggered.

## 5. Performance Checklist

| Item | Recommendation |
| --- | --- |
| Database indexes | Add `UNIQUE(tenant_id, key)` and `UNIQUE(user_id, key)` indexes to keep lookups sub-millisecond. |
| Cache TTL | Default TTL is 3600 seconds; tune via constructor or config. |
| Bulk operations | Use `bulkSet` to avoid N+1 writes inside loops. |
| Serialization | Store arrays/JSON as native JSON columns to avoid PHP `serialize()` overhead. |

## 6. Troubleshooting

| Issue | Resolution |
| --- | --- |
| Cache never invalidates | Ensure your `SettingsCacheInterface::forgetPattern` implementation supports wildcard deletion or fall back to looping keys. |
| Application settings accidentally mutated | Throw `ReadOnlySettingException` inside application repository `set()` and `delete()` methods; do not bind the application repository anywhere else. |
| Validation skipped during import | Call `SettingsValidationService->validate()` before invoking `bulkSet` or wrap `SettingsManager::import()` with a validation pass. |

## 7. Reference Implementations

- `apps/Atomy/app/Providers/SettingsServiceProvider.php` (DI bindings)
- `apps/Atomy/app/Settings/Repositories/*` (example repository implementations)
- `docs/examples` (runnable PHP files included in this package)
