# Nexus\Milestone

Milestone entity, approvals, deliverables, and billing/revenue rules for the Nexus ERP ecosystem.

## Overview

The **Nexus\Milestone** package is a Layer 1 atomic package that owns milestone CRUD, approval state, deliverables, the rule that milestone billing amount cannot exceed remaining project budget (BUS-PRO-0077), revenue recognition rule (BUS-PRO-0111), and resumable approval workflow (REL-PRO-0408). Budget "remaining" and "revenue update" are expressed via interfaces; the orchestrator wires to Budget/Receivable.

## Architecture

- **Layer 1 Atomic Package.** Pure PHP 8.3+. No framework dependencies.
- **Namespace:** `Nexus\Milestone`

## Key Interfaces

- `MilestoneManagerInterface` – milestone lifecycle
- `MilestoneQueryInterface` – read milestones
- `MilestonePersistInterface` – persistence
- `BudgetReservationInterface` – remaining budget check (implemented by adapter/orchestrator)

## Requirements (mapped)

- FUN-PRO-0569: Milestones with approvals and deliverables
- BUS-PRO-0077: Milestone billing amount cannot exceed remaining project budget
- BUS-PRO-0111: Revenue recognition (fixed-price, % completion or milestone approval)
- REL-PRO-0408: Milestone approval workflow resumable after failure
- REL-PRO-0390: ACID for financial calculations

## Installation

```bash
composer require nexus/milestone
```

## License

MIT.
