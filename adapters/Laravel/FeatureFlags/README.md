# Nexus Laravel FeatureFlags Adapter

This adapter provides Laravel-specific implementations for the FeatureFlags package.

## Purpose

The FeatureFlags package is an atomic package. This adapter provides Laravel integration.

## Installation

```bash
composer require nexus/laravel-featureflags-adapter
```

## Adapters Provided

### FlagCacheAdapter

Implements `Nexus\FeatureFlags\Contracts\FlagCacheInterface` using Laravel's cache.

### FlagRepositoryAdapter

Implements `Nexus\FeatureFlags\Contracts\FlagRepositoryInterface` using Laravel's database.

## Service Provider

The `FeatureFlagsAdapterServiceProvider` automatically binds the FeatureFlags interfaces.

## Dependencies

- `nexus/featureflags` - The atomic FeatureFlags package
- `illuminate/support` - Laravel framework components
