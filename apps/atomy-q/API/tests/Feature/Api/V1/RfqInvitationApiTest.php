<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Rfq;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class RfqInvitationApiTest extends ApiTestCase
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
        $this->createTenant($tenantId);

        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'invite-' . Str::lower($tenantId) . '@example.com',
            'name' => 'Invitation User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => '2026-04-22 00:00:00',
        ]);

        return $user;
    }

    private function createTenant(string $tenantId): void
    {
        if (Tenant::query()->where('id', $tenantId)->exists()) {
            return;
        }

        $tenant = new Tenant();
        $tenant->id = $tenantId;
        $tenant->code = 'T' . strtolower($tenantId);
        $tenant->name = 'Tenant ' . $tenantId;
        $tenant->email = strtolower($tenantId) . '@tenant.example.com';
        $tenant->status = 'active';
        $tenant->save();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createVendor(string $tenantId, array $overrides = []): Vendor
    {
        /** @var Vendor $vendor */
        $vendor = Vendor::query()->create(array_merge([
            'tenant_id' => $tenantId,
            'name' => 'Acme Holdings Sdn Bhd',
            'trading_name' => 'Acme Trading',
            'registration_number' => '201901234567',
            'country_code' => 'MY',
            'email' => 'acme@example.com',
            'phone' => '+60123456789',
            'status' => 'approved',
            'legal_name' => 'Acme Holdings Sdn Bhd',
            'display_name' => 'Acme Trading',
            'country_of_registration' => 'MY',
            'primary_contact_name' => 'Amina Zain',
            'primary_contact_email' => 'acme@example.com',
            'primary_contact_phone' => '+60123456789',
        ], $overrides));

        return $vendor;
    }

    private function createRfq(User $user): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create([
            'tenant_id' => (string) $user->tenant_id,
            'rfq_number' => 'RFQ-2026-INV-0001',
            'title' => 'Invitation RFQ',
            'status' => 'published',
            'owner_id' => $user->id,
            'submission_deadline' => '2026-05-02 00:00:00',
        ]);

        return $rfq;
    }

    public function testItRequiresSelectedVendorForInvitationCreation(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);
        $vendor = $this->createVendor($tenantId, [
            'display_name' => 'Alpha Trading',
            'primary_contact_email' => 'alpha@example.com',
            'email' => 'alpha@example.com',
        ]);

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/invitations',
            [
                'vendor_id' => (string) $vendor->id,
            ],
            $this->authHeaders($tenantId, (string) $user->id, 'invite-key-1'),
        );

        $response->assertUnprocessable();

        $this->putJson(
            '/api/v1/rfqs/' . $rfq->id . '/selected-vendors',
            [
                'vendor_ids' => [(string) $vendor->id],
            ],
            $this->authHeaders($tenantId, (string) $user->id, 'selection-key-1'),
        )->assertOk();

        $selected = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/invitations',
            [
                'vendor_id' => (string) $vendor->id,
            ],
            $this->authHeaders($tenantId, (string) $user->id, 'invite-key-2'),
        );

        $selected->assertCreated();
        $selected->assertJsonPath('data.vendor_id', (string) $vendor->id);
        $selected->assertJsonPath('data.vendor_email', 'alpha@example.com');
        $selected->assertJsonPath('data.vendor_name', 'Alpha Trading');
    }

    public function testItRejectsApprovedButUnselectedVendors(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);
        $vendor = $this->createVendor($tenantId, ['display_name' => 'Bravo Supplies']);

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/invitations',
            [
                'vendor_id' => (string) $vendor->id,
            ],
            $this->authHeaders($tenantId, (string) $user->id, 'invite-key-3'),
        );

        $response->assertUnprocessable();
    }
}
