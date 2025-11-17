# Nexus\Setting

**Framework-agnostic settings management engine with hierarchical resolution across user, tenant, and application layers.**

## Overview

`Nexus\Setting` is a pure PHP package that provides a flexible, secure, and performant settings management system. It implements a three-tier hierarchical resolution system (User → Tenant → Application) with caching, encryption support, and comprehensive validation.

## Key Features

- **Hierarchical Resolution**: Settings cascade through User → Tenant → Application layers
- **Framework-Agnostic**: Pure PHP with no framework dependencies
- **Type-Safe Getters**: Dedicated methods for string, int, bool, float, and array types
- **Caching Support**: Interface-driven caching with automatic invalidation
- **Encryption**: Support for encrypting sensitive settings at rest
- **Schema Registry**: Define and validate setting schemas programmatically
- **Bulk Operations**: Transaction-safe bulk updates and exports
- **Read-Only Protection**: Mark settings as immutable or protected
- **Audit Trail**: Track all setting changes with history
- **Tenant Isolation**: Automatic scoping for multi-tenant applications

## Architecture

This package follows the Nexus monorepo architectural principles:

- **Logic in Packages**: All business logic lives here (framework-agnostic)
- **Implementation in Applications**: Database, models, and Laravel-specific code in `apps/Atomy`

### Package Structure

```
packages/Setting/
├── composer.json              # Package definition (PHP ^8.2 only)
├── LICENSE                    # MIT License
├── README.md                  # This file
└── src/
    ├── Contracts/             # Interfaces for dependency injection
    │   ├── SettingRepositoryInterface.php
    │   ├── SettingsCacheInterface.php
    │   └── SettingsAuthorizerInterface.php
    ├── Exceptions/            # Domain-specific exceptions
    │   ├── SettingNotFoundException.php
    │   ├── ReadOnlySettingException.php
    │   └── ProtectedSettingException.php
    ├── Services/              # Business logic layer
    │   ├── SettingsManager.php
    │   ├── SettingsCacheManager.php
    │   ├── SettingsValidationService.php
    │   └── SettingsSchemaRegistry.php
    └── ValueObjects/          # Immutable value objects
        ├── SettingScope.php
        ├── SettingLayer.php
        └── EncryptedSetting.php
```

## Installation

This package is designed for use within the Nexus monorepo. Add it to your application's `composer.json`:

```json
{
    "require": {
        "nexus/setting": "*@dev"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../../packages/Setting"
        }
    ]
}
```

Then run:

```bash
composer require nexus/setting:"*@dev"
```

## Usage

### Basic Usage

```php
use Nexus\Setting\Services\SettingsManager;
use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Contracts\SettingsCacheInterface;

// Inject dependencies (bound by application layer)
$manager = new SettingsManager(
    $userRepo,      // SettingRepositoryInterface implementation
    $tenantRepo,    // SettingRepositoryInterface implementation
    $appRepo,       // SettingRepositoryInterface implementation
    $cache          // SettingsCacheInterface implementation
);

// Get setting with hierarchical resolution
$timezone = $manager->getString('timezone', 'UTC');

// Set user-specific setting
$manager->setUserSetting('user-123', 'theme', 'dark');

// Set tenant-specific setting
$manager->setTenantSetting('tenant-456', 'currency', 'MYR');

// Check if setting exists
if ($manager->has('mail.smtp.host')) {
    $host = $manager->getString('mail.smtp.host');
}

// Get setting origin (which layer it came from)
$origin = $manager->getOrigin('timezone'); // 'user', 'tenant', or 'application'
```

### Type-Safe Getters

```php
// String
$theme = $manager->getString('theme', 'light');

// Integer
$timeout = $manager->getInt('api.timeout', 30);

// Boolean
$enabled = $manager->getBool('feature.analytics', false);

// Float
$taxRate = $manager->getFloat('tax.rate', 0.06);

// Array
$permissions = $manager->getArray('user.permissions', []);
```

### Bulk Operations

```php
// Bulk update multiple settings in a transaction
$settings = [
    'timezone' => 'Asia/Kuala_Lumpur',
    'currency' => 'MYR',
    'date_format' => 'DD/MM/YYYY'
];
$manager->bulkSet($settings, 'tenant', 'tenant-456');

// Export all tenant settings
$exported = $manager->export('tenant-456');

// Import settings (restore/migration)
$manager->import($exported, 'tenant-789');
```

### Caching

