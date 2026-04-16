# Alpha Task 2 Quote Intelligence Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace misleading mock quote-intelligence live bindings with an honest deterministic alpha processor, define a dormant LLM mode contract, and harden ingestion failure handling without expanding beyond Task 2 scope.

**Architecture:** Keep the existing orchestration boundary intact through `OrchestratorContentProcessorInterface`, `SemanticMapperInterface`, `QuotationIntelligenceCoordinatorInterface`, and `QuoteIngestionOrchestrator`. Runtime mode selection happens in the Laravel app layer through config and container bindings: `deterministic` is the active alpha-safe mode, while `llm` is a documented, fail-fast dormant mode until a real provider adapter exists.

**Tech Stack:** Laravel 12, PHP 8.3, PHPUnit feature tests, existing Nexus quote-ingestion and quotation-intelligence orchestrators.

---

## File Map

- `apps/atomy-q/API/config/atomy.php`
  - Add quote-intelligence mode and dormant LLM env contract.
- `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
  - Select quote-intelligence bindings by configured mode.
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/DeterministicContentProcessor.php`
  - Renamed alpha-safe replacement for `MockContentProcessor`.
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/DeterministicSemanticMapper.php`
  - Renamed alpha-safe replacement for `MockSemanticMapper`.
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/DormantLlmContentProcessor.php`
  - Fail-fast placeholder content processor for misconfigured or unavailable `llm` mode.
- `apps/atomy-q/API/app/Adapters/QuotationIntelligence/DormantLlmSemanticMapper.php`
  - Fail-fast placeholder semantic mapper for misconfigured or unavailable `llm` mode.
- `apps/atomy-q/API/app/Jobs/ProcessQuoteSubmissionJob.php`
  - Sanitize terminal retry-exhaustion failure messages.
- `apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php`
  - Verify deterministic processing remains the alpha-success path and metadata persists.
- `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`
  - Verify mode defaults and fail-fast dormant-LLM behavior stay sanitized.
- `apps/atomy-q/API/.env.example`
  - Document runtime mode and dormant LLM env fields.
- `apps/atomy-q/API/README.md`
  - Describe deterministic-now / LLM-later behavior truthfully.
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
  - Record the shipped Task 2 behavior.
- `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`
  - Link the approved Task 2 spec and this implementation plan from Section 9 Task 2.

### Task 1: Add Quote-Intelligence Runtime Config And Mode Selection

**Files:**
- Modify: `apps/atomy-q/API/config/atomy.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Test: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`

- [x] **Step 1: Write the failing mode-selection test**

Add a focused feature test that proves alpha defaults to deterministic mode when no quote-intelligence env is set and that `llm` mode without provider config fails through the ingestion pipeline.

```php
public function test_quote_intelligence_defaults_to_deterministic_mode(): void
{
    config()->set('atomy.quote_intelligence.mode', 'deterministic');

    $user = $this->createUser();
    $rfq = $this->createRfq($user);

    $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
        ->post('/api/v1/quote-submissions/upload', [
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Deterministic Vendor',
            'file' => UploadedFile::fake()->create('quote.pdf', 12, 'application/pdf'),
        ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'ready');
}

public function test_quote_intelligence_llm_mode_without_provider_config_fails_safely(): void
{
    config()->set('atomy.quote_intelligence.mode', 'llm');
    config()->set('atomy.quote_intelligence.llm.provider', '');
    config()->set('atomy.quote_intelligence.llm.model', '');
    config()->set('atomy.quote_intelligence.llm.base_url', '');
    config()->set('atomy.quote_intelligence.llm.api_key', '');

    $user = $this->createUser();
    $rfq = $this->createRfq($user);

    $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
        ->post('/api/v1/quote-submissions/upload', [
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Dormant LLM Vendor',
            'file' => UploadedFile::fake()->create('quote.pdf', 12, 'application/pdf'),
        ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'failed');
    $response->assertJsonPath('data.error_code', 'INTELLIGENCE_FAILED');
    $response->assertJsonPath('data.error_message', 'Quote intelligence processing failed.');
}
```

- [x] **Step 2: Run the focused test to verify the new assertions fail**

Run: `cd apps/atomy-q/API && php artisan test --filter QuoteIngestionPipelineTest`

Expected: FAIL because no `atomy.quote_intelligence` config contract or mode-specific binding exists yet.

- [x] **Step 3: Add the config contract in `config/atomy.php`**

Extend the existing `atomy` config file instead of creating a new config namespace.

