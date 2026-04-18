# Alpha Task 8 Staging Operations Readiness Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the Atomy-Q alpha staging environment configurable and operable without tribal knowledge by shipping a copyable env contract, an operator runbook, a thin verification layer, and checklist-ready staging evidence fields.

**Architecture:** Keep Task 8 documentation-first. The runtime decision for alpha staging is synchronous deterministic quote ingestion via the existing queue boundary, so the main deliverables are explicit env examples, README-to-runbook handoff, one storage verification command, and a staging smoke runbook that records evidence in the release checklist. Avoid broad deployment automation or speculative platform abstractions.

**Tech Stack:** Laravel 12, PHP 8.3, Artisan console commands, PHPUnit feature tests, Next.js 16, Markdown documentation, Playwright command recipes, PostgreSQL, Redis, S3/MinIO-compatible storage.

---

## File Map

- `apps/atomy-q/API/.env.example`
  - Copyable API staging contract with required, conditional, and dormant keys clearly grouped.
- `apps/atomy-q/WEB/.env.example`
  - Copyable WEB staging contract with explicit `NEXT_PUBLIC_USE_MOCKS=false` posture and staging URL examples.
- `apps/atomy-q/API/README.md`
  - High-level API environment/runtime contract and links to the dedicated staging runbook.
- `apps/atomy-q/WEB/README.md`
  - High-level WEB staging contract and smoke guidance, with the runbook as the operational source of truth.
- `apps/atomy-q/docs/STAGING_ALPHA_RUNBOOK.md`
  - New operator-facing bring-up and smoke document for the design-partner staging environment.
- `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
  - Release evidence ledger extended with Task 8 staging fields and operator-recorded outcomes.
- `apps/atomy-q/API/app/Console/Commands/VerifyStorageDiskCommand.php`
  - New non-destructive Artisan command that writes and reads a test object on the configured disk and reports pass/fail details.
- `apps/atomy-q/API/tests/Feature/Console/VerifyStorageDiskCommandTest.php`
  - Feature coverage for the new storage verification command using a fake local disk.
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`
  - Existing quote upload boundary; lock the staging runtime documentation against its current sync-vs-queue behavior.
- `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`
  - Existing quote submission feature suite; extend only if needed to lock the chosen staging runtime assumptions.
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
  - Record API-side Task 8 operator/runtime changes if command or runtime docs change.
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
  - Record WEB-side Task 8 documentation/smoke posture changes if needed.
- `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`
  - Add the Task 8 implementation-plan link under Section 9.

## Task 1: Lock The Staging Runtime Decision Against The Current Queue Boundary

**Files:**
- Modify if needed: `apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php`
- Modify if needed: `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`

- [ ] **Step 1: Inspect the existing quote-upload dispatch behavior before editing docs**

Read the current upload path and confirm the staging decision is compatible with current code:

```bash
cd /home/azaharizaman/dev/atomy && sed -n '120,180p' apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php
```

Expected: `dispatchProcessingJob()` shows synchronous execution when `config('queue.default')` is `sync` or `null`, and queued dispatch otherwise.

- [ ] **Step 2: Add a focused failing test only if the current suite does not already lock the runtime assumption**

If `QuoteSubmissionWorkflowTest.php` does not already prove that `QUEUE_CONNECTION=sync` yields an immediate processed result, add a focused test like:

```php
public function test_quote_upload_runs_processing_inline_when_queue_connection_is_sync(): void
{
    config()->set('queue.default', 'sync');

    Storage::fake('local');

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $rfq = Rfq::factory()->create(['tenant_id' => $tenant->id, 'status' => 'published']);

    $response = $this->actingAs($user)->postJson(
        '/api/v1/quote-submissions',
        [
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Sync Vendor',
            'file' => UploadedFile::fake()->create('quote.pdf', 32, 'application/pdf'),
        ],
        ['X-Tenant-Id' => (string) $tenant->id],
    );

    $response->assertCreated();
    $this->assertContains($response->json('data.status'), ['ready', 'needs_review', 'failed']);
    $this->assertNotSame('uploaded', $response->json('data.status'));
}
```

