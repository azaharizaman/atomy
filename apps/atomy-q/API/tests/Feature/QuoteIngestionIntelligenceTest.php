<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Nexus\MachineLearning\Contracts\QuoteExtractionServiceInterface;
use Nexus\MachineLearning\ValueObjects\QuoteExtractionResult;
use Tests\Feature\Api\ApiTestCase;

final class QuoteIngestionIntelligenceTest extends ApiTestCase
{
    use RefreshDatabase;

    public function test_end_to_end_ingestion_with_intelligence_fields(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfq($user);
        $lineItem = RfqLineItem::create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Target Item',
            'quantity' => 1,
            'uom' => 'EA',
        ]);

        // Mock the extraction service to return "smart" data
        // This is how the NEW orchestrator will interact with the mapping service
        $mockResult = new QuoteExtractionResult(
            extractedLines: [[
                'source_vendor' => 'Test Vendor',
                'source_description' => 'Matched Item',
                'source_quantity' => 1.0,
                'source_uom' => 'EA',
                'source_unit_price' => 100.0,
                'rfq_line_item_id' => $lineItem->id, // Smart mapping
                'ai_confidence' => 95.0,
                'taxonomy_code' => 'PUMP-001',
                'raw_data' => ['original' => 'Matched Item 1EA $100'],
            ]],
            confidence: 95.0
        );

        $this->mock(QuoteExtractionServiceInterface::class)
            ->shouldReceive('extract')
            ->andReturn($mockResult);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/upload', [
                'rfq_id' => $rfq->id,
                'vendor_id' => (string) Str::ulid(),
                'vendor_name' => 'Test Vendor',
                'file' => UploadedFile::fake()->create('quote.pdf', 12, 'application/pdf'),
            ]);

        $response->assertCreated();
        $submissionId = $response->json('data.id');

        $submission = QuoteSubmission::with('normalizationSourceLines')->find($submissionId);
        $sourceLine = $submission->normalizationSourceLines->first();

        $this->assertNotNull($sourceLine, 'Source line should be created');
        
        // These assertions are expected to FAIL initially 
        // because the current orchestrator does not yet persist these fields.
        $this->assertEquals($lineItem->id, $sourceLine->rfq_line_item_id, 'rfq_line_item_id should be auto-linked');
        $this->assertEquals(95.0, (float) $sourceLine->ai_confidence);
        $this->assertEquals('PUMP-001', $sourceLine->taxonomy_code);
    }

    private function createUser(): User 
    {
         return User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => 'test-' . Str::random(5) . '@example.com',
            'name' => 'Test User',
            'password_hash' => 'hash',
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
        ]);
    }

    private function createRfq(User $user): Rfq 
    {
        return Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-' . Str::random(5),
            'title' => 'Test RFQ',
            'status' => 'draft',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(7),
        ]);
    }
}