```php
'quote_intelligence' => [
    'mode' => (string) env('QUOTE_INTELLIGENCE_MODE', 'deterministic'),
    'llm' => [
        'provider' => (string) env('QUOTE_INTELLIGENCE_LLM_PROVIDER', ''),
        'model' => (string) env('QUOTE_INTELLIGENCE_LLM_MODEL', ''),
        'base_url' => (string) env('QUOTE_INTELLIGENCE_LLM_BASE_URL', ''),
        'api_key' => (string) env('QUOTE_INTELLIGENCE_LLM_API_KEY', ''),
        'timeout_seconds' => (int) env('QUOTE_INTELLIGENCE_LLM_TIMEOUT_SECONDS', 30),
    ],
],
```

- [x] **Step 4: Implement mode-based bindings in `AppServiceProvider.php`**

Replace direct `Mock*` bindings with a config-driven branch that selects deterministic adapters by default and dormant-LLM placeholders only when `mode=llm`.

```php
$mode = (string) config('atomy.quote_intelligence.mode', 'deterministic');

if ($mode === 'llm') {
    $this->app->singleton(
        OrchestratorContentProcessorInterface::class,
        DormantLlmContentProcessor::class
    );
    $this->app->singleton(
        SemanticMapperInterface::class,
        DormantLlmSemanticMapper::class
    );
} else {
    $this->app->singleton(
        OrchestratorContentProcessorInterface::class,
        DeterministicContentProcessor::class
    );
    $this->app->singleton(
        SemanticMapperInterface::class,
        DeterministicSemanticMapper::class
    );
}
```

- [x] **Step 5: Re-run the focused pipeline test**

Run: `cd apps/atomy-q/API && php artisan test --filter QuoteIngestionPipelineTest`

Expected: deterministic-mode assertions now pass, while LLM-mode assertions still fail until the dormant adapters and sanitization behavior are implemented in later tasks.

- [x] **Step 6: Commit the config-and-binding slice**

```bash
git add apps/atomy-q/API/config/atomy.php \
  apps/atomy-q/API/app/Providers/AppServiceProvider.php \
  apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php
git commit -m "feat(api): add quote intelligence mode selection"
```

### Task 2: Rename Deterministic Adapters And Add Dormant LLM Placeholders

**Files:**
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/DeterministicContentProcessor.php`
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/DeterministicSemanticMapper.php`
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/DormantLlmContentProcessor.php`
- Create: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/DormantLlmSemanticMapper.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Test: `apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php`
- Test: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`

- [x] **Step 1: Write failing tests for deterministic metadata persistence and dormant-LLM failure**

Add assertions that deterministic processing still persists the existing alpha metadata and that dormant-LLM bindings raise controlled ingestion failures instead of pretending to work.

```php
public function test_deterministic_mode_persists_normalization_metadata(): void
{
    config()->set('atomy.quote_intelligence.mode', 'deterministic');

    $user = $this->createUser();
    $rfq = $this->createRfqWithLineItems($user);

    $submission = QuoteSubmission::query()->create([
        'tenant_id' => $user->tenant_id,
        'rfq_id' => $rfq->id,
        'vendor_id' => (string) Str::ulid(),
        'vendor_name' => 'Deterministic Test Vendor',
        'status' => 'uploaded',
        'file_path' => 'quote-submissions/intelligence-test.pdf',
        'file_type' => 'application/pdf',
        'original_filename' => 'quote.pdf',
        'submitted_at' => now(),
        'confidence' => 0.0,
        'line_items_count' => 0,
        'warnings_count' => 0,
        'errors_count' => 0,
    ]);

    app(QuoteIngestionOrchestrator::class)->process($submission->id, $submission->tenant_id);

    $submission->refresh();

    $this->assertSame('ready', $submission->status);
    $this->assertGreaterThan(0, $submission->normalizationSourceLines()->count());

    $line = $submission->normalizationSourceLines()->firstOrFail();
    $this->assertNotNull($line->rfq_line_item_id);
    $this->assertNotNull($line->ai_confidence);
    $this->assertNotSame('', (string) $line->taxonomy_code);
    $this->assertNotSame('', (string) $line->mapping_version);
    $this->assertArrayHasKey('quoted_quantity', $line->raw_data);
    $this->assertArrayHasKey('quoted_unit', $line->raw_data);
    $this->assertArrayHasKey('quoted_unit_price', $line->raw_data);
}
```

- [x] **Step 2: Run the intelligence and pipeline tests to verify the new assertions fail**

Run: `cd apps/atomy-q/API && php artisan test --filter "QuoteIngestion(Intelligence|Pipeline)Test"`

Expected: FAIL because the new adapter classes do not exist and LLM-mode bindings still have no fail-fast implementation.

