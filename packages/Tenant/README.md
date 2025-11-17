# Nexus\Tenant

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)

Framework-agnostic multi-tenancy context and isolation engine for the Nexus ERP system.

## Overview

The **Nexus\Tenant** package provides the core business logic and contracts necessary to manage multi-tenancy context, session, and administrative lifecycle. It serves as the central engine for identifying and validating the current tenant across the entire ERP system.

## Key Features

- **Framework-Agnostic**: Pure PHP with no Laravel dependencies
- **Context Management**: Set and retrieve active tenant across request/process lifecycle
- **Tenant Identification**: Support for multiple strategies (domain, subdomain, header, token, path)
- **Lifecycle Management**: Create, activate, suspend, reactivate, archive, and delete tenants
- **Impersonation**: Secure support staff impersonation with audit trails
- **Multi-Database Support**: Single database with tenant_id or separate databases per tenant
- **Enterprise Features**: Parent-child relationships, quotas, rate limiting, feature flags
- **Event-Driven**: Lifecycle events for integration with other packages
- **Caching**: Optimized performance with cache abstraction

## Architecture

This package follows the **"Logic in Packages, Implementation in Applications"** pattern:

- **Package Layer** (`packages/Tenant/`): Framework-agnostic business logic and interfaces
- **Application Layer** (`apps/Atomy/`): Laravel-specific implementation (models, migrations, repositories)

### What This Package Provides

- `TenantContextManager`: Core service for setting/retrieving current tenant context
- `TenantLifecycleService`: Business logic for tenant CRUD and state management
- `TenantImpersonationService`: Secure impersonation with validation and logging
- `TenantResolverService`: Identifies tenant from request (domain, subdomain, header, etc.)
- **Contracts**: All external dependencies defined via interfaces
- **Events**: Framework-agnostic events for lifecycle changes
- **Exceptions**: Domain-specific exceptions for error handling
- **Value Objects**: Immutable objects for tenant status, identification strategy, and settings

### What the Application Must Implement

The consuming application (`Nexus\Atomy`) must provide:

1. **Data Isolation**: Global Scope for automatic `WHERE tenant_id = X` clauses
2. **Persistence**: Migrations and Eloquent models implementing package interfaces
3. **Cache Integration**: Concrete implementation of `CacheRepositoryInterface`
4. **Context Propagation**: Middleware and queue job handling for tenant context
5. **Multi-Database Strategy**: Database connection switching (if using separate databases)

## Installation

```bash
composer require nexus/tenant:"*@dev"
```

## Requirements

- PHP 8.2 or higher
- PSR-3 Logger implementation (optional)

## Basic Usage

### 1. Setting Tenant Context

```php
use Nexus\Tenant\Services\TenantContextManager;

$contextManager = app(TenantContextManager::class);

// Set tenant by ID
$contextManager->setTenant('01HQRS...');

// Get current tenant ID
$tenantId = $contextManager->getCurrentTenantId(); // '01HQRS...'

// Check if tenant is set
if ($contextManager->hasTenant()) {
    // Tenant context is active
}

// Clear context
$contextManager->clearTenant();
```

### 2. Tenant Lifecycle Management

```php
use Nexus\Tenant\Services\TenantLifecycleService;

$lifecycle = app(TenantLifecycleService::class);

// Create new tenant
$tenant = $lifecycle->createTenant(
    code: 'ACME',
    name: 'Acme Corporation',
    email: 'admin@acme.com',
    domain: 'acme.example.com'
);

// Activate tenant
$lifecycle->activateTenant($tenant->getId());

// Suspend tenant (reversible)
$lifecycle->suspendTenant($tenant->getId(), reason: 'Payment overdue');

// Reactivate
$lifecycle->reactivateTenant($tenant->getId());

// Archive (soft delete)
$lifecycle->archiveTenant($tenant->getId());

// Permanently delete (hard delete after retention period)
$lifecycle->deleteTenant($tenant->getId());
```

### 3. Tenant Impersonation

```php
use Nexus\Tenant\Services\TenantImpersonationService;

$impersonation = app(TenantImpersonationService::class);

// Start impersonation (support staff accessing tenant)
$impersonation->impersonate(
    tenantId: '01HQRS...',
    originalUserId: 'admin-123',
    reason: 'Customer support ticket #4567'
);

// Check if impersonating
if ($impersonation->isImpersonating()) {
    $tenantId = $impersonation->getImpersonatedTenantId();
    $originalUser = $impersonation->getOriginalUserId();
}

// Stop impersonation
$impersonation->stopImpersonation();
```

### 4. Tenant Resolution

```php
use Nexus\Tenant\Services\TenantResolverService;

$resolver = app(TenantResolverService::class);

// Resolve from domain
$tenantId = $resolver->resolveFromDomain('acme.example.com');

// Resolve from subdomain
$tenantId = $resolver->resolveFromSubdomain('acme.myapp.com');

// Resolve from header
$tenantId = $resolver->resolveFromHeader($_SERVER, 'X-Tenant-ID');

// Resolve from path
$tenantId = $resolver->resolveFromPath('/tenant/acme/dashboard');
```

