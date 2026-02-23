# Nexus\IdentityOperations

> **Orchestrator for user lifecycle management**
>
> Coordinates Identity, Tenant, and AuditLogger packages for user lifecycle management.

---

## Overview

The **IdentityOperations** orchestrator is a non-business operational orchestrator that manages user lifecycle operations within the Nexus ERP system. It provides comprehensive user management workflows including onboarding, authentication, permissions, and MFA management.

### Key Capabilities

- **User Onboarding** - Create users, assign to tenants, setup permissions, send welcome notifications
- **User Lifecycle** - Activate, suspend, deactivate users with proper audit trails
- **MFA Management** - Enable, verify, disable MFA for users
- **Permission Management** - Assign, revoke, check permissions

### Value to Other Orchestrators

| Orchestrator | Value Provided |
|--------------|----------------|
| **TenantOperations** | Validates tenant has active users |
| **FinanceOperations** | Validates user has proper financial permissions |
| **HumanResourceOperations** | Manages employee user accounts |
| **SalesOperations** | Manages sales user permissions |
| **ProcurementOperations** | Manages procurement user permissions |
| **SupplyChainOperations** | Manages warehouse user permissions |
| **CRMOperations** | Manages CRM user permissions |

---

## Quick Start

### Example: User Onboarding

```php
use Nexus\IdentityOperations\Coordinators\UserOnboardingCoordinator;
use Nexus\IdentityOperations\DTOs\UserCreateRequest;

$coordinator = $container->get(UserOnboardingCoordinator::class);

$request = new UserCreateRequest(
    email: 'john.doe@example.com',
    password: 'secure-password',
    firstName: 'John',
    lastName: 'Doe',
    tenantId: 'tenant-123',
    roles: ['user'],
    sendWelcomeEmail: true,
);

$result = $coordinator->createUser($request);

if ($result->success) {
    echo "User created: {$result->userId}";
    echo "Tenant assignment: {$result->tenantUserId}";
} else {
    echo "Onboarding failed: {$result->message}";
}
```

### Example: User Lifecycle (Suspend)

```php
use Nexus\IdentityOperations\Coordinators\UserLifecycleCoordinator;
use Nexus\IdentityOperations\DTOs\UserSuspendRequest;

$coordinator = $container->get(UserLifecycleCoordinator::class);

$request = new UserSuspendRequest(
    userId: 'user-456',
    suspendedBy: 'admin-789',
    reason: 'Policy violation',
);

$result = $coordinator->suspend($request);

if ($result->success) {
    echo "User suspended: {$result->suspendedAt}";
} else {
    echo "Suspend failed: {$result->message}";
}
```

### Example: MFA Enable

```php
use Nexus\IdentityOperations\Coordinators\MfaCoordinator;
use Nexus\IdentityOperations\DTOs\MfaEnableRequest;

$coordinator = $container->get(MfaCoordinator::class);

$request = new MfaEnableRequest(
    userId: 'user-456',
    method: MfaMethod::TOTP,
);

$result = $coordinator->enable($request);

if ($result->success) {
    echo "MFA enabled, secret: {$result->secret}";
    echo "QR code: {$result->qrCodeUrl}";
}
```

### Example: Permission Assignment

```php
use Nexus\IdentityOperations\Coordinators\UserPermissionCoordinator;
use Nexus\IdentityOperations\DTOs\PermissionAssignRequest;

$coordinator = $container->get(UserPermissionCoordinator::class);

$request = new PermissionAssignRequest(
    userId: 'user-456',
    permission: 'finance.reports.view',
    tenantId: 'tenant-123',
    assignedBy: 'admin-789',
);

$result = $coordinator->assign($request);

if ($result->success) {
    echo "Permission assigned: {$result->permissionId}";
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
└── Exceptions/            # Domain errors
```

---

## Available Coordinators

| Coordinator | Purpose | Key Operations |
|-------------|---------|----------------|
| `UserOnboardingCoordinator` | Create new users | `createUser()`, `setupInitialPermissions()` |
| `UserLifecycleCoordinator` | Manage user states | `activate()`, `suspend()`, `deactivate()` |
| `UserAuthenticationCoordinator` | Authenticate users | `authenticate()`, `refreshToken()`, `logout()` |
| `UserPermissionCoordinator` | Manage permissions | `assign()`, `revoke()`, `check()` |
| `MfaCoordinator` | Manage MFA | `enable()`, `verify()`, `disable()` |

---

## Installation

```bash
composer require nexus/identity-operations
```

### Dependencies

- `nexus/identity` - Core user management
- `nexus/tenant` - Tenant context
- `nexus/audit-logger` - Audit trail logging
- `nexus/common` - Common utilities

---

## Architecture Layers

```
┌─────────────────────────────────────────────────────────┐
│                    Adapters (L3)                        │
│   Implements orchestrator interfaces                    │
└─────────────────────────────────────────────────────────┘
                            ▲ implements
┌─────────────────────────────────────────────────────────┐
│              IdentityOperations (L2)                     │
│   - Defines own interfaces in Contracts/                │
│   - Depends only on PSR interfaces                      │
│   - Coordinates multi-package workflows                 │
└─────────────────────────────────────────────────────────┘
                            ▲ uses via interfaces
┌─────────────────────────────────────────────────────────┐
│                Atomic Packages (L1)                      │
│   - Identity, Tenant, AuditLogger                       │
└─────────────────────────────────────────────────────────┘
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

- [Nexus Architecture Guidelines](../../ARCHITECTURE.md) - System-wide patterns
