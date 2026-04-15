# Quote Ingestion and AI Normalization Pipeline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship one real end-to-end quote ingestion pipeline: upload file, enqueue processing, extract vendor quote content using AI (stubbed), normalize it into persisted source lines, and expose status/errors back to users.

**Architecture:** 
- Async job with sync fallback
- Layer 2 orchestrator coordinating extraction → normalization → persistence
- Layer 1 contract for quote extraction (extend MachineLearning)
- Layer 3 mock provider returning realistic sample data

**Tech Stack:** 
- Laravel (jobs, queue)
- Nexus packages: MachineLearning (extend), new orchestrator (Layer 2)
- spatie/pdf-to-text for PDF text extraction
- PostgreSQL with JSONB columns

---

## File Structure

### New Files to Create

| File | Responsibility |
|------|----------------|
| `packages/MachineLearning/src/Contracts/QuoteExtractionServiceInterface.php` | Contract for document → line items |
| `packages/MachineLearning/src/Services/QuoteExtractionService.php` | Implementation with mock for Alpha |
| `packages/MachineLearning/src/Services/VertexAIMockProvider.php` | Stub provider for Alpha |
| `orchestrators/QuoteIngestion/src/QuoteIngestionOrchestrator.php` | Layer 2 orchestrator |
| `orchestrators/QuoteIngestion/src/QuoteIngestionServiceProvider.php` | Package registration |
| `orchestrators/QuoteIngestion/composer.json` | Package manifest |
| `apps/atomy-q/API/app/Jobs/ProcessQuoteSubmissionJob.php` | Laravel job with sync fallback |
| `apps/atomy-q/API/app/Services/QuoteIntake/QuoteSubmissionStatusManager.php` | Status transition logic |

### Files to Modify

| File | Changes |
|------|---------|
| `apps/atomy-q/API/database/migrations/..._add_processing_fields_to_quote_submissions.php` | New migration |
| `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php` | Real reparse implementation |
| `apps/atomy-q/API/app/Models/QuoteSubmission.php` | Add processing fields, fillable |
| `packages/MachineLearning/src/MachineLearningServiceProvider.php` | Register new contracts |
| `apps/atomy-q/API/routes/api.php` | Ensure reparse route exists |

---

## Implementation Tasks

### Phase 1: Database Schema

- [ ] **Task 1: Create migration for processing fields**

**Files:**
- Create: `apps/atomy-q/API/database/migrations/2026_03_24_000001_add_processing_fields_to_quote_submissions_table.php`

**Steps:**
- [ ] Write the failing test (verify QuoteSubmission has processing fields)

```bash
# Run existing test to establish baseline
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/QuoteSubmissionWorkflowTest.php --filter=test_quote_submission_upload_persists_uploaded_state_and_tenant_id -v
```

- [ ] Create migration

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_submissions', function (Blueprint $table): void {
            $table->string('error_code', 50)->nullable()->after('status');
            $table->text('error_message')->nullable()->after('error_code');
            $table->timestamp('processing_started_at')->nullable()->after('error_message');
            $table->timestamp('processing_completed_at')->nullable()->after('processing_started_at');
            $table->timestamp('parsed_at')->nullable()->after('processing_completed_at');
            $table->tinyInteger('retry_count')->default(0)->after('parsed_at');
        });
    }

    public function down(): void
    {
        Schema::table('quote_submissions', function (Blueprint $table): void {
            $table->dropColumn([
                'error_code',
                'error_message',
                'processing_started_at',
                'processing_completed_at',
                'parsed_at',
                'retry_count',
            ]);
        });
    }
};
```

- [ ] Run migration

```bash
php artisan migrate
```

- [ ] Update QuoteSubmission model

```php
// In apps/atomy-q/API/app/Models/QuoteSubmission.php
protected $fillable = [
    // ... existing
    'error_code',
    'error_message',
    'processing_started_at',
    'processing_completed_at',
    'parsed_at',
    'retry_count',
];