- [x] **Step 3: Create the deterministic adapter replacements**

Copy the current behavior from the `Mock*` adapters into new, honestly named classes without changing the deterministic extraction and taxonomy logic.

```php
final class DeterministicContentProcessor implements OrchestratorContentProcessorInterface
{
    private const VARIATIONS = [
        ' (Alpha Deterministic Match)',
        ' - Premium Grade',
        ' (Industrial Grade)',
        ' - Standard Model',
        ' (Professional Series)',
    ];

    public function analyze(string $storagePath): object
    {
        $submission = QuoteSubmission::query()
            ->where('file_path', $this->resolveRelativePath($storagePath))
            ->with(['rfq.lineItems' => fn ($q) => $q->orderBy('sort_order')])
            ->first();

        $lines = [];

        if ($submission && $submission->rfq && $submission->rfq->lineItems->isNotEmpty()) {
            if ((string) $submission->rfq->tenant_id !== (string) $submission->tenant_id) {
                $submission = null;
            }
        }

        if ($submission && $submission->rfq && $submission->rfq->lineItems->isNotEmpty()) {
            $items = $submission->rfq->lineItems->take(5)->values();
            $baseUnit = (string) ($items->first()->uom ?? 'EA');
            $currency = (string) ($items->first()->currency ?? 'USD');

            foreach ($items as $index => $item) {
                $variation = self::VARIATIONS[$index % count(self::VARIATIONS)];
                $lines[] = [
                    'rfq_line_id' => $item->id,
                    'description' => $item->description . $variation,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => 100.0 + ($index * 10),
                    'unit' => $baseUnit,
                    'currency' => $currency,
                    'terms' => 'Net 30',
                    'bbox' => ['x' => 10, 'y' => 20 + ($index * 30), 'w' => 100, 'h' => 15],
                ];
            }
        }

        return new class($lines) {
            public function __construct(private array $lines) {}

            public function getExtractedField(string $field, mixed $default = null): mixed
            {
                return $field === 'lines' ? $this->lines : $default;
            }
        };
    }

    private function resolveRelativePath(string $storagePath): string
    {
        $prefix = storage_path('app') . DIRECTORY_SEPARATOR;
        if (str_starts_with($storagePath, $prefix)) {
            return substr($storagePath, strlen($prefix));
        }

        return basename($storagePath);
    }
}

final class DeterministicSemanticMapper implements SemanticMapperInterface
{
    private const UNSPSC_CODES = [
        '20101507' => ['pump', 'valve', 'compressor', 'motor'],
        '30150000' => ['pipe', 'tube', 'fitting', 'flange'],
        '40101600' => ['sensor', 'meter', 'gauge', 'indicator'],
    ];

    public function mapToTaxonomy(string $description, string $tenantId): array
    {
        $lowerDesc = strtolower($description);

        foreach (self::UNSPSC_CODES as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lowerDesc, $keyword)) {
                    return [
                        'code' => $code,
                        'confidence' => 92.0,
                        'version' => 'v25.0',
                    ];
                }
            }
        }

        return [
            'code' => '20101507',
            'confidence' => 90.0,
            'version' => 'v25.0',
        ];
    }

    public function validateCode(string $code, string $version): bool
    {
        return in_array($code, array_keys(self::UNSPSC_CODES), true) || $code === '20101507';
    }
}
```

- [x] **Step 4: Create dormant-LLM placeholder adapters that fail with a domain exception**

Do not attempt provider calls. These adapters should validate config and throw a quote-intelligence exception with a safe operator-facing message whenever `llm` mode is selected before a real provider adapter exists.

```php
final readonly class DormantLlmContentProcessor implements OrchestratorContentProcessorInterface
{
    public function __construct(
        private array $llmConfig,
    ) {}

    public function analyze(string $storagePath): object
    {
        throw new QuotationIntelligenceException(
            $this->hasRequiredConfig()
                ? 'LLM quote intelligence mode is configured but no production adapter is implemented yet.'
                : 'LLM quote intelligence mode requires provider, model, base URL, and API key configuration.'
        );
    }

    private function hasRequiredConfig(): bool
    {
        return $this->llmConfig['provider'] !== ''
            && $this->llmConfig['model'] !== ''
            && $this->llmConfig['base_url'] !== ''
            && $this->llmConfig['api_key'] !== '';
    }
}
```

Use the same fail-fast pattern in `DormantLlmSemanticMapper`, even if `analyze()` will usually fail first. That keeps both interfaces mode-safe.

- [x] **Step 5: Update the provider bindings to construct dormant adapters with config**

