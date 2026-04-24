<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Adapters\Ai\Contracts\ProviderGovernanceClientInterface;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorEvidence;
use App\Models\VendorFinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Tests\Feature\Api\Concerns\BindsAiRuntimeStatus;
use Tests\Feature\Api\ApiTestCase;

final class VendorGovernanceApiTest extends ApiTestCase
{
    use BindsAiRuntimeStatus;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

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
    }

    public function testSanctionsScreeningReplaysForSameIdempotencyKey(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-22T00:00:00Z'));
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $vendor = $this->createVendor($tenantId);
        $headers = array_merge(
            $this->authHeaders($tenantId, (string) $user->id),
            ['Idempotency-Key' => 'vendor-governance-screening-key'],
        );

        $first = $this->postJson('/api/v1/vendors/' . $vendor->id . '/sanctions-screening', [
            'title' => 'April sanctions screening',
            'source' => 'manual-review',
        ], $headers);
        $second = $this->postJson('/api/v1/vendors/' . $vendor->id . '/sanctions-screening', [
            'title' => 'April sanctions screening',
            'source' => 'manual-review',
        ], $headers);

        $first->assertCreated();
        $second->assertCreated();
        $this->assertSame(
            $first->json('data.evidence.id'),
            $second->json('data.evidence.id'),
        );
        $this->assertSame(
            1,
            VendorEvidence::query()
                ->where('tenant_id', strtolower($tenantId))
                ->where('vendor_id', strtolower((string) $vendor->id))
                ->where('type', 'sanctions_screening')
                ->count(),
        );
    }

    public function testGovernanceIncludesProviderNarrativeWhenAiIsAvailable(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-22T00:00:00Z'));
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $vendor = $this->createVendor($tenantId);
        $evidence = $this->createEvidence($tenantId, (string) $vendor->id, [
            'domain' => 'compliance',
            'type' => 'iso_certificate',
            'title' => 'ISO 14001 certificate',
            'observed_at' => '2024-01-01T00:00:00Z',
            'expires_at' => '2026-01-01T00:00:00Z',
            'review_status' => 'pending',
        ]);
        $finding = $this->createFinding($tenantId, (string) $vendor->id, [
            'domain' => 'risk',
            'issue_type' => 'financial_watch',
            'severity' => 'high',
            'status' => 'open',
            'remediation_due_at' => '2026-04-01T00:00:00Z',
        ]);

        $this->bindAiRuntimeStatus([
            'governance_ai_narrative' => new AiCapabilityStatus(
                featureKey: 'governance_ai_narrative',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.governance_ai_narrative.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderGovernanceClientInterface::class, new readonly class implements ProviderGovernanceClientInterface {
            public function narrate(\App\Adapters\Ai\DTOs\GovernanceNarrativeRequest $request): array
            {
                return [
                    'headline' => 'Vendor governance is acceptable with targeted follow-up.',
                    'recommendations' => ['Complete the overdue remediation task'],
                ];
            }
        });

        $generate = $this->postJson('/api/v1/vendors/' . $vendor->id . '/governance/generate', [], $this->authHeaders($tenantId, (string) $user->id));
        $response = $this->getJson('/api/v1/vendors/' . $vendor->id . '/governance', $this->authHeaders($tenantId, (string) $user->id));

        $generate->assertOk();
        $response->assertOk();
        $generate->assertJsonPath('data.ai_narrative.available', true);
        $generate->assertJsonPath('data.ai_narrative.payload.headline', 'Vendor governance is acceptable with targeted follow-up.');
        $generate->assertJsonPath('data.ai_narrative.payload.source_facts.summary_scores.compliance_health_score', 45);
        $generate->assertJsonPath('data.ai_narrative.payload.source_facts.evidence.0.has_notes', false);
        $generate->assertJsonMissingPath('data.ai_narrative.payload.source_facts.evidence.0.id');
        $generate->assertJsonMissingPath('data.ai_narrative.payload.source_facts.findings.0.id');
        $this->assertIsString($generate->json('data.ai_narrative.payload.source_facts.findings.0.opened_by_hash'));
        $response->assertJsonPath('data.summary_scores.compliance_health_score', 45);
    }

    public function testGovernanceKeepsFactualDataWhenAiNarrativeIsUnavailable(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-22T00:00:00Z'));
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $vendor = $this->createVendor($tenantId);
        $this->createEvidence($tenantId, (string) $vendor->id, [
            'domain' => 'compliance',
            'type' => 'iso_certificate',
            'title' => 'ISO 14001 certificate',
            'observed_at' => '2024-01-01T00:00:00Z',
            'expires_at' => '2026-01-01T00:00:00Z',
            'review_status' => 'pending',
        ]);

        $this->bindAiRuntimeStatus([
            'governance_ai_narrative' => new AiCapabilityStatus(
                featureKey: 'governance_ai_narrative',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.governance_ai_narrative.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderGovernanceClientInterface::class, new readonly class implements ProviderGovernanceClientInterface {
            public function narrate(\App\Adapters\Ai\DTOs\GovernanceNarrativeRequest $request): array
            {
                throw new \RuntimeException('provider client should not be called when unavailable');
            }
        });

        $response = $this->getJson('/api/v1/vendors/' . $vendor->id . '/governance', $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonPath('data.summary_scores.compliance_health_score', 45);
        $response->assertJsonPath('data.ai_narrative.available', false);
        $response->assertJsonPath('data.ai_narrative.payload', null);
        $response->assertJsonPath('data.ai_narrative.feature_key', 'governance_ai_narrative');
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