protected $casts = [
    // ... existing
    'processing_started_at' => 'datetime',
    'processing_completed_at' => 'datetime',
    'parsed_at' => 'datetime',
    'retry_count' => 'integer',
];
```

- [ ] Commit

---

### Phase 2: Nexus Layer 1 - Quote Extraction Contract

- [ ] **Task 2: Create QuoteExtractionServiceInterface in MachineLearning package**

**Files:**
- Create: `packages/MachineLearning/src/Contracts/QuoteExtractionServiceInterface.php`

**Steps:**
- [ ] Create interface

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use Nexus\MachineLearning\ValueObjects\QuoteExtractionResult;

interface QuoteExtractionServiceInterface
{
    /**
     * Extract line items from a document (PDF for Alpha)
     *
     * @param string $filePath Absolute path to the document
     * @param string $tenantId Tenant identifier
     * @return QuoteExtractionResult
     */
    public function extract(string $filePath, string $tenantId): QuoteExtractionResult;
}
```

- [ ] Create QuoteExtractionResult value object

```php
// packages/MachineLearning/src/ValueObjects/QuoteExtractionResult.php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

final readonly class QuoteExtractionResult
{
    /**
     * @param array<int, array<string, mixed>> $extractedLines
     */
    public function __construct(
        public array $extractedLines,
        public float $confidence,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}
}
```

- [ ] Commit

---

- [ ] **Task 3: Create VertexAIMockProvider implementing QuoteExtractionServiceInterface**

**Files:**
- Create: `packages/MachineLearning/src/Services/VertexAIMockProvider.php`

**Steps:**
- [ ] Create mock provider

```php
<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Services;

use Nexus\MachineLearning\Contracts\QuoteExtractionServiceInterface;
use Nexus\MachineLearning\ValueObjects\QuoteExtractionResult;

final class VertexAIMockProvider implements QuoteExtractionServiceInterface
{
    public function extract(string $filePath, string $tenantId): QuoteExtractionResult
    {
        // Simulate processing delay
        usleep(500000); // 500ms

        // Return realistic sample data for Alpha testing
        $extractedLines = [
            [
                'source_vendor' => 'Vendor Quote',
                'source_description' => 'Industrial Pump Model X500',
                'source_quantity' => 10.0000,
                'source_uom' => 'units',
                'source_unit_price' => 1250.00,
                'raw_data' => [
                    'line_number' => 1,
                    'original_text' => 'Industrial Pump Model X500 - 10 units @ $1,250.00',
                    'extracted_at' => now()->toAtomString(),
                ],
            ],
            [
                'source_vendor' => 'Vendor Quote',
                'source_description' => 'Valve Assembly Kit Type B',
                'source_quantity' => 25.0000,
                'source_uom' => 'kits',
                'source_unit_price' => 85.50,
                'raw_data' => [
                    'line_number' => 2,
                    'original_text' => 'Valve Assembly Kit Type B - 25 kits @ $85.50',
                    'extracted_at' => now()->toAtomString(),
                ],
            ],
            [
                'source_vendor' => 'Vendor Quote',
                'source_description' => 'Pressure Gauge 0-100 PSI',
                'source_quantity' => 50.0000,
                'source_uom' => 'pcs',
                'source_unit_price' => 42.00,
                'raw_data' => [
                    'line_number' => 3,
                    'original_text' => 'Pressure Gauge 0-100 PSI - 50 pcs @ $42.00',
                    'extracted_at' => now()->toAtomString(),
                ],
            ],
        ];

        return new QuoteExtractionResult(
            extractedLines: $extractedLines,
            confidence: 85.5,
        );
    }
}
```

- [ ] Register in MachineLearningServiceProvider

```php
// In packages/MachineLearning/src/MachineLearningServiceProvider.php
public function register(): void
{
    // ... existing
    $this->app->singleton(
        QuoteExtractionServiceInterface::class,
        VertexAIMockProvider::class,
    );
}
```

