# API Reference: Identity

Complete API documentation for all interfaces, services, value objects, and exceptions.

## Repository Interfaces (CQRS Architecture)

The Identity package follows CQRS (Command Query Responsibility Segregation) pattern. Each repository domain has separate Query (read) and Persist (write) interfaces.

### User Repository Interfaces

#### UserQueryInterface (Read Model)
**Location:** `src/Contracts/UserQueryInterface.php`

**Methods:**
- `findById(string $id): UserInterface` - Find user by ULID
- `findByEmail(string $email): UserInterface` - Find user by email
- `findByEmailOrNull(string $email): ?UserInterface` - Find user by email or null
- `emailExists(string $email, ?string $excludeUserId = null): bool` - Check email exists
- `getUserRoles(string $userId): array` - Get user's assigned roles
- `getUserPermissions(string $userId): array` - Get user's direct permissions
- `findByStatus(string $status): array` - Find users by status
- `findByRole(string $roleId): array` - Find users by role
- `search(array $criteria): array` - Search users

#### UserPersistInterface (Write Model)
**Location:** `src/Contracts/UserPersistInterface.php`

**Methods:**
- `create(array $data): UserInterface` - Create new user
- `update(string $id, array $data): UserInterface` - Update user
- `delete(string $id): bool` - Delete user
- `assignRole(string $userId, string $roleId): void` - Assign role to user
- `revokeRole(string $userId, string $roleId): void` - Revoke role from user
- `assignPermission(string $userId, string $permissionId): void` - Assign direct permission
- `revokePermission(string $userId, string $permissionId): void` - Revoke direct permission
- `updateLastLogin(string $userId): void` - Update last login timestamp
- `incrementFailedLoginAttempts(string $userId): int` - Increment failed attempts
- `resetFailedLoginAttempts(string $userId): void` - Reset failed attempts
- `lockAccount(string $userId, string $reason): void` - Lock user account
- `unlockAccount(string $userId): void` - Unlock user account

#### UserRepositoryInterface (Combined - Deprecated)
**Location:** `src/Contracts/UserRepositoryInterface.php`

Extends `UserQueryInterface` and `UserPersistInterface` for backward compatibility.
**New code should use the specific Query or Persist interface.**

---

### Role Repository Interfaces

#### RoleQueryInterface (Read Model)
**Location:** `src/Contracts/RoleQueryInterface.php`

**Methods:**
- `findById(string $id): RoleInterface` - Find role by ULID
- `findByName(string $name, ?string $tenantId = null): RoleInterface` - Find by name
- `findByNameOrNull(string $name, ?string $tenantId = null): ?RoleInterface` - Find by name or null
- `nameExists(string $name, ?string $tenantId = null, ?string $excludeRoleId = null): bool`
- `getRolePermissions(string $roleId): array` - Get role's permissions
- `getAll(?string $tenantId = null): array` - Get all roles
- `getRoleHierarchy(?string $tenantId = null): array` - Get role hierarchy
- `hasUsers(string $roleId): bool` - Check if role has users
- `countUsers(string $roleId): int` - Count users in role

#### RolePersistInterface (Write Model)
**Location:** `src/Contracts/RolePersistInterface.php`

**Methods:**
- `create(array $data): RoleInterface` - Create new role
- `update(string $id, array $data): RoleInterface` - Update role
- `delete(string $id): bool` - Delete role
- `assignPermission(string $roleId, string $permissionId): void` - Assign permission
- `revokePermission(string $roleId, string $permissionId): void` - Revoke permission

---

### Permission Repository Interfaces

#### PermissionQueryInterface (Read Model)
**Location:** `src/Contracts/PermissionQueryInterface.php`

**Methods:**
- `findById(string $id): PermissionInterface` - Find permission by ULID
- `findByName(string $name): PermissionInterface` - Find by name
- `findByNameOrNull(string $name): ?PermissionInterface` - Find by name or null
- `nameExists(string $name, ?string $excludePermissionId = null): bool`
- `getAll(): array` - Get all permissions
- `findByResource(string $resource): array` - Find by resource
- `findMatching(string $permissionName): array` - Find matching with wildcards

#### PermissionPersistInterface (Write Model)
**Location:** `src/Contracts/PermissionPersistInterface.php`

