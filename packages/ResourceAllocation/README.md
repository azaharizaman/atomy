# Nexus\ResourceAllocation

Resource allocation and overallocation checks for the Nexus ERP ecosystem.

## Overview

The **Nexus\ResourceAllocation** package is a Layer 1 atomic package that owns allocation records (who, when, percentage or hours), the rule that allocation cannot exceed 100% per user per day (BUS-PRO-0084), overallocation detection, and double-booking prevention (REL-PRO-0402). No project/task persistence—consumers pass assignment or demand IDs. Reusable for project staffing, HR capacity, shared resources.

## Architecture

- **Layer 1 Atomic Package.** Pure PHP 8.3+. No framework dependencies.
- **Namespace:** `Nexus\ResourceAllocation`

## Key Interfaces

- `AllocationManagerInterface` – create/update allocations
- `AllocationQueryInterface` – read allocations
- `OverallocationCheckerInterface` – detect and prevent over-allocation

## Requirements (mapped)

- FUN-PRO-0571: Resource allocation and overallocation checks
- BUS-PRO-0084: Resource allocation percentage cannot exceed 100% per user per day
- REL-PRO-0402: Resource allocation MUST prevent double-booking
- PER-PRO-0357: Resource allocation view performance

## Installation

```bash
composer require nexus/resource-allocation
```

## License

MIT.
