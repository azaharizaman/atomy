# Implementation Summary - Atomy-Q Backend API

## Status
- **Phase**: Foundation complete, quote lifecycle productionized
- **Framework**: Laravel 12 (PHP 8.3)
- **Database**: PostgreSQL (26 tables migrated)
- **Cache/Queue**: Redis via `REDIS_URL`
- **Auth**: JWT (Bearer token) via `firebase/php-jwt`

## Authentication

### `POST /api/v1/auth/login` (email + password)

- **Request body:** `email`, `password` only. Tenant is **not** sent by the client; it is read from `users.tenant_id` after a successful match (`users.email` is globally unique).
- Uses `UserAuthenticationCoordinatorInterface` for credential authentication and session creation, then returns app JWT access + refresh tokens (claims include the user’s `tenant_id` and optional `sid`).
- `AuthController` injects `JwtServiceInterface`, `PasswordResetServiceInterface`, `IdentityUserQueryInterface`, and `UserAuthenticationCoordinatorInterface` in the constructor; login/password verification stays coordinator-driven while refresh remains token-based.

### `POST /api/v1/auth/register-company`

- Public alpha onboarding endpoint that creates the first tenant row and first owner user in one transaction.
- Accepts `tenant_code`, `company_name`, `owner_name`, `owner_email`, `owner_password`, and optional `timezone`, `locale`, `currency`.
- Delegates to `TenantCompanyOnboardingCoordinatorInterface`, which coordinates `TenantCompanyOnboardingService` in Layer 2 using the tenant creator and admin creator adapter ports.
- The Layer 3 Laravel adapter persists the tenant through `EloquentTenantPersistence`, the owner through `EloquentAdminCreator`, and returns JWT bootstrap data so the WEB app can sign the owner in immediately.
- The onboarding path uses the new `tenants` table / `Tenant` model and keeps `tenant_id` as the isolation key for the rest of the app.

### `POST /api/v1/auth/forgot-password`

- **Request body:** `email` only. Returns **200** with a generic message (no user enumeration). Mail send failures are logged and **do not** change the JSON response. When a user exists, `PasswordResetService` stores a hashed token keyed by **`(tenant_id, email)`** from that user’s row and sends `PasswordResetMail` (TTL text comes from `auth.passwords.users.expire`, minimum 1 minute via `AppServiceProvider`).

### `POST /api/v1/auth/reset-password`

- **Request body:** `email`, `token`, `password`, `password_confirmation`. Resolves the user by email, loads the token row for that user’s **tenant**, validates TTL and `Hash::check` inside a **DB transaction** with `lockForUpdate()` on the token row; updates `users.password_hash`; deletes the token row; **422** on invalid/expired token; unexpected failures are **logged** and return **500** with a generic message (no exception details).

### Tenancy & identity (policy)

- **One user → one tenant:** each `users` row is tied to exactly one `tenant_id`. A single user account does not span multiple tenants.
- **Two tenants → two users:** if a person must represent two separate organizations, they use **two separate user accounts** (two distinct user IDs).
- **Login:** `POST /api/v1/auth/login` uses email + password only; any legacy `tenant_id` field in the JSON body is **ignored**.
- **Uniqueness:** migration `2026_03_20_000001_users_email_unique_globally` replaces `UNIQUE(tenant_id, email)` with **`UNIQUE(email)`** on `users`.

### SSO (`POST /api/v1/auth/sso`) and full Identity coordinator

- `AuthController` now injects `UserAuthenticationCoordinatorInterface` directly and uses it for both login and SSO; the controller no longer lazy-resolves the coordinator from the container.
- `nexus/laravel-identity-adapter` registers `IdentityAdapterServiceProvider`, which wires `OidcSsoProviderAdapter`, `IdentityOperationsAdapter`, `LaravelPasswordHasher`, and the repository-backed RBAC query layer.
- **Atomy-Q bindings** (see `App\Providers\AppServiceProvider` and `App\Services\Identity\*`):
  - **Eloquent-backed**: `UserPersistInterface`, `UserQueryInterface`, `PasswordHasherInterface`, `UserAuthenticatorInterface`, `SessionManagerInterface`, `PermissionQueryInterface`, and `RoleQueryInterface`.
  - **App-backed adapters**: `TokenManagerInterface` and `MfaEnrollmentServiceInterface` are still alpha stubs, while `MfaVerificationServiceInterface` and `AuditLogRepositoryInterface` are runtime implementations. Gap 7’s MFA extension persists `challenge_id`-backed login challenges and audit rows for login success/failure, MFA challenge issuance/verification, and logout.
