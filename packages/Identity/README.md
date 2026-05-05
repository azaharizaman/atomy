# Nexus\Identity

**Framework-agnostic Identity and Access Management package for Nexus ERP**

The Identity package provides a comprehensive, pure PHP solution for authentication, authorization, session management, and user identity management. It follows strict contract-driven design principles and integrates seamlessly with the Nexus monorepo architecture.

## Features

- ✅ **Pure PHP 8.3+** - No framework dependencies in core logic
- ✅ **CQRS Architecture** - Separate Query and Persist interfaces for clean separation
- ✅ **Contract-Driven** - All data structures and operations defined via interfaces
- ✅ **Role-Based Access Control (RBAC)** - Flexible permission management with role hierarchy
- ✅ **Direct Permission Assignment** - Bypass roles for fine-grained control
- ✅ **Wildcard Permissions** - `users.*` grants all user permissions
- ✅ **Session Management** - Secure token-based authentication
- ✅ **API Token Authentication** - Scoped tokens for programmatic access
- ✅ **Multi-Factor Authentication (MFA)** - TOTP, WebAuthn/Passkeys, Backup Codes
- ✅ **Single Sign-On (SSO)** - SAML, OAuth2, OIDC support (pluggable)
- ✅ **Password Security** - Argon2id hashing, breach detection, history tracking
- ✅ **Account Lifecycle** - Registration, activation, suspension, locking
- ✅ **Security Events** - Integration with AuditLogger
- ✅ **Multi-Tenant** - Tenant-scoped users and roles

## Installation

```bash
composer require azaharizaman/nexus-identity:"*@dev"
```

## Architecture

### CQRS Pattern (v1.1.0+)

The package follows CQRS (Command Query Responsibility Segregation) pattern. Each repository domain has separate interfaces:

| Domain | Query Interface (Read) | Persist Interface (Write) |
|--------|------------------------|---------------------------|
| User | `UserQueryInterface` | `UserPersistInterface` |
| Role | `RoleQueryInterface` | `RolePersistInterface` |
| Permission | `PermissionQueryInterface` | `PermissionPersistInterface` |
| MFA Enrollment | `MfaEnrollmentQueryInterface` | `MfaEnrollmentPersistInterface` |
| Trusted Device | `TrustedDeviceQueryInterface` | `TrustedDevicePersistInterface` |
| WebAuthn Credential | `WebAuthnCredentialQueryInterface` | `WebAuthnCredentialPersistInterface` |
| Backup Code | `BackupCodeQueryInterface` | `BackupCodePersistInterface` |

**Best Practice:** Inject the specific Query or Persist interface based on your needs:

```php
// For read-only operations
public function __construct(
    private readonly UserQueryInterface $userQuery
) {}

// For write operations
public function __construct(
    private readonly UserPersistInterface $userPersist
) {}
```

### Package Structure

```
packages/domain/identity-security/
├── src/
│   ├── Contracts/              # Interfaces (CQRS Split)
│   │   ├── UserInterface.php
│   │   ├── UserQueryInterface.php      # Read operations
│   │   ├── UserPersistInterface.php    # Write operations
│   │   ├── UserRepositoryInterface.php # Combined (deprecated)
│   │   ├── RoleQueryInterface.php
│   │   ├── RolePersistInterface.php
│   │   ├── PermissionQueryInterface.php
│   │   ├── PermissionPersistInterface.php
│   │   └── ... (40+ interfaces)
│   ├── Services/               # Business Logic
│   │   ├── UserManager.php
│   │   ├── AuthenticationService.php
│   │   ├── MfaEnrollmentService.php
│   │   ├── MfaVerificationService.php
│   │   └── ... (10 services)
│   ├── ValueObjects/           # Immutable Data Structures
│   │   ├── UserStatus.php
│   │   ├── Credentials.php
│   │   ├── WebAuthnCredential.php
│   │   └── ... (20 value objects)
│   └── Exceptions/             # Domain Exceptions
│       ├── UserNotFoundException.php
│       ├── MfaRequiredException.php
│       └── ... (19 exceptions)
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   ├── integration-guide.md
│   └── examples/
├── tests/
├── composer.json
├── LICENSE
└── README.md
```

### Core Principles

1. **Logic in Packages, Implementation in Applications**
   - Package defines **what** (interfaces, services, value objects)
   - Application defines **how** (Eloquent models, repositories, migrations)

2. **Framework Agnostic**
   - Zero Laravel dependencies in `src/`
   - No `Illuminate\*` classes
   - No Eloquent models
   - No database queries

3. **Dependency Injection**
   - Constructor injection for all dependencies
   - Interface-based dependencies only

4. **CQRS Separation**
   - Query interfaces for read operations
   - Persist interfaces for write operations
   - Clear command/query responsibility

## Usage Examples

### User Management

