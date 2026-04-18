# Projects And Tasks Entities

This document defines the core business entities used by the Projects and Tasks domain. It stays away from database structure on purpose. For operational flow, see [workflows.md](./workflows.md). For state semantics, see [lifecycle.md](./lifecycle.md#state-model).

## Entity 001 - Project

The project is the tenant-scoped container for related RFQs and tasks.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique project identifier. | Required; tenant-scoped. |
| `name` | Project display name. | Required. |
| `client_id` | Client reference for the project. | Required in create/update payloads. |
| `start_date` | Planned project start. | Required on create; must be on or before end date. |
| `end_date` | Planned project finish. | Required on create; must be on or after start date. |
| `project_manager_id` | Responsible manager. | Required in create/update payloads. |
| `status` | Project state. | `planning`, `active`, `on_hold`, `completed`, `cancelled`. |
| `budget_type` | Budget categorization. | Optional in updates. |
| `completion_percentage` | Progress indicator. | Numeric from `0` to `100`. |

### Relationships

- A project can own many tasks.
- A project can own many RFQs.
- ACL rows determine who can see or manage the project.

### Business Rules

- The API must not expose projects outside the current tenant.
- Status updates are explicit and validated.

## Entity 002 - Project ACL

Project ACL rows govern who can see and manage the project.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `project_id` | Target project. | Required. |
| `user_id` | User granted access. | Must belong to the same tenant. |
| `role` | ACL role. | Canonical roles: `owner`, `admin`, `editor`, `viewer`. |
| `tenant_id` | Tenant boundary. | Required. |

### Relationships

- ACL rows are evaluated by the project controller and the task controller when task records are project-scoped.

### Business Rules

- Legacy aliases are normalized to canonical ACL roles on write.
- ACL updates replace the existing set for the project.

## Entity 003 - Task

The task is the tenant-scoped actionable work item.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `id` | Unique task identifier. | Required; tenant-scoped. |
| `title` | Task summary. | Required. |
| `description` | Task detail text. | Optional. |
| `status` | Task state. | `pending`, `in_progress`, `completed`, `cancelled`. |
| `priority` | Task urgency. | `low`, `medium`, `high`, `critical`. |
| `due_date` | Target completion date. | Optional. |
| `completed_at` | Completion timestamp. | Set when the task is marked complete. |
| `project_id` | Optional project ownership. | Must belong to the current tenant when present. |
| `assignee_ids` | Assigned users. | Array of tenant user IDs. |
| `predecessor_ids` | Dependency chain. | Array of task IDs within the same tenant. |

### Relationships

- A task may belong to a project or remain unscoped.
- A task can depend on multiple predecessor tasks.

### Business Rules

- Project-scoped tasks inherit project ACL visibility checks.
- Dependency updates must stay acyclic.

## Entity 004 - Project Health Snapshot

The health snapshot is the derived metric set returned by the project health endpoint.

### Core Properties

| Property | Business meaning | Rules / allowed values |
|---|---|---|
| `overall_score` | Composite project health score. | Derived. |
| `labor` | Labor-health slice. | Includes actual hours and percentage. |
| `expense` | Budget-expense slice. | Includes health percentage. |
| `timeline` | Timeline-health slice. | Includes completion and milestone counts. |

### Relationships

- The snapshot is derived from the project-management coordinator.

### Business Rules

- The snapshot is read-only and should not be treated as a persisted project state.

## Related Docs

- Workflow details: [workflows.md](./workflows.md)
- Project and task lifecycle: [lifecycle.md](./lifecycle.md)
