# Atomy-Q Coding Standards

## Changelog

| Date | Version | Change |
|---|---|---|
| 2026-04-18 | 1.0 | Initial Atomy-Q coding standards for the API, WEB, and Nexus dependency stack. |

## Purpose

This document defines the coding standards for the Atomy-Q SaaS application. It is the canonical reference for implementation style, layering, naming, error handling, and change hygiene across:

- `apps/atomy-q/API` - Laravel 12 backend
- `apps/atomy-q/WEB` - Next.js 16 frontend
- Nexus Layer 1 packages under `packages/`
- Nexus Layer 2 orchestrators under `orchestrators/`
- Nexus Layer 3 adapters under `adapters/Laravel/`

These standards apply to all production code and all durable support code that affects Atomy-Q behavior.

## Scope

These standards govern:

- PHP 8.3 code in the API and Nexus backend layers
- TypeScript/React/Next.js code in the WEB app
- Laravel adapters and application integration code
- validation, exceptions, data contracts, and tenant scoping
- naming, imports, file structure, and documentation hygiene

They do not replace:

- the Atomy-Q alpha release plan and release-management docs
- the QA/testing strategy document
- the security baseline document
- the project-wide Nexus architecture rules in `docs/project/ARCHITECTURE.md`

If a rule here conflicts with the broader Nexus architecture guide, the broader guide wins unless this Atomy-Q standard is stricter for alpha safety.

## Core Principles

### 1. Prefer explicit contracts over implicit behavior

- Depend on interfaces, not concrete implementations.
- Name contracts for the responsibility they represent, not for the current framework class that happens to implement them.
- Do not hide business logic inside controllers, React components, or ad hoc helper functions.

### 2. Keep the three layers isolated

- Layer 1 packages remain pure PHP with no framework dependency.
- Layer 2 orchestrators coordinate workflows and own their own interfaces.
- Layer 3 adapters bind the app to Laravel, Next.js, storage, queues, and persistence.

### 3. Make alpha behavior honest

- Live mode must fail loudly rather than fall back silently to seed or mock data.
- Unsupported flows must be hidden or explicitly deferred rather than simulated with fake success.
- If a surface is not alpha-ready, it must be obviously unavailable.

### 4. Protect tenant boundaries

- Every tenant-scoped read or write must carry `tenant_id`.
- Wrong-tenant existence must not be leaked through status codes, messages, or timing behavior where that can be avoided.
- `404` is preferred over `403` when the resource exists but belongs to another tenant and the existence should not be exposed.

### 5. Prefer immutable and deterministic code

- Use `final readonly class` where the object is a service, boundary, or value holder that should not mutate.
- Keep pure transformations deterministic.
- Avoid hidden global state, implicit caching, and side effects that are not obvious from the method name.

## Layer Rules

### Layer 1: `packages/`

Layer 1 is the domain truth for Atomy-Q and all other Nexus consumers.

Rules:

- No `Illuminate\*`, no `Symfony\*`, and no framework-specific dependencies.
- No database calls, request objects, HTTP resources, or application service providers.
- All external dependencies must be described through interfaces in `src/Contracts/`.
- Value objects must be immutable.
- Exceptions must be domain-specific.
- Public APIs should be stable and predictable.
- No synthetic IDs, placeholder return values, or fake success results.

Examples of allowed Layer 1 concerns:

- RFQ status policies
- quote and award value objects
- tenant identifiers and tenant context objects
- idempotency record semantics
- policy evaluation primitives

Examples of disallowed Layer 1 concerns:

- Eloquent models
- HTTP validation
- queue jobs
- filesystem adapters
- feature flags tied directly to Laravel config

### Layer 2: `orchestrators/`

Layer 2 coordinates workflows across packages and keeps cross-package logic out of controllers.

Rules:

- Remain framework-agnostic.
- Own interfaces for orchestration boundaries.
- Keep services stateless where possible.
- Compose packages through contracts, not concrete adapter details.
- Do not import Layer 3 classes.
- Do not bypass the orchestrator layer from API controllers when a workflow boundary exists.

