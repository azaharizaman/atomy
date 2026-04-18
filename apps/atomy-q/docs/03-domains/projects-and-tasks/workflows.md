# Projects And Tasks Workflows

This file covers the operational business flow for the Projects and Tasks domain. It does not describe the project or task state model; that belongs in [lifecycle.md](./lifecycle.md).

## Workflow Access

- The entire surface is feature-flagged.
- Users can only see project-scoped work that they are authorized to view through ACL or via unscoped task entries.

## Workflow 001 - Create And Update A Project

### Trigger

A user creates a new project or edits an existing one.

### Steps

1. The API validates the project payload and the date range.
2. Project creation uses idempotency so repeated submissions do not create duplicate records.
3. The project summary is persisted in the project service.
4. Reads return the tenant-scoped project payload, including the current status and core dates.
5. Status updates are validated against the accepted project status set.

### Outputs

- Project record
- Project detail payload
- Optional status update payload

### Failure Handling

- Disabled project features return `404`.
- Invalid date ranges return validation errors.
- Missing project records return `404`.

## Workflow 002 - Manage Project ACL And Visibility

### Trigger

An administrator opens the project ACL screen or updates project visibility.

### Steps

1. The API confirms the project belongs to the current tenant.
2. The ACL read endpoint returns the current user-role mapping.
3. The ACL update endpoint verifies the current user can manage ACLs.
4. The controller replaces the ACL rows for the project in one transaction.
5. Legacy role names are normalized to the canonical ACL role set.

### Outputs

- Current ACL rows
- Updated ACL rows

### Failure Handling

- Unauthorized ACL management returns `403`.
- Cross-tenant or missing projects return `404`.
- Duplicate ACL users are rejected by validation.

## Workflow 003 - Track Project Health And Budget

### Trigger

A user opens the project health or project budget view.

### Steps

1. The API checks that the project feature is enabled and the project belongs to the tenant.
2. The health coordinator calculates labor, expense, and timeline metrics.
3. The budget endpoint sums RFQ estimated values and signed-off award amounts for the project.

### Outputs

- Health score and breakdown
- Budgeted versus actual amounts
- Currency code derived from signed-off awards when present

### Failure Handling

- Missing project records return `404`.
- Missing signed-off awards do not break the budget view; the actual amount remains zero.

## Workflow 004 - Create And Maintain Tasks

### Trigger

A user creates, edits, re-statuses, or re-links a task.

### Steps

1. The API validates the task payload and tenant-owned project link, if present.
2. Task creation persists the task in a transaction and then links it to the project, if supplied.
3. Task updates apply title, description, priority, due date, and assignees.
4. Status updates write the new task status and stamp `completed_at` when completed.
5. Dependency updates validate that predecessor IDs belong to the tenant and do not create cycles.

### Outputs

- Task detail payload
- Dependency list
- Updated status payload

### Failure Handling

- Unknown task IDs return `404`.
- Unknown tenant task IDs in the dependency list return `422`.
- Circular dependencies return `422`.
- Project-scoped task changes are still protected by ACL checks when the task belongs to a project.

## Workflow 005 - Schedule Preview

### Trigger

A user opens the schedule preview entry point.

### Steps

1. The API confirms the feature is enabled and the tenant context exists.
2. The endpoint returns a planned placeholder response instead of a real scheduler output.

### Outputs

- Placeholder schedule-preview message

### Failure Handling

- The route is intentionally not a production planner yet.

## Domains Involved

- Projects and Tasks
- RFQ
- Auth
- Users and Roles
