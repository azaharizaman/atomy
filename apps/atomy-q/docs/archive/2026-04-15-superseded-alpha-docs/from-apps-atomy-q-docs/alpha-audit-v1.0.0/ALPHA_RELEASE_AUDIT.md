# Atomy-Q Alpha Release Audit (v1.0.0)

**Repository:** https://github.com/azaharizaman/atomy  
**Audit date:** 2026-03-23  
**Scope:** `apps/atomy-q/` (Laravel API + Next.js WEB) with relevant monorepo packages referenced by the API.  
**Method:** Static code scan, controller/service review, config review, and targeted build verification. **Do not treat “tests exist” as proof of production readiness** without a full integration run against real DB, AI, and deployed env.

---

## 1. Summary

Atomy-Q has a **strong skeleton**: JWT auth, tenant-scoped RFQ/quote/comparison/approval paths, migrations for core tables, and a **real** `POST /comparison-runs/final` flow that persists `ComparisonRun` rows and builds snapshots from `NormalizationSourceLine` data. However, **Alpha’s stated goal (“real AI, no mock data”) is not met today**: the API layer has **no wired LLM/quotation-intelligence pipeline** for quote normalization; `reparse` and several read endpoints are **stubbed**; **awards are entirely unimplemented (501)**; **vendor directory APIs are stubbed**; **normalization list endpoints return empty `data`** while mutations expect DB rows; **`tenants` table is not created by any migration** despite a `Tenant` model; **identity/session/permission/role** are bound to **stub/no-op** implementations in `AppServiceProvider`; **Next.js production build currently fails** (TypeScript error).  
**Risk:** cross-tenant and idempotency gaps remain in TODO-marked controller paths (duplicate RFQ, bulk actions, vendor remind, etc.).

---

## 2. Architecture analysis

**Intended flow (target):**  
`Next.js (WEB)` → `Axios` → `Laravel API (/api/v1)` → `Controllers` → `Eloquent models` / `QuoteIntake services` → `(missing) AI extraction & semantic normalization` → `PostgreSQL` → JSON responses → UI.

**As implemented:**

| Layer | Assessment | Notes |
|--------|------------|--------|
| **Frontend → API** | ⚠ **Risky** | `NEXT_PUBLIC_API_URL` must include `/api/v1`. Idempotency keys auto-injected for some POSTs. |
| **Auth** | ✔ **Mostly correct** | Email/password login + JWT; global email uniqueness. |
| **Tenant / company** | ❌ **Broken / unclear** | Data is scoped by `tenant_id` on rows, but **no `tenants` migration**, no public “create company tenant” API. “Company” in Alpha scope maps to **tenant**, which is **not first-class in API**. |
| **RFQ** | ⚠ **Risky** | Core CRUD + line items largely tenant-scoped. **Duplicate / saveDraft / bulkAction** return stubs or weak scoping. |
| **Vendor** | ⚠ **Risky** | **Vendor invitations** are real DB. **`/vendors` REST** is **stub** (empty / “Stub Vendor”). |
| **Quotation intake** | ⚠ **Risky** | Upload stores rows; **reparse/replace/assign** are stubbed; **no extraction pipeline**. |
| **Normalization** | ⚠ **Risky** | **DB-backed** mapping/conflicts/readiness for lines that exist; **GET source-lines / normalized-items return empty `data`**; **no AI**. |
| **Comparison** | ⚠ **Risky** | **Final comparison** is real; **preview** returns ephemeral id; **matrix/readiness/lock** endpoints largely stubbed. |
| **Award** | ❌ **Broken** | **All award endpoints 501 / not implemented.** |
| **AI / LLM** | ❌ **Broken** | `nexus/machine-learning` is a **dependency**, but **no usage in `atomy-q/API` app code**; no `OPENAI_*` (or similar) in API `.env.example`. |

**Flows (concise):**

- **RFQ flow:** Create/list/update RFQ and line items (real) → vendor invitations (real) → **duplicate RFQ** (stub id).
- **Quotation flow:** Upload file → row in `quote_submissions` → **no async parsing job**; `reparse` returns 202 with fake status.
- **Normalization flow:** Readiness is **rule-based** (all RFQ lines mapped, prices present, conflicts resolved). **Not AI normalization**. List endpoints for source/normalized items **empty**.
- **Comparison flow:** **Preview** non-persistent; **final** builds snapshot from `NormalizationSourceLine` + `RfqLineItem` and stores `ComparisonRun`.
- **User ↔ tenant:** `users.tenant_id` is the isolation key; **no `tenants` table** in migrations.
- **Vendor relation:** `vendor_invitations` + string `vendor_id` on quotes; **no real vendor master** API.

