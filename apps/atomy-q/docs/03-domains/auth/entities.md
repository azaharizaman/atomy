# Auth Entities

This document defines the core business entities used by the Auth domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For auth session semantics, see [lifecycle.md](./lifecycle.md#state-model).

## Entity 001 - Auth Context

The auth context is the tenant/user/session identity that the API attaches to the request after authentication succeeds.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `auth_user_id` | Current authenticated user. | Required for signed-in flows. |
| `auth_tenant_id` | Current tenant scope. | Required for tenant-scoped flows. |
| `auth_session_id` | Optional server-side session identifier. | Optional; used when present in the access token. |
| `role` | Primary role returned to the client. | Derived from the user’s role set. |

### Relationships

- Auth context is derived from the user record, tenant record, and current session state.
- The context is used by downstream domains to enforce tenant-scoped access.

### Business Rules

- Missing tenant context is a hard failure for protected routes.
- The context must not be inferred from request body fields.

## Entity 002 - JWT Access Token

The access token is the bearer credential the WEB app uses after login.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `sub` | User identifier. | Must match the authenticated user. |
| `tenant_id` | Tenant scope. | Must match the authenticated user’s tenant. |
| `sid` | Optional session identifier. | Present when the session provides one. |
| `type` | Token class. | `access`. |
| `expires_in` | Lifetime in seconds. | Derived from the configured JWT TTL. |

### Relationships

- The access token is paired with a refresh token in the login response.
- The WEB app stores it in the session layer and uses it for authenticated API requests.

### Business Rules

- Refresh tokens must not be accepted where an access token is expected.
- Token issuance must preserve tenant scope.

## Entity 003 - JWT Refresh Token

The refresh token allows the client to mint a new access token without repeating the login flow.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `sub` | User identifier. | Must match the original login context. |
| `tenant_id` | Tenant scope. | Required. |
| `type` | Token class. | `refresh`. |

### Relationships

- A valid refresh token is only meaningful for the tenant that issued it.

### Business Rules

- Tokens without a user or tenant identifier are rejected.
- Invalid token types are rejected.

## Entity 004 - MFA Challenge

The MFA challenge gates second-factor login after the password step succeeds.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `challenge_id` | Challenge identifier returned to the client. | Required when MFA is needed. |
| `user_id` | User being challenged. | Must belong to the tenant. |
| `tenant_id` | Tenant scope. | Required. |
| `method` | Verification method. | `totp` in the current implementation. |
| `consumed_at` | Consumption timestamp. | Null until verified. |
| `expires_at` | Challenge expiry. | Required for validity. |
| `attempts` | Retry counter. | Incremented on failed verification. |

### Relationships

- MFA challenges are created from the auth coordinator flow.
- The challenge is consumed only after successful TOTP or backup-code verification.

### Business Rules

- Consumed, expired, or tenant-mismatched challenges are rejected.
- The public error message stays generic.

## Entity 005 - Password Reset Request

The password reset request is the email/token flow used to recover access.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `email` | Target account email. | Required. |
| `token` | Reset token from the mail flow. | Required; must be valid and unexpired. |
| `password` | New password. | Must satisfy the reset validator. |

### Relationships

- Password reset requests are handled by the reset service and are separate from the JWT session itself.

### Business Rules

- The request response should not reveal whether the account exists.
- Expired or invalid tokens fail without changing the current password.

## Entity 006 - SSO Handshake

The SSO handshake is the tenant-scoped OIDC exchange used by the auth controller.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `action` | Which step is being performed. | `init` or `callback`. |
| `tenant_id` | Tenant scope. | Required. |
| `code` | Authorization code from the identity provider. | Required for callbacks. |
| `state` | Anti-forgery state token. | Required for callbacks. |

### Relationships

- Initiation uses tenant-scoped redirect URI configuration.
- Callback completion returns the same JWT pair as the password flow.

### Business Rules

- Missing or invalid provider configuration is a runtime failure.
- Callback responses without a user identity are treated as authentication failures.

## Related Docs

- Workflow details: [workflows.md](./workflows.md)
- Session semantics: [lifecycle.md](./lifecycle.md)
