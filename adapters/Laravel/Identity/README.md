# Nexus Laravel Identity Adapter

This adapter provides Laravel-specific implementations for the Identity package.

## Purpose

The Identity package is an atomic package. This adapter provides Laravel integration.

## Installation

```bash
composer require nexus/laravel-identity-adapter
```

## Adapters Provided

### CacheRepositoryAdapter

Implements `Nexus\Identity\Contracts\CacheRepositoryInterface` using Laravel's cache.

### PermissionCheckerAdapter

Implements `Nexus\Identity\Contracts\PermissionCheckerInterface` using Laravel's Gate.

## Service Provider

The `IdentityAdapterServiceProvider` automatically binds the Identity interfaces.

## Dependencies

- `nexus/identity` - The atomic Identity package
- `illuminate/support` - Laravel framework components
- `illuminate/auth` - Laravel authentication components
