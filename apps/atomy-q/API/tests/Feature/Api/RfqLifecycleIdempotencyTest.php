<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class RfqLifecycleIdempotencyTest extends ApiTestCase
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
            'email' => 'idem-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Idempotency User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    private function createRfq(User $user, string $status = 'published'): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create([
            'tenant_id' => (string) $user->tenant_id,
            'rfq_number' => 'RFQ-' . date('Y') . '-' . random_int(1000, 9999),
            'title' => 'Idempotent RFQ',
            'status' => $status,
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(10),
        ]);

        return $rfq;
    }

    public function test_duplicate_replays_same_created_rfq_for_same_idempotency_key(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);
        $headers = $this->authHeaders($tenantId, (string) $user->id, 'duplicate-replay-key');

        $first = $this->postJson('/api/v1/rfqs/' . $rfq->id . '/duplicate', [], $headers);
        $second = $this->postJson('/api/v1/rfqs/' . $rfq->id . '/duplicate', [], $headers);

        $first->assertCreated();
        $second->assertCreated();
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('rfqs', 2);
    }

    public function test_bulk_action_replays_same_result_for_same_idempotency_key(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfqA = $this->createRfq($user);
        $rfqB = $this->createRfq($user);
        $headers = $this->authHeaders($tenantId, (string) $user->id, 'bulk-replay-key');
        $payload = [
            'action' => 'close',
            'rfq_ids' => [(string) $rfqA->id, (string) $rfqB->id],
        ];

        $first = $this->postJson('/api/v1/rfqs/bulk-action', $payload, $headers);
        $second = $this->postJson('/api/v1/rfqs/bulk-action', $payload, $headers);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json(), $second->json());
        $this->assertDatabaseHas('rfqs', ['id' => $rfqA->id, 'status' => 'closed']);
        $this->assertDatabaseHas('rfqs', ['id' => $rfqB->id, 'status' => 'closed']);
    }
}
