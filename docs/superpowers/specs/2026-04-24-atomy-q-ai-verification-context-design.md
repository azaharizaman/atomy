# Atomy-Q AI Verification Context Design

- Date: 2026-04-24
- Area: monorepo verification for `orchestrators/InsightOperations` and `apps/atomy-q/API`
- Goal: make the AI plan verification path runnable without relying on invalid mixed-PHPUnit context assumptions.

## Problem Statement

The plan-5 verification command mixed two different PHPUnit execution models into one root invocation:

- `orchestrators/InsightOperations/tests` needs the monorepo or package autoloader to expose `Nexus\InsightOperations\*`.
- `apps/atomy-q/API/tests/Feature/FeatureFlagsApiTest.php` needs the API package bootstrap in `apps/atomy-q/API/phpunit.xml`, including Laravel test base classes under the API package's `autoload-dev`.

Running both under one root `./vendor/bin/phpunit ...` command fails for two separate reasons:

1. Root Composer did not autoload `Nexus\InsightOperations\*`, so the orchestrator tests could not load their contracts or coordinators.
2. API feature tests are not root-package PHPUnit tests. They depend on the API package bootstrap and cannot be treated as plain files under the root test runner.

This is not an application-feature defect. It is a verification-boundary defect.

## Goals

1. Make `InsightOperations` tests runnable from the monorepo root.
2. Make `InsightOperations` runnable from its own package directory inside this monorepo.
3. Keep API feature tests in the API package context that already owns their bootstrap.
4. Give plan-5 verification a single documented entrypoint that respects those boundaries.

## Non-Goals

- No attempt to create one universal PHPUnit bootstrap for every package in the monorepo.
- No attempt to pull API `Tests\*` namespaces into the root Composer autoloader.
- No change to application runtime behavior.

## Chosen Approach

Use a hybrid verification model:

1. Add the missing `Nexus\InsightOperations\` and `Nexus\InsightOperations\Tests\` mappings to the root Composer autoload maps so root PHPUnit can execute `orchestrators/InsightOperations/tests`.
2. Add package-local PHPUnit wiring to `orchestrators/InsightOperations`:
   - `phpunit.xml.dist`
   - Composer `test` script
3. Add a root Composer verification script for the plan-5 PHP checks:
   - run `InsightOperations` tests from root
   - run `FeatureFlagsApiTest` inside `apps/atomy-q/API`
4. Update the plan documentation so it no longer advertises a mixed-context root PHPUnit command.

## Why This Approach

This keeps the fix narrow and honest:

- `InsightOperations` becomes testable both from root and from its own package directory in the current monorepo checkout.
- The API test suite remains where it belongs, under the API package bootstrap.
- We avoid inventing a fake global PHPUnit context that would blur package boundaries and cause more fragile test commands later.

## Rejected Approaches

### 1. Keep a single root PHPUnit command and widen root autoload for API tests

Rejected because API feature tests still require Laravel bootstrap and API-specific test base classes. Adding more root autoload does not solve that bootstrap mismatch.

### 2. Fix only the plan doc and leave `InsightOperations` unwired

Rejected because the package would still lack a clear local test entrypoint, and the root orchestrator test invocation would remain broken.

### 3. Build a new monorepo-global PHPUnit harness

Rejected because it is substantially broader than the issue at hand and would create a new cross-package testing contract outside the scope of this implementation.

## File-Level Changes

- Root `composer.json`
  - add root autoload entries for `Nexus\InsightOperations\*`
  - add a Composer verification script for plan-5 PHP checks
- `orchestrators/InsightOperations/composer.json`
  - add a package-local `test` script that delegates to the root PHPUnit binary
- `orchestrators/InsightOperations/phpunit.xml.dist`
  - define package-local unit/integration test suites and bootstrap the root autoloader
- `docs/superpowers/plans/2026-04-23-atomy-q-ai-insights-governance-and-reporting.md`
  - replace the broken mixed-context PHPUnit command

## Verification Contract

The corrected plan-5 PHP verification contract is:

```bash
composer verify:atomy-q-ai-insights-governance-reporting
```

That command intentionally performs two context-aware steps:

1. Root PHPUnit runs `orchestrators/InsightOperations/tests`.
2. API package PHPUnit runs `tests/Feature/FeatureFlagsApiTest.php` from inside `apps/atomy-q/API`.

WEB verification remains unchanged because it already runs in the correct package context.

## Success Criteria

The work is complete when:

- root PHPUnit can load and execute `InsightOperations` tests,
- `InsightOperations` has a package-local PHPUnit config and runnable Composer `test` script,
- plan-5 docs no longer prescribe a broken mixed-context PHPUnit command,
- API feature tests continue to run only in the API package context.