- JWT access tokens now carry an optional `sid` claim and `JwtAuthenticate` rejects revoked sessions when that claim is present.
- `AtomyUserQuery` now exposes real role and permission data through `user_roles`, `role_permissions`, and `user_permissions` pivots, with legacy single-role fallback for seeded alpha users.
- `AtomyUserAuthenticator` now increments failed-login counters and locks accounts after five failures, and the new identity tests verify both lockout and wildcard RBAC through the real middleware path.
- `AuthTest::test_sso_*` exercise OIDC init + mock callback (`mock_authorization_code` + `SSO_MOCK_DISCOVERY_DOCUMENT`) end-to-end against these bindings.
- MFA verification now resolves tenant context from the persisted challenge record. `tenant_id` is optional on `POST /api/v1/auth/mfa/verify`; when supplied and mismatched, the endpoint returns the generic invalid-challenge response without exposing cross-tenant state.

### Environment

- `JWT_SECRET` must be non-empty (`php artisan key:generate` sets `APP_KEY`; JWT uses `config/jwt.php` / `.env` `JWT_SECRET`).

- **`GET /api/v1/feature-flags`:** Returns `{ data: { projects: bool, tasks: bool } }` mirroring `config('features.*')` for WEB nav and graceful degradation when routes return 404.

## OpenAPI (Scramble)

- **Package:** `dedoc/scramble` (dev). Config: `config/scramble.php` — documents routes under `api/v1` only.
- **Interactive UI:** `GET /docs/api` and `GET /docs/api.json` when the app is running.
- **Static export (for WEB client generation):** `php artisan scramble:export --path=../openapi/openapi.json` from `apps/atomy-q/API` writes `apps/atomy-q/openapi/openapi.json`. Regenerate after meaningful API contract changes.

## RFQ lifecycle mutations (2026-04-03)

- `POST /api/v1/rfqs/{id}/duplicate` creates a real tenant-scoped duplicate RFQ and copies line items through `Nexus\SourcingOperations`.
- `PUT /api/v1/rfqs/{id}/draft` persists draft-editable RFQ fields instead of echoing the request payload.
- `POST /api/v1/rfqs/bulk-action` supports real persisted bulk `close` and `cancel` actions with honest affected counts.
- `PATCH /api/v1/rfqs/{id}/status` delegates to the shared RFQ lifecycle transition policy rather than controller-local status assignment.
- `POST /api/v1/rfqs/{id}/invitations/{invId}/remind` enforces tenant-scoped RFQ/invitation lookup and stores `vendor_invitations.reminded_at`.
- Layer 3 bindings live in `AppServiceProvider`; the Laravel adapters sit under `App\Services\SourcingOperations\*`.

## RFQ lifecycle hardening (2026-04-04)

- Added Laravel bindings for orchestrator-local sourcing transaction and status-policy adapters so `SourcingOperationsCoordinator` stays Layer 2 clean while still using DB-backed transactions in Layer 3.
- `AtomyRfqInvitationReminder` now dispatches a real `Nexus\Notifier` email reminder instead of log-only behavior, using the new `rfq-invitation-reminder` template and DTO-carried invitation `channel`.
- Invitation reminder state is now persisted only after notification dispatch succeeds, preventing `reminded_at` from being written on delivery failure.
- Draft saves now preserve explicit nulls for nullable fields that are actually present in the request instead of falling back through `??` to old values.
- Duplicate RFQs now create `rfq_number` and insert inside a single transaction with retry on unique-key conflicts, closing the atomicity gap between number generation and persistence.
- Bulk-action execution now rejects preloaded record sets that do not exactly match the requested RFQ ids, preventing writes against unvalidated identifiers.

## Vendor selection and RFQ handoff (2026-04-22)

- `requisition_selected_vendors` persists the approved vendor shortlist for an RFQ/requisition with tenant, RFQ, vendor, selecting user, and selected-at metadata plus a tenant/RFQ/vendor uniqueness guard.
- `GET /api/v1/rfqs/{id}/selected-vendors` returns the tenant-scoped selected vendor projection; `PUT /api/v1/rfqs/{id}/selected-vendors` replaces the shortlist atomically and requires a non-empty distinct set of same-tenant `approved` vendor IDs.
- `POST /api/v1/rfqs/{id}/invitations` now requires `vendor_id`, derives vendor name/email from the approved vendor master, and rejects approved-but-unselected vendors with `422` so invitation creation cannot bypass requisition selection.
- RFQ invitation reminders remain tenant-scoped through the existing lifecycle coordinator path; roster reads continue to return only real invitation rows, not synthetic selected-vendor rows.
- Verification: `cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/Api/V1/RequisitionVendorSelectionApiTest.php tests/Feature/Api/V1/RfqInvitationApiTest.php` -> PASS, 6 tests / 32 assertions.

