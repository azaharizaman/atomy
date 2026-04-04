# Implementation Summary - Atomy-Q Backend API

## Status
- **Phase**: Foundation complete, quote lifecycle productionized
- **Framework**: Laravel 12 (PHP 8.3)
- **Database**: PostgreSQL (25 tables migrated)
- **Cache/Queue**: Redis via `REDIS_URL`
- **Auth**: JWT (Bearer token) via `firebase/php-jwt`

## Authentication

### `POST /api/v1/auth/login` (email + password)

- **Request body:** `email`, `password` only. Tenant is **not** sent by the client; it is read from `users.tenant_id` after a successful match (`users.email` is globally unique).
- Validates credentials against Eloquent `User` (`email`, `password_hash`) and returns JWT access + refresh tokens (claims include the user’s `tenant_id`).
- **Does not** use `nexus/identity-operations` for this path.
- `AuthController` injects `JwtServiceInterface` and `PasswordResetServiceInterface` (bound to `PasswordResetService`) in the constructor (login/refresh do not require Nexus Identity write/query adapters).

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

- `UserAuthenticationCoordinatorInterface` is resolved **only when** the SSO action runs (`app(UserAuthenticationCoordinatorInterface::class)` inside `sso()`).
- `nexus/laravel-identity-adapter` registers `IdentityAdapterServiceProvider`, which wires `OidcSsoProviderAdapter` and `IdentityOperationsAdapter`.
- **Atomy-Q bindings** (see `App\Providers\AppServiceProvider` and `App\Services\Identity\*`):
  - **Eloquent-backed**: `UserPersistInterface`, `UserQueryInterface`, `PasswordHasherInterface`, `UserAuthenticatorInterface` (maps to `users` table; JIT SSO provisioning writes `tenant_id`, `email`, `name`, `password_hash`, `status`, etc.).
  - **Stubs / no-ops until productized**: Identity `TokenManagerInterface`, `SessionManagerInterface` (JWT remains the app token), MFA enrollment/verification services, `PermissionQueryInterface` / `RoleQueryInterface` (no RBAC catalog tables yet), `AuditLogRepositoryInterface` (in-memory no-op sink so `IdentityOperationsAdapter::log` does not throw).
- `AuthTest::test_sso_*` exercise OIDC init + mock callback (`mock_authorization_code` + `SSO_MOCK_DISCOVERY_DOCUMENT`) end-to-end against these bindings.

### Environment

- `JWT_SECRET` must be non-empty (`php artisan key:generate` sets `APP_KEY`; JWT uses `config/jwt.php` / `.env` `JWT_SECRET`).

- **`GET /api/v1/feature-flags`:** Returns `{ data: { projects: bool, tasks: bool } }` mirroring `config('features.*')` for WEB nav and graceful degradation when routes return 404.

## OpenAPI (Scramble)

- **Package:** `dedoc/scramble` (dev). Config: `config/scramble.php` — documents routes under `api/v1` only.
- **Interactive UI:** `GET /docs/api` and `GET /docs/api.json` when the app is running.
- **Static export (for WEB client generation):** `php artisan scramble:export --path=../openapi/openapi.json` from `apps/atomy-q/API` writes `apps/atomy-q/openapi/openapi.json`. Regenerate after meaningful API contract changes.

## RFQ lifecycle mutations (2026-04-03)

- `POST /api/v1/rfqs/{id}/duplicate` now creates a real tenant-scoped duplicate RFQ and copies line items through `Nexus\SourcingOperations`.
- `PUT /api/v1/rfqs/{id}/draft` now persists draft-editable RFQ fields instead of echoing the request payload.
- `POST /api/v1/rfqs/bulk-action` now supports real persisted bulk `close` and `cancel` actions with honest affected counts.
- `PATCH /api/v1/rfqs/{id}/status` now delegates to the shared RFQ lifecycle transition policy rather than controller-local status assignment.
- `POST /api/v1/rfqs/{id}/invitations/{invId}/remind` now enforces tenant-scoped RFQ/invitation lookup and stores `vendor_invitations.reminded_at`.
- Layer 3 bindings live in `AppServiceProvider`; the Laravel adapters sit under `App\Services\SourcingOperations\*`.

## RFQ lifecycle hardening (2026-04-04)

- Added Laravel bindings for orchestrator-local sourcing transaction and status-policy adapters so `SourcingOperationsCoordinator` stays Layer 2 clean while still using DB-backed transactions in Layer 3.
- `AtomyRfqInvitationReminder` now dispatches a real `Nexus\Notifier` email reminder instead of log-only behavior, using the new `rfq-invitation-reminder` template and DTO-carried invitation `channel`.
- Invitation reminder state is now persisted only after notification dispatch succeeds, preventing `reminded_at` from being written on delivery failure.
- Draft saves now preserve explicit nulls for nullable fields that are actually present in the request instead of falling back through `??` to old values.
- Duplicate RFQs now create `rfq_number` and insert inside a single transaction with retry on unique-key conflicts, closing the atomicity gap between number generation and persistence.
- Bulk-action execution now rejects preloaded record sets that do not exactly match the requested RFQ ids, preventing writes against unvalidated identifiers.

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

