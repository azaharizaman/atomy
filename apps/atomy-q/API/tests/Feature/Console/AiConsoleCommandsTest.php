<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Nexus\Common\Contracts\ClockInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;

use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Contracts\ProviderGovernanceClientInterface;
use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\DTOs\ApprovalSummaryRequest;
use App\Adapters\Ai\DTOs\ApprovalSummaryResponse;
use App\Adapters\Ai\DTOs\AwardDebriefDraftRequest;
use App\Adapters\Ai\DTOs\AwardDebriefDraftResponse;
use App\Adapters\Ai\DTOs\AwardGuidanceRequest;
use App\Adapters\Ai\DTOs\AwardGuidanceResponse;
use App\Adapters\Ai\DTOs\ComparisonOverlayRequest;
use App\Adapters\Ai\DTOs\ComparisonOverlayResponse;
use App\Adapters\Ai\DTOs\GovernanceNarrativeRequest;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Adapters\Ai\ProviderDocumentIntelligenceClient;
use App\Adapters\Ai\ProviderNormalizationClient;
use App\Adapters\Ai\ProviderSourcingRecommendationClient;
use App\Services\Ai\AiOperationalAlertPublisher;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Log\LogManager;
use Tests\TestCase;

final class AiConsoleCommandsTest extends TestCase
{
    public function testAiStatusCommandEmitsJsonSnapshot(): void
    {
        $snapshot = new AiStatusSnapshot(
            mode: AiStatusSchema::MODE_PROVIDER,
            globalHealth: AiStatusSchema::HEALTH_HEALTHY,
            capabilityDefinitions: [],
            capabilityStatuses: [
                'dashboard_ai_summary' => new AiCapabilityStatus(
                    featureKey: 'dashboard_ai_summary',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                    messageKey: 'ai.dashboard_ai_summary.available',
                    status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                    available: true,
                    reasonCodes: ['provider_available'],
                    operatorCritical: false,
                    diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
                ),
            ],
            endpointGroupHealthSnapshots: [],
            reasonCodes: ['provider_available'],
            generatedAt: new \DateTimeImmutable('2026-04-24T03:00:00+00:00'),
        );

        $this->app->instance(AiRuntimeStatusInterface::class, new readonly class($snapshot) implements AiRuntimeStatusInterface {
            public function __construct(private AiStatusSnapshot $snapshot)
            {
            }

            public function snapshot(): AiStatusSnapshot
            {
                return $this->snapshot;
            }

            public function capabilityStatus(string $featureKey): ?AiCapabilityStatus
            {
                return $this->snapshot->capabilityStatuses[$featureKey] ?? null;
            }

            public function providerName(): string
            {
                return 'openrouter';
            }
        });

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2026-04-24T03:00:00+00:00'));
        $this->app->instance(AiOperationalAlertPublisher::class, new AiOperationalAlertPublisher(
            clock: $clock,
            cache: $this->app->make(CacheFactory::class)->store(),
            logs: $this->app->make(LogManager::class),
        ));

        $this->artisan('atomy:ai-status --json')
            ->assertExitCode(0);
    }

    public function testAiFailureDrillCommandPrintsScenarioExpectations(): void
    {
        $this->artisan('atomy:ai-drill quota-exceeded')
            ->expectsOutputToContain('quota-exceeded')
            ->expectsOutputToContain('provider_quota_exceeded')
            ->assertExitCode(0);
    }

    public function testAiVerifyContractsCommandRunsAllEndpointGroups(): void
    {
        $transport = $this->createMock(ProviderAiTransportInterface::class);
        $transport->expects(self::exactly(3))
            ->method('invoke')
            ->withAnyParameters()
            ->willReturn(['ok' => true]);

        $this->app->instance(ProviderDocumentIntelligenceClient::class, new ProviderDocumentIntelligenceClient($transport));
        $this->app->instance(ProviderNormalizationClient::class, new ProviderNormalizationClient($transport));
        $this->app->instance(ProviderSourcingRecommendationClient::class, new ProviderSourcingRecommendationClient($transport));
        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(InsightSummaryRequest $request): array
            {
                return ['headline' => 'ok'];
            }
        });
        $this->app->instance(ProviderGovernanceClientInterface::class, new readonly class implements ProviderGovernanceClientInterface {
            public function narrate(GovernanceNarrativeRequest $request): array
            {
                return ['summary' => 'ok'];
            }
        });
        $this->app->instance(ComparisonAwardAiClientInterface::class, new readonly class implements ComparisonAwardAiClientInterface {
            public function comparisonOverlay(ComparisonOverlayRequest $request): ComparisonOverlayResponse
            {
                return new ComparisonOverlayResponse(['payload' => ['headline' => 'ok']]);
            }

            public function awardGuidance(AwardGuidanceRequest $request): AwardGuidanceResponse
            {
                return new AwardGuidanceResponse(['payload' => ['headline' => 'ok']]);
            }

            public function awardDebriefDraft(AwardDebriefDraftRequest $request): AwardDebriefDraftResponse
            {
                return new AwardDebriefDraftResponse(['payload' => ['headline' => 'ok']]);
            }

            public function approvalSummary(ApprovalSummaryRequest $request): ApprovalSummaryResponse
            {
                return new ApprovalSummaryResponse(['payload' => ['headline' => 'ok']]);
            }
        });

        $this->artisan('atomy:ai-verify-contracts')
            ->expectsOutputToContain('Verified provider contract for document')
            ->expectsOutputToContain('Verified provider contract for governance')
            ->assertExitCode(0);
    }
}
