# Atomy-Q RFQ Evidence Vault Alpha Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an RFQ-scoped Evidence Vault that produces award justification evidence packs from quote, normalization, comparison, approval, award, signoff, and decision-trail records while removing the redundant generic document module.

**Architecture:** Replace generic document routes with RFQ-scoped Evidence Vault endpoints. Add focused Laravel services for summary assembly, supporting evidence upload, and immutable award-pack finalization; add a WEB hook and RFQ workspace page that render readiness, blockers, timeline, evidence sections, supporting upload, finalization, and export state.

**Tech Stack:** Laravel 12 API, Eloquent models/migrations, Laravel filesystem storage, PHPUnit feature tests, Next.js 16 App Router, TanStack Query, Vitest, generated OpenAPI client where needed.

---

## Source Design

- Design spec: `docs/superpowers/specs/2026-05-03-atomy-q-rfq-evidence-vault-alpha-design.md`
- Release-plan link: `apps/atomy-q/docs/02-release-management/current-release/release-plan.md`

## File Structure

API files:

- Modify `apps/atomy-q/API/routes/api.php`: remove generic `/documents` routes and add RFQ-scoped evidence-vault routes.
- Delete `apps/atomy-q/API/app/Http/Controllers/Api/V1/DocumentController.php`: generic stub controller should not remain exposed.
- Create `apps/atomy-q/API/app/Http/Controllers/Api/V1/EvidenceVaultController.php`: RFQ-scoped HTTP controller.
- Modify `apps/atomy-q/API/database/migrations/2026_03_11_000019_create_evidence_bundles_table.php`: make canonical pre-release schema match RFQ evidence bundles.
- Create `apps/atomy-q/API/database/migrations/2026_05_03_000001_create_evidence_bundle_items_table.php`.
- Create `apps/atomy-q/API/database/migrations/2026_05_03_000002_create_supporting_evidence_table.php`.
- Modify `apps/atomy-q/API/app/Models/EvidenceBundle.php`: add RFQ/comparison/approval/award fields, casts, relationships.
- Create `apps/atomy-q/API/app/Models/EvidenceBundleItem.php`.
- Create `apps/atomy-q/API/app/Models/SupportingEvidence.php`.
- Create `apps/atomy-q/API/app/Services/EvidenceVault/EvidenceVaultSummaryService.php`.
- Create `apps/atomy-q/API/app/Services/EvidenceVault/AwardEvidencePackFinalizer.php`.
- Create `apps/atomy-q/API/app/Services/EvidenceVault/SupportingEvidenceStorageService.php`.
- Create `apps/atomy-q/API/app/Http/Requests/StoreSupportingEvidenceRequest.php`.
- Create `apps/atomy-q/API/app/Http/Requests/FinalizeEvidencePackRequest.php`.
- Create `apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php`.
- Modify `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`: record RFQ Evidence Vault behavior after implementation.

WEB files:

- Delete `apps/atomy-q/WEB/src/app/(dashboard)/documents/page.tsx`.
- Modify `apps/atomy-q/WEB/src/config/nav.ts`: remove top-level Documents nav and records grouping.
- Modify `apps/atomy-q/WEB/src/lib/alpha-mode.ts`: make RFQ documents/evidence vault visible in alpha and top-level documents unreachable/removed.
- Modify `apps/atomy-q/WEB/src/lib/header-breadcrumbs.ts`: label RFQ documents section as Evidence Vault.
- Modify `apps/atomy-q/WEB/src/components/layout/main-sidebar-nav.tsx`: remove document icon case if no longer needed.
- Modify `apps/atomy-q/WEB/src/components/workspace/active-record-menu.tsx`: label RFQ child section `documents` as Evidence Vault.
- Create `apps/atomy-q/WEB/src/hooks/use-evidence-vault.ts`.
- Replace `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/documents/page.tsx`.
- Create `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/documents/page.test.tsx`.

OpenAPI/generated files:

- Update `apps/atomy-q/openapi/openapi.json` through Scramble export after API routes/resources are implemented.
- Regenerate `apps/atomy-q/WEB/src/generated/api` if the WEB implementation uses generated types.

## Task 1: API Schema And Model Foundation

