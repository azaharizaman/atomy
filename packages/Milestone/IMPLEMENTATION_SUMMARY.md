# Milestone Package – Implementation Summary

**Status:** Pending Implementation

## Scope

- Milestone CRUD, approval state, deliverables
- Rule: milestone billing amount cannot exceed remaining project budget (via BudgetReservationInterface)
- Revenue recognition rule; resumable approval workflow
- All persistence via `Contracts/`; budget/revenue via interfaces for orchestrator to wire

## Checklist

- [ ] Implement `MilestoneManagerInterface` and default service
- [ ] Implement `MilestoneQueryInterface` / `MilestonePersistInterface`
- [ ] Define `BudgetReservationInterface` for remaining budget check
- [ ] Unit tests for billing vs budget and approval workflow
- [ ] REQUIREMENTS.md with Milestone-specific requirements