```php
$llmConfig = (array) config('atomy.quote_intelligence.llm', []);

$this->app->singleton(
    OrchestratorContentProcessorInterface::class,
    static fn () => new DormantLlmContentProcessor($llmConfig)
);

$this->app->singleton(
    SemanticMapperInterface::class,
    static fn () => new DormantLlmSemanticMapper($llmConfig)
);
```

- [x] **Step 6: Re-run the focused tests**

Run: `cd apps/atomy-q/API && php artisan test --filter "QuoteIngestion(Intelligence|Pipeline)Test"`

Expected: deterministic metadata assertions pass; dormant-LLM mode now fails through the quote-ingestion pipeline instead of fabricating success.

- [x] **Step 7: Commit the adapter slice**

```bash
git add apps/atomy-q/API/app/Adapters/QuotationIntelligence/DeterministicContentProcessor.php \
  apps/atomy-q/API/app/Adapters/QuotationIntelligence/DeterministicSemanticMapper.php \
  apps/atomy-q/API/app/Adapters/QuotationIntelligence/DormantLlmContentProcessor.php \
  apps/atomy-q/API/app/Adapters/QuotationIntelligence/DormantLlmSemanticMapper.php \
  apps/atomy-q/API/app/Providers/AppServiceProvider.php \
  apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php \
  apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php
git commit -m "refactor(api): rename deterministic quote intelligence adapters"
```

### Task 3: Sanitize Quote-Ingestion Failure Paths

**Files:**
- Modify: `apps/atomy-q/API/app/Jobs/ProcessQuoteSubmissionJob.php`
- Modify: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`

- [x] **Step 1: Add the failing retry-exhaustion sanitization test**

Add a test that calls the job `failed()` path directly with a noisy exception and verifies the persisted quote submission still stores only the generic error message.

```php
public function test_job_failed_path_sanitizes_retry_exhaustion_error_message(): void
{
    $user = $this->createUser();
    $rfq = $this->createRfq($user);

    $submission = QuoteSubmission::query()->create([
        'tenant_id' => $user->tenant_id,
        'rfq_id' => $rfq->id,
        'vendor_id' => (string) Str::ulid(),
        'vendor_name' => 'Retry Failure Vendor',
        'status' => 'extracting',
        'file_path' => 'quote-submissions/retry.pdf',
        'submitted_at' => now(),
        'confidence' => 0.0,
        'line_items_count' => 0,
        'warnings_count' => 0,
        'errors_count' => 0,
    ]);

    $job = new ProcessQuoteSubmissionJob($submission->id);
    $job->failed(new \RuntimeException('Provider timeout at https://internal-llm.example with key sk-live-secret'));

    $submission->refresh();

    $this->assertSame('failed', $submission->status);
    $this->assertSame('MAX_RETRIES_EXCEEDED', $submission->error_code);
    $this->assertSame('Quote intelligence processing failed.', $submission->error_message);
}
```

- [x] **Step 2: Run the focused pipeline test**

Run: `cd apps/atomy-q/API && php artisan test --filter QuoteIngestionPipelineTest`

Expected: FAIL because `ProcessQuoteSubmissionJob::failed()` still persists raw exception messages.

- [x] **Step 3: Replace raw retry-exhaustion messaging with the shared generic message**

Keep operator detail in logs, but store only the generic message on the submission record.

```php
final class ProcessQuoteSubmissionJob implements ShouldQueue
{
    private const GENERIC_FAILURE_MESSAGE = 'Quote intelligence processing failed.';

    public function failed(\Throwable $exception): void
    {
        Log::error('Quote submission processing exhausted retries', [
            'quote_submission_id' => $this->quoteSubmissionId,
            'error_class' => $exception::class,
            'error_message' => $exception->getMessage(),
        ]);

        $submission = QuoteSubmission::find($this->quoteSubmissionId);

        if ($submission !== null) {
            $submission->status = 'failed';
            $submission->error_code = 'MAX_RETRIES_EXCEEDED';
            $submission->error_message = self::GENERIC_FAILURE_MESSAGE;
            $submission->processing_completed_at = now();
            $submission->save();
        }
    }
}
```

- [x] **Step 4: Re-run the pipeline test**

Run: `cd apps/atomy-q/API && php artisan test --filter QuoteIngestionPipelineTest`

Expected: PASS with the job failure path now matching the orchestrator’s sanitized API-visible behavior.

- [x] **Step 5: Commit the failure-handling slice**

```bash
git add apps/atomy-q/API/app/Jobs/ProcessQuoteSubmissionJob.php \
  apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php
