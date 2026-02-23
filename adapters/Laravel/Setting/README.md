# Nexus Laravel Setting Adapter

This adapter provides Laravel-specific implementations for the Setting package, enabling configuration management in Laravel applications.

## Purpose

The Setting package is an atomic package that must remain independently publishable. This adapter layer provides the concrete implementations that integrate Setting with the Laravel framework:

- **Database persistence** - Uses Laravel's Eloquent ORM
- **Cache integration** - Uses Laravel's cache system
- **Schema validation** - Uses Laravel's validation system

## Installation

```bash
composer require nexus/laravel-setting-adapter
```

## Adapters Provided

### SettingRepositoryAdapter

Implements `Nexus\Setting\Contracts\SettingRepositoryInterface` by using Laravel's database for persistence.

### SettingsCacheAdapter

Implements `Nexus\Setting\Contracts\SettingsCacheInterface` by using Laravel's cache system.

### SettingsAuthorizerAdapter

Implements `Nexus\Setting\Contracts\SettingsAuthorizerInterface` by using Laravel's authorization system.

## Service Provider

The `SettingAdapterServiceProvider` automatically binds the Setting interfaces to their adapter implementations when the Laravel application boots.

## Architecture

This follows the Nexus Three-Layer Architecture:

1. **Atomic Layer** (`packages/Setting`) - Pure business logic, no external dependencies
2. **Adapter Layer** (`adapters/Laravel/Setting`) - Framework-specific implementations
3. **Application Layer** - Uses adapters through interfaces

## Dependencies

- `nexus/setting` - The atomic Setting package
- `illuminate/support` - Laravel framework components
- `illuminate/database` - Laravel database components
- `psr/log` - PSR-3 logging interface
