# Atomy-Q Design-Partner Alpha Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a **tenant-safe vertical slice** of Atomy-Q (Laravel API + Next.js WEB) ready for **1–3 external buying-org design partners**: email/password + password reset, OpenAPI-driven client contract, real RFQ core + overview/activity, buyer-side quote intake + minimal comparison, **one** documented approval path, Playwright + API isolation tests, staging/partner documentation.

**Architecture:** Follow the repo **three-layer** rules for new Nexus-facing code (`AGENTS.md`, `docs/project/ARCHITECTURE.md`). Keep Atomy-Q API controllers thin: validate → delegate to services/models → JSON. WEB uses TanStack Query for server state; prefer **generated** API types from OpenAPI once P0 lands. Tenant isolation: **404** for missing or cross-tenant resources (no existence leak). **SSO and vendor portal are out of scope** for alpha per `[docs/superpowers/specs/2026-03-20-atomy-q-alpha-release-brainstorm-design.md](../specs/2026-03-20-atomy-q-alpha-release-brainstorm-design.md)`.

**Tech Stack:** Laravel 12, PHP 8.3 (`declare(strict_types=1);`), PostgreSQL, JWT (`firebase/php-jwt`), Next.js 16 App Router, TanStack Query, Axios (interim) → OpenAPI-generated client, Vitest, Playwright, PHPUnit.

**Related plan:** Quote ingestion / normalization / comparison depth is partially covered in `[2026-03-19-quote-ingestion-normalization-pilot-implementation-plan.md](2026-03-19-quote-ingestion-normalization-pilot-implementation-plan.md)`. **Reuse or complete** that work for alpha P2; this alpha plan adds **cross-cutting gates** (OpenAPI, forgot-password, approvals UI slice, partner doc, E2E).

---

## Status (2026-03-20)

### Blocker — full Playwright E2E on Fedora (and other unsupported hosts)

Playwright does **not** officially support Fedora. Browser installs use a fallback build (e.g. Ubuntu), which is fragile for **full** `npm run test:e2e` confidence. **Treat complete E2E suite execution as blocked** on this OS until run on an **officially supported** environment (e.g. Ubuntu LTS in CI or a local Ubuntu machine).

**Unblock checklist:**

1. On Ubuntu (or CI): `cd apps/atomy-q/WEB && npm run test:e2e:install && npm run test:e2e`
2. Optionally against staging: `PLAYWRIGHT_BASE_URL=… npm run test:e2e`
3. Record pass/fail in PR or release notes before inviting design partners.

**Partial verification done elsewhere:** `tests/auth.spec.ts` (login, forgot-password, reset-password mocks) was validated with Chromium installed; full suite not claimed green on Fedora.

---

### Accomplishments (plan closure — remaining items)

| Area | Done |
|------|------|
| **Task 6 Step 3** | Confirmed no extra pilot tasks needed: intake → final comparison → approval gate covered by `ComparisonSnapshotWorkflowTest` + API README pilot notes. |
| **Task 8 Step 5** | WEB `/forgot-password` and `/reset-password` already existed; added Playwright coverage in `apps/atomy-q/WEB/tests/auth.spec.ts` using the same CORS stub pattern as `smoke.spec.ts` (`getRequestOrigin` + `fulfillJsonRoute`). |
| **Task 12** | Added `apps/atomy-q/ALPHA_DESIGN_PARTNER_SUPPORTED_FLOWS.md` (identity, buyer-only, SSO not required, `comparison_approval` variant, stubs, verification commands). Linked from `apps/atomy-q/API/README.md` and `apps/atomy-q/WEB/README.md`. |
| **Summaries** | Updated `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` and `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` for partner doc + E2E notes. |
| **Plan checkboxes** | Task 6 Step 3, Task 8 Step 5, Task 12 steps 1–3 marked complete in this document. |

Earlier phases (P0 OpenAPI/codegen, P1 RFQ overview/activity + WEB wiring, P2 approvals path, P3 password reset API/tests, security/staging tasks, P4 RFQ counts) were already marked complete before this closure.

---

### Next steps to move ahead

1. **Unblock E2E:** Run full `npm run test:e2e` on Ubuntu or in CI; fix any failures.
2. **API gate:** Run full `cd apps/atomy-q/API && php artisan test` (PostgreSQL test DB per `phpunit.xml`) before partner invite.
3. **Optional:** Run plan **plan-document-reviewer** pass (spec + plan alignment).
4. **Partner onboarding:** Share `ALPHA_DESIGN_PARTNER_SUPPORTED_FLOWS.md` + ensure staging env matches `.env.example` (mail, CORS, `APP_URL`, `JWT_SECRET`).

