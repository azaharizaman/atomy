# API Reference: Nexus\Setting

This document summarizes the public, framework-agnostic API surface shipped with `Nexus\Setting`. All classes live under the `Nexus\Setting` namespace and are safe to consume from any PHP 8.3+ framework.

## Table of Contents

1. [Contracts (Interfaces)](#contracts-interfaces)
2. [Services](#services)
3. [Value Objects](#value-objects)
4. [Exceptions](#exceptions)
5. [Usage Patterns](#usage-patterns)

---

## Contracts (Interfaces)

### SettingRepositoryInterface

**Namespace:** `Nexus\Setting\Contracts`  
**Purpose:** Defines persistence operations for a single layer (user, tenant, or application). Applications must provide three concrete implementations.

| Method | Signature | Description |
| --- | --- | --- |
| `get` | `public function get(string $key, mixed $default = null): mixed` | Fetch a setting value or return the provided default. |
| `set` | `public function set(string $key, mixed $value): void` | Persist a value. Should guard against read-only/protected keys. |
| `delete` | `public function delete(string $key): void` | Remove a value. |
| `has` | `public function has(string $key): bool` | Determine if a key exists in this layer. |
| `getAll` | `public function getAll(): array<string, mixed>` | Return all key/value pairs for the layer. |
| `getByPrefix` | `public function getByPrefix(string $prefix): array<string, mixed>` | Filter keys by prefix (namespacing). |
| `getMetadata` | `public function getMetadata(string $key): ?array` | Retrieve schema metadata if stored in persistence. |
| `bulkSet` | `public function bulkSet(array $settings): void` | Atomic update for multiple settings. |
| `isWritable` | `public function isWritable(): bool` | Indicate if this layer accepts mutations (application layer typically returns `false`). |

### SettingsCacheInterface

**Purpose:** Adapter for your framework cache. Wrap PSR-16, Symfony Cache, Redis, etc.

- `get(string $key, mixed $default = null): mixed`
- `set(string $key, mixed $value, ?int $ttl = null): void`
- `forget(string $key): void`
- `has(string $key): bool`
- `flush(): void`
- `remember(string $key, callable $callback, ?int $ttl = null): mixed`
- `forgetPattern(string $pattern): void` (implement pattern/prefix clearing as supported by your cache backend)

### SettingsAuthorizerInterface

**Purpose:** Allows application-level ACL enforcement for specific setting keys.

- `canView(string $userId, string $key): bool`
- `canEdit(string $userId, string $key): bool`
- `canDelete(string $userId, string $key): bool`
- `getViewableKeys(string $userId): array`
- `getEditableKeys(string $userId): array`

### SettingsSchemaRegistryInterface

**Purpose:** Registry for describing setting schemas and metadata.

- `register(string $key, array $schema): void`
- `get(string $key): ?array`
- `has(string $key): bool`
- `all(): array`
- `unregister(string $key): void`
- `getByGroup(string $group): array`

---

## Services

### SettingsManager

**Namespace:** `Nexus\Setting\Services\SettingsManager`

**Constructor:**

```php
public function __construct(
	SettingRepositoryInterface $userRepository,
	SettingRepositoryInterface $tenantRepository,
	SettingRepositoryInterface $applicationRepository,
	SettingsCacheInterface $cache,
	array $protectedKeys = []
)
```

**Responsibilities:**

- Hierarchical resolution for `get*` methods (user → tenant → application)
- Scoped mutations (`setUserSetting`, `setTenantSetting`, `delete*`)
- Type-safe accessors (`getString`, `getInt`, `getFloat`, `getBool`, `getArray`)
- Bulk update workflows via `bulkSet()`
- Export/import helpers for tenant-level migrations
- Cache invalidation through an internal `SettingsCacheManager`

**Common Methods:**

| Method | Description |
| --- | --- |
| `get(string $key, mixed $default = null, ?string $userId = null, ?string $tenantId = null): mixed` | Resolve a key across layers. |
| `setUserSetting(string $userId, string $key, mixed $value): void` | Persist and invalidate cache for a user scope. |
| `setTenantSetting(string $tenantId, string $key, mixed $value): void` | Persist tenant-level overrides. |
| `bulkSet(array $settings, SettingLayer $layer, string $scopeId): void` | Transaction-safe batch updates. |
| `has(string $key, ?string $userId = null, ?string $tenantId = null): bool` | Determine if the key exists anywhere. |
| `getOrigin(string $key, ?string $userId = null, ?string $tenantId = null): ?string` | Returns `user`, `tenant`, `application`, or `null`. |
| `export(string $tenantId): array` / `import(array $data, string $tenantId): void` | Backup and restore helpers for tenant settings. |

### SettingsCacheManager

Thin wrapper over `SettingsCacheInterface` that manages scoped cache keys using `SettingScope`.

- `remember(string $key, callable $callback, ?int $ttl = null): mixed`
- `rememberScoped(SettingScope $scope, string $key, callable $callback, ?int $ttl = null): mixed`
- `forget(string $key): void`
- `forgetScoped(SettingScope $scope, string $key): void`
- `forgetScope(SettingScope $scope): void`
- `flush(): void`
- `has(string $key): bool`
- `hasScoped(SettingScope $scope, string $key): bool`
- `set(string $key, mixed $value, ?int $ttl = null): void`

### SettingsValidationService

Validates values before persistence.

- `__construct(SettingsSchemaRegistryInterface $registry)`
- `validate(string $key, mixed $value): bool` – Throws `SettingValidationException` when type/rule checks fail (supports `min`, `max`, `pattern`, `enum`, `required`).

### SettingsSchemaRegistry

Default in-memory implementation of `SettingsSchemaRegistryInterface`. Useful for registering schemas at boot time.

```php
$registry = new SettingsSchemaRegistry();
$registry->register('api.timeout', [
	'type' => 'int',
	'group' => 'api',
	'validation_rules' => ['min' => 1, 'max' => 300],
]);
```

---

## Value Objects

### SettingLayer (enum)

- Cases: `USER`, `TENANT`, `APPLICATION`
- Methods: `resolutionOrder()`, `isWritable()`, `priority()`

### SettingScope

Immutable wrapper that pairs a `SettingLayer` with an optional identifier.

- Named constructors: `SettingScope::user(string $id)`, `SettingScope::tenant(string $id)`, `SettingScope::application()`
- Helpers: `cacheKey(string $settingKey)`, `isWritable()`, `toString()`, `equals(SettingScope $other)`

### SettingMetadata

Describes the schema, defaults, and UI hints for a setting.

- Properties: `$key`, `$type`, `$defaultValue`, `$description`, `$validationRules`, `$isReadOnly`, `$isProtected`, `$isEncrypted`, `$group`, `$uiMetadata`
- Helpers: `SettingMetadata::fromArray(array $data)`, `toArray()`, `isWritable()`, `canBeOverridden()`

### EncryptedSetting

Indicates that a value should be encrypted in persistence.

- `EncryptedSetting::fromPlaintext(mixed $value)`
- `EncryptedSetting::fromEncrypted(string $value)`
- `getValue()`, `needsEncryption()`, `isAlreadyEncrypted()`

---

## Exceptions

| Exception | When Thrown |
| --- | --- |
| `SettingNotFoundException` | Optional helper for repositories that want to throw instead of returning defaults. |
| `ReadOnlySettingException` | Attempting to modify a read-only layer (e.g., application) or immutable key. |
| `ProtectedSettingException` | Attempting to override a protected key outside its owning layer. |
| `InvalidSettingScopeException` | Invalid combinations of layer + identifier. |
| `SettingValidationException` | Value failed schema validation or type enforcement. |

Each exception carries contextual data (key, layer, validation message) to ease logging and audit trails.

---

## Usage Patterns

### Registering Schema and Validating Input

```php
$registry = new SettingsSchemaRegistry();
$registry->register('mail.smtp.timeout', [
	'type' => 'int',
	'group' => 'mail',
	'validation_rules' => ['min' => 1, 'max' => 60],
]);

$validator = new SettingsValidationService($registry);
$validator->validate('mail.smtp.timeout', 30);
$manager->setTenantSetting('tenant-100', 'mail.smtp.timeout', 30);
```

### Cache-Aware Setting Reads

```php
$scope = SettingScope::tenant('tenant-100');
$timezone = $manager->getString('ui.timezone', 'UTC', tenantId: $scope->scopeId);
```

### Exporting Tenant Defaults

```php
$snapshot = $manager->export('tenant-100');
file_put_contents('/backups/tenant-100-settings.json', json_encode($snapshot, JSON_PRETTY_PRINT));
```

Refer to [`docs/examples`](examples) for runnable code samples covering both introductory and advanced scenarios.
