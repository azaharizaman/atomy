# Auth Domain Overview

## Purpose

The Auth domain covers the entry and session-control surface for Atomy-Q: company registration, login, SSO callback handling, MFA verification, password reset, token refresh, logout, and the small amount of device-trust plumbing that exists in alpha.

The implementation is split across the public onboarding flow, the tenant-scoped login/session endpoints, and the WEB app session bootstrap that consumes the returned tokens.

Operational workflows are documented in [workflows.md](./workflows.md).
The auth session model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Current Decisions

- Tenant isolation is enforced with `tenant_id`, not `company_id`.
- Company registration is part of the same customer entry path as login.
- Login, SSO, MFA verification, password reset, refresh, and logout are all active alpha behavior.
- MFA enrollment is not fully productized; the code supports a minimal challenge/verification path, not a complete admin UX.
- Auth failures must not leak cross-tenant resource existence.
- Vendor-portal auth is out of scope for the current buyer-side alpha.

## Core Entities

- `Auth context`: the tenant/user/session identifiers injected into request attributes after authentication
- `JWT access token`: the short-lived bearer token used by the WEB app
- `JWT refresh token`: the token used to mint a new access token
- `MFA challenge`: the tenant-scoped challenge record that gates second-factor login
- `Password reset request`: the email/token reset flow handled by the reset service
- `SSO handshake`: the tenant-scoped OIDC initiation/callback exchange
- `Device trust request`: the placeholder endpoint for future trusted-device support

## Inputs

- Company registration payload from the onboarding form
- Email/password login payload
- SSO `action`, `tenant_id`, `code`, and `state`
- MFA `challenge_id`, optional `tenant_id`, and `otp`
- Password reset `email`, `token`, and `password`
- Refresh token payload
- Authenticated request context for logout and device trust

## Outputs

- JWT access and refresh tokens
- Authenticated user payloads that include `tenantId`
- MFA challenge IDs when second factor is required
- Password reset acknowledgment responses
- Session logout acknowledgments
- Audit-log events for successful and failed authentication attempts

## Dependencies

### Other Atomy-Q domains

- **Tenant and Company** - company registration creates the initial workspace and owner account.
- **Users and Roles** - login resolves the user record and role set for the tenant-scoped account.
- **Dashboard shell** - successful authentication opens the workspace shell.

### Nexus packages

- `Nexus\Identity`
- `Nexus\Tenant`
- `Nexus\IdentityOperations`

### External dependencies

- Laravel
- Next.js
- JWT handling
- OIDC provider configuration
- Password reset transport
