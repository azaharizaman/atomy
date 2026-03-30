<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => (string) Str::ulid(),
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
     * @return array{0: User, 1: Rfq, 2: ComparisonRun, 3: Award}
     */
    private function seedAward(User $user): array
    {
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-AWARD-' . Str::lower((string) Str::ulid()),
            'title' => 'Award RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'closed',
        ]);

        RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Line',
            'quantity' => 1,
            'uom' => 'ea',
            'unit_price' => 10,
            'currency' => 'USD',
            'sort_order' => 0,
        ]);

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Winner Vendor',
            'status' => 'ready',
            'submitted_at' => now(),
            'confidence' => 95.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $run = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison',
            'description' => null,
            'idempotency_key' => null,
            'is_preview' => false,
            'created_by' => $user->id,
            'request_payload' => [],
            'matrix_payload' => [],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => ['snapshot' => ['normalized_lines' => [['source_line_id' => 'x']]]],
            'readiness_payload' => [],
            'status' => 'final',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
        ]);

        /** @var Award $award */
        $award = Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $run->id,
            'vendor_id' => (string) Str::ulid(),
            'status' => 'pending',
            'amount' => '1000.00',
            'currency' => 'USD',
            'split_details' => [],
            'protest_id' => null,
            'signoff_at' => null,
            'signed_off_by' => null,
        ]);

        return [$user, $rfq, $run, $award];
    }

    public function test_index_returns_live_award_rows_for_rfq(): void
    {
        [$user, $rfq, , $award] = $this->seedAward($this->createUser());

        $response = $this->getJson(
            '/api/v1/awards?rfqId=' . $rfq->id,
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $award->id);
        $response->assertJsonPath('data.0.rfq_id', $rfq->id);
    }

    public function test_signoff_updates_award_status_and_timestamps(): void
    {
        [$user, , , $award] = $this->seedAward($this->createUser());

        $response = $this->postJson(
            '/api/v1/awards/' . $award->id . '/signoff',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'signed_off');

        $award->refresh();
        self::assertSame('signed_off', $award->status);
        self::assertNotNull($award->signoff_at);
        self::assertSame($user->id, $award->signed_off_by);
    }

    public function test_debrief_returns_live_response_for_matching_vendor(): void
    {
        [$user, , , $award] = $this->seedAward($this->createUser());

        $response = $this->postJson(
            '/api/v1/awards/' . $award->id . '/debrief/' . $award->vendor_id,
            ['message' => 'Thanks for participating.'],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.award_id', $award->id);
        $response->assertJsonPath('data.vendor_id', $award->vendor_id);
    }
}