---

## File structure map

### API — auth & password reset


| Path                                                               | Responsibility                                                                                                                 |
| ------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------ |
| `apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php`  | Login, refresh, **forgot-password**, **reset-password** (new), SSO (unchanged for alpha)                                       |
| `apps/atomy-q/API/app/Services/Auth/PasswordResetService.php`      | Create token, validate token, reset hash, **same response whether email exists** (anti-enumeration)                            |
| `apps/atomy-q/API/database/migrations/*_password_reset_tokens.php` | If not present: token storage (hashed token, `user_id`, `expires_at`) — align with `config/auth.php` password reset table name |
| `apps/atomy-q/API/app/Models/PasswordResetToken.php`               | Optional Eloquent model for reset tokens                                                                                       |
| `apps/atomy-q/API/tests/Feature/Api/PasswordResetTest.php`         | Forgot + reset happy path, expired token, wrong tenant, enumeration-safe behavior                                              |
| `apps/atomy-q/API/config/mail.php`, `.env.example`                 | Mail driver for reset emails (log driver acceptable on staging)                                                                |


### API — OpenAPI


| Path                                                 | Responsibility                                                                                                                    |
| ---------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| `apps/atomy-q/API/composer.json`                     | Add OpenAPI generator dependency (recommended: **`dedoc/scramble`** — OpenAPI 3 from routes/controllers with minimal annotations) |
| `apps/atomy-q/API/config/scramble.php`               | Scramble config: API title, version, route filter `/api/v1/*`                                                                     |
| `apps/atomy-q/API/routes/web.php` or dedicated route | Expose `GET /docs/api` (Scramble UI) **only non-production** or behind admin if needed                                            |
| `apps/atomy-q/API/.github/workflows/*` or root CI    | Optional: `php artisan scramble:export` → `openapi.json` artifact; fail if drift                                                  |


### API — RFQ overview / activity


