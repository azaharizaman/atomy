# Alpha Task 6 Minimal Users & Roles Spec

## Document Control

- **Task:** Section 9, Task 6 - Decide Minimal Users/Roles Scope
- **Date:** 2026-04-17
- **Status:** Draft for review
- **Primary plan reference:** [ALPHA_RELEASE_PLAN_2026-04-15.md](./ALPHA_RELEASE_PLAN_2026-04-15.md)

## 1. Purpose

Task 6 closes blocker A5 by making a firm alpha product decision for tenant administration.

The current alpha exposes `Settings > Users & Roles` in the WEB shell and keeps the `/api/v1/users` and `/api/v1/roles` routes registered in Laravel, but both sides are still placeholder-heavy. The controller returns synthetic data, the page renders a disabled placeholder panel, and there is no explicit runtime contract for what alpha administrators are actually allowed to do.

This spec chooses the productionization path rather than the hide/gate path. It defines the minimal user-administration surface that alpha may support, the tenant-isolation rules that must apply, the API and WEB contracts, the endpoints that remain deferred, and the verification needed to call this blocker closed without expanding into a full RBAC program.

## 2. Product Decision

Task 6 adopts **Option A: productionize a minimal tenant-scoped Users & Roles surface**.

This means alpha will include:

- tenant-scoped user listing
- tenant-scoped user detail retrieval where the page needs it
- tenant-scoped user invite as an honest admin-created pending account flow
- tenant-scoped suspend/reactivate user lifecycle actions
- tenant-scoped read-only role listing

This task explicitly does **not** include:

- role CRUD
- permission matrix editing
- delegation rules administration
- authority limits administration
- invitation redemption UX
- email-delivery guarantees for invite

## 3. Current State

The repository already contains most of the infrastructure needed to support a minimal real surface, but the app boundary still behaves like a stub.

