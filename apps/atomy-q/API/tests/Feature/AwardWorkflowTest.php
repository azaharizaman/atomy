<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ComparisonRun;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\User;
use App\Models\VendorInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class AwardWorkflowTest extends ApiTestCase
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

    private function createUser(string $tenantId): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'award-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Award User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    /**
     * @return array{0: User, 1: Rfq, 2: ComparisonRun, 3: VendorInvitation, 4: QuoteSubmission}
     */
    private function seedAwardContext(User $user): array
    {
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-AWARD-' . Str::lower((string) Str::ulid()),
            'title' => 'Award RFQ',
            'owner_id' => $user->id,
            'estimated_value' => 1000,
            'savings_percentage' => 10,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $comparisonRun = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison',
            'description' => null,
            'idempotency_key' => null,
            'is_preview' => false,
            'created_by' => $user->id,
            'request_payload' => ['rfq_id' => $rfq->id],
            'matrix_payload' => [],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => [
                'snapshot' => [
                    'normalized_lines' => [
                        ['quote_submission_id' => 'line-1'],
                    ],
                    'rfq_version' => 1,
                    'resolutions' => [],
                    'currency_meta' => [],
                ],
            ],
            'readiness_payload' => [
                'all_ready' => true,
                'submission_count' => 1,
            ],
            'status' => 'final',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
        ]);

        $vendorId = (string) Str::ulid();

        $invitation = VendorInvitation::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendorId,
            'vendor_email' => 'winner@example.com',
            'vendor_name' => 'Winner Vendor',
            'status' => 'accepted',
            'invited_at' => now(),
            'responded_at' => now(),
            'channel' => 'email',
        ]);

        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendorId,
            'vendor_name' => 'Winner Vendor',
            'uploaded_by' => $user->id,
            'file_path' => '/tmp/winner.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => 'winner.pdf',
            'status' => 'ready',
            'submitted_at' => now(),
            'confidence' => 100,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
            'retry_count' => 0,
        ]);

        return [$user, $rfq, $comparisonRun, $invitation, $quote];
    }

    public function test_store_auto_creates_signed_off_award_from_workflow_selection(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        [, $rfq, $comparisonRun, $invitation] = $this->seedAwardContext($user);

        $response = $this->postJson(
            '/api/v1/awards',
            [
                'rfq_id' => $rfq->id,
                'comparison_run_id' => $comparisonRun->id,
                'vendor_id' => $invitation->vendor_id,
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertCreated();
        $response->assertJsonPath('data.rfq_id', $rfq->id);
        $response->assertJsonPath('data.comparison_run_id', $comparisonRun->id);
        $response->assertJsonPath('data.vendor_id', $invitation->vendor_id);
        $response->assertJsonPath('data.vendor_name', $invitation->vendor_name);
        $response->assertJsonPath('data.status', 'signed_off');
        $response->assertJsonPath('data.amount', 900);
        $this->assertDatabaseHas('awards', [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'vendor_id' => $invitation->vendor_id,
            'status' => 'signed_off',
        ]);
    }

    public function test_show_returns_404_for_cross_tenant_award(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $userA = $this->createUser($tenantA);
        $userB = $this->createUser($tenantB);
        [, $rfq, $comparisonRun, $invitation] = $this->seedAwardContext($userA);

        $awardResponse = $this->postJson(
            '/api/v1/awards',
            [
                'rfq_id' => $rfq->id,
                'comparison_run_id' => $comparisonRun->id,
                'vendor_id' => $invitation->vendor_id,
            ],
            $this->authHeaders($tenantA, (string) $userA->id),
        );

        $awardId = (string) $awardResponse->json('data.id');

        $response = $this->getJson(
            '/api/v1/awards/' . $awardId,
            $this->authHeaders($tenantB, (string) $userB->id),
        );

        $response->assertStatus(404);
    }
}

