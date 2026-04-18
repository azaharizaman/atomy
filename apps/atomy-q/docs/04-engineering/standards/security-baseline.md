# Atomy-Q Security Baseline

## Changelog

| Date | Version | Change |
|---|---|---|
| 2026-04-18 | 1.0 | Initial Atomy-Q security baseline for API, WEB, staging, tenant isolation, and Nexus dependency usage. |

## Purpose

This document defines the minimum security baseline for the Atomy-Q SaaS application.

The goal is to protect:

- tenant data
- buyer authentication and session integrity
- quote files and comparison evidence
- release evidence and operational posture
- API and WEB contract trust

This baseline applies to:

- `apps/atomy-q/API`
- `apps/atomy-q/WEB`
- Nexus Layer 1 packages in `packages/`
- Nexus Layer 2 orchestrators in `orchestrators/`
- Nexus Layer 3 adapters in `adapters/Laravel/`
- docs and release-management content that describes security-relevant behavior

## Security Principles

### 1. Tenant isolation is mandatory

- Every buyer-data access path must respect `tenant_id`.
- Cross-tenant existence must not be exposed through normal API behavior.
- Reads and writes must be tenant-scoped by design, not patched after the fact.

### 2. Identity is centralized

- Authentication and authorization must flow through `Nexus\Identity`.
- Custom auth logic must not be introduced in controllers, pages, or ad hoc helpers.
- Session, MFA, and RBAC behavior must stay consistent across API and WEB.

### 3. Live mode must be honest

- The WEB must not silently fall back to seed data in live mode.
- Unsupported surfaces must be hidden or clearly deferred.
- Staging and release evidence are invalid if they depend on mock mode.

### 4. Secrets must never leak

- Secrets belong in environment configuration or secret stores, not in code or docs.
- Tokens, passwords, keys, and signed URLs must not be logged or echoed back to the browser.
- Example environment files may document variable names and shape, not real secrets.

### 5. Security must be built into workflows

- Security is not a late-stage checklist item.
- Changes that affect auth, tenant scope, file handling, or release posture must be reviewed through the security baseline.

## Identity And Access Control

### Authentication

- Use the supported authentication flow from the API.
- Do not create parallel auth systems for special cases.
- Session bootstrap, login, logout, refresh, and MFA behavior must remain consistent.

### Authorization

- All authorization must flow through `Nexus\Identity`.
- Use the proper interface for the problem:
  - `PermissionCheckerInterface` for capability checks
  - `PolicyEvaluatorInterface` for context-aware decisions
- Do not embed authorization rules directly in UI logic unless the UI is only reflecting a server-enforced decision.

### Tenant Scope

- Any operation that touches buyer-owned data must include tenant scope.
- A missing tenant is a security failure.
- Tenant mismatch must not reveal more than necessary.

### Roles

- The minimal Users & Roles surface is allowed only as a tenant-scoped admin capability.
- Do not expand roles or permissions without an explicit product and release decision.

## API Security Requirements

### Request Handling

- Validate input before it reaches business logic.
- Reject malformed payloads instead of coercing them into acceptance.
- Use tenant-safe existence checks where the response should not reveal ownership.

### Response Handling

- Do not leak raw exception traces or framework internals in API responses.
- Use sanitized error shapes for operational failures.
- Do not return synthetic success data.

### State-Changing Endpoints

- All writes must be intentional and scoped.
- Sensitive multi-write flows should be transactional.
- Idempotency should be used where repeated submissions could create unsafe duplication.

### File and Quote Uploads

- Uploaded quote files must be treated as untrusted input.
- Storage paths must be controlled and tenant-safe.
- File processing must not silently succeed if the file cannot be read, parsed, or persisted correctly.

### OpenAPI and Client Trust

- If the API contract changes, regenerate the WEB client and verify the diff.
- The client must not be used to obscure contract drift.

## WEB Security Requirements

### Mock Mode

- `NEXT_PUBLIC_USE_MOCKS=true` is local-only and demo-only.
- Mock mode must never be treated as release evidence.
- Mock branches must be visibly separated from live branches in hooks and docs.

### Live Mode

- Live mode must fail loudly on unavailable, malformed, or undefined data unless the contract explicitly allows empty states.
- Do not catch live failures and convert them into fake success states.
- Do not use seed data as a hidden fallback in staging or alpha evidence.

### Browser Exposure

- Do not expose secrets in browser-visible environment variables.
- Only public API values that are safe for the browser should be prefixed with `NEXT_PUBLIC_`.
- Do not store long-lived secrets in local storage or unsafe client-side persistence.

### UI Safety

- Loading, empty, unavailable, and error states must be explicit.
- Unsupported paths should show deferred or unavailable states, not placeholder success.
- Sensitive actions must not be implied as successful unless the API confirmed them.

