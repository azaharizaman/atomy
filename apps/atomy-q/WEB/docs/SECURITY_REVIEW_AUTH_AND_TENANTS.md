# WEB App Security Review: Authentication, Session & Cross-Tenant

**Date:** 2026-03-14  
**Scope:** Atomy-Q WEB app authentication, user session, and multi-tenant data isolation.

---

## 1. Executive Summary

- **Backend (API):** Authentication and tenant context are correctly derived from the JWT. Protected routes use `jwt.auth` and `tenant` middleware; `auth_tenant_id` and `auth_user_id` come only from the token, not from client input. **No cross-tenant leakage via client-supplied tenant ID on protected endpoints.**
- **Frontend (WEB):** Two main gaps: (1) **No route-level protection** — unauthenticated users can open dashboard URLs and see layout until API calls return 401; (2) **Refresh flow broken** — `refresh_token` is never stored or sent, so session restore on reload fails and 401 retry cannot refresh.
- **Cross-tenant:** Login requires tenant_id + email + password; tokens are bound to (user, tenant). Several API controllers still have **TODO: tenant scoping** on stub or future implementations (VendorController, some RfqController methods); when those are implemented, they must use `$this->tenantId($request)` only.

---

## 2. Authentication Flow (Current)

### 2.1 Login (WEB → API)

- **WEB:** `POST /api/v1/auth/login` with `{ email, password }`; tenant context comes from the API response (`user.tenantId`) and JWT claims after authentication.
- **API:** Validates input; looks up user by `tenant_id` + `email`; checks password; issues access + refresh JWTs with `sub=userId`, `tenant_id=tenantId`, `type=access|refresh`. Returns `access_token`, `refresh_token`, `user` (id, email, name, role, tenantId).
- **WEB:** Stores `access_token` and `user` in Zustand; **does not store `refresh_token`** (see Issue 3 below).

**Verdict:** Login is correct. Tenant is required and used only for lookup; tokens are bound to that tenant. No privilege escalation via login.

### 2.2 Protected Requests (WEB → API)

- **WEB:** Axios interceptor adds `Authorization: Bearer <access_token>` from store.
- **API:** `JwtAuthenticate` middleware: reads Bearer token, decodes JWT, sets `auth_user_id` and `auth_tenant_id` from payload. `TenantContext` middleware: returns 403 if `auth_tenant_id` is missing.
- **Controllers:** Use `$this->tenantId($request)` and `$this->userId($request)` from `ExtractsAuthContext` (request attributes only). **Tenant is never read from body/query/header on protected routes.**

**Verdict:** Protected API correctly denies unauthenticated requests (401) and uses tenant from token only. No client-supplied tenant on protected routes.

### 2.3 Session Restore & Refresh (WEB)

- On load, Zustand rehydrates `user` and `isAuthenticated` from storage; **token is not persisted** (by design in partialize).
- **AuthProvider** runs when `isAuthenticated && !token`: calls `POST /auth/refresh` with **no body**.
- **API** expects `refresh_token` in request body; validation fails → 422. So refresh never succeeds; AuthProvider then calls `logout()`.
- **API interceptor** on 401: calls `POST /auth/refresh` with **no body** → same 422; then rejects and store calls `logout()`.

**Verdict:** Session restore and 401-retry refresh are broken because the WEB app never stores or sends `refresh_token`.

---

## 3. Issues & Recommendations

### Issue 1: No route-level protection (WEB) — **High**

**Finding:** There is no Next.js middleware and no guard in the dashboard layout. Unauthenticated users can open `/`, `/rfqs`, etc. They see the dashboard shell until API calls return 401; then the interceptor logs them out but they have already seen the layout.

**Recommendation:** Add a client-side guard in the dashboard layout: if `!isAuthenticated && !isLoading`, redirect to `/login` (and optionally set `?session_expired=1` when coming from a 401). Optionally add Next.js middleware that checks a signed or httpOnly cookie set at login so server can redirect before rendering.

**Status:** Addressed by adding a redirect in the dashboard layout when not authenticated and not loading.

---

### Issue 2: Refresh token not stored or sent (WEB) — **High**

