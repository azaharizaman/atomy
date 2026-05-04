<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class RfqCountsTest extends ApiTestCase
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

    public function test_counts_are_tenant_scoped(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();

        /** @var User $userA */
        $userA = User::query()->create([
            'tenant_id' => $tenantA,
            'email' => 'a-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'A',
            'password_hash' => Hash::make('p'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        /** @var User $userB */
        $userB = User::query()->create([
            'tenant_id' => $tenantB,
            'email' => 'b-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'B',
            'password_hash' => Hash::make('p'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        Rfq::query()->create([
            'tenant_id' => $tenantA,
            'rfq_number' => 'RFQ-A1',
            'title' => 'A draft',
            'owner_id' => $userA->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);
        Rfq::query()->create([
            'tenant_id' => $tenantA,
            'rfq_number' => 'RFQ-A2',
            'title' => 'A pub',
            'owner_id' => $userA->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);
        Rfq::query()->create([
            'tenant_id' => $tenantB,
            'rfq_number' => 'RFQ-B1',
            'title' => 'B pub',
            'owner_id' => $userB->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $this->getJson('/api/v1/rfqs/counts', $this->authHeaders($tenantA, (string) $userA->id))
            ->assertOk()
            ->assertJsonPath('data.draft', 1)
            ->assertJsonPath('data.published', 1)
            ->assertJsonPath('data.active', 1);

        $this->getJson('/api/v1/rfqs/counts', $this->authHeaders($tenantB, (string) $userB->id))
            ->assertOk()
            ->assertJsonPath('data.draft', 0)
            ->assertJsonPath('data.published', 1);
    }

    public function test_active_filter_matches_active_nav_count_bucket(): void
    {
        $tenantId = (string) Str::ulid();

        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'rfq-active-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'RFQ Owner',
            'password_hash' => Hash::make('p'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-ACTIVE-PUBLISHED',
            'title' => 'Published live RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);
        Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-ACTIVE-LEGACY',
            'title' => 'Active live RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'active',
        ]);
        Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-ACTIVE-DRAFT',
            'title' => 'Draft RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        $headers = $this->authHeaders($tenantId, (string) $user->id);

        $this->getJson('/api/v1/rfqs/counts', $headers)
            ->assertOk()
            ->assertJsonPath('data.active', 2);

        $this->getJson('/api/v1/rfqs?status=active', $headers)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_counts_returns_401_without_auth(): void
    {
        $this->getJson('/api/v1/rfqs/counts')->assertStatus(401);
    }
}