**Methods:**
- `create(array $data): PermissionInterface` - Create new permission
- `update(string $id, array $data): PermissionInterface` - Update permission
- `delete(string $id): bool` - Delete permission

---

### MFA Enrollment Repository Interfaces

#### MfaEnrollmentQueryInterface (Read Model)
**Location:** `src/Contracts/MfaEnrollmentQueryInterface.php`

**Methods:**
- `findById(string $enrollmentId): ?MfaEnrollmentInterface`
- `findByUserId(string $userId): array`
- `findActiveByUserId(string $userId): array`
- `findByUserAndMethod(string $userId, MfaMethod $method): ?MfaEnrollmentInterface`
- `findPrimaryByUserId(string $userId): ?MfaEnrollmentInterface`
- `countActiveByUserId(string $userId): int`
- `hasVerifiedEnrollment(string $userId): bool`
- `findUnverifiedOlderThan(int $hoursOld): array`
- `findPendingByUserAndMethod(string $userId, string $method): ?array`
- `findActiveByUserAndMethod(string $userId, string $method): ?array`
- `findActiveBackupCodes(string $userId): array`

#### MfaEnrollmentPersistInterface (Write Model)
**Location:** `src/Contracts/MfaEnrollmentPersistInterface.php`

**Methods:**
- `save(MfaEnrollmentInterface $enrollment): MfaEnrollmentInterface`
- `delete(string $enrollmentId): bool`
- `setPrimary(string $enrollmentId): bool`
- `create(array $data): array`
- `activate(string $enrollmentId): bool`
- `revoke(string $enrollmentId): bool`
- `revokeByUserAndMethod(string $userId, string $method): int`
- `revokeAllByUserId(string $userId): int`
- `consumeBackupCode(string $enrollmentId, \DateTimeImmutable $consumedAt): bool`
- `updateLastUsed(string $enrollmentId, \DateTimeImmutable $lastUsedAt): bool`

---

### Additional CQRS Interfaces

Similar Query/Persist separation exists for:
- **TrustedDeviceQueryInterface / TrustedDevicePersistInterface**
- **WebAuthnCredentialQueryInterface / WebAuthnCredentialPersistInterface**
- **BackupCodeQueryInterface / BackupCodePersistInterface**

See source code for complete method signatures.

---

## Entity Interfaces (5 total)

### UserInterface
**Location:** `src/Contracts/UserInterface.php`

Entity contract for User.

**Methods:**
- `getId(): string` - Get user ULID
- `getEmail(): string` - Get user email
- `getPasswordHash(): string` - Get hashed password
- `getStatus(): UserStatus` - Get user status enum

[Additional entity interfaces: RoleInterface, PermissionInterface, MfaEnrollmentInterface, TrustedDeviceInterface]

---

## Services (10 total)

### UserManager
**Location:** `src/Services/UserManager.php`

**Purpose:** User lifecycle management (create, update, delete, status changes)

### AuthenticationService
**Location:** `src/Services/AuthenticationService.php`

**Purpose:** Login, logout, credential validation

### MfaEnrollmentService
**Location:** `src/Services/MfaEnrollmentService.php`

**Purpose:** MFA enrollment and management (17 methods)

### MfaVerificationService
**Location:** `src/Services/MfaVerificationService.php`

**Purpose:** MFA verification (11 methods)

[Additional services: RoleManager, PermissionManager, PermissionChecker, WebAuthnManager, TotpManager, TrustedDeviceManager]

---

## Value Objects (20 total)

### UserStatus (Enum)
**Location:** `src/ValueObjects/UserStatus.php`

**Cases:** ACTIVE, INACTIVE, SUSPENDED, LOCKED, PENDING_ACTIVATION

### MfaMethod (Enum)
**Location:** `src/ValueObjects/MfaMethod.php`

**Cases:** PASSKEY, TOTP, SMS, EMAIL, BACKUP_CODES

[Additional value objects documented in source code]

---

## Exceptions (19 total)

### UserNotFoundException
**Location:** `src/Exceptions/UserNotFoundException.php`

### MfaRequiredException
**Location:** `src/Exceptions/MfaRequiredException.php`

[Additional exceptions documented in source code]

---

**Note:** For complete API documentation, refer to source code docblocks. All public methods have comprehensive PHPDoc annotations.

**Last Updated:** 2024-11-24