---

## 3. Missing features list (vs Alpha scope checklist)

| Alpha requirement | Status | Gap |
|-------------------|--------|-----|
| Login | ✔ | Works with seeded users; no self-serve registration. |
| Create company | ❌ | No tenant CRUD; no `tenants` migration; tenant is opaque ULID. |
| Create RFQ | ✔ | Core paths exist. |
| Add items | ✔ | Line items API exists. |
| Add vendors | ⚠ | **Invitations** yes; **vendor catalog** stub; vendors are strings/IDs. |
| Add quotations | ⚠ | Upload exists; **parsing/reparse** not real. |
| Normalize with AI | ❌ | **No AI integration** in API; normalization is rule + DB. |
| Compare quotations | ⚠ | **Final** comparison yes; **preview/matrix** incomplete. |
| Select winner | ❌ | **Award API not implemented.** |
| Save result | ❌ | **No award persistence**; decision trail partial elsewhere. |

---

## 4. Mock / fake detection

### Backend (`apps/atomy-q/API`)

- **`AppServiceProvider`:** `AtomyIdentityTokenManagerStub`, `AtomySessionManagerStub`, `AtomyPermissionQueryStub`, `AtomyRoleQueryStub`, `AtomyNoopMfa*`, `AtomyNoopAuditLogRepository`.
- **`VendorController`:** Returns empty arrays and `"Stub Vendor"` (explicit TODO).
- **`AwardController`:** `notImplemented` → **501** for all operations.
- **`QuoteSubmissionController`:** `replace`, `reparse`, `assign` stub responses; `show` tabs empty.
- **`NormalizationController`:** `sourceLines`, `normalizedItems` return **`data: []`**.
- **`ComparisonRunController`:** `preview` uses `uniqid`; `matrix`, `readiness`, `updateScoringModel`, `lock`, `unlock` stubbed.
- **`DashboardController`:** Zeros / empty arrays.
- **`RfqController`:** `duplicate` returns **`stub-duplicate-id`**; `saveDraft` / `bulkAction` incomplete.
- **`RfqTemplateController` / `RfqController`:** stub template/duplicate ids (per earlier grep).
- **`VendorInvitationController::remind`:** TODO; response not verified against DB.
- **Tests:** `Queue::fake()` in notification tests (test-only).

### Frontend (`apps/atomy-q/WEB`)

- **`NEXT_PUBLIC_USE_MOCKS`:** Widespread; when `true`, seed data and bypass live API for RFQ/normalization/award/comparison/approvals.
- **`src/data/seed.ts`:** Large mock RFQ universe.
- **`playwright.config.ts`:** Sets `NEXT_PUBLIC_USE_MOCKS: 'true'` for E2E webServer.
- **Playwright tests:** `mockUser`, `stubAuth`, route stubs (`smoke`, `auth`, `rfq-lifecycle-e2e`, etc.).
- **`RfqSectionStubPage`:** `[section]` catch-all scaffold for many workspace routes.
- **Generated OpenAPI types:** Embedded `stub-*` example strings (generated).

---

## 5. Backend problems

**P0**

- No **`tenants` table migration** anywhere in the repo; `Tenant` model is **orphaned**.
- **Award** module: **not implemented** (blocks “select winner / save result”).
- **AI normalization / extraction:** **not wired**; `reparse` is fake.
- **Normalization GET** `source-lines` / `normalized-items` return **empty** (UI cannot list lines from API).
- **RFQ duplicate** returns **hardcoded** `stub-duplicate-id`.
- **Idempotency + tenant TODOs** on duplicate, bulk, remind (incorrect or unsafe under concurrency).

**P1**

- **Vendor master** API stubbed.
- **Comparison preview** does not persist; **matrix/readiness** stubbed.
- **Account** password/preferences partially stubbed.
- **Identity stubs** for sessions/roles/permissions may block real RBAC or SSO expectations.
- **No queue jobs** in API app (`ShouldQueue` / `dispatch` not found) — no async extraction/normalization.

**P2**