Keep the assertion honest to the current ingestion pipeline. The important lock is "not left in `uploaded` when staging uses sync," not a fabricated success status.

- [ ] **Step 3: Run the focused quote submission suite**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter QuoteSubmissionWorkflowTest
```

Expected: PASS if the current controller already supports the chosen staging posture; FAIL only if the runtime assumption is wrong or undocumented behavior diverged.

- [ ] **Step 4: Make the code change only if the sync staging decision is not already expressible through queue configuration**

If the test fails because the current code cannot reliably honor `QUEUE_CONNECTION=sync` outside `local` and `testing`, make the smallest implementation change in `QuoteSubmissionController.php`:

```php
private function dispatchProcessingJob(QuoteSubmission $submission): void
{
    $shouldRunSync = in_array(config('queue.default'), ['sync', 'null'], true);

    if ($shouldRunSync) {
        $job = new ProcessQuoteSubmissionJob($submission->id);
        $job->runSync(app(QuoteIngestionOrchestrator::class));

        return;
    }

    ProcessQuoteSubmissionJob::dispatch($submission->id);
}
```

This keeps staging behavior configuration-driven instead of environment-name-driven.

- [ ] **Step 5: Re-run the focused quote submission suite**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter QuoteSubmissionWorkflowTest
```

Expected: PASS, proving the staging decision can be enforced by queue configuration and documented honestly.

- [ ] **Step 6: Commit after the task is implemented**

```bash
git add \
  apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php \
  apps/atomy-q/API/tests/Feature/QuoteSubmissionWorkflowTest.php
git commit -m "fix(atomy-q): lock sync quote ingestion staging mode"
```

## Task 2: Add The Storage Verification Command And Test Coverage

**Files:**
- Create: `apps/atomy-q/API/app/Console/Commands/VerifyStorageDiskCommand.php`
- Create: `apps/atomy-q/API/tests/Feature/Console/VerifyStorageDiskCommandTest.php`

- [ ] **Step 1: Write the failing command test**

Create `apps/atomy-q/API/tests/Feature/Console/VerifyStorageDiskCommandTest.php` with a fake-disk assertion:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class VerifyStorageDiskCommandTest extends TestCase
{
    public function test_verify_storage_disk_command_writes_and_reads_the_configured_disk(): void
    {
        config()->set('filesystems.default', 'local');
        Storage::fake('local');

        $exitCode = Artisan::call('atomy:verify-storage-disk', [
            '--disk' => 'local',
            '--path-prefix' => 'alpha-storage-smoke',
        ]);

        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Storage verification passed', $output);
        self::assertStringContainsString('disk=local', $output);
    }
}
```

- [ ] **Step 2: Run the focused console test and confirm it fails**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter VerifyStorageDiskCommandTest
```

Expected: FAIL with `Command "atomy:verify-storage-disk" is not defined`.

- [ ] **Step 3: Implement the new Artisan command**

Create `apps/atomy-q/API/app/Console/Commands/VerifyStorageDiskCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class VerifyStorageDiskCommand extends Command
{
    protected $signature = 'atomy:verify-storage-disk
                            {--disk= : Filesystem disk to verify (defaults to filesystems.default)}
                            {--path-prefix=alpha-storage-smoke : Prefix for the temporary verification path}';

    protected $description = 'Write and read a small verification object on the configured storage disk.';

    public function handle(): int
    {
        $disk = (string) ($this->option('disk') ?: config('filesystems.default'));
        $prefix = trim((string) $this->option('path-prefix'), '/');
        $path = $prefix . '/storage-check-' . now()->format('YmdHis') . '.txt';
        $contents = 'atomy-alpha-storage-check';

        try {
            $storage = Storage::disk($disk);
            $storage->put($path, $contents);
            $readBack = $storage->get($path);

            if ($readBack !== $contents) {
                $this->error(sprintf('Storage verification failed: disk=%s path=%s reason=content-mismatch', $disk, $path));
                return self::FAILURE;
            }

            $this->info(sprintf('Storage verification passed: disk=%s path=%s', $disk, $path));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error(sprintf('Storage verification failed: disk=%s path=%s reason=%s', $disk, $path, $e->getMessage()));
            return self::FAILURE;
        }
    }
}
```

