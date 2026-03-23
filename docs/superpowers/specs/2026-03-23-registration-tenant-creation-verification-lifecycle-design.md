# Registration, Tenant Creation & User Verification Lifecycle

**Date:** 2026-03-23
**Status:** Draft — Pending Package Integration Review

---

## 1. Overview

This spec covers the end-to-end self-service registration flow for Atomy-Q, including: user self-registration with simultaneous tenant creation, email verification, invite-based team joining, admin delegation, and user unregistration with data retention policies.

**Design principles:**
- **One email, one tenant** — a user account may only belong to one tenant at any time
- **Invite-only team joining** — users cannot request to join a tenant; they must be invited by an admin
- **No enumeration** — all registration and invite endpoints return non-revealing responses
- **Retention holds** — deletion is never immediate; all queued deletions have a 7-day hold before purge

---

## 2. Actor & Role Model

### 2.1 Roles

| Role | Capabilities |
|------|-------------|
| `admin` | Send invites, delegate admin, unregister (subject to constraints in §6) |
| `member` | Accept invite, request unregister |

- A tenant must always have **exactly one admin**
- The sole admin cannot demote themselves unless a second admin exists
- The sole admin cannot unregister unless a second user exists, or their unregistration triggers tenant deletion

### 2.2 User Statuses

| Status | Meaning |
|--------|---------|
| `pending_activation` | Registered but email not yet verified |
| `active` | Verified and can authenticate |
| `queued_deletion` | Unregistration requested; soft-deleted, purge pending |

### 2.3 Tenant Statuses

| Status | Meaning |
|--------|---------|
| `pending` | Registering user has not completed email verification |
| `active` | At least one active user; normal operations |
| `queued_deletion` | Orphaned (no active users); retention hold for 7 days before hard-delete |

---

## 3. Registration Flow

**Endpoint:** `POST /auth/register`

### 3.1 Input

| Field | Validation |
|-------|-----------|
| `email` | Required, valid email format, must not already exist in the system |
| `password` | Required, min 8 chars, at least 1 uppercase, 1 lowercase, 1 number |
| `tenant_name` | Required, min 1 char, case-sensitive unique across all tenants |

### 3.2 Processing Steps

1. Validate uniqueness of `tenant_name` — return 422 if already taken
2. Validate `email` uniqueness — return same success response as below regardless (anti-enumeration)
3. Create tenant record: `status = pending`, `name = tenant_name`, `retention_hold_until = null`
4. Create user record: `status = pending_activation`, `role = admin`, `tenant_id → new tenant`, `email_verified_at = null`
5. Generate signed verification token: payload = `(user_id, tenant_id, purpose = "email_verification", issued_at)`
6. Queue email job: send verification email to user with signed token link

### 3.3 Response

**Always return 201 with:**
```json
{
  "message": "Registration successful. Please check your email to verify your account."
}
```

Do not reveal whether the email already existed.

### 3.4 Verification Token

- **Expiry:** 24 hours
- **Payload:** `user_id`, `tenant_id`, `purpose = "email_verification"`, `issued_at`
- **Storage:** Signed JWT (HMAC-SHA256 with `APP_KEY`); no DB storage required

**Endpoint:** `GET /auth/verify?token=...`

On verification:
1. Decode and validate token (signature, expiry, purpose)
2. If valid: `status → active` for user; `status → active` for tenant
3. If token invalid or expired: return 422 with non-revealing message
4. Clear any stale pending invites for this user (they registered their own tenant)

---

## 4. Invite Flow

Only `admin` role may send invites. One active invite per tenant at a time — sending a new invite invalidates all previous pending invites for that tenant.

### 4.1 Send Invite

**Endpoint:** `POST /invitations`

**Input:**

| Field | Validation |
|-------|-----------|
| `email` | Required, valid email format |
| `tenant_id` | Required, must belong to the authenticated admin's tenant |

**Processing:**
1. Validate inviter is `admin` of `tenant_id`
2. Validate invitee email is not already a member of this tenant
3. Invalidate all existing pending invites for this tenant (`status → cancelled`)
4. Generate signed invite token: payload = `(invitee_email, tenant_id, invite_id, purpose = "invitation", issued_at)`
5. Create invite record: `status = pending`, `expires_at = now + 24h`
6. Queue email job: send invite email with signed token link

**Response:** `201` with `"Invitation sent."`

### 4.2 Invite Token

- **Expiry:** 24 hours
- **Payload:** `invitee_email`, `tenant_id`, `invite_id`, `purpose = "invitation"`, `issued_at`
- **Storage:** Signed JWT; invite record in DB for status tracking and invalidation