## Alpha Task 1 rectification (2026-04-15)

- Quote upload happy-path tests now include a valid RFQ line-item fixture, proving the mock processor can mark uploads `ready`; no-line upload coverage proves missing RFQ line context does not masquerade as ready.
- `Award::creator()` is available as a relation mapped to the current `signed_off_by` schema while preserving `signedOffByUser()`.
- Sourcing adapter tests mirror the production RFQ schema for project/schedule fields, and duplicate RFQ number exhaustion maps SQLite unique violations to `DuplicateRfqNumberException`.
- Verification: API alpha matrix passes with 93 tests / 522 assertions; full API suite passes with 425 tests / 1131 assertions.

## Endpoint Coverage

All **203 endpoints** from `API_ENDPOINTS.md` are registered. The quote lifecycle slice now returns live tenant-scoped data and mutations; the remaining areas still use the earlier stub or partially live patterns documented below.

| Section | Category | Endpoints | Controller |
|---------|----------|-----------|------------|
| 1 | Authentication & Session | 7 | `AuthController` |
| 2 | Dashboard | 5 | `DashboardController` |
| 3 | RFQ Management | 12 | `RfqController` |
| 4 | RFQ Templates | 7 | `RfqTemplateController` |
| 5 | Vendor Management | 5 | `VendorController` |
| 6 | Vendor Invitations | 3 | `VendorInvitationController` |
| 7 | Quote Intake | 7 | `QuoteSubmissionController` |
| 8 | Quote Normalization | 10 | `NormalizationController` |
| 9 | Comparison Matrix | 9 | `ComparisonRunController` |
| 10 | Scoring Models | 8 | `ScoringModelController` |
| 11 | Scoring Policies | 8 | `ScoringPolicyController` |
| 12 | Scenarios | 5 | `ScenarioController` |
| 13 | Recommendations | 4 | `RecommendationController` |
| 14 | Risk & Compliance | 7 | `RiskComplianceController` |
| 15 | Approvals | 12 | `ApprovalController` |
| 16 | Negotiations | 5 | `NegotiationController` |
| 17 | Award Decision | 7 | `AwardController` |
| 18 | PO/Contract Handoff | 6 | `HandoffController` |
| 19 | Decision Trail | 4 | `DecisionTrailController` |
| 20 | Documents & Evidence | 10 | `DocumentController` |
| 21 | Reports & Analytics | 11 | `ReportController` |
| 22 | Integrations | 11 | `IntegrationController` |
| 23 | Users & Access | 10 | `UserController` |
| 24 | Admin Settings | 6 | `SettingController` |
| 25 | Notifications | 5 | `NotificationController` |
| 26 | Search | 1 | `SearchController` |
| 27 | User Settings (Account) | 14 | `AccountController` |
| | **Total** | **203** | **27 controllers** |

## Nexus Packages Integrated

### L1 Packages (via composer path repos)
- `nexus/common`, `nexus/identity`, `nexus/sso`, `nexus/crypto`
- `nexus/tenant`, `nexus/setting`, `nexus/feature-flags`
- `nexus/procurement`, `nexus/party`
- `nexus/document`, `nexus/storage`
- `nexus/notifier`, `nexus/audit-logger`, `nexus/event-stream`
- `nexus/sequencing`, `nexus/currency`, `nexus/uom`
- `nexus/sanctions`, `nexus/aml-compliance`, `nexus/compliance`
- `nexus/reporting`, `nexus/query-engine`, `nexus/export`
- `nexus/connector`, `nexus/scheduler`
- `nexus/machine-learning`, `nexus/workflow`, `nexus/messaging`

### L2 Orchestrators
- `nexus/quotation-intelligence`
- `nexus/identity-operations`, `nexus/tenant-operations`
- `nexus/settings-management`
- `nexus/procurement-operations`
- `nexus/compliance-operations`
- `nexus/connectivity-operations`
- `nexus/data-exchange-operations`
- `nexus/insight-operations`

