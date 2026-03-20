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

final class RfqOverviewActivityTest extends ApiTestCase
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
            'email' => 'user-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Activity Test User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    public function test_activity_returns_404_for_cross_tenant(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $userA = $this->createUser($tenantA);
        $userB = $this->createUser($tenantB);

        $rfq = Rfq::query()->create([
            'tenant_id' => $tenantA,
            'rfq_number' => 'RFQ-ACT-0001',
            'title' => 'Tenant A RFQ',
            'owner_id' => $userA->id,
            'status' => 'active',
        ]);

        $response = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/activity',
            $this->authHeaders($tenantB, (string) $userB->id),
        );

        $response->assertStatus(404);
        $response->assertJsonPath('message', 'RFQ not found');
    }

    public function test_activity_returns_200_with_events_for_owning_tenant(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        $rfq = Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-ACT-0002',
            'title' => 'With quote activity',
            'owner_id' => $user->id,
            'status' => 'active',
        ]);

        QuoteSubmission::query()->create([
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'vendor_id' => (string) Str::ulid(),
            'vendor_name' => 'Acme Supplies',
            'status' => 'uploaded',
            'submitted_at' => now(),
            'confidence' => 100.0,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $response = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/activity?limit=10',
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertStatus(200);
        $response->assertJsonPath('meta.limit', 10);
        $response->assertJsonPath('meta.rfq_id', $rfq->id);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'type', 'actor', 'action', 'timestamp'],
            ],
            'meta' => ['limit', 'rfq_id'],
        ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertSame('quote', $data[0]['type']);
        $this->assertStringContainsString('Acme Supplies', (string) $data[0]['actor']);
    }

    public function test_activity_clamps_limit_between_1_and_50(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        $rfq = Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-ACT-0003',
            'title' => 'Limit clamp',
            'owner_id' => $user->id,
            'status' => 'draft',
        ]);

        $low = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/activity?limit=0',
            $this->authHeaders($tenantId, (string) $user->id),
        );
        $low->assertOk();
        $low->assertJsonPath('meta.limit', 1);

        $high = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/activity?limit=999',
            $this->authHeaders($tenantId, (string) $user->id),
        );
        $high->assertOk();
        $high->assertJsonPath('meta.limit', 50);
    }

    public function test_overview_is_tenant_isolated_returns_404(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $userA = $this->createUser($tenantA);
        $userB = $this->createUser($tenantB);

        $rfq = Rfq::query()->create([
            'tenant_id' => $tenantA,
            'rfq_number' => 'RFQ-OV-ISO-1',
            'title' => 'Tenant A overview target',
            'owner_id' => $userA->id,
            'status' => 'active',
        ]);

        $response = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/overview',
            $this->authHeaders($tenantB, (string) $userB->id),
        );

        $response->assertStatus(404);
    }

    public function test_overview_includes_blueprint_kpi_aliases(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-ACT-0004',
            'title' => 'Overview KPIs',
            'owner_id' => $user->id,
            'status' => 'active',
        ]);

        $rfq = Rfq::query()->where('rfq_number', 'RFQ-ACT-0004')->first();
        $this->assertNotNull($rfq);

        $response = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/overview',
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.expectedQuotes', 0);
        $response->assertJsonPath('data.normalizationProgress', 0);
        $response->assertJsonPath('data.latestComparisonRun', null);
        $response->assertJsonPath('data.approvalStatus.overall', 'none');
        $response->assertJsonStructure([
            'data' => [
                'expected_quotes',
                'expectedQuotes',
                'normalizationProgress',
                'latestComparisonRun',
                'approvalStatus' => ['overall', 'pending_count', 'approved_count', 'rejected_count'],
                'activity',
            ],
        ]);
    }

    public function test_overview_and_show_include_schedule_milestone_dates(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $expectedAward = now()->addDays(30);
        $technicalDue = now()->addDays(10);
        $financialDue = now()->addDays(20);

        $rfq = Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-SCHED-MS-1',
            'title' => 'Schedule milestones',
            'owner_id' => $user->id,
            'status' => 'published',
            'expected_award_at' => $expectedAward,
            'technical_review_due_at' => $technicalDue,
            'financial_review_due_at' => $financialDue,
        ]);

        $overview = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/overview',
            $this->authHeaders($tenantId, (string) $user->id),
        );
        $overview->assertOk();
        $overview->assertJsonPath('data.rfq.expected_award_at', $expectedAward->toAtomString());
        $overview->assertJsonPath('data.rfq.technical_review_due_at', $technicalDue->toAtomString());
        $overview->assertJsonPath('data.rfq.financial_review_due_at', $financialDue->toAtomString());

        $show = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id,
            $this->authHeaders($tenantId, (string) $user->id),
        );
        $show->assertOk();
        $show->assertJsonPath('data.expected_award_at', $expectedAward->toAtomString());
        $show->assertJsonPath('data.technical_review_due_at', $technicalDue->toAtomString());
        $show->assertJsonPath('data.financial_review_due_at', $financialDue->toAtomString());

        $this->putJson(
            '/api/v1/rfqs/' . $rfq->id,
            [
                'expected_award_at' => null,
                'technical_review_due_at' => null,
                'financial_review_due_at' => null,
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        )->assertOk();

        $cleared = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/overview',
            $this->authHeaders($tenantId, (string) $user->id),
        );
        $cleared->assertOk();
        $cleared->assertJsonPath('data.rfq.expected_award_at', null);
        $cleared->assertJsonPath('data.rfq.technical_review_due_at', null);
        $cleared->assertJsonPath('data.rfq.financial_review_due_at', null);
    }

    public function test_show_and_overview_include_description(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        $rfq = Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-DESC-PAYLOAD',
            'title' => 'Description payload',
            'owner_id' => $user->id,
            'description' => 'SOW and evaluation notes.',
        ]);

        $headers = $this->authHeaders($tenantId, (string) $user->id);

        $this->getJson('/api/v1/rfqs/' . $rfq->id, $headers)
            ->assertOk()
            ->assertJsonPath('data.description', 'SOW and evaluation notes.');

        $this->getJson('/api/v1/rfqs/' . $rfq->id . '/overview', $headers)
            ->assertOk()
            ->assertJsonPath('data.rfq.description', 'SOW and evaluation notes.');
    }
}
