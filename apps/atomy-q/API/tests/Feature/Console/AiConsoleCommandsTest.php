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
use App\Adapters\Ai\Contracts\ProviderDocumentIntelligenceClientInterface;
use App\Adapters\Ai\Contracts\ProviderGovernanceClientInterface;
use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\Contracts\ProviderNormalizationClientInterface;
use App\Adapters\Ai\Contracts\ProviderSourcingRecommendationClientInterface;
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
use App\Adapters\Ai\Exceptions\AiTransportUnavailableException;
use App\Adapters\Ai\ProviderDocumentIntelligenceClient;
use App\Adapters\Ai\ProviderNormalizationClient;
use App\Adapters\Ai\ProviderSourcingRecommendationClient;
use App\Adapters\Ai\Support\OpenRouterDocumentExtractionMapper;
use App\Adapters\Ai\Support\OpenRouterDocumentPayloadFactory;
use App\Services\Ai\AiOperationalAlertPublisher;
use App\Services\Ai\AiProviderCheckSeverity;
use App\Services\Ai\AiProviderCheckFinding;
use App\Services\Ai\AiProviderEndpointCheck;
use App\Services\Ai\AiProviderReadinessResult;
use App\Services\Ai\Contracts\AiOperationalAlertPublisherInterface;
use App\Services\Ai\Contracts\AiProviderReadinessCheckerInterface;
use App\Services\Ai\Contracts\ProviderContractVerifierInterface;
use App\Services\Ai\ProviderContractVerificationResult;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class AiConsoleCommandsTest extends TestCase
{
    public function testAiProviderCheckCommandEmitsJsonWithSafeDefaults(): void
    {
        $secret = 'sk-live-provider-secret';
        $checker = new class implements AiProviderReadinessCheckerInterface {
            /** @var list<array<string, mixed>> */
            public array $calls = [];

            public function check(
                array $endpointGroups,
                bool $deep,
                bool $publishAlerts,
                string $tenantId,
                string $rfqId,
            ): AiProviderReadinessResult {
                $this->calls[] = compact('endpointGroups', 'deep', 'publishAlerts', 'tenantId', 'rfqId');

                return new AiProviderReadinessResult(
                    checkedAt: '2026-04-24T03:00:00+00:00',
                    mode: AiStatusSchema::MODE_PROVIDER,
                    provider: 'openrouter',
                    deep: false,
                    endpointGroups: [
                        new AiProviderEndpointCheck(
                            endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                            configured: true,
                            enabled: true,
                            endpointUri: 'https://openrouter.ai/api/v1/chat/completions?api_key=[redacted]',
                            probeHealth: AiStatusSchema::HEALTH_HEALTHY,
                            latencyMs: 42,
                            severity: AiProviderCheckSeverity::OK,
                            reasonCodes: ['provider_available'],
                            diagnostics: ['auth_token' => '[redacted]'],
                        ),
                    ],
                    operatorFindings: [
                        new AiProviderCheckFinding(
                            severity: AiProviderCheckSeverity::OK,
                            area: 'security',
                            message: 'Provider token is redacted in operator output.',
                            endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                        ),
                    ],
                    publishedAlerts: [
                        ['message' => 'Provider alert payload redacted before command output.'],
                    ],
                );
            }
        };
        $this->app->instance(AiProviderReadinessCheckerInterface::class, $checker);

        $exitCode = Artisan::call('atomy:ai-provider-check', ['--json' => true]);
        $output = Artisan::output();
        $payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertIsArray($payload);
        self::assertSame(AiProviderCheckSeverity::OK, $payload['global_status']);
        self::assertSame(AiStatusSchema::ENDPOINT_GROUP_INSIGHT, $payload['endpoint_groups'][0]['endpoint_group']);
        self::assertStringNotContainsString($secret, $output);
        self::assertSame([
            [
                'endpointGroups' => AiStatusSchema::endpointGroups(),
                'deep' => false,
                'publishAlerts' => false,
                'tenantId' => 'plan6-tenant',
                'rfqId' => 'plan6-rfq',
            ],
        ], $checker->calls);
    }

    public function testAiProviderCheckCommandFailsWhenJsonPayloadCannotBeEncoded(): void
    {
        $this->app->instance(AiProviderReadinessCheckerInterface::class, new readonly class implements AiProviderReadinessCheckerInterface {
            public function check(
                array $endpointGroups,
                bool $deep,
                bool $publishAlerts,
                string $tenantId,
                string $rfqId,
            ): AiProviderReadinessResult {
                return new AiProviderReadinessResult(
                    checkedAt: '2026-04-24T03:00:00+00:00',
                    mode: AiStatusSchema::MODE_PROVIDER,
                    provider: 'openrouter',
                    deep: false,
                    endpointGroups: [],
                    operatorFindings: [],
                    publishedAlerts: [
                        ['latency_sample' => NAN],
                    ],
                );
            }
        });

        $this->artisan('atomy:ai-provider-check --json')
            ->expectsOutputToContain('Failed to encode AI provider check payload.')
            ->assertExitCode(1);
    }

    public function testAiProviderCheckCommandFailsOnWarningsWhenConfigured(): void
    {
        $this->app->instance(AiProviderReadinessCheckerInterface::class, new readonly class implements AiProviderReadinessCheckerInterface {
            public function check(
                array $endpointGroups,
                bool $deep,
                bool $publishAlerts,
                string $tenantId,
                string $rfqId,
            ): AiProviderReadinessResult {
                return new AiProviderReadinessResult(
                    checkedAt: '2026-04-24T03:00:00+00:00',
                    mode: AiStatusSchema::MODE_PROVIDER,
                    provider: 'openrouter',
                    deep: true,
                    endpointGroups: [
                        new AiProviderEndpointCheck(
                            endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                            configured: true,
                            enabled: true,
                            endpointUri: 'https://openrouter.ai/api/v1/chat/completions',
                            probeHealth: AiStatusSchema::HEALTH_DEGRADED,
                            latencyMs: 120,
                            severity: AiProviderCheckSeverity::WARNING,
                            reasonCodes: ['provider_degraded'],
                        ),
                    ],
                    operatorFindings: [
                        new AiProviderCheckFinding(
                            severity: AiProviderCheckSeverity::WARNING,
                            area: 'auth',
                            message: 'Endpoint [document] has no configured provider token.',
                            endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                            reasonCode: 'missing_auth_token',
                        ),
                    ],
                );
            }
        });

        $this->artisan('atomy:ai-provider-check --endpoint-group=document --deep --publish-alerts --json --fail-on=warning')
            ->expectsOutputToContain('"exit_severity": "warning"')
            ->assertExitCode(1);
    }

    public function testAiProviderCheckCommandFailsForUnsupportedEndpointGroup(): void
    {
        $this->artisan('atomy:ai-provider-check --endpoint-group=unsupported')
            ->expectsOutputToContain('Unsupported endpoint group(s): unsupported')
            ->assertExitCode(1);
    }

    public function testAiProviderCheckCommandFailsForBlankTenantOption(): void
    {
        $this->artisan('atomy:ai-provider-check --tenant-id=')
            ->expectsOutputToContain('The --tenant-id option must be non-empty.')
            ->assertExitCode(1);
    }

    public function testAiProviderCheckCommandFailsForBlankRfqOption(): void
    {
        $this->artisan('atomy:ai-provider-check --rfq-id=')
            ->expectsOutputToContain('The --rfq-id option must be non-empty.')
            ->assertExitCode(1);
    }

    public function testAiProviderCheckCommandFailsForUnsupportedFailOnOption(): void
    {
        $this->artisan('atomy:ai-provider-check --fail-on=warnng')
            ->expectsOutputToContain('Unsupported --fail-on value [warnng]. Supported values: warning.')
            ->assertExitCode(1);
    }

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
            logger: $this->app->make(LogManager::class)->channel('stack'),
        ));
        $this->app->instance(
            AiOperationalAlertPublisherInterface::class,
            $this->app->make(AiOperationalAlertPublisher::class),
        );

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

        $this->app->instance(ProviderDocumentIntelligenceClient::class, new ProviderDocumentIntelligenceClient(
            $transport,
            new OpenRouterDocumentPayloadFactory('model-a', 'file-parser', 'mistral-ocr'),
            new OpenRouterDocumentExtractionMapper(),
        ));
        $this->app->instance(ProviderDocumentIntelligenceClientInterface::class, $this->app->make(ProviderDocumentIntelligenceClient::class));
        $this->app->instance(ProviderNormalizationClient::class, new ProviderNormalizationClient($transport));
        $this->app->instance(ProviderNormalizationClientInterface::class, $this->app->make(ProviderNormalizationClient::class));
        $this->app->instance(ProviderSourcingRecommendationClient::class, new ProviderSourcingRecommendationClient($transport));
        $this->app->instance(
            ProviderSourcingRecommendationClientInterface::class,
            $this->app->make(ProviderSourcingRecommendationClient::class),
        );
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
            ->expectsOutputToContain('Verified provider contract for normalization')
            ->expectsOutputToContain('Verified provider contract for sourcing_recommendation')
            ->expectsOutputToContain('Verified provider contract for comparison_award')
            ->expectsOutputToContain('Verified provider contract for insight')
            ->expectsOutputToContain('Verified provider contract for governance')
            ->assertExitCode(0);
    }

    public function testAiVerifyContractsCommandFailsForUnsupportedEndpointGroup(): void
    {
        $this->artisan('atomy:ai-verify-contracts --endpoint-group=unsupported')
            ->expectsOutputToContain('Unsupported endpoint group(s): unsupported')
            ->assertExitCode(1);
    }

    public function testAiVerifyContractsCommandFailsForBlankTenantOption(): void
    {
        $this->artisan('atomy:ai-verify-contracts --tenant-id=')
            ->expectsOutputToContain('The --tenant-id option must be non-empty.')
            ->assertExitCode(1);
    }

    public function testAiVerifyContractsCommandFailsForBlankRfqOption(): void
    {
        $this->artisan('atomy:ai-verify-contracts --rfq-id=')
            ->expectsOutputToContain('The --rfq-id option must be non-empty.')
            ->assertExitCode(1);
    }

    public function testAiVerifyContractsCommandFailsWhenVerifierReportsFailedResult(): void
    {
        $this->app->instance(ProviderContractVerifierInterface::class, new readonly class implements ProviderContractVerifierInterface {
            public function endpointGroups(): array
            {
                return [AiStatusSchema::ENDPOINT_GROUP_DOCUMENT];
            }

            public function assertEndpointGroups(array $endpointGroups): void
            {
            }

            public function verify(array $endpointGroups, string $tenantId, string $rfqId): array
            {
                return [
                    new ProviderContractVerificationResult(
                        endpointGroup: 'document',
                        severity: AiProviderCheckSeverity::FAILED,
                        verified: false,
                        reasonCodes: ['provider_invalid_payload'],
                        message: 'Provider contract verification returned an invalid payload for document',
                    ),
                ];
            }
        });

        $this->artisan('atomy:ai-verify-contracts --endpoint-group=document')
            ->expectsOutputToContain('Provider contract verification returned an invalid payload for document')
            ->assertExitCode(1);
    }

    public function testAiVerifyContractsCommandClassifiesProviderUnavailableExceptions(): void
    {
        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(InsightSummaryRequest $request): array
            {
                throw new AiTransportUnavailableException('AI endpoint [insight] is unavailable.');
            }
        });

        $this->artisan('atomy:ai-verify-contracts --endpoint-group=insight')
            ->expectsOutputToContain('Provider contract verification failed for insight.')
            ->assertExitCode(1);
    }

    public function testAiVerifyContractsCommandFailsWhenProviderReturnsListPayload(): void
    {
        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(InsightSummaryRequest $request): array
            {
                return ['not-associative'];
            }
        });

        $this->artisan('atomy:ai-verify-contracts --endpoint-group=insight')
            ->expectsOutputToContain('Provider contract verification returned an invalid payload for insight')
            ->assertExitCode(1);
    }
}