```php
use Nexus\Identity\Services\UserManager;
use Nexus\Identity\ValueObjects\Credentials;

// Create a new user
$user = $userManager->createUser([
    'email' => 'john@example.com',
    'password' => 'SecureP@ssw0rd!',
    'name' => 'John Doe',
    'tenant_id' => 'tenant_ulid',
]);

// Change password
$userManager->changePassword($user->getId(), 'NewSecureP@ssw0rd!');

// Activate user
$userManager->activateUser($user->getId());

// Lock user
$userManager->lockUser($user->getId(), 'Suspicious activity detected');
```

### Authentication

```php
use Nexus\Identity\Services\AuthenticationService;
use Nexus\Identity\ValueObjects\Credentials;

$credentials = new Credentials('john@example.com', 'SecureP@ssw0rd!');

// Login
$result = $authService->login($credentials, [
    'ip' => '192.168.1.1',
    'user_agent' => 'Mozilla/5.0...',
]);

$user = $result['user'];
$session = $result['session'];

// Validate session
$authenticatedUser = $authService->validateSession($session->token);

// Logout
$authService->logout($session->token);
```

### Authorization

#### Basic Permission Checking (RBAC)

```php
use Nexus\Identity\Services\PermissionChecker;

// Check permission
if ($permissionChecker->hasPermission($user, 'users.create')) {
    // User can create users
}

// Check multiple permissions
if ($permissionChecker->hasAllPermissions($user, ['users.create', 'users.update'])) {
    // User has all permissions
}

// Check role
if ($permissionChecker->hasRole($user, 'admin')) {
    // User is an admin
}

// Wildcard permission matching
// If user has "users.*", they have "users.create", "users.update", etc.
```

#### Policy-Based Authorization (ABAC)

For complex authorization that requires context, relationships, or business rules:

```php
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;
use Nexus\Identity\ValueObjects\Policy;

// Register a custom policy
$leavePolicy = Policy::define('hrm.leave.apply_on_behalf')
    ->description('User can apply leave on behalf of employees in same department or as their manager')
    ->check(function(UserInterface $user, string $action, mixed $resource, array $context) use ($employeeQuery) {
        // Extract target employee from context
        $targetEmployeeId = $context['target_employee_id'] ?? null;
        if (!$targetEmployeeId) {
            return false;
        }

        $userEmployee = $employeeQuery->findByUserId($user->getId());
        $targetEmployee = $employeeQuery->findById($targetEmployeeId);

        // Check if same department OR user is manager
        return $userEmployee?->getDepartmentId() === $targetEmployee?->getDepartmentId()
            || $userEmployee?->getId() === $targetEmployee?->getManagerId();
    });

$policyEvaluator->registerPolicy(
    $leavePolicy->getName(),
    $leavePolicy->getEvaluator()
);

// Evaluate policy with context
$canApply = $policyEvaluator->evaluate(
    user: $user,
    action: 'hrm.leave.apply_on_behalf',
    resource: null,
    context: ['target_employee_id' => $employeeId]
);

if ($canApply) {
    // User can apply leave on behalf of this employee
}
```

**When to use Policy-Based Authorization:**
- ✅ Authorization depends on relationships (e.g., manager, same department)
- ✅ Authorization requires resource state (e.g., invoice status, case ownership)
- ✅ Complex multi-condition rules
- ✅ Context-dependent decisions

**When to use Basic Permission Checking:**
- ✅ Simple "can user perform action" checks
- ✅ Role-based access (admin, manager, user)
- ✅ Static permissions that don't change based on context
```

### Role Management

```php
use Nexus\Identity\Services\RoleManager;

// Create a role
$role = $roleManager->createRole([
    'name' => 'manager',
    'description' => 'Department Manager',
    'tenant_id' => 'tenant_ulid',
]);

// Assign permission to role
$roleManager->assignPermission($role->getId(), $permission->getId());

// Assign role to user
$userManager->assignRole($user->getId(), $role->getId());
```

### Permission Management

```php
use Nexus\Identity\Services\PermissionManager;

// Create a permission
$permission = $permissionManager->createPermission([
    'name' => 'users.create',
    'resource' => 'users',
    'action' => 'create',
    'description' => 'Create new users',
]);

// Create wildcard permission
$wildcardPermission = $permissionManager->createPermission([
    'name' => 'users.*',
    'resource' => 'users',
    'action' => '*',
    'description' => 'All user operations',
]);
```

### API Token Management

```php
use Nexus\Identity\Contracts\TokenManagerInterface;

// Generate API token
$token = $tokenManager->generateToken(
    userId: $user->getId(),
    name: 'Production API',
    scopes: ['users.read', 'invoices.read'],
    expiresAt: new \DateTimeImmutable('+1 year')
);

// Validate token
$tokenUser = $tokenManager->validateToken($token->token);

// Revoke token
$tokenManager->revokeToken($token->id);
```

## Value Objects

Value objects are immutable and enforce business rules:

### UserStatus

```php
use Nexus\Identity\ValueObjects\UserStatus;

$status = UserStatus::ACTIVE;
$status->canAuthenticate(); // true

$locked = UserStatus::LOCKED;
$locked->requiresAdminIntervention(); // true
```

### Credentials

```php
use Nexus\Identity\ValueObjects\Credentials;

$credentials = new Credentials('user@example.com', 'password');
// Validates email format on construction
```

### Permission

```php
use Nexus\Identity\ValueObjects\Permission;

