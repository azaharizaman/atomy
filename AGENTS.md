# AGENTS FOUNDATIONAL MANDATES & AGENTIC GUIDELINES

This document serves as the absolute source of truth for Coding CLI agents working on the Atomy (Nexus) project. These mandates take precedence over general defaults.

## ⚡ Build, Lint & Test Commands

### PHP (Composer-based packages)
```bash
# Run all tests in a package (from package directory)
composer install
./vendor/bin/phpunit

# Run single test file
./vendor/bin/phpunit tests/Unit/Services/MyServiceTest.php

# Run single test method
./vendor/bin/phpunit --filter testMyMethod

# Run static analysis (PHPStan)
./vendor/bin/phpstan analyse src --level=max

# Run with coverage
./vendor/bin/phpunit --coverage-html build/coverage
```

### Frontend (React/Next.js in apps/atomy-q/)
```bash
cd apps/atomy-q/WEB

# Install dependencies
npm install

# Run development server
npm run dev

# Build production
npm run build

# Lint
npm run lint

# Unit tests (Vitest)
npm run test:unit
npm run test:unit:watch  # watch mode

# Run single unit test file
npx vitest run src/hooks/useMyHook.test.ts

# E2E tests (Playwright)
npm run test:e2e
npm run test:e2e:headed      # visible browser
npm run test:e2e:ui          # Playwright UI
npm run test:e2e:debug       # Debug mode

# Generate API client from OpenAPI
npm run generate:api
```

### Root-level E2E tests
```bash
npm run test:e2e             # All Playwright tests
npm run test:e2e:laravel    # Laravel-specific tests
npm run test:e2e:api        # API tests
npm run test:e2e:report     # View HTML report
```

## 📝 Code Style Guidelines

**Detailed standards are in [ARCHITECTURE.md](docs/project/ARCHITECTURE.md) Section 6.**

### Quick Reference
- **Strict Types**: `declare(strict_types=1);` mandatory in every PHP file
- **Immutability**: Use `final readonly class` for services; `readonly` properties for VOs
- **Dependency Injection**: Constructor injection with specific interfaces. NEVER use generic `object`
- **Interfaces over Classes**: Depend on interfaces, never concrete implementations
- **No Framework Coupling**: Layers 1 & 2 must NOT use `Illuminate\*` or `Symfony\*`
- **Error Handling**: Use domain-specific exceptions, never generic `\Exception`
- **CQRS**: Split repositories into `*QueryInterface` and `*PersistInterface`

### Naming Conventions
Refer to [NAMING_CONVENTIONS.md](docs/project/NAMING_CONVENTIONS.md)

### Imports
- Use fully-qualified class names or explicit `use` statements
- Group imports: `php` built-ins → `psr` interfaces → `nexus` packages → project local
- Sort alphabetically within groups

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

## 🔍 Code review (superpowers: requesting-code-review)

When dispatching the **code-reviewer** subagent for changes that touch **`apps/atomy-q/`**, the reviewer **must** walk the checklist in [`docs/superpowers/CODE_REVIEW_GUIDELINES_ATOMY_Q.md`](docs/superpowers/CODE_REVIEW_GUIDELINES_ATOMY_Q.md) and append **`### Atomy-Q guideline pass`** to the review. The superpowers template **`requesting-code-review/code-reviewer.md`** (in your skills install) embeds this requirement.