**Files:**
- Modify: `apps/atomy-q/API/database/migrations/2026_03_11_000019_create_evidence_bundles_table.php`
- Create: `apps/atomy-q/API/database/migrations/2026_05_03_000001_create_evidence_bundle_items_table.php`
- Create: `apps/atomy-q/API/database/migrations/2026_05_03_000002_create_supporting_evidence_table.php`
- Modify: `apps/atomy-q/API/app/Models/EvidenceBundle.php`
- Create: `apps/atomy-q/API/app/Models/EvidenceBundleItem.php`
- Create: `apps/atomy-q/API/app/Models/SupportingEvidence.php`
- Test: `apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php`

- [ ] **Step 1: Write the first failing schema/model test**

Add `EvidenceVaultApiTest` with a model persistence test before implementation:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EvidenceBundle;
use App\Models\EvidenceBundleItem;
use App\Models\Rfq;
use App\Models\SupportingEvidence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class EvidenceVaultApiTest extends ApiTestCase
{
    use RefreshDatabase;

    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        return $app;
    }

    public function testEvidenceBundlePersistsRfqScopedManifestItemsAndSupportingEvidence(): void
    {
        [$user, $rfq] = $this->seedUserAndRfq();

        $bundle = EvidenceBundle::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'type' => 'award_justification',
            'status' => 'draft',
            'version' => 1,
            'manifest' => ['rfq_id' => $rfq->id],
            'checksum' => null,
            'created_by' => $user->id,
        ]);

        EvidenceBundleItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'evidence_bundle_id' => $bundle->id,
            'source_type' => 'quote_submission',
            'source_id' => (string) Str::ulid(),
            'artifact_kind' => 'quote_source',
            'label' => 'Supplier quote',
            'metadata' => ['status' => 'ready'],
            'included_at' => now(),
        ]);

        SupportingEvidence::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'reason' => 'Clarification email from buyer',
            'original_filename' => 'clarification.pdf',
            'file_type' => 'application/pdf',
            'storage_path' => 'supporting-evidence/clarification.pdf',
            'checksum' => hash('sha256', 'clarification'),
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
        ]);

        $this->assertDatabaseHas('evidence_bundles', [
            'id' => $bundle->id,
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'type' => 'award_justification',
        ]);
        $this->assertDatabaseHas('evidence_bundle_items', [
            'evidence_bundle_id' => $bundle->id,
            'artifact_kind' => 'quote_source',
        ]);
        $this->assertDatabaseHas('supporting_evidence', [
            'rfq_id' => $rfq->id,
            'reason' => 'Clarification email from buyer',
        ]);
    }

    /**
     * @return array{0: User, 1: Rfq}
     */
    private function seedUserAndRfq(): array
    {
        $tenantId = (string) Str::ulid();

        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'evidence-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Evidence User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        $rfq = Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-EV-' . Str::lower((string) Str::ulid()),
            'title' => 'Evidence RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'closed',
        ]);

        return [$user, $rfq];
    }
}
```

- [ ] **Step 2: Run the failing test**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter EvidenceVaultApiTest::testEvidenceBundlePersistsRfqScopedManifestItemsAndSupportingEvidence
```

Expected: FAIL because `evidence_bundle_items` and `supporting_evidence` do not exist and `evidence_bundles` lacks canonical columns.

- [ ] **Step 3: Patch migrations**

Replace `evidence_bundles` with the canonical pre-release shape:

```php
Schema::create('evidence_bundles', function (Blueprint $table): void {
    $table->ulid('id')->primary();
    $table->ulid('tenant_id')->index();
    $table->ulid('rfq_id');
    $table->ulid('comparison_run_id')->nullable();
    $table->ulid('approval_id')->nullable();
    $table->ulid('award_id')->nullable();
    $table->string('type')->default('award_justification');
    $table->string('status')->default('draft');
    $table->unsignedInteger('version')->default(1);
    $table->json('manifest')->nullable();
    $table->string('checksum', 64)->nullable();
    $table->timestamp('finalized_at')->nullable();
    $table->ulid('created_by')->nullable();
    $table->timestamps();

    $table->index(['tenant_id', 'rfq_id']);
    $table->index(['tenant_id', 'status']);
    $table->index(['tenant_id', 'rfq_id', 'status']);
});
```

Create `evidence_bundle_items`:

```php
Schema::create('evidence_bundle_items', function (Blueprint $table): void {
    $table->ulid('id')->primary();
    $table->ulid('tenant_id')->index();
    $table->ulid('evidence_bundle_id');
    $table->string('source_type');
    $table->ulid('source_id')->nullable();
    $table->string('artifact_kind');
    $table->string('label');
    $table->string('storage_path')->nullable();
    $table->string('checksum', 64)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('included_at');
    $table->timestamps();

    $table->index(['tenant_id', 'evidence_bundle_id']);
    $table->index(['tenant_id', 'source_type', 'source_id']);
});
```

