# Canary atomy API

A Symfony 7.4 API Platform application providing RESTful API endpoints for the Nexus enterprise management system. This API serves as the backend interface for module management, user access, and multi-tenant operations.

## Overview

The Canary atomy API is built on [API Platform](https://api-platform.com/) (v4.2) and provides:

- **Module Management**: Browse and install available Nexus modules
- **User Management**: Access user data with multi-tenant filtering
- **Multi-Tenancy**: Full tenant isolation via header-based context
- **RESTful API**: Standard JSON API with OpenAPI documentation

## Technology Stack

| Component | Version |
|-----------|---------|
| PHP | >=8.2 |
| Symfony | 7.4.* |
| API Platform | ^4.2.17 |
| Doctrine ORM | ^3.6.2 |
| PostgreSQL | 16.12 |

## Prerequisites

- PHP 8.2 or higher
- Composer
- PostgreSQL 16+ (or Docker for containerized setup)
- Symfony CLI (recommended)

## Installation

### 1. Clone and Install Dependencies

```bash
cd apps/canary-atomy-api
composer install
```

### 2. Configure Environment

Copy the environment file and adjust settings as needed:

```bash
cp .env .env.local
```

The default configuration uses PostgreSQL. Update `DATABASE_URL` in `.env.local` if needed:

```bash
DATABASE_URL="postgresql://postgres:postgres@localhost:5432/postgres?serverVersion=16"
```

### 3. Set Up the Database

Create the database and run migrations:

```bash
# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate
```

### 4. Start the Development Server

```bash
# Using Symfony CLI
symfony server:start

# Or using PHP built-in server
php -S localhost:8000 -t public/
```

The API will be available at `http://localhost:8000`

## Configuration

### Database Configuration

Configure your database connection in `.env`:

```bash
# PostgreSQL (default)
DATABASE_URL="postgresql://postgres:postgres@localhost:5432/postgres?serverVersion=16"

# MySQL/MariaDB alternative
DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"

# SQLite (development only)
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
```

### CORS Configuration

The application uses NelmioCorsBundle for Cross-Origin Resource Sharing. Configure allowed origins in `.env`:

```bash
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### Security Configuration

The application uses HTTP Basic Authentication. Default users are configured in `config/packages/security.yaml`:

| Username | Password | Roles |
|----------|----------|-------|
| admin | (hashed) | ROLE_USER, ROLE_ADMIN |
| user | (hashed) | ROLE_USER |

> **Note**: In production, configure proper authentication (JWT, OAuth, etc.) via Symfony Security.

## Running the Application

### Development Commands

```bash
# Clear cache
php bin/console cache:clear

# Run the development server
php -S localhost:8000 -t public/

# Check API status
php bin/console debug:router

# View available services
php bin/console debug:container
```

### Docker Setup

The project includes Docker configuration for quick setup:

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

## API Documentation

### Base URL

```
http://localhost:8000/api
```

### Available EndpointsT

#### Modules

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/modules` | List all available modules |
| GET | `/api/modules/{moduleId}` | Get specific module details |
| POST | `/api/modules/{moduleId}/install` | Install a module (requires ADMIN) |
| DELETE | `/api/modules/{id}` | Uninstall a module (requires ADMIN) |

#### Users

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/users` | List users (filtered by tenant) |

### OpenAPI Documentation

Access the interactive API documentation:

- **Swagger UI**: `http://localhost:8000/api/docs`
- **ReDoc**: `http://localhost:8000/api/docs.json` (OpenAPI JSON)

## Multi-Tenancy

The API supports multi-tenant operations through tenant context. All tenant-aware endpoints use the `X-Tenant-ID` header to identify the current tenant.

### Setting Tenant Context

Include the `X-Tenant-ID` header in all requests:

```bash
curl -H "X-Tenant-ID: tenant-123" http://localhost:8000/api/modules
```

### Tenant Context Priority

The tenant is resolved in the following order:

1. **Explicitly set tenant** (via code)
2. **X-Tenant-ID header** (HTTP header)
3. **tenant_id query parameter** (`?tenant_id=xxx`)
4. **Authenticated user's tenant** (from JWT/token)

### TenantSubscriber

The [`TenantSubscriber`](src/EventSubscriber/TenantSubscriber.php) automatically extracts and sets the tenant context for every request:

```php
// Priority: Header > Query Parameter > Route Attribute
$tenantId = $request->headers->get('X-Tenant-ID');
```

## Module Management

### Available Modules

The API discovers modules from the `orchestrators/` directory. Each orchestrator is treated as an installable module with metadata read from its `composer.json`.

### Current Available Orchestrators

| Module ID | Description |
|-----------|-------------|
| AccountingOperations | Period close, consolidation, statements, ratios |
| ComplianceOperations | Compliance monitoring, audit trails |
| ConnectivityOperations | Integration connectivity |
| CRMOperations | Lead management, pipeline, activities |
| CustomerServiceOperations | Customer service management |
| DataExchangeOperations | Data import/export |
| FinanceOperations | General ledger operations |
| HumanResourceOperations | Employee lifecycle, payroll |
| IdentityOperations | User lifecycle, MFA, sessions |
| InsightOperations | Analytics and reporting |
| IntelligenceOperations | ML orchestration |
| ManufacturingOperations | MRP II, BOMs, work orders |
| ProcurementOperations | Purchase-to-pay, supplier management |
| ProjectManagementOperations | Project tracking |
| SalesOperations | Quote-to-cash, opportunities |
| SettingsManagement | Application settings |
| SupplyChainOperations | Inventory, procurement, warehouse |
| SystemAdministration | System management |
| TenantOperations | Multi-tenant operations |

### Installing a Module

```bash
# Install a module
curl -X POST http://localhost:8000/api/modules/AccountingOperations/install \
  -H "Content-Type: application/json" \
  -H "X-Tenant-ID: tenant-123" \
  -u admin:password
```

### Uninstalling a Module

```bash
# Uninstall a module (by installed module ID)
curl -X DELETE http://localhost:8000/api/modules/{installed-module-id} \
  -H "X-Tenant-ID: tenant-123" \
  -u admin:password
```

## Authentication & Authorization

### HTTP Basic Authentication

The API uses HTTP Basic Authentication for simplicity. Include credentials in requests:

```bash
curl -u admin:password http://localhost:8000/api/modules
```

### Role-Based Access Control

| Role | Description |
|------|-------------|
| ROLE_USER | Basic user access |
| ROLE_ADMIN | Administrative access (module installation) |

### Securing Endpoints

The `InstalledModule` entity has built-in security:

```php
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/modules/{moduleId}/install',
            security: 'is_granted("ROLE_ADMIN")'
        ),
    ],
)]
```

## Project Structure

```
apps/canary-atomy-api/
├── config/              # Symfony configuration
│   └── packages/        # Package-specific config (security, doctrine, etc.)
├── migrations/          # Doctrine migrations
├── public/              # Web root
├── src/
│   ├── ApiResource/     # API Platform resources (DTOs)
│   │   ├── Module.php
│   │   └── User.php
│   ├── Controller/      # Symfony controllers
│   ├── Entity/          # Doctrine entities
│   │   └── InstalledModule.php
│   ├── EventSubscriber/ # Event subscribers
│   │   └── TenantSubscriber.php
│   ├── Repository/      # Doctrine repositories
│   ├── Service/         # Business logic services
│   │   ├── ModuleInstaller.php
│   │   ├── ModuleRegistry.php
│   │   └── TenantContext.php
│   └── State/           # API Platform state processors
│       ├── ModuleCollectionProvider.php
│       ├── ModuleItemProvider.php
│       └── UserCollectionProvider.php
├── templates/           # Twig templates
├── bin/                 # Console commands
├── compose.yaml         # Docker Compose configuration
├── composer.json        # PHP dependencies
└── symfony.lock        # Symfony lock file
```

## Key Services

### ModuleRegistry

Scans the orchestrators directory to discover available modules:

```php
$moduleRegistry->getAvailableModules();
$moduleRegistry->getModule($moduleId);
$moduleRegistry->moduleExists($moduleId);
```

### ModuleInstaller

Handles module installation lifecycle:

```php
$installer->install($moduleId, $installedBy);
$installer->uninstall($moduleId);
$installer->isInstalled($moduleId);
$installer->getInstalledModules();
```

### TenantContext

Manages the current tenant context:

```php
$tenantContext->setTenant($tenantId);
$tenantContext->getCurrentTenantId();
$tenantContext->hasTenant();
$tenantContext->requireTenant();
```

## Troubleshooting

### Database Connection Issues

```bash
# Verify database connection
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:query:sql "SELECT 1"
```

### Clear Cache

```bash
# Clear all caches
php bin/console cache:clear

# Clear specific environment cache
php bin/console cache:clear --env=prod
```

### View Logs

```bash
# Development logs
tail -f var/log/dev.log

# Production logs
tail -f var/log/prod.log
```

## Contributing

Follow the [Nexus Architecture Guidelines](../../docs/project/ARCHITECTURE.md) when modifying this API:

- Use strict types (`declare(strict_types=1);`)
- Follow PSR-4 autoloading standards
- Implement proper type hints and return types
- Add docblocks for all public APIs
- Use the three-layer architecture (Packages → Orchestrators → Adapters)

## License

Proprietary - All rights reserved
