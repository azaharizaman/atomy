# Tenant And Company Workflows

This file covers the operational business flow for the Tenant And Company domain. It does not describe the tenant state model; that belongs in a separate lifecycle document.

## Workflow Access

- The onboarding flow is public and starts at `/register-company`.
- No authenticated session is required to submit the form.
- The workflow is intended for a first-time customer creating the initial tenant and owner account.

## Workflow 001 - Self-Serve Tenant Creation

### Trigger

A new customer starts the company registration flow and submits the onboarding form.

### Steps

1. The WEB registration page pre-fills timezone and locale defaults where possible.
2. The form validates `company_name`, `tenant_code`, owner details, and optional workspace defaults.
3. The client sends `POST /api/v1/auth/register-company`.
4. The API validates the request and starts a database transaction.
5. The tenant onboarding coordinator creates the tenant record.
6. The owner account is created and linked to the new tenant.
7. The API issues access and refresh tokens for the owner session.
8. The WEB app stores the authenticated session and redirects the user to `/`.

### Outputs

- Tenant record created
- Owner account created and assigned admin access
- Workspace defaults captured on the tenant
- Access and refresh tokens returned to the client
- Auth store initialized with the new tenant session
- Dashboard opened for the new workspace

### Failure Handling

- If form validation fails, the request never leaves the client and the user stays on the onboarding screen.
- If the API rejects the payload, the form shows field-level errors or a generic onboarding error.
- If the tenant code or company name already exists, the API returns `422` and no duplicate tenant is persisted.
- If owner creation fails after tenant creation starts, the transaction rolls back and no partial tenant is left behind.
- If the API returns an incomplete bootstrap response, the WEB app stops short of redirecting and shows a session bootstrap error.

### Domains Involved

- Tenant and Company
- Auth
- Dashboard shell
- Settings / Users & Roles