Create `supporting_evidence`:

```php
Schema::create('supporting_evidence', function (Blueprint $table): void {
    $table->ulid('id')->primary();
    $table->ulid('tenant_id')->index();
    $table->ulid('rfq_id');
    $table->ulid('vendor_id')->nullable();
    $table->ulid('quote_submission_id')->nullable();
    $table->ulid('award_id')->nullable();
    $table->text('reason');
    $table->string('original_filename');
    $table->string('file_type')->nullable();
    $table->string('storage_path');
    $table->string('checksum', 64);
    $table->ulid('uploaded_by')->nullable();
    $table->timestamp('uploaded_at');
    $table->timestamps();

    $table->index(['tenant_id', 'rfq_id']);
    $table->index(['tenant_id', 'quote_submission_id']);
    $table->index(['tenant_id', 'award_id']);
});
```

- [ ] **Step 4: Patch models**

`EvidenceBundle` fillable/casts/relationships:

```php
protected $fillable = [
    'tenant_id',
    'rfq_id',
    'comparison_run_id',
    'approval_id',
    'award_id',
    'type',
    'status',
    'version',
    'manifest',
    'checksum',
    'finalized_at',
    'created_by',
];

protected $casts = [
    'manifest' => 'array',
    'version' => 'integer',
    'finalized_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];
```

Create `EvidenceBundleItem` and `SupportingEvidence` using `HasUlids`, non-incrementing string keys, explicit `$table`, `$fillable`, and casts for JSON/date columns.

- [ ] **Step 5: Run the test and migration gate**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter EvidenceVaultApiTest::testEvidenceBundlePersistsRfqScopedManifestItemsAndSupportingEvidence
cd apps/atomy-q/API && DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan migrate:fresh --seed
```

Expected: both PASS.

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/database/migrations apps/atomy-q/API/app/Models apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php
git commit -m "Add RFQ evidence vault persistence foundation"
```

## Task 2: RFQ-Scoped Routes And Remove Generic Documents API

**Files:**
- Modify: `apps/atomy-q/API/routes/api.php`
- Delete: `apps/atomy-q/API/app/Http/Controllers/Api/V1/DocumentController.php`
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/EvidenceVaultController.php`
- Test: `apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php`

- [ ] **Step 1: Add failing route tests**

Append tests:

```php
public function testGenericDocumentsRoutesAreRemoved(): void
{
    [$user] = $this->seedUserAndRfq();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/documents')
        ->assertNotFound();
}

public function testEvidenceVaultSummaryEndpointIsRfqScoped(): void
{
    [$user, $rfq] = $this->seedUserAndRfq();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/rfqs/' . $rfq->id . '/evidence-vault')
        ->assertOk()
        ->assertJsonPath('data.rfq.id', $rfq->id)
        ->assertJsonPath('data.award_pack.status', 'not_ready');
}
```

- [ ] **Step 2: Run failing route tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter "EvidenceVaultApiTest::testGenericDocumentsRoutesAreRemoved|EvidenceVaultApiTest::testEvidenceVaultSummaryEndpointIsRfqScoped"
```

Expected: FAIL because `/documents` still exists and RFQ evidence-vault route is missing.

- [ ] **Step 3: Patch routes**

Remove `use App\Http\Controllers\Api\V1\DocumentController;` and add:

```php
use App\Http\Controllers\Api\V1\EvidenceVaultController;
```

Remove the `Route::prefix('documents')` and `Route::prefix('evidence-bundles')` groups. Add inside authenticated v1 routes:

```php
Route::prefix('rfqs/{rfqId}/evidence-vault')->group(function (): void {
    Route::get('/', [EvidenceVaultController::class, 'show']);
    Route::post('supporting-evidence', [EvidenceVaultController::class, 'storeSupportingEvidence']);
    Route::post('award-pack/finalize', [EvidenceVaultController::class, 'finalizeAwardPack']);
    Route::get('award-pack/export', [EvidenceVaultController::class, 'exportAwardPack']);
});
```

- [ ] **Step 4: Add controller shell**

