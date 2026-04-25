# Atomy-Q Frontend (WEB)

This is the Next.js frontend for the Atomy-Q quote comparison and procurement platform.

## Local Development

1. Install dependencies:
   ```bash
   npm install
   ```
2. Copy `.env.example` to `.env.local` and set local values.
3. Start the dev server:
   ```bash
   npm run dev
   ```

Mock mode is local-only. Use `NEXT_PUBLIC_USE_MOCKS=true` only for local UI work or seed-backed demos. Never enable mock mode in staging or production.

## Generated API Client

The OpenAPI document is exported from the Laravel API into `../openapi/openapi.json` (see [`../API/README.md`](../API/README.md)).

Regenerate the typed client after API changes:

```bash
npm run generate:api
```

The generated output is committed under `src/generated/api/` so builds do not depend on a local PHP API checkout.

## Staging Validation

Staging validation should use the deployed WEB hostname and the deployed API.

- `NEXT_PUBLIC_API_URL` must point at the deployed API and include `/api/v1`.
- `NEXT_PUBLIC_USE_MOCKS=false`.
- `PLAYWRIGHT_BASE_URL` should point at the deployed WEB hostname for manual checks or smoke tests.
- Set `PLAYWRIGHT_BASE_URL` in `.env.local` for local/manual runs or inject it as an environment variable in CI.

The full staging bring-up and smoke sequence is in [`../docs/02-release-management/current-release/staging-runbook.md`](../docs/02-release-management/current-release/staging-runbook.md).

## AI launch readiness

- The WEB-side AI posture is driven by the shared status contract at `GET /api/v1/ai/status` and the docs-led handoff in:
  - [`../../../docs/superpowers/specs/2026-04-23-atomy-q-global-ai-fallback-design.md`](../../../docs/superpowers/specs/2026-04-23-atomy-q-global-ai-fallback-design.md)
  - [`../../../docs/superpowers/plans/2026-04-23-atomy-q-ai-launch-readiness-and-operational-hardening.md`](../../../docs/superpowers/plans/2026-04-23-atomy-q-ai-launch-readiness-and-operational-hardening.md)
- When AI is off or degraded, WEB should keep the manual RFQ path visible and show only scoped unavailable messaging for the AI-powered surfaces.
- The documentation-led verification entry point is `composer verify:atomy-q-ai-insights-governance-reporting` from the monorepo root, plus the WEB unit and E2E commands listed in `docs/superpowers/plans/2026-04-23-atomy-q-ai-launch-readiness-and-operational-hardening.md` under `Runbook Output` -> `Verification Matrix`.

## Tests

Essential local entry points:

```bash
npm run test:unit                       # fast hook/component coverage during local work
npm run test:e2e                        # full browser regression pass before CI/review
npm run test:e2e -- tests/screen-smoke.spec.ts  # quick deployed/local smoke validation
```

E2E ownership is split by surface:

```bash
npx playwright test tests/dashboard-nav.spec.ts tests/rfq-alpha-journeys.spec.ts
npx playwright test tests/projects-tasks-smoke.spec.ts tests/settings-users-smoke.spec.ts
E2E_USE_REAL_API=true NEXT_PUBLIC_USE_MOCKS=false npx playwright test tests/auth.spec.ts tests/rfq-lifecycle-e2e.spec.ts
NEXT_PUBLIC_ALPHA_MODE=true npx playwright test tests/dashboard-nav.spec.ts tests/rfq-alpha-journeys.spec.ts
```

On Windows, inline environment-variable prefixes differ by shell. Use `cross-env` or the shell-native form instead of the Unix examples above:

```bash
# cross-env (PowerShell/CMD compatible)
npx cross-env E2E_USE_REAL_API=true NEXT_PUBLIC_USE_MOCKS=false playwright test tests/auth.spec.ts
npx cross-env NEXT_PUBLIC_ALPHA_MODE=true playwright test tests/dashboard-nav.spec.ts

# PowerShell-native form
$env:E2E_USE_REAL_API='true'; $env:NEXT_PUBLIC_USE_MOCKS='false'; npx playwright test tests/auth.spec.ts
$env:NEXT_PUBLIC_ALPHA_MODE='true'; npx playwright test tests/dashboard-nav.spec.ts
```

The RFQ alpha suite owns mocked browser journeys. Projects/tasks and settings/users are adjacent smoke coverage because those routes are hidden when `NEXT_PUBLIC_ALPHA_MODE=true`. The live RFQ lifecycle spec is intentionally narrow and should be run only with the local test API available.

For E2E runs that need browser installation:

```bash
npm run test:e2e:install
```

Hook changes must follow the testing strategy in [`../docs/04-engineering/standards/testing-strategy.md`](../docs/04-engineering/standards/testing-strategy.md): every changed hook needs mock-mode behavior coverage plus the live-mode transport error, valid payload, undefined payload, and malformed payload tests.