$permission = Permission::fromName('users.create');
$permission->resource; // 'users'
$permission->action; // 'create'
$permission->isWildcard(); // false

$wildcard = Permission::fromName('users.*');
$wildcard->matches('users.create'); // true
$wildcard->matches('users.update'); // true
$wildcard->matches('roles.create'); // false
```

## Exception Handling

All domain exceptions extend PHP's base `Exception`:

```php
use Nexus\Identity\Exceptions\UserNotFoundException;
use Nexus\Identity\Exceptions\InvalidCredentialsException;
use Nexus\Identity\Exceptions\InsufficientPermissionsException;

try {
    $user = $userManager->findUser($userId);
} catch (UserNotFoundException $e) {
    // Handle user not found
}

try {
    $authService->login($credentials);
} catch (InvalidCredentialsException $e) {
    // Handle invalid credentials
} catch (AccountLockedException $e) {
    // Handle locked account
}
```

## Integration with Application Layer

The application layer (`apps/Atomy`) must provide implementations for all contracts:

1. **Eloquent Models** implementing entity interfaces
2. **Repositories** implementing repository interfaces
3. **Service Implementations** (password hashing, validation, etc.)
4. **Database Migrations**
5. **Service Provider Bindings**

Example binding in `AppServiceProvider`:

```php
// Repository bindings
$this->app->singleton(UserRepositoryInterface::class, DbUserRepository::class);
$this->app->singleton(RoleRepositoryInterface::class, DbRoleRepository::class);
$this->app->singleton(PermissionRepositoryInterface::class, DbPermissionRepository::class);

// Service implementations
$this->app->singleton(PasswordHasherInterface::class, LaravelPasswordHasher::class);
$this->app->singleton(PasswordValidatorInterface::class, LaravelPasswordValidator::class);
$this->app->singleton(UserAuthenticatorInterface::class, LaravelUserAuthenticator::class);
$this->app->singleton(SessionManagerInterface::class, LaravelSessionManager::class);
$this->app->singleton(TokenManagerInterface::class, LaravelTokenManager::class);

// Permission checker (uses base implementation)
$this->app->singleton(PermissionCheckerInterface::class, PermissionChecker::class);
```

## Security Considerations

1. **Password Hashing**: Use Argon2id or bcrypt (minimum cost 12)
2. **Session Tokens**: Cryptographically secure random tokens (256 bits minimum)
3. **API Tokens**: One-way hashed in database, only shown once on generation
4. **Failed Login Tracking**: Lock account after configurable threshold (default 5)
5. **Password History**: Prevent reuse of last N passwords (default 5)
6. **Session Fingerprinting**: Bind sessions to IP/User-Agent
7. **MFA Enforcement**: Can be required per role
8. **Audit Logging**: All authentication/authorization events logged

## Requirements Addressed

This package addresses all requirements listed in REQUIREMENTS.csv for `Nexus\Identity`:

- ✅ ARC-IDE-1300 to ARC-IDE-1310: Architectural requirements
- ✅ BUS-IDE-1311 to BUS-IDE-1360: Business requirements
- ✅ FUN-IDE-1361 to FUN-IDE-1410: Functional requirements
- ✅ PERF-IDE-1411 to PERF-IDE-1416: Performance requirements
- ✅ REL-IDE-1417 to REL-IDE-1423: Reliability requirements
- ✅ SCL-IDE-1424 to SCL-IDE-1428: Scalability requirements
- ✅ MAINT-IDE-1429 to MAINT-IDE-1435: Maintainability requirements
- ✅ COMP-IDE-1436 to COMP-IDE-1444: Compliance requirements (GDPR, PCI-DSS, NIST)
- ✅ USE-IDE-1445 to USE-IDE-1500: User stories

## Testing

Package tests should use mocks for all repository implementations:

```php
use Nexus\Identity\Services\UserManager;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function test_create_user()
    {
        $mockRepo = $this->createMock(UserRepositoryInterface::class);
        $mockRepo->expects($this->once())
            ->method('emailExists')
            ->willReturn(false);
        
        $userManager = new UserManager($mockRepo, $hasher, $validator);
        // ... test logic
    }
}
```

## 📖 Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites, core concepts, and first integration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all 28 interfaces, 10 services, 20 value objects, and 19 exceptions
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples with complete setup instructions
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple usage patterns for authentication, authorization, and MFA
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Advanced scenarios including WebAuthn, passwordless auth, and complex workflows

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress, metrics, and key design decisions
- `REQUIREMENTS.md` - All 401 requirements with status tracking
- `TEST_SUITE_SUMMARY.md` - Test coverage metrics and test inventory (331+ tests, 95%+ coverage)
- `VALUATION_MATRIX.md` - Package valuation metrics for funding assessment ($300K+ estimated value)
- See root `../../ARCHITECTURE.md` for overall system architecture
- See `../../docs/NEXUS_PACKAGES_REFERENCE.md` for package ecosystem reference

## License

MIT License. See [LICENSE](LICENSE) file for details.