Create controller with tenant-safe RFQ lookup:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Rfq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EvidenceVaultController extends Controller
{
    use ExtractsAuthContext;

    public function show(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($rfqId)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'rfq' => [
                    'id' => (string) $rfq->id,
                    'title' => $rfq->title,
                    'rfq_number' => $rfq->rfq_number,
                ],
                'award_pack' => [
                    'status' => 'not_ready',
                    'bundle_id' => null,
                    'version' => null,
                    'finalized_at' => null,
                    'checksum' => null,
                ],
                'readiness' => [
                    'ready' => false,
                    'blockers' => [],
                ],
                'timeline' => [],
                'sections' => [],
                'actions' => [
                    'can_finalize' => false,
                    'can_export' => false,
                    'can_upload_supporting_evidence' => true,
                ],
            ],
        ]);
    }
}
```

- [ ] **Step 5: Run route tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter "EvidenceVaultApiTest::testGenericDocumentsRoutesAreRemoved|EvidenceVaultApiTest::testEvidenceVaultSummaryEndpointIsRfqScoped"
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/routes/api.php apps/atomy-q/API/app/Http/Controllers/Api/V1/EvidenceVaultController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/DocumentController.php apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php
git commit -m "Replace generic document API with RFQ evidence vault routes"
```

## Task 3: Evidence Vault Summary Service And Blockers

**Files:**
- Create: `apps/atomy-q/API/app/Services/EvidenceVault/EvidenceVaultSummaryService.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/EvidenceVaultController.php`
- Test: `apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php`

- [ ] **Step 1: Add failing summary tests**

Add tests for a complete evidence set and a blocked evidence set. Reuse existing model factories manually as in `AwardWorkflowTest`.

Expected complete response assertions:

```php
$this->actingAs($user, 'sanctum')
    ->getJson('/api/v1/rfqs/' . $rfq->id . '/evidence-vault')
    ->assertOk()
    ->assertJsonPath('data.award_pack.status', 'draft_ready')
    ->assertJsonPath('data.readiness.ready', true)
    ->assertJsonPath('data.actions.can_finalize', true)
    ->assertJsonFragment(['code' => 'quote_sources'])
    ->assertJsonFragment(['code' => 'final_comparison'])
    ->assertJsonFragment(['code' => 'approval_trail'])
    ->assertJsonFragment(['code' => 'award_signoff']);
```

Expected blocked response assertions:

```php
$this->actingAs($user, 'sanctum')
    ->getJson('/api/v1/rfqs/' . $rfq->id . '/evidence-vault')
    ->assertOk()
    ->assertJsonPath('data.award_pack.status', 'not_ready')
    ->assertJsonPath('data.readiness.ready', false)
    ->assertJsonFragment(['code' => 'FINAL_COMPARISON_MISSING'])
    ->assertJsonFragment(['code' => 'APPROVAL_DECISION_MISSING'])
    ->assertJsonFragment(['code' => 'AWARD_SIGNOFF_MISSING']);
```

- [ ] **Step 2: Run failing summary tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter "EvidenceVaultApiTest::testEvidenceVaultSummaryReportsCompleteAwardEvidence|EvidenceVaultApiTest::testEvidenceVaultSummaryReportsReadinessBlockers"
```

Expected: FAIL because the controller returns static sections/blockers.

- [ ] **Step 3: Implement summary service**

Create service with public method:

```php
/**
 * @return array<string, mixed>
 */
public function summarize(string $tenantId, Rfq $rfq): array
```

Implementation rules:

- Fetch ready/final quote submissions by `tenant_id` and `rfq_id`.
- Count unresolved normalization conflicts through source lines under the RFQ's quote submissions.
- Fetch latest final comparison with `is_preview=false`, `status` in `['finalized', 'frozen']`, and same tenant/RFQ.
- Fetch latest approval with same tenant/RFQ and `status` in `['approved', 'rejected']`; approval must be approved for pack readiness.
- Fetch latest award with same tenant/RFQ and non-null `signoff_at`.
- Fetch existing latest evidence bundle by tenant/RFQ/type.
- Fetch decision-trail entries by tenant/RFQ.
- Return deterministic blocker codes:
  - `FINAL_COMPARISON_MISSING`
  - `QUOTE_SOURCE_MISSING`
  - `NORMALIZATION_CONFLICT_UNRESOLVED`
  - `APPROVAL_DECISION_MISSING`
  - `AWARD_MISSING`
  - `AWARD_SIGNOFF_MISSING`
  - `DECISION_TRAIL_INCOMPLETE`

Use payload shape:

```php
return [
    'rfq' => ['id' => (string) $rfq->id, 'title' => $rfq->title, 'rfq_number' => $rfq->rfq_number],
    'award_pack' => ['status' => $status, 'bundle_id' => $bundleId, 'version' => $version, 'finalized_at' => $finalizedAt, 'checksum' => $checksum],
    'readiness' => ['ready' => $ready, 'blockers' => $blockers],
    'timeline' => $timeline,
    'sections' => $sections,
    'actions' => ['can_finalize' => $ready && $status !== 'finalized', 'can_export' => $status === 'finalized', 'can_upload_supporting_evidence' => true],
];
```

- [ ] **Step 4: Inject service into controller**

Constructor:

```php
public function __construct(
    private readonly EvidenceVaultSummaryService $summaryService,
) {
}
```

Controller `show`:

```php
return response()->json([
    'data' => $this->summaryService->summarize($tenantId, $rfq),
]);
```

- [ ] **Step 5: Run summary tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter EvidenceVaultApiTest
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/API/app/Services/EvidenceVault/EvidenceVaultSummaryService.php apps/atomy-q/API/app/Http/Controllers/Api/V1/EvidenceVaultController.php apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php
git commit -m "Add RFQ evidence vault readiness summary"
```

