# Nexus Laravel Monitoring Adapter

This adapter provides Laravel-specific implementations for the Monitoring package.

## Installation

```bash
composer require nexus/laravel-monitoring-adapter
```

## Adapters Provided

### TelemetryTrackerAdapter

Implements `Nexus\Telemetry\Contracts\TelemetryTrackerInterface` using Laravel's logging/caching.

### MetricStorageAdapter

Implements `Nexus\Telemetry\Contracts\MetricStorageInterface` using Laravel's database.

## Service Provider

The `MonitoringAdapterServiceProvider` automatically binds the Monitoring interfaces.