**Comparison snapshot, award, and approval (live):** `ComparisonRun` / `Approval` / `DecisionTrailEntry` models align with migrations (`response_payload`, `requested_by`, hash-chain columns). `ComparisonSnapshotService` builds tenant-scoped frozen payloads from RFQ line items + normalization source lines and now includes frozen comparison vendor summaries for the award screen. `POST /comparison-runs/final` creates a `final` run, stores `response_payload.snapshot`, enforces all submissions `ready` + readiness pass, and requires two ready quotes when `vendors_count >= 2` on the RFQ (from loaded attributes; otherwise at least one). `GET /comparison-runs` supports `?rfq_id=`. `AwardController` now validates award vendors against tenant-scoped RFQ submissions, persists per-vendor debrief rows in `Debrief`, serializes the frozen comparison vendor snapshot for award display, and keeps signoff idempotent in addition to creating/listing live award records. `POST /approvals/{id}/approve` gates on final run + snapshot + live readiness. `DecisionTrailRecorder::recordSnapshotFrozen` writes `comparison_snapshot_frozen` entries and now also records award debrief events. `DecisionTrailController` index/show read from `decision_trail_entries`. Tests: `ComparisonSnapshotWorkflowTest`, `AwardWorkflowTest`.

**WEB pilot (Tasks 6–7):** Hooks `use-normalization-review`, `use-quote-submission`, `use-quote-submissions`, `use-comparison-runs`, `use-award`, `use-freeze-comparison`, `use-comparison-readiness`; quote intake, comparison runs, award, and approvals pages now render live API data in non-mock mode while preserving mock/demo branches; normalize page is exception-first with freeze CTA; comparison runs list shows snapshot-frozen banner + decision trail link; RFQ overview normalization parsing includes optional bucket counts from API.

**Seed flow (Task 8):** `atomy:seed-rfq-flow` calls `syncNormalizationLinesForQuotes()` after HTTP uploads so comparison final can pass pilot gates.

## Testing & Seed Data

- Added feature test coverage for auth flows, middleware enforcement, and all protected API endpoints.
- Added quote intake workflow validation contracts for upload payload shape, supported quote submission status values, and normalization/comparison mutation requests.
- Auth feature tests now validate token semantics and refresh tokens via the login flow using an in-memory SQLite database; protected endpoint auth checks run per-route with unique IDs and assert non-401/403 responses, JWT issuance in API tests resolves via `JwtServiceInterface`, and example tests create users directly without model factories.
- Added unit tests for `JwtService`, `ExtractsAuthContext`, and core model relationships.
- **`DatabaseSeeder`** delegates to **`PetrochemicalTenantSeeder`**: single fixed tenant (default ULID `01KKH77M4R0V8QZ1M8NB3XWWWQ` or `ATOMY_SEED_TENANT_ID`), fictional buyer **Nordfjord Process Chemicals AS**, **12** capital projects with **`project_acl`**, **56** RFQs (mix of draft / published / closed / awarded / cancelled), **150+** quote submissions, petrochemical-style line items (tiny through large bundles), vendor pool with **risk-flagged** suppliers, **`risk_items`** on risky bids, quote statuses along the real intake pipeline (`uploaded` → … → `ready`), normalization source lines + optional **open conflicts** for stuck RFQs, preview + **`final`** comparison runs with **tight vs spread** `scoring_payload`, approvals (**pending** on closed, **approved** on awarded), **awards** + **handoffs** where appropriate. Login: `user1@example.com` … `user8@example.com` / `secret`.
- Seeder aligned with `approval_history.metadata` column (replacing legacy `payload` field).
- Seeder aligned with `report_runs` columns (`schedule_id`, `report_type`, `file_path`, `parameters`).
- RFQ index endpoint now reads from `rfqs` table with tenant scoping and basic filters.
- RFQ list/show endpoints now return real RFQ data (owner, counts, ISO deadlines, pagination meta) with sorting and search support.
- **`GET /api/v1/rfqs/{rfqId}/activity`:** tenant-scoped activity feed (query `limit` 1–50, default 20); **`GET .../overview`** embeds the same feed under `data.activity` and adds blueprint aliases: `expectedQuotes`, `normalizationProgress`, `latestComparisonRun`, `approvalStatus`. Tests: `RfqOverviewActivityTest`.
- **Partner onboarding:** `../ALPHA_DESIGN_PARTNER_SUPPORTED_FLOWS.md` documents supported vs stubbed flows for design-partner alpha (identity, buyer-only scope, `comparison_approval` variant, verification commands).
- Account profile endpoints now return real user data (including tenant/role) for seeded logins.
- Seeder can use `ATOMY_SEED_TENANT_ID` for a predictable tenant in local environments.
- PHPUnit is configured to use PostgreSQL (port `5433`) with JWT + Redis env defaults for tests.

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
