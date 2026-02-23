# Nexus Laravel Tenant Adapter

This adapter provides Laravel-specific implementations for the Tenant package, enabling multi-tenant support in Laravel applications.

## Purpose

The Tenant package is an atomic package that must remain independently publishable. This adapter layer provides the concrete implementations that integrate Tenant with the Laravel framework:

- **Database tenant resolution** - Uses Laravel's database layer
- **Cache integration** - Uses Laravel's cache system
- **Event dispatcher** - Uses Laravel's event system

## Installation

```bash
composer require nexus/laravel-tenant-adapter
```

## Adapters Provided

### TenantContextAdapter

Implements `Nexus\Tenant\Contracts\TenantContextInterface` by resolving the current tenant from Laravel's application context (request, auth, or cache).

### TenantPersistenceAdapter

Implements `Nexus\Tenant\Contracts\TenantPersistenceInterface` by delegating to Laravel's Eloquent ORM for database operations.

### TenantQueryAdapter

Implements `Nexus\Tenant\Contracts\TenantQueryInterface` by using Laravel's query builder for tenant lookups.

### CacheRepositoryAdapter

Implements `Nexus\Tenant\Contracts\CacheRepositoryInterface` by using Laravel's cache system.

### EventDispatcherAdapter

Implements `Nexus\Tenant\Contracts\EventDispatcherInterface` by using Laravel's event dispatcher.

### ImpersonationStorageAdapter

Implements `Nexus\Tenant\Contracts\ImpersonationStorageInterface` by using Laravel's session/cache for storing impersonation tokens.

## Service Provider

The `TenantAdapterServiceProvider` automatically binds the Tenant interfaces to their adapter implementations when the Laravel application boots.

## Architecture

This follows the Nexus Three-Layer Architecture:

1. **Atomic Layer** (`packages/Tenant`) - Pure business logic, no external dependencies
2. **Adapter Layer** (`adapters/Laravel/Tenant`) - Framework-specific implementations
3. **Application Layer** - Uses adapters through interfaces

## Dependencies

- `nexus/tenant` - The atomic Tenant package
- `illuminate/support` - Laravel framework components
- `illuminate/database` - Laravel database components
- `psr/log` - PSR-3 logging interface
