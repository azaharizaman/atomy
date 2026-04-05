<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessQuoteSubmissionJob;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;
use Tests\Feature\Api\ApiTestCase;

final class QuoteIngestionIntelligenceTest extends ApiTestCase
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
            'email' => 'intelligence-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Intelligence Test User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    private function createRfqWithLineItems(User $user): Rfq
    {
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-INTEL-' . Str::upper(Str::random(6)),
            'title' => 'Intelligence Test RFQ',
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

    public function test_orchestrator_creates_source_lines_with_rfq_line_item_ids(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfqWithLineItems($user);

        $lineItems = $rfq->lineItems()->get();
        self::assertCount(3, $lineItems);

        $submission = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Smart Mock Vendor',
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

        $orchestrator = app(QuoteIngestionOrchestrator::class);
        $orchestrator->process($submission->id, $submission->tenant_id);

        $submission->refresh();

        self::assertEquals('ready', $submission->status);
        self::assertGreaterThan(0, $submission->line_items_count);

        $sourceLines = $submission->normalizationSourceLines()->get();
        self::assertNotEmpty($sourceLines, 'Source lines should be created after processing');

        foreach ($sourceLines as $sourceLine) {
            self::assertNotNull(
                $sourceLine->rfq_line_item_id,
                sprintf('Source line at sort_order %d should have rfq_line_item_id populated', $sourceLine->sort_order)
            );

            $rfqLineItem = $rfq->lineItems()->where('id', $sourceLine->rfq_line_item_id)->first();
            self::assertNotNull($rfqLineItem, 'rfq_line_item_id should reference a valid RFQ line item');
        }
    }

    public function test_reparse_deletes_unmapped_lines_and_reprocesses(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfqWithLineItems($user);

        $submission = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Test Vendor',
            'status' => 'ready',
            'file_path' => 'quote-submissions/reparse-test.pdf',
            'file_type' => 'application/pdf',
            'submitted_at' => now(),
            'confidence' => 85.0,
            'line_items_count' => 3,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $existingLines = $submission->normalizationSourceLines()->createMany([
            [
                'tenant_id' => $user->tenant_id,
                'quote_submission_id' => $submission->id,
                'rfq_line_item_id' => null,
                'source_vendor' => 'Old Vendor',
                'source_description' => 'Old Line 1',
                'source_quantity' => 1,
                'source_uom' => 'EA',
                'source_unit_price' => 100.00,
                'raw_data' => ['old' => true],
                'sort_order' => 0,
            ],
            [
                'tenant_id' => $user->tenant_id,
                'quote_submission_id' => $submission->id,
                'rfq_line_item_id' => $rfq->lineItems()->first()->id,
                'source_vendor' => 'Old Vendor',
                'source_description' => 'Old Mapped Line',
                'source_quantity' => 2,
                'source_uom' => 'EA',
                'source_unit_price' => 200.00,
                'raw_data' => ['old' => true, 'mapped' => true],
                'sort_order' => 1,
            ],
        ]);

        self::assertCount(2, $submission->normalizationSourceLines()->get());

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/' . $submission->id . '/reparse');

        $response->assertStatus(202);

        $submission->refresh();

        self::assertNull($submission->error_code);

        $remainingLines = $submission->normalizationSourceLines()->get();
        
        foreach ($remainingLines as $line) {
            self::assertNotNull(
                $line->rfq_line_item_id,
                sprintf('After reparse, source line at sort_order %d should have rfq_line_item_id', $line->sort_order)
            );
        }
    }

    public function test_upload_and_process_creates_intelligent_source_lines(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfqWithLineItems($user);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/upload', [
                'rfq_id' => $rfq->id,
                'vendor_id' => (string) Str::ulid(),
                'vendor_name' => 'Upload Intelligence Vendor',
                'file' => UploadedFile::fake()->create('intelligence-quote.pdf', 12, 'application/pdf'),
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'ready');

        $quoteId = (string) $response->json('data.id');
        $submission = QuoteSubmission::query()->findOrFail($quoteId);

        $sourceLines = $submission->normalizationSourceLines()->orderBy('sort_order')->get();
        self::assertNotEmpty($sourceLines, 'Source lines should be created after upload and processing');

        $lineItems = $rfq->lineItems()->get();
        $mappedRfqs = $sourceLines->pluck('rfq_line_item_id')->filter()->unique();

        self::assertGreaterThan(
            0,
            $mappedRfqs->count(),
            'At least some source lines should have rfq_line_item_id mapped to RFQ line items'
        );
    }
}