## Storage And File Security

### Quote Files

- Uploaded quote files are untrusted.
- Only approved storage disks and paths may be used.
- File persistence must be verifiable in staging and release smoke.
- Access to stored quote files must remain tenant-safe.

### Evidence Files

- Release artifacts, screenshots, and smoke evidence must not contain secrets or sensitive tenant data beyond what is needed for release proof.
- Evidence should be stored only in approved locations.

### Storage Configuration

- Staging must use a real writable storage backend.
- The storage configuration must be explicit in the runbook.
- Do not rely on implicit default disks for alpha evidence.

## Logging And Error Handling

### Logging Rules

- Log enough to diagnose a failure.
- Do not log secrets, passwords, tokens, raw signed URLs, or sensitive payload bodies.
- Sanitize user input before it reaches logs where it could expose buyer data.

### Error Rules

- Use domain-specific exceptions for expected business failures.
- Convert infrastructure failures into safe, actionable application errors where appropriate.
- Do not expose stack traces or database internals in browser-facing or customer-facing responses.

### Operational Signals

- Quote ingestion failures should be visible and traceable.
- Tenant and auth failures must be precise but not revealing.
- Release checklist evidence should reflect actual operational state, not guessed state.

## Dependency Security

### Package Dependencies

- Prefer the smallest Nexus dependency that solves the problem.
- Do not introduce a package solely because it is convenient if a narrower package already exists.
- Do not duplicate security-sensitive behavior in Atomy-Q when Nexus already owns it.

### Update Discipline

- Security-relevant dependency updates must be reviewed for their effect on auth, storage, transport, and release posture.
- If a dependency update changes client or server behavior, re-run the affected tests and release gates.

### Third-Party Risk

- New third-party dependencies should be justified, limited, and reviewed.
- Prefer first-party Nexus packages and existing project tooling over new external libraries when possible.

## Environment And Secret Handling

### Environment Files

- `.env.example` files should document variable names, shape, and defaults only.
- Real secrets must never be committed.
- Example files should be sufficient to understand configuration requirements without revealing credentials.

### Staging Variables

At minimum, staging security-sensitive configuration must explicitly cover:

- `APP_KEY`
- `JWT_SECRET`
- database credentials
- storage credentials
- `NEXT_PUBLIC_API_URL`
- `NEXT_PUBLIC_USE_MOCKS=false`
- quote-intelligence mode
- CORS origins

### Browser-Facing Environment Values

- Only expose values intended for the browser.
- Treat every `NEXT_PUBLIC_*` value as public by definition.
- Do not place secret or privileged configuration in browser-visible variables.

## HTTP And Browser Security

### CORS

- The API must allow only the intended WEB origin(s).
- Credentialed auth traffic must be explicit and controlled.
- CORS settings should match the deployed WEB and API origins documented in the runbook.

### Cookies and Sessions

- Session and auth behavior must be compatible with the supported alpha flow.
- Secure session handling must be preserved in staging and production-like environments.
- Authentication state should not be leaked across tenants or browser contexts.

### Headers

- Security-related headers should be configured for the deployed app where applicable.
- Sensitive responses should not be cached unintentionally.
- Browser-facing pages should avoid exposing stack traces, debug metadata, or internal tokens.

## Tenant Data Protection

- Buyer data, vendor data, quote data, and decision-trail data are tenant-owned assets.
- Tenant checks must be enforced on every read/write path.
- Release evidence should not contain another tenant's data.
- Cross-tenant probing must not reveal whether a resource exists.

## Alpha Release Security Rules

Alpha release evidence is invalid if any of the following are true:

- live mode silently falls back to seed data
- tenant isolation is weakened
- auth or MFA behavior is inconsistent across API and WEB
- quote files are stored or processed without explicit control
- unsupported surfaces are exposed as if production-ready
- secrets or privileged values appear in docs, logs, or client-visible output

## Review Requirements

Before merging security-sensitive work, confirm:

- the relevant layer boundary is respected
- tenant and auth behavior are reviewed
- file and storage handling is safe
- live-mode behavior is honest
- logs and errors are sanitized
- release evidence does not leak secrets or tenant data

## Incident And Response Expectations

If a security issue is discovered:

1. Contain the issue by limiting exposure or disabling the affected path.
2. Record the issue in the current release or maintenance record.
3. Patch the root cause in the appropriate layer.
4. Re-run the relevant tests and release checks.
5. Update the canonical docs if the security posture or operational contract changed.

## Enforcement

This baseline is mandatory for Atomy-Q work.

If a change violates this document, the change is not releasable until the issue is corrected or explicitly accepted by the release owner with a documented rationale.