| Path                                                             | Responsibility                                                                                                      |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php` | `overview`: add fields from `WEB/BACKEND_API_GAPS.md` where feasible; new `activity` action or dedicated controller |
| `apps/atomy-q/API/routes/api.php`                                | `GET /rfqs/{id}/activity` (if split endpoint preferred in gaps doc)                                                 |
| `apps/atomy-q/API/tests/Feature/*Rfq*Test.php`                   | Tenant isolation for overview + activity                                                                            |


### API — approvals (alpha variant)


| Path                                                                  | Responsibility                                                                                                 |
| --------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php` | `index` returns real rows for tenant; **document** which approval `type`(s) alpha supports                     |
| `apps/atomy-q/API/app/Models/Approval.php`                            | Ensure factories/seed create **approvable** rows linked to finalized comparison per existing `approve()` logic |
| `apps/atomy-q/API/tests/Feature/ApprovalAlphaPathTest.php`            | List → show → approve/reject with tenant B getting **404**                                                     |


### WEB — client & errors


| Path                                                               | Responsibility                                                                       |
| ------------------------------------------------------------------ | ------------------------------------------------------------------------------------ |
| `apps/atomy-q/WEB/package.json`                                    | Add `@hey-api/openapi-ts` or `openapi-typescript` + script `generate:api`            |
| `apps/atomy-q/WEB/src/lib/api-client.ts` (or generated output dir) | Typed client + shared `parseApiError` for `{ message, errors, code }` shapes         |
| `apps/atomy-q/WEB/src/**/*` hooks using raw Axios                  | Gradually swap to generated client for **alpha routes** (RFQ, auth reset, approvals) |


### WEB — screens


| Path                                                       | Responsibility                                                             |
| ---------------------------------------------------------- | -------------------------------------------------------------------------- |
| `apps/atomy-q/WEB/src/app/(auth)/login/page.tsx`           | Link to forgot-password                                                    |
| `apps/atomy-q/WEB/src/app/(auth)/forgot-password/page.tsx` | **Modify/Verify** — POST forgot-password                                   |
| `apps/atomy-q/WEB/src/app/(auth)/reset-password/page.tsx`  | **Modify/Verify** — token from email link query param + POST reset         |
| `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/`**             | Complete list/detail/overview per blueprint; wire activity                 |
| `apps/atomy-q/WEB/src/app/(dashboard)/approvals/**`        | **Create** minimal queue + detail + approve/reject for **one** variant     |
| `apps/atomy-q/WEB/src/components/layout/sidebar.tsx`       | Show Approvals nav when feature enabled; hide SSO prominence per alpha doc |


### Tests & docs


| Path                                                                                       | Responsibility                                                |
| ------------------------------------------------------------------------------------------ | ------------------------------------------------------------- |
| `apps/atomy-q/WEB/tests/auth.spec.ts`                                                      | Extend: forgot-password + reset flow (stub email or mail log) |
| `apps/atomy-q/WEB/tests/rfq-workflow.spec.ts` or new `alpha-slice.spec.ts`                 | Login → RFQ list → create → detail → **approval** path        |
| `apps/atomy-q/WEB/docs/SECURITY_REVIEW_AUTH_AND_TENANTS.md`                                | Checklist run; annotate alpha completion                      |
| `apps/atomy-q/ALPHA_DESIGN_PARTNER_SUPPORTED_FLOWS.md`                                     | **Create** — supported vs stubbed, SSO deferred, buyer-only   |
| `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`, `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` | Update after each merge-worthy chunk                          |


### Package hygiene (parallel, time-boxed)


| Path                                                                 | Responsibility                                                                                             |
| -------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| `packages/FeatureFlags/src/Core/Decorators/CachedFlagRepository.php` | Fix abstract method mismatch if `feature-flags` package is loaded in CI path (`PACKAGE_TEST_READINESS.md`) |


---

## Phase P0 — OpenAPI + generated WEB types + error normalization

### Task 1: Add OpenAPI generation to the API

**Files:**

- Modify: `apps/atomy-q/API/composer.json`
- Create: `apps/atomy-q/API/config/scramble.php` (if using Scramble)
- Modify: `apps/atomy-q/API/bootstrap/app.php` or `AppServiceProvider` (Scramble registration per package docs)
- Modify: `apps/atomy-q/API/.env.example` — document `SCRAMBLE_`* if any
- **Step 1:** Choose generator and add dependency.

Run:

```bash
cd apps/atomy-q/API && composer require dedoc/scramble --dev
```

Expected: package installs without conflicts.

- **Step 2:** Publish config and restrict to `api/v1` routes (see Scramble docs).
- **Step 3:** Generate static `openapi.json` for the WEB repo.

Run:

```bash
cd apps/atomy-q/API && php artisan scramble:export --path=storage/app/openapi.json
```

Expected: file exists and contains `paths./api/v1/auth/login`.

- **Step 4:** Commit `composer.json` / `composer.lock` and config.

```bash
git add apps/atomy-q/API/composer.json apps/atomy-q/API/composer.lock apps/atomy-q/API/config/scramble.php
git commit -m "chore(api): add OpenAPI export via Scramble"
```

---

### Task 2: Generate TypeScript client in WEB

**Files:**

- Modify: `apps/atomy-q/WEB/package.json`
- Create: `apps/atomy-q/WEB/scripts/generate-api.sh` or npm script copying `../API/storage/app/openapi.json`
- Create: `apps/atomy-q/WEB/src/generated/api/` (or team convention) — generated output **gitignored** or committed per team policy; **recommend gitignore + CI generate**
- **Step 1:** Add dev dependency `@hey-api/openapi-ts`.

Run:

```bash
cd apps/atomy-q/WEB && npm install -D @hey-api/openapi-ts
```

- **Step 2:** Add script `"generate:api": "openapi-ts -i ../API/storage/app/openapi.json -o src/generated/api -c @hey-api/client-fetch"` (adjust to actual OpenAPI path and client style).
- **Step 3:** Run `npm run generate:api` and fix any schema issues in API (Scramble attributes / response types) until generation succeeds.
- **Step 4:** Commit `package.json` / lockfile and generation script.

---

### Task 3: Shared API error parsing in WEB

**Files:**

- Create: `apps/atomy-q/WEB/src/lib/api-error.ts`
- Create: `apps/atomy-q/WEB/src/lib/api-error.test.ts`
- Modify: one representative hook (e.g. `use-rfqs` or auth) to use parser
- **Step 1:** Write failing unit test for parser.

```typescript
// apps/atomy-q/WEB/src/lib/api-error.test.ts
import { describe, it, expect } from "vitest";
import { parseApiError } from "./api-error";

describe("parseApiError", () => {
  it("maps axios 422 validation payload", () => {
    const err = {
      response: { status: 422, data: { errors: { email: ["invalid"] } } },
    };
    const r = parseApiError(err);
    expect(r.status).toBe(422);
    expect(r.fieldErrors?.email).toBeDefined();
  });
});
```

- **Step 2:** Run `cd apps/atomy-q/WEB && npm run test:unit -- src/lib/api-error.test.ts` — expect FAIL until implementation exists.
- **Step 3:** Implement `parseApiError` for Axios error shape + optional `message` string.
- **Step 4:** Run tests — expect PASS.
- **Step 5:** Commit.

---

## Phase P1 — RFQ vertical slice (API + WEB) + overview / activity

### Task 4: RFQ overview fields + activity endpoint

**Files:**

- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php`
- Modify: `apps/atomy-q/API/routes/api.php`
- Modify: `apps/atomy-q/WEB/BACKEND_API_GAPS.md` — check off implemented items
- Test: extend or add `apps/atomy-q/API/tests/Feature/RfqOverviewActivityTest.php`
- [x] **Step 1:** Feature tests: cross-tenant **404**, owning tenant **200**, limit clamp, overview KPI aliases.

- [x] **Step 2:** `GET /api/v1/rfqs/{rfqId}/activity?limit=` (1–50, default 20), tenant-scoped + project ACL when `project_id` set.

- [x] **Step 3:** Overview `data` adds `expectedQuotes`, `normalizationProgress`, `latestComparisonRun`, `approvalStatus` (alongside existing nested shapes).

- [x] **Step 4:** `php artisan test --filter=RfqOverviewActivityTest` — PASS.

- [x] **Step 5:** Commit (includes regenerated `apps/atomy-q/openapi/openapi.json`).

---

### Task 5: WEB RFQ list/detail/overview wired to real API

**Files:**

- Modify: hooks under `apps/atomy-q/WEB/src/hooks/` (e.g. `use-rfqs.ts`, `use-rfq-overview.ts`)
- Modify: pages under `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/`
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_STATUS.md`
- [x] **Step 1:** Ensure list uses pagination meta from API `{ data, meta }`.
- [x] **Step 2:** Overview shows new KPI fields; activity list renders from activity endpoint.
- [x] **Step 3:** `cd apps/atomy-q/WEB && npm run test:unit` for affected hooks.
- [x] **Step 4:** Commit.

---

## Phase P2 — Buyer-side quote intake + minimal comparison + approval path

### Task 6: Quote intake + comparison (align with quote pilot plan)

**Files:** See `[2026-03-19-quote-ingestion-normalization-pilot-implementation-plan.md](2026-03-19-quote-ingestion-normalization-pilot-implementation-plan.md)`.

- [x] **Step 1:** Verify buyer can upload / create quote submission **without vendor portal** (tenant user only). *(Covered by existing quote workflow tests in API.)*
- [x] **Step 2:** Verify `ComparisonRunController` can produce a **final** run usable by `ApprovalController::approve`. *(Verified via `ComparisonSnapshotWorkflowTest`.)*
- [x] **Step 3:** If gaps remain, add tasks from pilot plan until **one** RFQ can go: intake → normalize (minimal) → finalize comparison. *(No further gaps: `ComparisonSnapshotWorkflowTest` + API README pilot notes cover intake → final run → approval gate.)*

---

### Task 7: Approvals — real `index` + WEB slice

**Files:**

- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ApprovalController.php`
- Modify: `apps/atomy-q/API/database/seeders/` or factory — seed approval pending for finalized comparison
- Create: `apps/atomy-q/API/tests/Feature/ApprovalAlphaPathTest.php`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/approvals/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/approvals/[id]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/components/layout/sidebar.tsx`
- [x] **Step 1:** Feature test: `GET /api/v1/approvals` returns seeded approval for tenant A; empty or 404 behavior documented for tenant with none.
- [x] **Step 2:** Implement `index` query: `where('tenant_id', $tenantId)` with pagination.
- [x] **Step 3:** WEB: list page + detail with approve/reject calling existing endpoints; handle **404** as generic not found.
- [x] **Step 4:** Commit.

---

## Phase P3 — Partner gate (forgot-password, security checklist, staging)

### Task 8: Password reset end-to-end

**Files:**

- Create: `apps/atomy-q/API/app/Services/Auth/PasswordResetService.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/AuthController.php`
- Create migration if table missing for reset tokens
- Create: `apps/atomy-q/API/tests/Feature/Api/PasswordResetTest.php`
- Modify/Verify: `apps/atomy-q/WEB/src/app/(auth)/forgot-password/page.tsx`
- Modify/Verify: `apps/atomy-q/WEB/src/app/(auth)/reset-password/page.tsx`
- Modify: `apps/atomy-q/API/tests/Feature/Api/AuthTest.php` if assertions expect `501` on forgot-password
- [x] **Step 1:** Write failing test: `POST /api/v1/auth/forgot-password` returns **200** with generic message (not 501).

```php
public function test_forgot_password_returns_ok_without_leaking_user_existence(): void
{
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'nobody@example.com',
    ]);
    $response->assertOk();
    $response->assertJsonStructure(['message']);
}
```

- [x] **Step 2:** Implement token persistence, email notification (use `Mail::fake()` in tests).
- [x] **Step 3:** Add `POST /api/v1/auth/reset-password` with `email`, `token`, `password`, `password_confirmation` (validate strength).
- [x] **Step 4:** Run `cd apps/atomy-q/API && php artisan test --filter=PasswordReset`.
- [x] **Step 5:** WEB pages + Playwright step (use `log` mail driver or intercept).
- [x] **Step 6:** Commit.

---

### Task 9: Security checklist + tenant isolation regression

**Files:**

- Modify: `apps/atomy-q/WEB/docs/SECURITY_REVIEW_AUTH_AND_TENANTS.md` — add “Alpha sign-off” section with date
- Add/extend tests: RFQ, Approval, QuoteSubmission cross-tenant **404** tests
- [x] **Step 1:** Walk checklist; file issues for any gap.
- [x] **Step 2:** Add PHPUnit coverage for **every** alpha-exposed write endpoint touched in P1–P2. *(Approval + RFQ counts + existing activity/quote tests.)*
- [x] **Step 3:** Commit.

---

### Task 10: Staging & environment

**Files:**

- Modify: `apps/atomy-q/API/.env.example`
- Modify: `apps/atomy-q/WEB/.env.example`
- Optional: `deploy/` or `docs/atomy-q/STAGING.md`
- [x] **Step 1:** Document required env vars: `APP_URL`, `JWT_SECRET`, mail, `DATABASE_URL`, CORS origins for WEB.
- [x] **Step 2:** Health route verified (`/up` Laravel 11+ or existing).
- [x] **Step 3:** Commit.

---

## Phase P4 — Optional polish

### Task 11: RFQ sidebar status counts

**Files:**

- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php` or new `RfqCountsController`
- Modify: WEB sidebar
- [x] **Step 1:** If still valuable after alpha nav review, add `GET /api/v1/rfqs/counts` tenant-scoped.

---

## Partner readiness

### Task 12: Supported flows document

**Files:**

- Create: `apps/atomy-q/ALPHA_DESIGN_PARTNER_SUPPORTED_FLOWS.md`
- [x] **Step 1:** Document: identity (email/password + reset), **no SSO requirement**, buyer-only (no vendor portal), **one** approval variant, list of stubbed nav items.
- [x] **Step 2:** Link from `apps/atomy-q/API/README.md` and `apps/atomy-q/WEB/README.md`.
- [x] **Step 3:** Commit.

---

## E2E gate (run before inviting partners)

- **Step 1:** `cd apps/atomy-q/API && php artisan test`
- **Step 2:** `cd apps/atomy-q/WEB && npm run test:unit`
- **Step 3:** `cd apps/atomy-q/WEB && PLAYWRIGHT_BASE_URL=https://staging.example.com npm run test:e2e` (replace `https://staging.example.com` with your staging WEB origin; omit the variable to target local dev server)

Expected: all pass; E2E includes login, RFQ create/list/detail, **approval** action, forgot-password request (or tagged `@alpha` subset if suite is large).

**Blocked on Fedora / unsupported Playwright hosts:** do not sign off the full E2E gate until Step 3 runs on a supported OS (see **Status → Blocker** above).

---

## Plan review (recommended)

After editing this plan, dispatch **plan-document-reviewer** with:

- Plan path: `docs/superpowers/plans/2026-03-20-atomy-q-design-partner-alpha-implementation-plan.md`
- Spec path: `docs/superpowers/specs/2026-03-20-atomy-q-alpha-release-brainstorm-design.md`

Fix issues ≤3 review rounds, then execute.

---

## Execution handoff

**Plan complete and saved to `docs/superpowers/plans/2026-03-20-atomy-q-design-partner-alpha-implementation-plan.md`. Two execution options:**

1. **Subagent-Driven (recommended)** — fresh subagent per task, review between tasks, fast iteration (`superpowers:subagent-driven-development`).
2. **Inline execution** — run tasks in one session with checkpoints (`superpowers:executing-plans`).

**Which approach?** (Reply in chat when you start execution.)