# Staging Alpha Runbook

## Purpose

This runbook is the operator-facing source of truth for bringing up the Atomy-Q design-partner staging environment and proving the mocks-off alpha golden path against deployed API and WEB hosts.

Use this document when you need to:
- validate that staging is wired for real tenant-scoped API traffic
- confirm quote uploads can persist to the configured storage disk
- run the alpha smoke flow with `NEXT_PUBLIC_USE_MOCKS=false`
- capture release evidence for staging readiness

## Supported staging posture

The supported design-partner alpha posture is intentionally narrow and deterministic:
- WEB runs against the deployed API with `NEXT_PUBLIC_USE_MOCKS=false`.
- API runs with `QUOTE_INTELLIGENCE_MODE=deterministic`.
- Staging uses `QUEUE_CONNECTION=sync` for the main alpha path.
- Quote upload processes inline through the existing API dispatch boundary, so the main smoke path does not require a worker.
- Storage must be a real writable disk. The staged `.env.example` contract uses `FILESYSTEM_DISK=s3`.
- `MAIL_MAILER=log` is acceptable for alpha staging. The Users & Roles invite check is about pending-activation record creation, not outbound delivery.

If the team changes staging to asynchronous queue processing, this runbook is no longer complete. It must then be updated to include worker bring-up, failed-job inspection, and post-upload queue verification before it can be treated as current.

## Required environment variables

Confirm these values are set in the deployed environment before bring-up.

API-critical variables:
- `APP_ENV=staging`
- `APP_URL` must be set to the deployed API origin
- `APP_KEY` must be set to the deployed Laravel application key
- `DB_CONNECTION=pgsql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `JWT_SECRET` must be set to a non-empty deployed signing secret
- `QUEUE_CONNECTION=sync`
- `FILESYSTEM_DISK=s3`
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`
- `AWS_ENDPOINT` and `AWS_URL` when using MinIO or another S3-compatible endpoint
- `AWS_USE_PATH_STYLE_ENDPOINT=true`
- `QUOTE_INTELLIGENCE_MODE=deterministic`
- `MAIL_MAILER=log`
- `ATOMY_SEED_TENANT_ID`, `ATOMY_SEED_EMAIL`, `ATOMY_SEED_PASSWORD` if operators plan to use `atomy:seed-rfq-flow`

WEB-critical variables:
- `NEXT_PUBLIC_APP_URL` must be set to the deployed WEB origin
- `NEXT_PUBLIC_API_URL` must be set to the deployed API base including `/api/v1`
- `NEXT_PUBLIC_USE_MOCKS=false`
- `PLAYWRIGHT_BASE_URL` must point at the deployed WEB origin when running Playwright against the live host

CORS and session posture:
- API `config/cors.php` allowed origins must include the deployed WEB origin.
- Credentialed requests must remain enabled for the WEB auth/session flow.

## Bring-up sequence

Run the API-side bring-up in this order.

1. Install PHP dependencies if the release artifact does not already contain them.

```bash
cd apps/atomy-q/API && composer install --no-interaction --prefer-dist --optimize-autoloader
```

2. Clear stale cached config before applying the staging env.

```bash
cd apps/atomy-q/API && php artisan optimize:clear
```

3. Apply schema changes.

```bash
cd apps/atomy-q/API && php artisan migrate --force
```

4. If the shared staging environment requires baseline seeded reference data, run the application seeder.

```bash
cd apps/atomy-q/API && php artisan db:seed --force
```

5. Verify the configured storage disk can write, read, and clean up a smoke object.

```bash
cd apps/atomy-q/API && php artisan atomy:verify-storage-disk --disk=s3
```

6. If you need a seeded RFQ journey for shared-environment verification, create it only after migrations and storage verification pass.

```bash
cd apps/atomy-q/API && php artisan atomy:seed-rfq-flow --count=1 --status=published --base-url="$APP_URL"
```

7. Confirm the WEB build is using the deployed API origin with mocks disabled.

