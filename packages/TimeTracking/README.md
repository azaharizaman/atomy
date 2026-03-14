# Nexus\TimeTracking

Time entry and timesheet rules (hours, approval, immutability) for the Nexus ERP ecosystem.

## Overview

The **Nexus\TimeTracking** package is a Layer 1 atomic package that owns timesheet entry (date, hours, optional work item ID), validation (e.g. hours not negative, max 24h/day/user), approval state machine, immutability of approved timesheets, and rules for billing rate default. No project/task persistence—only IDs and interfaces for work item and approver. Reusable for any domain that logs time.

## Architecture

- **Layer 1 Atomic Package.** Pure PHP 8.3+. No framework dependencies.
- **Namespace:** `Nexus\TimeTracking`

## Key Interfaces

- `TimesheetManagerInterface` – create/update timesheet lifecycle
- `TimesheetQueryInterface` – read timesheets
- `TimesheetPersistInterface` – persistence contract
- `TimesheetApprovalInterface` – approval workflow

## Requirements (mapped)

- FUN-PRO-0254/0566: Time tracking and timesheet entry
- FUN-PRO-0575: Timesheet approval workflow
- BUS-PRO-0056: Timesheet hours cannot be negative or exceed 24h/day/user
- BUS-PRO-0063: Approved timesheets are immutable
- BUS-PRO-0070: Task actual hours = sum of approved timesheet hours
- BUS-PRO-0101/0125, REL-PRO-0396, SEC-PRO-0460

## Installation

```bash
composer require nexus/time-tracking
```

## License

MIT.