- Dashboard KPIs empty placeholders.
- Large surface area of **optional** modules (risk, integrations, reports, etc.) increases maintenance.

---

## 6. Frontend problems

**P0**

- **`npm run build` fails** (TypeScript): `HorizontalProcessTrack.tsx` — `aria-label` typed as `string | null` vs `string | undefined`.
- Alpha requires **`NEXT_PUBLIC_USE_MOCKS=false`**; many flows still **fallback to seed** on API failure in `use-rfqs` (see hook comments).

**P1**

- **`RfqSectionStubPage`:** sections like **negotiations, documents, risk** are **scaffold-only** (navigation parity without implementation).
- **RFQs list page:** bulk actions TODOs (close/archive/export).
- Playwright defaults to **mocks** — CI may not catch **live API** regressions.

**P2**

- Generated client `TODO` in `client.gen.ts` (types/error handling).

---

## 7. AI integration status

| Check | Status |
|--------|--------|
| Provider configured in Atomy-Q API | ❌ No `OPENAI`/`ANTHROPIC`/etc. in `API/.env.example` |
| Env keys | ❌ Not documented for Atomy-Q API |
| Prompts / pipeline | ❌ No controllers/services invoking ML or quotation-intelligence |
| Response parsing | ❌ N/A at API layer |
| Error handling / retry | ❌ N/A |
| Fallback | ⚠ Rule-based readiness + `QuoteSubmissionReadinessService` only |

**Note:** `packages/MachineLearning` contains **OpenAI/Anthropic** providers, but **Atomy-Q API does not reference them** in application code. `nexus/quotation-intelligence` is in `composer.json` but **no imports** under `apps/atomy-q/API/app`.

---

## 8. Database issues

- **Missing `tenants` migration** (model exists).
- **No `company_id`** naming — tenant is **`tenant_id`** (aligns with internal docs; Alpha doc said `company_id` — **terminology mismatch**).
- Otherwise **34 migrations** for RFQ, quotes, normalization, comparison, awards (**awards table exists** but controller does not write).
- **Seeder** `PetrochemicalTenantSeeder` inserts **large demo dataset** when RFQs empty; **does not** insert `tenants` rows.

---

## 9. Config issues

**API `.env.example`**

- Missing: **AI provider keys**, **queue** worker command hints, **production CORS** origins (only localhost in `config/cors.php`).
- **DB port 5433** — must match docker/compose.
- **S3/MinIO** placeholders — **required** for `FILESYSTEM_DISK=s3` uploads.
- **JWT_SECRET** required — app throws if empty (good).

**WEB `.env.example`**

- Documents `NEXT_PUBLIC_API_URL` and `NEXT_PUBLIC_USE_MOCKS` (good).
- **Port mismatch risk:** example uses `8001`; API `.env` uses `8000` — **document single source of truth**.

---

## 10. Deployment issues

| Check | Result (2026-03-23) |
|--------|-------------------|
| `composer install` | Not re-run in audit; `vendor/` present in workspace |
| `php artisan` | ✔ Laravel `12.55.1` |
| `npm run build` (WEB) | ❌ **Fails** (TS error in `HorizontalProcessTrack.tsx`) |
| Migrations | Need `pg` + credentials; **verify** `tenants` gap before production |
| Storage | S3/MinIO config required for uploads |
| Queue | **No jobs** in app; **no** documented worker for AI |
| Env | **Manual** alignment of API URL, CORS, JWT, DB |

---

## 11. Non-alpha features to remove or disable

**Candidates to disable via feature flags or route removal for Alpha:**

- **Billing / subscription** (Account controller sections if exposed).
- **Analytics / reporting** (`ReportController`, report schedules, dashboards beyond minimal).
- **Notifications** (Postmark tests — optional for Alpha).
- **Advanced RBAC** (until real `PermissionQuery`/`RoleQuery` replace stubs).
- **Multi-provider AI** (ship **one** provider or **none** with rule-based fallback).
- **Risk & compliance, integrations, negotiations, scenarios** — **scaffold UIs** already exist; **hide** from nav or mark “Coming soon”.

**Recommendation:** Use existing `FEATURE_PROJECTS_ENABLED` / `FEATURE_TASKS_ENABLED` pattern; add **`FEATURE_ALPHA_CORE_ONLY`** to hide non-core nav and stub APIs.