## Contracts

All external dependencies are defined via interfaces that must be implemented by the application:

### TenantInterface

Defines the data structure for a tenant entity.

```php
interface TenantInterface
{
    public function getId(): string;
    public function getCode(): string;
    public function getName(): string;
    public function getStatus(): string;
    public function getDomain(): ?string;
    // ... (20+ methods)
}
```

### TenantRepositoryInterface

Defines persistence operations for tenants.

```php
interface TenantRepositoryInterface
{
    public function findById(string $id): ?TenantInterface;
    public function findByCode(string $code): ?TenantInterface;
    public function findByDomain(string $domain): ?TenantInterface;
    public function create(array $data): TenantInterface;
    public function update(string $id, array $data): TenantInterface;
    public function delete(string $id): bool;
    // ... (15+ methods)
}
```

### CacheRepositoryInterface

Defines caching operations for tenant data.

```php
interface CacheRepositoryInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, ?int $ttl = null): bool;
    public function forget(string $key): bool;
    public function flush(): bool;
}
```

### TenantContextInterface

Defines the tenant context contract for global access.

```php
interface TenantContextInterface
{
    public function setTenant(string $tenantId): void;
    public function getCurrentTenantId(): ?string;
    public function hasTenant(): bool;
    public function clearTenant(): void;
}
```

## Events

The package emits framework-agnostic events for lifecycle changes:

- `TenantCreatedEvent`: Fired when a new tenant is created
- `TenantActivatedEvent`: Fired when a tenant is activated
- `TenantSuspendedEvent`: Fired when a tenant is suspended
- `TenantReactivatedEvent`: Fired when a suspended tenant is reactivated
- `TenantArchivedEvent`: Fired when a tenant is archived (soft deleted)
- `TenantDeletedEvent`: Fired when a tenant is permanently deleted
- `TenantUpdatedEvent`: Fired when tenant metadata is updated
- `ImpersonationStartedEvent`: Fired when impersonation begins
- `ImpersonationEndedEvent`: Fired when impersonation stops

## Value Objects

### TenantStatus

Immutable value object representing tenant status:

```php
use Nexus\Tenant\ValueObjects\TenantStatus;

$status = TenantStatus::pending();    // Tenant created, not yet active
$status = TenantStatus::active();     // Tenant active and operational
$status = TenantStatus::suspended();  // Temporarily suspended (reversible)
$status = TenantStatus::archived();   // Soft deleted
$status = TenantStatus::trial();      // Trial period
```

### IdentificationStrategy

Defines how tenants are identified:

```php
use Nexus\Tenant\ValueObjects\IdentificationStrategy;

$strategy = IdentificationStrategy::domain();      // acme.example.com
$strategy = IdentificationStrategy::subdomain();   // acme.myapp.com
$strategy = IdentificationStrategy::header();      // X-Tenant-ID header
$strategy = IdentificationStrategy::path();        // /tenant/acme
$strategy = IdentificationStrategy::token();       // API token embedded tenant
```

### TenantSettings

Immutable settings object for tenant-specific configuration:

```php
use Nexus\Tenant\ValueObjects\TenantSettings;

$settings = new TenantSettings(
    timezone: 'Asia/Kuala_Lumpur',
    locale: 'en_MY',
    currency: 'MYR',
    dateFormat: 'd/m/Y',
    timeFormat: 'H:i',
    metadata: ['custom_field' => 'value']
);
```

## Exception Handling

The package provides specific exceptions for error scenarios:

```php
use Nexus\Tenant\Exceptions\TenantNotFoundException;
use Nexus\Tenant\Exceptions\InvalidTenantStatusException;
use Nexus\Tenant\Exceptions\TenantSuspendedException;
use Nexus\Tenant\Exceptions\TenantContextNotSetException;
use Nexus\Tenant\Exceptions\InvalidIdentificationStrategyException;
use Nexus\Tenant\Exceptions\ImpersonationNotAllowedException;
use Nexus\Tenant\Exceptions\DuplicateTenantCodeException;
use Nexus\Tenant\Exceptions\DuplicateTenantDomainException;

try {
    $tenant = $repository->findById('invalid-id');
} catch (TenantNotFoundException $e) {
    // Handle not found
}
```

## Testing

```bash
# Run package tests (unit tests, no database)
cd packages/Tenant
composer test

# Run with coverage
composer test -- --coverage-html coverage/
```

## Security Considerations

- Tenant context must be set before any database operations
- Cross-tenant data access is prevented at multiple layers
- Impersonation requires specific permissions and generates audit trails
- All tenant state changes use ACID transactions
- Suspended tenants cannot access the system
- Cache keys are tenant-scoped to prevent data leakage

## Contributing

This package follows the Nexus monorepo architecture guidelines. All business logic must remain framework-agnostic.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Documentation

- [Architecture Guidelines](../../ARCHITECTURE.md)
- [Implementation Guide](../../docs/TENANT_IMPLEMENTATION.md)
- [Requirements](../../REQUIREMENTS.csv)
