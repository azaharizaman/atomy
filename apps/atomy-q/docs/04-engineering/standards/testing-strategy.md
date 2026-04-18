# Atomy-Q Testing Strategy

## Changelog

| Date | Version | Change |
|---|---|---|
| 2026-04-18 | 1.0 | Initial Atomy-Q testing strategy for API, WEB, docs, release gates, and Nexus layer verification. |

## Purpose

This document defines how Atomy-Q is tested.

The goal is not to maximize test count. The goal is to prove that the buyer-side alpha journey is honest, tenant-safe, contract-aligned, and releasable.

This strategy applies to:

- `apps/atomy-q/API`
- `apps/atomy-q/WEB`
- Nexus Layer 1 packages in `packages/`
- Nexus Layer 2 orchestrators in `orchestrators/`
- Nexus Layer 3 adapters in `adapters/Laravel/`
- release-management and docs changes that affect execution or behavior

## Testing Principles

### 1. Test the boundary where failures matter

Test the point where a change can actually break:

- Layer 1 at the package boundary
- Layer 2 at the orchestration boundary
- Layer 3 at the adapter or HTTP boundary
- WEB hooks at the transport and normalization boundary
- E2E at the user journey boundary

### 2. Prefer honest tests over brittle tests

- Tests should verify behavior, not incidental implementation details.
- Avoid tests that pass only because they mirror current internal structure.
- Do not use seed or mock behavior to fake a live readiness result.

### 3. Keep live and mock evidence separate

- Mock mode is useful for local development and demo behavior.
- Live-mode tests must prove the real transport path and real payload shape.
- A passing mock test does not prove release readiness.

### 4. Make alpha-critical behavior fail loudly

- Live alpha flows must fail on undefined or malformed data rather than coercing it into success.
- Tests must assert that failures are visible, not silently masked.

### 5. Tie tests to change class

- Small cosmetic changes need smaller verification than alpha release gate work.
- Auth, tenant scoping, RFQ, quote intake, comparison, awards, and staging changes require stronger evidence.

## Test Layers

### Unit Tests

Purpose:
- verify pure logic and isolated behavior

Use for:
- Layer 1 package rules
- utility functions
- normalization logic
- small React component logic
- deterministic transformations

Characteristics:
- fast
- no network
- no external services
- narrow in scope

### Integration Tests

Purpose:
- verify interaction between a few units at a stable boundary

Use for:
- repository/adaptor behavior
- controller-to-service interaction
- hook-to-API payload normalization
- orchestrator composition with controlled collaborators

Characteristics:
- may touch database or framework runtime
- should still be deterministic
- should verify contracts rather than internal implementation details

### Feature Tests / API Tests

Purpose:
- verify HTTP endpoints, tenant scoping, validation, and persistence behavior

Use for:
- auth flows
- RFQ lifecycle
- quote intake
- normalization
- comparison
- awards
- minimal users/roles
- staging-related API behavior

Characteristics:
- run through Laravel's testing stack
- should assert response shape, status codes, and side effects
- must reflect tenant isolation where relevant

### WEB Unit / Component Tests

Purpose:
- verify hooks, components, route pages, and state behavior

Use for:
- live-mode hook parsing
- mock-mode fallback behavior
- UI state rendering
- error and unavailable states
- route-level content decisions

Characteristics:
- fast enough for routine local work
- must cover live transport and normalization for hook changes
- should be explicit about mock vs live behavior

### E2E Tests

Purpose:
- verify the user journey through the running WEB and API applications

Use for:
- alpha buyer journey smoke
- compare-to-award end-to-end verification
- staging smoke with `NEXT_PUBLIC_USE_MOCKS=false`
- navigation and deferred-surface checks

Characteristics:
- slower than unit or feature tests
- used for release confidence
- should stay focused on the alpha journey, not every edge case

## Test Scope By Layer

### Layer 1 Package Testing

Every meaningful Layer 1 change should have:

- unit tests for the new or changed contract behavior
- coverage for happy path and relevant edge cases
- tests for tenant-sensitive or deterministic value objects when applicable

Layer 1 tests should prove:

- the contract behaves as expected
- invalid inputs fail correctly
- value objects remain deterministic and immutable where expected

### Layer 2 Orchestrator Testing

Every meaningful Layer 2 change should have:

- unit tests for the orchestration path
- collaborator interaction tests where the workflow spans multiple packages
- error-path coverage for invalid or missing inputs

Layer 2 tests should prove:

- the correct sequence of work happens
- the orchestrator does not depend on framework details
- the workflow fails correctly when a dependency fails or returns invalid data

### Layer 3 Adapter Testing

Every meaningful Layer 3 change should have:

- feature or integration tests for the adapter boundary
- validation coverage for request input
- tenant-scoped access tests where data is buyer-specific
- response-shape checks for API consumers

Layer 3 tests should prove:

- the framework binding is correct
- tenant boundaries are preserved
- persistence behavior matches the contract
- adapter errors are not silently hidden

### WEB Testing

Every meaningful WEB change should have:

- component or hook tests for the changed behavior
- live-mode coverage for any hook that consumes API data
- mock-mode tests when mock behavior is supported
- page tests for route-level state changes
- E2E tests when the change affects alpha workflow navigation or release evidence

WEB tests should prove:

- live mode fails loudly when the API is unavailable or malformed
- mock mode remains explicitly local-only
- UI states are honest for loading, empty, unavailable, and error conditions
- route guards and deferred surfaces behave as intended

