<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Award;
use App\Models\QuoteSubmission;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class VendorWorkflowTest extends ApiTestCase
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

    private function createUser(?string $tenantId = null): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId ?? (string) Str::ulid(),
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

    private function createVendor(string $tenantId, array $overrides = []): Vendor
    {
        /** @var Vendor $vendor */
        $vendor = Vendor::query()->create(array_merge([
            'tenant_id' => $tenantId,
            'name' => 'Acme Industrial',
            'trading_name' => 'Acme',
            'registration_number' => 'REG-1001',
            'tax_id' => 'TAX-1001',
            'country_code' => 'MY',
            'email' => 'sales@acme.test',
            'phone' => '+60-12345678',
            'status' => 'active',
            'onboarded_at' => now()->subDays(10),
            'metadata' => [
                'compliance_status' => 'compliant',
                'kyc_verified' => true,
                'sanctions_screened' => true,
                'compliance_last_checked_at' => now()->subDay()->toAtomString(),
            ],
        ], $overrides));

        return $vendor;
    }

    public function test_index_returns_tenant_scoped_vendor_rows(): void
    {
        $user = $this->createUser();
        $this->createVendor((string) $user->tenant_id, ['name' => 'Vendor A']);
        $this->createVendor((string) $user->tenant_id, ['name' => 'Vendor B']);
        $this->createVendor((string) Str::ulid(), ['name' => 'Other Tenant Vendor']);

        $response = $this->getJson(
            '/api/v1/vendors',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonMissing(['name' => 'Other Tenant Vendor']);
    }

    public function test_show_returns_404_for_cross_tenant_vendor(): void
    {
        $user = $this->createUser();
        $otherTenantVendor = $this->createVendor((string) Str::ulid());

        $response = $this->getJson(
            '/api/v1/vendors/' . $otherTenantVendor->id,
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertNotFound();
    }

    public function test_performance_returns_live_metrics_from_quotes_and_awards(): void
    {
        $user = $this->createUser();
        $vendor = $this->createVendor((string) $user->tenant_id);

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => (string) Str::ulid(),
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
            'status' => 'ready',
            'submitted_at' => now(),
            'confidence' => 90.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);
        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => (string) Str::ulid(),
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
            'status' => 'processing',
            'submitted_at' => now(),
            'confidence' => 70.0,
            'line_items_count' => 1,
            'warnings_count' => 1,
            'errors_count' => 0,
        ]);

        Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => (string) Str::ulid(),
            'comparison_run_id' => null,
            'vendor_id' => $vendor->id,
            'status' => 'signed_off',
            'amount' => '5000.00',
            'currency' => 'USD',
            'split_details' => [],
            'protest_id' => null,
            'signoff_at' => now(),
            'signed_off_by' => (string) $user->id,
        ]);

        $response = $this->getJson(
            '/api/v1/vendors/' . $vendor->id . '/performance',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.vendor_id', $vendor->id);
        $response->assertJsonPath('data.metrics.quotes_submitted', 2);
        $response->assertJsonPath('data.metrics.quotes_ready', 1);
        $response->assertJsonPath('data.metrics.awards_won', 1);
        $response->assertJsonPath('data.metrics.average_confidence', 80);
    }

    public function test_compliance_reads_vendor_metadata(): void
    {
        $user = $this->createUser();
        $vendor = $this->createVendor((string) $user->tenant_id, [
            'metadata' => [
                'compliance_status' => 'review_required',
                'kyc_verified' => false,
                'sanctions_screened' => true,
                'compliance_last_checked_at' => '2026-04-01T00:00:00+00:00',
            ],
        ]);

        $response = $this->getJson(
            '/api/v1/vendors/' . $vendor->id . '/compliance',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'review_required');
        $response->assertJsonPath('data.kyc_verified', false);
        $response->assertJsonPath('data.sanctions_screened', true);
        $response->assertJsonPath('data.last_checked_at', '2026-04-01T00:00:00+00:00');
    }

    public function test_history_returns_tenant_scoped_award_rows(): void
    {
        $user = $this->createUser();
        $vendor = $this->createVendor((string) $user->tenant_id);

        $ownAward = Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => (string) Str::ulid(),
            'comparison_run_id' => null,
            'vendor_id' => $vendor->id,
            'status' => 'signed_off',
            'amount' => '1200.50',
            'currency' => 'USD',
            'split_details' => [],
            'protest_id' => null,
            'signoff_at' => now(),
            'signed_off_by' => (string) $user->id,
        ]);

        Award::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'rfq_id' => (string) Str::ulid(),
            'comparison_run_id' => null,
            'vendor_id' => $vendor->id,
            'status' => 'signed_off',
            'amount' => '999.00',
            'currency' => 'USD',
            'split_details' => [],
            'protest_id' => null,
            'signoff_at' => now(),
            'signed_off_by' => (string) Str::ulid(),
        ]);

        $response = $this->getJson(
            '/api/v1/vendors/' . $vendor->id . '/history',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.award_id', $ownAward->id);
        $response->assertJsonPath('data.0.amount', 1200.5);
    }
}
