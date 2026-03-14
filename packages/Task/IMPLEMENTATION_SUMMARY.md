# Task Package – Implementation Summary

**Status:** Pending Implementation

## Scope

- Task CRUD, assignees, priority, due dates
- Dependency graph and acyclicity check (BUS-PRO-0090)
- Optional schedule calculation for Gantt (early/late dates)
- All persistence and external needs via `Contracts/`

## Checklist

- [ ] Implement `TaskManagerInterface` and default service
- [ ] Implement `TaskQueryInterface` / `TaskPersistInterface` (CQRS)
- [ ] Implement `DependencyGraphInterface` and cycle detection
- [ ] Implement `ScheduleCalculatorInterface` for Gantt
- [ ] Unit tests for cycle detection and schedule calc
- [ ] REQUIREMENTS.md populated with Task-specific requirements
