<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Rfq;
use App\Models\User;
use App\Models\VendorInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Nexus\Laravel\Notifier\Jobs\SendEmailNotificationJob;

final class RfqInvitationReminderTest extends ApiTestCase
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
            'email' => 'invite-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Invitation User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    public function test_remind_updates_metadata_with_tenant_scope(): void
    {
        Queue::fake();

        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-' . date('Y') . '-7001',
            'title' => 'Reminder RFQ',
            'status' => 'published',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(7),
        ]);

        /** @var VendorInvitation $invitation */
        $invitation = VendorInvitation::query()->create([
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'vendor_email' => 'vendor@example.com',
            'vendor_name' => 'Vendor Co',
            'status' => 'pending',
            'invited_at' => now()->subDay(),
        ]);

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/invitations/' . $invitation->id . '/remind',
            [],
            $this->authHeaders($tenantId, (string) $user->id, 'remind-key-1'),
        );

        $response->assertOk();
        $response->assertJsonPath('data.id', (string) $invitation->id);
        $this->assertNotNull(VendorInvitation::query()->findOrFail($invitation->id)->reminded_at);
        Queue::assertPushed(SendEmailNotificationJob::class);
    }

    public function test_remind_returns_404_for_cross_tenant_access(): void
    {
        $owner = $this->createUser((string) Str::ulid());
        $other = $this->createUser((string) Str::ulid());

        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create([
            'tenant_id' => (string) $owner->tenant_id,
            'rfq_number' => 'RFQ-' . date('Y') . '-7002',
            'title' => 'Tenant A RFQ',
            'status' => 'published',
            'owner_id' => $owner->id,
            'submission_deadline' => now()->addDays(7),
        ]);

        /** @var VendorInvitation $invitation */
        $invitation = VendorInvitation::query()->create([
            'tenant_id' => (string) $owner->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_email' => 'vendor@example.com',
            'vendor_name' => 'Vendor Co',
            'status' => 'pending',
            'invited_at' => now()->subDay(),
        ]);

        $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/invitations/' . $invitation->id . '/remind',
            [],
            $this->authHeaders((string) $other->tenant_id, (string) $other->id, 'remind-key-2'),
        )->assertStatus(404);

        $this->assertNull($invitation->fresh()->reminded_at);
    }
}
