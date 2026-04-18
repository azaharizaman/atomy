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

The full staging bring-up and smoke sequence is in [`../docs/STAGING_ALPHA_RUNBOOK.md`](../docs/STAGING_ALPHA_RUNBOOK.md).

## Tests

Essential local entry points:

```bash
npm run test:unit                       # fast hook/component coverage during local work
npm run test:e2e                        # full browser regression pass before CI/review
npm run test:e2e -- tests/screen-smoke.spec.ts  # quick deployed/local smoke validation
```

For E2E runs that need browser installation:

```bash
npm run test:e2e:install
```

Hook changes must follow the enforced coverage standard in [`docs/HOOK_TEST_STANDARD.md`](docs/HOOK_TEST_STANDARD.md): every hook needs mock-mode fallback coverage plus the live-mode transport error, valid payload, undefined payload, and malformed payload tests.
