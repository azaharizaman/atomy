<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorEvidence;
use App\Models\VendorFinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class VendorGovernanceApiTest extends ApiTestCase
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

    public function testGovernanceListsEvidenceFindingsAndScoresForTenantVendor(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-22T00:00:00Z'));
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $vendor = $this->createVendor($tenantId);
        $otherVendor = $this->createVendor($tenantId, ['display_name' => 'Other Vendor']);
        $this->createEvidence($tenantId, (string) $vendor->id, [
            'domain' => 'compliance',
            'type' => 'iso_certificate',
            'title' => 'ISO 14001 certificate',
            'observed_at' => '2024-01-01T00:00:00Z',
            'expires_at' => '2026-01-01T00:00:00Z',
            'review_status' => 'pending',
        ]);
        $this->createFinding($tenantId, (string) $vendor->id, [
            'domain' => 'risk',
            'issue_type' => 'financial_watch',
            'severity' => 'high',
            'status' => 'open',
            'remediation_due_at' => '2026-04-01T00:00:00Z',
        ]);
        $this->createEvidence($tenantId, (string) $otherVendor->id, ['title' => 'Other vendor evidence']);

        $response = $this->getJson('/api/v1/vendors/' . $vendor->id . '/governance', $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonCount(1, 'data.evidence');
        $response->assertJsonCount(1, 'data.findings');
        $response->assertJsonPath('data.evidence.0.title', 'ISO 14001 certificate');
        $response->assertJsonPath('data.findings.0.issue_type', 'financial_watch');
        $response->assertJsonPath('data.summary_scores.compliance_health_score', 45);
        $response->assertJsonPath('data.summary_scores.risk_watch_score', 45);
        $response->assertJsonPath('data.summary_scores.evidence_freshness_score', 25);
        $this->assertContains('compliance_document_expired', $response->json('data.warning_flags'));
        $this->assertContains('open_severe_risk_finding', $response->json('data.warning_flags'));
        Carbon::setTestNow();
    }

    public function testGovernanceCrossTenantAccessReturnsNotFound(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $userB = $this->createUser($tenantB);
        $vendor = $this->createVendor($tenantA);

        $response = $this->getJson('/api/v1/vendors/' . $vendor->id . '/governance', $this->authHeaders($tenantB, (string) $userB->id));

        $response->assertNotFound();
    }

    public function testDueDiligenceCanUpdateEvidenceReviewStatus(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $vendor = $this->createVendor($tenantId);
        $evidence = $this->createEvidence($tenantId, (string) $vendor->id, [
            'domain' => 'compliance',
            'type' => 'due_diligence',
            'title' => 'Beneficial ownership questionnaire',
            'review_status' => 'pending',
        ]);

        $listResponse = $this->getJson('/api/v1/vendors/' . $vendor->id . '/due-diligence', $this->authHeaders($tenantId, (string) $user->id));
        $listResponse->assertOk();
        $listResponse->assertJsonPath('data.items.0.title', 'Beneficial ownership questionnaire');
        $listResponse->assertJsonPath('data.overall_status', 'current');

        $patchResponse = $this->patchJson('/api/v1/vendors/' . $vendor->id . '/due-diligence/' . $evidence->id, [
            'review_status' => 'reviewed',
            'reviewed_by' => (string) $user->id,
            'notes' => 'Reviewed by compliance',
        ], $this->authHeaders($tenantId, (string) $user->id));

        $patchResponse->assertOk();
        $patchResponse->assertJsonPath('data.status', 'reviewed');
        $patchResponse->assertJsonPath('data.item.notes', 'Reviewed by compliance');
    }

    public function testSanctionsScreeningCreatesReviewedEvidenceAndHistory(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-22T00:00:00Z'));
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $vendor = $this->createVendor($tenantId);

        $response = $this->postJson('/api/v1/vendors/' . $vendor->id . '/sanctions-screening', [
            'title' => 'April sanctions screening',
            'source' => 'manual-review',
        ], $this->authHeaders($tenantId, (string) $user->id));

        $response->assertCreated();
        $response->assertJsonPath('data.screening_status', 'completed');
        $response->assertJsonPath('data.evidence.type', 'sanctions_screening');
        $response->assertJsonPath('data.evidence.review_status', 'reviewed');

        $history = $this->getJson('/api/v1/vendors/' . $vendor->id . '/sanctions-history', $this->authHeaders($tenantId, (string) $user->id));
        $history->assertOk();
        $history->assertJsonPath('data.history.0.title', 'April sanctions screening');
        Carbon::setTestNow();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createUser(string $tenantId, array $overrides = []): User
    {
        /** @var User $user */
        $user = User::query()->create(array_merge([
            'tenant_id' => $tenantId,
            'email' => 'governance-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Governance User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ], $overrides));

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
            'name' => 'Northwind Holdings Sdn Bhd',
            'trading_name' => 'Northwind',
            'registration_number' => '202601234567',
            'tax_id' => 'TAX-202601234567',
            'country_code' => 'MY',
            'email' => 'contact@northwind.test',
            'phone' => '+60123456789',
            'status' => 'approved',
            'legal_name' => 'Northwind Holdings Sdn Bhd',
            'display_name' => 'Northwind',
            'country_of_registration' => 'MY',
            'primary_contact_name' => 'Nadia Rahman',
            'primary_contact_email' => 'contact@northwind.test',
            'primary_contact_phone' => '+60123456789',
        ], $overrides));

        return $vendor;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createEvidence(string $tenantId, string $vendorId, array $overrides = []): VendorEvidence
    {
        /** @var VendorEvidence $evidence */
        $evidence = VendorEvidence::query()->create(array_merge([
            'tenant_id' => $tenantId,
            'vendor_id' => $vendorId,
            'domain' => 'esg',
            'type' => 'sustainability_disclosure',
            'title' => 'Sustainability disclosure',
            'source' => 'manual',
            'observed_at' => '2026-04-01T00:00:00Z',
            'expires_at' => '2027-04-01T00:00:00Z',
            'review_status' => 'reviewed',
            'reviewed_by' => 'compliance-user',
            'notes' => null,
        ], $overrides));

        return $evidence;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createFinding(string $tenantId, string $vendorId, array $overrides = []): VendorFinding
    {
        /** @var VendorFinding $finding */
        $finding = VendorFinding::query()->create(array_merge([
            'tenant_id' => $tenantId,
            'vendor_id' => $vendorId,
            'domain' => 'risk',
            'issue_type' => 'delivery_risk',
            'severity' => 'medium',
            'status' => 'open',
            'opened_at' => '2026-04-01T00:00:00Z',
            'opened_by' => 'risk-user',
            'remediation_owner' => 'procurement',
            'remediation_due_at' => '2026-06-01T00:00:00Z',
            'resolution_summary' => null,
        ], $overrides));

        return $finding;
    }
}
