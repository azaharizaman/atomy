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
- **Test:** Optional E2E or manual.

### 1.2 Project edit and status (PUT /projects/:id, PATCH /projects/:id/status)

- **API:** Already implemented; no change.
- **WEB:** In projects/[projectId]/page.tsx: add "Edit" button and edit form; add status dropdown (draft/active/on_hold/completed/cancelled). Call api.put and api.patch, invalidate queries.
- **Test:** Manual or unit test for mutation hooks.

### 1.3 Show project context on RFQ detail (read-only)

- **API:** Single-RFQ returns project_id via RfqResource; add project_name when project_id set if needed.
- **WEB:** In use-rfq.ts add projectId (and projectName) to RfqDetail and normalizeRfq. In RFQ layout or overview show "Project: <name>" with link to /projects/[projectId] when set.

---

## Part 2: API-driven RFQ filtering by project_id

### 2.1 GET /rfqs accepts project_id

- **API:** In RfqController::index add: if ($projectId = $request->query('project_id')) { $query->where('project_id', $projectId); }
- **Test:** Feature test GET /rfqs?project_id=<id> returns only RFQs with that project_id.

### 2.2 WEB passes project_id to API

- **WEB:** In use-rfqs.ts add projectId?: string to UseRfqsParams and pass in params to api.get('/rfqs', { params }). In rfqs/page.tsx pass projectId to useRfqs and remove client-side filtering; use server response.

---

## Part 3: Project ACL storage and permission enforcement

### 3.1 ACL persistence

- **Schema:** Migration create_project_acl_table: project_id, user_id, role, tenant_id, unique (project_id, user_id).
- **Model:** ProjectAcl (or ProjectMember) in app/Models.
- **API:** getAcl: load from project_acl, return roles. updateAcl: validate roles array, sync project_acl, return updated ACL.
- **Test:** Feature tests for GET/PUT /projects/:id/acl.

### 3.2 Permission enforcement when project_id is present

- **Helper:** Service that given (tenant_id, user_id, project_id) returns whether user has at least viewer role. Empty ACL = no access (strict).
- **RFQ/Task:** When resource has project_id set, check project ACL before proceeding; 403 if no access.
- **Test:** Feature test: project_id set, user not in ACL gets 403; user in ACL gets 200.

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
4. Part 3 – ACL (migration, model, getAcl/updateAcl persistence, then enforcement).
5. Part 4 – Task Inbox (page, use-tasks, task detail drawer, status update).
