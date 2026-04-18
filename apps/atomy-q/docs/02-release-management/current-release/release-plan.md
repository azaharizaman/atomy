# Current Release Plan

## Active Task

The Atomy-Q current release plan has one active execution task:

### Task 9: Final Alpha Release Gate

Goal:
- consolidate blocker status, verification evidence, staging smoke results, and accepted deferments into one release decision

Required completion criteria:
- blocker ledger reflects current reality
- WEB lint/build/unit gates are in a releasable state
- API alpha matrix passes against a clean test database
- at least one true staging golden-path smoke passes with `NEXT_PUBLIC_USE_MOCKS=false`
- every intentionally deferred feature still exposed in docs or code has owner, rationale, and operator-facing behavior
- release checklist contains engineering, product, and operator sign-off fields
- alpha-supported flow is stated explicitly and cannot be inferred from broader route surface

## Completed Tasks

The following execution work is complete and now treated as historical release evidence:
- Task 1: release evidence baseline
- Task 2: deterministic quote-intelligence binding
- Task 3: award end-to-end proof
- Task 4: live-mode fail-loud enforcement
- Task 5: hide or defer non-alpha surfaces
- Task 6: minimal users and roles scope
- Task 7: API contract and WEB client alignment
- Task 8: staging operations readiness documentation

Historical task plans and specs:
- [`../../../07-history/archived-releases/alpha-2026-04/`](../../../07-history/archived-releases/alpha-2026-04/)
