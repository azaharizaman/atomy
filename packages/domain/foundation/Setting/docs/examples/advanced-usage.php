<?php
declare(strict_types=1);

use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Contracts\SettingsCacheInterface;
use Nexus\Setting\Exceptions\ProtectedSettingException;
use Nexus\Setting\Services\SettingsManager;
use Nexus\Setting\ValueObjects\SettingLayer;
use Nexus\Setting\ValueObjects\SettingScope;

/**
 * Advanced usage example: layered repositories, encryption, cache tagging, and import/export.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Naive in-memory repository used as storage backend.
 *
 * @psalm-suppress MissingConstructor
 */
final class InMemoryRepository implements SettingRepositoryInterface
{
	/** @var array<string, mixed> */
	private array $state = [];

	public function __construct(array $seed = [])
	{
		$this->state = $seed;
	}

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->state[$key] ?? $default;
	}

	public function set(string $key, mixed $value): void
	{
		$this->state[$key] = $value;
	}

	public function delete(string $key): void
	{
		unset($this->state[$key]);
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->state);
	}

	public function getAll(): array
	{
		return $this->state;
	}

	public function getByPrefix(string $prefix): array
	{
		return array_filter(
			$this->state,
			static fn ($_, $key) => str_starts_with($key, $prefix),
			ARRAY_FILTER_USE_BOTH
		);
	}

	public function getMetadata(string $key): ?array
	{
		return isset($this->state[$key]) ? ['key' => $key, 'type' => gettype($this->state[$key])] : null;
	}

	public function bulkSet(array $settings): void
	{
		foreach ($settings as $key => $value) {
			$this->state[$key] = $value;
		}
	}

	public function isWritable(): bool
	{
		return true;
	}
}

/**
 * Decorator that encrypts selected keys before persistence.
 */
final class EncryptedSettingRepository implements SettingRepositoryInterface
{
	/** @param array<string> $sensitiveKeys */
	public function __construct(
		private readonly SettingRepositoryInterface $inner,
		private readonly string $encryptionKey,
		private readonly array $sensitiveKeys = []
	) {}

	public function get(string $key, mixed $default = null): mixed
	{
		$value = $this->inner->get($key, $default);
		if ($value === $default || ! $this->isSensitive($key) || $value === null) {
			return $value;
		}

		return $this->decrypt((string) $value);
	}

	public function set(string $key, mixed $value): void
	{
		if ($this->isSensitive($key) && $value !== null) {
			$value = $this->encrypt((string) $value);
		}

		$this->inner->set($key, $value);
	}

	public function delete(string $key): void
	{
		$this->inner->delete($key);
	}

	public function has(string $key): bool
	{
		return $this->inner->has($key);
	}

	public function getAll(): array
	{
		$all = $this->inner->getAll();
		foreach ($all as $key => $value) {
			if ($this->isSensitive($key) && $value !== null) {
				$all[$key] = $this->decrypt((string) $value);
			}
		}

		return $all;
	}

	public function getByPrefix(string $prefix): array
	{
		$values = $this->inner->getByPrefix($prefix);
		foreach ($values as $key => $value) {
			if ($this->isSensitive($key) && $value !== null) {
				$values[$key] = $this->decrypt((string) $value);
			}
		}

		return $values;
	}

	public function getMetadata(string $key): ?array
	{
		return $this->inner->getMetadata($key);
	}

	public function bulkSet(array $settings): void
	{
		foreach ($settings as $key => $value) {
			$this->set($key, $value);
		}
	}

	public function isWritable(): bool
	{
		return $this->inner->isWritable();
	}

	private function isSensitive(string $key): bool
	{
		return in_array($key, $this->sensitiveKeys, true);
	}

	private function encrypt(string $value): string
	{
		return base64_encode($value . '::' . hash_hmac('sha256', $value, $this->encryptionKey));
	}

	private function decrypt(string $payload): string
	{
		$decoded = base64_decode($payload, true);
		if ($decoded === false) {
			return '';
		}

		[$value] = explode('::', $decoded, 2);

		return $value;
	}
}

/**
 * Simple cache that tracks keys per scope for targeted invalidation.
 */
final class TaggableSettingsCache implements SettingsCacheInterface
{
	/** @var array<string, mixed> */
	private array $cache = [];

	/** @var array<string, array<int, string>> */
	private array $tags = [];

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
		$this->tags = [];
	}

	public function remember(string $key, callable $callback, ?int $ttl = null): mixed
	{
		if (! $this->has($key)) {
			$this->set($key, $callback());
		}

		return $this->cache[$key];
	}

	public function tagScope(SettingScope $scope, string $key): void
	{
		$scopeKey = $scope->toString();
		$this->tags[$scopeKey] ??= [];
		$this->tags[$scopeKey][] = $key;
	}

	public function forgetTaggedScope(SettingScope $scope): void
	{
		$scopeKey = $scope->toString();
		foreach ($this->tags[$scopeKey] ?? [] as $key) {
			$this->forget($key);
		}
		unset($this->tags[$scopeKey]);
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

$userRepo = new InMemoryRepository([
	'editor.theme' => 'dark',
	'editor.font_size' => 14,
]);

$tenantRepo = new EncryptedSettingRepository(
	new InMemoryRepository([
		'mail.smtp.password' => 'super-secret',
		'feature.flags.inventory' => true,
	]),
	encryptionKey: 'tenant-secret-key',
	sensitiveKeys: ['mail.smtp.password']
);

$applicationRepo = new InMemoryRepository([
	'platform.timezone' => 'UTC',
	'platform.locale' => 'en_MY',
	'feature.flags.inventory' => false,
]);

$cache = new TaggableSettingsCache();
$manager = new SettingsManager($userRepo, $tenantRepo, $applicationRepo, $cache, ['mail.smtp.password']);

// Fetch configuration with resolution order (user → tenant → application)
$tenantId = 'tenant-44';
$userId = 'user-9';

$theme = $manager->getString('editor.theme', userId: $userId, tenantId: $tenantId);
$inventoryFlag = $manager->getBool('feature.flags.inventory', tenantId: $tenantId);
$timezone = $manager->getString('platform.timezone');

printf("Theme=%s | Inventory Feature=%s | Timezone=%s\n", $theme, $inventoryFlag ? 'on' : 'off', $timezone);

// Bulk update tenant defaults in a single transaction
$manager->bulkSet([
	'editor.theme' => 'light',
	'editor.font_size' => 16,
	'feature.flags.inventory' => true,
], SettingLayer::TENANT, $tenantId);

// Track origin of specific keys for audits
$origin = $manager->getOrigin('editor.font_size', userId: $userId, tenantId: $tenantId);
printf("editor.font_size currently resolved from %s layer\n", $origin ?? 'unknown');

// Export tenant snapshot for backup
$export = $manager->export($tenantId);
print_r($export);

// Simulate restoring into a new tenant
$newTenantId = 'tenant-55';
$manager->import($export, $newTenantId);

// Attempting to override protected keys throws
try {
	$manager->setTenantSetting($tenantId, 'mail.smtp.password', 'hack');
} catch (ProtectedSettingException $exception) {
	echo "Protected key prevented update: {$exception->getMessage()}" . PHP_EOL;
}

// Display hierarchical view after import
foreach ([$tenantId, $newTenantId] as $tid) {
	$currencyScope = SettingScope::tenant($tid);
	$cache->tagScope($currencyScope, 'tenant.currency');
	$tenantSettings = $manager->getAllTenantSettings($tid);
	printf("Tenant %s settings: %s\n", $tid, json_encode($tenantSettings));
}
