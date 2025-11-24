# Getting Started with Nexus Identity

## Prerequisites

- **PHP 8.3 or higher** (native enums, readonly properties, constructor property promotion)
- **Composer** for package management
- **Redis or Memcached** (recommended for session storage and permission caching)
- **HTTPS** (required for WebAuthn/passkey authentication)

### Optional
- **QR Code scanner app** (Google Authenticator, Authy, Microsoft Authenticator) for TOTP MFA
- **FIDO2-compatible device** (YubiKey, Touch ID, Face ID, Windows Hello) for passwordless auth

---

## When to Use This Package

This package is designed for:

✅ **Multi-tenant ERP/SaaS applications** requiring user authentication and authorization  
✅ **Enterprise applications** needing comprehensive security (password policies, MFA, audit logging)  
✅ **Applications requiring modern authentication** (passwordless, WebAuthn/passkeys, TOTP)  
✅ **Applications with complex RBAC** (hierarchical roles, wildcard permissions)  
✅ **Applications needing framework flexibility** (Laravel today, Symfony tomorrow)

Do NOT use this package for:

❌ **Single-page apps with JWT-only auth** (too heavyweight, use simpler token-based auth)  
❌ **Public APIs without user accounts** (use API gateway with API keys instead)  
❌ **Non-PHP applications** (package is PHP-only)  
❌ **Applications already using Auth0/Okta** (unless migrating away from them)

---

## Core Concepts

### Concept 1: Framework Agnosticism

**Nexus Identity** contains ZERO framework dependencies in its core. All business logic is pure PHP 8.3+.

- **The Package** defines WHAT needs to be done (interfaces, services, value objects)
- **The Application** defines HOW it's done (Eloquent models, Doctrine entities, Redis sessions)

**Example:**
```php
// Package defines the contract
interface UserRepositoryInterface {
    public function findById(string $id): UserInterface;
}

// Laravel app provides the implementation
class EloquentUserRepository implements UserRepositoryInterface {
    public function findById(string $id): UserInterface {
        return User::findOrFail($id); // Eloquent model
    }
}
```

### Concept 2: Multi-Tenancy is Mandatory

EVERY entity in Identity is **tenant-scoped** by design:
- Users belong to a tenant
- Roles belong to a tenant  
- Sessions belong to a user (who belongs to a tenant)

**You cannot bypass tenant isolation.** This is a security feature, not a bug.

```php
// Correct: Repository auto-scopes by tenant
$users = $userRepository->findAll(); // Only returns current tenant's users

// Wrong: Trying to access cross-tenant data will fail
$otherTenantUser = $userRepository->findById('user-from-other-tenant'); // Throws exception
```

### Concept 3: Interfaces Define Dependencies

Every external dependency is an **interface**:
- Password hashing? → `PasswordHasherInterface`
- Session storage? → `SessionManagerInterface`
- Caching? → `CacheRepositoryInterface`

Your application binds concrete implementations to these interfaces via dependency injection.

### Concept 4: RBAC with Wildcards

Permissions use a **wildcard system**:
- `users.*` grants ALL user permissions (`users.create`, `users.edit`, `users.delete`, etc.)
- `reports.*.view` grants view permission for ALL report types
- `invoices.1234.edit` grants edit permission for specific invoice (resource-level)

Wildcard resolution is **automatic** - no manual pattern matching needed.

### Concept 5: MFA is Pluggable

MFA methods are **optional and modular**:
- TOTP (RFC 6238) - Built-in ✅
- WebAuthn/Passkeys (FIDO2) - Built-in ✅
- Backup Codes - Built-in ✅
- SMS - Interface defined, implementation TBD (use `Nexus\Notifier`)
- Email - Interface defined, implementation TBD (use `Nexus\Notifier`)

You can add custom MFA methods by implementing `MfaVerifierInterface`.

---

## Installation

```bash
composer require nexus/identity:"*@dev"
```

---

## Basic Configuration

### Step 1: Implement Required Interfaces

The package requires 3 core repository interfaces:

#### 1.1 User Repository

