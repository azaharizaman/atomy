# Agentic Coding Guidelines

On-demand guidance for recurring implementation and review patterns. Keep this file pattern-based, not incident-based.

## Purpose

- Capture reusable failure patterns and finalization checks.
- Keep `AGENTS.md` lean for lower token cost.
- Prefer concise rules that apply across future work.

## Workflow

1. Map the affected package, app area, contracts, and tests before editing.
2. Check `docs/project/NEXUS_PACKAGES_REFERENCE.md` before adding a package or duplicate capability.
3. Define or update contracts before implementations.
4. Add or adjust focused tests for changed behavior and edge cases.
5. Update affected `IMPLEMENTATION_SUMMARY.md` files when public behavior, contracts, routes, or runtime configuration changes.
6. Before completion, run the smallest verification command that proves the change.

## Known Failure Patterns

### Tenant leakage

- Cause: tenant scope missing from secondary reads, eager loads, deletes, write paths, ACL checks, or fallback data.
- Fix: apply tenant filtering at the query root and in relation constraints; return 404 for wrong-tenant resources.

### Synthetic success

- Cause: returning fake IDs, empty strings, fabricated counts, or `0` when a dependency or branch fails.
- Fix: throw a domain exception or surface an explicit unavailable state.

### Partial transactions

- Cause: multi-write workflows persist one record before dependent writes, audit events, idempotency completion, or decision-trail writes finish.
- Fix: wrap related persistence in one transaction or use a documented outbox boundary.

### Side-effect ordering

- Cause: state such as `reminded_at` or alert status is saved before dispatch succeeds.
- Fix: dispatch first, then persist success state, unless an outbox pattern owns delivery.

### DTO and adapter drift

- Cause: UI payloads, adapter mappings, provider DTOs, and backend validation evolve independently.
- Fix: validate DTOs strictly, keep request fields in parity, and reject lossy casts or malformed live payloads.

### Mock/live mode bleed

- Cause: live hooks silently fall back to seed data, mock mode runs live queries, or fabricated UI values hide missing backend data.
- Fix: keep mock branches explicit and make live-mode failures visible.

### HTTP semantic drift

- Cause: GET endpoints generate or persist artifacts; POST routes persist without idempotency completion.
- Fix: keep GET read-only and use explicit POST generate/mutate routes with idempotency where retries can occur.

### PATCH field-presence bugs

- Cause: `??` merges or default mapping erase the difference between omitted fields and explicit `null`.
- Fix: track field presence separately from field value.

### Provider/runtime boundary leakage

- Cause: concrete provider dependencies, generic exceptions, raw exception output, or unsanitized payload context cross adapter boundaries.
- Fix: depend on interfaces, map failures to domain exceptions, and log identifier-only context unless deeper diagnostics are intentionally protected.

### PHP and framework boundary mismatch

- Cause: framework APIs expect `Closure`, readonly promoted properties are normalized incorrectly, or Layer 2 phpdoc leaks Layer 1 types.
- Fix: adapt to framework API types at Layer 3 and keep Layer 2 contracts framework- and package-safe.

### Identifier and numeric normalization

- Cause: trimmed-but-uncanonicalized identifiers, lexicographic ordering of numeric suffixes, decimal truncation, or blank accepted IDs.
- Fix: canonicalize on construction/write, validate non-empty identifiers, and parse numeric suffixes numerically.

### Test and review closure drift

- Cause: review threads remain open after fixes, tests contain dead helpers, or assertions only cover happy-path payload shape.
- Fix: sweep unresolved threads, remove dead test code, and assert contract-critical fields.

## Pre-Finalization Checklist

- [ ] Tenant-safe reads, writes, eager loads, deletes, and ACL checks verified.
- [ ] No synthetic success values or fabricated live-mode fallbacks.
- [ ] Multi-write, audit, artifact, idempotency, and decision-trail writes share a transaction or outbox boundary.
- [ ] DTOs, UI payloads, backend validation, and adapter mappings are in parity.
- [ ] GET routes are read-only; persisted POST routes have retry/idempotency semantics where needed.
- [ ] PATCH APIs preserve omitted versus explicit `null`.
- [ ] Layer 1 and Layer 2 remain framework-free and interface-first.
- [ ] Exceptions are domain-specific and sanitized for operator-facing output.
- [ ] Numeric, array, identifier, and readonly-constructor edge cases are guarded.
- [ ] Tests and docs match the final behavior.

## Multi-Agent Roles

- Architect: design, contracts, boundaries, and cross-package impact.
- Developer: implementation and local logic against agreed contracts.
- QA: verification, regression coverage, and edge-case review.
- Maintenance: dependency, documentation, and cleanup work.