## Task 4: Supporting Evidence Upload

**Files:**
- Create: `apps/atomy-q/API/app/Http/Requests/StoreSupportingEvidenceRequest.php`
- Create: `apps/atomy-q/API/app/Services/EvidenceVault/SupportingEvidenceStorageService.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/EvidenceVaultController.php`
- Test: `apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php`

- [ ] **Step 1: Add failing upload tests**

Test success with `Storage::fake('local')`:

```php
Storage::fake('local');

$response = $this->actingAs($user, 'sanctum')
    ->postJson('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
        'reason' => 'Buyer clarification',
        'file' => UploadedFile::fake()->create('clarification.pdf', 12, 'application/pdf'),
    ]);

$response->assertCreated()
    ->assertJsonPath('data.reason', 'Buyer clarification')
    ->assertJsonPath('data.original_filename', 'clarification.pdf');

$this->assertDatabaseHas('supporting_evidence', [
    'tenant_id' => $user->tenant_id,
    'rfq_id' => $rfq->id,
    'reason' => 'Buyer clarification',
]);
```

Test validation:

```php
$this->actingAs($user, 'sanctum')
    ->postJson('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/supporting-evidence', [
        'reason' => '',
    ])
    ->assertUnprocessable()
    ->assertJsonValidationErrors(['reason', 'file']);
```

- [ ] **Step 2: Run failing upload tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter "EvidenceVaultApiTest::testSupportingEvidenceUploadStoresFileAndMetadata|EvidenceVaultApiTest::testSupportingEvidenceUploadRequiresReasonAndFile"
```

Expected: FAIL because upload endpoint is not implemented.

- [ ] **Step 3: Add request validation**

`StoreSupportingEvidenceRequest` rules:

```php
return [
    'reason' => ['required', 'string', 'min:5', 'max:2000'],
    'file' => ['required', 'file', 'max:10240'],
    'vendor_id' => ['nullable', 'string'],
    'quote_submission_id' => ['nullable', 'string'],
    'award_id' => ['nullable', 'string'],
];
```

- [ ] **Step 4: Add storage service**

Service method:

```php
public function store(string $tenantId, Rfq $rfq, User $actor, UploadedFile $file, string $reason, array $relations = []): SupportingEvidence
```

Rules:

- Store under `supporting-evidence/{tenantId}/{rfqId}/{ulid}-{sanitizedOriginalName}` on `local` disk unless project config has a canonical upload disk.
- Compute SHA-256 checksum from stored file content.
- Persist `SupportingEvidence` only after storage succeeds.
- Return saved model.

- [ ] **Step 5: Wire controller**

Add method:

```php
public function storeSupportingEvidence(StoreSupportingEvidenceRequest $request, string $rfqId): JsonResponse
```

Use tenant-safe RFQ lookup, authenticated actor, and return `201`.

- [ ] **Step 6: Run upload tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter EvidenceVaultApiTest
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add apps/atomy-q/API/app/Http/Requests/StoreSupportingEvidenceRequest.php apps/atomy-q/API/app/Services/EvidenceVault/SupportingEvidenceStorageService.php apps/atomy-q/API/app/Http/Controllers/Api/V1/EvidenceVaultController.php apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php
git commit -m "Add RFQ supporting evidence upload"
```

## Task 5: Award Pack Finalization And Export

