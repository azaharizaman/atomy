# SettingsManagement Minimal Productionization Design

**Topic:** Make `orchestrators/SettingsManagement` presentable for Atomy-Q alpha without expanding runtime feature scope.
**Date:** 2026-04-10
**Status:** Approved

## 1. Executive Summary
`orchestrators/SettingsManagement` currently has runnable PHPUnit tests, but package ergonomics are inconsistent: README documents `composer test` and `composer test-coverage`, while `composer.json` does not define those scripts. This creates avoidable friction and weakens confidence during review.

This design introduces a minimal packaging-quality baseline:
- working Composer test scripts,
- stable PHPUnit configuration file,
- README command parity,
- lightweight package-scoped CI workflow.

The goal is presentation and reliability of developer workflow, not runtime feature expansion.

## 2. Goals and Success Criteria
- **Goal:** Ensure README test commands run exactly as documented.
- **Goal:** Provide one clear command path for local verification and CI verification.
- **Goal:** Keep changes low-risk and isolated to package quality tooling.

**Success Criteria**
- `composer test` succeeds in `orchestrators/SettingsManagement`.
- `composer test-coverage` succeeds and emits text coverage summary.
- README test section exactly matches package scripts.
- GitHub Actions workflow runs package install and tests on relevant path changes.

## 3. Scope Boundaries
### In Scope
- `composer.json` scripts alignment.
- `phpunit.xml` creation for package-local defaults.
- README test section correction.
- New CI workflow file in `.github/workflows/` scoped to this package.

### Out of Scope
- Runtime/business logic changes in `src/`.
- Additional tooling that may introduce churn (e.g., strict PHPStan rollout).
- Cross-package monorepo test unification.
- Large coverage artifact publication/reporting stack.

## 4. Architecture and Workflow
The package remains a standalone orchestrator library with local Composer and PHPUnit execution.

Quality flow after this design:
1. Engineer runs `composer install` in package.
2. Engineer runs `composer test` for default unit checks.
3. Engineer runs `composer test-coverage` for text coverage signal.
4. CI performs the same install + test path on pull requests touching the package.

No application runtime dependencies or service bindings change.

## 5. File-Level Changes
- `orchestrators/SettingsManagement/composer.json`
  - Add `scripts.test` and `scripts.test-coverage`.
- `orchestrators/SettingsManagement/phpunit.xml` (new)
  - Define bootstrap, testsuite path, and basic coverage include path.
- `orchestrators/SettingsManagement/README.md`
  - Keep test instructions synchronized with scripts and add explicit package-local usage.
- `.github/workflows/settings-management-ci.yml` (new)
  - Package-scoped workflow using path filters and running Composer + PHPUnit commands.

## 6. Error Handling and Risk Controls
- If coverage driver (Xdebug/PCOV) is missing, `composer test-coverage` should fail clearly with PHPUnit output; no silent fallback.
- CI scope remains narrow via path filters to avoid slowing broader alpha pipelines.
- No runtime code path changes reduce regression risk for Atomy-Q alpha.

## 7. Testing Strategy
- Local verification:
  - `composer test`
  - `composer test-coverage`
- CI verification:
  - Push a branch touching package files and confirm workflow passes.

## 8. Rollout
- Merge as one small PR.
- Announce new canonical package test commands in PR description.
- Use this package as baseline template for similar orchestrators only after alpha-critical work settles.
