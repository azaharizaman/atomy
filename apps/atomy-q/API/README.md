# Atomy-Q API (Laravel)

This is the backend API for the Atomy‑Q Quote Comparison & Procurement platform. It exposes the `/api/v1` REST endpoints used by the WEB app and includes JWT authentication, multi‑tenant scoping, and stubbed controllers for the full endpoint surface.

**Design-partner alpha:** Current scope and release posture are documented in [`../docs/01-product/scope/alpha-scope.md`](../docs/01-product/scope/alpha-scope.md) and [`../docs/02-release-management/current-release/release-plan.md`](../docs/02-release-management/current-release/release-plan.md).
**Staging bring-up:** [`../docs/02-release-management/current-release/staging-runbook.md`](../docs/02-release-management/current-release/staging-runbook.md) is the source of truth for alpha staging.
**Engineering standards:** [`../docs/04-engineering/standards/`](../docs/04-engineering/standards/) contains the coding, branching, done, testing, and security baselines.

## Alpha staging posture

- Deterministic quote intelligence is the alpha staging default.
- `QUEUE_CONNECTION=sync` is the design-partner path for alpha staging unless the team explicitly chooses async later.
- Verify storage wiring with `php artisan atomy:verify-storage-disk`.
- Keep `../docs/02-release-management/current-release/staging-runbook.md` open when bringing the environment up.

## AI launch readiness

- The AI-first alpha handoff is documented in:
  - [`../../../docs/superpowers/specs/2026-04-23-atomy-q-global-ai-fallback-design.md`](../../../docs/superpowers/specs/2026-04-23-atomy-q-global-ai-fallback-design.md)
  - [`../../../docs/superpowers/plans/2026-04-23-atomy-q-ai-launch-readiness-and-operational-hardening.md`](../../../docs/superpowers/plans/2026-04-23-atomy-q-ai-launch-readiness-and-operational-hardening.md)
- The verification entry point for the documentation-led AI handoff is `composer verify:atomy-q-ai-insights-governance-reporting` from the monorepo root.
- Rollback posture is environment-level `AI_MODE=off`; the core RFQ chain must remain manually operable and AI-only surfaces must report truthful unavailability.
- The launch-readiness runbook should stay aligned with the API env contract in `.env.example` and the status contract exposed at `GET /api/v1/ai/status`.

## Quote intelligence modes

- `deterministic` is the supported alpha staging default and is the mode used when `QUOTE_INTELLIGENCE_MODE` is unset.
- `llm` is defined in the environment contract, but it remains dormant until a production provider adapter is wired in.
- In live operation, alpha does not silently fall back from `llm` to deterministic mode; misconfigured or unimplemented `llm` settings fail through the quote-intelligence exception path, are logged server-side, and return the existing sanitized API failure shape for the calling workflow.

## Requirements
- PHP 8.3+
- Composer
- PostgreSQL (recommended for local dev; tests can use SQLite in memory)
- Redis (optional for caching/queues in local dev)

## Setup
1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Create your environment file**
   ```bash
   cp .env.example .env
   ```
   `.env.example` is already staged for alpha; use it as the copyable contract and only change deployment-specific values.

3. **Generate app key**
   ```bash
   php artisan key:generate
   ```

4. **Run migrations and seed data**
   ```bash
   php artisan migrate --seed
   ```

5. **Run the API**
   ```bash
   php artisan serve
   ```
   The API will be available at `http://localhost:8000/api/v1`.

## OpenAPI (Scramble)

Interactive docs (local): `GET /docs/api` and `GET /docs/api.json` (see `config/scramble.php`; `api_path` is `api/v1`).

Export a static spec for the WEB client generator (written to the monorepo `apps/atomy-q/openapi/` folder):

```bash
php artisan scramble:export --path=../openapi/openapi.json
```

Optional: set `API_VERSION` in `.env` for the `info.version` field in the exported document.

## Environment Contract

Use [`./.env.example`](./.env.example) as the copyable env contract. See **Alpha staging posture** above for the operational defaults and verification commands.

## Quote intake & comparison (pilot)

- **Quote submissions** move through normalization review; `QuoteSubmissionReadinessService` marks blockers (unmapped RFQ lines, missing unit prices, open conflicts) and drives `needs_review` vs `ready`.
- **POST `/comparison-runs/final`** persists a **final** `comparison_runs` row with an immutable `response_payload.snapshot` (normalized lines, resolutions, currency meta). It requires every quote on the RFQ to be `ready` and free of blocking issues; when the RFQ expects ≥2 vendors (`vendors_count` from list/overview), at least **two** ready submissions are required.
- **POST `/approvals/{id}/approve`** requires a linked **final** comparison run whose snapshot includes `normalized_lines`, and re-checks that all submissions are still `ready` without blocking issues.
- **Decision trail**: freezing a snapshot appends a `decision_trail_entries` row with `event_type = comparison_snapshot_frozen` (hash-chained per run). **GET `/decision-trail`** returns tenant-scoped entries; filter with `?rfq_id=`.
- **`atomy:seed-rfq-flow`**: after HTTP quote upload, syncs `normalization_source_lines` in the database for each seeded quote so the comparison-final step can succeed against the new gates (still requires a running API and valid login).

## Running API Tests

From `apps/atomy-q/API`:

1. Install dependencies
   ```bash
   composer install
   ```

2. Ensure a **dedicated test database** is available (do not use your development DB).

   This project’s `phpunit.xml` uses `DB_CONNECTION=pgsql` with:
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=5433`
   - `DB_DATABASE=atomy_test`
   - `DB_USERNAME=postgres`
   - `DB_PASSWORD=secret`

   Create a separate database (e.g. `atomy_test`) and set these in `apps/atomy-q/API/.env` (or rely on `phpunit.xml`) so tests never run against production or shared dev data.

3. Run all tests
   ```bash
   php artisan test
   ```

4. Run a specific test (example)
   ```bash
   php artisan test --filter ProjectAclTest
   ```

Notes:
- Tests run via PHPUnit (see `phpunit.xml`).