### L3 Adapters
- `nexus/laravel-identity-adapter`
- `nexus/laravel-tenant-adapter`
- `nexus/laravel-setting-adapter`

## Database Tables (25)

`users`, `rfqs`, `rfq_line_items`, `rfq_templates`, `vendor_invitations`, `quote_submissions`, `normalization_source_lines`, `normalization_conflicts`, `comparison_runs`, `scoring_models`, `scoring_policies`, `scenarios`, `approvals`, `approval_history`, `negotiation_rounds`, `awards`, `handoffs`, `decision_trail_entries`, `evidence_bundles`, `report_schedules`, `report_runs`, `integrations`, `integration_jobs`, `notifications`, `risk_items`

All tables include `tenant_id` (indexed) for multi-tenant isolation.

## Eloquent Models (26)

One model per table plus a `Tenant` model. All use ULID primary keys via `HasUlids` trait.

## Configuration

All configurable via environment variables (`.env`):
- Database: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Redis: `REDIS_URL`
- JWT: `JWT_SECRET`, `JWT_TTL`, `JWT_REFRESH_TTL`
- App: `APP_URL`, `APP_ENV`, `APP_DEBUG`, `APP_KEY`
- Runtime settings via `Nexus\Setting` (hierarchical: User > Tenant > Application)

Custom config files: `config/jwt.php`, `config/atomy.php`

## RFQ Flow Seed Script (`atomy:seed-rfq-flow`)

- **Artisan command**: `php artisan atomy:seed-rfq-flow` — seeds real-world RFQ data by **replaying the full API flow** over HTTP (create RFQ → line items → publish → invite vendors → submit quotes → intake → normalization → comparison → close/award).
- **Purpose**: (1) Generate seed data via real API calls; (2) Smoke-test that the API flow executes correctly.
- **Options**: `--count=1`, `--status=draft|published|closed|awarded`, `--base-url=`, `--tenant=`, `--email=`, `--password=`. Uses `APP_URL` and env `ATOMY_SEED_TENANT_ID`, `ATOMY_SEED_EMAIL`, `ATOMY_SEED_PASSWORD` when not passed.
- **Prerequisite**: API server running (e.g. `php artisan serve`); database migrated and seeded (so login user exists). Run from API app: `php artisan atomy:seed-rfq-flow --count=1 --status=published --base-url=http://127.0.0.1:8000 -n`.
- **Design**: `docs/plans/2026-03-13-rfq-flow-seed-script-design.md`.

Persistence added for flow-driven endpoints: **RfqController** (store, storeLineItem, updateStatus, lineItems, updateLineItem, destroyLineItem), **VendorInvitationController** (store), **QuoteSubmissionController** (upload, updateStatus). QuoteSubmission model fillable/casts aligned with `quote_submissions` migration.

Quote intake persistence is now tenant-scoped for `upload`, `index`, and `show`: uploads persist `uploaded_by` and `original_filename`, quote list/show endpoints return real stored submissions with `blocking_issue_count`, and RFQ overview now exposes normalization readiness buckets (`uploaded_count`, `needs_review_count`, `ready_count`) alongside the legacy accepted/progress fields for compatibility.

**Quote intake + normalization (live):** `QuoteSubmissionController` now persists live upload/show/reparse data, returns quote-submission state with processing metadata, and requeues reparses against the tenant-scoped row. `NormalizationSourceLine` and `NormalizationConflict` models match the shipped migrations (`rfq_line_item_id`, `normalization_source_line_id`, etc.). `QuoteSubmissionReadinessService` centralizes blocking rules: each RFQ line must be covered by a mapped source line, mapped lines require `source_unit_price`, and open conflicts (`resolution` null) block readiness. `NormalizationController` persists mapping/bulk-mapping/override/revert/conflict-resolution, returns `404` when the RFQ or rows are not tenant-visible, and recalculates `quote_submissions.status` to `needs_review` or `ready` after mutations. `GET /normalization/{rfqId}/conflicts` lists conflicts for the RFQ and includes `meta.has_blocking_issues` / `meta.blocking_issue_count` aggregated across that RFQ’s submissions. Feature coverage: `QuoteIngestionPipelineTest`, `NormalizationReviewWorkflowTest`.