### 4.3 Accept Invite

**Endpoint:** `GET /invitations/accept?token=...`

#### Path A — Email not yet registered in the system

1. Validate token (signature, expiry, purpose = "invitation", status = pending)
2. Create user: `status = active`, `role = member`, `tenant_id = invited tenant`
3. Delete invite record
4. Ensure tenant `status = active`

#### Path B — Email already registered and attached to another tenant (denounce path)

1. Validate token
2. Return UI prompt for **"Denounce & Join"** confirmation (see 4.4)
3. On user confirmation:
   - If old tenant has other active users:
     - User `tenant_id → new tenant`, `role = member`
     - User data stays in old tenant (orphaned under old tenant; not transferred)
   - If old tenant has no other active users (orphan):
     - Old tenant `status → queued_deletion`, `retention_hold_until = now + 7 days`
     - User `tenant_id → new tenant`, `role = member`
4. Delete invite record
5. Send confirmation email: "You have left [OLD TENANT NAME] and joined [NEW TENANT NAME]"

### 4.4 Denounce & Join Confirmation

The confirmation screen must display:
- Old tenant name
- New tenant name
- Warning: *"Accepting this invite will remove you from [OLD TENANT NAME]. If you are the only member, [OLD TENANT NAME] and all its data will be permanently deleted after 7 days. This action cannot be undone."*

User must explicitly acknowledge the warning before proceeding.

### 4.5 Orphaned Tenant Cleanup

A daily scheduled job:
1. Find all tenants where `status = queued_deletion` AND `retention_hold_until < now`
2. For each tenant: cancel all pending invitations (`status → cancelled`) to avoid FK constraint violations on user hard-delete
3. Hard-delete tenant and ALL associated data (users, RFQs, projects, tasks, invitations, etc.)
4. No data transfer — orphaned data is permanently lost

---

## 5. Admin Delegation

**Endpoint:** `POST /tenant/delegate-admin`

**Input:**

| Field | Validation |
|-------|-----------|
| `user_id` | Required, must be a `member` of the authenticated admin's tenant |

**Processing:**
1. Validate requester is `admin`
2. Validate target user is a `member` of the same tenant
3. Show confirmation: *"You will be logged out immediately. [Name] must log in to gain admin access."*
4. On confirm:
   - Inviting admin: `role → member`, all sessions of the delegating admin revoked immediately
   - Target user: `role → admin`
   - Queue email job: notify both parties of the delegation
5. Inviting admin's session is invalidated; they must log in fresh to regain access with their (new) member role

**Constraint:** Last admin cannot delegate if they are the sole user in the tenant (tenant would have no admin).

---

## 6. User Unregistration

**Endpoint:** `POST /auth/unregister`

**Processing:**

#### By `member`:
1. Show warning: *"Your account and data will be queued for permanent deletion after 7 days. This cannot be undone."*
2. On confirm: User `status → queued_deletion`, `deleted_at = now + 7 days`
3. Daily scheduled job (see 6.2) purges user record and cascades delete user-owned data
4. If user was the **last active user** in the tenant → tenant `status → queued_deletion`, `retention_hold_until = now + 7 days`

#### By `admin`:
- If other active members exist: admin must first delegate admin role to another member, then unregister
- If **last user in tenant**: triggers both user deletion and tenant queued deletion (same warning as member, with additional tenant deletion notice)
- Admin cannot unregister if they are the sole admin and no other members exist (return 422)

### 6.2 User Data Purge Job

A daily scheduled job:
1. Find all users where `status = queued_deletion` AND `deleted_at < now`
2. Cancel any pending invitations sent by the departing user (`status → cancelled`) to avoid FK constraint violations
3. Hard-delete user record and cascade delete user-owned data (RFQs, projects, tasks, etc. created solely by this user)
4. After purge, check if the user's former tenant has any remaining active users
5. If no remaining active users: tenant `status → queued_deletion`, `retention_hold_until = now + 7 days`

---

## 7. Email Notifications

| Event | Recipient | Content |
|-------|-----------|---------|
| Registration submitted | User | "Verify your email" with verification link |
| Invite sent | Invitee | "You've been invited to [Tenant Name]" with accept link |
| Denounce & Join confirmed | User | "You have left [Old Tenant] and joined [New Tenant]" |
| Admin delegated | Both old & new admin | "Admin role has been transferred to [Name]" |
| Tenant queued for deletion | Last departing user | "Your organization [Tenant Name] is scheduled for deletion in 7 days" |

All emails are transactional. Invites are not re-sent if expired; inviter must send a new invite.

---

## 8. Data Model

