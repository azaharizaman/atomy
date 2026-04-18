# Tenant And Company Lifecycle

This file describes the tenant state model itself. The operational onboarding flow is documented in [workflows.md](./workflows.md).

## Purpose

The Tenant And Company lifecycle defines how a tenant record moves between states over time, from creation through suspension, archiving, restoration, and hard deletion.

The current implementation spans the Nexus tenant package and the Atomy-Q API adapter layer:

- `packages/Tenant` defines the canonical tenant lifecycle vocabulary and transition rules.
- `apps/atomy-q/API` persists the tenant record, onboarding progress, retention fields, and soft-delete state.

## State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `pending` | Default lifecycle state for a newly created tenant in the Nexus package. | `packages/Tenant/src/Enums/TenantStatus.php`, `packages/Tenant/src/Services/TenantLifecycleService.php` |
| `active` | Tenant is ready for normal workspace usage. The Atomy onboarding creator writes this state immediately after registration. | `packages/Tenant/src/Enums/TenantStatus.php`, `apps/atomy-q/API/app/Adapters/Tenant/EloquentTenantCreator.php` |
| `trial` | Tenant is in a trial period. Trial expiry is tracked by `trial_ends_at`. | `packages/Tenant/src/Enums/TenantStatus.php`, `apps/atomy-q/API/app/Models/Tenant.php` |
| `suspended` | Tenant access is paused but the record remains intact. | `packages/Tenant/src/Enums/TenantStatus.php`, `apps/atomy-q/API/app/Models/Tenant.php` |
| `queued_deletion` | Tenant is staged for deletion or reactivation in the Nexus package. | `packages/Tenant/src/Enums/TenantStatus.php` |
| `archived` | Tenant is soft-deleted and retained for recovery or retention handling. | `packages/Tenant/src/Enums/TenantStatus.php`, `apps/atomy-q/API/app/Adapters/Tenant/EloquentTenantPersistence.php` |
| `deleted` | Tenant is hard-deleted and no longer recoverable through the normal restore path. | `apps/atomy-q/API/app/Adapters/Tenant/EloquentTenantPersistence.php`, `packages/Tenant/src/Services/TenantLifecycleService.php` |

## Entry Criteria

### pending

- A tenant has been created in the package lifecycle layer.
- The record exists, but the workspace is not yet treated as fully active in the package model.

### active

- The tenant has completed onboarding.
- The Atomy onboarding flow creates the tenant in `active` state with `onboarding_progress = 100`.
- The record is ready for normal use in the app.

### trial

- A tenant has been assigned trial status.
- `trial_ends_at` is present and can be checked with `isTrialExpired()`.

### suspended

- A tenant is intentionally paused by lifecycle policy or admin action.

### queued_deletion

- A tenant has been marked for later deletion or restoration in the package model.

### archived

- A tenant has been soft-deleted.
- The Atomy adapter sets `status = archived` before calling soft delete.

### deleted

- The tenant has been force-deleted.
- The row is no longer recoverable through the normal restore path.

## Transitions

### pending -> active

- Trigger: tenant onboarding completes successfully.
- Current Atomy path: the onboarding creator writes `active` directly.
- Package path: `TenantLifecycleService::activateTenant()`.

### pending -> trial

- Trigger: tenant is created with trial entitlement.
- Package path: supported by `TenantStatus::canTransitionTo()`.

### pending -> archived

- Trigger: tenant is removed before full activation.
- Package path: supported by `TenantStatus::canTransitionTo()`.

### pending -> queued_deletion

- Trigger: tenant is staged for deletion while still pending.
- Package path: supported by `TenantStatus::canTransitionTo()`.

### active -> suspended

- Trigger: tenant is paused by lifecycle policy or administration.
- Package path: `TenantLifecycleService::suspendTenant()`.

### active -> archived

- Trigger: tenant is archived through the lifecycle service or API adapter.
- Atomy path: `EloquentTenantPersistence::delete()` sets `status = archived` and soft deletes the row.

### active -> queued_deletion

- Trigger: tenant is scheduled for deletion.
- Package path: supported by `TenantStatus::canTransitionTo()`.

### suspended -> active

- Trigger: tenant is reactivated.
- Package path: `TenantLifecycleService::reactivateTenant()`.

### suspended -> archived

- Trigger: tenant is archived instead of reactivated.
- Package path: supported by `TenantStatus::canTransitionTo()`.

### suspended -> queued_deletion

- Trigger: tenant is moved from suspension into deletion staging.
- Package path: supported by `TenantStatus::canTransitionTo()`.

### trial -> active

- Trigger: trial is converted into a live tenant.
- Package path: supported by `TenantStatus::canTransitionTo()`.

### trial -> suspended

- Trigger: trial access is paused.
- Package path: supported by `TenantStatus::canTransitionTo()`.

### trial -> archived

- Trigger: trial tenant is removed or retained after trial completion.
- Package path: supported by `TenantStatus::canTransitionTo()`.

### trial -> queued_deletion

- Trigger: trial tenant is scheduled for deletion.
- Package path: supported by `TenantStatus::canTransitionTo()`.

### queued_deletion -> active

- Trigger: tenant is restored before hard deletion.
- Package path: supported by `TenantStatus::canTransitionTo()`.
- Atomy path: `EloquentTenantPersistence::restore()` returns the row to `active`.

### archived -> deleted

- Trigger: retention is complete and hard delete is requested.
- Atomy path: `EloquentTenantPersistence::forceDelete()`.

## Retention Rules

- Archived tenants are kept as soft-deleted rows until `forceDelete()` runs.
- `retention_hold_until` marks a tenant as queued for deletion in the Atomy model.
- `isQueuedForDeletion()` returns true when `retention_hold_until` is present or the tenant is soft-deleted.
- `trial_ends_at` is a time signal only; the current Atomy model reports expiration, but does not auto-transition the state by itself.

## Dependencies

### Other Atomy-Q domains

- **Tenant and Company workflows** - onboarding sets the initial active state.
- **Auth** - authenticated sessions consume the tenant status attached to the user context.
- **Dashboard shell** - uses the tenant session to decide whether the workspace can render.
- **Settings / Users & Roles** - tenant status affects whether the admin surface is meaningful to the current user.

### Nexus packages

- `Nexus\Tenant`

### External dependencies

- Laravel