**Quote ingestion + normalization hardening (April 2026):** `QuoteIngestionOrchestrator` now delegates into `QuotationIntelligenceCoordinator` with tenant-context propagation, RFQ-aware decision-trail writes, and delta reparse preservation for manual overrides. The API app binds tenant-scoped bridge adapters for document lookup, tenant lookup, RFQ procurement lookup, decision-trail persistence, deterministic content extraction, deterministic semantic mapping, in-memory UoM lookup, and static exchange-rate lookup. `NormalizationController` now serializes `ai_confidence`, `taxonomy_code`, and `mapping_version` so the UI can render the intelligence payload without extra joins. Feature coverage now includes `QuoteIngestionIntelligenceTest` and `QuoteIngestionPipelineTest`, both passing on isolated SQLite verification runs.

**Quote intelligence runtime mode contract (2026-04-16):** `config/atomy.php` now defines `atomy.quote_intelligence` with a deterministic default mode plus dormant LLM connection settings sourced from `QUOTE_INTELLIGENCE_MODE`, `QUOTE_INTELLIGENCE_LLM_PROVIDER`, `QUOTE_INTELLIGENCE_LLM_MODEL`, `QUOTE_INTELLIGENCE_LLM_BASE_URL`, `QUOTE_INTELLIGENCE_LLM_API_KEY`, and `QUOTE_INTELLIGENCE_LLM_TIMEOUT_SECONDS`. In the API provider, deterministic mode binds the renamed deterministic adapters, `llm` mode returns a dormant LLM adapter that raises a truthful configuration-based `QuotationIntelligenceException` when invoked without a production provider, and unsupported mode values also fail through the same domain exception path. `QuoteIngestionPipelineTest` covers the config default, direct binding resolution, and the upload-path failure contract, while `ComparisonRunWorkflowTest` confirms the controller still returns the stable 422 comparison-workflow JSON instead of a raw 500.

**Quote intelligence review follow-up (2026-04-16):** The deterministic processor now tenant-scopes the parent RFQ eager load in addition to tenant-scoping the quote submission and RFQ line items, the deterministic semantic mapper is declared `readonly` and documents its intentionally tenant-agnostic validation path, and the operator docs now spell out the `QUOTE_INTELLIGENCE_*` env contract plus the logged, sanitized failure behavior for dormant `llm` mode.

**Quote ingestion retry failure persistence (2026-04-16):** `ProcessQuoteSubmissionJob::failed()` now logs the full retry-exhaustion exception server-side, then persists `status = failed`, `error_code = MAX_RETRIES_EXCEEDED`, and the sanitized generic `QuoteIngestionOrchestrator::GENERIC_FAILURE_MESSAGE` on the submission row so API responses do not leak backend details.

**Comparison snapshot, preview, matrix, award, and approval (live):** `ComparisonRun` / `Approval` / `DecisionTrailEntry` models align with migrations (`response_payload`, `requested_by`, hash-chain columns). `ComparisonRunController` now binds `BatchQuoteComparisonCoordinatorInterface`, `ComparisonReadinessValidatorInterface`, `QuoteComparisonMatrixServiceInterface`, and `ApprovalGateServiceInterface` so preview/matrix/readiness use the live `Nexus\QuotationIntelligence` comparison boundary. `POST /comparison-runs/preview` persists a real preview `ComparisonRun` row, stores live `matrix_payload` / `scoring_payload` / `approval_payload` / `readiness_payload`, and returns the persisted run id instead of a synthetic preview token. `GET /comparison-runs/{id}/matrix` and `GET /comparison-runs/{id}/readiness` return the stored payloads for the tenant-scoped run. `POST /comparison-runs/final` creates a `final` run, stores `response_payload.snapshot`, enforces all submissions `ready` + readiness pass, and requires two ready quotes when `vendors_count >= 2` on the RFQ (from loaded attributes; otherwise at least one). `GET /comparison-runs` supports `?rfq_id=`. `AwardController` now validates award vendors against tenant-scoped RFQ submissions, persists per-vendor debrief rows in `Debrief`, serializes the frozen comparison vendor snapshot for award display, and keeps signoff idempotent in addition to creating/listing live award records. `POST /approvals/{id}/approve` gates on final run + snapshot + live readiness. `DecisionTrailRecorder::recordSnapshotFrozen` writes `comparison_snapshot_frozen` entries and now also records award debrief events. `DecisionTrailController` index/show read from `decision_trail_entries`. `PATCH /comparison-runs/{id}/scoring-model`, `POST /comparison-runs/{id}/lock`, and `POST /comparison-runs/{id}/unlock` now return explicit `422` beta-deferred responses (`COMPARISON_CONTROL_DEFERRED`) instead of mutating fake state. Tests: `ComparisonRunWorkflowTest`, `ComparisonSnapshotWorkflowTest`.

