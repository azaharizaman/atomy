# Atomy-Q Identity Gap 7 Closure (Alpha) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the stubbed authentication and authorization logic in Atomy-Q with production-grade Nexus Identity implementations, supporting multi-tenancy, RBAC, MFA, and server-side sessions.

**Architecture:** Layered Architecture. L1 (Identity Package) for core logic, L2 (IdentityOperations Orchestrator) for coordination, and L3 (Laravel Adapters) for database and framework integration.

**Tech Stack:** PHP 8.3, Laravel 12, Nexus Core, Argon2id, JWT, TOTP (OTPHP).

**Execution status:** Completed (2026-04-08). Tasks 1, 2, 5, 6, 7, 8, 9, and 11 were implemented in the base track, and Task 10 was completed by the 2026-04-08 MFA/audit extension (`mfaVerify` bridge + persisted audit logging). This plan remains the base closure record for the original Gap 7 scope.

---

### Task 1: Database Schema Infrastructure

**Files:**
- Create: `apps/atomy-q/API/database/migrations/2026_04_07_000002_update_users_table_for_gap_7.php`
- Create: `apps/atomy-q/API/database/migrations/2026_04_07_000003_create_identity_rbac_tables.php`
- Create: `apps/atomy-q/API/database/migrations/2026_04_07_000004_create_identity_sessions_table.php`
- Create: `apps/atomy-q/API/database/migrations/2026_04_07_000005_create_identity_mfa_tables.php`

- [ ] **Step 1: Create migration for users table updates**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('failed_login_attempts')->default(0)->after('status');
            $table->string('lockout_reason')->nullable()->after('failed_login_attempts');
            $table->timestamp('lockout_expires_at')->nullable()->after('lockout_reason');
            $table->boolean('mfa_enabled')->default(false)->after('lockout_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['failed_login_attempts', 'lockout_reason', 'lockout_expires_at', 'mfa_enabled']);
        });
    }
};
```

- [ ] **Step 2: Create migration for RBAC tables**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->ulid('parent_role_id')->nullable();
            $table->timestamps();

            $table->foreign('parent_role_id')->references('id')->on('roles')->onDelete('set null');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->ulid('user_id');
            $table->ulid('role_id');
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->ulid('role_id');
            $table->ulid('permission_id');
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->ulid('user_id');
            $table->ulid('permission_id');
            $table->primary(['user_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
```

