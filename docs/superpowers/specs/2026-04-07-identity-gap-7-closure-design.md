# Spec: Atomy-Q Identity Gap 7 Closure (Alpha)

**Topic:** Closing Gap 7 - Identity, Permissions, and Session support for Alpha Release.
**Date:** 2026-04-07
**Status:** Draft

## 1. Executive Summary
This spec outlines the closure of "Gap 7" in the Atomy-Q Alpha readiness analysis. The current identity implementation in `apps/atomy-q/API` is a minimal stub that bypasses the core Nexus `Identity` (L1) and `IdentityOperations` (L2) packages. This design replaces the stubbed logic with a production-grade, multi-tenant aware identity system supporting MFA (TOTP + Backup Codes), full RBAC, server-side sessions, and comprehensive audit logging.

## 2. Goals & Success Criteria
- **Goal:** Replace manual `AuthController` logic with the Nexus `UserAuthenticationCoordinatorInterface`.
- **Goal:** Implement full Nexus RBAC with hierarchical roles and wildcard permissions.
- **Goal:** Support server-side session tracking and instant revocation.
- **Goal:** Implement TOTP and Backup Code MFA for Alpha.
- **Success Criteria:** 
    - No direct use of `App\Models\User` for authentication in `AuthController`.
    - All permission checks go through the `PermissionCheckerInterface`.
    - Revoked sessions instantly block further access even with a valid JWT.
    - Full audit trail exists in the `AuditLogger` for all identity-related events.

## 3. Architecture & Data Model

### 3.1 Database Schema (New & Modified Tables)
We will align the application schema with the requirements of `Nexus\Identity`.

- **`users` (Existing, to be modified):**
    - Add `failed_login_attempts` (INT, default 0)
    - Add `lockout_reason` (STRING, nullable)
    - Add `lockout_expires_at` (TIMESTAMP, nullable)
    - Add `mfa_enabled` (BOOLEAN, default false)
- **`roles` (New):**
    - `id` (ULID, primary)
    - `tenant_id` (ULID, index)
    - `name` (STRING)
    - `description` (TEXT, nullable)
    - `parent_role_id` (ULID, nullable, self-referencing)
- **`permissions` (New):**
    - `id` (ULID, primary)
    - `name` (STRING, unique) - e.g., `rfqs.create`, `rfqs.*`
    - `description` (TEXT, nullable)
- **Pivot Tables (New):**
    - `user_roles`: `user_id`, `role_id`
    - `role_permissions`: `role_id`, `permission_id`
    - `user_permissions`: `user_id`, `permission_id` (for direct assignment)
- **`sessions` (New):**
    - `id` (STRING, primary) - Secure random session identifier
    - `user_id` (ULID, index)
    - `tenant_id` (ULID, index)
    - `payload` (JSON) - Metadata like IP, User-Agent
    - `last_activity` (TIMESTAMP)
- **MFA Tables (New):**
    - `mfa_enrollments`: `id`, `user_id`, `method` (enum: TOTP), `secret` (encrypted), `is_active`
    - `mfa_backup_codes`: `id`, `user_id`, `code_hash` (Argon2id), `used_at` (TIMESTAMP, nullable)

### 3.2 Model Implementation
- `App\Models\User`: Must implement `Nexus\Identity\Contracts\UserInterface`.
- `App\Models\Role`: Must implement `Nexus\Identity\Contracts\RoleInterface`.
- `App\Models\Permission`: Must implement `Nexus\Identity\Contracts\PermissionInterface`.

## 4. Components & Flow

### 4.1 Authentication Flow
Refactor `AuthController` to use `UserAuthenticationCoordinatorInterface`.

1. **Login (`POST /auth/login`)**:
    - App calls `$coordinator->authenticate($email, $password, $tenantId)`.
    - Coordinator handles:
        - Password verification (Argon2id).
        - Lockout checks & failure incrementing.
        - MFA status check.
    - If MFA is required: Return `401 MFA_REQUIRED` with a `challenge_id`.
    - If successful: Create server-side session, issue JWT (AccessToken + RefreshToken).
2. **MFA Verify (`POST /auth/mfa/verify`)**:
    - App calls `$coordinator->verify($userId, MfaMethod::TOTP, $code)`.
    - If successful: Upgrade session to "verified", issue final JWTs.

### 4.2 Middleware & Authorization
1. **`JwtAuthenticate` (Updated)**:
    - Decodes JWT.
    - **Session Check**: Calls `SessionValidatorInterface::isValid($sessionId)`. If not in DB/Cache, reject with `401`.
    - Sets `auth_user_id` and `auth_tenant_id` on the request.
2. **`NexusPermission` (New)**:
    - Applied as `nexus.can:permission.name`.
    - Uses `PermissionCheckerInterface` to resolve roles -> permissions + direct permissions.
    - Supports **Wildcards** (e.g., `rfqs.*` matches `rfqs.view`).
    - Enforces **Tenant Scoping**: Queries are automatically restricted to the current `auth_tenant_id`.

### 4.3 Audit Logging
The `IdentityOperationsAdapter` will be wired to the `AuditLogger` package.
- Events to log: `user.login.success`, `user.login.failure`, `user.locked`, `user.mfa.verified`, `user.logout`, `permission.granted`, `role.assigned`.

## 5. Integration & Adapters
We will use the existing `IdentityAdapterServiceProvider` from `adapters/Laravel/Identity` to:
- Bind Eloquent repositories to `UserRepositoryInterface`, `RoleRepositoryInterface`, etc.
- Bind `PermissionCheckerInterface` to its adapter.
- Bind `UserAuthenticationCoordinatorInterface` to the L2 implementation.

## 6. Testing Strategy
- **Unit Tests**:
    - Permission resolution logic (wildcards, hierarchy).
    - Session expiration and revocation.
- **Integration Tests**:
    - Full login flow with and without MFA.
    - Middleware rejection on revoked sessions.
    - Multi-tenant isolation (User A in Tenant 1 cannot access Tenant 2 even with a valid ID).
- **Manual Verification**:
    - Verify audit logs are populated in the database after auth events.

## 7. Future Enhancements (Post-Alpha)
- SSO (SAML/OIDC) full integration (beyond the current basic OIDC stub).
- Passkey/WebAuthn support.
- IP Whitelisting & Geo-fencing.
- Adaptive MFA (Risk-based).