```php
use Nexus\Setting\Services\SettingsCacheManager;

$cacheManager = new SettingsCacheManager($cacheInterface);

// Cache with TTL
$value = $cacheManager->remember('setting.key', fn() => $expensive_operation());

// Invalidate specific key
$cacheManager->forget('setting.key');

// Invalidate all settings for a scope
$cacheManager->forgetScope('tenant', 'tenant-456');

// Flush entire cache
$cacheManager->flush();
```

### Schema Registry & Validation

```php
use Nexus\Setting\Services\SettingsSchemaRegistry;
use Nexus\Setting\Services\SettingsValidationService;

$registry = new SettingsSchemaRegistry();

// Register a setting schema
$registry->register('api.timeout', [
    'type' => 'integer',
    'min' => 1,
    'max' => 300,
    'description' => 'API timeout in seconds'
]);

$validator = new SettingsValidationService($registry);

// Validate before setting
if ($validator->validate('api.timeout', 30)) {
    $manager->setTenantSetting('tenant-456', 'api.timeout', 30);
}
```

## Hierarchical Resolution

Settings are resolved in the following order:

1. **User Layer**: User-specific settings (highest priority)
2. **Tenant Layer**: Tenant-specific settings
3. **Application Layer**: Application/environment defaults (lowest priority, read-only)

```php
// Example: Getting 'timezone' setting
// 1. Check user settings for current user
// 2. If not found, check tenant settings
// 3. If not found, check application config
// 4. If not found, return default value

$timezone = $manager->getString('timezone', 'UTC');
```

## Contracts (Interfaces)

### SettingRepositoryInterface

Defines the persistence contract for settings at each layer.

```php
interface SettingRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function delete(string $key): void;
    public function has(string $key): bool;
    public function getAll(): array;
    public function getByPrefix(string $prefix): array;
    public function getMetadata(string $key): ?array;
}
```

### SettingsCacheInterface

Defines the caching contract for settings.

```php
interface SettingsCacheInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, ?int $ttl = null): void;
    public function forget(string $key): void;
    public function flush(): void;
    public function has(string $key): bool;
}
```

### SettingsAuthorizerInterface

Defines the authorization contract for settings access control.

```php
interface SettingsAuthorizerInterface
{
    public function canView(string $userId, string $key): bool;
    public function canEdit(string $userId, string $key): bool;
}
```

## Integration with Atomy

In `apps/Atomy`, you'll implement:

1. **Migrations**: Database schema for `settings`, `setting_history` tables
2. **Models**: Eloquent models implementing package interfaces
3. **Repositories**: Concrete implementations of repository interfaces
4. **Service Provider**: Binding contracts to implementations
5. **API Routes**: RESTful endpoints for settings CRUD

See `apps/Atomy/app/Providers/SettingsServiceProvider.php` for binding examples.

## Requirements Fulfilled

This package fulfills the following requirements from `REQUIREMENTS.csv`:

- **Architectural Requirements**: ARC-SET-1289 to ARC-SET-1299 (Framework-agnostic design)
- **Business Requirements**: BUS-SET-1300 to BUS-SET-1314 (Hierarchical resolution, caching, encryption)
- **Functional Requirements**: FUN-SET-1315 to FUN-SET-1345 (31 functional requirements)
- **Performance Requirements**: PER-SET-1346 to PER-SET-1350 (Caching, bulk operations)
- **Security Requirements**: SEC-SET-1351 to SEC-SET-1357 (Audit logging, tenant isolation, encryption)

## Testing

Package tests should be unit tests with mocked repository implementations (no database dependencies).

```php
// Example test
$mockUserRepo = $this->createMock(SettingRepositoryInterface::class);
$mockTenantRepo = $this->createMock(SettingRepositoryInterface::class);
$mockAppRepo = $this->createMock(SettingRepositoryInterface::class);
$mockCache = $this->createMock(SettingsCacheInterface::class);

$manager = new SettingsManager($mockUserRepo, $mockTenantRepo, $mockAppRepo, $mockCache);

// Test hierarchical resolution
$mockUserRepo->expects($this->once())
    ->method('get')
    ->with('timezone', null)
    ->willReturn('Asia/Kuala_Lumpur');

$result = $manager->getString('timezone', 'UTC');
$this->assertEquals('Asia/Kuala_Lumpur', $result);
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Contributing

This package is part of the Nexus monorepo. Follow the architectural guidelines in `ARCHITECTURE.md`.

**Key Principle**: Logic in Packages, Implementation in Applications.
