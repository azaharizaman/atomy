# Atomy-Q Alpha Test Gate Design

## Context

The current Atomy-Q API feature suite has meaningful coverage for several alpha-critical workflows, but broad API coverage is partly inflated by route smoke tests that only assert authenticated requests are not rejected with `401` or `403`. The WEB app has Playwright coverage and test infrastructure, but the alpha release gate needs a clearer split between fast UI regression tests and real-API release evidence.

This design defines an alpha-first test expansion. It does not attempt to harden every API and WEB route immediately. The goal is to make alpha release confidence defensible by proving the buyer journey is tenant-safe, contract-aligned, live-mode honest, and regression-resistant.

## Goals

- Define a repeatable alpha release-gate suite for `apps/atomy-q/API` and `apps/atomy-q/WEB`.
- Prioritize tests around the buyer alpha journey instead of every route group.
- Separate route smoke coverage from real feature coverage.
- Ensure API tests cover tenant isolation, validation, persistence, idempotency, rollback, and side effects for alpha-critical behavior.
- Ensure WEB tests cover live-mode data handling and real-API Playwright journeys.
- Produce a test matrix that maps alpha capabilities to concrete test evidence.

## Non-Goals

- Exhaustively backfill feature coverage for all account, settings, reports, integrations, handoffs, notifications, top-level document library routes, and generic document-management routes.
- Replace all existing tests.
- Treat mocked WEB E2E as release evidence.
- Add backward compatibility tests for legacy behavior.
- Build a new test runner if existing Laravel, Vitest, Playwright, and root E2E commands can express the gate.

## Alpha Scope

The alpha gate covers these capabilities:

- Auth, session, MFA, and tenant context.
- Project and task visibility where they affect RFQ access.
- RFQ create, update, draft, status transition, duplicate, and bulk action.
- Vendor selection, invitations, and sourcing recommendations.
- Quote upload, quote state, source lines, normalization review, override, and readiness.
- Comparison preview, finalization, matrix, readiness, and AI overlay.
- Approval summary, approve, reject, history, and AI summary evidence.
- Award creation, guidance, signoff, debrief, and repeat-action behavior.
- RFQ Evidence Vault readiness, supporting evidence upload, award-pack finalization, manifest export, and generic Documents route removal.
- Decision trail evidence for irreversible or AI-assisted decisions.
- WEB alpha journeys for the same flow against the real local Laravel API.

Deferred route families must be explicit in the matrix rather than silently counted as covered.

## Test Architecture

Use an alpha gate ladder:

1. API critical feature tests.
2. API shared contract and security matrix.
3. WEB unit and hook tests for live-mode data handling.
4. Playwright alpha journeys against the real local Laravel API.
5. Release documentation and command evidence.

Mocked WEB tests stay useful for fast UI feedback. Real-API WEB tests are required for release-gate evidence.

## API Feature Layer

Extend existing alpha workflow feature tests where they already carry domain setup and assertions. Avoid creating a parallel suite that duplicates fixtures without improving confidence.

For each alpha capability, cover the relevant subset of:

- Success path.
- No token returns `401`.
- Invalid token returns `401`.
- Missing tenant context returns `403`.
- Wrong tenant resource access returns `404`.
- Invalid payload returns the app-standard validation envelope.
- Correct database rows are created, updated, or deleted.
- Failed validation or business rules leave no partial persistence.
- Jobs, mail, storage, AI artifacts, audit logs, and decision trail rows are created only when expected.
- Jobs, mail, storage, AI artifacts, audit logs, and decision trail rows are not created on failure.

Priority API domains:

- Auth and identity.
- Projects, tasks, and project ACL where they gate RFQs.
- RFQ lifecycle and line items.
- Vendor selection, invitations, and recommendations.
- Quote submissions and source lines.
- Normalization review and override.
- Comparison runs.
- Approvals.
- Awards.
- RFQ Evidence Vault.
- Decision trail.

## API Shared Contract Layer

Add an alpha-focused contract matrix that proves shared guarantees for protected alpha routes. This supplements, and should eventually replace reliance on, the existing smoke matrix that only checks "not unauthorized or forbidden."

The matrix should verify:

- Protected alpha routes reject missing tokens with `401`.
- Invalid tokens return `401`.
- Tokens without tenant context return `403`.
- Tenant-owned resources return `404` for wrong-tenant access.
- Validation failures use the canonical error envelope.
- List endpoints consumed by WEB expose stable `data` and `meta` shapes.
- Unsupported or deferred alpha controls return explicit stable errors, not fake success.

This matrix should not claim full feature coverage for route families outside alpha.

## Idempotency And Transaction Layer

For alpha mutations with idempotency middleware or multi-write behavior:

- Repeating the same request with the same idempotency key must not duplicate records or side effects.
- Replayed responses must be stable enough for clients to safely retry.
- Failed requests must not persist partial rows.
- Failed validation or business rule paths must not dispatch jobs, send mail, write files, or record success-state audit/decision evidence.
- Irreversible actions such as signoff and award/debrief finalization must behave predictably on repeat.

Priority mutations include RFQ creation, RFQ duplicate, RFQ bulk action, invitations, vendor recommendations, quote source lines, vendor creation, sanctions screening, comparison finalization, approval decisions, award signoff/debrief, RFQ Evidence Vault supporting evidence upload, and RFQ Evidence Vault award-pack finalization.

## WEB Unit And Hook Layer

Use Vitest for API-facing hooks, data adapters, and alpha page state logic. These tests should be fast and deterministic.

For each alpha hook or adapter that consumes API data, cover:

- Live-mode valid payload.
- Live-mode transport failure.
- Live-mode undefined payload.
- Live-mode malformed payload.
- Empty but valid tenant-scoped data.
- Explicit mock-mode behavior where supported.
- Expired or missing auth state where the UI owns behavior.

WEB assertions should prove that live mode fails honestly. Malformed or unavailable API data must not silently become a successful UI state.

## WEB Playwright Real-API Layer

Playwright release-gate tests should run with `NEXT_PUBLIC_USE_MOCKS=false` against the real local Laravel API. Seeded data is acceptable when full UI setup would make the test slow or brittle, but the browser must exercise the real WEB/API transport path.

Required alpha journeys:

- Login and authenticated dashboard entry.
- Create or open an RFQ.
- Select vendors, invite vendors, or run recommendation flow.
- Upload or inspect quote intake state.
- Review normalization source lines and apply an override where applicable.
- Preview or finalize comparison.
- Perform approval action.
- Create or inspect award, signoff, and debrief behavior.
- Open the RFQ Evidence Vault and verify readiness, blockers, or finalized export state.
- Verify decision trail evidence appears for AI-assisted or irreversible actions.

Mocked Playwright remains available for UI-only regression checks, but cannot satisfy release-gate evidence.

## Alpha Test Matrix

Create an alpha test matrix before broad backfill. The matrix is the source of truth for inclusion and deferral.

Each row should include:

- Capability.
- API route or routes.
- WEB screen or journey.
- Existing test file.
- Current coverage status.
- Missing alpha cases.
- Required command.
- Deferral note, if not in the current alpha gate.

The matrix should explicitly mark smoke-only coverage as smoke-only.

## Execution Model

Stage 1: Gate inventory.

- Write the alpha test matrix.
- Mark existing tests as behavioral, contract, smoke, or missing.
- Identify the smallest test additions that make the gate defensible.

Stage 2: API backfill.

- Add tenant isolation, validation envelope, idempotency, rollback, no-side-effect, and decision-trail tests in priority alpha domains.
- Prefer extending existing feature files with established fixtures.

Stage 3: WEB backfill.

- Add Vitest coverage for alpha hooks and data adapters.
- Add or harden Playwright real-API journeys.

Stage 4: Release gate command.

- Define the repeatable command set using existing scripts where possible.
- Document which commands are release evidence and which are fast regression checks.

Candidate commands:

```bash
cd apps/atomy-q/API
php artisan migrate:fresh --seed
php artisan test --testsuite=Feature --filter Alpha
```

```bash
cd apps/atomy-q/WEB
npm run test:unit
NEXT_PUBLIC_USE_MOCKS=false npm run test:e2e:ci
```

```bash
npm run test:e2e:laravel
```

The implementation plan should refine exact commands from the current scripts and avoid inventing a separate runner unless the existing commands cannot express the gate. The default API selection mechanism is PHPUnit groups because it can span existing feature files without forcing disruptive file moves. New or updated release-gate API tests should use `#[Group('alpha-gate')]` unless the project already has a stronger local convention by implementation time.

## Acceptance Criteria

- Every alpha capability has API feature coverage or an explicit documented deferral.
- Every tenant-owned alpha resource family has a wrong-tenant `404` case.
- Every alpha mutation has invalid-payload coverage.
- Idempotent alpha mutations prove no duplicate records or duplicate side effects.
- Multi-write alpha mutations prove no partial persistence on failure.
- Decision trail or audit expectations are tested for comparison, approval, award, and AI-assisted decisions.
- RFQ Evidence Vault tests prove RFQ scoping, wrong-tenant `404`, blocker reporting, supporting evidence storage behavior, manifest finalization, export, immutable finalized evidence, and generic Documents route removal.
- Job, mail, storage, and AI side effects are asserted on success and failure where applicable.
- WEB alpha hooks or adapters have live-mode success, transport failure, undefined payload, malformed payload, and empty-state coverage where applicable.
- Playwright alpha release-gate tests run against the real local API with `NEXT_PUBLIC_USE_MOCKS=false`.
- Mock-only WEB tests are clearly labeled as fast regression tests, not release evidence.
- The current route smoke matrix is not counted as feature coverage.

## Risks And Mitigations

- Risk: The gate becomes too slow for routine development.
  Mitigation: Keep fast Vitest and targeted API subsets separate from the full release gate.

- Risk: E2E setup becomes brittle.
  Mitigation: Use stable seed fixtures and API setup for complex state, while preserving real browser-to-API transport.

- Risk: The matrix turns into documentation that drifts.
  Mitigation: Keep it close to release docs and update it when adding or deferring alpha coverage.

- Risk: Shallow route families remain undercovered.
  Mitigation: Mark them as deferred and schedule a post-alpha hardening wave.

## Implementation Defaults

- API test selection: use PHPUnit group `alpha-gate` for new or updated release-gate feature tests.
- API file organization: extend existing domain feature files when fixtures are already present; create new `*AlphaGateTest.php` files only when no suitable file exists.
- Matrix location: write the matrix to `apps/atomy-q/docs/05-qa/alpha-test-matrix.md` so it sits with QA/release evidence rather than generic design docs.
- Matrix format: use Markdown tables grouped by alpha capability with explicit status values: `behavioral`, `contract`, `smoke-only`, `missing`, and `deferred`.
- WEB unit selection: place Vitest tests next to the relevant hook/adapter when practical; otherwise use `apps/atomy-q/WEB/tests/support` for shared test utilities only.
- WEB E2E selection: keep Playwright specs in `apps/atomy-q/WEB/tests` and name release-gate specs with `alpha-gate` in the filename or test title.
- Real-API E2E startup: prefer root scripts that start Laravel and WEB together. If existing scripts are insufficient, add one small root script instead of requiring manual server startup for release evidence.