### 8.1 `tenants` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | ULID | Primary key |
| `name` | VARCHAR(255) | Case-sensitive unique |
| `status` | ENUM('pending','active','queued_deletion') | Default 'pending' |
| `retention_hold_until` | TIMESTAMP NULL | Set when queued for deletion |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

### 8.2 `users` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | ULID | Primary key |
| `tenant_id` | ULID NULL | FK → tenants.id; null only after full deletion |
| `email` | VARCHAR(255) | Unique |
| `password_hash` | VARCHAR(255) | bcrypt/argon2 |
| `role` | ENUM('admin','member') | Default 'member' |
| `status` | ENUM('pending_activation','active','queued_deletion') | Default 'pending_activation' |
| `email_verified_at` | TIMESTAMP NULL | Set on verification |
| `deleted_at` | TIMESTAMP NULL | Set when queued for deletion |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

### 8.3 `invitations` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | ULID | Primary key |
| `tenant_id` | ULID | FK → tenants.id |
| `inviter_id` | ULID | FK → users.id |
| `invitee_email` | VARCHAR(255) | Email to be invited |
| `token` | VARCHAR(512) | Signed JWT; stored for reference |
| `status` | ENUM('pending','accepted','expired','cancelled') | Default 'pending' |
| `expires_at` | TIMESTAMP | Now + 24h |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Indexes:**
- `tenant_id + status` (for listing/invalidation)
- `invitee_email + status` (for accept lookup)

---

## 9. Anti-Patterns & Edge Cases

### 9.1 Email Already Registered at Registration
Return success response without revealing that the email exists.

### 9.2 Invite for Already-Member Email
Return 422 with message: "This user is already a member of this organization."

### 9.3 Invite for Registered Email (Different Tenant)
Accept invite proceeds to denounce path (see 4.3, Path B).

### 9.4 Expired Invite Token
Return 422: "This invitation link has expired. Please ask your admin to send a new invite."

### 9.5 Tenant Name Collision
Return 422 on registration: "An organization with this name already exists."

### 9.6 Solo Admin Unregistration
Blocked at API level with 422: "You cannot unregister while you are the sole admin. Please delegate admin to another member first."

### 9.7 Admin Delegation to Non-Existent User
Not possible — invite-only model means only members can be delegated admin.

### 9.8 Concurrent Invite Acceptance
First accepted invite wins. Subsequent accepts against the same invite record (or its cancelled status) return 422.

### 9.9 Denounce to Join Own Tenant
Not possible — invite for a tenant the user already belongs to is blocked at send time.

---

## 10. API Endpoints Summary

| Method | Path | Auth | Role | Description |
|--------|------|------|------|-------------|
| POST | `/auth/register` | None | — | Self-register with tenant creation |
| GET | `/auth/verify` | None | — | Verify email, activate user + tenant |
| POST | `/invitations` | Bearer | admin | Send invite (invalidates previous) |
| GET | `/invitations/accept` | None | — | Accept invite (denounce path if registered elsewhere) |
| POST | `/auth/unregister` | Bearer | member/admin | Request account deletion |
| POST | `/tenant/delegate-admin` | Bearer | admin | Delegate admin role to member |

---

## 11. Security Considerations

- All signed tokens use HMAC-SHA256 with `APP_KEY`
- Token payloads include `purpose` to prevent reuse across flows
- Session revocation on logout and admin delegation is immediate
- Tenant name uniqueness is enforced at the DB level with a unique constraint
- Retention hold timestamps prevent premature purge even if cron misfires
- No tenant existence or user existence information is leaked via timing or error messages

---

## 12. Nexus Package Integration

This section maps each implementation step to existing Nexus Layer 1 packages and identifies what must be built new.

### 12.1 Existing Packages — Full Reuse

| Flow Step | Package | Interface / Method to Use | Notes |
|-----------|---------|--------------------------|-------|
| Create user record | `Nexus\Identity` | `UserManager::createUser()` | Validates email uniqueness, password complexity, hashes password, sets `PENDING_ACTIVATION` status |
| Activate user on verification | `Nexus\Identity` | `UserManager::activateUser()` | Sets `status → ACTIVE`, `email_verified_at = now` |
| Password hashing | `Nexus\Identity` | `PasswordHasherInterface` | bcrypt/argon2 via adapter |
| Password validation | `Nexus\Identity` | `PasswordValidatorInterface` | Enforces min length + complexity rules |
| Create tenant record | `Nexus\Tenant` | `TenantLifecycleService::createTenant()` | Sets status `Pending`; dispatch `TenantCreatedEvent` |
| Activate tenant on verification | `Nexus\Tenant` | `TenantLifecycleService::activateTenant()` | Transitions `Pending → Active`; dispatch `TenantActivatedEvent` |
| Archive orphan tenant (queue for deletion) | `Nexus\Tenant` | `TenantLifecycleService::archiveTenant()` | Transitions to `Archived`; dispatch `TenantArchivedEvent`; `retention_hold_until` set via metadata or `updateTenant()` |
| Tenant name uniqueness | `Nexus\Tenant` | `TenantValidationInterface` | Adapt `codeExists()` / `domainExists()` logic to validate `name` uniqueness |
| Tenant context (auth middleware) | `Nexus\Tenant` | `TenantContextManager` / `TenantContextInterface` | Request-scoped tenant resolution |
| Audit logging (all lifecycle events) | `Nexus\AuditLogger` | `AuditLogManager::log()` / `logSystemActivity()` | Generic enough for registration, verification, invite, delegation, unregistration |