This command must be non-destructive. Do not add object deletion in the first pass; the runbook can note the verification prefix for cleanup if needed.

- [ ] **Step 4: Re-run the focused console test**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter VerifyStorageDiskCommandTest
```

Expected: PASS.

- [ ] **Step 5: Manually verify the command output format**

Run:

```bash
cd apps/atomy-q/API && php artisan atomy:verify-storage-disk --disk=local --path-prefix=alpha-storage-smoke
```

Expected: one success line containing `Storage verification passed`, `disk=local`, and the written path.

- [ ] **Step 6: Commit after the task is implemented**

```bash
git add \
  apps/atomy-q/API/app/Console/Commands/VerifyStorageDiskCommand.php \
  apps/atomy-q/API/tests/Feature/Console/VerifyStorageDiskCommandTest.php
git commit -m "feat(atomy-q): add staging storage verification command"
```

## Task 3: Harden The API Environment Contract And README

**Files:**
- Modify: `apps/atomy-q/API/.env.example`
- Modify: `apps/atomy-q/API/README.md`

- [ ] **Step 1: Snapshot the current API env contract before editing**

Run:

```bash
cd /home/azaharizaman/dev/atomy && sed -n '1,220p' apps/atomy-q/API/.env.example && printf '\n--- README ---\n' && sed -n '1,220p' apps/atomy-q/API/README.md
```

Expected: current env keys and README sections are visible so the regrouping can stay additive, not destructive.

- [ ] **Step 2: Rewrite `.env.example` into staging-oriented sections**

Update `apps/atomy-q/API/.env.example` so the sections are grouped like:

```env
APP_ENV=staging
APP_URL=https://api.alpha.example.com
APP_KEY=

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=atomy_alpha
DB_USERNAME=atomy
DB_PASSWORD=secret

# Queue / cache
QUEUE_CONNECTION=sync
REDIS_URL=redis://127.0.0.1:6379/0

# JWT
JWT_SECRET=
JWT_TTL=60
JWT_REFRESH_TTL=120
JWT_ALGO=HS256
JWT_ISSUER=atomy-q

# Storage (use the real staging disk; MinIO shown here as the reference shape)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=atomy-alpha
AWS_URL=https://minio.alpha.example.com
AWS_ENDPOINT=https://minio.alpha.example.com
AWS_USE_PATH_STYLE_ENDPOINTS=true

# Quote intelligence
QUOTE_INTELLIGENCE_MODE=deterministic
QUOTE_INTELLIGENCE_LLM_PROVIDER=
QUOTE_INTELLIGENCE_LLM_MODEL=
QUOTE_INTELLIGENCE_LLM_BASE_URL=
QUOTE_INTELLIGENCE_LLM_API_KEY=
QUOTE_INTELLIGENCE_LLM_TIMEOUT_SECONDS=30

# Feature flags
FEATURE_PROJECTS_ENABLED=true
FEATURE_TASKS_ENABLED=true
```

Keep existing keys that the app already uses. Do not silently delete settings just because Task 8 is narrowing the focus.

- [ ] **Step 3: Update the API README with the staging contract and runbook handoff**

Add or rewrite sections in `apps/atomy-q/API/README.md` to state:

- alpha staging uses deterministic quote intelligence
- alpha staging uses `QUEUE_CONNECTION=sync` unless the team explicitly chooses async
- storage verification command is `php artisan atomy:verify-storage-disk`
- the runbook is the source of truth for staging bring-up

Include a concise staging block like:

```md
## Staging alpha contract

- Use `QUEUE_CONNECTION=sync` for the design-partner alpha path so quote ingestion completes inline during upload.
- Keep `QUOTE_INTELLIGENCE_MODE=deterministic` unless a live provider adapter is explicitly enabled and verified.
- Use the real configured storage disk for quote uploads and verify it with:

```bash
php artisan atomy:verify-storage-disk --disk=s3 --path-prefix=alpha-storage-smoke
```

- Follow [`../docs/STAGING_ALPHA_RUNBOOK.md`](../docs/STAGING_ALPHA_RUNBOOK.md) for bring-up, smoke steps, and evidence capture.
```

- [ ] **Step 4: Review the API docs diff for missing contract categories**

Run:

```bash
cd /home/azaharizaman/dev/atomy && git diff -- apps/atomy-q/API/.env.example apps/atomy-q/API/README.md
```

Expected: the diff clearly shows required env, sync staging decision, storage verification command, and runbook linkage.

- [ ] **Step 5: Commit after the task is implemented**

```bash
git add apps/atomy-q/API/.env.example apps/atomy-q/API/README.md
git commit -m "docs(atomy-q): document api staging alpha contract"
```

## Task 4: Harden The WEB Environment Contract And README

**Files:**
- Modify: `apps/atomy-q/WEB/.env.example`
- Modify: `apps/atomy-q/WEB/README.md`

- [ ] **Step 1: Write the WEB env contract directly into `.env.example`**

Update `apps/atomy-q/WEB/.env.example` so the file is copyable for staging:

```env
# Public URL of the deployed WEB app
NEXT_PUBLIC_APP_URL=https://alpha.example.com

# API base URL consumed by the browser client. Include /api/v1 and do not use a trailing slash.
NEXT_PUBLIC_API_URL=https://api.alpha.example.com/api/v1

# Design-partner staging must always use the real API path.
NEXT_PUBLIC_USE_MOCKS=false

# Optional local-only mock tenant setting; do not set this in staging.
# NEXT_PUBLIC_TENANT_ID=

# Optional Playwright smoke override when validating a deployed staging host.
# PLAYWRIGHT_BASE_URL=https://alpha.example.com
```

- [ ] **Step 2: Update the WEB README to separate local development from staging validation**

Add or rewrite a section in `apps/atomy-q/WEB/README.md`:

```md
## Staging alpha contract

- Set `NEXT_PUBLIC_API_URL` to the deployed API base URL including `/api/v1`.
- Set `NEXT_PUBLIC_USE_MOCKS=false`.
- Use the deployed WEB hostname for manual or Playwright smoke validation.
- Follow [`../docs/STAGING_ALPHA_RUNBOOK.md`](../docs/STAGING_ALPHA_RUNBOOK.md) for the full staging bring-up and smoke sequence.
```

Also make sure the README no longer leaves ambiguity about mock mode:

- mock mode is local-only
- staging validation must be mocks-off

- [ ] **Step 3: Review the WEB docs diff**

Run:

```bash
cd /home/azaharizaman/dev/atomy && git diff -- apps/atomy-q/WEB/.env.example apps/atomy-q/WEB/README.md
```

Expected: the diff clearly shows staging URL examples, mocks-off posture, and runbook linkage.

- [ ] **Step 4: Commit after the task is implemented**

```bash
git add apps/atomy-q/WEB/.env.example apps/atomy-q/WEB/README.md
git commit -m "docs(atomy-q): document web staging alpha contract"
```

## Task 5: Write The Staging Alpha Runbook

**Files:**
- Create: `apps/atomy-q/docs/STAGING_ALPHA_RUNBOOK.md`

- [ ] **Step 1: Create the runbook skeleton with the operator-critical sections**

Create `apps/atomy-q/docs/STAGING_ALPHA_RUNBOOK.md` with this top-level structure:

```md
# Atomy-Q Staging Alpha Runbook

## 1. Purpose
## 2. Supported staging posture
## 3. Required environment variables
## 4. Bring-up sequence
## 5. Storage verification
## 6. Queue posture
## 7. Seed policy
## 8. Mocks-off golden-path smoke
## 9. Evidence capture
## 10. Failure triage
```

- [ ] **Step 2: Fill in the bring-up sequence with exact commands**

The runbook must contain concrete commands, not prose placeholders. Include at least:

```bash
cd apps/atomy-q/API && php artisan migrate --force
cd apps/atomy-q/API && php artisan atomy:verify-storage-disk --disk=s3 --path-prefix=alpha-storage-smoke
cd apps/atomy-q/API && php artisan atomy:seed-rfq-flow --count=1 --status=published --base-url="$APP_URL"
```

If the chosen seed policy is not to use `atomy:seed-rfq-flow` for the design-partner smoke, replace that command with the actual approved seed path and explain why.

- [ ] **Step 3: Document the queue posture honestly**

Write the queue section explicitly:

```md
## 6. Queue posture

