<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessQuoteSubmissionJob;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;
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
        return Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-PIPELINE-' . Str::upper(Str::random(6)),
            'title' => 'Pipeline Test RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);
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
