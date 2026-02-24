# Nexus Laravel AuditLogger Adapter

This adapter provides Laravel-specific implementations for the AuditLogger package.

## Installation

```bash
composer require nexus/laravel-auditlogger-adapter
```

## Adapters Provided

### AuditLogRepositoryAdapter

Implements `Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface` using Laravel's database.

## Service Provider

The `AuditLoggerAdapterServiceProvider` automatically binds the AuditLogger interfaces.