**Files:**
- Create: `apps/atomy-q/API/app/Http/Requests/FinalizeEvidencePackRequest.php`
- Create: `apps/atomy-q/API/app/Services/EvidenceVault/AwardEvidencePackFinalizer.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/EvidenceVaultController.php`
- Modify: `apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php`
- Test: `apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php`

- [ ] **Step 1: Add failing finalization tests**

Test not-ready response:

```php
$this->actingAs($user, 'sanctum')
    ->postJson('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/award-pack/finalize')
    ->assertUnprocessable()
    ->assertJsonFragment(['code' => 'FINAL_COMPARISON_MISSING']);
```

Test finalized immutable pack:

```php
$this->actingAs($user, 'sanctum')
    ->postJson('/api/v1/rfqs/' . $rfq->id . '/evidence-vault/award-pack/finalize')
    ->assertCreated()
    ->assertJsonPath('data.status', 'finalized')
    ->assertJsonPath('data.type', 'award_justification');

$this->assertDatabaseHas('evidence_bundles', [
    'tenant_id' => $user->tenant_id,
    'rfq_id' => $rfq->id,
    'status' => 'finalized',
    'type' => 'award_justification',
]);
$this->assertDatabaseHas('decision_trail_entries', [
    'tenant_id' => $user->tenant_id,
    'rfq_id' => $rfq->id,
    'event_type' => 'evidence_pack_finalized',
]);
```

- [ ] **Step 2: Run failing finalization tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter "EvidenceVaultApiTest::testAwardPackFinalizationRejectsNotReadyRfq|EvidenceVaultApiTest::testAwardPackFinalizationCreatesImmutableManifest"
```

Expected: FAIL because finalization is not implemented.

- [ ] **Step 3: Add finalizer**

Finalizer method:

```php
public function finalize(string $tenantId, Rfq $rfq, User $actor): EvidenceBundle
```

Rules:

- Call `EvidenceVaultSummaryService::summarize`.
- If `readiness.ready` is false, throw `ValidationException::withMessages(['evidence' => ['Evidence pack is not ready.']])` and include blocker details in controller JSON.
- In one transaction, create next bundle version, build `EvidenceBundleItem` rows for included sources, compute canonical JSON checksum, set `status=finalized`, `finalized_at=now()`, and record `evidence_pack_finalized`.
- If an older finalized bundle exists, mark it `superseded`.

Checksum generation:

```php
$manifestJson = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
$checksum = hash('sha256', $manifestJson);
```

- [ ] **Step 4: Add decision-trail recorder method**

Add:

```php
public function recordEvidencePackFinalized(string $tenantId, string $rfqId, string $bundleId, string $checksum, string $actorId): DecisionTrailEntry
```

Payload:

```php
[
    'bundle_id' => $bundleId,
    'checksum' => $checksum,
    'actor_id' => $actorId,
]
```

- [ ] **Step 5: Wire controller finalize/export**

`finalizeAwardPack` returns `201` with bundle summary. `exportAwardPack` returns latest finalized manifest JSON:

```php
return response()->json([
    'data' => [
        'bundle_id' => (string) $bundle->id,
        'checksum' => $bundle->checksum,
        'manifest' => $bundle->manifest,
    ],
]);
```

- [ ] **Step 6: Run finalization tests**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter EvidenceVaultApiTest
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add apps/atomy-q/API/app/Http/Requests/FinalizeEvidencePackRequest.php apps/atomy-q/API/app/Services/EvidenceVault/AwardEvidencePackFinalizer.php apps/atomy-q/API/app/Http/Controllers/Api/V1/EvidenceVaultController.php apps/atomy-q/API/app/Services/QuoteIntake/DecisionTrailRecorder.php apps/atomy-q/API/tests/Feature/EvidenceVaultApiTest.php
git commit -m "Finalize immutable RFQ award evidence packs"
```

## Task 6: WEB Navigation And Hook

**Files:**
- Modify: `apps/atomy-q/WEB/src/config/nav.ts`
- Modify: `apps/atomy-q/WEB/src/lib/alpha-mode.ts`
- Modify: `apps/atomy-q/WEB/src/lib/header-breadcrumbs.ts`
- Modify: `apps/atomy-q/WEB/src/components/workspace/active-record-menu.tsx`
- Delete: `apps/atomy-q/WEB/src/app/(dashboard)/documents/page.tsx`
- Create: `apps/atomy-q/WEB/src/hooks/use-evidence-vault.ts`
- Test: `apps/atomy-q/WEB/src/components/alpha/alpha-deferred-screen.test.tsx`