- [ ] Commit

---

### Phase 3: Nexus Layer 2 - Quote Ingestion Orchestrator

- [ ] **Task 4: Create QuoteIngestion orchestrator package**

**Files:**
- Create: `orchestrators/QuoteIngestion/composer.json`
- Create: `orchestrators/QuoteIngestion/src/QuoteIngestionServiceProvider.php`
- Create: `orchestrators/QuoteIngestion/src/QuoteIngestionOrchestrator.php`

**Steps:**
- [ ] Create composer.json

```json
{
    "name": "nexus/quote-ingestion",
    "description": "Quote ingestion orchestration - extraction, normalization, persistence",
    "type": "library",
    "require": {
        "php": "^8.3",
        "nexus/machine-learning": "^1.0",
        "laravel/framework": "^11.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "Nexus\\QuoteIngestion\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Nexus\\QuoteIngestion\\Tests\\": "tests/"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

- [ ] Create service provider

```php
<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion;

use Illuminate\Support\ServiceProvider;
use Nexus\MachineLearning\Contracts\QuoteExtractionServiceInterface;

class QuoteIngestionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QuoteIngestionOrchestrator::class, function ($app) {
            return new QuoteIngestionOrchestrator(
                $app->make(QuoteExtractionServiceInterface::class),
            );
        });
    }
}
```

- [ ] Create orchestrator

```php
<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion;

use App\Models\QuoteSubmission;
use Nexus\MachineLearning\Contracts\QuoteExtractionServiceInterface;
use Nexus\MachineLearning\ValueObjects\QuoteExtractionResult;

final class QuoteIngestionOrchestrator
{
    public function __construct(
        private QuoteExtractionServiceInterface $extractionService,
    ) {}

    /**
     * Process a quote submission through the full pipeline
     *
     * @param string $quoteSubmissionId
     * @return void
     */
    public function process(string $quoteSubmissionId): void
    {
        $submission = QuoteSubmission::find($quoteSubmissionId);
        
        if ($submission === null) {
            return;
        }

        // Update status to extracting
        $submission->status = 'extracting';
        $submission->processing_started_at = now();
        $submission->save();

        try {
            // Step 1: Extract from file
            $filePath = storage_path('app/' . $submission->file_path);
            $result = $this->extractionService->extract($filePath, $submission->tenant_id);

            // Step 2: Handle extraction failure
            if ($result->errorCode !== null) {
                $this->handleFailure($submission, $result->errorCode, $result->errorMessage);
                return;
            }

            // Step 3: Update to normalizing
            $submission->status = 'normalizing';
            $submission->save();

            // Step 4: Persist extracted lines
            $this->persistSourceLines($submission, $result);

            // Step 5: Determine final status based on confidence threshold
            $finalStatus = $result->confidence >= 80.0 ? 'ready' : 'needs_review';
            $submission->status = $finalStatus;
            $submission->confidence = $result->confidence;
            $submission->line_items_count = count($result->extractedLines);
            $submission->processing_completed_at = now();
            $submission->parsed_at = now();
            $submission->save();

        } catch (\Throwable $e) {
            $this->handleFailure($submission, 'EXTRACTION_FAILED', $e->getMessage());
        }
    }

