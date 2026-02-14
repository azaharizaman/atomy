# HumanResourceOperations - Code Review & Next Steps Plan

## Review scope

This review focused on the `orchestrators/HumanResourceOperations` module to identify implementation gaps blocking production readiness.

## Key findings

1. **Architecture intent is clear and modern**
   - The README and architecture docs describe a strong coordinator/data-provider/rule-based orchestration model.

2. **Implementation is partial (many skeletons/placeholders)**
   - Multiple handlers, pipelines, and services still contain `TODO: Implement...` placeholders.
   - `@skeleton` markers show dependency wiring and domain logic are intentionally incomplete.

3. **Most critical gap is payroll + attendance data plumbing**
   - `PayrollDataProvider` and `AttendanceDataProvider` contain the highest concentration of skeleton logic and currently return fallback values.
   - This means payroll and attendance flows are at high risk of producing incorrect results if used in production as-is.

4. **Testing is present but execution setup is incomplete at repo root level**
   - Orchestrator tests exist, but this repository currently does not expose a default `composer test` script at orchestrator level.

## Priority plan (recommended execution order)

### Phase 1 (P0): Wire real data dependencies

**Goal:** Replace placeholder return values in DataProviders with real package contract integrations.

- Implement `AttendanceDataProvider` methods via:
  - `Nexus\Hrm` employee/schedule repositories
  - `Nexus\Attendance` repositories
- Implement `PayrollDataProvider` methods via:
  - `Nexus\Hrm` employee repository
  - `Nexus\Attendance` attendance repository
  - `Nexus\Leave` leave repository
  - `Nexus\Payroll` payroll repository
- Add constructor injection for all required interfaces and remove hardcoded fallback values.

**Exit criteria:** no stub data in provider outputs; all provider unit tests pass.

### Phase 2 (P0): Complete orchestration handlers currently marked TODO

**Goal:** Turn use-case placeholders into end-to-end orchestration logic.

Implement first (highest impact):

- Attendance use cases:
  - `RecordCheckInHandler`
  - `RecordCheckOutHandler`
  - `DetectAttendanceAnomaliesHandler`
- Leave use cases:
  - `ApplyLeaveHandler` (finish internal TODO block)
  - `ApproveLeaveHandler`
  - `CancelLeaveHandler`
  - `RecalculateLeaveBalanceHandler`
  - `ProcessCarryForwardHandler`
- Employee lifecycle use cases:
  - `RegisterEmployeeHandler`
  - `UpdateEmployeeProfileHandler`
  - `TerminateEmployeeHandler`

**Exit criteria:** each handler has orchestration flow, validation usage, error handling, and tests.

### Phase 3 (P1): Finish payroll computation path

**Goal:** Ensure payroll math and statutory calculations are production-accurate.

- Implement `PayrollCalculationService` skeleton methods, including overtime and statutory deduction computation.
- Implement `PayrollCoordinator` skeleton segment for payslip generation/integration with `Nexus\Payroll`.
- Implement payroll use cases:
  - `CalculateOTHandler`
  - `GeneratePayslipHandler`
  - `SyncPayrollAdjustmentsHandler`

**Exit criteria:** sample payroll runs produce deterministic, audited outputs and pass integration tests.

### Phase 4 (P1): Replace pipeline placeholders

**Goal:** Complete flow abstractions used by leave workflow.

- Implement:
  - `Pipelines/Leave/ApplyLeavePipeline.php`
  - `Pipelines/Leave/LeaveApprovalWorkflow.php`

**Exit criteria:** pipelines are executable and covered by tests.

### Phase 5 (P1): Strengthen automated quality gates

**Goal:** Prevent regression and ensure maintainability.

- Add/verify a stable test command (document exact command in orchestrator README).
- Expand integration tests for coordinator happy-path + failure-path scenarios.
- Add static analysis (PHPStan/Psalm) and code style checks if not already enforced in CI.

**Exit criteria:** contributors can run one documented command set locally; CI validates tests + static analysis.

## Suggested first implementation sprint (1 week)

1. Day 1-2: Complete DataProviders and dependency injection.
2. Day 3-4: Implement attendance and leave handlers.
3. Day 5: Add/repair tests for implemented flows; document run instructions.

## Definition of done for this orchestrator

- No `TODO: Implement...` placeholders in production classes.
- No `@skeleton` methods returning fake defaults.
- Attendance, leave, payroll, and employee flows covered by tests.
- README examples map to actually implemented behavior.
