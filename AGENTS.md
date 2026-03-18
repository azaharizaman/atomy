# AGENTS FOUNDATIONAL MANDATES & AGENTIC GUIDELINES

This document serves as the absolute source of truth for Coding CLI agents working on the Atomy (Nexus) project. These mandates take precedence over general defaults.

## 🏗️ Core Architecture & Standards

All development MUST strictly adhere to the project's **Three-Layer Architecture** and **Coding Standards**.

- **Source of Truth**: Refer to [ARCHITECTURE.md](docs/project/ARCHITECTURE.md) for detailed architectural rules, coding standards (PHP 8.3), and security guidelines.
- **Package Reference**: Consult [NEXUS_PACKAGES_REFERENCE.md](docs/project/NEXUS_PACKAGES_REFERENCE.md) before implementing any feature to prevent duplication.

### Quick Mandates:
- **Strict Typing**: `declare(strict_types=1);` is mandatory in every file.
- **Immutability**: Use `final readonly class` for services and `readonly` properties for VOs.
- **Dependency Injection**: Constructor injection with specific interfaces. NEVER use generic `object`.
- **Three-Layer Rule**:
    - **Layer 1 (packages/)**: Pure PHP, no framework dependencies.
    - **Layer 2 (orchestrators/)**: Cross-package coordination, own interfaces, stateless.
    - **Layer 3 (adapters/)**: Framework-specific implementation (Laravel).
- **Anti-Patterns**: NO `use Illuminate\*` in Layers 1 or 2. NO direct imports of L1 in L2. NO synthetic return values on failure.

## 🤖 Agentic Workflow & Tool Engagement

- **Discovery First**: Map relevant packages before changing code. Use `grep_search` and `ls -R`.
- **Interface First**: Define the contract in `src/Contracts/` before implementation.
- **Validation**: Reproduce bugs with a test case first. Write happy-path and edge-case tests for new features.
- **Documentation**: Update `IMPLEMENTATION_SUMMARY.md` in the affected package after every change.
- **Multi-Tenancy**: Always filter by `tenantId`. Guard against cross-tenant data leakage.
- **Zero-Check**: Guard against division by zero and empty array operations.

## 👥 Multi-Agent Coordination

Nexus is a large system. Agents should follow these coordination patterns:

- **Architect Agent**: Responsible for design, interface definitions, and enforcing boundaries.
- **Developer Agent**: Responsible for implementation and logic. Follows Architect's contracts.
- **QA Agent**: Responsible for testing, verification, and regression analysis.
- **Maintenance Agent**: Handles dependency updates, documentation syncing, and refactoring.

## 🚫 Common Anti-Patterns to Avoid

### Type Safety
- **AVOID**: Using generic `object` or `?object` for dependencies. Use specific interfaces.

### PHP 8.3 Readonly
- **AVOID**: Mutable arrays in readonly classes. Use `ArrayObject` for internal mutation if necessary, or native arrays for true immutability.
- **AVOID**: Using `private readonly` on promoted properties (readonly implies private).

### Method Implementation
- **AVOID**: Methods that ignore passed parameters.
- **AVOID**: Delete methods that only log and don't remove from storage.

### Algorithms
- **AVOID**: Division without zero-check.
- **AVOID**: Index access without normalization (use modulo).

### Multi-Tenancy
- **AVOID**: Ignoring tenant ID in queries.
- **AVOID**: Returning 403 when a resource exists but belongs to another tenant. Use tenant-scoped existence checks only (e.g. `where('tenant_id', $tenantId)->where('id', $id)->exists()`); return **404** for both "not found" and "wrong tenant" so cross-tenant resource existence is not leaked.

### Exception Handling
- **AVOID**: Returning synthetic IDs (e.g., `QUO-' . uniqid()`) instead of throwing exceptions when a service is unavailable.

## ✅ Quality Checklist
- [ ] All dependencies use specific interface types (not `object`)
- [ ] All classes use `readonly` when appropriate (PHP 8.3+)
- [ ] All methods actually apply their parameters
- [ ] All delete methods remove from storage
- [ ] Division operations have zero guards
- [ ] Array indices are normalized with modulo
- [ ] Tenant filtering is applied in multi-tenant contexts
- [ ] Failures throw domain exceptions, not return synthetic values

## Agent Loopback
For every PR review comment resolved, the agent must reflect on its own errors and anti-patterns and update this document with what was learned from PR review comments.

### Learnings
After each resolved PR review comment, append a new entry below using this template and link back to the PR/issue.

**Template:**
- **Date:** YYYY-MM-DD
- **PR/Issue ID:** (link or number)
- **Summary of mistake:** Brief description of what was wrong.
- **Root cause:** Why it happened (e.g. missed rule, wrong assumption).
- **Action taken:** What was changed (code/doc).
- **Follow-up tasks:** Any remaining work or checks.

**Example:**
- **Date:** 2026-03-18
- **PR/Issue ID:** #123
- **Summary of mistake:** RFQ controller accepted unvalidated `estimated_value` and `savings_percentage`, allowing requests to bypass validation.
- **Root cause:** Validation rules array omitted those fields; controller used `$request->input()` instead of `$validated`.
- **Action taken:** Added `nullable|numeric|min:0` and `nullable|numeric|min:0|max:100` to `rfqValidationRules` and switched store/update to use `$validated` for those fields.
- **Follow-up tasks:** None.