- [ ] **Step 1: Update alpha/nav tests first**

Change expectations:

```ts
expect(isTopLevelNavVisibleInAlpha('documents')).toBe(false);
expect(isRfqSectionVisibleInAlpha('documents')).toBe(true);
expect(isDeferredAlphaPath('/documents')).toBe(true);
expect(isDeferredAlphaPath('/rfqs/01JABC123/documents')).toBe(false);
```

- [ ] **Step 2: Run failing nav tests**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/components/alpha/alpha-deferred-screen.test.tsx
```

Expected: FAIL because RFQ documents are still deferred.

- [ ] **Step 3: Patch alpha/nav config**

In `alpha-mode.ts`, add `documents` to `ALPHA_RFQ_SECTION_IDS` and keep `/documents` deferred.

In `nav.ts`, remove `{ id: 'documents', label: 'Documents', href: '/documents', path: '/documents' }` from `MAIN_NAV_ITEMS` and remove `documents` from `RECORDS_MAIN_NAV_IDS`.

Update RFQ workspace label to `Evidence Vault` wherever RFQ section IDs are defined.

- [ ] **Step 4: Add WEB hook**

`use-evidence-vault.ts` exports:

```ts
export interface EvidenceVaultSummary {
  rfq: { id: string; title: string | null; rfq_number: string | null };
  award_pack: { status: 'not_ready' | 'draft_ready' | 'finalized' | 'superseded'; bundle_id: string | null; version: number | null; finalized_at: string | null; checksum: string | null };
  readiness: { ready: boolean; blockers: Array<{ code: string; message: string }> };
  timeline: Array<{ code: string; label: string; status: string; occurred_at: string | null }>;
  sections: Array<{ code: string; label: string; status: string; items: unknown[] }>;
  actions: { can_finalize: boolean; can_export: boolean; can_upload_supporting_evidence: boolean };
}
```

Use `fetchLiveOrFail` and strict normalizers like existing live hooks. Mutations:

- `uploadSupportingEvidence`
- `finalizeAwardPack`
- `exportAwardPack`

- [ ] **Step 5: Run nav tests**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/components/alpha/alpha-deferred-screen.test.tsx
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add apps/atomy-q/WEB/src/config/nav.ts apps/atomy-q/WEB/src/lib/alpha-mode.ts apps/atomy-q/WEB/src/lib/header-breadcrumbs.ts apps/atomy-q/WEB/src/components/workspace/active-record-menu.tsx apps/atomy-q/WEB/src/app/\\(dashboard\\)/documents/page.tsx apps/atomy-q/WEB/src/hooks/use-evidence-vault.ts apps/atomy-q/WEB/src/components/alpha/alpha-deferred-screen.test.tsx
git commit -m "Expose RFQ evidence vault navigation"
```

## Task 7: WEB RFQ Evidence Vault Page

**Files:**
- Replace: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/documents/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/documents/page.test.tsx`
- Modify: `apps/atomy-q/WEB/src/hooks/use-evidence-vault.ts`

- [ ] **Step 1: Write failing page tests**

Mock `useEvidenceVault` and assert:

```ts
expect(await screen.findByRole('heading', { name: 'Evidence Vault' })).toBeInTheDocument();
expect(screen.getByText('Award Justification Pack')).toBeInTheDocument();
expect(screen.getByText('Final comparison missing')).toBeInTheDocument();
expect(screen.getByRole('button', { name: /finalize award pack/i })).toBeDisabled();
```

Ready/finalized state:

```ts
expect(screen.getByText('Evidence pack finalized')).toBeInTheDocument();
expect(screen.getByRole('button', { name: /export evidence pack/i })).toBeEnabled();
```

Attach drawer validation:

```ts
fireEvent.click(screen.getByRole('button', { name: /attach supporting evidence/i }));
expect(screen.getByLabelText(/reason/i)).toBeInTheDocument();
expect(screen.getByLabelText(/file/i)).toBeInTheDocument();
```

- [ ] **Step 2: Run failing page tests**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run 'src/app/(dashboard)/rfqs/[rfqId]/documents/page.test.tsx'
```

Expected: FAIL because the page still renders the alpha-deferred screen.

- [ ] **Step 3: Replace page**

Page contract:

```tsx
export default function RfqDocumentsPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  return <EvidenceVaultPageContent rfqId={rfqId} />;
}
```

Render:

- `PageHeader title="Evidence Vault"`
- readiness banner from `summary.readiness`
- Award Justification Pack card with status
- blocker checklist
- sections list
- timeline list
- attach supporting evidence drawer/form
- finalize button disabled unless `actions.can_finalize`
- export button disabled unless `actions.can_export`

- [ ] **Step 4: Run page tests**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run 'src/app/(dashboard)/rfqs/[rfqId]/documents/page.test.tsx'
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/WEB/src/app/\\(dashboard\\)/rfqs/\\[rfqId\\]/documents/page.tsx apps/atomy-q/WEB/src/app/\\(dashboard\\)/rfqs/\\[rfqId\\]/documents/page.test.tsx apps/atomy-q/WEB/src/hooks/use-evidence-vault.ts
git commit -m "Build RFQ evidence vault page"
```

## Task 8: Contract, Docs, And Verification

**Files:**
- Modify: `apps/atomy-q/openapi/openapi.json`
- Modify: `apps/atomy-q/WEB/src/generated/api/*` if generated client changes
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-plan.md`
- Modify: `apps/atomy-q/docs/02-release-management/current-release/release-checklist.md`

- [ ] **Step 1: Export OpenAPI**

Run:

```bash
cd apps/atomy-q/API && DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan scramble:export --path=../openapi/openapi.json
```

Expected: PASS and OpenAPI removes `/documents` while adding `/rfqs/{rfqId}/evidence-vault`.

- [ ] **Step 2: Regenerate WEB API client**

Run:

```bash
cd apps/atomy-q/WEB && npm run generate:api
```

Expected: PASS.

- [ ] **Step 3: Update docs**

Add API implementation summary bullet:

```md
**RFQ Evidence Vault (2026-05-03):** The generic document module was removed from the product contract and replaced with RFQ-scoped Evidence Vault endpoints. The vault assembles award justification evidence from quote submissions, normalization state, final comparison, approvals, awards, signoff, decision-trail entries, and buyer-uploaded supporting evidence. Finalized award packs store immutable manifests with checksums and supersede older versions when regenerated.
```

Update release plan/checklist only after implementation gates pass:

```md
- RFQ Evidence Vault: supported when API/WEB evidence-vault tests, OpenAPI export, generated client, WEB build, and staging smoke evidence are green.
```

- [ ] **Step 4: Run focused API gates**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter EvidenceVaultApiTest
cd apps/atomy-q/API && php artisan test --filter "QuoteSubmissionWorkflowTest|NormalizationReviewWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest"
```

Expected: PASS.

- [ ] **Step 5: Run focused WEB gates**

Run:

```bash
cd apps/atomy-q/WEB && npx vitest run src/components/alpha/alpha-deferred-screen.test.tsx 'src/app/(dashboard)/rfqs/[rfqId]/documents/page.test.tsx'
cd apps/atomy-q/WEB && npm run build
```

Expected: PASS.

- [ ] **Step 6: Run diff hygiene**

Run:

```bash
git diff --check
```

Expected: no output.

- [ ] **Step 7: Commit**

```bash
git add apps/atomy-q/openapi/openapi.json apps/atomy-q/WEB/src/generated/api apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md apps/atomy-q/docs/02-release-management/current-release/release-plan.md apps/atomy-q/docs/02-release-management/current-release/release-checklist.md
git commit -m "Document and verify RFQ evidence vault alpha support"
```

## Final Verification Matrix

Run before claiming the feature complete:

```bash
cd apps/atomy-q/API && php artisan test --filter EvidenceVaultApiTest
cd apps/atomy-q/API && php artisan test --filter "QuoteSubmissionWorkflowTest|NormalizationReviewWorkflowTest|ComparisonSnapshotWorkflowTest|AwardWorkflowTest"
cd apps/atomy-q/API && DB_CONNECTION=sqlite DB_DATABASE=':memory:' php artisan scramble:export --path=../openapi/openapi.json
cd apps/atomy-q/WEB && npm run generate:api
cd apps/atomy-q/WEB && npx vitest run src/components/alpha/alpha-deferred-screen.test.tsx 'src/app/(dashboard)/rfqs/[rfqId]/documents/page.test.tsx'
cd apps/atomy-q/WEB && npm run build
git diff --check
```

Expected result: all commands pass, with only documented non-blocking warnings if any.

## Rollback

Because Atomy-Q is pre-release, rollback should be branch-level revert of the Evidence Vault implementation commits. Do not preserve generic `/documents` compatibility as a workaround. If the feature misses external alpha, restore RFQ documents to alpha-deferred and keep the approved design as a deferred proposal.
