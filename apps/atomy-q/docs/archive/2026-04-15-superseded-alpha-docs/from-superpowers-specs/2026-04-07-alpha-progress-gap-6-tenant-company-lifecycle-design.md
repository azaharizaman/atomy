# Alpha Progress Gap 6 Tenant / Company Lifecycle Design

**Date:** 2026-04-07
**Status:** Draft
**Scope:** Atomy-Q alpha gap 6, minimal first-class tenant/company lifecycle

## Context

The alpha progress analysis identifies tenant/company lifecycle as the remaining platform-level gap for Alpha. The app already uses `tenant_id` as the isolation key everywhere, but the actual tenant record, creation path, and onboarding bootstrap are not yet first-class.

This design deliberately keeps the scope narrow:

- ship one honest onboarding path that creates a company and its first owner
- persist a real tenant row instead of a stub
- bootstrap the owner session immediately after onboarding
- keep the app, orchestrators, and packages cleanly separated
- preserve the richer tenant administration roadmap for later

Terminology:

- **Company** is the user-facing product term.
- **Tenant** is the persisted platform primitive and the term used by the Nexus packages and database schema.

## Problem

Atomy-Q can authenticate users and apply tenant-scoped queries, but it cannot yet create the tenant that those users belong to. That leaves the system with an isolation key but no first-class lifecycle for the account that owns the data.

For Alpha, this creates three issues:

1. The platform cannot onboard a new customer without seed data or manual database work.
2. The existing `Nexus\Tenant` package is not being exercised through a real SaaS lifecycle.
3. The UX cannot explain a clean company creation flow if the backend still treats tenant creation as an internal artifact.

## Goals

- Add a minimal, production-shaped company creation flow for Alpha.
- Persist a real tenant record in the database.
- Create the first owner user in the same onboarding transaction.
- Bootstrap the owner session/JWT immediately after successful onboarding.
- Keep tenant lifecycle logic in `Nexus\Tenant`, orchestration in Layer 2, and HTTP/UI wiring in Layer 3.
- Record the richer post-alpha tenant administration work so it is not lost.

## Non-goals

- Full tenant administration console
- Tenant rename flows
- Deactivate/reactivate/archive UI
- Inviting additional admins during Alpha
- Parent/child tenant hierarchy UI
- Custom domain/subdomain management
- Multi-tenant switching UX
- Billing plan management

## Options Considered

### Option 1: Public onboarding endpoint for company creation

Expose a public `POST /auth/register-company` flow that creates the tenant and owner user, then returns JWT tokens.

Pros:

- Best fit for Alpha self-service onboarding
- Uses a single end-to-end path
- Demonstrates the first-party Nexus packages in a real customer flow
- Keeps the rest of tenant administration out of scope

Cons:

- Requires new request validation, orchestration, and adapter wiring
- Needs careful transaction handling so tenant and user creation stay atomic

### Option 2: Admin-only tenant creation endpoint

Require an existing tenant admin to create new tenants from inside the app.

Pros:

- Simpler permissions model
- Fewer public-facing edge cases

Cons:

- Does not solve the Alpha onboarding problem
- Still leaves first customer creation dependent on seed/manual setup

### Option 3: Full tenant administration module now

Build create, rename, deactivate, archive, invite, and hierarchy management together.

Pros:

- Future-proof in one pass

Cons:

- Over-scopes Alpha
- Increases coupling between onboarding, admin UX, and lifecycle rules
- Risks delaying the release while the platform still lacks a basic creation path

## Recommendation

Choose Option 1.

Alpha should ship a single trustworthy company creation workflow and no more. That is enough to prove the platform can onboard a real customer while still leaving a documented path for the broader tenant lifecycle.

## Proposed Architecture

### Layer 1: `Nexus\Tenant`

`Nexus\Tenant` remains the source of truth for tenant lifecycle semantics.

It should own:

- tenant identity and status rules
- tenant validation
- tenant creation/update persistence contracts
- tenant events
- tenant lookup semantics used by onboarding and later admin flows

