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
            'status' => 'draft',
        ]);
        Rfq::query()->create([
            'tenant_id' => $tenantA,
            'rfq_number' => 'RFQ-A2',
            'title' => 'A pub',
            'owner_id' => $userA->id,
            'status' => 'published',
        ]);
        Rfq::query()->create([
            'tenant_id' => $tenantB,
            'rfq_number' => 'RFQ-B1',
            'title' => 'B pub',
            'owner_id' => $userB->id,
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

    public function test_counts_returns_401_without_auth(): void
    {
        $this->getJson('/api/v1/rfqs/counts')->assertStatus(401);
    }
}
