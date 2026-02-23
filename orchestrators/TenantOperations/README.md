# Nexus\TenantOperations

> **Orchestrator for multi-tenant lifecycle management**
>
> Coordinates Tenant, Setting, FeatureFlags, Backoffice, AuditLogger, and Identity packages.

---

## Overview

The **TenantOperations** orchestrator is a non-business operational orchestrator that manages multi-tenant infrastructure within the Nexus ERP system. It provides the foundation for all other operational orchestrators to function correctly in a multi-tenant environment.

### Key Capabilities

- **Tenant Onboarding** - Create new tenants with settings, features, and company structure
- **Tenant Lifecycle** - Suspend, activate, archive, and delete tenants
- **Impersonation** - Admin user impersonation for support operations
- **Validation** - Tenant state validation for other orchestrators

### Value to Other Orchestrators

| Orchestrator | Value Provided |
|--------------|----------------|
| **FinanceOperations** | Validates tenant has proper licensing for financial modules |
| **HumanResourceOperations** | Validates organizational chart exists for tenant |
| **AccountingOperations** | Validates tenant has ChartOfAccount configured |
| **SalesOperations** | Validates tenant has sales configuration |
| **ProcurementOperations** | Validates tenant has supplier management enabled |
| **SupplyChainOperations** | Validates tenant has warehouse configurations |
| **CRMOperations** | Validates tenant has CRM modules enabled |

---

## Quick Start

### Example: Tenant Onboarding

```php
use Nexus\TenantOperations\Coordinators\TenantOnboardingCoordinator;
use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;

$coordinator = $container->get(TenantOnboardingCoordinator::class);

$request = new TenantOnboardingRequest(
    tenantCode: 'acme-corp',
    tenantName: 'Acme Corporation',
    domain: 'acme-corp.nexuserp.com',
    adminEmail: 'admin@acme-corp.com',
    adminPassword: 'secure-password',
    plan: 'professional',
    currency: 'USD',
    timezone: 'America/New_York',
    language: 'en',
);

$result = $coordinator->onboard($request);

if ($result->success) {
    echo "Tenant created: {$result->tenantId}";
    echo "Admin user: {$result->adminUserId}";
    echo "Company: {$result->companyId}";
} else {
    echo "Onboarding failed: {$result->message}";
    print_r($result->issues);
}
```

### Example: Tenant Validation

```php
use Nexus\TenantOperations\Coordinators\TenantValidationCoordinator;
use Nexus\TenantOperations\DTOs\ModulesValidationRequest;

$coordinator = $container->get(TenantValidationCoordinator::class);

// Validate tenant has required modules
$request = new ModulesValidationRequest(
    tenantId: 'tenant-123',
    requiredModules: ['finance', 'hr', 'sales'],
);

$result = $coordinator->validateModules($request);

if ($result->valid) {
    echo "Tenant has all required modules";
} else {
    echo "Validation failed:";
    foreach ($result->errors as $error) {
        echo "- {$error['message']}";
    }
}
```

### Example: Impersonation

```php
use Nexus\TenantOperations\Coordinators\TenantImpersonationCoordinator;
use Nexus\TenantOperations\DTOs\ImpersonationStartRequest;

$coordinator = $container->get(TenantImpersonationCoordinator::class);

// Start impersonation
$request = new ImpersonationStartRequest(
    adminUserId: 'admin-456',
    targetTenantId: 'tenant-123',
    reason: 'Investigating user issue',
    sessionTimeoutMinutes: 30,
);

$result = $coordinator->startImpersonation($request);

if ($result->success) {
    echo "Impersonation started: {$result->sessionId}";
    echo "Expires at: {$result->expiresAt}";
}
```

---

## Architecture

This orchestrator follows the **Advanced Orchestrator Pattern** with these principles:

1. **Coordinators are Traffic Cops** - Direct flow, don't do work
2. **DataProviders Aggregate** - Cross-package data aggregation
3. **Rules are Composable** - Individual, testable validation classes
4. **Services do Heavy Lifting** - Complex business logic
5. **Strict Contracts** - Always use DTOs

---

## Directory Structure

```
src/
├── Coordinators/           # Entry points for operations
├── DataProviders/         # Cross-package data aggregation
├── Rules/                 # Validation constraints
├── Services/              # Complex business logic
├── DTOs/                 # Request/Response objects
├── Contracts/             # Interfaces
├── Exceptions/            # Domain errors
└── Workflows/           # Stateful processes (planned)
```

---

## Available Coordinators

| Coordinator | Purpose | Key Operations |
|-------------|---------|----------------|
| `TenantOnboardingCoordinator` | Create new tenants | `onboard()`, `validatePrerequisites()` |
| `TenantLifecycleCoordinator` | Manage tenant states | `suspend()`, `activate()`, `archive()`, `delete()` |
| `TenantImpersonationCoordinator` | Admin impersonation | `startImpersonation()`, `endImpersonation()` |
| `TenantValidationCoordinator` | Validate tenant state | `validateActive()`, `validateModules()`, `validateConfiguration()` |

---

## Installation

```bash
composer require nexus/tenant-operations
```

### Dependencies

- `nexus/tenant` - Core tenant management
- `nexus/setting` - Tenant-specific settings
- `nexus/feature-flags` - Feature flag management
- `nexus/backoffice` - Company structure
- `nexus/audit-logger` - Audit trail logging
- `nexus/identity` - User authentication/authorization

---

## Architecture Layers

```
┌─────────────────────────────────────────────────────┐
│                    Adapters (L3)                    │
│   Implements orchestrator interfaces                │
└─────────────────────────────────────────────────────┘
                          ▲ implements
┌─────────────────────────────────────────────────────┐
│              TenantOperations (L2)                  │
│   - Defines own interfaces in Contracts/            │
│   - Depends only on PSR interfaces                 │
│   - Coordinates multi-package workflows              │
└─────────────────────────────────────────────────────┘
                          ▲ uses via interfaces
┌─────────────────────────────────────────────────────┐
│              Atomic Packages (L1)                    │
│   - Tenant, Setting, FeatureFlags                   │
│   - Backoffice, AuditLogger, Identity              │
└─────────────────────────────────────────────────────┘
```

---

## Testing

```bash
# Unit tests (Rules, Services)
vendor/bin/phpunit tests/Unit

# Integration tests (Coordinators)
vendor/bin/phpunit tests/Integration
```

---

## License

MIT License

---

## Related Documentation

- [ARCHITECTURAL.md](./ARCHITECTURAL.md) - Architecture details
- [REQUIREMENTS.md](./REQUIREMENTS.md) - Functional requirements
- [Nexus Architecture Guidelines](../../ARCHITECTURE.md) - System-wide patterns