---

- **Date:** 2026-03-18
- **PR/Issue ID:** [#296](https://github.com/azaharizaman/atomy/pull/296)
- **Summary of mistake:** Project and task controllers returned 403 when a resource existed but belonged to another tenant (two-step check: load by id, then compare tenant_id). This allowed any authenticated tenant to probe IDs and learn whether a project/task existed in another tenant—a tenant-isolation information leak.
- **Root cause:** Prioritised "strict" status codes (403 for forbidden) over the existing design that collapsed both "not found" and "wrong tenant" to 404 to avoid leaking existence.
- **Action taken:** Reverted to tenant-scoped existence checks only (`where('tenant_id', $tenantId)->where('id', $id)->exists()`); return 404 when the check fails. Updated ProjectsApiTest and TasksApiTest to expect 404 for cross-tenant access. Removed unused `$userA` in ProjectsApiTest. In ApiEndpointsProjectsTasksTest, added `setUp()` to resolve path and read file once into `$apiEndpointsContent`, and asserted file existence in setUp so all tests use cached content and fail clearly if the file is missing.
- **Follow-up tasks:** None.

---

- **Date:** 2026-03-18
- **PR/Issue ID:** Projects/Tasks Phase 2 PR review
- **Summary of mistake:** ACL and project context features shipped with gaps that could break tenant isolation and create inconsistent behavior across API/UI: ACL reads/writes were not consistently tenant-scoped; `GET /rfqs?project_id=...` applied the filter without enforcing project ACL; RFQ overview missed the ACL guard; access denials returned 403 (existence leak); ACL migration lacked tenant-scoped uniqueness, `user_id` FK, and supporting indexes; seed fallback filtering diverged from live API; UI lacked task drawer error state + keyboard-accessible overlay; mutation mapping returned synthetic empty strings; implementation plan docs were ambiguous on `project_name`, role semantics, rollout risk, and `NULL project_id` policy.
- **Root cause:** Incomplete threat-modeling for multi-tenancy on “secondary” queries (delete/read-back, list filters, fallback data paths) plus mismatched conventions between controllers and docs (403 vs 404, unclear `NULL project_id` handling). Some frontend hooks optimized for “happy path” and masked malformed payloads.
- **Action taken:** Scoped ACL delete/read queries by `tenant_id` and added payload validation (`distinct`) to prevent duplicate principals. Enforced project ACL in RFQ listing when `project_id` is provided and in RFQ overview; changed ACL denial to 404 to avoid existence leaks. Implemented Option A for `project_name` by tenant-scoped eager-loading and deriving `project_name` from the relationship. Updated ACL migration for tenant-scoped uniqueness, added `user_id` FK with cascade delete, and added indexes aligned to query patterns. Made `ProjectAclService` `final readonly class`. Fixed frontend local status filtering, ensured seed filtering parity by supporting `projectId`, added task drawer error UI + accessible overlay dismissal, and made create-project mapping fail fast on invalid payloads. Updated Phase 2 plan doc to explicitly document these semantics and rollout mitigations.
- **Follow-up tasks:** Ensure automated tests cover: tenant-scoped ACL persistence, RFQ `project_id` filter ACL enforcement, and UI error/a11y states; keep docs updated when enforcement semantics change.

---

- **Date:** 2026-03-18
- **PR/Issue ID:** PR review (WEB test and hook fixes)
- **Summary of mistake:** (1) RFQ list and project-health numeric fields used `Number(...)` without rejecting empty string/null, so missing API values became 0 instead of undefined; (2) Playwright route handlers used a single captured `origin` so CORS headers could be wrong when request origin differed; (3) screen-smoke spec had duplicate framenavigated listeners and an unused screens array; (4) `.gitignore` used `**/.last-run.json`, which could hide unrelated files.
- **Root cause:** Optimising for “something numeric” instead of “missing vs present”; route handlers written with a single mutable origin updated after first navigation; test structure grew without refactoring to a shared origin util and screens-driven loop.
- **Action taken:** Added `parseOptionalNumber` in use-rfqs (undefined for null/empty/whitespace, finite-only) and used it for vendorsCount/quotesCount. Updated use-project-health `toNum` to return undefined for null/undefined/empty string and to use `Number.isFinite`. In smoke.spec.ts introduced `getRequestOrigin(route)` and `fulfillJsonRoute(route, body, status)` and used request-derived origin in all route handlers. In screen-smoke.spec.ts added `trackOriginOnNavigation(page, originRef)`, made stubProjectsApi/stubTasksApi take optional baseUrl (from `PLAYWRIGHT_BASE_URL`), refactored the test to iterate over a screens array (Reporting, Approval Queue, Settings/Users & Roles included), and removed duplicate framenavigated logic. Scoped `.gitignore` to `test-results/.last-run.json`. In use-rfqs.test.ts kept `NEXT_PUBLIC_USE_MOCKS` set at module load and restore in afterAll (moving to beforeAll caused hook timeouts).
- **Follow-up tasks:** None.
