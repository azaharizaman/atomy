<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $response->assertJsonValidationErrors(['rfq_id', 'vendor_id', 'file']);
    }

    public function test_quote_submission_status_rejects_unsupported_transition(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-1001',
            'title' => 'Workflow RFQ',
            'owner_id' => $user->id,
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
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_quote_submission_status_maps_legacy_accepted_to_ready(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-1002',
            'title' => 'Workflow RFQ',
            'owner_id' => $user->id,
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

    public function test_rfq_overview_counts_ready_quotes_in_progress(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-1003',
            'title' => 'Workflow RFQ',
            'owner_id' => $user->id,
            'status' => 'published',
        ]);

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Vendor One',
            'status' => 'ready',
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
        $response->assertJsonPath('data.normalization.accepted_count', 1);
        $response->assertJsonPath('data.normalization.progress_pct', 100);
    }

    public function test_final_comparison_run_returns_ready_for_approval_state(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-1004',
            'title' => 'Workflow RFQ',
            'owner_id' => $user->id,
            'status' => 'published',
        ]);

        $response = $this->postJson(
            '/api/v1/comparison-runs/final',
            ['rfq_id' => $rfq->id],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'ready_for_approval');
        $response->assertJsonPath('data.rfq_id', $rfq->id);
    }

    public function test_final_comparison_run_rejects_non_ulid_rfq_id(): void
    {
        $user = $this->createUser();

        $response = $this->postJson(
            '/api/v1/comparison-runs/final',
            ['rfq_id' => 'rfq-123'],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rfq_id']);
    }
}