### 12.2 Existing Packages — Require Adapter Layer

| Flow Step | Package | Interface / Method | Adapter Work Required |
|-----------|---------|---------------------|----------------------|
| Signed token generation (verification + invite) | `Nexus\Crypto` | `CryptoManager::hmac()` / `SodiumSigner::hmac()` | Build token envelope: encode payload + expiry into signed string; `purpose` in payload prevents cross-flow reuse |
| Async email notifications | `Nexus\Notifier` | `NotificationManagerInterface` + `AbstractNotification` + `ChannelType::Email` + `NotifiableInterface` | Implement: `EmailChannel` (Laravel Mail / PSR-18), `NotificationQueueInterface` (Laravel Queue), `NotificationHistoryRepositoryInterface` (DB table), `NotificationRendererInterface` (simple `{{var}}` substitution) |
| Endpoint idempotency (all mutating endpoints) | `Nexus\Idempotency` | `IdempotencyServiceInterface::begin()/complete()/fail()` | Bind `IdempotencyStoreInterface` to Laravel DB adapter; per-endpoint `OperationRef` (e.g. `"register"`, `"invite"`); `ClientKey` from `X-Idempotency-Key` request header |

### 12.3 New Code Required

The following are **not covered by any existing Nexus package** and must be built as part of this feature:

| Component | Layer | Description |
|-----------|-------|-------------|
| **VerificationToken** service + VO | Layer 1 (`Nexus\Identity` or new `Nexus\AuthTokens`) | Generate signed tokens (use `Nexus\Crypto` for signing), validate expiry + purpose, invalidate per user or per token |
| **Invitation** entity, repository, service | Layer 1 (`Nexus\Identity` or new `Nexus\Invitation`) | Create invite, invalidate previous per tenant, accept invite (Path A + Path B denounce), enforce one-active-invite-per-tenant rule, 24h expiry |
| **Denounce & Join** logic | Orchestrator (`IdentityOperations`) | Transfer user between tenants, detect orphan old tenant, queue for deletion, send confirmation email |
| **Admin delegation** | Layer 1 + Orchestrator | Transfer `admin` role to member, revoke all sessions of delegating admin, email both parties |
| **User unregistration** | Layer 1 (`Nexus\Identity`) + Orchestrator | Soft-delete: `status → queued_deletion`, `deleted_at = now + 7 days`; enforce solo-admin gate; trigger orphan tenant detection |
| **Retention hold extension** on tenant | Layer 1 (`Nexus\Tenant`) | Add `retention_hold_until` to `TenantInterface` metadata or as a first-class field; expose via `TenantLifecycleService` |
| **Purge scheduled jobs** | Adapter (Laravel) | Daily command: purge users past `deleted_at`; daily command: purge tenants past `retention_hold_until`; cascade-delete all associated data; cancel related pending invites first |

### 12.4 Adapter Layer Completions Required

The current `AuthController` uses Eloquent directly for login. Before Layer 2 orchestrators can be properly wired, these Laravel adapter bindings must exist:

| Interface | Package | Status |
|----------|---------|--------|
| `Nexus\Identity\Contracts\UserPersistInterface` | `Nexus\Identity` | Needs Laravel adapter binding |
| `Nexus\Identity\Contracts\UserQueryInterface` | `Nexus\Identity` | Needs Laravel adapter binding |
| `Nexus\Tenant\Contracts\TenantPersistenceInterface` | `Nexus\Tenant` | Partial — check `adapters/Laravel/Tenant/` |
| `Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface` | `Nexus\AuditLogger` | Needs Laravel adapter binding |
| `Nexus\Notifier\Contracts\NotificationChannelInterface` | `Nexus\Notifier` | Email channel not yet implemented |

These adapter completions are a prerequisite for the implementation plan and should be listed as the first phase of work.
