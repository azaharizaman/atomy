# Projects And Tasks Lifecycle

This file describes the project and task state models themselves. The operational create, ACL, health, and dependency flows are documented in [workflows.md](./workflows.md).

## Purpose

The Projects and Tasks lifecycle covers two related but separate state machines:

- the project state machine
- the task state machine

The controller layer validates the accepted state strings, while the package layer owns deeper business rules.

## Project State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `planning` | The project is being defined and is not yet active work. | `ProjectController::store()`, `ProjectController::updateStatus()` |
| `active` | The project is in normal execution. | `ProjectController::updateStatus()` |
| `on_hold` | The project is paused. | `ProjectController::updateStatus()` |
| `completed` | The project has finished. | `ProjectController::updateStatus()` |
| `cancelled` | The project has been stopped. | `ProjectController::updateStatus()` |

### Project Transition Notes

- The API validates the target state but does not expose a separate transition graph in the controller.
- A project’s dates, budget type, and completion percentage can change independently of the state.
- A project feature flag can hide the whole surface, which is not a lifecycle state.

## Task State Model

| State | Meaning in code | Where it appears |
|---|---|---|
| `pending` | The task exists but has not started. | `TaskController::store()`, `TaskController::updateStatus()` |
| `in_progress` | The task is actively being worked on. | `TaskController::updateStatus()` |
| `completed` | The task has been finished. | `TaskController::updateStatus()` |
| `cancelled` | The task is no longer being worked. | `TaskController::updateStatus()` |

### Task Transition Notes

- Completing a task stamps `completed_at`.
- Priority, due date, assignees, and predecessors can evolve without changing the task state.
- Dependency validation happens on write; the lifecycle itself does not store the graph separately.

## ACL And Visibility Rules

- Project ACL is not a lifecycle state, but it directly affects who can observe the project and any project-scoped tasks.
- Unscoped tasks can still be listed by the tenant, but project-scoped tasks require project visibility checks.

## Related Docs

- Project and task workflows: [workflows.md](./workflows.md)
- Project and task entities: [entities.md](./entities.md)
