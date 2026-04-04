<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class RfqLifecycleMutationTest extends ApiTestCase
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
            'email' => 'buyer-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Lifecycle User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    private function createRfq(User $user, array $overrides = []): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create(array_merge([
            'tenant_id' => (string) $user->tenant_id,
            'rfq_number' => 'RFQ-' . date('Y') . '-' . random_int(1000, 9999),
            'title' => 'Lifecycle RFQ',
            'description' => 'Original description',
            'status' => 'draft',
            'owner_id' => $user->id,
            'estimated_value' => 1000,
            'savings_percentage' => 10,
            'submission_deadline' => now()->addDays(10),
            'closing_date' => now()->addDays(12),
            'payment_terms' => 'Net 30',
            'evaluation_method' => 'weighted_score',
        ], $overrides));

        return $rfq;
    }

    public function test_duplicate_persists_new_rfq_and_copies_line_items(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user, ['status' => 'published']);

        RfqLineItem::query()->create([
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'description' => 'Servers',
            'quantity' => 2,
            'uom' => 'EA',
            'unit_price' => 500,
            'currency' => 'USD',
            'specifications' => 'Rack units',
            'sort_order' => 1,
        ]);

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/duplicate',
            [],
            $this->authHeaders($tenantId, (string) $user->id, 'dup-key-1'),
        );

        $response->assertCreated();
        $newId = (string) $response->json('data.id');
        $this->assertNotSame((string) $rfq->id, $newId);
        $response->assertJsonPath('data.status', 'draft');
        $response->assertJsonPath('data.title', 'Lifecycle RFQ');

        $this->assertDatabaseHas('rfqs', [
            'id' => $newId,
            'tenant_id' => $tenantId,
            'status' => 'draft',
        ]);
        $this->assertDatabaseCount('rfqs', 2);
        $this->assertSame(1, RfqLineItem::query()->where('rfq_id', $rfq->id)->count());
        
        $this->assertDatabaseHas('rfq_line_items', [
            'rfq_id' => $newId,
            'tenant_id' => $tenantId,
            'description' => 'Servers',
            'quantity' => 2,
            'uom' => 'EA',
            'unit_price' => 500,
            'currency' => 'USD',
            'specifications' => 'Rack units',
            'sort_order' => 1,
        ]);
    }

    public function test_duplicate_returns_404_for_wrong_tenant(): void
    {
        $owner = $this->createUser((string) Str::ulid());
        $other = $this->createUser((string) Str::ulid());
        $rfq = $this->createRfq($owner, ['status' => 'published']);

        $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/duplicate',
            [],
            $this->authHeaders((string) $other->tenant_id, (string) $other->id, 'dup-key-2'),
        )->assertStatus(404);
    }

    public function test_save_draft_persists_editable_fields(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);

        $response = $this->putJson(
            '/api/v1/rfqs/' . $rfq->id . '/draft',
            [
                'title' => 'Updated draft title',
                'description' => 'Updated description',
                'payment_terms' => 'Net 45',
                'estimated_value' => 4200.55,
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.title', 'Updated draft title');
        $response->assertJsonPath('data.payment_terms', 'Net 45');
        $this->assertDatabaseHas('rfqs', [
            'id' => $rfq->id,
            'title' => 'Updated draft title',
            'description' => 'Updated description',
            'payment_terms' => 'Net 45',
        ]);
    }

    public function test_save_draft_can_clear_nullable_fields_with_explicit_nulls(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user, [
            'description' => 'Needs clearing',
            'payment_terms' => 'Net 45',
            'expected_award_at' => now()->addDays(14),
            'technical_review_due_at' => now()->addDays(8),
            'financial_review_due_at' => now()->addDays(9),
        ]);

        $response = $this->putJson(
            '/api/v1/rfqs/' . $rfq->id . '/draft',
            [
                'description' => null,
                'payment_terms' => null,
                'expected_award_at' => null,
                'technical_review_due_at' => null,
                'financial_review_due_at' => null,
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.description', null);
        $response->assertJsonPath('data.payment_terms', null);

        $this->assertDatabaseHas('rfqs', [
            'id' => $rfq->id,
            'description' => null,
            'payment_terms' => null,
            'expected_award_at' => null,
            'technical_review_due_at' => null,
            'financial_review_due_at' => null,
        ]);
    }

    public function test_save_draft_rejects_non_draft_rfqs(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user, ['status' => 'published']);

        $response = $this->putJson(
            '/api/v1/rfqs/' . $rfq->id . '/draft',
            ['title' => 'Should fail'],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
    }

    public function test_bulk_action_closes_selected_rfqs_and_reports_affected_count(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfqA = $this->createRfq($user, ['status' => 'published']);
        $rfqB = $this->createRfq($user, ['status' => 'published', 'rfq_number' => 'RFQ-' . date('Y') . '-9998']);

        $response = $this->postJson(
            '/api/v1/rfqs/bulk-action',
            [
                'action' => 'close',
                'rfq_ids' => [(string) $rfqA->id, (string) $rfqB->id],
            ],
            $this->authHeaders($tenantId, (string) $user->id, 'bulk-key-1'),
        );

        $response->assertOk();
        $response->assertJsonPath('data.affected', 2);
        $response->assertJsonPath('data.status', 'closed');
        $this->assertDatabaseHas('rfqs', ['id' => $rfqA->id, 'status' => 'closed']);
        $this->assertDatabaseHas('rfqs', ['id' => $rfqB->id, 'status' => 'closed']);
    }

    public function test_status_transition_uses_real_lifecycle_policy(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user, ['status' => 'draft']);

        $this->patchJson(
            '/api/v1/rfqs/' . $rfq->id . '/status',
            ['status' => 'published'],
            $this->authHeaders($tenantId, (string) $user->id),
        )->assertOk()->assertJsonPath('data.status', 'published');

        $invalid = $this->patchJson(
            '/api/v1/rfqs/' . $rfq->id . '/status',
            ['status' => 'draft'],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $invalid->assertStatus(422);
        $invalid->assertJsonPath('error', 'Validation failed');
    }
}
