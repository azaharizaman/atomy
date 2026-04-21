<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\ApiTestCase;

final class VendorApiTest extends ApiTestCase
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
            'email' => 'vendor-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Vendor User',
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
            'tax_id' => 'TAX-201901234567',
            'country_code' => 'MY',
            'email' => 'amina@example.com',
            'phone' => '+60123456789',
            'legal_name' => 'Acme Holdings Sdn Bhd',
            'display_name' => 'Acme Trading',
            'country_of_registration' => 'MY',
            'status' => 'draft',
            'primary_contact_name' => 'Amina Zain',
            'primary_contact_email' => 'amina@example.com',
            'primary_contact_phone' => '+60123456789',
        ], $overrides));

        return $vendor;
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function crossTenantEndpointProvider(): array
    {
        return [
            'show' => ['show'],
            'patch' => ['patch'],
            'status' => ['status'],
        ];
    }

    public function test_list_vendors_for_current_tenant(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        $this->createVendor($tenantId, ['display_name' => 'Alpha Trading']);
        $this->createVendor($tenantId, ['display_name' => 'Bravo Supplies']);
        $this->createVendor((string) Str::ulid(), ['display_name' => 'Other Tenant Vendor']);

        $response = $this->getJson('/api/v1/vendors', $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.display_name', 'Alpha Trading');
        $response->assertJsonPath('data.1.display_name', 'Bravo Supplies');
        $response->assertJsonMissing(['display_name' => 'Other Tenant Vendor']);
    }

    public function test_create_draft_vendor(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        $response = $this->postJson('/api/v1/vendors', [
            'legal_name' => 'Northwind Holdings Sdn Bhd',
            'display_name' => 'Northwind',
            'registration_number' => '202601234567',
            'country_of_registration' => 'MY',
            'primary_contact_name' => 'Nadia Rahman',
            'primary_contact_email' => 'nadia@northwind.test',
            'primary_contact_phone' => '+6011223344',
        ], $this->authHeaders($tenantId, (string) $user->id));

        $response->assertCreated();
        $response->assertJsonPath('data.legal_name', 'Northwind Holdings Sdn Bhd');
        $response->assertJsonPath('data.display_name', 'Northwind');
        $response->assertJsonPath('data.status', 'draft');
        $response->assertJsonPath('data.primary_contact_email', 'nadia@northwind.test');
        $response->assertJsonPath('data.approval_record', null);

        $this->assertDatabaseHas('vendors', [
            'tenant_id' => strtolower($tenantId),
            'legal_name' => 'Northwind Holdings Sdn Bhd',
            'display_name' => 'Northwind',
            'status' => 'draft',
        ]);
    }

    public function test_create_vendor_allows_null_contact_phone(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        $response = $this->postJson('/api/v1/vendors', [
            'legal_name' => 'Null Phone Holdings Sdn Bhd',
            'display_name' => 'Null Phone',
            'registration_number' => '202601234570',
            'country_of_registration' => 'MY',
            'primary_contact_name' => 'Nadia Rahman',
            'primary_contact_email' => 'nadia.null@northwind.test',
            'primary_contact_phone' => null,
        ], $this->authHeaders($tenantId, (string) $user->id));

        $response->assertCreated();
        $response->assertJsonPath('data.primary_contact_phone', null);
        $response->assertJsonPath('data.phone', null);
    }

    public function test_show_vendor_for_current_tenant(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $vendor = $this->createVendor($tenantId, [
            'legal_name' => 'Summit Industrial Sdn Bhd',
            'display_name' => 'Summit Industrial',
        ]);

        $response = $this->getJson('/api/v1/vendors/' . $vendor->id, $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonPath('data.id', $vendor->id);
        $response->assertJsonPath('data.legal_name', 'Summit Industrial Sdn Bhd');
        $response->assertJsonPath('data.display_name', 'Summit Industrial');
        $response->assertJsonPath('data.status', 'draft');
    }

    public function test_patch_vendor_core_fields(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $vendor = $this->createVendor($tenantId);

        $response = $this->patchJson('/api/v1/vendors/' . $vendor->id, [
            'legal_name' => 'Acme Holdings Updated Sdn Bhd',
            'display_name' => 'Acme Updated',
            'registration_number' => '202601234568',
            'country_of_registration' => 'SG',
            'primary_contact_name' => 'Amina Rahman',
            'primary_contact_email' => 'amina.rahman@example.com',
            'primary_contact_phone' => null,
        ], $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonPath('data.legal_name', 'Acme Holdings Updated Sdn Bhd');
        $response->assertJsonPath('data.display_name', 'Acme Updated');
        $response->assertJsonPath('data.registration_number', '202601234568');
        $response->assertJsonPath('data.country_of_registration', 'SG');
        $response->assertJsonPath('data.primary_contact_phone', null);
        $response->assertJsonPath('data.status', 'draft');
    }

    public function test_approve_vendor(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $vendor = $this->createVendor($tenantId, ['status' => 'under_review']);

        $response = $this->patchJson('/api/v1/vendors/' . $vendor->id . '/status', [
            'status' => 'approved',
            'approval_note' => 'Approved after compliance review',
        ], $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonPath('data.status', 'approved');
        $response->assertJsonPath('data.approval_record.approved_by_user_id', (string) $user->id);
        $response->assertJsonPath('data.approval_record.approval_note', 'Approved after compliance review');
        $this->assertNotNull($response->json('data.approval_record.approved_at'));

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'tenant_id' => $tenantId,
            'status' => 'approved',
            'approved_by_user_id' => (string) $user->id,
            'approval_note' => 'Approved after compliance review',
        ]);
    }

    #[DataProvider('crossTenantEndpointProvider')]
    public function test_cross_tenant_access_returns_404_semantics(string $action): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $userB = $this->createUser($tenantB);
        $vendor = $this->createVendor($tenantA);

        $response = match ($action) {
            'show' => $this->getJson('/api/v1/vendors/' . $vendor->id, $this->authHeaders($tenantB, (string) $userB->id)),
            'patch' => $this->patchJson('/api/v1/vendors/' . $vendor->id, [
                'legal_name' => 'Cross Tenant Attempt',
                'display_name' => 'Cross Tenant Attempt',
                'registration_number' => '202601234569',
                'country_of_registration' => 'MY',
                'primary_contact_name' => 'Cross Tenant Attempt',
                'primary_contact_email' => 'cross.tenant@example.com',
                'primary_contact_phone' => null,
            ], $this->authHeaders($tenantB, (string) $userB->id)),
            'status' => $this->patchJson('/api/v1/vendors/' . $vendor->id . '/status', [
                'status' => 'approved',
                'approval_note' => 'Cross tenant attempt',
            ], $this->authHeaders($tenantB, (string) $userB->id)),
        };

        $response->assertNotFound();
    }

    public function test_invalid_status_transition_returns_422(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $vendor = $this->createVendor($tenantId);

        $response = $this->patchJson('/api/v1/vendors/' . $vendor->id . '/status', [
            'status' => 'archived',
        ], $this->authHeaders($tenantId, (string) $user->id));

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Cannot transition vendor status from Draft to Archived.');
    }
}
