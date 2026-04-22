<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Rfq;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class RequisitionVendorSelectionApiTest extends ApiTestCase
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
            'email' => 'buyer-' . Str::lower($tenantId) . '@example.com',
            'name' => 'Selection User',
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
            'rfq_number' => 'RFQ-2026-SEL-0001',
            'title' => 'Selection RFQ',
            'status' => 'draft',
            'owner_id' => $user->id,
            'submission_deadline' => '2026-05-02 00:00:00',
        ]);

        return $rfq;
    }

    public function testItReplacesSelectedVendorsAndListsTheCurrentSelection(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);
        $vendorA = $this->createVendor($tenantId, [
            'display_name' => 'Alpha Trading',
            'primary_contact_email' => 'alpha@example.com',
            'email' => 'alpha@example.com',
        ]);
        $vendorB = $this->createVendor($tenantId, [
            'display_name' => 'Bravo Supplies',
            'primary_contact_email' => 'bravo@example.com',
            'email' => 'bravo@example.com',
        ]);
        $vendorC = $this->createVendor($tenantId, [
            'display_name' => 'Charlie Industrial',
            'primary_contact_email' => 'charlie@example.com',
            'email' => 'charlie@example.com',
        ]);

        $response = $this->putJson(
            '/api/v1/rfqs/' . $rfq->id . '/selected-vendors',
            [
                'vendor_ids' => [(string) $vendorA->id, (string) $vendorB->id],
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.rfq_id', (string) $rfq->id);
        $response->assertJsonPath('data.0.vendor_id', (string) $vendorA->id);
        $response->assertJsonPath('data.0.vendor_name', 'Alpha Trading');
        $response->assertJsonPath('data.0.vendor_email', 'alpha@example.com');
        $response->assertJsonPath('data.0.status', 'approved');
        $response->assertJsonPath('data.0.selected_by_user_id', (string) $user->id);
        $this->assertNotNull($response->json('data.0.selected_at'));

        $this->assertDatabaseHas('requisition_selected_vendors', [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendorA->id,
            'selected_by_user_id' => (string) $user->id,
        ]);
        $this->assertDatabaseHas('requisition_selected_vendors', [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendorB->id,
            'selected_by_user_id' => (string) $user->id,
        ]);

        $this->assertCount(
            2,
            DB::table('requisition_selected_vendors')
                ->where('tenant_id', $tenantId)
                ->where('rfq_id', $rfq->id)
                ->get(),
        );

        $list = $this->getJson(
            '/api/v1/rfqs/' . $rfq->id . '/selected-vendors',
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $list->assertOk();
        $list->assertJsonCount(2, 'data');
        $list->assertJsonPath('data.0.vendor_name', 'Alpha Trading');
        $list->assertJsonPath('data.1.vendor_name', 'Bravo Supplies');
        $list->assertJsonMissing(['vendor_name' => 'Charlie Industrial']);

        $replace = $this->putJson(
            '/api/v1/rfqs/' . $rfq->id . '/selected-vendors',
            [
                'vendor_ids' => [(string) $vendorC->id],
            ],
            $this->authHeaders($tenantId, (string) $user->id, 'selection-key-2'),
        );

        $replace->assertOk();
        $replace->assertJsonCount(1, 'data');
        $replace->assertJsonPath('data.0.vendor_name', 'Charlie Industrial');
        $this->assertDatabaseMissing('requisition_selected_vendors', [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendorA->id,
        ]);
        $this->assertDatabaseMissing('requisition_selected_vendors', [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendorB->id,
        ]);
    }

    public function testItReturns404ForCrossTenantVendorSelectionAttempts(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $userA = $this->createUser($tenantA);
        $userB = $this->createUser($tenantB);
        $rfq = $this->createRfq($userA);
        $vendor = $this->createVendor($tenantA);

        $this->putJson(
            '/api/v1/rfqs/' . $rfq->id . '/selected-vendors',
            ['vendor_ids' => [(string) $vendor->id]],
            $this->authHeaders($tenantB, (string) $userB->id),
        )->assertNotFound();
    }

    public function testItRejectsNonApprovedVendors(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);
        $vendor = $this->createVendor($tenantId, ['status' => 'draft']);

        $response = $this->putJson(
            '/api/v1/rfqs/' . $rfq->id . '/selected-vendors',
            ['vendor_ids' => [(string) $vendor->id]],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertUnprocessable();
    }

    public function testItRejectsDuplicateVendorIds(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);
        $vendor = $this->createVendor($tenantId);

        $response = $this->putJson(
            '/api/v1/rfqs/' . $rfq->id . '/selected-vendors',
            ['vendor_ids' => [(string) $vendor->id, (string) $vendor->id]],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertUnprocessable();
    }
}
