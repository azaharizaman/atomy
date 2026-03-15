# Requirements: Nexus\Task

Layer 1 atomic package: task entity, assignment, dependencies, Gantt-support. Reusable outside project context.

| Package Namespace | Requirements Type | Code | Requirement Statements | Status |
|-------------------|-------------------|------|------------------------|--------|
| `Nexus\Task` | Business Requirements | BUS-PRO-0049 | A task MUST belong to a project (enforced by orchestrator) | |
| `Nexus\Task` | Business Requirements | BUS-PRO-0090 | Task dependencies must not create circular references | |
| `Nexus\Task` | Functional Requirement | FUN-PRO-0242 | Create and manage tasks | |
| `Nexus\Task` | Functional Requirement | FUN-PRO-0565 | Create and manage tasks with assignees + priority | |
| `Nexus\Task` | Functional Requirement | FUN-PRO-0570 | Task dependencies (predecessor) & Gantt-support (calc) | |
| `Nexus\Task` | Functional Requirement | FUN-PRO-0248 | Task assignment and notifications | |
| `Nexus\Task` | Functional Requirement | FUN-PRO-0260 | My Tasks view | |
| `Nexus\Task` | Functional Requirement | FUN-PRO-0567 | My Tasks (view all tasks assigned to user) | |
| `Nexus\Task` | Performance Requirement | PER-PRO-0335 | Task creation and assignment | |
| `Nexus\Task` | Performance Requirement | PER-PRO-0348 | Gantt chart rendering (100 tasks) | |
| `Nexus\Task` | User Story | USE-PRO-0516 | As a project manager, I want to create tasks within a project with descriptions, assignees, and due dates | |
| `Nexus\Task` | User Story | USE-PRO-0523 | As a team member, I want to view all tasks assigned to me across all projects in one place | |
| `Nexus\Task` | User Story | USE-PRO-0543 | As a project manager, I want to mark tasks as complete and track project completion percentage | |
| `Nexus\Task` | User Story | USE-PRO-0549 | As a team member, I want to receive notifications when tasks are assigned to me or deadlines are approaching | |