The design-partner alpha staging path uses `QUEUE_CONNECTION=sync`. Quote upload processes inline through the existing `QuoteSubmissionController` dispatch boundary, so a separate worker is not required for the main smoke journey.

If the team switches staging to an async queue connection later, Task 8 is not complete until this runbook is updated with:
- the worker startup command
- a job-success verification step
- failed-job inspection commands
```

- [ ] **Step 4: Write the mocks-off golden-path smoke as a numbered operator checklist**

Include explicit steps and expected outcomes:

```md
1. Register a tenant from the staging WEB.
   Expected: redirected into the authenticated buyer workspace for the new tenant.
2. Log in as the staging tenant admin.
   Expected: dashboard loads without mock banners and requests target the staging API URL.
3. Create an RFQ and publish it.
   Expected: RFQ appears in the RFQ list with a persisted RFQ number.
4. Invite at least one vendor.
   Expected: invitation row is visible in the RFQ vendor/invitation surface.
5. Upload a quote file.
   Expected: upload succeeds and the quote leaves `uploaded` state because staging runs sync ingestion.
6. Open normalization readiness / review.
   Expected: readiness data is present and blockers, if any, are explicit.
7. Freeze a final comparison run.
   Expected: a persisted final run exists and anchors downstream actions.
8. Create and sign off an award.
   Expected: award data persists and remains visible after reload.
9. Invite a user from `Settings > Users & Roles`.
   Expected: a real pending/inactive tenant-scoped user record is returned by the live API.
```

- [ ] **Step 5: Add a short failure-triage section**

Document the first place an operator should look when the smoke breaks:

```md
- CORS/auth failures: verify `NEXT_PUBLIC_API_URL`, API `APP_URL`, and `config/cors.php` allowed origins.
- Quote upload/storage failures: rerun `php artisan atomy:verify-storage-disk`.
- Quote remains `uploaded`: verify `QUEUE_CONNECTION=sync` in the deployed API env and confirm the upload response payload.
- Users invite failure: verify the API uses the live `/users/invite` contract and inspect the JSON error message surfaced by the WEB.
```

- [ ] **Step 6: Review the full runbook for placeholders and contradictions**

Run:

```bash
cd /home/azaharizaman/dev/atomy && rg -n "TODO|TBD|placeholder|fill in|later" apps/atomy-q/docs/STAGING_ALPHA_RUNBOOK.md
```

Expected: no matches.

- [ ] **Step 7: Commit after the task is implemented**

```bash
git add apps/atomy-q/docs/STAGING_ALPHA_RUNBOOK.md
git commit -m "docs(atomy-q): add staging alpha runbook"
```

## Task 6: Extend The Release Checklist For Task 8 Evidence

**Files:**
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
- Modify if needed: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify if needed: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Add a dedicated Task 8 evidence section to the release checklist**

Add a section like:

```md
## Latest Task 8 Staging Operations Readiness Evidence - 2026-04-17

