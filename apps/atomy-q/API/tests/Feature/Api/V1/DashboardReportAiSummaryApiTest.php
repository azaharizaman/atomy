<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Models\Approval;
use App\Models\Award;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Tests\Feature\Api\Concerns\BindsAiRuntimeStatus;
use Tests\Feature\Api\ApiTestCase;

final class DashboardReportAiSummaryApiTest extends ApiTestCase
{
    use RefreshDatabase;
    use BindsAiRuntimeStatus;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function testDashboardKpisReturnsProviderSummaryWhenAiIsAvailable(): void
    {
        $this->bindAiRuntimeStatus([
            'dashboard_ai_summary' => new AiCapabilityStatus(
                featureKey: 'dashboard_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.dashboard_ai_summary.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(InsightSummaryRequest $request): array
            {
                return [
                    'headline' => 'Dashboard is on track.',
                    'highlights' => ['RFQ throughput is stable'],
                    'recommendations' => ['Continue current operating cadence'],
                ];
            }
        });

        $generate = $this->postJson('/api/v1/dashboard/kpis/generate', [], $this->authHeaders());
        $response = $this->getJson('/api/v1/dashboard/kpis', $this->authHeaders());

        $generate->assertOk();
        $response->assertOk();
        $response->assertJsonPath('data.active_rfqs', 0);
        $response->assertJsonPath('data.ai_summary.available', true);
        $response->assertJsonPath('data.ai_summary.payload.headline', 'Dashboard is on track.');
        $response->assertJsonPath('data.ai_summary.source_facts.active_rfqs', 0);
    }

    public function testDashboardKpisReturnsTenantScopedFunctionalFacts(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $otherTenantId = (string) Str::ulid();
        $otherUser = $this->createUser($otherTenantId);
        $draft = $this->createRfq($tenantId, $user, [
            'status' => 'draft',
            'estimated_value' => 10000,
            'savings_percentage' => 10,
            'created_at' => Carbon::parse('2026-04-01 00:00:00'),
        ]);
        $published = $this->createRfq($tenantId, $user, [
            'status' => 'published',
            'estimated_value' => 20000,
            'savings_percentage' => 5,
            'created_at' => Carbon::parse('2026-04-02 00:00:00'),
        ]);
        $awarded = $this->createRfq($tenantId, $user, [
            'status' => 'awarded',
            'estimated_value' => 30000,
            'savings_percentage' => 12,
            'created_at' => Carbon::parse('2026-04-03 00:00:00'),
        ]);
        $this->createRfq($otherTenantId, $otherUser, ['status' => 'published', 'estimated_value' => 90000]);

        Approval::factory()->pending()->forRfq($published)->create([
            'tenant_id' => $tenantId,
            'requested_by' => $user->id,
        ]);
        QuoteSubmission::factory()->extracting()->forRfq($published)->create([
            'tenant_id' => $tenantId,
            'uploaded_by' => $user->id,
            'errors_count' => 0,
            'warnings_count' => 1,
        ]);
        Award::factory()->pending()->forRfq($awarded)->create([
            'tenant_id' => $tenantId,
            'amount' => 27000,
        ]);
        Award::factory()->signedOff()->forRfq($awarded)->create([
            'tenant_id' => $tenantId,
            'amount' => 26000,
            'signoff_at' => Carbon::parse('2026-04-13 00:00:00'),
        ]);
        Approval::factory()->pending()->forRfq($this->createRfq($otherTenantId, $otherUser, ['status' => 'published']))->create([
            'tenant_id' => $otherTenantId,
            'requested_by' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/dashboard/kpis', $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonPath('data.active_rfqs', 3);
        $response->assertJsonPath('data.pending_approvals', 1);
        $response->assertJsonPath('data.quote_intake_count', 1);
        $response->assertJsonPath('data.awards_in_flight', 1);
        self::assertSame(5600.0, (float) $response->json('data.total_savings'));
        self::assertSame(10.0, (float) $response->json('data.avg_cycle_time_days'));
        $response->assertJsonPath('data.ai_summary.available', false);
        $response->assertJsonPath('data.ai_summary.reason_codes.0', 'no_cached_ai_artifact');
    }

    public function testDashboardKpisReturnsUnavailableSummaryWithoutLosingFacts(): void
    {
        $this->bindAiRuntimeStatus([
            'dashboard_ai_summary' => new AiCapabilityStatus(
                featureKey: 'dashboard_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.dashboard_ai_summary.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(InsightSummaryRequest $request): array
            {
                throw new \RuntimeException('provider client should not be called when unavailable');
            }
        });

        $response = $this->getJson('/api/v1/dashboard/kpis', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.active_rfqs', 0);
        $response->assertJsonPath('data.ai_summary.available', false);
        $response->assertJsonPath('data.ai_summary.payload', null);
        $response->assertJsonPath('data.ai_summary.feature_key', 'dashboard_ai_summary');
    }

    public function testDashboardKpisGetDoesNotInvokeProviderWhenCacheIsMissing(): void
    {
        $this->bindAiRuntimeStatus([
            'dashboard_ai_summary' => new AiCapabilityStatus(
                featureKey: 'dashboard_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.dashboard_ai_summary.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $spy = new ProviderInsightClientSpy([
            'headline' => 'Dashboard should not generate on GET.',
        ]);
        $this->app->instance(ProviderInsightClientInterface::class, $spy);

        $response = $this->getJson('/api/v1/dashboard/kpis', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.ai_summary.available', false);
        $response->assertJsonPath('data.ai_summary.payload', null);
        self::assertSame(0, $spy->getCallCount());
    }

    public function testReportSpendTrendReturnsProviderSummaryWhenAiIsAvailable(): void
    {
        $this->bindAiRuntimeStatus([
            'reporting_ai_summary' => new AiCapabilityStatus(
                featureKey: 'reporting_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.reporting_ai_summary.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(InsightSummaryRequest $request): array
            {
                return [
                    'headline' => 'Spend trend is steady.',
                    'summary' => 'No significant monthly variance detected.',
                    'bullets' => ['Monthly period remains consistent.'],
                ];
            }
        });

        $generate = $this->postJson('/api/v1/reports/spend-trend/generate', [], $this->authHeaders());
        $response = $this->getJson('/api/v1/reports/spend-trend', $this->authHeaders());

        $generate->assertOk();
        $response->assertOk();
        $response->assertJsonPath('data.period', 'monthly');
        $response->assertJsonPath('data.series', []);
        $response->assertJsonPath('data.ai_summary.available', true);
        $response->assertJsonPath('data.ai_summary.payload.headline', 'Spend trend is steady.');
        $response->assertJsonPath('data.ai_summary.source_facts.period', 'monthly');
        $response->assertJsonPath('data.ai_summary.source_facts.series', []);
    }

    public function testReportKpisReturnsTenantScopedFunctionalFacts(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $otherTenantId = (string) Str::ulid();
        $otherUser = $this->createUser($otherTenantId);

        $this->createRfq($tenantId, $user, [
            'status' => 'published',
            'estimated_value' => 10000,
            'savings_percentage' => 10,
        ]);
        $awarded = $this->createRfq($tenantId, $user, [
            'status' => 'awarded',
            'estimated_value' => 30000,
            'savings_percentage' => 5,
        ]);
        Award::factory()->signedOff()->forRfq($awarded)->create([
            'tenant_id' => $tenantId,
            'amount' => 25000,
        ]);
        Award::factory()->pending()->forRfq($awarded)->create([
            'tenant_id' => $tenantId,
            'amount' => 99000,
        ]);
        $this->createRfq($otherTenantId, $otherUser, [
            'status' => 'published',
            'estimated_value' => 99999,
            'savings_percentage' => 40,
        ]);

        $response = $this->getJson('/api/v1/reports/kpis', $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonPath('data.active_rfqs', 2);
        self::assertSame(25000.0, (float) $response->json('data.total_spend'));
        self::assertSame(2500.0, (float) $response->json('data.savings'));
        $response->assertJsonPath('data.ai_summary.reason_codes.0', 'no_cached_ai_artifact');
    }

    public function testReportSpendTrendAndCategoryReturnTenantScopedRows(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $otherTenantId = (string) Str::ulid();
        $otherUser = $this->createUser($otherTenantId);

        $valves = $this->createRfq($tenantId, $user, [
            'status' => 'awarded',
            'category' => 'Valves',
            'created_at' => Carbon::parse('2026-04-05 00:00:00'),
        ]);
        Award::factory()->signedOff()->forRfq($valves)->create([
            'tenant_id' => $tenantId,
            'amount' => 12000,
            'created_at' => Carbon::parse('2026-04-10 00:00:00'),
        ]);
        $electrical = $this->createRfq($tenantId, $user, [
            'status' => 'awarded',
            'category' => 'Electrical',
            'created_at' => Carbon::parse('2026-04-06 00:00:00'),
        ]);
        Award::factory()->signedOff()->forRfq($electrical)->create([
            'tenant_id' => $tenantId,
            'amount' => 8000,
            'created_at' => Carbon::parse('2026-04-11 00:00:00'),
        ]);
        Award::factory()->pending()->forRfq($electrical)->create([
            'tenant_id' => $tenantId,
            'amount' => 99000,
            'created_at' => Carbon::parse('2026-04-12 00:00:00'),
        ]);
        $other = $this->createRfq($otherTenantId, $otherUser, [
            'status' => 'awarded',
            'category' => 'Valves',
        ]);
        Award::factory()->signedOff()->forRfq($other)->create([
            'tenant_id' => $otherTenantId,
            'amount' => 90000,
        ]);

        $trend = $this->getJson('/api/v1/reports/spend-trend', $this->authHeaders($tenantId, (string) $user->id));
        $category = $this->getJson('/api/v1/reports/spend-by-category', $this->authHeaders($tenantId, (string) $user->id));

        $trend->assertOk();
        $trend->assertJsonPath('data.period', 'monthly');
        $trend->assertJsonPath('data.series.0.period', '2026-04');
        self::assertSame(20000.0, (float) $trend->json('data.series.0.spend'));
        $category->assertOk();
        $category->assertJsonPath('data.categories.0.category', 'Electrical');
        self::assertSame(8000.0, (float) $category->json('data.categories.0.spend'));
        $category->assertJsonPath('data.categories.1.category', 'Valves');
        self::assertSame(12000.0, (float) $category->json('data.categories.1.spend'));
    }

    public function testDashboardGenerationSendsRealSourceFactsToProvider(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $this->createRfq($tenantId, $user, ['status' => 'published']);
        $this->bindAiRuntimeStatus([
            'dashboard_ai_summary' => new AiCapabilityStatus(
                featureKey: 'dashboard_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.dashboard_ai_summary.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);
        $spy = new ProviderInsightClientSpy(['headline' => 'Generated from tenant facts.']);
        $this->app->instance(ProviderInsightClientInterface::class, $spy);

        $response = $this->postJson('/api/v1/dashboard/kpis/generate', [], $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonPath('data.active_rfqs', 1);
        $response->assertJsonPath('data.ai_summary.available', true);
        self::assertSame(1, $spy->getCallCount());
        self::assertSame('dashboard_ai_summary', $spy->lastRequest?->featureKey);
        self::assertSame('dashboard_kpis', $spy->lastRequest?->subjectType);
        self::assertSame(1, $spy->lastRequest?->facts['active_rfqs'] ?? null);
    }

    public function testDashboardGenerationReturnsUnavailableArtifactWhenProviderFails(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $this->createRfq($tenantId, $user, ['status' => 'published']);
        $this->bindAiRuntimeStatus([
            'dashboard_ai_summary' => new AiCapabilityStatus(
                featureKey: 'dashboard_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.dashboard_ai_summary.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);
        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(InsightSummaryRequest $request): array
            {
                throw new \RuntimeException('provider down');
            }
        });

        $response = $this->postJson('/api/v1/dashboard/kpis/generate', [], $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonPath('data.active_rfqs', 1);
        $response->assertJsonPath('data.ai_summary.available', false);
        $response->assertJsonPath('data.ai_summary.reason_codes.0', 'provider_unavailable');
    }

    public function testReportKpisGenerationSendsRealSourceFactsToProvider(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $awarded = $this->createRfq($tenantId, $user, [
            'status' => 'awarded',
            'estimated_value' => 50000,
            'savings_percentage' => 8,
        ]);
        Award::factory()->signedOff()->forRfq($awarded)->create([
            'tenant_id' => $tenantId,
            'amount' => 46000,
        ]);
        Award::factory()->pending()->forRfq($awarded)->create([
            'tenant_id' => $tenantId,
            'amount' => 99000,
        ]);
        $this->bindReportingAiAvailable();
        $spy = new ProviderInsightClientSpy(['headline' => 'Generated from report KPI facts.']);
        $this->app->instance(ProviderInsightClientInterface::class, $spy);

        $response = $this->postJson('/api/v1/reports/kpis/generate', [], $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonPath('data.active_rfqs', 1);
        $response->assertJsonPath('data.ai_summary.available', true);
        self::assertSame(1, $spy->getCallCount());
        self::assertSame('reporting_ai_summary', $spy->lastRequest?->featureKey);
        self::assertSame('report_kpis', $spy->lastRequest?->subjectType);
        self::assertSame(1, $spy->lastRequest?->facts['active_rfqs'] ?? null);
        self::assertSame(46000.0, (float) ($spy->lastRequest?->facts['total_spend'] ?? 0));
        self::assertSame(4000.0, (float) ($spy->lastRequest?->facts['savings'] ?? 0));
    }

    public function testReportSpendByCategoryGenerationSendsRealSourceFactsToProvider(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $electrical = $this->createRfq($tenantId, $user, [
            'status' => 'awarded',
            'category' => 'Electrical',
        ]);
        Award::factory()->signedOff()->forRfq($electrical)->create([
            'tenant_id' => $tenantId,
            'amount' => 14000,
        ]);
        Award::factory()->pending()->forRfq($electrical)->create([
            'tenant_id' => $tenantId,
            'amount' => 99000,
        ]);
        $this->bindReportingAiAvailable();
        $spy = new ProviderInsightClientSpy(['headline' => 'Generated from category facts.']);
        $this->app->instance(ProviderInsightClientInterface::class, $spy);

        $response = $this->postJson('/api/v1/reports/spend-by-category/generate', [], $this->authHeaders($tenantId, (string) $user->id));

        $response->assertOk();
        $response->assertJsonPath('data.categories.0.category', 'Electrical');
        $response->assertJsonPath('data.ai_summary.available', true);
        self::assertSame(1, $spy->getCallCount());
        self::assertSame('reporting_ai_summary', $spy->lastRequest?->featureKey);
        self::assertSame('report_spend_by_category', $spy->lastRequest?->subjectType);
        self::assertSame('Electrical', $spy->lastRequest?->facts['categories'][0]['category'] ?? null);
        self::assertSame(14000.0, (float) ($spy->lastRequest?->facts['categories'][0]['spend'] ?? 0));
    }

    public function testReportSpendTrendReturnsUnavailableSummaryWithoutLosingData(): void
    {
        $this->bindAiRuntimeStatus([
            'reporting_ai_summary' => new AiCapabilityStatus(
                featureKey: 'reporting_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.reporting_ai_summary.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(InsightSummaryRequest $request): array
            {
                throw new \RuntimeException('provider client should not be called when unavailable');
            }
        });

        $response = $this->getJson('/api/v1/reports/spend-trend', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.period', 'monthly');
        $response->assertJsonPath('data.series', []);
        $response->assertJsonPath('data.ai_summary.available', false);
        $response->assertJsonPath('data.ai_summary.payload', null);
    }

    public function testReportSpendTrendGetDoesNotInvokeProviderWhenCacheIsMissing(): void
    {
        $this->bindAiRuntimeStatus([
            'reporting_ai_summary' => new AiCapabilityStatus(
                featureKey: 'reporting_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.reporting_ai_summary.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $spy = new ProviderInsightClientSpy([
            'headline' => 'Report should not generate on GET.',
        ]);
        $this->app->instance(ProviderInsightClientInterface::class, $spy);

        $response = $this->getJson('/api/v1/reports/spend-trend', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.ai_summary.available', false);
        $response->assertJsonPath('data.ai_summary.payload', null);
        self::assertSame(0, $spy->getCallCount());
    }

    private function createUser(string $tenantId): User
    {
        $this->createTenant($tenantId);

        return User::factory()->create([
            'tenant_id' => $tenantId,
            'email' => 'dashboard-report-' . Str::lower($tenantId) . '@example.com',
        ]);
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

    private function bindReportingAiAvailable(): void
    {
        $this->bindAiRuntimeStatus([
            'reporting_ai_summary' => new AiCapabilityStatus(
                featureKey: 'reporting_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.reporting_ai_summary.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createRfq(string $tenantId, User $owner, array $overrides = []): Rfq
    {
        return Rfq::factory()->create(array_replace([
            'tenant_id' => $tenantId,
            'owner_id' => $owner->id,
            'project_id' => null,
            'rfq_number' => 'RFQ-' . Str::upper(Str::random(8)),
            'title' => 'Insight test RFQ',
            'category' => 'Valves',
            'status' => 'draft',
            'estimated_value' => 0,
            'savings_percentage' => 0,
        ], $overrides));
    }
}

final class ProviderInsightClientSpy implements ProviderInsightClientInterface
{
    private int $callCount = 0;
    public ?InsightSummaryRequest $lastRequest = null;

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(private readonly array $response)
    {
    }

    public function summarize(InsightSummaryRequest $request): array
    {
        $this->callCount++;
        $this->lastRequest = $request;

        return $this->response;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }
}