---

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
- **PR/Issue ID:** [#298](https://github.com/azaharizaman/atomy/pull/298)
- **Summary of mistake:** (1) RFQ list and project-health numeric fields used `Number(...)` without rejecting empty string/null, so missing API values became 0 instead of undefined; (2) Playwright route handlers used a single captured `origin` so CORS headers could be wrong when request origin differed; (3) screen-smoke spec had duplicate framenavigated listeners and an unused screens array; (4) `.gitignore` used `**/.last-run.json`, which could hide unrelated files.
- **Root cause:** Optimising for “something numeric” instead of “missing vs present”; route handlers written with a single mutable origin updated after first navigation; test structure grew without refactoring to a shared origin util and screens-driven loop.
- **Action taken:** Added `parseOptionalNumber` in use-rfqs (undefined for null/empty/whitespace, finite-only) and used it for vendorsCount/quotesCount. Updated use-project-health `toNum` to return undefined for null/undefined/empty string and to use `Number.isFinite`. In smoke.spec.ts introduced `getRequestOrigin(route)` and `fulfillJsonRoute(route, body, status)` and used request-derived origin in all route handlers. In screen-smoke.spec.ts added `trackOriginOnNavigation(page, originRef)`, made stubProjectsApi/stubTasksApi take optional baseUrl (from `PLAYWRIGHT_BASE_URL`), refactored the test to iterate over a screens array (Reporting, Approval Queue, Settings/Users & Roles included), and removed duplicate framenavigated logic. Scoped `.gitignore` to `test-results/.last-run.json`. In use-rfqs.test.ts kept `NEXT_PUBLIC_USE_MOCKS` set at module load and restore in afterAll (moving to beforeAll caused hook timeouts).
- **Follow-up tasks:** None.

---

- **Date:** 2026-03-20
- **PR/Issue ID:** PolicyEngine package review
- **Summary of mistake:** Initial PolicyEngine implementation left multiple consistency and hardening gaps: stale doc symbol names, sensitive identifiers in exception messages, concrete-type dependency instead of validator interface, unsafe index access in range parsing, action-shadowing in request merge order, weak JSON decode normalization/error handling, and non-canonical ID VO normalization.
- **Root cause:** Prioritised feature delivery speed over a final architecture/security hardening pass; relied on happy-path correctness and missed cross-tenant leakage, deterministic normalization, and documentation parity checks.
- **Action taken:** Aligned docs with shipped symbols, introduced `PolicyValidatorInterface`, switched tenant mismatch behavior to not-found semantics in evaluator, wrapped fingerprint serialization with domain exception mapping, hardened JSON decode rule validation and error normalization, canonicalized IDs (`PolicyId`, `PolicyVersion`, `RuleId`, `TenantId`), normalized `ConditionGroup` mode storage, fixed `PolicyRequest` action precedence, added missing comparator and decoder tests, and updated package reference docs.
- **Follow-up tasks:** Keep a mandatory pre-PR checklist for (1) tenant-leak review, (2) VO normalization determinism, (3) interface-first DI, (4) decode/encode exception mapping, and (5) docs-symbol parity against current code.

---

- **Date:** 2026-03-20
- **PR/Issue ID:** PolicyEngine follow-up review
- **Summary of mistake:** A follow-up review still found unused parameters/signatures, incomplete runtime-safe constructor validation for typed arrays, and partially-qualified contract references in central documentation.
- **Root cause:** First hardening pass fixed primary security/runtime issues but missed secondary consistency cleanup (dead params, API signature simplification, and doc namespace strictness) that should be part of the same quality gate.
- **Action taken:** Added explicit `ConditionGroup` item type validation, removed unused parameters from `PolicyNotFound::for` and `TenantMismatch::between` signatures and updated call sites, aligned package reference table to fully-qualified PolicyEngine contracts, and added regression test coverage for invalid `ConditionGroup` items.
- **Follow-up tasks:** Add a mandatory "API signature hygiene" and "doc fully-qualified symbol pass" checklist step before final commit.

---

- **Date:** 2026-03-22
- **PR/Issue ID:** Idempotency package PR review (feat/idempotency-package)
- **Summary of mistake:** (1) README usage example let `InProgress` fall through to `complete()`; (2) non-atomic `find`+`save` allowed double `FirstExecution` under concurrency; (3) deleting an expired pending row without an attempt token let stale `complete()`/`fail()` match the replacement by the same `(tenant, operation, clientKey, fingerprint)`; (4) failed-record retry path deleted before fingerprint comparison, allowing a different payload to wipe the failed record; (5) `SystemClock` used the default timezone; (6) docs/plan described pipe-delimited store keys while `InMemoryIdempotencyStore` used JSON tuples; (7) magic numbers and duplicated bounded-string validation; (8) monolithic store contract where CQRS-style split helps adapters.
- **Root cause:** Layer 1 examples focused on the happy path; concurrency and TTL edge cases were under-specified; documentation was not re-synced after implementation chose JSON keys; token binding for superseded attempts was not modeled.
- **Action taken:** README now branches Replay / InProgress / FirstExecution so only `FirstExecution` calls `complete()`, and documents `AttemptToken` on `complete`/`fail`. Added `claimPending()` on `IdempotencyPersistInterface`, `AttemptToken` on `IdempotencyRecord`, and validation in `complete`/`fail`. Failed-retry path compares fingerprint before delete. `SystemClock::now()` returns UTC. Plan/README/IMPLEMENTATION_SUMMARY/VALUATION_MATRIX aligned on JSON-encoded keys; `IdempotencyPolicy::DEFAULT_PENDING_TTL_SECONDS`; `BoundedStringValidator` shared by VOs; split `IdempotencyQueryInterface` / `IdempotencyPersistInterface` with composite `IdempotencyStoreInterface`. Tests added for stale attempt token and fingerprint-before-delete on failed retry.
- **Follow-up tasks:** DB adapters must implement `claimPending` with a real unique constraint on `(tenant_id, operation_ref, client_key)` and document UTC timestamps.

---

- **Date:** 2026-03-31
- **PR/Issue ID:** [#340](https://github.com/azaharizaman/atomy/pull/340)
- **Summary of mistake:** The normalize workspace review thread stayed open even after mock-mode query gating was added and verified in the branch.
- **Root cause:** I rechecked the code but did not sync the resolved thread state on GitHub once the fix was confirmed.
- **Action taken:** Verified `useNormalizationSourceLines(..., { enabled: !useMocks })` in `normalize/page.tsx` and resolved the corresponding PR thread.
- **Follow-up tasks:** Keep PR thread status aligned with code verification before moving on.

---

- **Date:** 2026-03-31
- **PR/Issue ID:** [#340](https://github.com/azaharizaman/atomy/pull/340)
- **Summary of mistake:** The normalize row-checkbox accessibility thread stayed open even though the branch already added labels, checked state, and selection handlers.
- **Root cause:** I verified the implementation locally but did not immediately mark the GitHub review thread as resolved.
- **Action taken:** Confirmed the checkbox controls in `normalize/page.tsx` are now named and controlled, then resolved the inline review thread.
- **Follow-up tasks:** Resolve accessibility follow-up threads at the same time the fix is validated.

---

- **Date:** 2026-03-31
- **PR/Issue ID:** [#340](https://github.com/azaharizaman/atomy/pull/340)
- **Summary of mistake:** The quote-intake normalization review query thread remained open after the mock-mode `enabled: !useMocks` guard had already been applied.
- **Root cause:** The implementation was correct, but I did not sync the review thread state after verification.
- **Action taken:** Reconfirmed `useNormalizationReview(rfqId, { enabled: !useMocks })` in `quote-intake/page.tsx` and resolved the review thread.
- **Follow-up tasks:** Check GitHub thread state whenever a fix is already present in the branch.

---

- **Date:** 2026-03-31
- **PR/Issue ID:** [#340](https://github.com/azaharizaman/atomy/pull/340)
- **Summary of mistake:** The debrief persistence review thread stayed open after the branch added a persistent `Debrief` record and comparison snapshot storage.
- **Root cause:** I confirmed the data model change in code but did not sync the review thread resolution immediately.
- **Action taken:** Reverified debrief persistence in `AwardController.php` and resolved the remaining PR thread.
- **Follow-up tasks:** Keep audit-data fixes and review-thread cleanup paired in the same pass.

---

- **Date:** 2026-04-04
- **PR/Issue ID:** RFQ lifecycle PR review follow-up
- **Summary of mistake:** The RFQ lifecycle slice shipped with several reviewable gaps clustered around boundary integrity: Layer 2 depended directly on a Layer 1 transition-policy contract, duplicate RFQs were persisted before line-item copy was guaranteed, reminder state (`reminded_at`) was written before notification dispatch succeeded, draft patching used `??` merges that discarded explicit null clears, bulk actions trusted preloaded records without confirming they matched the requested RFQ ids, and invitation delivery/logging referenced `channel` without carrying that field through the DTO/adapter mapping. The review pass also exposed a related contract mismatch where `estimated_value` is validated as nullable in HTTP requests while the RFQ schema still stores it as non-null.
- **Root cause:** I optimized for happy-path feature completion and assumed surrounding contracts were “close enough” once tests passed. That skipped a final pass for transactional boundaries, patch semantics, DTO-to-adapter parity, and architecture layering. I also failed to cross-check nullable request contracts against actual persistence nullability.
- **Action taken:** Added orchestrator-local sourcing policy and transaction contracts, wrapped duplicate orchestration in one transaction boundary, made duplicate-number generation and insert atomic with retry on unique collisions, changed reminder flow to dispatch before persisting `reminded_at`, added field-presence tracking to `SaveRfqDraftCommand`, validated preloaded bulk-action ids against the requested set, carried invitation `channel` through DTO/query/persist mapping, replaced the reminder log stub with a real Notifier-backed email dispatch, expanded unit/feature regression coverage, and added sourcing-specific review checklist items in `docs/superpowers/CODE_REVIEW_GUIDELINES_ATOMY_Q.md`.
- **Follow-up tasks:** Before future RFQ lifecycle PRs, run an explicit checklist for (1) orchestrator-local interface boundaries, (2) transaction scope across every multi-write flow, (3) send-then-persist ordering for I/O side effects, (4) field-presence handling for nullable patch APIs, (5) DTO field parity with logging/notifier usage, and (6) request-validation nullability versus database schema nullability.

---

- **Date:** 2026-04-05
- **PR/Issue ID:** RFQ lifecycle PR review follow-up 2
- **Summary of mistake:** The reminder and transaction adapter follow-up still had framework-edge and notification hygiene gaps: the DB transaction adapter passed generic callables straight into `DB::transaction()` instead of converting them to `Closure`, reminder template fallback logic used `??` so blank strings bypassed defaults, notification template data carried unnecessary `vendor_email` PII, recipient locale/timezone were hardcoded, recipient identifiers were not uniquely anchored on the invitation id, and implementation summaries still listed already-finished adapter wiring as pending.
- **Root cause:** I focused on the main workflow semantics and did not finish a final adapter/API polish pass for framework contracts, empty-string behavior, notification identity/privacy, and doc drift against the actual bindings already present in `AppServiceProvider`.
- **Action taken:** Wrapped sourcing transaction callbacks with `Closure::fromCallable()`, changed reminder notification fallbacks to treat blank values as missing, removed `vendor_email` from the reminder template payload, made reminder recipients use runtime locale/timezone fallbacks and invitation-id-based identifiers, clarified the intentionally unused duplicate line-item parameter for static analysis, and updated both sourcing implementation summaries plus the Atomy-Q review checklist.
- **Follow-up tasks:** Before merging notification or adapter changes, explicitly check (1) framework API type expectations versus declared interface types, (2) `??` versus blank-string fallback behavior, (3) notification payload PII minimization, (4) unique identifiers for preference-aware notifiables, and (5) whether `IMPLEMENTATION_SUMMARY.md` follow-ups still match the current branch reality.

---

- **Date:** 2026-04-05
- **PR/Issue ID:** RFQ lifecycle PR review follow-up 3
- **Summary of mistake:** The RFQ lifecycle persistence adapter still had two hidden boundary issues: duplicate numbering depended on lexicographic `rfq_number` ordering instead of the numeric suffix, and duplicate-number exhaustion leaked infra-level `QueryException`/`RuntimeException` rather than a sourcing-operations domain exception. A related review pass also caught that the adapter’s defensive unsupported-action branch returned `0`, test contracts around reminder email output were too loose, and documentation/test style drift had started creeping back in.
- **Root cause:** I verified the happy-path transaction fix but did not pressure-test larger suffix values, retry-exhaustion behavior, or the adapter’s fallback/error branches. I also let a reviewer-driven “unused parameter” concern obscure the intended Layer 2 ownership of line-item copy orchestration instead of explicitly documenting that boundary.
- **Action taken:** Switched RFQ number lookup to numeric-suffix ordering with prefix-aware parsing, wrapped exhausted duplicate-number failures in a new `DuplicateRfqNumberException`, made unsupported adapter bulk actions throw a domain exception instead of returning `0`, strengthened adapter tests for reminder email shape and duplicate-number behavior, renamed the PHPUnit methods to camelCase, refreshed the sourcing implementation summary wording, and extended Atomy-Q review guidance to cover numeric ordering, domain-exception mapping, and coordinator-owned copy boundaries.
- **Follow-up tasks:** Before future persistence changes, explicitly review (1) numeric ordering versus lexicographic ordering for composite identifiers, (2) retry-exhaustion exception mapping, (3) adapter dead-code fallback branches returning synthetic values, (4) email/notification payload contract assertions, and (5) whether a suggested “fix” would incorrectly pull coordinator-owned orchestration into a single adapter.

---

- **Date:** 2026-04-08
- **PR/Issue ID:** [#349](https://github.com/azaharizaman/atomy/pull/349)
- **Summary of mistake:** Identity adapter follow-up left avoidable review friction: account-state writes were not consistently tenant-scoped for defense-in-depth, and closure decisions (repository delete contract behavior and MFA metadata logging scope) were not explicitly dispositioned in the plan.
- **Root cause:** I closed functional requirements first, then deferred contract-hardening and disposition documentation, which forced reviewers to infer intent from implementation details.
- **Action taken:** Added tenant-scoped account-state mutation query path in `EloquentUserRepository`, re-ran Gap 7 feature and adapter permission tests, and updated the PR349 closure plan with explicit compatibility and scope dispositions plus verification evidence.
- **Follow-up tasks:** For future identity review closures, require a final pass that includes (1) write-path tenant-scope checks, (2) explicit compatibility disposition for contract-adjacent suggestions, and (3) verification-gate command reproducibility from the current workspace layout.

---

- **Date:** 2026-04-08
- **PR/Issue ID:** [#349](https://github.com/azaharizaman/atomy/pull/349)
- **Summary of mistake:** Review-thread closure lag hid several unresolved hardening items across session validation, permission matching, MFA data mapping, and tenant-scoped query paths.
- **Root cause:** I relied on prior batch status instead of revalidating every still-open thread against current HEAD and then syncing both code and thread state in one pass.
- **Action taken:** Implemented tenant-safe middleware/session checks, hardened MFA/user auth persistence paths, fixed permission matching/query edge cases, restored interface-first controller dependency for MFA challenge store, added missing MFA mapper and encryption cast, updated affected call sites, re-ran Gap 7 verification commands, and resolved fixed PR threads while documenting one deferred schema-level FK redesign thread.
- **Follow-up tasks:** Before claiming PR closure, run an explicit unresolved-thread sweep and require each thread to be either (1) code-fixed and resolved, or (2) intentionally left open with a posted rationale and a tracked follow-up plan.

---

- **Date:** 2026-04-09
- **PR/Issue ID:** [#349](https://github.com/azaharizaman/atomy/pull/349)
- **Summary of mistake:** Identity follow-up still had contract and scoping drift: adapter RBAC hydration did not consistently carry tenant context, Layer 2 auth phpdoc referenced a Layer 1 enum type, permission create path allowed blank names to reach persistence, and TOTP tests had non-deterministic verification checks that could mask period-handling bugs.
- **Root cause:** I accepted earlier "working" behavior without re-checking contract boundaries and determinism assumptions against the latest reviewer concerns and current code paths.
- **Action taken:** Threaded tenant context through `IdentityOperationsAdapter::mapUserToArray` into permission/role lookups, updated `AuthenticatorInterface` status typing to Layer-2-safe scalar docs, added explicit non-empty name validation in `EloquentPermissionRepository::create`, removed dead `findUserOrFail` from `EloquentUserRepository`, and hardened `TotpManagerTest` with fixed timestamps, mutated-invalid code assertions, and a boundary-sensitive custom-period check.
- **Follow-up tasks:** For identity review closures, add a mandatory pass for (1) tenant context propagation in all adapter hydration paths, (2) Layer 2 contract isolation from Layer 1 types, and (3) deterministic time-based test vectors that can fail the wrong implementation.

---

- **Date:** 2026-04-15
- **PR/Issue ID:** WEB PR review follow-up (seed/live fallback hygiene)
- **Summary of mistake:** Several hooks mixed live-mode and mock-mode behaviors by silently falling back to seed data (or fabricated counts) when live responses were unavailable, while seed project IDs also diverged from the RFQ seed project ID pool.
- **Root cause:** I optimized for resilient demo behavior without enforcing a strict separation between mock-only data paths and live-query error semantics, and I did not keep seed ID pools centrally aligned.
- **Action taken:** Aligned `SEED_PROJECTS` IDs to the RFQ generator `01J...` pool; removed fabricated pending-approval counts; moved approvals seed usage into explicit mock branches; removed hidden seed fallback from RFQ overview live path; removed duplicate readiness/live refetch fallback; kept seed fallbacks only where explicitly required for mock mode (quote submissions, RFQ invitations); and cleaned related import/typing/test-format issues.
- **Follow-up tasks:** Before future frontend merges, run a checklist for (1) mock-vs-live branch separation, (2) no synthetic fallback values in live hooks, (3) canonical seed ID reuse across generators/accessors, and (4) nullable hook return types reflected in consumers.

---

- **Date:** 2026-04-15
- **PR/Issue ID:** WEB PR review follow-up (readiness/mock gating and seed pool completeness)
- **Summary of mistake:** Comparison-run readiness handling treated mock-mode `fetchLiveOrFail` undefined as a hard error, seed projects still missed two IDs from the RFQ generator pool, and navigation child-record badges retained hardcoded placeholder counts.
- **Root cause:** I applied fail-loud behavior without preserving an explicit mock-mode guard for readiness and did not re-check all project IDs or placeholder UI counts after the previous remediation pass.
- **Action taken:** Added mock-mode query disabling in `use-comparison-run-readiness`, kept live-mode fail-loud behavior for undefined responses, added the two missing seed project IDs, removed unsupported hardcoded child-record badges in `active-record-menu`, and tightened implementation summary wording to explicitly document `use-comparison-run.ts` fetch-retry behavior across mock vs live mode.
- **Follow-up tasks:** For future review closures, run a final matrix check across (1) mock-mode enabled behavior, (2) live-mode fail-loud behavior, (3) central seed ID pool completeness, and (4) removal or wiring of placeholder UI counters before declaring comments resolved.
