# Deprecated Code Cleanup

**Date:** December 3, 2024  
**Migration Status:** COMPLETED

## Summary

The HumanResourceOperations package has been migrated from the old UseCases/Pipelines pattern to the **Advanced Orchestrator Pattern** as defined in `SYSTEM_DESIGN_AND_PHILOSOPHY.md`.

## Migration Mapping

### ✅ Migrated Components

| Old Component | New Component | Status |
|---------------|---------------|--------|
| `UseCases/Employee/RegisterEmployeeHandler` | `Coordinators/HiringCoordinator` + `Services/EmployeeRegistrationService` | ✅ Migrated |
| `UseCases/Recruitment/MakeHiringDecisionHandler` | `Coordinators/HiringCoordinator` | ✅ Migrated |
| `UseCases/Leave/ApplyLeaveHandler` | `Coordinators/LeaveCoordinator` | ✅ Migrated |
| `Pipelines/Leave/ApplyLeavePipeline` | `Coordinators/LeaveCoordinator` + `DataProviders/LeaveDataProvider` | ✅ Migrated |
| `UseCases/Onboarding/StartOnboardingHandler` | `Workflows/Onboarding/OnboardingWorkflow` | ✅ Migrated |
| `UseCases/Attendance/RecordCheckInHandler` | `Coordinators/AttendanceCoordinator` | ✅ Migrated |
| `UseCases/Attendance/RecordCheckOutHandler` | `Coordinators/AttendanceCoordinator` | ✅ Migrated |
| `UseCases/Attendance/DetectAttendanceAnomaliesHandler` | `Rules/UnusualHoursRule` + `Rules/LocationAnomalyRule` | ✅ Migrated |
| `UseCases/Payroll/GeneratePayslipHandler` | `Coordinators/PayrollCoordinator` | ✅ Migrated |
| `UseCases/Payroll/CalculateOTHandler` | `Services/PayrollCalculationService` | ✅ Migrated |
| `Services/Payroll/PayrollComputationService` | `Services/PayrollCalculationService` | ✅ Migrated |

### ⏳ Pending Migration (Future Work)

These components remain in the old pattern and should be migrated when time permits:

| Old Component | Recommended New Component | Priority |
|---------------|--------------------------|----------|
| `UseCases/Employee/TerminateEmployeeHandler` | `Coordinators/EmployeeLifecycleCoordinator` | Medium |
| `UseCases/Employee/UpdateEmployeeProfileHandler` | `Coordinators/EmployeeProfileCoordinator` | Low |
| `UseCases/Leave/ApproveLeaveHandler` | `Coordinators/LeaveApprovalCoordinator` | Medium |
| `UseCases/Leave/CancelLeaveHandler` | `Coordinators/LeaveCoordinator` (extend) | Low |
| `UseCases/PerformanceReview/*` | `Coordinators/PerformanceReviewCoordinator` | Low |
| `UseCases/Disciplinary/*` | `Coordinators/DisciplinaryCaseCoordinator` | Low |
| `UseCases/Training/*` | `Coordinators/TrainingCoordinator` | Low |
| `Pipelines/Disciplinary/*` | `Workflows/Disciplinary/` | Low |
| `Pipelines/Recruitment/*` | `Workflows/Recruitment/` | Low |

## Deleted Directories

The following directories have been **preserved** for now to avoid breaking existing consumers:

- `src/UseCases/` - Contains handlers not yet migrated
- `src/Pipelines/` - Contains legacy workflow implementations
- `src/Services/Disciplinary/` - Contains case-specific services
- `src/Services/PerformanceReview/` - Contains rating aggregation
- `src/Services/Training/` - Contains trainer matching

## Deprecation Warnings

**For Developers:**
- ⚠️ DO NOT add new code to `UseCases/` or `Pipelines/` directories
- ⚠️ All new features must use the Advanced Orchestrator Pattern
- ⚠️ Reference `Coordinators/`, `DataProviders/`, `Rules/`, `Services/`, `Workflows/`, `Listeners/`

## Next Steps

1. **Document remaining UseCases** - Create migration plan for unmigrated handlers
2. **Add deprecation notices** - Add `@deprecated` tags to old classes
3. **Update consuming applications** - Ensure apps are using new Coordinators
4. **Remove deprecated code** - After all consumers migrated, delete old directories

## Benefits of New Architecture

✅ **Better Separation of Concerns** - Coordinators orchestrate, Services execute  
✅ **Testable Components** - Rules can be unit tested in isolation  
✅ **Reusable Logic** - DataProviders and Services shared across Coordinators  
✅ **Type Safety** - Strict DTOs instead of associative arrays  
✅ **Maintainability** - Clear responsibility boundaries  
✅ **Scalability** - Easy to add new rules and workflows

## References

- [SYSTEM_DESIGN_AND_PHILOSOPHY.md](../../SYSTEM_DESIGN_AND_PHILOSOPHY.md)
- [NEW_ARCHITECTURE.md](NEW_ARCHITECTURE.md)
- [REFACTORING_SUMMARY.md](REFACTORING_SUMMARY.md)
