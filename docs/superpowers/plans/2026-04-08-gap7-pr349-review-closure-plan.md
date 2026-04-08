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
- [ ] Evaluate tenant-scoping defense-in-depth for user account-state updates in `EloquentUserRepository` without breaking package contracts.
- [ ] Evaluate optional domain exception for role deletion with assigned users; keep backward compatibility if callers rely on boolean failure.

## Batch 3 (Optional) - observability polish
- [ ] Decide whether MFA verification attempt metadata (`ipAddress`, `userAgent`) should be logged in-process to satisfy static-analysis/readability concerns.

## Verification Gates
- [ ] `php artisan test tests/Feature/Api/AuthTest.php tests/Feature/IdentityGap7Test.php --stop-on-failure`
- [ ] `./vendor/bin/phpunit adapters/Laravel/Identity/tests/Adapters/PermissionCheckerAdapterTest.php`

## Completion Criteria
- [ ] All Batch 1 items merged.
- [ ] No regression in Gap 7 feature tests.
- [ ] Review comments addressed or explicitly dispositioned with rationale.
