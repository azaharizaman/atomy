<?php
declare(strict_types=1);

use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Contracts\SettingsCacheInterface;
use Nexus\Setting\Services\SettingsManager;
use Nexus\Setting\ValueObjects\SettingLayer;
use Nexus\Setting\ValueObjects\SettingScope;

/**
 * Basic usage example: resolve settings with hierarchical overrides.
 *
 * Run via: php docs/examples/basic-usage.php
 */

require_once __DIR__ . '/../../vendor/autoload.php'; // Adjust path when running outside monorepo

final class ArraySettingRepository implements SettingRepositoryInterface
{
	/** @var array<string, mixed> */
	private array $store;

	public function __construct(array $seed = [])
	{
		$this->store = $seed;
	}

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->store[$key] ?? $default;
	}

	public function set(string $key, mixed $value): void
	{
		$this->store[$key] = $value;
	}

	public function delete(string $key): void
	{
		unset($this->store[$key]);
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->store);
	}

	public function getAll(): array
	{
		return $this->store;
	}

	public function getByPrefix(string $prefix): array
	{
		return array_filter(
			$this->store,
			static fn ($_, $key) => str_starts_with($key, $prefix),
			ARRAY_FILTER_USE_BOTH
		);
	}

	public function getMetadata(string $key): ?array
	{
		return isset($this->store[$key]) ? ['key' => $key, 'type' => gettype($this->store[$key])] : null;
	}

	public function bulkSet(array $settings): void
	{
		foreach ($settings as $key => $value) {
			$this->store[$key] = $value;
		}
	}

	public function isWritable(): bool
	{
		return true;
	}
}

final class ArraySettingsCache implements SettingsCacheInterface
{
	/** @var array<string, mixed> */
	private array $cache = [];

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->cache[$key] ?? $default;
	}

	public function set(string $key, mixed $value, ?int $ttl = null): void
	{
		$this->cache[$key] = $value;
	}

	public function forget(string $key): void
	{
		unset($this->cache[$key]);
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->cache);
	}

	public function flush(): void
	{
		$this->cache = [];
	}

	public function remember(string $key, callable $callback, ?int $ttl = null): mixed
	{
		if (! $this->has($key)) {
			$this->cache[$key] = $callback();
		}

		return $this->cache[$key];
	}

	public function forgetPattern(string $pattern): void
	{
		$pattern = str_replace('*', '.*', preg_quote($pattern, '/'));
		foreach (array_keys($this->cache) as $key) {
			if (preg_match('/^' . $pattern . '$/', $key) === 1) {
				unset($this->cache[$key]);
			}
		}
	}
}

$userRepo = new ArraySettingRepository([
	'ui.theme' => 'dark',
]);

$tenantRepo = new ArraySettingRepository([
	'tenant.currency' => 'MYR',
]);

$applicationRepo = new class ($tenantRepo->getAll()) extends ArraySettingRepository {
	public function set(string $key, mixed $value): void
	{
		throw new RuntimeException('Application repository is read-only');
	}

	public function delete(string $key): void
	{
		throw new RuntimeException('Application repository is read-only');
	}

	public function bulkSet(array $settings): void
	{
		throw new RuntimeException('Application repository is read-only');
	}
};

$cache = new ArraySettingsCache();
$manager = new SettingsManager($userRepo, $tenantRepo, $applicationRepo, $cache, ['mail.smtp.password']);

echo "Current theme: " . $manager->getString('ui.theme', 'light', userId: 'user-1', tenantId: 'tenant-1') . PHP_EOL;
echo "Tenant currency: " . $manager->getString('tenant.currency', 'USD', tenantId: 'tenant-1') . PHP_EOL;

$manager->setTenantSetting('tenant-1', 'tenant.currency', 'EUR');
echo "Updated currency: " . $manager->getString('tenant.currency', tenantId: 'tenant-1') . PHP_EOL;

$manager->bulkSet([
	'ui.language' => 'en',
	'ui.date_format' => 'DD/MM/YYYY',
], SettingLayer::TENANT, 'tenant-1');

$scope = SettingScope::tenant('tenant-1');
var_export($scope->toString());
echo PHP_EOL;
