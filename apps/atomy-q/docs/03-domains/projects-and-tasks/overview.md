# Projects And Tasks Overview

## Purpose

The Projects and Tasks domain covers the optional contextual work-management features in Atomy-Q: projects, task assignment, project ACL, project health, budget snapshots, task dependencies, and the planned schedule-preview surface.

The domain is not required to complete the RFQ-to-award alpha journey, but when it is enabled it must still obey tenant scoping and project ACL rules.

Operational workflows are documented in [workflows.md](./workflows.md).
The project/task state model is documented in [lifecycle.md](./lifecycle.md).
Core entity definitions are documented in [entities.md](./entities.md).

## Current Decisions

- The whole surface is feature-flagged.
- `features.projects` and `features.tasks` can disable the corresponding routes with a `404`.
- Project visibility is controlled by tenant-scoped ACL entries.
- Tasks attached to a project must still pass project ACL checks when the project is set.
- Schedule preview is present as a planned surface, not a fully implemented planner.

## Core Entities

- `Project`: the tenant-scoped work container
- `Task`: the tenant-scoped actionable work item
- `Project ACL`: the per-user visibility and management mapping for a project
- `Project health snapshot`: the derived project score and health breakdown
- `Task dependency graph`: the predecessor relationships between tasks

## Inputs

- Authenticated tenant and user context
- Project create/update/status payloads
- Project ACL payloads
- Task create/update/status/dependency payloads
- Optional query parameters such as `assignee_id`

## Outputs

- Project listings and detail payloads
- Project health and budget summaries
- Task listings and detail payloads
- Project ACL rows
- Task dependency payloads
- Placeholder schedule-preview response

## Dependencies

### Other Atomy-Q domains

- **RFQ** - projects can own RFQs and RFQ summaries use project data.
- **Auth** - project and task access is tenant- and user-scoped.
- **Users and Roles** - ACL entries point at tenant users.

### Nexus packages

- `Nexus\Project`
- `Nexus\Task`
- `Nexus\ProjectManagementOperations`

### External dependencies

- Laravel
- Feature flags
- Idempotency support
- Project ACL persistence
