<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Adapters\QuotationIntelligence\DeterministicContentProcessor;
use App\Adapters\QuotationIntelligence\DeterministicSemanticMapper;
use App\Adapters\QuotationIntelligence\DormantLlmContentProcessor;
use App\Adapters\QuotationIntelligence\DormantLlmSemanticMapper;
use App\Jobs\ProcessQuoteSubmissionJob;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;
use Nexus\QuotationIntelligence\Contracts\BatchQuoteComparisonCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\SemanticMapperInterface;
use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use Tests\Feature\Api\ApiTestCase;

final class QuoteIngestionPipelineTest extends ApiTestCase
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
        $app['config']->set('queue.default', 'sync');

        return $app;
    }

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => 'pipeline-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Pipeline Test User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    private function createRfq(User $user): Rfq
    {
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-PIPELINE-' . Str::upper(Str::random(6)),
            'title' => 'Pipeline Test RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Industrial Pump Model X500',
            'quantity' => 5,
            'uom' => 'EA',
            'unit_price' => 2500.00,
            'currency' => 'USD',
            'sort_order' => 0,
        ]);

        RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Control Valve DN50',
            'quantity' => 10,
            'uom' => 'EA',
            'unit_price' => 450.00,
            'currency' => 'USD',
            'sort_order' => 1,
        ]);

        RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Stainless Steel Piping 2 inch',
            'quantity' => 100,
            'uom' => 'M',
            'unit_price' => 85.00,
            'currency' => 'USD',
            'sort_order' => 2,
        ]);

        return $rfq;
    }

    private function resetQuoteIntelligenceBindings(): void
    {
        app()->forgetInstance(OrchestratorContentProcessorInterface::class);
        app()->forgetInstance(SemanticMapperInterface::class);
        app()->forgetInstance(QuotationIntelligenceCoordinatorInterface::class);
        app()->forgetInstance(QuoteIngestionOrchestrator::class);
        app()->forgetInstance(BatchQuoteComparisonCoordinatorInterface::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadAtomyConfigContractWithoutQuoteIntelligenceMode(): array
    {
        $previousMode = getenv('QUOTE_INTELLIGENCE_MODE');

        putenv('QUOTE_INTELLIGENCE_MODE');
        unset($_ENV['QUOTE_INTELLIGENCE_MODE'], $_SERVER['QUOTE_INTELLIGENCE_MODE']);

        /** @var array<string, mixed> $config */
        $config = require config_path('atomy.php');

        if ($previousMode === false) {
            putenv('QUOTE_INTELLIGENCE_MODE');
        } else {
            putenv('QUOTE_INTELLIGENCE_MODE=' . $previousMode);
            $_ENV['QUOTE_INTELLIGENCE_MODE'] = $previousMode;
            $_SERVER['QUOTE_INTELLIGENCE_MODE'] = $previousMode;
        }

        return $config;
    }

    public function test_upload_triggers_job(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/upload', [
                'rfq_id' => $rfq->id,
                'vendor_id' => (string) Str::ulid(),
                'vendor_name' => 'Test Vendor',
                'file' => UploadedFile::fake()->create('quote.pdf', 12, 'application/pdf'),
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'ready');

        $submission = QuoteSubmission::first();
        self::assertNotNull($submission);
        self::assertEquals('ready', $submission->status);
    }

    public function test_quote_intelligence_defaults_to_deterministic_mode(): void
    {
        $contract = $this->loadAtomyConfigContractWithoutQuoteIntelligenceMode();

        self::assertSame('deterministic', $contract['quote_intelligence']['mode']);

        config()->set('atomy', $contract);
        $this->resetQuoteIntelligenceBindings();
        self::assertInstanceOf(DeterministicContentProcessor::class, app()->make(OrchestratorContentProcessorInterface::class));
        self::assertInstanceOf(DeterministicSemanticMapper::class, app()->make(SemanticMapperInterface::class));

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
        $this->resetQuoteIntelligenceBindings();
        config()->set('atomy.quote_intelligence.mode', 'llm');
        config()->set('atomy.quote_intelligence.llm.provider', '');
        config()->set('atomy.quote_intelligence.llm.model', '');
        config()->set('atomy.quote_intelligence.llm.base_url', '');
        config()->set('atomy.quote_intelligence.llm.api_key', '');

        $contentProcessor = app()->make(OrchestratorContentProcessorInterface::class);
        $semanticMapper = app()->make(SemanticMapperInterface::class);

        self::assertInstanceOf(DormantLlmContentProcessor::class, $contentProcessor);
        self::assertInstanceOf(DormantLlmSemanticMapper::class, $semanticMapper);

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

    public function test_quote_intelligence_llm_mode_semantic_mapper_is_dormant(): void
    {
        $this->resetQuoteIntelligenceBindings();
        config()->set('atomy.quote_intelligence.mode', 'llm');

        $semanticMapper = app()->make(SemanticMapperInterface::class);

        self::assertInstanceOf(DormantLlmSemanticMapper::class, $semanticMapper);

        $this->expectException(QuotationIntelligenceException::class);
        $semanticMapper->mapToTaxonomy('Pump assembly', (string) Str::ulid());
    }

    public function test_quote_intelligence_llm_mode_with_provider_config_fails_until_adapter_exists(): void
    {
        $this->resetQuoteIntelligenceBindings();
        config()->set('atomy.quote_intelligence.mode', 'llm');
        config()->set('atomy.quote_intelligence.llm.provider', 'openai');
        config()->set('atomy.quote_intelligence.llm.model', 'gpt-5');
        config()->set('atomy.quote_intelligence.llm.base_url', 'https://api.example.test/v1');
        config()->set('atomy.quote_intelligence.llm.api_key', 'test-key');

        $contentProcessor = app()->make(OrchestratorContentProcessorInterface::class);

        self::assertInstanceOf(DormantLlmContentProcessor::class, $contentProcessor);

        $this->expectException(QuotationIntelligenceException::class);
        $this->expectExceptionMessage('Quote intelligence LLM mode is configured but no production adapter is implemented yet.');

        $contentProcessor->analyze(storage_path('app/quote-submissions/configured-llm.pdf'));
    }

    public function test_unsupported_quote_intelligence_mode_fails_safe_through_pipeline(): void
    {
        $this->resetQuoteIntelligenceBindings();
        config()->set('atomy.quote_intelligence.mode', 'unsupported-mode');

        $contentProcessor = app()->make(OrchestratorContentProcessorInterface::class);
        $semanticMapper = app()->make(SemanticMapperInterface::class);

        self::assertNotInstanceOf(DeterministicContentProcessor::class, $contentProcessor);
        self::assertNotInstanceOf(DeterministicSemanticMapper::class, $semanticMapper);

        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/upload', [
                'rfq_id' => $rfq->id,
                'vendor_id' => (string) Str::ulid(),
                'vendor_name' => 'Unsupported Mode Vendor',
                'file' => UploadedFile::fake()->create('quote.pdf', 12, 'application/pdf'),
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'failed');
        $response->assertJsonPath('data.error_code', 'INTELLIGENCE_FAILED');
        $response->assertJsonPath('data.error_message', 'Quote intelligence processing failed.');
    }

    public function test_job_failed_path_sanitizes_retry_exhaustion_error_message(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $submission = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Sensitive Vendor',
            'status' => 'uploaded',
            'file_path' => 'quote-submissions/test.pdf',
            'submitted_at' => now(),
            'confidence' => 0.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $job = new ProcessQuoteSubmissionJob($submission->id);
        $job->failed(new \RuntimeException('Retry exhausted for secret backend endpoint https://sensitive.example.test'));

        $submission->refresh();

        self::assertSame('failed', $submission->status);
        self::assertSame('MAX_RETRIES_EXCEEDED', $submission->error_code);
        self::assertSame(QuoteIngestionOrchestrator::GENERIC_FAILURE_MESSAGE, $submission->error_message);
    }

    public function test_job_failed_path_logs_retry_exhaustion_when_submission_is_missing(): void
    {
        Log::spy();

        $job = new ProcessQuoteSubmissionJob('missing-quote-submission-id');
        $job->failed(new \RuntimeException('Retry exhausted for secret backend endpoint https://sensitive.example.test'));

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(static function (string $message, array $context): bool {
                return $message === 'ProcessQuoteSubmissionJob exhausted retries'
                    && $context['quote_submission_id'] === 'missing-quote-submission-id'
                    && $context['error_class'] === \RuntimeException::class
                    && $context['error_message'] === 'Retry exhausted for secret backend endpoint https://sensitive.example.test';
            });
    }

    public function test_reparse_resets_and_reprocesses(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $submission = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Test Vendor',
            'status' => 'failed',
            'error_code' => 'EXTRACTION_FAILED',
            'file_path' => 'quote-submissions/test.pdf',
            'submitted_at' => now(),
            'confidence' => 0.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/' . $submission->id . '/reparse');

        $response->assertStatus(202);

        $submission->refresh();
        self::assertNotEquals('failed', $submission->status);
        self::assertNull($submission->error_code);
    }

    public function test_reparse_is_idempotent_when_processing(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $submission = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Test Vendor',
            'status' => 'extracting',
            'file_path' => 'quote-submissions/test.pdf',
            'submitted_at' => now(),
            'confidence' => 0.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/' . $submission->id . '/reparse');

        $response->assertStatus(202);
        $response->assertJsonPath('data.message', 'Processing already in progress');

        $submission->refresh();
        self::assertEquals('extracting', $submission->status);
    }

    public function test_orchestrator_updates_status(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $submission = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Test Vendor',
            'status' => 'uploaded',
            'file_path' => 'quote-submissions/test.pdf',
            'submitted_at' => now(),
            'confidence' => 0.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $orchestrator = app(QuoteIngestionOrchestrator::class);
        $orchestrator->process($submission->id, $submission->tenant_id);

        $submission->refresh();

        self::assertEquals('ready', $submission->status);
        self::assertEquals(3, $submission->line_items_count);
        self::assertNotNull($submission->processing_completed_at);
    }

    public function test_show_returns_processing_fields(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $submission = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Test Vendor',
            'status' => 'failed',
            'error_code' => 'EXTRACTION_FAILED',
            'error_message' => 'File not found',
            'file_path' => 'quote-submissions/test.pdf',
            'submitted_at' => now(),
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
            'retry_count' => 1,
            'confidence' => 0.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->get('/api/v1/quote-submissions/' . $submission->id);

        $response->assertOk();
        $response->assertJsonPath('data.error_code', 'EXTRACTION_FAILED');
        $response->assertJsonPath('data.error_message', 'File not found');
        $response->assertJsonPath('data.retry_count', 1);
    }
}
