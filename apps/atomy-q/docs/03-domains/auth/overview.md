# Auth Domain Overview

## Scope

Authentication for Atomy-Q covers tenant company registration, tenant user login, JWT-backed session handling, session validation, and the minimal alpha-ready MFA challenge path.

## Current Decisions

- Tenant isolation is enforced with `tenant_id`, not `company_id`.
- Login and bootstrap flows are part of alpha.
- Full MFA enrollment productization is not part of alpha.
- Auth failures must not leak cross-tenant resource existence.
- The canonical alpha auth surface is buyer-side only; vendor portal login is out of scope.

## Application Ownership

- API:
  `apps/atomy-q/API` owns auth routes, JWT issuance, session checks, and tenant/user bootstrap.
- WEB:
  `apps/atomy-q/WEB` owns login, registration, session bootstrap, and guarded navigation.

## Nexus Dependencies

Layer 1:
- `packages/Identity`
- `packages/Tenant`

Layer 2:
- `orchestrators/IdentityOperations`
- `orchestrators/TenantOperations`

Layer 3:
- `adapters/Laravel/Identity`
- `adapters/Laravel/Tenant`
- app-local auth controllers, middleware, and providers in `apps/atomy-q/API/app`