**WEB pilot (Tasks 6–7):** Hooks `use-normalization-review`, `use-quote-submission`, `use-quote-submissions`, `use-comparison-runs`, `use-comparison-run`, `use-comparison-run-matrix`, `use-comparison-run-readiness`, `use-award`, `use-freeze-comparison`, `use-comparison-readiness`; quote intake, comparison runs, award, and approvals pages now render live API data in non-mock mode while preserving mock/demo branches; normalize page is exception-first with freeze CTA; comparison runs list shows the snapshot-frozen banner only for real final runs; the comparison run detail page renders live run metadata, snapshot vendors/lines, matrix clusters, readiness blockers/warnings, and keeps the beta-only lock/unlock surface out of the alpha UI; RFQ overview normalization parsing includes optional bucket counts from API.

**Seed flow (Task 8):** `atomy:seed-rfq-flow` calls `syncNormalizationLinesForQuotes()` after HTTP uploads so comparison final can pass pilot gates.

## 2026-04-17 Alpha Task 6 Minimal Users & Roles

- `UserController` now serves live tenant-scoped `GET /api/v1/users` and `GET /api/v1/roles` data through the identity query interfaces, with invite/suspend/reactivate mutating persisted rows instead of returning synthetic success payloads.
- Unsupported `/api/v1/users/{id}/delegation-rules` and `/api/v1/users/{id}/authority-limits` routes return honest deferred responses instead of fake data, and cross-tenant user lookups fail with tenant-safe `404` semantics.
- The alpha admin surface stays narrow: tenant admin is limited to users/roles list, invite, suspend, and reactivate flows; broader identity administration remains deferred until a future task.

## Testing & Seed Data
- Added feature test coverage for auth flows, middleware enforcement, and all protected API endpoints.
- Added identity gap regression coverage for login lockout, wildcard role permissions, and authenticated session revocation.
- Added quote intake workflow validation contracts for upload payload shape, supported quote submission status values, and normalization/comparison mutation requests.
- Auth feature tests now validate token semantics and refresh tokens via the login flow using an in-memory SQLite database; protected endpoint auth checks run per-route with unique IDs and assert non-401/403 responses, JWT issuance in API tests resolves via `JwtServiceInterface`, and example tests create users directly without model factories.
- Added unit tests for `JwtService`, `ExtractsAuthContext`, and core model relationships.
- **`DatabaseSeeder`** delegates to **`PetrochemicalTenantSeeder`**: creates the fixed tenant row first (default ULID `01KKH77M4R0V8QZ1M8NB3XWWWQ` or `ATOMY_SEED_TENANT_ID`), then seeds fictional buyer **Nordfjord Process Chemicals AS**, **12** capital projects with **`project_acl`**, **56** RFQs (mix of draft / published / closed / awarded / cancelled), **150+** quote submissions, petrochemical-style line items (tiny through large bundles), vendor pool with **risk-flagged** suppliers, **`risk_items`** on risky bids, quote statuses along the real intake pipeline (`uploaded` → … → `ready`), normalization source lines + optional **open conflicts** for stuck RFQs, preview + **`final`** comparison runs with **tight vs spread** `scoring_payload`, approvals (**pending** on closed, **approved** on awarded), **awards** + **handoffs** where appropriate. Login: `user1@example.com` … `user8@example.com` / `secret`.
- Seeder aligned with `approval_history.metadata` column (replacing legacy `payload` field).
- Seeder aligned with `report_runs` columns (`schedule_id`, `report_type`, `file_path`, `parameters`).
- RFQ index endpoint now reads from `rfqs` table with tenant scoping and basic filters.
- RFQ list/show endpoints now return real RFQ data (owner, counts, ISO deadlines, pagination meta) with sorting and search support.
- **`GET /api/v1/rfqs/{rfqId}/activity`:** tenant-scoped activity feed (query `limit` 1–50, default 20); **`GET .../overview`** embeds the same feed under `data.activity` and adds blueprint aliases: `expectedQuotes`, `normalizationProgress`, `latestComparisonRun`, `approvalStatus`. Tests: `RfqOverviewActivityTest`.
- **Partner onboarding:** `../docs/01-product/scope/alpha-scope.md` and `../docs/02-release-management/current-release/release-plan.md` document the current alpha scope, active release gate, and verification posture.
- Account profile endpoints now return real user data (including tenant/role) for seeded logins.
- Seeder can use `ATOMY_SEED_TENANT_ID` for a predictable tenant in local environments.
- PHPUnit is configured to use PostgreSQL (port `5433`) with JWT + Redis env defaults for tests.

