# Project Client Nullability Follow-Up Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the persisted `ProjectController::SELF_CLIENT_ID` sentinel with nullable `projects.client_id` semantics.

**Architecture:** Keep the current sentinel behavior until a database migration can safely convert existing rows. The follow-up migration should make `projects.client_id` nullable, backfill existing `"self"` values to `NULL`, and update API/query/reporting code to handle `NULL` as the internal-project marker.

**Tech Stack:** Laravel migrations, Eloquent/query builder, PHPUnit feature tests, Atomy-Q API project services.

---

### Task 1: Add nullable client_id migration

**Files:**
- Create: `apps/atomy-q/API/database/migrations/YYYY_MM_DD_HHMMSS_make_projects_client_id_nullable.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ProjectController.php`
- Modify: `apps/atomy-q/API/app/Services/Project/AtomyProjectQuery.php`
- Modify: `apps/atomy-q/API/app/Services/Project/AtomyProjectPersist.php`

- [ ] Add a migration that converts existing `projects.client_id = 'self'` rows to `NULL`.
- [ ] Change the `projects.client_id` column to nullable.
- [ ] Update create/update persistence so missing internal-client projects store `NULL`.
- [ ] Update query filters so client filters distinguish explicit client IDs from internal-client `NULL` rows.

### Task 2: Update downstream consumers

**Files:**
- Modify reports/exports that read `projects.client_id`.
- Modify joins or filters that compare against `ProjectController::SELF_CLIENT_ID`.
- Modify ACL checks if they join or filter through project client identity.

- [ ] Replace literal `"self"` comparisons with nullable semantics.
- [ ] Ensure `LIKE` filters do not accidentally include or exclude internal projects because `client_id` is `NULL`.
- [ ] Add regression coverage for internal projects with `client_id = NULL`.

### Task 3: Remove sentinel debt

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ProjectController.php`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`

- [ ] Remove `ProjectController::SELF_CLIENT_ID` once no code persists or compares against `"self"`.
- [ ] Update implementation notes to document nullable client semantics.
- [ ] Run `cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/ProjectsApiTest.php`.
