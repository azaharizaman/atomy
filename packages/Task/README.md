# Nexus\Task

Task entity, assignment, dependencies, and schedule calculation (Gantt-support) for the Nexus ERP ecosystem.

## Overview

The **Nexus\Task** package is a Layer 1 atomic package that owns task CRUD, assignees, priority, due dates, predecessor/dependency graph, acyclicity checks, and optional schedule calculation (early/late dates for Gantt). It has no concept of "project" inside the package—callers associate tasks to a project or other parent via IDs. Reusable for support tickets, campaign tasks, HR goals, and project work.

## Architecture

- **Layer 1 Atomic Package.** Pure PHP 8.3+. No framework dependencies.
- **Namespace:** `Nexus\Task`
- **Purity:** No `Illuminate\*` or framework-specific imports in `src/`.

## Key Interfaces

- `TaskManagerInterface` – create/update task lifecycle
- `TaskQueryInterface` – read tasks by criteria
- `TaskPersistInterface` – persistence contract (CQRS write side)
- `DependencyGraphInterface` – predecessor graph and acyclicity
- `ScheduleCalculatorInterface` – early/late dates for Gantt

## Requirements (mapped)

- FUN-PRO-0242/0565: Create and manage tasks with assignees and priority
- FUN-PRO-0570: Task dependencies (predecessor) and Gantt calc
- BUS-PRO-0090: Task dependencies must not create circular references
- PER-PRO-0335/0348: Task creation and Gantt rendering performance

## Installation

```bash
composer require nexus/task
```

## License

MIT.
