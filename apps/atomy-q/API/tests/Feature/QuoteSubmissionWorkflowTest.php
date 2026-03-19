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
            $this->authHeaders((string) $user->id, (string) $user->tenant_id),
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
            $this->authHeaders((string) $user->id, (string) $user->tenant_id),
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_final_comparison_run_returns_ready_for_approval_state(): void
    {
        $user = $this->createUser();

        $response = $this->postJson(
            '/api/v1/comparison-runs/final',
            ['rfq_id' => 'rfq-123'],
            $this->authHeaders((string) $user->id, (string) $user->tenant_id),
        );

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'ready_for_approval');
    }
}
