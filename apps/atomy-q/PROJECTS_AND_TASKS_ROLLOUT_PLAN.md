## Atomy-Q Projects & Tasks Rollout Plan

### 1. Objectives

- **Projects as portfolios**: Group RFQs under optional projects, with budgets and health to compare planned vs. actual spend and schedule.
- **Project-scoped governance**: Project ACL that can supersede global permissions for RFQs and tasks tied to a project.
- **Task management via `Nexus\Task`**: Use tasks for both project work and RFQ workflows (approvals, follow-ups), including dependencies and schedule calculation.
- **API-first design**: Extend `apps/atomy-q/API_ENDPOINTS.md` with a clearly tagged **planned** API surface for projects and tasks.
- **Incremental rollout**: Deliver capabilities in phases while keeping the existing RFQ flows stable.

### 2. Architecture Overview

- **Layer 1 packages**
  - `Nexus\Project`: project entity, PM assignment, completion rules, client visibility.
  - `Nexus\Task`: task entity, dependencies, acyclicity, schedule calculation.
- **Layer 2 orchestrator**
  - `Nexus\ProjectManagementOperations`: uses Project, Task, TimeTracking, ResourceAllocation, Milestone, Budget and Receivable packages.
  - Laravel adapter `Nexus\Laravel\ProjectManagementOperations` to connect orchestrator contracts to Atomy-Q persistence.
- **Atomy-Q API (Laravel app)**
  - New controllers/handlers under `apps/atomy-q/API/app/Http/Controllers/Api/V1` for projects and tasks.
  - Implementation of app-specific contracts required by the Laravel adapter (`ProjectTaskIdsQueryInterface`, `ProjectBudgetQueryInterface`, `ProjectBudgetPersistInterface`, `ProjectMessagingSenderInterface`).
  - Project-aware authorization using existing identity/tenant context plus project ACLs.
- **Atomy-Q WEB (Next.js)**
  - New project routes (`/projects`, `/projects/[id]`) and RFQ pages aware of `project_id`.
  - Task UIs: user task inbox, RFQ tasks tab, project tasks tab, and optional Gantt-style views.

### 3. Phased Rollout

#### Phase 1 – Domain & API Foundations (backend-heavy, feature-flagged)

- **Goals**
  - Introduce `Nexus\Project` and `Nexus\Task` into Atomy-Q API as first-class concepts.
  - Wire `Nexus\ProjectManagementOperations` via the Laravel adapter for project health and budget, callable from the API.
  - Define and document the planned API surface for projects and tasks without breaking existing clients.

- **Scope**
  - Add optional `project_id` to the RFQ domain model and persistence, without making it required.
  - Implement L1 bindings for `ProjectManagerInterface`, `ProjectQueryInterface`, `ProjectPersistInterface`, and `TaskManagerInterface`, `TaskQueryInterface`, `TaskPersistInterface`.
  - Register the ProjectManagementOperations Laravel adapter and implement required app contracts using existing RFQ and financial data.
  - Implement internal services for:
    - Project CRUD and lifecycle, using `Nexus\Project`.
    - Task CRUD and lifecycle, using `Nexus\Task`.
    - Project health retrieval via `ProjectManagementOperationsCoordinator`.
  - Expose backend implementations behind feature flags or internal routes while finalizing the public API.
  - Update `apps/atomy-q/API_ENDPOINTS.md` with:
    - **Section 28 – Projects (planned)**.
    - **Section 29 – Tasks (planned)**.

- **Not in scope**
  - No user-facing WEB routes for projects/tasks yet.
  - Project ACL enforcement on existing endpoints remains disabled or permissive until Phase 2.

#### Phase 2 – UI Exposure & Governance Enforcement

- **Goals**
  - Surface projects and tasks in the Atomy-Q WEB app.
  - Introduce project-scoped permissions that can override general RFQ/task permissions when `project_id` is present.