- [ ] **Step 3: Create migration for sessions table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->ulid('user_id')->index();
            $table->ulid('tenant_id')->index();
            $table->json('payload');
            $table->timestamp('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
```

- [ ] **Step 4: Create migration for MFA tables**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfa_enrollments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->string('method'); // 'totp'
            $table->text('secret'); // Encrypted
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mfa_backup_codes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->string('code_hash');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_backup_codes');
        Schema::dropIfExists('mfa_enrollments');
    }
};
```

- [ ] **Step 5: Run migrations**

Run: `php artisan migrate`

- [ ] **Step 6: Commit**

```bash
git add database/migrations/*.php
git commit -m "feat(identity): add database schema for Gap 7 identity closure"
```

### Task 2: Implement Models and Relations

**Files:**
- Modify: `apps/atomy-q/API/app/Models/User.php`
- Create: `apps/atomy-q/API/app/Models/Role.php`
- Create: `apps/atomy-q/API/app/Models/Permission.php`

- [ ] **Step 1: Update User model**

Modify `apps/atomy-q/API/app/Models/User.php`:
- Implement `Nexus\Identity\Contracts\UserInterface`.
- Add `roles` and `permissions` BelongsToMany relations.
- Add `failed_login_attempts`, `lockout_reason`, `lockout_expires_at`, `mfa_enabled` to `$fillable`.
- Update `$casts`.

- [ ] **Step 2: Create Role model**

Create `apps/atomy-q/API/app/Models/Role.php`:
- Implement `Nexus\Identity\Contracts\RoleInterface`.
- Add `tenant` BelongsTo relation.
- Add `parent` BelongsTo relation.
- Add `permissions` BelongsToMany relation.

- [ ] **Step 3: Create Permission model**

Create `apps/atomy-q/API/app/Models/Permission.php`:
- Implement `Nexus\Identity\Contracts\PermissionInterface`.

- [ ] **Step 4: Commit**

```bash
git add app/Models/*.php
git commit -m "feat(identity): implement User, Role, and Permission models for Gap 7"
```

### Task 5: Implement Layer 3 Repositories

**Files:**
- Create: `adapters/Laravel/Identity/src/Adapters/EloquentUserRepository.php`
- Create: `adapters/Laravel/Identity/src/Adapters/EloquentRoleRepository.php`
- Create: `adapters/Laravel/Identity/src/Adapters/EloquentPermissionRepository.php`
- Create: `adapters/Laravel/Identity/src/Adapters/EloquentMfaEnrollmentRepository.php`
- Create: `adapters/Laravel/Identity/src/Adapters/EloquentBackupCodeRepository.php`

- [ ] **Step 1: Implement EloquentUserRepository**

Implement `findById`, `findByEmail`, `updateLastLogin`, `incrementFailedLoginAttempts`, etc.

- [ ] **Step 2: Implement EloquentRoleRepository**

Implement `findById`, `findByName`, `getTenantRoles`, etc.

- [ ] **Step 3: Implement EloquentPermissionRepository**

Implement `findById`, `findByName`, `all`, etc.

- [ ] **Step 4: Implement MFA Repositories**

Implement `EloquentMfaEnrollmentRepository` and `EloquentBackupCodeRepository`.

- [ ] **Step 5: Commit**

```bash
git add adapters/Laravel/Identity/src/Adapters/*.php
git commit -m "feat(identity): implement Eloquent repositories for Gap 7"
```

### Task 6: Implement Session Management (L3)

**Files:**
- Create: `adapters/Laravel/Identity/src/Adapters/DatabaseSessionManager.php`

- [ ] **Step 1: Implement DatabaseSessionManager**

Implement `SessionManagerInterface`:
- `createSession(string $userId, array $metadata = []): SessionToken`
- `validateSession(string $token): UserInterface`
- `revokeSession(string $token): void`
- `revokeSessionForTenant(string $token, string $tenantId): void`
- `revokeAllSessionsForTenant(string $userId, string $tenantId): void`
- `isValid(string $token): bool`

- [ ] **Step 2: Commit**

```bash
git add adapters/Laravel/Identity/src/Adapters/DatabaseSessionManager.php
git commit -m "feat(identity): implement database session manager"
```

### Task 7: RBAC Permission Checker with Wildcards

**Files:**
- Modify: `adapters/Laravel/Identity/src/Adapters/PermissionCheckerAdapter.php`

- [ ] **Step 1: Implement wildcard and hierarchical permission checking**

Update `hasPermission` to load user permissions (including roles) and check for matches using wildcards (e.g. `rfqs.*`).

- [ ] **Step 2: Commit**

```bash
git add adapters/Laravel/Identity/src/Adapters/PermissionCheckerAdapter.php
git commit -m "feat(identity): implement wildcard and hierarchical permission checking"
```

### Task 8: Wiring the Identity Adapter

**Files:**
- Modify: `adapters/Laravel/Identity/src/Providers/IdentityAdapterServiceProvider.php`
- Modify: `adapters/Laravel/Identity/src/Mappers/LaravelUserMapper.php`

- [ ] **Step 1: Update LaravelUserMapper**

Implement missing `UserInterface` methods: `hasMfaEnabled`, `getMetadata`, `isLocked`, etc.

- [ ] **Step 2: Update IdentityAdapterServiceProvider**

Bind:
- `UserRepositoryInterface` -> `EloquentUserRepository`
- `RoleRepositoryInterface` -> `EloquentRoleRepository`
- `PermissionRepositoryInterface` -> `EloquentPermissionRepository`
- `MfaEnrollmentRepositoryInterface` -> `EloquentMfaEnrollmentRepository`
- `BackupCodeRepositoryInterface` -> `EloquentBackupCodeRepository`
- `SessionManagerInterface` -> `DatabaseSessionManager`
- `PasswordHasherInterface` -> `LaravelPasswordHasher` (New Adapter)

- [ ] **Step 3: Create LaravelPasswordHasher**

Create `adapters/Laravel/Identity/src/Adapters/LaravelPasswordHasher.php` implementing `Nexus\Identity\Contracts\PasswordHasherInterface` using `Illuminate\Support\Facades\Hash`.

- [ ] **Step 4: Commit**

```bash
git add adapters/Laravel/Identity/src/
git commit -m "feat(identity): wire all gap 7 components in ServiceProvider"
```

### Task 9: Middleware Updates

**Files:**
- Create: `apps/atomy-q/API/app/Http/Middleware/NexusPermission.php`
- Modify: `apps/atomy-q/API/app/Http/Middleware/JwtAuthenticate.php`
- Modify: `apps/atomy-q/API/app/Http/Kernel.php`

- [ ] **Step 1: Update JwtAuthenticate**

Add check for session validity using `SessionValidatorInterface`.
If token has `sid` claim, verify it exists in `sessions` table.

- [ ] **Step 2: Create NexusPermission middleware**

Implement `handle(Request $request, Closure $next, string $permission)`:
- Use `PermissionCheckerInterface` to check if `auth_user_id` has `$permission` in `auth_tenant_id`.

- [ ] **Step 3: Register middleware in Kernel**

Add `nexus.can` alias for `NexusPermission`.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Middleware/ app/Http/Kernel.php
git commit -m "feat(identity): implement authorization middleware and session check"
```

### Task 10: AuthController Refactoring

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php`

- [ ] **Step 1: Update login method**

Replace manual DB query and `Hash::check` with `$coordinator->authenticate()`.
Handle `MfaRequiredException` to return 401 with challenge.

- [ ] **Step 2: Implement logout method**

Use `$coordinator->logout()`.

- [ ] **Step 3: Implement mfaVerify method**

Use `$coordinator->verify()`.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Api/V1/AuthController.php
git commit -m "refactor(auth): use UserAuthenticationCoordinator for all auth flows"
```

### Task 11: Integration Testing

**Files:**
- Create: `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php`

- [ ] **Step 1: Write integration tests**

- Test login success/failure.
- Test account lockout after 5 failed attempts.
- Test session revocation.
- Test RBAC with wildcards.
- Test multi-tenant isolation.

- [ ] **Step 2: Run tests**

Run: `php artisan test tests/Feature/IdentityGap7Test.php`

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/IdentityGap7Test.php
git commit -m "test(identity): add integration tests for Gap 7 identity closure"
```