For this gap, Layer 1 should be extended only where the Alpha path needs it. The likely addition is tenant name uniqueness validation so the onboarding flow can reject duplicate company names before persistence.

Layer 1 must not know about:

- HTTP requests
- Laravel controllers
- JWT/session issuance
- user onboarding details

### Layer 2: Tenant and Identity orchestration

Layer 2 should coordinate the onboarding workflow across packages.

The orchestration boundary should:

- generate or validate the tenant code
- create the tenant through `Nexus\Tenant`
- create the first owner user through the Identity onboarding boundary
- bind the owner to the new tenant
- surface a compact onboarding result to the API layer

If the existing orchestrator contracts are too broad for Alpha, add a smaller onboarding facade in Layer 2 rather than pushing coordination into the controller. The API should call one orchestrator method, not assemble the workflow itself.

### Layer 3: Atomy-Q API and WEB

The Laravel API should own:

- HTTP request validation
- route wiring
- database migrations and Eloquent adapters
- transaction boundaries
- JWT issuance
- response shaping

The WEB app should own:

- the onboarding screen
- form validation and loading/error states
- calling the new onboarding endpoint
- storing the issued auth tokens
- redirecting into the authenticated dashboard

### External dependencies

Keep framework-specific mechanics in the app layer:

- Laravel DB transactions
- Eloquent models and migrations
- JWT token issuance
- React Hook Form / client-side form state
- Next.js routing and redirects

## Alpha Scope

### 1. Company creation flow

Add a public onboarding endpoint for a first-time company owner.

Recommended shape:

- `POST /auth/register-company`
- request fields:
  - `company_name`
  - `owner_name`
  - `owner_email`
  - `password`
  - optional `timezone`
  - optional `locale`
  - optional `currency`

On success, the flow should:

1. validate the input
2. create a tenant row with default lifecycle values
3. create the owner user on that tenant
4. issue access and refresh tokens
5. return the same auth payload shape used by login, so the WEB app can reuse its session bootstrap logic

### 2. Minimal tenant record

The tenant table should be real and support the minimum Alpha lifecycle:

- ULID primary key
- code
- name
- email
- status
- timezone
- locale
- currency
- metadata
- timestamps

The `code` can remain internal, but it must be generated deterministically enough for validation and uniqueness checks.

### 3. Atomic onboarding

Tenant creation and owner creation must be atomic.

If tenant insertion succeeds but user creation fails, the whole operation should roll back. The API must not return a half-created company.

### 4. Honest failure semantics

Failures should be explicit and non-synthetic:

- duplicate company name or code: `422`
- validation errors: `422`
- tenant/user creation collision: domain-specific error mapped to a safe HTTP response
- unexpected infrastructure failure: `500`

No fake IDs, no placeholder success payloads.

## Deferred Nice-to-Haves

These are intentionally out of Alpha scope, but they should stay documented in this design and in the implementation summaries:

- tenant rename
- tenant deactivate/reactivate
- tenant archive/delete
- invite additional admins
- invite additional members
- parent-child tenant hierarchy
- custom domain and subdomain management
- tenant switching for users who belong to multiple tenants
- tenant billing and plan administration

Suggested backlog note:

- keep these in a “post-alpha tenant lifecycle” section in the implementation summary or progress analysis so they remain visible without blocking the Alpha onboarding path

## Testing Strategy

The implementation should be test-driven in three layers:

### Package tests

- tenant validation rejects duplicate company names if that rule is added
- tenant lifecycle service creates tenants with the correct status and metadata defaults

### API tests

- onboarding succeeds and returns auth tokens
- onboarding creates both tenant and owner user records
- duplicate company names or emails fail cleanly
- failed user creation rolls back tenant creation

### WEB tests

- onboarding page submits the correct payload
- validation errors render inline
- successful onboarding stores tokens and redirects into the dashboard

## Success Criteria

This gap is closed when all of the following are true:

- a new company can be created from the UI or API without seed data
- the company is stored as a real tenant row
- the first owner user is created in the same operation
- the owner receives a valid authenticated session immediately
- the app continues to use `tenant_id` as the isolation key everywhere else
- the richer tenant admin lifecycle remains documented for later work

