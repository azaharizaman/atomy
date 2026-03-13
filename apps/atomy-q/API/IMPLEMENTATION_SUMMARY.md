# Implementation Summary - Atomy-Q Backend API

## Status
- **Phase**: Foundation complete (stub endpoints, models, migrations, auth)
- **Framework**: Laravel 12 (PHP 8.3)
- **Database**: PostgreSQL (25 tables migrated)
- **Cache/Queue**: Redis via `REDIS_URL`
- **Auth**: JWT (Bearer token) via `firebase/php-jwt`

## Endpoint Coverage

All **203 endpoints** from `API_ENDPOINTS.md` are registered and returning stub responses with correct HTTP status codes and response shapes.

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

## Testing & Seed Data

- Added feature test coverage for auth flows, middleware enforcement, and all protected API endpoints.
- Auth feature tests now validate token semantics and refresh tokens via the login flow using an in-memory SQLite database; protected endpoint auth checks run per-route with unique IDs and assert non-401/403 responses, JWT issuance in API tests resolves via `JwtServiceInterface`, and example tests create users directly without model factories.
- Added unit tests for `JwtService`, `ExtractsAuthContext`, and core model relationships.
- `DatabaseSeeder` now generates ample mock data across all 25 tables for realistic API seed state.
- Seeder aligned with `approval_history.metadata` column (replacing legacy `payload` field).
- Seeder aligned with `report_runs` columns (`schedule_id`, `report_type`, `file_path`, `parameters`).
- RFQ index endpoint now reads from `rfqs` table with tenant scoping and basic filters.
- RFQ list/show endpoints now return real RFQ data (owner, counts, ISO deadlines, pagination meta) with sorting and search support.
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

1. Wire controllers to actual Eloquent queries (replace stubs with DB operations)
2. Add Form Request validation classes for each endpoint
3. Create API Resource transformers for consistent response formatting
4. Implement real authentication flow with database-backed users
5. Create `adapters/Laravel/QuotationIntelligence` adapter for orchestrator ports
6. Add rate-limiting middleware
7. Add OpenAPI/Swagger documentation
8. Write feature tests for critical flows

## Post-Review Remediation (PR #285)

- Removed hard-coded login backdoor from `AuthController` and now validate credentials against `users` table.
- Added fail-fast JWT secret validation and moved JWT settings into DI wiring via `AppServiceProvider`.
- Replaced synthetic success payloads in award/negotiation/setting mutation stubs with explicit `501 Not Implemented` responses.
- Aligned schema/model definitions (`Scenario` fields, `comparison_runs.discarded_by` type) and strengthened several migration indexes.
- Added missing `declare(strict_types=1);` headers in scaffolded PHP files flagged during review.