## 2026-04-16 - Alpha Task 3 Award End-To-End

- `AwardController` now requires `comparison_run_id` on create, rejects non-finalized comparison runs for award creation, and preserves tenant-safe `404` behavior for cross-tenant reads and mutations.
- Award create, debrief, and signoff are now backed by decision-trail evidence through explicit `award_created`, `award_debriefed`, and `award_signed_off` events.
- Debrief remains available as soon as an award record exists; signoff remains idempotent and records only the first successful signoff event.
- `DecisionTrailRecorder` now allocates per-run event sequences under transaction/lock so concurrent award workflow writes do not race on `(comparison_run_id, sequence)`.
- Verification:
  - `cd apps/atomy-q/API && php artisan test --filter AwardWorkflowTest` -> PASS, 9 tests, 49 assertions.

## 2026-04-17 Alpha Task 6 Follow-Up Review Remediation

- Restored backward-compatible `UserQueryInterface::emailExists(string $email, ?string $excludeUserId = null, ?string $tenantId = null)` usage while keeping tenant-scoped duplicate-email checks in the users invite flow.
- `UserQueryAdapter`, `AtomyUserQuery`, and `EloquentUserRepository` now trim tenant and excluded-user identifiers before applying tenant filters or `whereKeyNot(...)`, so whitespace cannot bypass Task 6 duplicate checks.
- `IdentityGap7Test` now covers tenant-safe cross-tenant suspend `404` behavior and confirms the roles index stays isolated to the authenticated tenant.

## 2026-04-18 Alpha Task 8 Staging Operations Readiness

- Added the non-destructive `atomy:verify-storage-disk` command and documented it as the operator storage smoke for alpha staging.
- The alpha staging contract is now explicit in the API docs: `QUOTE_INTELLIGENCE_MODE=deterministic`, `QUEUE_CONNECTION=sync`, and `NEXT_PUBLIC_USE_MOCKS=false` are the supported design-partner posture.
- `API/README.md` and `docs/02-release-management/current-release/staging-runbook.md` now point operators to the staging runbook as the source of truth for bring-up and mocks-off smoke evidence.
- The `POST /users/invite` OpenAPI contract now documents the required `{ email, name }` request body and the full user invite response shape so WEB generation no longer emits the stale `body?: never` contract.

## Middleware

- `JwtAuthenticate` — Validates Bearer JWT, extracts `auth_user_id` and `auth_tenant_id`
- `TenantContext` — Ensures tenant context is present on all protected routes

## Architecture Compliance

- All app code under `apps/atomy-q/API` (L3)
- No `use Illuminate\*` in L1 or L2 packages
- Controllers depend on interfaces, not concrete classes
- Tenant ID from JWT; never trusted from client
- `declare(strict_types=1);` in every file
- All models use `final` or `readonly` where appropriate

## Next Steps (for production readiness)

1. Finish the remaining live controller surfaces outside the quote lifecycle flow
2. Add Form Request validation classes for the next write-heavy endpoints
3. Create API Resource transformers for consistent response formatting
4. Create `adapters/Laravel/QuotationIntelligence` adapter for orchestrator ports
5. Add rate-limiting middleware
6. Add OpenAPI/Swagger documentation
7. Continue adding feature tests as the remaining flow slices are productionized

## Post-Review Remediation (PR #285)

- Removed hard-coded login backdoor from `AuthController` and now validate credentials against `users` table.
- Added fail-fast JWT secret validation and moved JWT settings into DI wiring via `AppServiceProvider`.
- Replaced synthetic success payloads in award/negotiation/setting mutation stubs with explicit `501 Not Implemented` responses.
- Aligned schema/model definitions (`Scenario` fields, `comparison_runs.discarded_by` type) and strengthened several migration indexes.
- Added missing `declare(strict_types=1);` headers in scaffolded PHP files flagged during review.

## Operational Approvals Hardening (2026-03-26)

