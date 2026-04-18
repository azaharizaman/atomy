# Users And Roles Lifecycle

This file describes the user-account state model itself. The operational invite, suspend, reactivate, and role-list flows are documented in [workflows.md](./workflows.md).

## Purpose

The Users and Roles lifecycle describes how a tenant user moves through pending activation, active use, suspension, and any future governance states that are not yet implemented.

The implementation currently spans the identity package and the app-local user controller.

## State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `pending_activation` | The account exists but is not yet active for normal use. | `UserController::invite()` |
| `active` | The account can participate in the workspace. | `UserController::reactivate()`, auth session handling |
| `suspended` | The account is paused and should not be treated as active. | `UserController::suspend()` |

## Entry Criteria

### pending_activation

- A user has just been invited.
- The controller assigns a placeholder password hash and the base `user` role.

### active

- The user can log in and operate the workspace.
- The account has been reactivated or otherwise transitioned into an active state by upstream identity handling.

### suspended

- The account has been manually paused by an administrator.

## Transitions

### pending_activation -> active

- Trigger: the account is activated through the wider identity flow.
- The user controller itself does not currently expose a dedicated invite-acceptance endpoint.

### active -> suspended

- Trigger: an administrator suspends the account.

### suspended -> active

- Trigger: an administrator reactivates the account.

## Governance Rules

- All lifecycle actions must remain tenant-scoped.
- No cross-tenant lookups should leak whether a user exists elsewhere.
- Delegation rules and authority limits are deferred; they do not yet participate in a real state machine.

## Related Docs

- User workflows: [workflows.md](./workflows.md)
- User entities: [entities.md](./entities.md)
