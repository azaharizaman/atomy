<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class QuoteSubmissionWorkflowTest extends ApiTestCase
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

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => 'buyer-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Quote Workflow User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    public function test_quote_submission_upload_requires_rfq_vendor_and_file(): void
    {
        $user = $this->createUser();

        $response = $this->postJson(
            '/api/v1/quote-submissions/upload',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath('details.rfq_id.0', 'The rfq id field is required.');
        $response->assertJsonPath('details.vendor_id.0', 'The vendor id field is required.');
        $response->assertJsonPath('details.file.0', 'The file field is required.');
    }

    public function test_quote_submission_status_rejects_unsupported_transition(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-1001',
            'title' => 'Workflow RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor One',
            'status' => 'uploaded',
            'submitted_at' => now(),
            'confidence' => 100.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $response = $this->patchJson(
            '/api/v1/quote-submissions/' . $quote->id . '/status',
            ['status' => 'ready'],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath('details.status.0', 'Unsupported quote submission status transition.');
    }

    public function test_quote_submission_status_rejects_uploaded_to_ready(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-1002',
            'title' => 'Workflow RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor One',
            'status' => 'uploaded',
            'submitted_at' => now(),
            'confidence' => 100.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $response = $this->patchJson(
            '/api/v1/quote-submissions/' . $quote->id . '/status',
            ['status' => 'ready'],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath('details.status.0', 'Unsupported quote submission status transition.');
    }

    public function test_rfq_overview_counts_uploaded_quotes_as_not_ready(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-1003',
            'title' => 'Workflow RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor One',
            'status' => 'uploaded',
            'submitted_at' => now(),
            'confidence' => 100.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $response = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/overview',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.normalization.accepted_count', 0);
        $response->assertJsonPath('data.normalization.progress_pct', 0);
    }

    public function test_quote_submission_status_maps_legacy_accepted_to_ready(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-1004',
            'title' => 'Workflow RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor One',
            'status' => 'uploaded',
            'submitted_at' => now(),
            'confidence' => 100.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $response = $this->patchJson(
            '/api/v1/quote-submissions/' . $quote->id . '/status',
            ['status' => 'accepted'],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'ready');

        $this->assertDatabaseHas('quote_submissions', [
            'id' => $quote->id,
            'status' => 'ready',
        ]);
    }

    public function test_quote_submission_upload_persists_uploaded_state_and_tenant_id(): void
    {
        Storage::fake('local');

        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-2001',
            'title' => 'Upload RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);
        RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Pump seal kit',
            'quantity' => 2,
            'uom' => 'EA',
            'unit_price' => 100,
            'currency' => 'USD',
            'sort_order' => 1,
        ]);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/upload', [
                'rfq_id' => $rfq->id,
                'vendor_id' => (string) Str::ulid(),
                'vendor_name' => 'Upload Vendor',
                'file' => UploadedFile::fake()->create('quote.txt', 12, 'text/plain'),
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'ready');
        $response->assertJsonPath('data.rfq_id', $rfq->id);
        $response->assertJsonPath('data.blocking_issue_count', 0);

        $quoteId = (string) $response->json('data.id');
        $this->assertDatabaseHas('quote_submissions', [
            'id' => $quoteId,
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'status' => 'ready',
            'uploaded_by' => $user->id,
        ]);
    }

    public function test_quote_submission_upload_without_rfq_lines_does_not_become_ready(): void
    {
        Storage::fake('local');

        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-2002',
            'title' => 'Upload RFQ Without Lines',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        $response = $this->withHeaders($this->authHeaders((string) $user->tenant_id, (string) $user->id))
            ->post('/api/v1/quote-submissions/upload', [
                'rfq_id' => $rfq->id,
                'vendor_id' => (string) Str::ulid(),
                'vendor_name' => 'Upload Vendor',
                'file' => UploadedFile::fake()->create('quote.txt', 12, 'text/plain'),
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'failed');

        $quoteId = (string) $response->json('data.id');
        $this->assertDatabaseHas('quote_submissions', [
            'id' => $quoteId,
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'status' => 'failed',
        ]);
    }

    public function test_quote_submission_show_returns_404_for_other_tenant(): void
    {
        $owner = $this->createUser();
        $otherTenantUser = $this->createUser();

        $rfq = Rfq::query()->create([
            'tenant_id' => $owner->tenant_id,
            'rfq_number' => 'RFQ-2003',
            'title' => 'Tenant scoped RFQ',
            'owner_id' => $owner->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $owner->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Tenant Vendor',
            'status' => 'uploaded',
            'file_path' => 'quote-submissions/tenant-vendor.txt',
            'file_type' => 'text/plain',
            'submitted_at' => now(),
            'uploaded_by' => $owner->id,
            'confidence' => 100.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $response = $this->getJson(
            '/api/v1/quote-submissions/' . $quote->id,
            $this->authHeaders((string) $otherTenantUser->tenant_id, (string) $otherTenantUser->id),
        );

        $response->assertNotFound();
    }

    public function test_quote_submission_index_filters_by_rfq_for_current_tenant(): void
    {
        $user = $this->createUser();

        $rfqA = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-2004',
            'title' => 'List RFQ A',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        $rfqB = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-2005',
            'title' => 'List RFQ B',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfqA->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor A',
            'status' => 'uploaded',
            'file_path' => 'quote-submissions/vendor-a.txt',
            'file_type' => 'text/plain',
            'submitted_at' => now(),
            'confidence' => 100.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfqB->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor B',
            'status' => 'needs_review',
            'file_path' => 'quote-submissions/vendor-b.txt',
            'file_type' => 'text/plain',
            'submitted_at' => now(),
            'confidence' => 70.0,
            'line_items_count' => 1,
            'warnings_count' => 1,
            'errors_count' => 0,
        ]);

        $response = $this->getJson(
            '/api/v1/quote-submissions?rfq_id=' . urlencode((string) $rfqA->id),
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.rfq_id', $rfqA->id);
        $response->assertJsonPath('data.0.blocking_issue_count', 0);
    }

    public function test_rfq_overview_returns_quote_readiness_buckets(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-2005',
            'title' => 'Overview Buckets RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        foreach (['uploaded', 'needs_review', 'ready'] as $status) {
            QuoteSubmission::query()->create([
                'tenant_id' => $user->tenant_id,
                'rfq_id' => $rfq->id,
                'vendor_id' => (string) Str::ulid(),
                'vendor_name' => 'Vendor ' . $status,
                'status' => $status,
                'file_path' => 'quote-submissions/' . $status . '.txt',
                'file_type' => 'text/plain',
                'submitted_at' => now(),
                'confidence' => 100.0,
                'line_items_count' => 0,
                'warnings_count' => 0,
                'errors_count' => 0,
            ]);
        }

        $response = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/overview',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.normalization.uploaded_count', 1);
        $response->assertJsonPath('data.normalization.needs_review_count', 1);
        $response->assertJsonPath('data.normalization.ready_count', 1);
        $response->assertJsonPath('data.normalization.total_quotes', 3);
    }
}