```php
namespace App\Repositories;

use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Contracts\UserInterface;
use App\Models\User;

final readonly class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): UserInterface
    {
        return User::findOrFail($id);
    }

    public function findByEmail(string $email): ?UserInterface
    {
        return User::where('email', $email)->first();
    }

    public function save(UserInterface $user): void
    {
        $user->save();
    }

    public function delete(string $id): void
    {
        User::findOrFail($id)->delete();
    }
}
```

#### 1.2 Role Repository

```php
namespace App\Repositories;

use Nexus\Identity\Contracts\RoleRepositoryInterface;
use App\Models\Role;

final readonly class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findById(string $id): RoleInterface
    {
        return Role::findOrFail($id);
    }

    public function findByName(string $name): ?RoleInterface
    {
        return Role::where('name', $name)->first();
    }

    // ... other methods
}
```

#### 1.3 Permission Repository

```php
namespace App\Repositories;

use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use App\Models\Permission;

final readonly class EloquentPermissionRepository implements PermissionRepositoryInterface
{
    public function findById(string $id): PermissionInterface
    {
        return Permission::findOrFail($id);
    }

    // ... other methods
}
```

### Step 2: Bind Interfaces in Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Identity\Contracts\{
    UserRepositoryInterface,
    RoleRepositoryInterface,
    PermissionRepositoryInterface,
    PasswordHasherInterface,
    PasswordValidatorInterface,
    SessionManagerInterface,
    TokenManagerInterface
};
use App\Repositories\{
    EloquentUserRepository,
    EloquentRoleRepository,
    EloquentPermissionRepository
};
use App\Services\{
    LaravelPasswordHasher,
    LaravelPasswordValidator,
    LaravelSessionManager,
    LaravelTokenManager
};

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->singleton(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->singleton(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->singleton(PermissionRepositoryInterface::class, EloquentPermissionRepository::class);

        // Laravel-specific services
        $this->app->singleton(PasswordHasherInterface::class, LaravelPasswordHasher::class);
        $this->app->singleton(PasswordValidatorInterface::class, LaravelPasswordValidator::class);
        $this->app->singleton(SessionManagerInterface::class, LaravelSessionManager::class);
        $this->app->singleton(TokenManagerInterface::class, LaravelTokenManager::class);

        // Package services (auto-wired)
        $this->app->singleton(\Nexus\Identity\Services\UserManager::class);
        $this->app->singleton(\Nexus\Identity\Services\AuthenticationService::class);
        $this->app->singleton(\Nexus\Identity\Services\PermissionChecker::class);
        $this->app->singleton(\Nexus\Identity\Services\RoleManager::class);
        $this->app->singleton(\Nexus\Identity\Services\PermissionManager::class);
        $this->app->singleton(\Nexus\Identity\Services\MfaEnrollmentService::class);
        $this->app->singleton(\Nexus\Identity\Services\MfaVerificationService::class);
    }
}
```

Register in `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\IdentityServiceProvider::class,
],
```

### Step 3: Create Eloquent Models

Your Eloquent models must implement package interfaces:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\ValueObjects\UserStatus;

class User extends Model implements UserInterface
{
    protected $fillable = ['email', 'name', 'password', 'status'];

    protected $casts = [
        'status' => UserStatus::class,
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->password;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    // ... implement other interface methods
}
```

### Step 4: Run Migrations

Create migrations for all Identity tables (see integration-guide.md for complete schema).

---

## Your First Integration

### Example 1: User Login

```php
use Nexus\Identity\Services\AuthenticationService;
use Nexus\Identity\ValueObjects\Credentials;
use Nexus\Identity\Exceptions\InvalidCredentialsException;
use Nexus\Identity\Exceptions\AccountLockedException;

class AuthController
{
    public function __construct(
        private readonly AuthenticationService $authService
    ) {}

    public function login(Request $request)
    {
        $credentials = new Credentials(
            email: $request->input('email'),
            password: $request->input('password')
        );

        try {
            $result = $this->authService->authenticate($credentials);
            
            return response()->json([
                'message' => 'Login successful',
                'session_token' => $result['session_token'],
                'user' => $result['user'],
            ]);
            
        } catch (InvalidCredentialsException $e) {
            return response()->json(['error' => 'Invalid email or password'], 401);
            
        } catch (AccountLockedException $e) {
            return response()->json(['error' => 'Account locked. Try again later.'], 423);
        }
    }
}
```

