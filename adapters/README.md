# Adapters Directory

This directory contains adapter packages that bridge Nexus packages with external systems.

Adapters are responsible for:
- Framework-specific implementations (ServiceProviders, Eloquent Models)
- Third-party API integrations
- Database/ORM adapters
- HTTP layer (Controllers, Resources)
- Background job implementations

## Creating an Adapter

1. Create a new directory: `adapters/Framework/Package/` (e.g., `adapters/Laravel/Finance/`)
2. Initialize with `composer init` (name: `nexus/laravel-package`)
3. Depend on required Nexus packages and framework libraries
4. Implement interfaces defined in domain packages

## Structure

```
adapters/
├── Laravel/                   # Laravel framework adapters
│   ├── Finance/               # Finance package adapter
│   │   ├── composer.json
│   │   ├── README.md
│   │   └── src/
│   │       ├── ServiceProviders/
│   │       ├── Models/
│   │       ├── Repositories/
│   │       ├── Migrations/
│   │       ├── Http/
│   │       │   ├── Controllers/
│   │       │   └── Resources/
│   │       └── Jobs/
│   └── Inventory/             # Inventory package adapter
│       └── ...
├── Symfony/                   # Symfony framework adapters (future)
├── Doctrine/                  # Doctrine ORM adapters (future)
└── README.md
```

## Available Adapters

### Laravel Adapters

| Adapter | Package | Description |
|---------|---------|-------------|
| `nexus/laravel-finance` | `adapters/Laravel/Finance` | Eloquent implementations for Finance package |
| `nexus/laravel-inventory` | `adapters/Laravel/Inventory` | Eloquent implementations for Inventory package |

## Guidelines

1. **Adapters MAY depend on framework-specific code**
   - ServiceProviders extend `Illuminate\Support\ServiceProvider`
   - Models extend `Illuminate\Database\Eloquent\Model`
   
2. **Adapters MUST implement interfaces from domain packages**
   - `EloquentAccountRepository` implements `AccountRepositoryInterface`
   
3. **Adapters SHOULD be thin wrappers around framework functionality**
   - No business logic in adapters
   - Delegate all domain operations to package services
   
4. **Domain logic belongs in domain packages, NOT adapters**
   - Adapters only translate between framework and domain interfaces
   
5. **Packages MUST NEVER depend on adapters**
   - Dependency direction: `adapters/` → `packages/`
   - Never: `packages/` → `adapters/`

## Installation

Add the desired adapter to your Laravel application:

```bash
# Finance adapter
composer require nexus/laravel-finance

# Inventory adapter
composer require nexus/laravel-inventory
```

The service providers are auto-discovered by Laravel.