## Mandatory Coverage Rules

### Hook Coverage Standard

Every changed or newly added hook in `apps/atomy-q/WEB/src/hooks` must have:

1. mock-mode behavior coverage
2. live-mode transport error coverage
3. live-mode valid payload coverage
4. live-mode undefined payload coverage
5. live-mode malformed payload coverage

If a hook cannot support mock behavior, the test must still lock down the intended mock-mode behavior explicitly.

### Live-Mode Coverage Standard

For live-mode hooks and pages:

- test the transport boundary
- test undefined responses
- test malformed payloads
- test explicit error states in the UI

Live-mode tests must not silently import mock helpers or rely on seed behavior unless the branch is explicitly in mock mode.

### Alpha-Journey Coverage Standard

Any change that can affect the alpha journey must have at least one of:

- API feature tests
- WEB live-hook and page tests
- E2E smoke verification

The stronger the alpha impact, the more layers of verification are required.

## Change Class Verification Matrix

### C1: Cosmetic

Required:

- targeted unit or component tests
- no regression in relevant page rendering
- docs update if behavior or ownership changed

Typical examples:

- copy edits
- spacing tweaks
- style-only changes

### C2: Behavior Inside Existing Alpha Flow

Required:

- targeted tests for the changed area
- relevant live-mode coverage if the WEB consumes API data
- `npm run build` for WEB changes
- release checklist entry if the change is alpha-relevant

Typical examples:

- hook normalization updates
- empty-state and unavailable-state changes
- deterministic seed updates that affect mock behavior

### C3: Auth, Tenant, API Contract, Or Cross-Cutting Alpha Changes

Required:

- WEB lint/build/unit where WEB is touched
- API feature or alpha matrix coverage where API is touched
- E2E smoke for alpha-journey changes
- current release checklist entry
- explicit non-regression statement

Typical examples:

- auth/session changes
- tenant-scoped API behavior
- RFQ/quote/comparison/award workflow changes
- staging/runtime posture changes
- OpenAPI/client regeneration

## Atomy-Q Required Test Suites

### API

The API must normally use:

- `php artisan test` for full-suite confidence when the change is broad
- targeted test filters for focused changes
- alpha matrix coverage for release-sensitive work

Use full suite when:

- shared infrastructure changed
- multiple domains were touched
- the change affects auth, tenant scope, persistence, or cross-cutting adapters
- release gate work is being finalized

Use targeted tests when:

- the change is isolated to one feature or one adapter
- the full suite would not add meaningful signal for the change

### WEB

The WEB must normally use:

- `npm run test:unit`
- `npm run build`
- targeted `.live.test.ts` and page tests when hooks or routes change
- `npm run test:e2e` when a journey or release gate is affected

Use full unit/build verification when:

- hooks changed
- route content changed
- live-vs-mock behavior changed
- alpha journey behavior changed

Use E2E when:

- the change affects navigation through the alpha flow
- release evidence depends on the browser journey
- a page change can only be trusted in the integrated app

### Release-Management and Docs

Docs and release-management changes must be verified by:

- confirming the canonical document path
- checking linked references are not stale
- confirming current release state is accurately described

Docs changes do not need product test execution unless they change a documented operational assumption.

## Alpha Release Gates

### Task 9 Gate Expectations

The final alpha release gate expects evidence that:

- the current release checklist reflects the real state of the system
- blocker status is current
- the alpha-supported journey is explicit
- live WEB behavior is honest
- staging smoke evidence exists with `NEXT_PUBLIC_USE_MOCKS=false`

### Minimum Release Verification

For final alpha release readiness, the following categories should be represented in evidence:

- API alpha matrix or equivalent high-value targeted API subset
- WEB build
- WEB unit coverage
- live-mode hook coverage
- at least one E2E or staging smoke proving the buyer journey

If a category is intentionally omitted, the omission must be justified in the release checklist.

## Test Data And Fixtures

- Use the smallest realistic fixture that proves the behavior.
- Prefer deterministic fixtures for alpha-critical tests.
- Do not use seed data to cover up live-mode contract gaps.
- Keep test fixtures tenant-scoped and explicit.
- Avoid tests that depend on unrelated global state.

## Mocking Rules

- Mock external dependencies, not the behavior under test.
- Do not mock away the contract you are trying to verify.
- For live-mode WEB hooks, mock the transport boundary only as much as needed to drive the payload shape.
- For alpha-critical API flows, prefer testing through the actual framework boundary where practical.

## Database Rules For Tests

- Tests must never run against a production database.
- Use isolated test databases or in-memory databases only when the test contract supports it.
- Tenant-scoped tests must explicitly verify tenant separation.
- Database fixtures should remain minimal and deterministic.

## What Not To Test

Do not spend test effort on:

- duplicated assertions that prove the same thing
- implementation details that can change without breaking the contract
- fake success paths for unsupported surfaces
- broad UI snapshots that do not assert meaningful behavior
- mock-only behavior where live behavior is the release requirement

## Review Expectations

Before a test-heavy change is considered done, confirm:

- the tests cover the actual changed boundary
- live and mock behavior are not conflated
- the change class drove the right verification depth
- tenant isolation or release posture was not weakened
- the release checklist was updated if the work is alpha-sensitive

## Enforcement

If the changed behavior is not covered at the correct boundary, the work is not done.

If a test is green but proves only mock behavior for a live alpha path, it is insufficient.

If a release-critical change lacks the required verification category, it should not merge.