Examples of Layer 2 concerns:

- RFQ lifecycle coordination
- quote ingestion workflows
- normalization readiness and conflict handling
- award orchestration
- tenant onboarding orchestration

### Layer 3: `adapters/Laravel/` and app-local integration

Layer 3 binds the application to Laravel, persistence, and runtime concerns.

Rules:

- Controllers stay thin and translate HTTP into application calls.
- Eloquent models, migrations, middleware, jobs, providers, and queue integrations belong here.
- Use Layer 1 and Layer 2 contracts through their defined interfaces.
- Handle framework details here, not in domain code.
- Keep API responses explicit and contract-aligned.

For the WEB app, Layer 3-equivalent concerns include:

- route pages
- client components
- query hooks
- generated API client wrappers
- UI state management
- live-mode error handling

## PHP Standards

### File headers and typing

- Every PHP file must begin with `declare(strict_types=1);`.
- Every class, function, and method should have the narrowest practical types.
- Avoid `mixed`, `object`, `?object`, and broad unions unless the contract genuinely requires them.

### Class design

- Prefer `final` classes by default.
- Prefer `final readonly class` for services and immutable holders.
- Use `readonly` promoted properties for immutable dependencies and values.
- Do not introduce mutable state unless the state is essential to the responsibility.

### Imports and namespaces

- Use explicit `use` imports or fully qualified class names.
- Group imports in this order:
  - PHP built-ins
  - PSR interfaces
  - Nexus packages
  - project-local classes
- Sort imports alphabetically within each group.
- Avoid unused imports, aliasing without purpose, and overly long import blocks created by copy-paste.

### Interfaces and dependency injection

- Depend on interfaces whenever a choice exists.
- Constructor injection is mandatory for services and orchestrators.
- Do not use service locators or hidden static dependencies.
- Do not inject generic `object` dependencies.

### Exceptions

- Throw domain-specific exceptions for business failures.
- Do not return synthetic success values to represent failure.
- Do not expose raw infrastructure exceptions to callers when a domain exception can describe the problem better.

### Collections and arrays

- Validate array shape before reading nested data.
- Guard against empty arrays, missing offsets, and divide-by-zero conditions.
- Normalize array indexes before use when order matters.
- Prefer small DTOs and value objects over ad hoc associative arrays where the shape is stable.

### Time, money, and identifiers

- Use explicit timezone handling.
- Treat money as money, not floating-point convenience.
- Use canonical identifier formats consistently across API, WEB, and docs.
- Do not invent IDs or use timestamp-based placeholders when a real persisted identifier should exist.

## Laravel API Standards

### Controllers

- Controllers translate HTTP into application use cases.
- They should validate, authorize, call the correct service/orchestrator, and return a response.
- They should not contain business workflow logic.
- They should not decide tenant scope casually or duplicate logic from services.

### Validation

- Use request validation before touching business logic.
- Use validated data, not raw request input, for write paths.
- Explicitly validate nullability, required fields, numeric bounds, string lengths, and allowed enum values.
- For tenant-scoped resources, validate in a way that preserves not-found semantics when appropriate.

### Persistence

- All tenant-scoped queries must include tenant scoping.
- Do not load by primary key and then check tenant ownership in a second step when a tenant-scoped query can enforce the boundary directly.
- Use transactional boundaries for multi-write workflows.
- Keep persistence logic in adapters or repository implementations, not in controllers.

### API responses

- Return explicit success and error shapes.
- Do not use synthetic success payloads.
- If a surface is deferred, say so clearly and keep the behavior consistent across UI and API.

### Laravel-specific conventions for Atomy-Q

- Auth, tenant onboarding, RFQ lifecycle, quote intake, normalization, comparison, awards, and minimal users/roles are alpha-critical.
- Reports, integrations, billing, risk, negotiations, and similar surfaces must remain deferred unless the alpha scope is updated.
- The API should never imply a capability the WEB cannot exercise in live mode.

