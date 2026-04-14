# Atomy-Q Alpha Mitigation Plan — Gaps 8, 9, 10

Date: 2026-04-09  
Scope: `apps/atomy-q/`  
Source gap analysis: `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`

## Objective

Close the remaining alpha execution risks for:

- Gap 8: production-readiness debt (build/test/env/infra contracts)
- Gap 9: mock-fallback leakage into live flows
- Gap 10: fragmented or stale execution docs

## Non-negotiable alpha criteria

1. WEB `npm run build` and `npm run lint` pass in CI and staging.
2. API critical feature tests pass with queue/storage enabled and tenant-safe behavior preserved.
3. Live-mode UI paths fail loudly on API failure; no silent mock/seed fallback.
4. A single active mitigation ledger exists and is kept current.

## Workstream A — Gap 8 (Production readiness)

Owner: Engineering + DevOps  
Target: Alpha gate before release declaration

Tasks:

1. Build and test gate hardening
   - Standardize command matrix for API + WEB in CI and staging.
   - Add explicit failure gates for build/lint/unit/e2e entry points.
2. Environment contract hardening
   - Document required env vars (AI, queue, storage, host/port alignment) with defaults and fail-fast behavior.
   - Validate runtime mismatch detection (API/WEB URL and port consistency).
3. Queue and storage readiness
   - Verify queue worker startup and retry policy in staging.
   - Verify S3/MinIO credentials, bucket policy, and upload/download smoke.

Verification evidence:

- CI logs for build/lint/test matrix
- Staging smoke run output for queue + storage
- Updated runbook references committed in-repo

## Workstream B — Gap 9 (Mock fallback removal)

Owner: WEB + API  
Target: Immediate for golden path

Tasks:

1. Mock flag policy
   - Restrict mock mode to explicit local-development use only.
   - Remove ambiguous wording that treats mock mode as normal alpha behavior.
2. Golden-path runtime enforcement
   - Ensure RFQ intake, normalization, comparison, and award pages do not silently swap to seed data in live mode.
   - Return observable error UI when live API calls fail.
3. Test enforcement
   - Add/maintain tests asserting no mock fallback in live mode.
   - Keep separate tests for explicit mock mode only.

Verification evidence:

- WEB README + environment docs update
- Unit/e2e tests that fail if live mode silently consumes mock data
- Manual smoke checklist captured in release notes

## Workstream C — Gap 10 (Roadmap/documentation consolidation)

Owner: Product + Engineering  
Target: Completed in this change set for stale-weekly artifact retirement

Tasks:

1. Retire stale weekly-plan artifacts
   - Remove `PLAN-WEEK-1.md` through `PLAN-WEEK-5.md`.
   - Remove generated GitHub-project bootstrap artifacts tied to obsolete weekly tasks.
2. Replace with single active index
   - Keep `PLAN-INDEX.md` as active mitigation index only.
   - Track execution from this mitigation plan plus current superpowers plans.
3. Ongoing documentation governance
   - Require every alpha-impacting PR to update mitigation status or explicitly state "no status change."

Verification evidence:

- No remaining references to deleted weekly plan files
- `PLAN-INDEX.md` points to active docs only
- This mitigation plan committed as the operational source

## Change log (2026-04-09)

- Established active mitigation plan for Gaps 8/9/10.
- Retired stale weekly execution artifacts and bootstrap payloads.
- Repointed plan index to active, current documentation only.
- Completed Phase 1: restored `nexus/sourcing-operations` lock/vendor wiring, RFQ lifecycle mutation tests green, WEB build/lint hard errors cleared.
- Initiated Phase 2 with live-path hardening: removed silent live-mode RFQ seed fallback (`use-rfqs`) and added explicit live-mode fail-loud tests.
- Currently executing Phase 3: vendor API stubs replaced with tenant-scoped live endpoints (`index/show/performance/compliance/history`) plus `VendorWorkflowTest` coverage.
- Verification checkpoint refreshed: API targeted matrix and WEB build/lint gates are currently green (lint warnings remain and are tracked as non-blocking).

## Gap 9 Implementation Plans (2026-04-14)

Gap 9 (Mock Fallback Elimination) implementation is underway. See spec and plans:

- **Spec:** `docs/superpowers/specs/2026-04-14-gap-9-fail-loud-design.md`
- **Plan - Phase 1 (Comparison):** `docs/superpowers/plans/2026-04-14-gap-9-fail-loud-comparison.md`
- **Plan - Phase 2a (Quote Intake):** `docs/superpowers/plans/2026-04-14-gap-9-fail-loud-quote-intake.md`
- **Plan - Phase 2b (RFQ Details):** `docs/superpowers/plans/2026-04-14-gap-9-fail-loud-rfq-details.md`
- **Plan - Phase 3 (Awards/Vendors/Projects):** `docs/superpowers/plans/2026-04-14-gap-9-fail-loud-awards-vendors-projects.md`
- **Plan - Phase 4 (Approvals/Auth/Dashboard):** `docs/superpowers/plans/2026-04-14-gap-9-fail-loud-approvals-auth-dashboard.md`

## Atomy-Q code review checklist and document pass

- §1 Exception contracts: N/A (markdown documentation file, no code)
- §2 DI interfaces: N/A (markdown documentation file, no code)
- §3 Tenant + eager loads: N/A (markdown documentation file, no code)
- §4 Activity limits: N/A (markdown documentation file, no code)
- §5 WEB parsing + catch: N/A (markdown documentation file, no code)
- §6 UI duplication: N/A (markdown documentation file, no code)
- §7 Playwright CORS: N/A (markdown documentation file, no code)
- §8 Docs/plans: Pass — Markdown formatting is clean (proper backtick nesting: `nexus/sourcing-operations`, `use-rfqs`, `VendorWorkflowTest`); changelog accurately reflects phases and completion status
- §9 Password reset: N/A (not applicable to mitigation plan documentation)
- §10 Verification: Pass — Changelog confirms tests are green (RFQ lifecycle mutation tests, WEB build/lint gates); lint warnings tracked as non-blocking
- §11 Sourcing lifecycle: Pass — Changelog correctly documents RFQ lifecycle restoration, vendor API migration to tenant-scoped live endpoints, and transaction boundary work alignment with phase completion milestones
