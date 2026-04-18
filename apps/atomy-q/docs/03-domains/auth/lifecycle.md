# Auth Lifecycle

This file describes the auth session state model itself. The operational login, reset, and SSO flows are documented in [workflows.md](./workflows.md).

## Purpose

The Auth lifecycle describes how a user moves from anonymous access through password login, optional MFA, authenticated session use, token refresh, logout, and expiration.

The implementation is split between the API auth controller, the MFA challenge store, the JWT service, and the auth coordinator.

## State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `anonymous` | No authenticated request context is attached. | Protected routes before middleware/auth context resolution. |
| `password_authenticated` | The password step succeeded, but the user still needs to finish the login flow. | `apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php` |
| `mfa_challenge_pending` | A challenge exists and awaits a valid code. | `AuthController::login()`, `AuthController::mfaVerify()` |
| `authenticated` | JWT tokens have been issued and the request can proceed as a signed-in tenant user. | `AuthController::login()`, `AuthController::mfaVerify()`, `AuthController::sso()` |
| `refreshable` | The client holds a refresh token that can mint a new access token. | `AuthController::refresh()` |
| `logged_out` | The auth coordinator has invalidated the current session context. | `AuthController::logout()` |
| `expired_or_invalid` | The token, challenge, or reset request is no longer usable. | `AuthController::mfaVerify()`, `AuthController::refresh()`, `AuthController::resetPassword()` |

## Entry Criteria

### anonymous

- No auth context is attached to the request.
- The user has not completed login or the session has expired.

### password_authenticated

- The password login step succeeded.
- The auth coordinator returned a user context that still needs MFA completion.

### mfa_challenge_pending

- The login flow requires a second factor.
- A challenge record exists and has not been consumed.

### authenticated

- JWT access and refresh tokens have been issued.
- The tenant/user context is attached to the request or stored in the client session.

### refreshable

- The client holds a valid refresh token with the correct token type and tenant context.

### logged_out

- The current session has been ended through the coordinator.

### expired_or_invalid

- A challenge is expired or consumed.
- A refresh token does not decode or is not the correct token class.
- A reset token is invalid or expired.

## Transitions

### anonymous -> password_authenticated

- Trigger: password login succeeds but MFA is still required.

### anonymous -> authenticated

- Trigger: password login succeeds and MFA is not required.
- Trigger: SSO callback returns a valid user context.

### password_authenticated -> mfa_challenge_pending

- Trigger: the login flow creates an MFA challenge.

### mfa_challenge_pending -> authenticated

- Trigger: the user submits a valid MFA code and the coordinator completes login.

### authenticated -> refreshable

- Trigger: the client receives a refresh token together with the access token.

### refreshable -> authenticated

- Trigger: the refresh token is decoded and a new token pair is issued.

### authenticated -> logged_out

- Trigger: the user explicitly logs out and the coordinator accepts the request.

### any state -> expired_or_invalid

- Trigger: the token, challenge, or reset request fails validation or passes its lifetime.

## Session Rules

- The tenant context is mandatory for protected work; missing tenant context is not treated as a valid anonymous session.
- Authentication responses should not reveal whether a user exists in another tenant.
- MFA challenges are single-use once consumed.
- Refresh tokens must keep the same tenant context as the original session.
- Logout is best-effort only when the auth context is present; the code currently returns a placeholder `501` if no auth context is attached.

## Related Docs

- Login and reset flow: [workflows.md](./workflows.md)
- Auth entities: [entities.md](./entities.md)
