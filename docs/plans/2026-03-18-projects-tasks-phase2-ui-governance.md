# Projects & Tasks Phase 2 – Implementation on New Branch

**Goal:** Implement all remaining items from the rollout plan on a new branch: (1) Project create/edit UI and RFQ project context in WEB, (2) API-driven RFQ filtering by project_id, (3) Project ACL storage and permission enforcement, (4) WEB Task Inbox with task detail.

**Branch:** Create and work on a new branch (e.g. `feature/projects-tasks-phase2-ui-governance`) from current `main`. All changes below are done on that branch.

**Architecture:** Keep API-first: extend existing Laravel controllers and Next.js app; add one new migration for ACL; add optional permission checks when `project_id` is set on RFQ/task access.

**Tech stack:** Laravel (API), Next.js (WEB), existing hooks and API client, PHPUnit, React Query.

---

## Part 0: Branch and docs

- Create branch from `main`: `git checkout -b feature/projects-tasks-phase2-ui-governance` (or agreed name).
- Add implementation plan to repo: save this plan as `docs/plans/2026-03-18-projects-tasks-phase2-ui-governance.md` for execution reference.

---

## Part 1: Make projects usable (WEB UI)

### 1.1 Project create UI (POST /projects)

- **API:** Already implemented in ProjectController::store; no change.
- **WEB:** In projects/page.tsx: add "Create project" button and form (modal or inline). Form fields: name, client_id, start_date, end_date, project_manager_id. On submit: api.post('/projects', payload), invalidate projects query. Add useCreateProject mutation hook.
- **Test:** Automated tests required (unit + integration/E2E as appropriate). This is a prerequisite for Part 3 (ACL) to prevent regressions.

### 1.2 Project edit and status (PUT /projects/:id, PATCH /projects/:id/status)

- **API:** Already implemented; no change.
- **WEB:** In projects/[projectId]/page.tsx: add "Edit" button and edit form; add status dropdown (draft/active/on_hold/completed/cancelled). Call api.put and api.patch, invalidate queries.
- **Test:** Automated tests required. Mutation hooks must have automated unit tests + integration tests covering ACL/resource governance scenarios (permission escalation, project_id set/unset, and unauthorized access). These tests must run in CI as required checks.

### 1.3 Show project context on RFQ detail (read-only)

- **API (Option A):** Eager-load `project` in the RFQ show/overview queries (tenant-scoped) and expose `project_name` on the response (via `RfqResource` or controller mapping) as `$rfq->project?->name`.
- **WEB:** In use-rfq.ts ensure `RfqDetail` and normalizeRfq include `projectId` (nullable) and `projectName` (string). When `project_id` is null set `projectId = null` and `projectName = "Unassigned"` (or "No project"). In RFQ layout/overview render "Project: Unassigned" with no link when `projectId` is null; render "Project: <name>" as a clickable link to `/projects/[projectId]` only when non-null.

---

## Part 2: API-driven RFQ filtering by project_id

### 2.1 GET /rfqs accepts project_id

- **API:** In RfqController::index add: if ($projectId = $request->query('project_id')) { $query->where('project_id', $projectId); }
- **Test:** Feature test GET /rfqs?project_id=<id> returns only RFQs with that project_id.

**Rollout warning:** This introduces a security window if Part 2 ships before Part 3 (ACL). The `project_id` query param must not be exposed in any UI (dropdowns, links, deep-links) until Part 3 is deployed.
- **Timeline expectation:** Part 2 (filtering) and Part 3 (ACL enforcement) should be deployed together or with minimal delay.
- **Mitigations if decoupled:** Gate behind a feature flag; restrict the `project_id` filter to internal/admin users in `RfqController::index` until ACL is live; add an integration test ensuring unauthenticated/non-admin requests cannot use `project_id` while the flag is off.

### 2.2 WEB passes project_id to API

- **WEB:** In use-rfqs.ts add projectId?: string to UseRfqsParams and pass in params to api.get('/rfqs', { params }). In rfqs/page.tsx pass projectId to useRfqs and remove client-side filtering; use server response.

---

## Part 3: Project ACL storage and permission enforcement

### 3.1 ACL persistence

- **Schema:** Migration create_project_acl_table: project_id, user_id, role, tenant_id, unique (tenant_id, project_id, user_id). Add explicit indexes for frequent lookups: index on project_id, index on user_id, index on tenant_id, and consider composite indexes for (user_id, tenant_id) and (project_id, role) if role-based lookups are common. Keep the unique constraint (it creates a unique index).
- **Model:** ProjectAcl (or ProjectMember) in app/Models.
- **API:** getAcl: load from project_acl, return roles. updateAcl: validate roles array, sync project_acl, return updated ACL.
- **Test:** Feature tests for GET/PUT /projects/:id/acl.

### 3.2 Permission enforcement when project_id is present

- **Helper:** Service that given (tenant_id, user_id, project_id) returns whether user has at least viewer role. Role hierarchy (highest → lowest): owner > admin > editor > viewer > none. “At least viewer” means any of {viewer, editor, admin, owner}. Empty ACL = no access (strict).
- **Backfill strategy:** For existing RFQs/Tasks with `project_id` populated and empty ACLs, run a backfill migration/script to create ACL rows for the responsible principals (e.g., owner/manager) before enabling enforcement; until backfill completes, do not enable enforcement in production.
- **RFQ/Task:** When resource.project_id is non-null, check project ACL before proceeding; return 404 on no access (avoid leaking existence). When resource.project_id is NULL, skip project ACL enforcement and treat the resource as “Unassigned”.
- **Test:** Feature test: project_id set, user not in ACL gets 404; user in ACL gets 200.

---

## Part 4: Task Inbox (WEB)

### 4.1 Task Inbox page and list

- **WEB:** Add app/(dashboard)/tasks/page.tsx. Hook use-tasks.ts: useTasks(params?) calling GET /tasks. Tasks page: table/list from useTasks(), row click opens task detail.
- **Nav:** Add "Task Inbox" or "Tasks" link to /tasks in sidebar.

### 4.2 Task detail and status (drawer/modal)

- **WEB:** use-task.ts: useTask(taskId) for GET /tasks/:id; mutation for PATCH /tasks/:id/status. Drawer/modal on task select: show detail and status control; on change call PATCH and invalidate queries.

### 4.3 Entry point from nav

- Sidebar "Tasks" or "Task Inbox" pointing to /tasks.

---

## Order of implementation (recommended)

1. Part 0 – branch + save plan.
2. Part 2 – RFQ filtering (API + WEB params).
3. Part 1 – Projects UI (create, edit, status, project on RFQ detail).
4. Part 3 – ACL:
   - (1) schema migration + DB changes (migration)
   - (2) ACL domain model + API contracts (model)
   - (3) implement and deploy persistence for getAcl and updateAcl + run integration tests (getAcl/updateAcl persistence)
   - (4) verify persistence via acceptance/manual checks and automated coverage (persistence verification gate)
   - (5) enable enforcement in code paths (enforcement)
5. Part 4 – Task Inbox (page, use-tasks, task detail drawer, status update).