- Operator: <name>
- WEB URL: <https://alpha.example.com>
- API URL: <https://api.alpha.example.com/api/v1>
- Runtime posture: `NEXT_PUBLIC_USE_MOCKS=false`, `QUEUE_CONNECTION=sync`, `QUOTE_INTELLIGENCE_MODE=deterministic`
- Storage verification: <PASS/FAIL and command used>
- Queue verification: Not required for the main alpha smoke because staging uses `QUEUE_CONNECTION=sync`
- Golden-path smoke: <PASS/FAIL with short summary>
```

Do not leave angle-bracket placeholders in the final committed version. If real staging evidence does not yet exist, say that explicitly in prose and keep the checklist honest.

- [ ] **Step 2: Add the release-gate fallback language if staging smoke is still pending**

Ensure the checklist says explicitly:

```md
If a true staging mocks-off smoke has not been completed, design-partner readiness is not yet earned and the release remains internal alpha only.
```

- [ ] **Step 3: Update implementation summaries only if code or operator behavior changed materially**

If Task 8 added the storage verification command or changed the quote-ingestion runtime contract, add concise entries to:

```md
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
```

Keep these entries factual. Do not duplicate the full runbook.

- [ ] **Step 4: Review the release-checklist diff**

Run:

```bash
cd /home/azaharizaman/dev/atomy && git diff -- apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md
```

Expected: the diff shows Task 8 evidence fields, explicit internal-alpha fallback language, and concise implementation-summary notes only where necessary.

- [ ] **Step 5: Commit after the task is implemented**

```bash
git add \
  apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md \
  apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md
git commit -m "docs(atomy-q): record task 8 staging readiness evidence"
```

## Task 7: Final Verification Pass

**Files:**
- Verify only: `apps/atomy-q/API/.env.example`
- Verify only: `apps/atomy-q/WEB/.env.example`
- Verify only: `apps/atomy-q/API/README.md`
- Verify only: `apps/atomy-q/WEB/README.md`
- Verify only: `apps/atomy-q/docs/STAGING_ALPHA_RUNBOOK.md`
- Verify only: `apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md`
- Verify only: `apps/atomy-q/API/app/Console/Commands/VerifyStorageDiskCommand.php`
- Verify only: `apps/atomy-q/API/tests/Feature/Console/VerifyStorageDiskCommandTest.php`

- [ ] **Step 1: Run the focused API verification set**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter "VerifyStorageDiskCommandTest|QuoteSubmissionWorkflowTest"
```

Expected: PASS.

- [ ] **Step 2: Run the storage command once with an explicit local disk**

Run:

```bash
cd apps/atomy-q/API && php artisan atomy:verify-storage-disk --disk=local --path-prefix=alpha-storage-smoke
```

Expected: PASS with a success line containing the disk and path.

- [ ] **Step 3: Do a docs sanity scan for the staging posture**

Run:

```bash
cd /home/azaharizaman/dev/atomy && rg -n "NEXT_PUBLIC_USE_MOCKS=false|QUEUE_CONNECTION=sync|QUOTE_INTELLIGENCE_MODE=deterministic|STAGING_ALPHA_RUNBOOK" apps/atomy-q/API/README.md apps/atomy-q/WEB/README.md apps/atomy-q/docs/STAGING_ALPHA_RUNBOOK.md apps/atomy-q/docs/ALPHA_RELEASE_CHECKLIST.md apps/atomy-q/API/.env.example apps/atomy-q/WEB/.env.example
```

Expected: all four core Task 8 contract points are present and aligned across docs.

- [ ] **Step 4: Review the final diff for scope creep**

Run:

```bash
cd /home/azaharizaman/dev/atomy && git diff --stat
```

Expected: changes are limited to Task 8 files and any minimal runtime/command files needed for the documented posture.

- [ ] **Step 5: Commit after the task is fully verified**

```bash
git add -A
git commit -m "chore(atomy-q): complete task 8 staging readiness"
```

## Self-Review Checklist

- Spec coverage:
  - runtime decision: covered in Task 1, Task 3, Task 5, Task 7
  - env contract: covered in Task 3 and Task 4
  - runbook: covered in Task 5
  - thin verification layer: covered in Task 2 and Task 7
  - checklist evidence: covered in Task 6
  - internal-alpha fallback if no staging smoke: covered in Task 6
- Placeholder scan:
  - no `TODO`, `TBD`, or "similar to Task N" shortcuts remain
- Type/signature consistency:
  - command name is consistently `atomy:verify-storage-disk`
  - staging runtime decision is consistently `QUEUE_CONNECTION=sync`
  - mock posture is consistently `NEXT_PUBLIC_USE_MOCKS=false`