```bash
cd apps/atomy-q/WEB && npm install
cd apps/atomy-q/WEB && npm run build
```

8. If operators want a browser-assisted smoke against the deployed host, point Playwright at the live WEB URL.

```bash
cd apps/atomy-q/WEB && PLAYWRIGHT_BASE_URL="$NEXT_PUBLIC_APP_URL" E2E_USE_REAL_API=1 PLAYWRIGHT_USE_EXISTING_SERVER=1 npm run test:e2e -- tests/rfq-lifecycle-e2e.spec.ts
```

## Storage verification

The alpha staging path assumes quote files land on a real writable storage disk before intake processing continues.

Primary verification command:

```bash
cd apps/atomy-q/API && php artisan atomy:verify-storage-disk --disk=s3
```

Expected result:
- the command exits successfully
- the temporary smoke object is written and read back on the configured disk
- the temporary smoke object is deleted before the command finishes

If staging uses a non-S3 disk for a short-lived internal environment, rerun the same command against that exact disk name and record it in the release evidence.

## Queue posture

The current design-partner alpha staging posture uses `QUEUE_CONNECTION=sync`.

Operational meaning:
- quote upload runs inline for the main smoke path
- the operator should not start a queue worker to validate the primary alpha journey
- a quote that stays in `uploaded` after the upload request is a staging failure, not an expected queue delay

This is an honest contract, not a temporary assumption. If the team switches to an async queue connection, this runbook must be revised to include:
- worker start commands
- queue backlog inspection
- failed job inspection
- explicit post-upload verification that the queued quote-processing job completed

## Seed policy

Use the smallest seed footprint that still proves staging is healthy.

Approved policy:
- the canonical alpha smoke is a mocks-off operator journey starting from real tenant registration on the deployed WEB
- use `atomy:seed-rfq-flow` only when you need a shared-environment RFQ fixture for operator rehearsal, API validation, or regression confirmation outside the clean registration journey
- do not use WEB mock mode for staging validation
- do not treat seed data as a substitute for the tenant registration, login, and workflow smoke below

Seed command for shared-environment verification:

```bash
cd apps/atomy-q/API && php artisan atomy:seed-rfq-flow --count=1 --status=published --base-url="$APP_URL"
```

The seed command requires a reachable API, a valid login path, and usable seed credentials in either the CLI flags or `ATOMY_SEED_*` variables.

## Mocks-off golden-path smoke

Run this checklist against the deployed WEB with `NEXT_PUBLIC_USE_MOCKS=false` and the deployed API reachable at `NEXT_PUBLIC_API_URL`.

1. Open the deployed WEB origin and confirm the auth shell loads without browser-console CORS errors.
   Expected: the login page renders and the link to `register a new company` is visible.

2. Register a fresh tenant from `/register-company`.
   Enter a unique company name, tenant code, owner name, owner email, and owner password.
   Expected: the form succeeds, the owner account is created, and the browser is redirected into the authenticated workspace.

3. Log out and log back in with the owner credentials you just created.
   Expected: login succeeds without `Invalid credentials`, token/bootstrap failures, or redirect loops.

4. Create a new RFQ from the live dashboard flow.
   Populate the required fields, save the RFQ, and add at least two line items.
   Expected: the RFQ appears in the live RFQ list and opens to a real overview page, not seed fallback behavior.

5. Publish the RFQ and invite at least two vendors from the Vendors section.
   Expected: each invite appears in the invitation list with the intended vendor name and email. Delivery is not required for this smoke, but the pending invitation records must persist.

6. Upload quote files for the invited vendors through the quote-intake workflow.
   Expected: each upload reaches the API successfully and writes to the configured storage disk. Because `QUEUE_CONNECTION=sync`, processing runs inline and the quote should not remain stuck in `uploaded` as a normal steady state.

