# Auth Workflows

This file covers the operational business flows for the Auth domain. It does not describe session state semantics; that belongs in [lifecycle.md](./lifecycle.md).

## Workflow Access

- Company registration is public.
- Login, MFA verification, refresh, logout, and device trust require the auth middleware/session plumbing.
- SSO initiation and callback are tenant-scoped and depend on configured identity-provider settings.

## Workflow 001 - Company Registration

### Trigger

A new customer submits the company onboarding form.

### Steps

1. The WEB form collects company, owner, and workspace defaults.
2. The client submits the onboarding request to the API registration endpoint.
3. The API validates the payload and creates the tenant and owner records in a transaction.
4. The API issues the initial JWT session.
5. The WEB app stores the session and redirects to the workspace shell.

### Outputs

- Tenant record created
- Owner user created
- Session tokens returned
- Auth store initialized in the browser

### Failure Handling

- Validation failures remain on the onboarding screen.
- Duplicate company or tenant code returns a validation error.
- A partial tenant or owner record is rolled back if creation fails mid-transaction.
- Incomplete bootstrap responses stop the client from redirecting.

## Workflow 002 - Password Login And MFA Challenge

### Trigger

A tenant user submits an email/password login request.

### Steps

1. The API validates the credentials and resolves the tenant-bound user record.
2. The auth coordinator checks whether MFA is required by the account or one of its roles.
3. If MFA is required, the API creates a challenge and returns `401` with a `challenge_id`.
4. If MFA is not required, the API issues access and refresh tokens immediately.
5. The response payload includes the user identity and tenant identifier for the client session store.

### Outputs

- Access token and refresh token on success
- MFA challenge ID when second factor is required
- Audit events for both success and failure paths

### Failure Handling

- Invalid credentials return `401` without leaking whether the account exists in another tenant.
- Locked or inactive accounts are treated as invalid credentials in the public response.
- Unexpected service failures return `500` and are reported to the application logger.

## Workflow 003 - MFA Verification

### Trigger

A user submits an MFA code against an existing challenge.

### Steps

1. The API resolves the challenge by challenge ID and optional tenant ID.
2. The API rejects consumed, expired, missing, or tenant-mismatched challenges.
3. The verification service checks the TOTP code, then backup codes if needed.
4. The challenge is consumed on success.
5. The auth coordinator completes the login session and the API returns JWT tokens.

### Outputs

- Verified login session
- Access token and refresh token
- Audit trail for challenge issuance, success, and failure

### Failure Handling

- Invalid or expired challenges return `401`.
- Bad codes increment the challenge attempt counter.
- Session completion errors return `403` when the login session or tenant context is inconsistent.

## Workflow 004 - Password Reset And Refresh

### Trigger

A user requests a password reset or submits a refresh token.

### Steps

1. The password-reset endpoint accepts the email address and sends a reset link if an account exists.
2. The reset endpoint validates the email, token, and password confirmation before completing the reset.
3. The refresh endpoint decodes the refresh token and verifies that it is a refresh token.
4. If the refresh token is valid, the API reissues access and refresh tokens.

### Outputs

- Reset-mail acknowledgment
- Password reset confirmation
- Fresh session tokens after refresh

### Failure Handling

- Reset-link requests remain intentionally vague so account existence is not leaked.
- Expired or malformed reset tokens return `422`.
- Invalid refresh tokens return `401`.

## Workflow 005 - Logout And Device Trust

### Trigger

A signed-in user logs out or attempts to mark the current device as trusted.

### Steps

1. The logout endpoint reads the current auth context from request attributes.
2. If the auth context is present, the auth coordinator performs the logout action.
3. The device-trust endpoint currently returns a placeholder response.

### Outputs

- Logout acknowledgment
- Placeholder device-trust response

### Failure Handling

- Missing auth context returns a `501` placeholder response for logout rather than pretending the flow is complete.
- Device trust is not implemented yet and returns `501`.

## Domains Involved

- Auth
- Tenant and Company
- Users and Roles
- Dashboard shell
