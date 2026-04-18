# Atomy-Q Definition Of Done

## Changelog

| Date | Version | Change |
|---|---|---|
| 2026-04-18 | 1.0 | Initial Atomy-Q definition of done for code, docs, QA, release evidence, and alpha readiness. |

## Purpose

This document defines what "done" means for Atomy-Q work.

In Atomy-Q, "done" does not mean:

- code compiled locally
- a branch was merged
- a page visually renders
- a mock/demo path works

In Atomy-Q, "done" means the change is:

- implemented
- verified
- documented
- scoped correctly
- safe for alpha or explicitly accepted as deferred

This standard applies to:

- `apps/atomy-q/API`
- `apps/atomy-q/WEB`
- Nexus Layer 1 packages
- Nexus Layer 2 orchestrators
- Nexus Layer 3 adapters
- docs and release-management updates that affect alpha execution

## Definition

A change is done only when all relevant items below are true.

### 1. The scope is correct

- The change matches the approved branch or change-control scope.
- No unrelated product work has been mixed in.
- Any intentional scope expansion has been explicitly approved.
- The change maps to the correct blocker, task, or durable decision.

### 2. The implementation is complete

- The intended behavior exists in the correct layer.
- Layer boundaries are respected.
- No temporary stub, fake success, or hidden placeholder remains in the delivered path.
- Live mode behaves honestly.

### 3. The behavior is verified

- The appropriate automated tests exist and pass.
- The verification depth matches the change class.
- Tenant-scoped, contract-sensitive, and live-mode changes are tested at the boundary where they can fail.
- Manual verification is only supplementary, never the only proof for alpha-critical work.

### 4. The docs are current

- Canonical product, domain, engineering, release, or operations docs are updated if the change affects durable behavior.
- If the change alters a decision, the decision is captured in the relevant canonical document.
- If the change affects alpha release posture, the release checklist is updated.

### 5. The release evidence is traceable

- The change appears in the current release checklist when it belongs to alpha remediation or release work.
- The evidence links back to the actual branch or PR.
- Non-regression statements are explicit.
- Sign-offs are present when required.

### 6. The code is safe to ship

- The code does not weaken tenant isolation.
- The code does not reintroduce mock fallback into live paths.
- The code does not silently widen alpha scope.
- The code does not expose unsupported behavior as if it were production-ready.

## Done Criteria By Work Type

### A. Layer 1 Package Change

Done when:

- public contracts are coherent and typed
- immutable/value semantics are preserved where appropriate
- package tests cover the changed behavior
- no framework dependency was introduced
- package documentation reflects the durable decision

### B. Layer 2 Orchestrator Change

Done when:

- orchestration remains framework-agnostic
- interfaces are owned and explicit
- composed package behavior is verified
- no Layer 3 dependency leaks into the orchestrator
- downstream adapters can consume the orchestration cleanly

### C. Layer 3 Adapter / API Change

Done when:

- the adapter or controller is thin and contract-aligned
- tenant scoping is preserved
- request validation and response shapes are correct
- persistence is verified at the adapter boundary
- relevant API tests pass
- release checklist evidence is updated when alpha-critical

### D. WEB Change

Done when:

- live-mode behavior is honest
- mock-mode behavior is explicit and local-only
- hooks reject malformed data instead of silently coercing it
- UI states for loading, empty, unavailable, and error are correct
- targeted unit and/or E2E tests pass
- build passes
- docs are updated when behavior changes

### E. Docs-Only Change

Done when:

- the document is placed in the canonical docs structure
- the document starts with a changelog if it is a live standard, policy, or canonical guidance document
- references point to the current canonical source of truth
- outdated references are removed or redirected
- the doc does not create a second source of truth for the same topic

### F. Alpha Remediation Change

Done when:

- the change-control ledger entry exists and is accurate
- the branch is correctly named and scoped
- the required gates for the class have passed
- non-regression is stated explicitly
- the current release checklist has the needed evidence
- the change does not re-open closed blockers

### G. Final Alpha Release Gate Work

Done when:

- Task 9 criteria in the current release plan are satisfied
- release checklist shows the correct blocker state
- staging smoke evidence exists with `NEXT_PUBLIC_USE_MOCKS=false`
- engineering, product, and operator sign-off is recorded
- the release posture can be defended from the docs alone

## Mandatory Verification Minimums

### PHP / API

For API or backend work, done normally requires:

- relevant PHPUnit feature or unit coverage
- `php artisan test` or a targeted subset appropriate to the change
- if the change touches the alpha surface, the alpha matrix or relevant subset must pass

### WEB

For WEB work, done normally requires:

- relevant Vitest coverage
- build success
- live-mode coverage when the change touches a hook or live page
- Playwright coverage when the change affects an alpha journey or release gate

### Docs

For docs work, done requires:

- the right canonical document updated
- the index/navigation updated if needed
- no contradictory legacy reference left behind

## Alpha-Specific Done Rules

Atomy-Q alpha work is not done if any of the following are true:

- live mode still silently falls back to seed data
- a closed blocker has been reintroduced
- tenant scope can be bypassed or inferred incorrectly
- unsupported surfaces are exposed as if alpha-approved
- release evidence exists only in a developer's memory or chat history

For alpha-critical work, "done" requires evidence that another operator can verify the result from the docs and the branch history alone.

## Checklist

Before marking work done, confirm:

- scope is unchanged or explicitly approved
- implementation exists in the correct layer
- automated verification passed
- docs are current
- release evidence is recorded if needed
- there are no hidden alpha regressions
- the branch can be merged without reopening known blockers

## Enforcement

If one of the required items is missing, the work is not done.

If a work item is partially complete, it must stay open and be tracked in the appropriate release, change-control, or implementation record.

If the work is outside alpha scope, it may still be complete from a code perspective, but it is not alpha-releasable unless the release docs say so.
