# Tenant And Company Entities

This document defines the core business entities used by the Tenant And Company domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For tenant state semantics, see [lifecycle.md](./lifecycle.md#state-model).

## Entity 001 - Tenant / Company

The tenant is the customer workspace boundary. The company name is the human-facing label for that tenant.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique tenant identity used across the app and packages. | ULID; must be unique. |
| `code` | Short business handle for the tenant. | Required; unique; normalized to uppercase in onboarding; letters and numbers with `-` or `_` are allowed. |
| `name` | Display name shown to users and admins. | Required; unique; trimmed; must not be empty. |
| `email` | Primary contact email for the tenant/company. | Required; valid email; normalized to lower-case. |
| `status` | Tenant lifecycle state. | `pending`, `active`, `suspended`, `trial`, `archived`, `queued_deletion`; see [lifecycle.md](./lifecycle.md#state-model). |
| `domain` | Optional customer-facing custom domain. | Optional; unique when present; may be derived from the tenant code. |
| `subdomain` | Optional subdomain shortcut. | Optional; unique when present; derived from the domain/code when possible. |
| `parentId` | Optional parent tenant reference for hierarchy. | Optional; parent removal must not delete the child tenant. |
| `timezone` | Default timezone for the workspace. | Required after normalization; defaults to `UTC`. |
| `locale` | Default locale for the workspace. | Required after normalization; defaults to `en`. |
| `currency` | Default currency for workspace display and onboarding. | Required after normalization; defaults to `USD`. |
| `dateFormat` | Default date presentation format. | Defaults to `Y-m-d`. |
| `timeFormat` | Default time presentation format. | Defaults to `H:i`. |
| `onboardingProgress` | Completion signal for workspace setup. | Integer from `0` to `100`; onboarding completion sets this to `100`. |
| `trialEndsAt` | Trial boundary. | Optional date/time; used to determine whether trial access is expired. |
| `retentionHoldUntil` | Deletion hold boundary. | Optional date/time; when present, the tenant is queued for later deletion handling. |
| `storageQuota` | Workspace storage allowance. | Optional; `null` means unlimited. |
| `storageUsed` | Current storage usage signal. | Non-negative whole number. |
| `maxUsers` | Maximum users allowed in the tenant. | Optional; `null` means unlimited. |
| `rateLimit` | API throughput ceiling. | Optional; `null` means no explicit limit. |
| `isReadOnly` | Whether writes should be blocked for the tenant. | Boolean. |
| `metadata` | Free-form tenant context. | Business tags and provisioning metadata only; do not hide core lifecycle state here. |

### Relationships

- A tenant owns many users.
- A tenant can optionally have a parent tenant and child tenants.
- A tenant is the business boundary for all workspace data, access, and configuration.

### Business Rules

- A tenant code must be unique.
- A tenant name must be unique.
- A tenant must be created before any tenant-scoped user can exist.
- A new workspace should have exactly one owner admin at onboarding.
- A tenant should never be left without an admin owner.
- Tenant archival or deletion must end normal workspace access for that tenant.
- Parent-child links are optional and should not be used as a deletion cascade for child tenants.

### Related Docs

- Onboarding flow: [Workflow 001 - Self-Serve Tenant Creation](./workflows.md#workflow-001---self-serve-tenant-creation)
- State model: [State Model](./lifecycle.md#state-model)

## Entity 002 - Owner User

The owner user is the first human account created for a new tenant. It anchors access to the workspace and becomes the initial admin.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique user identity. | ULID or equivalent unique identifier. |
| `tenantId` | The tenant this user belongs to. | Required; a user belongs to exactly one tenant in this domain. |
| `email` | Login identifier and contact address. | Required; valid email; normalized to lower-case; must be unique in the identity system. |
| `name` | Display name for the account holder. | Required for onboarding owner; used in the UI and admin surfaces. |
| `role` | Authority level in the tenant. | The onboarding owner is always `admin`; other tenant users may be `user` in the broader identity surface. |
| `status` | Current account usability. | The onboarding owner is created `active`; broader identity states include `pending_activation`, `active`, `suspended`, and `locked`. |
| `timezone` | Optional personal display preference. | Defaults from tenant workspace settings when not provided. |
| `locale` | Optional personal display preference. | Defaults from tenant workspace settings when not provided. |
| `emailVerifiedAt` | Verification signal. | Set on bootstrap owner creation. |
| `passwordHash` | Stored secret for login. | Never exposed in UI; only the hashed value is stored and used by the auth domain. |
| `mfaEnabled` | MFA preference for the account. | Managed by the broader identity/auth surface, not by tenant onboarding itself. |

### Relationships

- An owner user belongs to one tenant.
- An owner user is the first admin for that tenant.
- Tenant-scoped access, settings, and onboarding bootstrap depend on this user existing.

### Business Rules

- A tenant onboarding flow must create an owner user.
- The owner user must be created as an admin.
- The owner user must be active and verified as part of bootstrap.
- A tenant should not be considered operational until at least one admin user exists.
- Tenant archival or deletion must end the owner user’s practical access to the workspace.
- Removing the owner should be treated as an administrative action that requires another admin to remain or be appointed.

### Related Docs

- Onboarding flow: [Workflow 001 - Self-Serve Tenant Creation](./workflows.md#workflow-001---self-serve-tenant-creation)
- State model: [State Model](./lifecycle.md#state-model)

## Entity 003 - Workspace Defaults

Workspace defaults are the tenant-wide presentation settings that shape how the new workspace behaves for its users.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `timezone` | Default timezone for the workspace. | Should be a valid timezone identifier; falls back to `UTC`. |
| `locale` | Default language/region setting. | Should be a valid locale string; falls back to `en`. |
| `currency` | Default monetary display code. | Three-letter currency code; falls back to `USD`. |
| `dateFormat` | Default date formatting. | Standard display pattern; currently defaults to `Y-m-d`. |
| `timeFormat` | Default time formatting. | Standard display pattern; currently defaults to `H:i`. |

### Relationships

- Workspace defaults belong to one tenant.
- The owner user receives these defaults at bootstrap unless a more specific user preference exists later.
- Dashboard and admin views should use these defaults when rendering tenant-wide values.

### Business Rules

- Workspace defaults are initialized during onboarding.
- If a value is omitted, the domain should use its standard fallback.
- Workspace defaults should influence presentation, not historical records.
- Changes to these values affect future display and setup behavior, not past audit history.

### Related Docs

- Onboarding flow: [Workflow 001 - Self-Serve Tenant Creation](./workflows.md#workflow-001---self-serve-tenant-creation)
- State model: [State Model](./lifecycle.md#state-model)