**Finding:** Login response contains `refresh_token`, but the frontend never stores it. `POST /auth/refresh` is called with an empty body; API requires `refresh_token` in the body, so refresh always fails (422).

**Recommendation:**

- Store `refresh_token` in the auth store (or in sessionStorage only). Persist it in the same way as `user`/`isAuthenticated` so that after reload we can call refresh with it.
- On `POST /auth/refresh`, send `{ refresh_token: refreshToken }` in the body.
- For production, consider moving refresh token to an httpOnly cookie set by the API so it is not accessible to JS and is sent automatically with `withCredentials`.

**Status:** Addressed by adding `refreshToken` to the auth store, persisting it (optionally in partialize), and sending it in both AuthProvider init and the API interceptor refresh call.

---

### Issue 3: Mock login and env tenant (WEB) — **Low**

**Finding:** When `NEXT_PUBLIC_USE_MOCKS === 'true'`, the login page can “Use mock account” with `tenantId` from `NEXT_PUBLIC_TENANT_ID` in the mocked user payload (not from a tenant field on the real login form). This is acceptable for dev/demo only; ensure this is disabled or gated in production.

**Recommendation:** Ensure production builds do not set `NEXT_PUBLIC_USE_MOCKS=true` and that production config does not rely on mock tenant IDs for real auth.

---

### Issue 4: API TODOs for tenant scoping — **Medium (when implemented)**

**Finding:**

- **VendorController:** All methods have `// TODO: tenant scoping via $this->tenantId($request)`. Currently return stub/empty data. When wiring to real DB, **must** filter by `$this->tenantId($request)`.
- **RfqController:** `update`, `duplicate`, `saveDraft`, `bulkAction` have the same TODO. Other RfqController methods already use `$this->tenantId($request)` and scope queries by `tenant_id`.

**Recommendation:** Before implementing any of these methods with real data, add tenant filtering first (e.g. `->where('tenant_id', $this->tenantId($request))`). Do not trust any tenant ID from request body or query; use only `$this->tenantId($request)` (from JWT).

---

### Issue 5: Auth layout does not redirect logged-in users (WEB) — **Low**

**Finding:** The `(auth)/layout` wraps login/forgot-password but does not redirect already-logged-in users away from `/login`. So a logged-in user can still see the login page (they can submit and get a new session). Not a security hole but can be confusing.

**Recommendation:** In the login page (or auth layout), if `isAuthenticated`, redirect to `/`.

---

## 4. Cross-Tenant Data Leakage: Assessment

| Area | Risk | Notes |
|------|------|--------|
| Login | Low | User must supply tenant_id + email + password; token is for that tenant only. |
| Protected API | Low | Tenant and user come from JWT only (`auth_tenant_id`, `auth_user_id`). |
| GET /me, /rfqs, /rfqs/:id, etc. | Low | Controllers use `$this->tenantId($request)` and scope by it. |
| VendorController | Medium (future) | Stub today; when implemented, must add tenant filter. |
| RfqController stubs | Medium (future) | Same as above for update/duplicate/saveDraft/bulkAction. |
| Request body/query tenant | None | No protected endpoint uses client-supplied tenant for authorization. |

**Conclusion:** There is no current loophole that allows one tenant to see or modify another tenant’s data via the WEB app or the protected API. The only way to get a token for a tenant is to know credentials for a user in that tenant. Future implementation of VendorController and the RfqController stub methods must enforce tenant scoping via `$this->tenantId($request)`.

---

## 5. Checklist (Post-Review)

- [x] Backend: Tenant and user from JWT only on protected routes.
- [x] Backend: No client-supplied tenant used for authorization.
- [x] WEB: Route protection (redirect when not authenticated) — implemented in dashboard layout (redirect to `/login?redirect=...` when `!isAuthenticated` after load).
- [x] WEB: Store and send refresh_token for refresh and session restore — implemented in store (`refreshToken` + `setTokens`), auth-provider (init refresh with body), api interceptor (401 retry with body).
- [ ] API: When implementing VendorController and RfqController stubs, add tenant scoping (VendorController docblock added).
- [ ] Production: Disable mock auth and avoid default tenant IDs for real users.