- `OperationalApprovalController` now wraps start and decision mutations in DB transactions so the instance row, workflow row, and comments stay in sync on failure.
- `OperationalApprovalController@index` now returns tenant-scoped paginated approval instances with `meta.total`, `meta.per_page`, `meta.current_page`, and `meta.last_page` instead of loading the full list into memory.
- `PolicyEngineInterface` is bound to an app-level engine that evaluates stored policy definitions (table `policy_definitions`) using `Nexus\PolicyEngine`, logs fail-closed denials, and denies on missing/invalid policies.
- `AtomyApprovalPolicyRegistry` is registered as a singleton and reused by the `PolicyRegistryInterface` binding so the app-level policy registry resolves to one shared instance.
- `OperationalApprovalWorkflowMissingException` now carries a reason discriminator so the exception renderer returns 404 for a missing workflow row and 500 when the approval row has no workflow correlation.
- `OperationalApprovalApiTest` now asserts persisted `operational_approval_workflows` rows are created on start and transition to `approved` on decision.

## RFQ schedule milestones (2026-03-20 + 2026-03-21)

- **`rfqs` table:** nullable timestamps `expected_award_at`, `technical_review_due_at`, `financial_review_due_at` (migration `2026_03_20_000002_add_schedule_milestone_dates_to_rfqs_table.php`) for horizontal timeline / planning dates (queryable, explicit).
- **`submission_deadline`:** **Required** on create (`POST /rfqs`); **NOT NULL** in DB after migration `2026_03_21_000001_make_submission_deadline_required_on_rfqs_table.php` (existing nulls backfilled to `created_at + 14 days`). On `PUT /rfqs/{id}`, the value cannot be cleared; when both `submission_deadline` and `closing_date` are set, **`closing_date` must be ≥ `submission_deadline`** (422 otherwise).
- **API:** `closing_date` and review/award milestones remain optional on `POST`/`PUT` (`nullable|date`). Schedule fields returned as RFC 3339 atom strings (or JSON `null`) on `GET /rfqs/{id}` and under `data.rfq` on `GET /rfqs/{id}/overview`.
- **`description`:** Included on `GET /rfqs/{id}` and `GET /rfqs/{id}/overview` (`data` / `data.rfq`) as nullable text; still writable via existing `POST`/`PUT` validation.
- **OpenAPI:** `apps/atomy-q/openapi/openapi.json` updated for show + overview `rfq` shapes. Regenerate via `php artisan scramble:export` when using Scramble as source of truth.

## 2026-03-19 PR Remediation
- `AuthController` SSO flow no longer accepts client-provided `redirect_uri`; redirect URI is resolved server-side from tenant-aware config with fallback to global OIDC redirect.
- `AuthController` SSO catch-all now distinguishes server/runtime failures (reported + HTTP 500) from authentication failures (HTTP 401).
- `ProjectController::updateAcl` now enforces project-level ACL management authorization (owner/admin only) before mutating `project_acl`.
- Queue test `IdentityWelcomeQueueTest` now asserts enqueued job payload properties with a predicate closure, not class-only assertion.

## 2026-04-17 Alpha Task 2 Contract Follow-Up

- `UserController::roles` now declares an explicit `@response` shape for Scramble so `/roles` exports a concrete `data[]` item schema instead of `Array<unknown>`.
- Regenerating `apps/atomy-q/openapi/openapi.json` from the API source now preserves typed role fields (`id`, `name`, `description`, `tenant_id`, `is_system_role`) for downstream client generation.

## 2026-04-21 Projects Client Field Optionality

- `ProjectController::store` no longer requires `client_id` in request validation; missing values now default to internal sentinel `self`.
- `projects.client_id` remains NOT NULL; the internal-project sentinel `"self"` is persisted as a literal string via `ProjectController::SELF_CLIENT_ID`, so downstream reports, joins, filters, and ACL checks must special-case it until nullable client IDs are migrated.
- `ProjectController::update` now safely preserves/falls back `client_id` instead of requiring explicit client input for internal project grouping usage.
- Project list/show/store/update responses now include `project_manager_id` to support WEB edit/create manager selection workflows.
- Verification:
  - `cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/ProjectsApiTest.php` -> PASS (6 tests, 11 assertions).

## 2026-04-21 Playwright Real-API CORS Support

- Updated `config/cors.php` local allowed origins to include Playwright's default dev port (`http://localhost:3100`, `http://127.0.0.1:3100`) alongside existing `:3000` entries.
- This removes browser-side CORS failures when WEB E2E tests run in real-API mode without custom port overrides.

## 2026-04-21 Config Strict Types Compliance

- Added `declare(strict_types=1);` to `config/cors.php` immediately after `<?php` to align with repository strict-types rules for PHP files.
- Verification:
  - `cd apps/atomy-q/API && php -l config/cors.php` -> PASS (no syntax errors).