- **Scope**
  - Add **Projects** section in the main navigation:
    - Projects list at `/projects` using `GET /projects`.
    - Project detail at `/projects/[id]` using `GET /projects/:id`, `/projects/:id/health`, `/projects/:id/rfqs`, `/projects/:id/tasks`, `/projects/:id/budget`.
  - Extend RFQ flows:
    - Add optional project selector in RFQ create/edit and list filters, wired to `/projects`.
    - Show project context on RFQ detail and link to the owning project.
  - Introduce project ACL UI:
    - Project Access tab on Project Detail using `GET /projects/:id/acl` and `PUT /projects/:id/acl`.
    - Model project roles (Owner, Manager, Contributor, Viewer, ClientStakeholder) and map them to permissions.
  - Enforce project-scoped permissions in the API:
    - When `project_id` is set, require matching project ACL role for RFQ and task operations instead of relying solely on global roles.
    - Keep behaviour for RFQs without `project_id` unchanged.
  - Add **Task Inbox** in WEB:
    - Global inbox page that consumes `GET /tasks`.
    - Task detail drawer backed by `GET /tasks/:id`, `PUT /tasks/:id`, `PATCH /tasks/:id/status`.
    - Entry points from dashboard widgets and RFQ/Project screens.

- **Not in scope**
  - Task dependency editing UI or Gantt/schedule visualization beyond simple lists and statuses.

#### Phase 3 – Advanced Tasks, Scheduling & Reporting

- **Goals**
  - Leverage full `Nexus\Task` capabilities (dependencies, schedule calculation) and ProjectManagementOperations health to provide richer project portfolio management.

- **Scope**
  - Enable dependency management:
    - UI for viewing and editing task dependencies using `GET /tasks/:id/dependencies` and `PUT /tasks/:id/dependencies`.
    - Guard against cycles using `DependencyGraphInterface` in the backend.
  - Add schedule previews and Gantt-style views:
    - Backend endpoint `POST /tasks/schedule/preview` wired to `ScheduleCalculatorInterface`.
    - Frontend Gantt chart or timeline components for project tasks.
  - Deepen project portfolio reporting:
    - Project dashboards summarizing planned vs. awarded vs. actual spend per project and across projects.
    - Additional report endpoints or views consuming project and task data.
  - Tighten governance:
    - Project ACLs drive visibility in global search, dashboards, and notifications.
    - Approval and risk workflows optionally generate or depend on tasks in `Nexus\Task`.

- **Not in scope**
  - Cross-tenant or cross-application project sharing; all data remains tenant-scoped.

### 4. Planned API Surface (Summary)

The following planned endpoints have been added to `apps/atomy-q/API_ENDPOINTS.md` and are the contract for implementation:

- **Projects (planned)**
  - `GET /projects` – list projects.
  - `POST /projects` – create project.
  - `GET /projects/:id` – project detail.
  - `PUT /projects/:id` – update project metadata.
  - `PATCH /projects/:id/status` – transition project status with completion rule.
  - `GET /projects/:id/health` – retrieve project health via `ProjectManagementOperationsCoordinator`.
  - `GET /projects/:id/rfqs` – RFQs under a project.
  - `GET /projects/:id/tasks` – tasks under a project.
  - `GET /projects/:id/budget` – budget vs. actuals summary.
  - `GET /projects/:id/acl` – get project ACL.
  - `PUT /projects/:id/acl` – update project ACL.

- **Tasks (planned)**
  - `GET /tasks` – user task inbox with filters.
  - `POST /tasks` – create task.
  - `GET /tasks/:id` – task detail.
  - `PUT /tasks/:id` – update task metadata.
  - `PATCH /tasks/:id/status` – update task status.
  - `GET /tasks/:id/dependencies` – read dependency graph for a task.
  - `PUT /tasks/:id/dependencies` – update dependencies.
  - `POST /tasks/schedule/preview` – preview schedule using `ScheduleCalculatorInterface`.

### 5. Risk & Mitigation Notes

- **Risk: Overly aggressive permissions changes.**
  - Mitigation: Introduce project ACL enforcement behind feature flags and observe audit logs before making strict enforcement the default.
- **Risk: Coupling RFQ lifecycle too tightly to projects.**
  - Mitigation: Keep `project_id` optional and preserve legacy flows for RFQs without a project.
- **Risk: Task overload and user confusion.**
  - Mitigation: In Phase 2, start with a focused subset of task types (e.g. approvals, RFQ follow-ups) and expand gradually with clear UI affordances.

