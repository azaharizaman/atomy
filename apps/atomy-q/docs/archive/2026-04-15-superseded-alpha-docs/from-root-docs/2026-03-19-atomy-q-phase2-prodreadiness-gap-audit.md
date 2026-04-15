# Atomy‑Q Phase 2 + Production Readiness — Gap Audit (Verified)

**Date:** 2026-03-19  
**Scope:** Projects/Tasks Phase 2 + IdentityOperations production readiness (OIDC + Email)  

This document is a *freeze* of what is **already implemented** vs **still missing** with file-level evidence, to avoid re-implementing completed work.

---

## A) Projects/Tasks Phase 2 (Atomy‑Q)

### A1) Projects UI (WEB)

- ✅ **Implemented**: Projects list + create form  
  - `apps/atomy-q/WEB/src/app/(dashboard)/projects/page.tsx`
- ✅ **Implemented**: Project detail + edit + status update  
  - `apps/atomy-q/WEB/src/app/(dashboard)/projects/[projectId]/page.tsx`
- ✅ **Implemented**: Navigation entry  
  - `apps/atomy-q/WEB/src/app/(dashboard)/layout.tsx`
- ❌ **Missing**: Automated tests for Projects UI  
  - No `projects/*.test.*` present under `apps/atomy-q/WEB/src/app/(dashboard)/projects/`

### A2) RFQ project context (WEB + API)

- ✅ **Implemented**: RFQ show includes `project_name` (tenant-scoped eager-load)  
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php`
- ✅ **Implemented**: WEB maps `project_id` + `project_name`  
  - `apps/atomy-q/WEB/src/hooks/use-rfq.ts`
- ✅ **Implemented**: RFQ layout shows project link when assigned  
  - `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/layout.tsx`
- ❌ **Missing**: Consistent “Project: Unassigned” display when `project_id` is null  
  - Layout currently hides the row entirely when unassigned

### A3) RFQ filtering by `project_id`

- ✅ **Implemented**: `GET /rfqs?project_id=...` server filtering with ACL guard  
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php::index()`
- ✅ **Implemented**: WEB passes `project_id` param (Projects dropdown filter)  
  - `apps/atomy-q/WEB/src/hooks/use-rfqs.ts` and `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/page.tsx`
- ⚠️ **Gap (tests)**: No explicit feature test that `GET /rfqs?project_id=...` is denied (404) when user lacks project ACL  
  - Closest: `apps/atomy-q/API/tests/Feature/ProjectAclTest.php` covers RFQ show denial; index denial not clearly covered

### A4) Project ACL (storage + enforcement + UI)

- ✅ **Implemented**: ACL migration  
  - `apps/atomy-q/API/database/migrations/2026_03_18_000001_create_project_acl_table.php`
- ✅ **Implemented**: API endpoints `GET/PUT /projects/:id/acl`  
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/ProjectController.php`
- ✅ **Implemented**: Persistence tests for ACL endpoints  
  - `apps/atomy-q/API/tests/Feature/ProjectAclTest.php`
- ✅ **Implemented**: Enforcement when `project_id` is set (RFQs + Task show/update/status) returning **404** on denial  
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php`  
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/TaskController.php`
- ❌ **Missing**: WEB “Project Access” tab to manage ACL  
  - No UI consuming `/projects/:id/acl`
- ❌ **Missing**: Role hierarchy semantics (owner/admin/editor/viewer)  
  - `apps/atomy-q/API/app/Services/Project/ProjectAclService.php` currently checks membership only (role unused)
- ❌ **Missing (blocker)**: `GET /tasks` list enforces project ACL per-row  
  - Current behavior: list is tenant-scoped only; project-linked tasks may be listed to users not in project ACL
- ❌ **Missing**: Feature test for task ACL denial (404) for `GET /tasks/:id` / status updates  
  - `apps/atomy-q/API/tests/Feature/TasksApiTest.php` does not assert ACL denial cases

### A5) Project budget endpoint

- ❌ **Missing**: Real budget computation (endpoint exists but returns zeros)  
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/ProjectController.php::budget()`

---

## B) IdentityOperations production readiness (OIDC + Email + CI)

### B1) CI workflow for IdentityOperations

- ❌ **Missing**: `.github/workflows/identity-ops.yml` running tests in `orchestrators/IdentityOperations` with coverage  
  - Existing workflow focuses on `packages/**`: `.github/workflows/phpunit-atomy-api.yml`

### B2) OIDC SSO (Atomy‑Q + IdentityOperations)

- ✅ **Exists**: Identity contract `SsoProviderInterface`  
  - `packages/Identity/src/Contracts/SsoProviderInterface.php`
- ✅ **Exists**: OIDC provider implementation in SSO package  
  - `packages/SSO/src/Providers/OidcProvider.php`
- ❌ **Missing**: Atomy‑Q API SSO endpoint implementation (currently `501`)  
  - `apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php::sso()`
- ❌ **Missing**: IdentityOperations coordinator SSO orchestration (redirect/callback)  
  - `orchestrators/IdentityOperations/src/Coordinators/UserAuthenticationCoordinator.php` currently covers password+refresh only
- ❌ **Missing**: Groups/claims → roles mapping logic (tenant-scoped)

### B3) Real email delivery via Notifier + async queue

- ✅ **Exists**: Identity adapter currently calls `NotificationManagerInterface::send(...)` synchronously  
  - `adapters/Laravel/Identity/src/Adapters/IdentityOperationsAdapter.php`
- ✅ **Exists**: Postmark config hooks in Atomy‑Q API  
  - `apps/atomy-q/API/config/mail.php` and `apps/atomy-q/API/config/services.php`
- ❌ **Missing**: Laravel Notifier adapter package + `PostmarkEmailAdapter`  
  - No `adapters/Laravel/Notifier/...` in repo
- ❌ **Missing**: Queue job wiring for async notification sending (docs mention jobs; code not present)