## WEB Standards

### Component boundaries

- Keep pages thin and compose them from focused components and hooks.
- Move data fetching into hooks.
- Move page-specific state into the smallest practical scope.
- Avoid mixing transport logic, presentation, and persistence assumptions in the same component.

### React and Next.js

- Use client components only when browser state, query hooks, or interactive UI require them.
- Prefer straightforward component composition over clever abstractions.
- Keep live-mode and mock-mode behavior separate and explicit.
- Never let mock data accidentally become the hidden fallback for live validation.

### Hooks

- Hooks should normalize API payloads at the boundary.
- Hooks must reject malformed payloads instead of coercing them into seemingly valid data.
- If a hook supports mock mode, mock handling must be explicit and local-only.
- Live-mode transport errors must remain visible to the caller.

### State and data flow

- TanStack Query should own server-state fetching and caching.
- Local component state should only hold UI concerns.
- Do not mirror server state unnecessarily into multiple client stores.
- Keep the data source obvious in the component tree.

### Accessibility and UX

- Interactive controls must be keyboard accessible.
- Loading, empty, unavailable, and error states must be visually distinct.
- Do not render a blank or fake-success view when live data is unavailable.
- Respect mobile layouts and responsive constraints.

## Atomy-Q Domain Conventions

### Tenant scope

- Every API and persistence path that touches buyer data must remain tenant-scoped.
- Never rely on the user interface alone to enforce tenant boundaries.

### RFQ and line items

- RFQ line items should be deterministic in ordering, labeling, and totals.
- Live line-item views must use live data when live mode is active.
- Mock line-item data may exist only inside explicit mock-mode branches.

### Quote intake and normalization

- Quote intake and normalization should fail loudly on malformed data.
- Do not silently coerce broken payloads into usable records.
- Preserve evidence metadata when writing normalized results.

### Comparison and awards

- Comparison finalization must not claim readiness if the gating data is missing.
- Award flows must preserve the decision trail and signoff evidence.

### Users and roles

- The minimal admin surface should stay intentionally small during alpha.
- Invite/suspend/reactivate flows must remain tenant-scoped.
- Do not expose unsupported role-selection complexity until it is productized.

## Naming Conventions

- Use descriptive names that match the domain language.
- Name things after the business concept, not after the implementation accident.
- Keep filenames predictable and topic-oriented.

Preferred patterns:

- `*Service` for domain services
- `*Manager` only when the object truly manages a lifecycle
- `*Interface` for contracts
- `*Dto` or `*Data` for typed payload objects
- `*Exception` for domain failures
- `use-*` for React hooks

Avoid:

- vague names like `Helper`, `Utils`, or `Common`
- overloaded names that hide multiple responsibilities
- file names that only make sense with the surrounding task history

## Documentation and Change Hygiene

- If behavior changes, update the relevant docs in `apps/atomy-q/docs` in the same change.
- Durable decisions belong in the canonical product, domain, or engineering docs, not only in task specs.
- Update `IMPLEMENTATION_SUMMARY.md` files when code changes alter behavior or accepted constraints.
- Release-checklist evidence must reflect the current state of the system, not historical assumptions.
- New standards or changes to standards must append a changelog entry at the top of the relevant document.

## Review Checklist

Before merging code that touches Atomy-Q behavior, confirm:

- strict typing is in place where required
- Layer 1 and Layer 2 remain framework-free
- tenant scoping is enforced on reads and writes
- live mode fails loudly when data is absent or malformed
- mock mode is explicit and local-only
- exceptions are domain-specific
- imports are clean and grouped
- naming reflects the business domain
- docs are updated when durable decisions change

## Enforcement

These standards are mandatory for new code and for meaningful changes to existing code.

If a change intentionally deviates from this document, the deviation must be:

- documented
- justified
- reviewed
- time-bounded
- reflected in the relevant docs and implementation summary

If the deviation is temporary, the follow-up work must be explicit and tracked.
