# Users And Roles Overview

## Purpose

The Users and Roles domain is the tenant-scoped administrative surface for managing team membership and the small role catalog that Atomy-Q exposes in alpha.

It covers user listing, user invitations, suspension/reactivation, role lookup, and the currently deferred account-governance actions that are not productized yet.

Operational workflows are documented in [workflows.md](./workflows.md).
The user/account state model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Current Decisions

- All user operations are tenant-scoped.
- The surface is intentionally minimal and does not try to replace the full identity package.
- Invitations create a pending activation record for a new tenant user.
- Suspended users can be reactivated through the same surface.
- Update, delegation rules, and authority-limits are deferred and return explicit placeholder responses.

## Core Entities

- `User account`: the tenant-scoped person record returned by the API
- `Role`: a tenant-scoped or system-scoped role returned by the role lookup endpoint
- `Invitation`: the create-user flow that seeds a pending activation account
- `Delegation rules`: the deferred per-user delegation policy surface
- `Authority limits`: the deferred per-user authorization ceiling surface

## Inputs

- Authenticated tenant context
- User search filters such as `status`
- Invite payload containing `email` and `name`
- User identifier for show/suspend/reactivate and deferred subroutes

## Outputs

- Paginated user listings
- User details with tenant and role information
- Newly invited user records
- Role listings
- Explicit `501` placeholder responses for deferred actions

## Dependencies

### Other Atomy-Q domains

- **Auth** - the auth controller consumes the same tenant/user identity surface.
- **Tenant and Company** - all user records are tenant-scoped.
- **Dashboard shell** - user state affects who can operate the workspace.

### Nexus packages

- `Nexus\Identity`

### External dependencies

- Laravel
- Password hashing
- UUID/ULID generation
