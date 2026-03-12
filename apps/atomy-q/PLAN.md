# Atomy-Q Frontend Stack Plan

This document describes the recommended frontend stack and implementation plan for Atomy-Q, given a **Laravel API backend** (203 endpoints, JWT auth, multi-tenant). It serves as the single reference for frontend architecture and rollout order.

---

## 1. Stack Overview

| Layer | Choice | Purpose |
|-------|--------|---------|
| **Framework** | Next.js 16 (App Router) | Main app host, optional SSR, single deploy surface |
| **API client** | OpenAPI-generated client | Type-safe calls aligned with API contract |
| **Server state** | TanStack Query | Caching, loading, mutations, optimistic updates |
| **Auth** | JWT + refresh in client + auth context/store | Token handling, tenant, redirects |
| **Forms** | React Hook Form + Zod | Validation and DX aligned with API |
| **UI** | Radix + Tailwind | Accessibility and styling |
| **Design system** | Design-System-v2 as single source | One component set (package or copy) |
| **Client state** | Zustand (or React context) | Sidebar, modals, theme |
| **Tables** | TanStack Table | RFQ list, quote intake, data views |
| **Toasts** | Sonner | Mutation and error feedback |
| **Charts** | Recharts (optional) | Dashboard and analytics |

---

## 2. Rationale by Layer

### Framework: Next.js (App Router)

- **quote-comparison-web** is already Next.js 16; no framework change.
- Fits dashboard-style SaaS: optional SSR for landing/auth, static app shell, single deploy.
- Allows future BFF route or proxy if needed.

### API Layer: OpenAPI Client + TanStack Query

- **OpenAPI client:** Generate TypeScript client from Laravel API (OpenAPI/Swagger). Use for all `/api/v1/*` calls so the 203-endpoint surface stays type-safe and consistent.
- **TanStack Query:** Use for all server state (RFQs, submissions, dashboard KPIs, etc.): caching, loading/error, refetch, optimistic updates.
- Single pattern for “call API → show in UI” and “mutate → invalidate/optimistic.”

**Suggested tooling:** `openapi-fetch` or `@hey-api/openapi-ts` for the client; `@tanstack/react-query` for data fetching.

### Auth: JWT + Refresh

- Store **access token** in memory (or short-lived cookie if API supports it); **refresh token** in httpOnly cookie or secure storage.
- On load: call `GET /api/v1/me` (or equivalent); on 401, attempt refresh then redirect to sign-in.
- Use a small **auth context or store** (user, tenant, login/logout) that provides the token to the API client and drives redirects.
- Aligns with existing API JWT design; no heavy auth SDK unless OIDC/SSO is added later.

### Forms: React Hook Form + Zod

- **React Hook Form** for performance on large forms (Create RFQ, line items, settings).
- **Zod** for schemas and validation; align with or derive from API DTOs so client validation matches backend.

### UI: Radix + Tailwind + One Design System

- **Radix UI** for accessible primitives (modals, dropdowns, tabs, etc.).
- **Tailwind** for layout and styling.
- Treat **Design-System-v2** (or the chosen canonical design app under `apps/atomy-q/`) as the single source: either publish as an internal package consumed by `quote-comparison-web`, or copy canonical components into the main app and keep Design-System-v2 as the showcase.
- Prevents drift between canary apps and production UI.

### Client State: Zustand (or React Context)

- Use **Zustand** (or a small React context) for UI state only: sidebar open/closed, active modal, theme. Optionally current tenant if needed outside auth.
- Keep **server state** exclusively in TanStack Query.

### Tables: TanStack Table

- Keep **@tanstack/react-table** for RFQ list, quote intake tables, approval queue, etc., with existing config-driven columns and `DataTableView`-style abstraction.

### Feedback and Charts

- **Toasts:** Sonner for mutation and error feedback.
- **Charts:** Recharts for dashboard (spend trend, vendor scores) if already used in Design-System-v2; otherwise any small chart library is fine.

### Routing and Permissions

- Use **Next.js App Router** and layout-based routes for main areas (dashboard, RFQs, approvals, settings).
- Add **role/permission checks** in layout or page components (or a thin wrapper) that read from the auth store and redirect or show 403 when the user lacks permission. Optionally mirror the API permission model so the frontend hides UI the user cannot use.

---

## 3. Implementation Order

1. **OpenAPI + API client**  
   Add OpenAPI spec to the Laravel API; generate the TypeScript client and wire it to use the JWT from the auth flow.

2. **TanStack Query + auth**  
   Add TanStack Query and an auth provider (login, logout, `/me`, refresh). Protect routes and attach the token to the API client.

3. **Design system consolidation**  
   Choose one design system source (e.g. Design-System-v2) and consume it from `quote-comparison-web` (package or copy).

4. **Forms**  
   Introduce React Hook Form + Zod on the next major form (e.g. Create RFQ or settings).

5. **Client state**  
   Add Zustand only when needed (e.g. sidebar state, global modal state).

---

## 3.1 OpenAPI Ownership & Update Workflow

- **Source of truth**: Laravel API repo generates the OpenAPI spec (`/openapi.json` or `/openapi.yaml`).
- **Ownership**: Backend team owns schema updates; frontend consumes only the generated client.
- **Generation**: Add a CI step that regenerates the OpenAPI spec on API changes and fails if the spec is stale.
- **Client update**: Frontend runs a single `generate-client` script that pulls the spec and regenerates types.

## 3.2 JWT Storage & Refresh Strategy

- **Access token**: keep in memory only.
- **Refresh token**: store in httpOnly cookie set by the API.
- **Refresh flow**: on 401, attempt refresh once and retry the request; if it fails, clear auth state and redirect to sign-in.
- **Cross-tab sync**: use `BroadcastChannel` or `localStorage` events to propagate logout and token refresh across tabs.

## 3.3 Multi-Tenant UX & Guarding

- **Tenant context**: store `tenantId` in auth state; attach it to all API calls if required.
- **Tenant switching**: explicit selector or "current tenant" control in the app shell.
- **Guarding**: route guards check both auth and tenant presence; redirect to tenant selection if missing.

## 3.4 API Error Normalization

- Define a single error contract in the API client (e.g., `{ code, message, details }`).
- Create a shared error parser that maps HTTP errors to UI-safe error objects.
- Require all TanStack Query hooks to use the shared error parser.

## 3.5 Frontend Testing Strategy

- **Unit**: component tests for critical UI logic (forms, tables).
- **Integration**: query hooks with MSW-based API mocks using the OpenAPI schema.
- **E2E**: minimal Playwright flows (login, RFQ list, create RFQ, approval flow).

## 3.6 Mock Data & Dev Fixtures

- Maintain a mock data pack aligned to the OpenAPI schema (MSW + JSON fixtures).
- Ensure fixtures include multiple tenants and roles to exercise permissions and data isolation.

## 4. Related Documents

- **API contract:** `apps/atomy-q/API_ENDPOINTS.md`
- **API implementation:** `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- **Frontend blueprint:** `apps/atomy-q/QUOTE_COMPARISON_FRONTEND_BLUEPRINT_v2.md` (if present)
- **Architecture:** `docs/project/ARCHITECTURE.md`

---

*Last updated: 2026-03-12*
