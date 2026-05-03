# Current Release Plan

## Active Task

The Atomy-Q current release plan has one active execution task:

### Task 9: Final Alpha Release Gate

Goal:
- consolidate blocker status, verification evidence, staging smoke results, and accepted deferments into one release decision

Required completion criteria:
- the superseding launch readiness design is applied as the controlling go/no-go contract
- blocker ledger reflects current reality
- WEB lint/build/unit gates are in a releasable state
- API alpha matrix passes against a clean test database
- at least one true staging golden-path smoke passes with `NEXT_PUBLIC_USE_MOCKS=false`
- every intentionally deferred feature still exposed in docs or code has owner, rationale, and operator-facing behavior
- release posture is explicitly selected as either AI-enabled alpha or clearly disclosed manual-continuity alpha
- manual continuity and human-in-the-loop gates pass regardless of AI posture
- customer disclosure is complete if paid AI-assisted features are unavailable
- release checklist contains engineering, product, and operator sign-off fields
- alpha-supported flow is stated explicitly and cannot be inferred from broader route surface

Controlling readiness contract:
- [`2026-04-30-atomy-q-alpha-launch-readiness-design.md`](../../../../../docs/superpowers/specs/2026-04-30-atomy-q-alpha-launch-readiness-design.md)

## Approved Scope-Inclusion Proposal

The RFQ-local Evidence Vault is approved as an alpha feature and is locally implemented for alpha. It remains subject to the final Task 9 release gate and must not be represented as externally release-ready until the broader alpha checklist records PostgreSQL, staging mocks-off smoke, disclosure, and sign-off evidence.

Design:
- [`2026-05-03-atomy-q-rfq-evidence-vault-alpha-design.md`](../../../../../docs/superpowers/specs/2026-05-03-atomy-q-rfq-evidence-vault-alpha-design.md)

Scope rule:
- The feature is RFQ workspace only.
- Top-level `/documents` and generic document API endpoints are redundant for Atomy-Q and should be removed rather than promoted.
- The alpha capability is an award justification evidence pack over quote, normalization, comparison, approval, award, signoff, and decision-trail evidence.
- RFQ Evidence Vault is supported when API/WEB evidence-vault tests, OpenAPI export, generated client, WEB build, and staging smoke evidence are green.

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
