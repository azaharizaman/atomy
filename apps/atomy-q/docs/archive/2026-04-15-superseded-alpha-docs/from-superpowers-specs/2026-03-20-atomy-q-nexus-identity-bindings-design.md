# Design: Atomy-Q API — Nexus Identity container bindings

**Date:** 2026-03-20  
**Status:** Draft (brainstorm → review → plan → implement)  
**Goal:** Make `UserAuthenticationCoordinatorInterface` resolvable so `POST /api/v1/auth/sso` works and feature tests can run without skipping, without breaking the existing JWT + Eloquent password login path.

## Context

- `nexus/laravel-identity-adapter` registers `IdentityAdapterServiceProvider`, which wires `UserAuthenticationCoordinator`, `OidcSsoProviderAdapter`, and `IdentityOperationsAdapter`.
- **App-owned contracts** (no default implementation in the adapter): `UserPersistInterface`, `UserQueryInterface`, and several others required by `IdentityOperationsAdapter`’s constructor.
- Laravel builds the **full** coordinator graph when `app(UserAuthenticationCoordinatorInterface::class)` runs: `UserAuthenticationService` requires `AuthenticatorInterface`, which is aliased to `IdentityOperationsAdapter`. So **SSO init/callback still forces resolution of the entire adapter**, not only `OidcSsoProviderAdapter`.
- Atomy-Q already depends on `nexus/laravel-notifier-adapter` (likely binds `NotificationManagerInterface`). It depends on `nexus/audit-logger` (L1) but **may not** include `nexus/laravel-auditlogger-adapter` — verify `AuditLogRepositoryInterface` is bound at runtime.
- `CacheRepositoryInterface` and `TransactionManagerInterface` are registered by the Identity adapter provider. `Queue` is Laravel core.

## Non-goals (for first slice)

- Replacing `AuthController` password login with `coordinator->authenticate()` (optional later alignment).
- Full MFA, session store, and role/permission RBAC parity with Nexus docs — stub or minimal implementations acceptable if SSO path does not invoke them.

## Approaches

### A — Full production bindings

Implement Eloquent-backed (or dedicated repository) classes for **every** constructor dependency of `IdentityOperationsAdapter`, aligned with Nexus interface semantics and tenant isolation.

- **Pros:** Coordinator behaves as designed; future UserController / admin flows reuse the same stack.  
- **Cons:** Large effort; touches MFA, sessions, tokens (Identity vs app JWT), audit, notifications.

### B — Phased: “SSO graph green” (recommended first)

1. **Inventory** — List every interface Laravel must resolve to instantiate `IdentityOperationsAdapter` (see adapter constructor). For each, mark: already bound by a discovered package vs missing.
2. **Add missing Laravel adapters** — e.g. require `nexus/laravel-auditlogger-adapter` if `AuditLogRepositoryInterface` is unbound (resolve package name mismatch with `nexus/audit-logger` if any).
3. **Implement L3 in `apps/atomy-q/API`** (Layer 3 only):
   - `AtomyUserQuery` → `UserQueryInterface` (map `App\Models\User` to `UserInterface` / DTOs expected by callers; tenant-scoped queries).
   - `AtomyUserPersist` → `UserPersistInterface` (persist updates aligned with `users` table; role/permission methods no-op or map to future tables with clear `RuntimeException` if invoked before schema exists).
   - `LaravelPasswordHasher` → `PasswordHasherInterface` (delegate to `Hash::make` / `Hash::check`).
   - `UserAuthenticatorInterface` — implement using existing password verification pattern + `UserQueryInterface` (or wrap Laravel guard pattern if contract allows).
   - **Identity** `TokenManagerInterface` + `SessionManagerInterface` — either thin JWT/session wrappers consistent with app JWT config, or in-memory/no-op implementations **documented as SSO-only** until unified token story exists.
   - **MFA** — `MfaEnrollmentServiceInterface` / `MfaVerificationServiceInterface`: stub implementations returning “not supported” or no-op, unless OIDC path calls them (verify in `OidcSsoProviderAdapter` / SSO service).
4. **Register** all of the above in `AppServiceProvider` or a dedicated `IdentityAtomyServiceProvider` (clearer).
5. **Tests** — Remove skip guards in `AuthTest` SSO tests; add a unit test for `UserQueryInterface` tenant scoping.

- **Pros:** Unblocks SSO and coordinator; incremental; matches AGENTS.md Layer 3 boundary.  
- **Cons:** Some methods are stubs; must document which API routes must not call until fleshed out.

### C — Narrow coordinator for Atomy (package change)

Split or fork `IdentityAdapterServiceProvider` so a “lite” coordinator for SSO does not pull `IdentityOperationsAdapter`.  

- **Pros:** Smallest app binding surface.  
- **Cons:** Violates “extend adapter, don’t fork” preference; upstream merge burden; not recommended unless B proves infeasible.

## Recommendation

**Approach B** in two milestones:

| Milestone | Outcome |
|-----------|---------|
| **B1** | Container resolves coordinator; SSO tests pass; login still uses existing `AuthController` JWT path. |
| **B2** | Harden `UserPersist` / `UserQuery` for roles, failed-login counters, `updateLastLogin`, etc., as product requires; replace stubs with real MFA/session when needed. |

## Architecture sketch

```
AppServiceProvider (or IdentityAtomyServiceProvider)
  bind UserQueryInterface      → AtomyUserQuery
  bind UserPersistInterface    → AtomyUserPersist
  bind PasswordHasherInterface → LaravelPasswordHasher
  bind UserAuthenticatorInterface → AtomyUserAuthenticator
  bind Identity TokenManagerInterface → AtomyIdentityTokenBridge (or stub)
  bind SessionManagerInterface → AtomySessionManagerStub
  bind MfaEnrollmentServiceInterface → StubMfaEnrollment
  bind MfaVerificationServiceInterface → StubMfaVerification
  (+ verify NotificationManagerInterface, AuditLogRepositoryInterface via packages)
```

**Tenant rule:** All queries/filters use `tenant_id`; cross-tenant ID probes return not-found semantics consistent with existing API policy (404, not 403).

## Testing

- `AuthTest` SSO tests enabled when `UserPersistInterface` binds.
- New `Tests\Unit\Identity\AtomyUserQueryTest` (tenant isolation).
- Optional: contract test that `app(UserAuthenticationCoordinatorInterface::class)` resolves without exception.

## Risks

- **Dual token systems:** App JWT (`JwtService`) vs Identity `TokenManagerInterface` — document which path owns refresh for coordinator-based flows vs password login.
- **Schema drift:** `UserPersistInterface` methods (roles, lockout) may not map 1:1 to `users` columns — implement minimal mapping or throw domain exceptions for unimplemented operations.

## Open decision (product)

See question in brainstorm thread: prioritize **B1 only** vs **B1 + aligning password login through coordinator** in the same phase.
