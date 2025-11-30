# Test Suite Summary: Nexus\Scheduler

**Test Framework Target:** PHPUnit 11.x  
**Last Test Run:** 2025-11-29 10:15 UTC  
**Status:** ✅ All Passing (1 deprecation warning)

## Test Coverage Metrics

### Overall Coverage (Smoke Suite)
- **Line Coverage:** Pending (coverage run not yet captured)
- **Function Coverage:** Pending
- **Class Coverage:** Pending
- **Complexity Coverage:** Pending

> Code coverage will be captured once the suite expands beyond the initial smoke scenarios. The tooling (`composer test:coverage`) is wired and ready when Xdebug/PCOV is enabled locally or in CI.

### Planned Coverage Targets
- Line Coverage ≥ 80%
- Function Coverage ≥ 85%
- Critical Value Objects + Core Engines at 100% branch validation

## Test Inventory (Planned)

### Unit Tests (1/20 implemented)
- `ScheduleManagerTest` — ✅ covers happy path dispatch, permanent failure (no retry), and recurring rollover using in-memory doubles.
- `ScheduleDefinitionTest` — Planned: validate creation rules, ULID enforcement, and metadata serialization.
- `ScheduleRecurrenceTest` — Planned: cover interval, daily, weekly, and custom recurrence calculations.
- `ScheduledJobTest` — Planned: verify status transitions, locking windows, and retry policies.
- `ExecutionEngineTest` — Planned: ensure handler dispatch, retry backoff, and telemetry hooks.
- `RecurrenceEngineTest` — Planned: confirm next-run calculations for all enum types.

### Integration Tests (0/6 implemented)
- `ScheduleManagerFlowTest` — Planned: create schedule, enqueue, execute, and reschedule recurrence using in-memory adapters.
- `TelemetryHookTest` — Planned: assert counters fire only when supplied tracker is present.
- `CalendarExportContractTest` — Planned: stub exporter to ensure interface contract is met.

### Feature / Example Validation (Manual for now)
- `docs/examples/basic-usage.php`
- `docs/examples/advanced-usage.php`

## Test Results Summary

```bash
cd packages/Scheduler
composer test

PHPUnit 11.5.44 by Sebastian Bergmann and contributors.

...                                                               3 / 3 (100%)

Time: 00:00.020, Memory: 8.00 MB

OK, but there were issues!
Tests: 3, Assertions: 16, PHPUnit Deprecations: 1.

Deprecation: Nexus\Scheduler\Tests\Unit\ScheduleManagerTest::it_handles_permanent_failures_without_retry
The "TestCase::expectDeprecation" method is deprecated and will be removed in PHPUnit 12. Expect deprecation notices using "expectWarning" instead.
```

## Testing Strategy

### What Is Tested Today
- ScheduleManager happy path execution, recurring rollover, and permanent failure handling using deterministic doubles.

### What Will Be Tested Next
- Value object invariants and normalization logic.
- Recurrence edge cases (DST transitions, long-running intervals, custom dates).
- Execution engine behavior (handler resolution, retry policies, telemetry hooks).
- Schedule repository integrations via contract tests (in-memory implementation + adapter examples).

### What Is NOT Tested (Yet) and Why
- Database adapters, queue drivers, and distributed locking are deferred to consuming applications; package will provide contract tests only.
- CLI/worker orchestration belongs to the application layer (Laravel Artisan, Symfony Console) and is therefore out of scope.
- Value object permutations and the recurrence/ execution engines still need dedicated suites (tracked in REQUIREMENTS.md as ⏳ Pending).

### Known Test Gaps
- Only the ScheduleManager smoke test exists; coverage for value objects, recurrence logic, and execution engine is still missing.
- Deprecation warning originates from PHPUnit 11's `expectDeprecation` helper—tests should migrate to `expectWarning`/`expectWarningMessage` before PHPUnit 12.
- Performance/stress testing is not instrumented; to be addressed after baseline unit coverage lands.

## How to Run Tests

```bash
cd packages/Scheduler
composer install            # one-time, installs phpunit/phpunit via require-dev
composer test               # runs PHPUnit using phpunit.xml.dist
composer test:coverage      # optional, enable Xdebug/PCOV first
```

## CI/CD Integration
- A lightweight GitHub Actions workflow (`scheduler-tests.yml`, planned) will execute PHPUnit on every PR touching `packages/Scheduler/**`.
- Coverage reports will feed into `TEST_SUITE_SUMMARY.md` during release preparation once the suite expands.
