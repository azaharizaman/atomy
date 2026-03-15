# Nexus\Project

Core project entity and lifecycle rules for the Nexus ERP ecosystem.

## Overview

The **Nexus\Project** package is a Layer 1 atomic package that owns project metadata (name, client, start/end, budget type, status), project manager assignment, completion rules (e.g. status cannot be "completed" if incomplete tasks exist), and client visibility rule. Used in project context; orchestrator enforces "task belongs to project" by passing project context.

## Architecture

- **Layer 1 Atomic Package.** Pure PHP 8.3+. No framework dependencies.
- **Namespace:** `Nexus\Project`

## Key Interfaces

- `ProjectManagerInterface` – project lifecycle
- `ProjectQueryInterface` – read projects
- `ProjectPersistInterface` – persistence contract (CQRS)

## Requirements (mapped)

- BUS-PRO-0042: A project MUST have a project manager assigned
- FUN-PRO-0564/0236: Create project with basic details
- BUS-PRO-0096: Project status cannot be "completed" if there are incomplete tasks
- BUS-PRO-0106: Client stakeholders can view only their own projects
- SEC: Tenant isolation, RBAC, client portal (project scope)

## Installation

```bash
composer require nexus/project
```

## License

MIT.