7. Open quote intake and normalization for each uploaded quote.
   Resolve any mapping conflicts until the submissions are no longer blocked.
   Expected: readiness becomes clear enough to proceed, and the normalization UI reflects live API data rather than mocks.

8. Open the comparison workflow and verify readiness before freezing the final run.
   Expected: readiness does not report blocking issues for the RFQ, and the final comparison action is available.

9. Freeze the final comparison run.
   Expected: the final comparison succeeds, a real final run is created, and the RFQ now has comparison evidence suitable for award creation.

10. Open the Award section, create the award from the final comparison, and sign it off.
    Expected: the award record is created successfully, the recommended vendor and amount are visible, and signoff changes the award state to signed off without API validation errors.

11. Open `Settings -> Users & Roles` and invite one additional user.
    Enter a real email-format address and name.
    Expected: the invite succeeds, a new row appears in the tenant user list, and the new user status is pending activation. Do not treat email delivery as part of this smoke because staging uses `MAIL_MAILER=log` by default.

12. Capture evidence immediately after the smoke completes.
    Expected: screenshots, command output, and release-checklist notes are recorded while the browser state and logs are still available.

## Evidence capture

Capture enough evidence that another operator can reconstruct what was verified.

Minimum evidence set:
- staging date and operator name
- deployed WEB origin and deployed API origin
- confirmation that `NEXT_PUBLIC_USE_MOCKS=false`
- confirmation that `QUEUE_CONNECTION=sync`
- confirmation that `QUOTE_INTELLIGENCE_MODE=deterministic`
- output of `php artisan migrate --force`
- output of `php artisan atomy:verify-storage-disk --disk=s3`
- output of `php artisan atomy:seed-rfq-flow --count=1 --status=published --base-url="$APP_URL"` if the seed path was used
- screenshots of tenant registration success, RFQ overview, vendor invite state, quote intake/readiness, final comparison, award signoff, and Users & Roles pending activation row
- any accepted constraints or observed non-blocking issues

Record the evidence in `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md` as part of the staging-readiness pass.

## Failure triage

### CORS or auth failures

Symptoms:
- login or registration fails in-browser despite healthy API credentials
- browser console shows blocked cross-origin requests
- authenticated pages bounce back to login or fail to bootstrap `/me`

First checks:
- confirm `NEXT_PUBLIC_API_URL` points to the deployed API and includes `/api/v1`
- confirm API `APP_URL` and WEB `NEXT_PUBLIC_APP_URL` match the deployed origins being used
- confirm the deployed WEB origin is allowed in API CORS config
- confirm `JWT_SECRET` is set in the deployed API environment

### Storage failures

Symptoms:
- quote upload fails during request handling
- upload appears to succeed but the quote file cannot be read back or processed
- storage-related exceptions appear in API logs

First checks:
- rerun `cd apps/atomy-q/API && php artisan atomy:verify-storage-disk --disk=s3`
- confirm `FILESYSTEM_DISK=s3`
- confirm bucket, endpoint, region, and credentials are correct for the deployed environment
- confirm the target bucket or endpoint allows write, read, and delete for the configured credentials

### Queue or runtime failures

Symptoms:
- uploaded quotes remain stuck in `uploaded`
- intake or readiness never moves forward after a successful upload
- runtime errors appear during quote processing or comparison final

First checks:
- confirm `QUEUE_CONNECTION=sync` in the deployed API environment
- confirm `QUOTE_INTELLIGENCE_MODE=deterministic`
- inspect API logs around the upload and readiness requests
- if the environment was changed to async, stop using this runbook as-is and add worker plus failed-job verification before retrying

### Users invite failures

Symptoms:
- `Users & Roles` fails to load
- invite submission returns an API error
- invited user does not appear in the tenant roster

First checks:
- confirm the authenticated user is still in the intended tenant workspace
- reload the page and verify `/users` and `/roles` responses are healthy
- confirm the invited email is not already present in that tenant
- remember that success means a pending-activation user row is created; outbound email delivery is not required while `MAIL_MAILER=log`
