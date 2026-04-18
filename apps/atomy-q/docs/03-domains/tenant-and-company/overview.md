# Tenant And Company Overview

## Purpose

The Tenant And Company domain covers the first workspace bootstrap for Atomy-Q: creating a tenant/company record, creating the owner account, and returning the authenticated session that opens the app.

It is implemented by the public company registration flow in [`apps/atomy-q/WEB/src/app/(auth)/register-company/page.tsx`](/home/azaharizaman/dev/atomy/apps/atomy-q/WEB/src/app/(auth)/register-company/page.tsx) and the backend onboarding endpoint in [`apps/atomy-q/API/app/Http/Controllers/Api/V1/RegisterCompanyController.php`](/home/azaharizaman/dev/atomy/apps/atomy-q/API/app/Http/Controllers/Api/V1/RegisterCompanyController.php).

Operational business flows for this domain are documented in [workflows.md](./workflows.md).
The tenant state model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Core Entities

- `Tenant`: the persisted workspace container and isolation boundary
- `Company`: the user-facing tenant record shown during onboarding
- `Owner user`: the first admin account created for the tenant
- `Workspace defaults`: timezone, locale, and currency values captured during onboarding
- `Session tokens`: the access and refresh tokens returned after onboarding

## Inputs

- `tenant_code`
- `company_name`
- `owner_name`
- `owner_email`
- `owner_password`
- Optional workspace defaults: `timezone`, `locale`, `currency`
- Browser-derived defaults in the WEB form for timezone and locale
- Public onboarding request to `POST /api/v1/auth/register-company`
- Follow-up session bootstrap via `POST /api/v1/auth/login` or `/me` when the onboarding response needs to be completed client-side

## Outputs

- A persisted tenant row with tenant metadata
- A persisted owner user linked to that tenant
- JWT access and refresh tokens for the owner session
- A bootstrap user payload that includes `tenantId`
- Redirect to the dashboard landing page after successful onboarding
- Updated auth store state in the WEB app so the session survives navigation

## Dependencies

### Other Atomy-Q domains

- **Auth** - the onboarding flow ends by creating a session that is consumed by the login/session plumbing in the auth domain.
- **Dashboard shell** - successful onboarding redirects into the main workspace at `/`.
- **Settings / Users & Roles** - tenant ownership and the first admin user feed the later admin surface, even though the registration flow does not render that screen directly.

### Nexus packages

- `Nexus\TenantOperations`

### External dependencies

- Laravel
- Next.js
- React Hook Form
- Zod
- Axios
- sonner
- firebase/php-jwt
