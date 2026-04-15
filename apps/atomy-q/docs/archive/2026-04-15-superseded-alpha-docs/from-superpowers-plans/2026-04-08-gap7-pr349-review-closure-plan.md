# Gap 7 PR349 Review Closure Plan

> **For agentic workers:** Use subagent-driven execution with small, reviewable batches.

**Goal:** Reduce PR349 review-comment volume below merge threshold by addressing high-signal correctness/performance/consistency findings without a full rewrite.

**Scope:** Refactor existing implementation in-place. No do-over.

## Batch 1 (Must Fix) - correctness + low-risk efficiency
- [x] Add explicit return type for `AuditLog::getId()`.
- [x] Optimize `EloquentPermissionRepository::findMatching()` with DB pre-filtering before wildcard matching.
- [x] Push tenant-role filtering into `AtomyUserQuery` query path.
- [x] Make `AtomyLegacyRole` timestamps deterministic per instance.
- [x] Replace full-table session cleanup scans with chunked processing in `DatabaseSessionManager`.
- [x] Simplify redundant `normalizeTenantId()` implementation.
- [x] Add explicit exact-permission negative assertion in permission checker adapter tests.
- [x] Replace `AtomyAuditLogRepository::exportToArray()` eager load/map with cursor-based bounded iteration.

## Batch 2 (Should Fix) - contract hardening
- [x] Evaluate tenant-scoping defense-in-depth for user account-state updates in `EloquentUserRepository` without breaking package contracts.
- [x] Evaluate optional domain exception for role deletion with assigned users; keep backward compatibility if callers rely on boolean failure.
  - Disposition: keep `bool` repository delete behavior for backward compatibility; preserve `RoleInUseException` behavior at `RoleManager` service layer.

## Batch 3 (Optional) - observability polish
- [x] Decide whether MFA verification attempt metadata (`ipAddress`, `userAgent`) should be logged in-process to satisfy static-analysis/readability concerns.
  - Disposition: implemented in `AtomyMfaVerificationService` with best-effort request metadata capture and structured MFA attempt logging, without changing interface contracts or persistence schema.

## Verification Gates
- [x] `php artisan test tests/Feature/Api/AuthTest.php tests/Feature/IdentityGap7Test.php --stop-on-failure`
- [x] `apps/atomy-q/API/vendor/bin/phpunit ../../../adapters/Laravel/Identity/tests/Adapters/PermissionCheckerAdapterTest.php`

## Completion Criteria
- [x] All Batch 1 items implemented in this branch.
- [x] No regression in Gap 7 feature tests.
- [x] Review comments addressed or explicitly dispositioned with rationale.