    private function persistSourceLines(QuoteSubmission $submission, QuoteExtractionResult $result): void
    {
        $sortOrder = 0;
        foreach ($result->extractedLines as $line) {
            $submission->sourceLines()->create([
                'tenant_id' => $submission->tenant_id,
                'quote_submission_id' => $submission->id,
                'source_vendor' => $line['source_vendor'],
                'source_description' => $line['source_description'],
                'source_quantity' => $line['source_quantity'],
                'source_uom' => $line['source_uom'],
                'source_unit_price' => $line['source_unit_price'],
                'raw_data' => $line['raw_data'],
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function handleFailure(QuoteSubmission $submission, string $errorCode, ?string $errorMessage): void
    {
        $submission->status = 'failed';
        $submission->error_code = $errorCode;
        $submission->error_message = $errorMessage;
        $submission->processing_completed_at = now();
        $submission->save();
    }
}
```

- [ ] Add relationship to QuoteSubmission model if not exists

```php
// In QuoteSubmission.php
public function sourceLines(): HasMany
{
    return $this->hasMany(NormalizationSourceLine::class, 'quote_submission_id');
}
```

- [ ] Commit

---

### Phase 4: Laravel Job with Sync Fallback

- [ ] **Task 5: Create ProcessQuoteSubmissionJob**

**Files:**
- Create: `apps/atomy-q/API/app/Jobs/ProcessQuoteSubmissionJob.php`

**Steps:**
- [ ] Create job

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\QuoteSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;

class ProcessQuoteSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300]; // 10s, 60s, 300s

    public function __construct(
        public string $quoteSubmissionId,
    ) {
        $this->uniqueId = $quoteSubmissionId;
    }

    public function handle(QuoteIngestionOrchestrator $orchestrator): void
    {
        $submission = QuoteSubmission::find($this->quoteSubmissionId);
        
        if ($submission === null) {
            Log::warning('Quote submission not found', ['id' => $this->quoteSubmissionId]);
            return;
        }

        // Check if already processing
        if (in_array($submission->status, ['extracting', 'normalizing'], true)) {
            Log::info('Quote submission already processing', ['id' => $this->quoteSubmissionId]);
            return;
        }

        // Increment retry count
        $submission->retry_count = ($submission->retry_count ?? 0) + 1;
        $submission->save();

        $orchestrator->process($this->quoteSubmissionId);
    }

    public function failed(\Throwable $exception): void
    {
        $submission = QuoteSubmission::find($this->quoteSubmissionId);
        
        if ($submission !== null) {
            $submission->status = 'failed';
            $submission->error_code = 'MAX_RETRIES_EXCEEDED';
            $submission->error_message = $exception->getMessage();
            $submission->processing_completed_at = now();
            $submission->save();
        }
    }

    /**
     * Execute synchronously (sync fallback)
     */
    public function runSync(QuoteIngestionOrchestrator $orchestrator): void
    {
        Log::warning('ProcessQuoteSubmissionJob running in sync fallback mode', [
            'quote_submission_id' => $this->quoteSubmissionId,
        ]);
        $this->handle($orchestrator);
    }
}
```

- [ ] Commit

---

### Phase 5: Controller Integration

- [ ] **Task 6: Wire upload to dispatch job, implement real reparse**

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`

**Steps:**
- [ ] Add imports

```php
use App\Jobs\ProcessQuoteSubmissionJob;
```

- [ ] Modify upload() to dispatch job

```php
// After $qs->save(), add:
ProcessQuoteSubmissionJob::dispatch($qs->id);
```

- [ ] Implement real reparse() method

```php
public function reparse(Request $request, string $id): JsonResponse
{
    $tenantId = $this->tenantId($request);
    
    $qs = QuoteSubmission::query()
        ->where('tenant_id', $tenantId)
        ->where('id', $id)
        ->first();

    if ($qs === null) {
        return response()->json(['message' => 'Quote submission not found'], 404);
    }

    // Idempotent: if already processing, return current state
    if (in_array($qs->status, ['extracting', 'normalizing'], true)) {
        return response()->json([
            'data' => [
                'id' => $qs->id,
                'status' => $qs->status,
                'message' => 'Processing already in progress',
            ],
        ], 202);
    }

    // Reset and re-dispatch
    $qs->status = 'uploaded';
    $qs->error_code = null;
    $qs->error_message = null;
    $qs->processing_started_at = null;
    $qs->processing_completed_at = null;
    $qs->retry_count = 0;
    $qs->save();

    // Delete existing source lines for clean reparse
    $qs->sourceLines()->delete();

    ProcessQuoteSubmissionJob::dispatch($qs->id);

    return response()->json([
        'data' => [
            'id' => $qs->id,
            'status' => 'extracting',
        ],
    ], 202);
}
```

- [ ] Enhance show() to return error fields

```php
private function quoteSubmissionData(QuoteSubmission $submission): array
{
    return [
        'id' => $submission->id,
        'rfq_id' => $submission->rfq_id,
        'vendor_id' => $submission->vendor_id,
        'vendor_name' => $submission->vendor_name,
        'uploaded_by' => $submission->uploaded_by,
        'status' => $submission->status,
        'file_path' => $submission->file_path,
        'file_type' => $submission->file_type,
        'original_filename' => $submission->original_filename,
        'blocking_issue_count' => $submission->blockingIssueCount(),
        'submitted_at' => $submission->submitted_at?->toAtomString(),
        // New fields
        'error_code' => $submission->error_code,
        'error_message' => $submission->error_message,
        'processing_started_at' => $submission->processing_started_at?->toAtomString(),
        'processing_completed_at' => $submission->processing_completed_at?->toAtomString(),
        'parsed_at' => $submission->parsed_at?->toAtomString(),
        'retry_count' => $submission->retry_count,
        'line_items_count' => $submission->line_items_count,
        'confidence' => $submission->confidence,
    ];
}
```

- [ ] Commit

---

### Phase 6: Sync Fallback for Dev/Testing

- [ ] **Task 7: Add sync fallback to upload endpoint**

**Files:**
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/QuoteSubmissionController.php`

**Steps:**
- [ ] In upload() method, add sync fallback option

```php
// After $qs->save()
$shouldRunSync = filter_var(env('QUEUE_CONNECTION', 'redis'), FILTER_SANITIZE_STRING) === 'sync'
    || app()->environment(['local', 'testing']);

if ($shouldRunSync) {
    $job = new ProcessQuoteSubmissionJob($qs->id);
    $job->runSync(app(QuoteIngestionOrchestrator::class));
} else {
    ProcessQuoteSubmissionJob::dispatch($qs->id);
}
```

- [ ] Add import for QuoteIngestionOrchestrator

```php
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;
```

- [ ] Commit

---

### Phase 7: Testing

- [ ] **Task 8: Write feature tests for the pipeline**

**Files:**
- Create: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`

**Steps:**
- [ ] Create comprehensive test

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessQuoteSubmissionJob;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QuoteIngestionPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_triggers_async_job(): void
    {
        Queue::fake();
        
        $rfq = $this->createRfq();
        
        $response = $this->postJson('/api/v1/quote-submissions', [
            'rfq_id' => $rfq->id,
            'vendor_id' => 'vendor-123',
            'vendor_name' => 'Test Vendor',
        ], $this->authHeaders());
        
        $response->assertStatus(201);
        
        $submission = QuoteSubmission::first();
        Queue::assertPushed(ProcessQuoteSubmissionJob::class, function ($job) use ($submission) {
            return $job->quoteSubmissionId === $submission->id;
        });
    }

    public function test_reparse_resets_and_reprocesses(): void
    {
        $rfq = $this->createRfq();
        
        $submission = QuoteSubmission::create([
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfq->id,
            'vendor_id' => 'vendor-123',
            'vendor_name' => 'Test Vendor',
            'status' => 'failed',
            'error_code' => 'EXTRACTION_FAILED',
            'file_path' => 'quote-submissions/test.pdf',
        ]);

        $response = $this->postJson("/api/v1/quote-submissions/{$submission->id}/reparse", [], $this->authHeaders());
        
        $response->assertStatus(202);
        $response->assertJsonPath('data.status', 'extracting');
        
        $submission->refresh();
        $this->assertEquals('uploaded', $submission->status);
        $this->assertNull($submission->error_code);
    }

    public function test_reparse_is_idempotent_when_processing(): void
    {
        $rfq = $this->createRfq();
        
        $submission = QuoteSubmission::create([
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfq->id,
            'vendor_id' => 'vendor-123',
            'vendor_name' => 'Test Vendor',
            'status' => 'extracting',
            'file_path' => 'quote-submissions/test.pdf',
        ]);

        $response = $this->postJson("/api/v1/quote-submissions/{$submission->id}/reparse", [], $this->authHeaders());
        
        $response->assertStatus(202);
        $response->assertJsonPath('data.message', 'Processing already in progress');
        
        $submission->refresh();
        $this->assertEquals('extracting', $submission->status);
    }

    public function test_processing_creates_normalization_source_lines(): void
    {
        $rfq = $this->createRfq();
        
        $submission = QuoteSubmission::create([
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfq->id,
            'vendor_id' => 'vendor-123',
            'vendor_name' => 'Test Vendor',
            'status' => 'uploaded',
            'file_path' => 'quote-submissions/test.pdf',
        ]);

        $orchestrator = app(\Nexus\QuoteIngestion\QuoteIngestionOrchestrator::class);
        $orchestrator->process($submission->id);
        
        $submission->refresh();
        
        $this->assertEquals('ready', $submission->status);
        $this->assertEquals(3, $submission->line_items_count);
        $this->assertNotNull($submission->processing_completed_at);
    }

    public function test_low_confidence_goes_to_needs_review(): void
    {
        // This requires mocking the provider to return low confidence
        // Test that confidence < 80% results in needs_review status
        $this->markTestIncomplete('Requires provider mock with low confidence');
    }

    public function test_failure_stores_error_info(): void
    {
        $rfq = $this->createRfq();
        
        $submission = QuoteSubmission::create([
            'tenant_id' => $this->tenantId,
            'rfq_id' => $rfq->id,
            'vendor_id' => 'vendor-123',
            'vendor_name' => 'Test Vendor',
            'status' => 'uploaded',
            'file_path' => 'nonexistent.pdf',
        ]);

        $orchestrator = app(\Nexus\QuoteIngestion\QuoteIngestionOrchestrator::class);
        
        try {
            $orchestrator->process($submission->id);
        } catch (\Throwable $e) {
            // Expected to fail
        }
        
        $submission->refresh();
        
        $this->assertEquals('failed', $submission->status);
        $this->assertNotNull($submission->error_code);
        $this->assertNotNull($submission->processing_completed_at);
    }

    private function createRfq(): Rfq
    {
        return Rfq::create([
            'tenant_id' => $this->tenantId,
            'rfq_number' => 'RFQ-' . uniqid(),
            'title' => 'Test RFQ',
            'status' => 'open',
            'submission_deadline' => now()->addDays(7),
        ]);
    }
}
```

- [ ] Run tests

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/QuoteIngestionPipelineTest.php -v
```

- [ ] Fix any issues, commit

---

### Phase 8: Verification

- [ ] **Task 9: End-to-end verification**

**Steps:**
- [ ] Run all QuoteSubmission tests

```bash
cd apps/atomy-q/API && ./vendor/bin/phpunit tests/Feature/QuoteSubmissionWorkflowTest.php -v
```

- [ ] Verify migration runs cleanly

```bash
php artisan migrate --pretend
```

- [ ] Verify routes exist

```bash
php artisan route:list --path=quote-submissions
```

- [ ] Commit final changes

---

## Summary

| Phase | Tasks | Key Files |
|-------|-------|-----------|
| 1. Schema | 1 | Migration, Model |
| 2. L1 Contract | 2-3 | QuoteExtractionServiceInterface, VertexAIMockProvider |
| 3. L2 Orchestrator | 4 | QuoteIngestionOrchestrator package |
| 4. Job | 5 | ProcessQuoteSubmissionJob |
| 5. Controller | 6 | QuoteSubmissionController |
| 6. Sync Fallback | 7 | Upload method enhancement |
| 7. Tests | 8 | QuoteIngestionPipelineTest |
| 8. Verification | 9 | Run all tests |

**Total estimated tasks:** 9 major tasks with ~25-30 individual steps
