<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Adapters\QuotationIntelligence\DeterministicContentProcessor;
use App\Jobs\ProcessQuoteSubmissionJob;
use App\Models\DecisionTrailEntry;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Tests\Feature\Api\ApiTestCase;

final class QuoteIngestionIntelligenceTest extends ApiTestCase
{
    use RefreshDatabase;

    private string $testStorageRoot;

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
        $app['config']->set('logging.default', 'null');
        $app['config']->set('logging.channels.stack.channels', ['null']);

        $this->testStorageRoot = sys_get_temp_dir() . '/atomy-q-api-test-storage';
        if (!is_dir($this->testStorageRoot)) {
            mkdir($this->testStorageRoot, 0777, true);
        }
        $app['config']->set('filesystems.disks.local.root', $this->testStorageRoot);

        return $app;
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testStorageRoot)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->testStorageRoot, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }

            rmdir($this->testStorageRoot);
        }

        parent::tearDown();
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
        config()->set('atomy.quote_intelligence.mode', 'deterministic');

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

        self::assertNotEmpty(
            DecisionTrailEntry::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('rfq_id', $rfq->id)
                ->get(),
            'Auto-mapping should write decision trail entries'
        );

        foreach ($sourceLines as $sourceLine) {
            self::assertNotNull(
                $sourceLine->rfq_line_item_id,
                sprintf('Source line at sort_order %d should have rfq_line_item_id populated', $sourceLine->sort_order)
            );

            self::assertNotNull($sourceLine->ai_confidence, 'ai_confidence should be populated');
            self::assertNotNull($sourceLine->taxonomy_code, 'taxonomy_code should be populated');
            self::assertNotSame('', (string) $sourceLine->taxonomy_code, 'taxonomy_code should be non-empty');
            self::assertNotNull($sourceLine->mapping_version, 'mapping_version should be populated');
            self::assertNotSame('', (string) $sourceLine->mapping_version, 'mapping_version should be non-empty');
            self::assertIsArray($sourceLine->raw_data, 'raw_data should be persisted');
            self::assertArrayHasKey('quoted_quantity', $sourceLine->raw_data, 'raw_data should include quoted_quantity');
            self::assertArrayHasKey('quoted_unit', $sourceLine->raw_data, 'raw_data should include quoted_unit');
            self::assertArrayHasKey('quoted_unit_price', $sourceLine->raw_data, 'raw_data should include quoted_unit_price');

            $rfqLineItem = $rfq->lineItems()->where('id', $sourceLine->rfq_line_item_id)->first();
            self::assertNotNull($rfqLineItem, 'rfq_line_item_id should reference a valid RFQ line item');
        }
    }

    public function test_deterministic_processor_scopes_same_path_lookup_to_current_tenant(): void
    {
        $otherUser = $this->createUser();
        $otherRfq = $this->createRfqWithLineItems($otherUser);
        $targetUser = $this->createUser();
        $targetRfq = $this->createRfqWithLineItems($targetUser);
        $sharedPath = 'quote-submissions/shared-same-path.pdf';

        $otherRfq->lineItems()->update([
            'description' => 'Other Tenant Exclusive Pump',
            'uom' => 'BOX',
            'currency' => 'EUR',
        ]);

        $targetRfq->lineItems()->update([
            'description' => 'Target Tenant Exclusive Valve',
            'uom' => 'SET',
            'currency' => 'MYR',
        ]);

        QuoteSubmission::query()->create([
            'tenant_id' => $otherUser->tenant_id,
            'rfq_id' => $otherRfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Other Tenant Vendor',
            'status' => 'uploaded',
            'file_path' => $sharedPath,
            'file_type' => 'application/pdf',
            'original_filename' => 'other.pdf',
            'submitted_at' => now(),
            'confidence' => 0.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        QuoteSubmission::query()->create([
            'tenant_id' => $targetUser->tenant_id,
            'rfq_id' => $targetRfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Target Tenant Vendor',
            'status' => 'uploaded',
            'file_path' => $sharedPath,
            'file_type' => 'application/pdf',
            'original_filename' => 'target.pdf',
            'submitted_at' => now(),
            'confidence' => 0.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        app(TenantContextInterface::class)->setTenant($targetUser->tenant_id);

        /** @var DeterministicContentProcessor $processor */
        $processor = app(DeterministicContentProcessor::class);
        $result = $processor->analyze(storage_path('app/' . $sharedPath));
        $lines = $result->getExtractedField('lines', []);

        self::assertNotEmpty($lines);

        $targetLineItemIds = $targetRfq->lineItems()->pluck('id')->all();
        $otherLineItemIds = $otherRfq->lineItems()->pluck('id')->all();

        foreach ($lines as $line) {
            self::assertContains($line['rfq_line_id'], $targetLineItemIds);
            self::assertNotContains($line['rfq_line_id'], $otherLineItemIds);
            self::assertStringContainsString('Target Tenant Exclusive Valve', (string) $line['description']);
            self::assertSame('SET', $line['unit']);
            self::assertSame('MYR', $line['currency']);
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
                'raw_data' => ['old' => true, 'mapped' => true, 'override' => ['unit_price' => 210.00]],
                'sort_order' => 1,
            ],
            [
                'tenant_id' => $user->tenant_id,
                'quote_submission_id' => $submission->id,
                'rfq_line_item_id' => null,
                'source_vendor' => 'Old Vendor',
                'source_description' => 'Old Unmapped Override Line',
                'source_quantity' => 3,
                'source_uom' => 'EA',
                'source_unit_price' => 300.00,
                'raw_data' => ['old' => true, 'override' => ['unit_price' => 310.00]],
                'sort_order' => 2,
            ],
        ]);

        self::assertCount(3, $submission->normalizationSourceLines()->get());

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/' . $submission->id . '/reparse');

        $response->assertStatus(202);

        $submission->refresh();

        self::assertNull($submission->error_code);

        self::assertFalse(
            $submission->normalizationSourceLines()->whereKey($existingLines[0]->id)->exists(),
            'Unmapped source line without override should be deleted during delta reparse'
        );

        $mappedOverrideLine = $submission->normalizationSourceLines()->whereKey($existingLines[1]->id)->first();
        self::assertNotNull($mappedOverrideLine, 'Mapped line with override should be preserved');
        self::assertIsArray($mappedOverrideLine->raw_data);
        self::assertArrayHasKey('override', $mappedOverrideLine->raw_data);

        $unmappedOverrideLine = $submission->normalizationSourceLines()->whereKey($existingLines[2]->id)->first();
        self::assertNotNull($unmappedOverrideLine, 'Unmapped line with override should be preserved');
        self::assertIsArray($unmappedOverrideLine->raw_data);
        self::assertArrayHasKey('override', $unmappedOverrideLine->raw_data);

        $remainingLines = $submission->normalizationSourceLines()->get();
        self::assertNotEmpty($remainingLines);

        $nonOverrideLines = $remainingLines->filter(static function ($line): bool {
            $raw = is_array($line->raw_data) ? $line->raw_data : [];
            return !array_key_exists('override', $raw);
        });

        foreach ($nonOverrideLines as $line) {
            self::assertNotNull(
                $line->rfq_line_item_id,
                sprintf('After reparse, non-overridden source line at sort_order %d should have rfq_line_item_id', $line->sort_order)
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

        $mappedRfqs = $sourceLines->pluck('rfq_line_item_id')->filter()->unique();

        self::assertGreaterThan(
            0,
            $mappedRfqs->count(),
            'At least some source lines should have rfq_line_item_id mapped to RFQ line items'
        );

        self::assertNotEmpty(
            DecisionTrailEntry::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('rfq_id', $rfq->id)
                ->get(),
            'Upload processing should write decision trail entries'
        );

        foreach ($sourceLines as $sourceLine) {
            self::assertNotNull($sourceLine->ai_confidence, 'ai_confidence should be populated');
            self::assertNotNull($sourceLine->taxonomy_code, 'taxonomy_code should be populated');
            self::assertNotSame('', (string) $sourceLine->taxonomy_code, 'taxonomy_code should be non-empty');
            self::assertNotNull($sourceLine->mapping_version, 'mapping_version should be populated');
            self::assertNotSame('', (string) $sourceLine->mapping_version, 'mapping_version should be non-empty');
        }
    }
}
