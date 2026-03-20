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
}