### Example 2: Check Permission

```php
use Nexus\Identity\Services\PermissionChecker;
use Nexus\Identity\Exceptions\InsufficientPermissionsException;

class InvoiceController
{
    public function __construct(
        private readonly PermissionChecker $permissions
    ) {}

    public function store(Request $request)
    {
        $user = $request->user(); // Authenticated user
        
        if (!$this->permissions->can($user->getId(), 'invoices.create')) {
            throw new InsufficientPermissionsException('Cannot create invoices');
        }

        // Create invoice...
    }
}
```

### Example 3: Enroll TOTP MFA

```php
use Nexus\Identity\Services\MfaEnrollmentService;

class MfaController
{
    public function __construct(
        private readonly MfaEnrollmentService $mfaService
    ) {}

    public function enrollTotp(Request $request)
    {
        $userId = $request->user()->getId();
        
        $result = $this->mfaService->enrollTotp(
            userId: $userId,
            issuer: 'Nexus ERP',
            accountName: $request->user()->email
        );

        return response()->json([
            'qr_code' => $result['qrCodeDataUrl'], // Base64 PNG image
            'secret' => $result['secret'], // For manual entry
            'message' => 'Scan QR code with authenticator app',
        ]);
    }

    public function verifyTotpEnrollment(Request $request)
    {
        $this->mfaService->verifyTotpEnrollment(
            userId: $request->user()->getId(),
            code: $request->input('code')
        );

        return response()->json(['message' => 'TOTP enrolled successfully']);
    }
}
```

---

## Next Steps

- **Read the [API Reference](api-reference.md)** for detailed interface documentation
- **Check [Integration Guide](integration-guide.md)** for complete Laravel/Symfony examples
- **See [Examples](examples/)** for more code samples
- **Review [REQUIREMENTS.md](../REQUIREMENTS.md)** for all 401 requirements

---

## Troubleshooting

### Common Issues

#### Issue 1: "Interface not bound"

**Error:**
```
Target [Nexus\Identity\Contracts\UserRepositoryInterface] is not instantiable.
```

**Cause:** Interface not bound in service provider

**Solution:**
```php
$this->app->singleton(
    UserRepositoryInterface::class,
    EloquentUserRepository::class
);
```

#### Issue 2: "Tenant context missing"

**Error:**
```
Call to a member function getCurrentTenantId() on null
```

**Cause:** `Nexus\Tenant` package not configured

**Solution:** Install and configure `Nexus\Tenant` package, ensure tenant middleware is active.

#### Issue 3: "Invalid session token"

**Error:**
```
InvalidSessionException: Session expired or invalid
```

**Cause:** Session expired or revoked

**Solution:**
- Check session lifetime in config
- Verify session token is sent correctly in `X-Session-Token` header
- Check if session was manually revoked

#### Issue 4: "TOTP verification failing"

**Error:**
```
MfaVerificationException: Invalid TOTP code
```

**Cause:** Time drift between server and authenticator app

**Solution:**
- Ensure server time is synchronized (use NTP)
- Check TOTP window configuration (default ±30 seconds)
- Verify secret was correctly entered in authenticator app

#### Issue 5: "WebAuthn registration failing"

**Error:**
```
WebAuthnVerificationException: Attestation verification failed
```

**Cause:** Origin mismatch or HTTPS requirement

**Solution:**
- Ensure application is served over HTTPS (required for WebAuthn)
- Verify origin matches application URL exactly (https://example.com)
- Check browser console for WebAuthn API errors

---

**Last Updated:** 2024-11-24  
**Package Version:** 1.0.0  
**Maintained By:** Nexus Architecture Team