Observed current state:

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/UserController.php` returns synthetic payloads for:
  - `index`
  - `invite`
  - `show`
  - `update`
  - `suspend`
  - `reactivate`
  - `roles`
  - delegation and authority-limit routes
- `apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.tsx` is a placeholder page with disabled invite controls.
- `apps/atomy-q/API/routes/api.php` already exposes:
  - `GET /api/v1/users`
  - `POST /api/v1/users/invite`
  - `GET /api/v1/users/{id}`
  - `PUT /api/v1/users/{id}`
  - `POST /api/v1/users/{id}/suspend`
  - `POST /api/v1/users/{id}/reactivate`
  - `GET /api/v1/users/{id}/delegation-rules`
  - `PUT /api/v1/users/{id}/delegation-rules`
  - `PUT /api/v1/users/{id}/authority-limits`
  - `GET /api/v1/roles`
- The API app already has live identity infrastructure:
  - `AtomyUserQuery`
  - `AtomyUserPersist`
  - `RoleQueryInterface` binding through the Laravel identity adapter
  - a real `users` table and `roles` table model layer
- Existing WEB smoke tests already assume the `Users & Roles` page is reachable from the navigation shell.

This means the blocker is no longer “invent the identity plumbing”. The blocker is “replace the placeholder boundary with an honest alpha contract”.

## 4. Scope

### In scope

- Replace synthetic user/role controller responses with real tenant-scoped API behavior.
- Keep `Settings > Users & Roles` visible in alpha mode.
- Turn the WEB page into a real admin page with:
  - loading state
  - empty state
  - error state
  - list rendering
  - invite action
  - suspend/reactivate actions
- Define which existing `/users` routes are live in alpha and which remain explicitly deferred.
- Enforce tenant-safe `404` semantics for missing or wrong-tenant user access.
- Extend API and WEB tests to verify the minimal real surface.

### Out of scope

- Full role management and permission editing.
- SSO / MFA administration.
- Account profile editing beyond what the minimal users page strictly requires.
- Multi-tenant admin tooling.
- A broad settings-management program.
- New identity packages, new role systems, or major data-model redesign.

## 5. Alpha Surface Contract

Task 6 defines two route classes inside the existing `/users` and `/roles` surface.

### 5.1 Alpha-live routes

These routes must behave as real tenant-scoped functionality in alpha:

- `GET /api/v1/users`
- `POST /api/v1/users/invite`
- `GET /api/v1/users/{id}`
- `POST /api/v1/users/{id}/suspend`
- `POST /api/v1/users/{id}/reactivate`
- `GET /api/v1/roles`

### 5.2 Alpha-deferred routes

These routes may remain registered, but they must no longer return synthetic fake-success payloads:

- `PUT /api/v1/users/{id}`
- `GET /api/v1/users/{id}/delegation-rules`
- `PUT /api/v1/users/{id}/delegation-rules`
- `PUT /api/v1/users/{id}/authority-limits`

For these deferred routes, the controller must return an honest deferred response, consistent with the rest of the alpha cleanup work. They must not pretend to update business state.

## 6. Product Intent

The purpose of `Users & Roles` in alpha is narrow:

- allow a tenant admin to see who currently exists in the tenant
- allow a tenant admin to provision an additional internal user record
- allow a tenant admin to disable or re-enable access for an internal user
- show the role vocabulary that currently exists for the tenant

The purpose is **not** to prove the full future-state access-control product.

This means Task 6 should optimize for:

- honesty
- tenant isolation
- minimal useful admin capability
- low regression risk

Task 6 should not optimize for:

- maximum flexibility
- broad RBAC customization
- speculative future workflows

## 7. Runtime Contract

### 7.1 User list contract

`GET /api/v1/users` must return a tenant-scoped paginated or pagination-shaped list.

Required behavior:

- only users belonging to the authenticated tenant are returned
- ordering is stable and predictable for the UI
- payload rows are real persisted users, not seed rows or fabricated records
- the response includes enough metadata for the page to render an empty state versus a populated table

Minimum row fields expected by the WEB page:

- `id`
- `name`
- `email`
- `status`
- `role`
- `created_at`
- `last_login_at` when available

### 7.2 User detail contract

`GET /api/v1/users/{id}` must return a tenant-scoped user record.

Required behavior:

- if the user belongs to the authenticated tenant, return the persisted record
- if the user does not exist in the tenant, return `404`
- if the user exists in another tenant, also return `404`

Task 6 must not leak cross-tenant resource existence through `403`, alternate messages, or structurally different error payloads.

### 7.3 Invite contract

`POST /api/v1/users/invite` is defined as a minimal admin-created pending-account flow.

For alpha, “invite” means:

- create a real tenant-scoped user record
- set the new record to a pending or inactive pre-activation state
- use the existing default role model rather than introducing role assignment UX
- return the created record to the WEB page

For alpha, “invite” does **not** guarantee:

- email delivery
- acceptance token redemption
- self-service password setup flow
- welcome-journey completion

The controller and UI copy must not imply that a real outbound invitation workflow has been completed if the implementation only creates a pending account record.

### 7.4 Suspend/reactivate contract

`POST /api/v1/users/{id}/suspend` and `POST /api/v1/users/{id}/reactivate` must perform real persisted state changes.

Required behavior:

- the target user must be resolved within the current tenant
- wrong-tenant or missing users return `404`
- the persisted user status must change accordingly
- the response must reflect the updated stored status

These routes must not:

- return synthetic success for non-existent records
- silently no-op while still returning updated-looking payloads

### 7.5 Roles contract

`GET /api/v1/roles` is read-only in alpha.

Required behavior:

- return tenant-visible roles
- include tenant-scoped and shared/system-visible roles where the existing role query layer supports them
- provide enough structure for the page to display the current role vocabulary

This route must not be treated as a hidden full RBAC management API. It is display support for the alpha admin page.

## 8. Data and Validation Rules

### 8.1 Tenant isolation

All task-6 live routes must obey these rules:

- every list query must scope by `tenant_id`
- every single-record lookup must scope by `tenant_id` or normalize wrong-tenant access to `404`
- every mutation must apply only within the current tenant

Task 6 must follow the project rule that cross-tenant existence is not leaked.

### 8.2 Invite validation

The invite flow must validate at least:

- required email
- valid email shape
- required display name or sufficient name input to generate one
- duplicate email conflicts against the current global uniqueness behavior in the `users` table

The current schema makes `users.email` globally unique, so Task 6 must not pretend that the same email can be invited into multiple tenants. The runtime behavior and user-facing error message must reflect that current storage rule honestly.

### 8.3 Role assignment rule

Role editing is not part of Task 6.

New invite records should default to the existing baseline `user` role unless the implementation discovers that the app has a different already-established tenant-safe default. This task must not add a role picker unless the role assignment path is proven live, tenant-safe, and fully tested.

## 9. API Implementation Requirements

### 9.1 Dependency model

`UserController` must depend on specific interfaces rather than concrete implementations.

Expected dependencies:

- `Nexus\Identity\Contracts\UserQueryInterface`
- `Nexus\Identity\Contracts\UserPersistInterface`
- `Nexus\Identity\Contracts\RoleQueryInterface`
- `Nexus\Identity\Contracts\PasswordHasherInterface`

If additional interfaces are needed for cleaner separation, they should still be interface-first and consistent with the existing binding model in `AppServiceProvider`.

### 9.2 Controller behavior

The live methods in `UserController` must:

- extract the authenticated tenant from `ExtractsAuthContext`
- validate request payloads at the HTTP boundary
- call live identity query/persist interfaces
- normalize wrong-tenant access to `404`
- return real persisted state

The deferred methods in `UserController` must:

- return an explicit deferred/not-implemented response
- avoid fake state updates
- avoid returning stub business payloads

### 9.3 Deferred endpoint response shape

Task 6 does not require every deferred route to be deleted or hidden from the backend. It does require the backend to stop pretending those routes are implemented.

The deferred methods should follow the same honest-deferred style already used elsewhere in the app:

- explicit error/deferred status
- explicit message
- no fake `data` object that looks persisted

## 10. WEB Requirements

### 10.1 Page behavior

`apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.tsx` must stop behaving like a placeholder screen.

The page must:

- load real user data from the API
- render a clear empty state when there are no tenant users beyond the current baseline
- render an explicit error state when the API fails
- support inviting a user
- support suspend/reactivate actions
- avoid disabled placeholder controls once the real surface is live

### 10.2 UI honesty

The page must not:

- present fake success toasts for unimplemented actions
- render hard-coded stub user rows
- imply that role editing is supported when it is not
- imply that outbound invitation email definitely occurred if the backend only created a pending user record

### 10.3 Roles presentation

The page may show roles in one or both of these ways:

- as a display column for each user’s current effective role
- as a read-only side panel, summary block, or metadata section listing available tenant roles

The page should not expose role mutation controls unless the implementation explicitly extends scope and corresponding tests, which Task 6 does not currently authorize.

## 11. Error Handling

### 11.1 Status codes

Task 6 should use these response expectations:

- `200` for successful list/show/status-change/roles reads
- `201` for successful invite creation
- `404` for missing or wrong-tenant user access
- `409` for duplicate email conflicts under the current global uniqueness rule
- `422` for malformed request payloads
- `501` or the project’s established deferred-response equivalent for routes intentionally left unimplemented in alpha

### 11.2 Failure behavior

Task 6 must not:

- return synthetic IDs
- return fake users
- silently ignore a failed state change
- distinguish wrong-tenant from missing-user access in a way that leaks information

## 12. Testing Strategy

### 12.1 API tests

Extend `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php` with coverage for:

- tenant-scoped users list
- tenant-scoped user detail
- cross-tenant `404` semantics
- invite creates a real pending user record
- duplicate email conflict behavior
- suspend changes persisted status
- reactivate changes persisted status
- roles list returns real role data

If deferred `/users` routes remain registered, add assertions that they return an honest deferred response instead of stub payloads.

### 12.2 WEB tests

Add or update tests for:

- page renders a real list
- empty-state rendering
- error-state rendering
- invite interaction
- suspend/reactivate interaction

Update existing navigation and smoke tests so they verify the live page behavior rather than the placeholder copy.

### 12.3 Regression focus

The highest-risk regressions are:

- cross-tenant existence leakage
- fake-success responses surviving in the controller
- the users page still rendering placeholder-only behavior
- invite semantics drifting into “pretend email invite” territory without a real backend path

## 13. File-Level Targets

### Primary files

- `apps/atomy-q/API/app/Http/Controllers/Api/V1/UserController.php`
- `apps/atomy-q/WEB/src/app/(dashboard)/settings/users/page.tsx`
- `apps/atomy-q/API/tests/Feature/IdentityGap7Test.php`

### Likely supporting files

- `apps/atomy-q/WEB/tests/dashboard-nav.spec.ts`
- `apps/atomy-q/WEB/tests/screen-smoke.spec.ts`
- WEB hook or client files needed to fetch `/users` and `/roles`
- implementation summary docs if the package/app convention requires them for this slice

## 14. Completion Criteria

Task 6 is complete only when all of the following are true:

- `Users & Roles` remains visible in alpha navigation
- the page shows real tenant data rather than a stub panel
- `/api/v1/users` and `/api/v1/roles` return real tenant-scoped data
- invite creates a real pending account record
- suspend/reactivate perform real persisted state changes
- wrong-tenant user access returns `404`
- deferred `/users` routes no longer return fake success payloads
- API and WEB tests covering the minimal live surface pass

## 15. Rollout Notes

Task 6 should be implemented as a narrow alpha-hardening change, not as a general identity-admin initiative.

Recommended execution order:

1. Replace synthetic controller methods for the alpha-live routes.
2. Convert deferred methods to honest deferred responses.
3. Add API coverage for tenant scoping and lifecycle behavior.
4. Rebuild the WEB page on top of the live routes.
5. Update smoke and navigation coverage to assert the real alpha behavior.

If later work needs full RBAC administration, that should be tracked as a separate post-alpha task with its own product decision and spec.