git commit -m "fix(api): sanitize quote ingestion terminal failures"
```

### Task 4: Update Environment Docs, Implementation Summary, And Release Links

**Files:**
- Modify: `apps/atomy-q/API/.env.example`
- Modify: `apps/atomy-q/API/README.md`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md`

- [x] **Step 1: Add the quote-intelligence env contract to `.env.example`**

Insert the new deterministic-default and dormant-LLM variables near the existing alpha config section.

```env
QUOTE_INTELLIGENCE_MODE=deterministic
QUOTE_INTELLIGENCE_LLM_PROVIDER=
QUOTE_INTELLIGENCE_LLM_MODEL=
QUOTE_INTELLIGENCE_LLM_BASE_URL=
QUOTE_INTELLIGENCE_LLM_API_KEY=
QUOTE_INTELLIGENCE_LLM_TIMEOUT_SECONDS=30
```

- [x] **Step 2: Update the API README**

Add a short section that states deterministic mode is the current alpha path and that `llm` mode is defined but dormant until provider wiring exists.

```md
### Quote intelligence modes

- `QUOTE_INTELLIGENCE_MODE=deterministic` is the supported alpha default.
- `QUOTE_INTELLIGENCE_MODE=llm` is reserved for future provider-backed normalization and will fail fast until a production adapter and provider settings are supplied.
- Alpha does not silently fall back from `llm` mode to deterministic mode in live operation.
```

- [x] **Step 3: Update `IMPLEMENTATION_SUMMARY.md`**

Append a short section documenting the shipped behavior.

```md
**Quote intelligence mode cleanup (April 2026):** Atomy-Q now binds an explicit quote-intelligence runtime mode through `config/atomy.php`. The alpha default is a deterministic processor and semantic mapper with honest non-`Mock` naming. A dormant `llm` mode is documented through env contract and fail-fast placeholder adapters so live mode does not silently pretend external AI is available. Quote-ingestion terminal failures now persist the generic user-facing message `Quote intelligence processing failed.` while logs retain operator detail.
```

- [x] **Step 4: Link the Task 2 spec and plan from the release plan**

Add the follow-up references directly under Section 9 Task 2.

```md
**Files:**
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Modify or add: `apps/atomy-q/API/app/Adapters/QuotationIntelligence/*`
- Modify: `apps/atomy-q/API/.env.example`
- Modify: `apps/atomy-q/API/README.md`
- Test: `apps/atomy-q/API/tests/Feature/QuoteIngestionIntelligenceTest.php`
- Test: `apps/atomy-q/API/tests/Feature/QuoteIngestionPipelineTest.php`
- Follow-up spec: [`ALPHA_TASK2_QUOTE_INTELLIGENCE_SPEC_2026-04-16.md`](./ALPHA_TASK2_QUOTE_INTELLIGENCE_SPEC_2026-04-16.md)
- Follow-up plan: [`ALPHA_TASK2_QUOTE_INTELLIGENCE_IMPLEMENTATION_PLAN_2026-04-16.md`](./ALPHA_TASK2_QUOTE_INTELLIGENCE_IMPLEMENTATION_PLAN_2026-04-16.md)
```

- [x] **Step 5: Run the minimum Task 2 verification commands**

Run:

```bash
cd apps/atomy-q/API && php artisan test --filter QuoteIngestionIntelligenceTest
cd apps/atomy-q/API && php artisan test --filter QuoteIngestionPipelineTest
```

Expected: PASS for both targeted feature suites.

- [x] **Step 6: Commit the documentation slice**

```bash
git add apps/atomy-q/API/.env.example \
  apps/atomy-q/API/README.md \
  apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md \
  apps/atomy-q/docs/ALPHA_RELEASE_PLAN_2026-04-15.md
git commit -m "docs(api): document quote intelligence runtime modes"
```

## Self-Review

### Spec coverage

- Deterministic alpha mode is covered in Tasks 1 and 2.
- Dormant LLM env contract and config-gated behavior are covered in Tasks 1, 2, and 4.
- Sanitized ingestion failure handling is covered in Task 3.
- Normalization metadata persistence is covered in Task 2.
- API docs, implementation summary, and release-plan linking are covered in Task 4.

No approved spec requirements are left without a task.

### Placeholder scan

- No `TBD`, `TODO`, or deferred implementation placeholders remain in the plan steps.
- Every code-changing step includes concrete file targets and example code.
- Every verification step includes an exact command and expected outcome.

### Type consistency

- The plan consistently uses `QUOTE_INTELLIGENCE_MODE`, `deterministic`, and `llm`.
- The renamed deterministic adapters are used consistently across config, bindings, tests, and docs.
- The sanitized public-facing failure message remains `Quote intelligence processing failed.` throughout the plan.