---

## 12. Realistic 30-day plan (Day 1 → Day 30)

**Theme:** Stabilize **core path** (auth → RFQ → quotes → normalization → comparison → award) → remove mocks for Alpha → deploy.

Each day lists **focus**, **primary modules**, **files to touch**, **tests to run**.

| Day | Focus | Modules / files | Tests |
|-----|--------|-----------------|-------|
| 1 | **P0 triage** | Audit spreadsheet from this doc; assign owners | — |
| 2 | Fix **Next build** | `WEB/src/components/ds/HorizontalProcessTrack.tsx` | `npm run build`, `npm run lint` |
| 3 | **Tenants migration** + model | New migration `create_tenants_table`; FK optional | `php artisan migrate`, PHPUnit smoke |
| 4 | **Tenant + user seed** | Align `PetrochemicalTenantSeeder` with `tenants` | `php artisan db:seed` |
| 5 | **Create tenant API** (minimal) | New controller or extend auth; **POST /tenants** or admin | Feature test |
| 6 | **Register flow** (optional) | `AuthController` + validation | API feature test |
| 7 | **VendorController** real DB | `vendors` table or reuse invitations aggregate | PHPUnit + API test |
| 8 | **Normalization GET** | Return real `NormalizationSourceLine` rows in `sourceLines` / `normalizedItems` | Feature test |
| 9 | **RFQ duplicate** | Real copy of RFQ + lines + invitations | **Idempotency** test |
| 10 | **saveDraft / bulkAction** | Tenant-scoped queries | Feature tests |
| 11 | **Vendor remind** | Load invitation by tenant + RFQ | Feature test |
| 12 | **Quote reparse** | Stub → queue job interface | Contract test |
| 13 | **AI: provider config** | `config/services.php`, `.env.example` | Config test |
| 14 | **AI: extraction** | Wire `quotation-intelligence` or ML package to **one** endpoint | Integration test (mock HTTP) |
| 15 | **AI: populate** `NormalizationSourceLine` | From extraction output | DB assertions |
| 16 | **Normalization error handling** | Retries, user-visible errors | Unit + integration |
| 17 | **Comparison preview** | Persist or deprecate; align UI | API test |
| 18 | **Matrix endpoint** | Derive from `ComparisonRun.snapshot` | PHPUnit |
| 19 | **AwardController** | Implement `store` + `index` using `awards` table | Feature test |
| 20 | **WEB award UI** | Wire to API; remove seed path | Playwright (live API) |
| 21 | **Remove mock fallbacks** | `use-rfqs` etc. | Vitest + manual |
| 22 | **CORS + env** | Production origins | Smoke |
| 23 | **Identity stubs** | Replace stubs with real or document “Alpha = noop” | Security review |
| 24 | **Queue worker** | `supervisor` config for extraction | Staging |
| 25 | **S3 production** | Bucket policy, CORS | Upload E2E |
| 26 | **Staging deploy** | Docker/compose or VM | Full regression |
| 27 | **Performance** | N+1 queries on hot paths | Load smoke |
| 28 | **Security** | Tenant isolation audit on **all** new endpoints | Automated + manual |
| 29 | **Release freeze** | **Code freeze**; docs + runbook | Final test suite |
| 30 | **Alpha deployed** | Tag release; monitor | **Production smoke** |

---

## 13. Priority list

### P0 (must fix before Alpha)

- **Next.js production build** (TS error).
- **`tenants` (company) data model** + creation path.
- **Award API + UI** (select winner, persist).
- **Normalization list APIs** return real data (or UI must not depend on them).
- **AI** either **shipped** (minimal) or **explicitly** deferred with **product decision** (currently **not** meeting Alpha_SCOPE “real AI”).
- **Remove/stop** stub responses on **RFQ duplicate** and any **idempotent** route that lies about IDs.
- **Cross-tenant** TODOs on **bulk/duplicate/remind**.

### P1 (should fix before Alpha)

- **Vendor master** implementation.
- **Vendor remind** correctness.
- **Comparison preview/matrix** consistency.
- **Quote reparse** real job or **disabled** in UI.
- **CORS** for production origins.
- **E2E against live API** (not only `USE_MOCKS`).

### P2 (later)

- Dashboard KPIs, advanced reporting, integrations, risk modules, **non-core** workspace stubs.

---

*End of report v1.0.0*
