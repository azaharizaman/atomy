# Users And Roles Entities

This document defines the core business entities used by the Users and Roles domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For user-account state semantics, see [lifecycle.md](./lifecycle.md#state-model).

## Entity 001 - User Account

The user account is the tenant-scoped identity record returned by the controller.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique user identifier. | Required; tenant-scoped. |
| `name` | Display name shown in the UI. | Required. |
| `email` | Login and contact email. | Required; normalized to lower-case on invite. |
| `status` | Account lifecycle state. | `pending_activation`, `active`, `suspended` are currently surfaced. |
| `role` | Primary role label exposed to the client. | Derived from the user’s role list. |
| `tenant_id` | Tenant boundary. | Required. |
| `created_at` | Creation timestamp. | Always returned in ISO-8601 form. |
| `last_login_at` | Last successful login timestamp. | Optional; derived from metadata when present. |

### Relationships

- A user belongs to exactly one tenant in the current auth model.
- A user can have multiple roles, but the controller exposes one primary role label.

### Business Rules

- User lookup must always be tenant-scoped.
- Roles are resolved through the identity query layer rather than invented in the controller.

## Entity 002 - Role

Roles describe the privilege labels available to a tenant user.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique role identifier. | Required. |
| `name` | Role label. | Returned as the visible primary role string. |
| `description` | Human-readable role description. | Optional. |
| `tenant_id` | Owning tenant, or null for system role. | Tenant-scoped when the role is custom. |
| `is_system_role` | Whether the role comes from the system catalog. | Boolean. |

### Relationships

- Roles are returned through the role query interface.
- Roles can be attached to users and influence login/MFA behavior upstream in auth.

### Business Rules

- The user controller does not hard-code role permissions beyond the minimal admin surface.

## Entity 003 - Invitation

The invitation is the create-user path that seeds a new account in a pending activation state.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `email` | Target user email. | Required; unique within the tenant. |
| `name` | Display name. | Required. |
| `password_hash` | Placeholder credential for the pending account. | Generated server-side. |
| `status` | Invite lifecycle state. | Created as `pending_activation`. |
| `role` | Initial role assignment. | Created as `user`. |

### Relationships

- The invitation becomes a tenant user record immediately in the current implementation.

### Business Rules

- Duplicate tenant email addresses are rejected.

## Entity 004 - Delegation Rules

Delegation rules are the per-user policy surface for delegated authority.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `user_id` | Target user. | Required. |
| `rules` | Delegation policy payload. | Not implemented yet. |

### Relationships

- The controller exposes read and write routes, but the implementation is still deferred.

### Business Rules

- The domain should treat this as a future capability, not a current source of truth.

## Entity 005 - Authority Limits

Authority limits are the per-user approval or spend ceiling surface.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `user_id` | Target user. | Required. |
| `limits` | Authority ceiling payload. | Not implemented yet. |

### Relationships

- The controller exposes an update route, but the current alpha behavior is placeholder only.

### Business Rules

- Authority limits should not be inferred from roles alone.

## Related Docs

- Workflow details: [workflows.md](./workflows.md)
- User/account lifecycle: [lifecycle.md](./lifecycle.md)
