<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Tests\Unit;

use DateTimeImmutable;
use Nexus\MachineLearning\Contracts\AiCapabilityCatalogInterface;
use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\MachineLearning\Enums\AiCapabilityGroup;
use Nexus\MachineLearning\Enums\AiFallbackUiMode;
use Nexus\MachineLearning\Enums\AiHealth;
use Nexus\MachineLearning\Enums\AiMode;
use Nexus\MachineLearning\Exceptions\AiRuntimeContractException;
use Nexus\MachineLearning\ValueObjects\AiCapabilityDefinition;
use Nexus\MachineLearning\ValueObjects\AiCapabilityStatus;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Nexus\MachineLearning\ValueObjects\AiEndpointHealthSnapshot;
use Nexus\MachineLearning\ValueObjects\AiRuntimeSnapshot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AiRuntimeContractsTest extends TestCase
{
    #[Test]
    public function it_parses_ai_mode_and_legacy_llm_alias(): void
    {
        self::assertSame(AiMode::PROVIDER, AiMode::fromConfig('llm'));
        self::assertSame(AiMode::OFF, AiMode::fromConfig('off'));
        self::assertSame(AiMode::DETERMINISTIC, AiMode::fromConfig('deterministic'));
    }

    #[Test]
    public function it_serializes_capability_definitions_and_lookup_results(): void
    {
        $definition = new AiCapabilityDefinition(
            featureKey: 'procurement_ai_quote_summary',
            capabilityGroup: AiCapabilityGroup::DOCUMENT_INTELLIGENCE,
            requiresAi: true,
            hasManualFallback: true,
            fallbackUiMode: AiFallbackUiMode::SHOW_MANUAL_CONTINUITY_BANNER,
            degradationMessageKey: 'ai.procurement.quote_summary.degraded',
            operatorCritical: true,
        );

        self::assertSame(
            [
                'feature_key' => 'procurement_ai_quote_summary',
                'capability_group' => 'document_intelligence',
                'requires_ai' => true,
                'has_manual_fallback' => true,
                'fallback_ui_mode' => 'show_manual_continuity_banner',
                'degradation_message_key' => 'ai.procurement.quote_summary.degraded',
                'operator_critical' => true,
            ],
            $definition->toArray(),
        );

        $catalog = new class ($definition) implements AiCapabilityCatalogInterface {
            public function __construct(
                private readonly AiCapabilityDefinition $definition,
            ) {
            }

            public function all(): array
            {
                return [$this->definition];
            }

            public function findByFeatureKey(string $featureKey): ?AiCapabilityDefinition
            {
                return $featureKey === $this->definition->featureKey ? $this->definition : null;
            }
        };

        self::assertSame($definition, $catalog->findByFeatureKey('procurement_ai_quote_summary'));
        self::assertNull($catalog->findByFeatureKey('missing_feature'));
    }

    #[Test]
    public function it_serializes_endpoint_group_health_snapshots(): void
    {
        $checkedAt = new DateTimeImmutable('2026-04-23T10:15:00+08:00');

        $snapshot = new AiEndpointHealthSnapshot(
            endpointGroup: AiEndpointGroup::NORMALIZATION,
            health: AiHealth::DEGRADED,
            checkedAt: $checkedAt,
            reasonCodes: ['provider_timeout', 'manual_fallback_used'],
            latencyMs: 482,
            diagnostics: [
                'provider_name' => 'provider-x',
                'endpoint_uri' => 'https://ai.example.test/runtime',
                'timeout_seconds' => 20,
            ],
        );

        self::assertSame(
            [
                'endpoint_group' => 'normalization',
                'health' => 'degraded',
                'checked_at' => '2026-04-23T10:15:00+08:00',
                'reason_codes' => ['provider_timeout', 'manual_fallback_used'],
                'latency_ms' => 482,
                'diagnostics' => [
                    'provider_name' => 'provider-x',
                    'endpoint_uri' => 'https://ai.example.test/runtime',
                    'timeout_seconds' => 20,
                ],
            ],
            $snapshot->toArray(),
        );
    }

    #[Test]
    public function it_serializes_the_runtime_snapshot(): void
    {
        $definitionB = new AiCapabilityDefinition(
            featureKey: 'sourcing_ai_recommendation',
            capabilityGroup: AiCapabilityGroup::SOURCING_RECOMMENDATION_INTELLIGENCE,
            requiresAi: true,
            hasManualFallback: true,
            fallbackUiMode: AiFallbackUiMode::SHOW_UNAVAILABLE_MESSAGE,
            degradationMessageKey: 'ai.sourcing.recommendation.degraded',
            operatorCritical: false,
        );

        $definitionA = new AiCapabilityDefinition(
            featureKey: 'procurement_ai_quote_summary',
            capabilityGroup: AiCapabilityGroup::DOCUMENT_INTELLIGENCE,
            requiresAi: true,
            hasManualFallback: true,
            fallbackUiMode: AiFallbackUiMode::SHOW_MANUAL_CONTINUITY_BANNER,
            degradationMessageKey: 'ai.procurement.quote_summary.degraded',
            operatorCritical: true,
        );

        $endpoint = new AiEndpointHealthSnapshot(
            endpointGroup: AiEndpointGroup::DOCUMENT,
            health: AiHealth::HEALTHY,
            checkedAt: new DateTimeImmutable('2026-04-23T10:20:00+08:00'),
            reasonCodes: [],
            latencyMs: 120,
            diagnostics: [
                'provider_name' => 'provider-x',
                'endpoint_uri' => 'https://ai.example.test/document',
            ],
        );

        $endpointA = new AiEndpointHealthSnapshot(
            endpointGroup: AiEndpointGroup::DOCUMENT,
            health: AiHealth::HEALTHY,
            checkedAt: new DateTimeImmutable('2026-04-23T10:20:00+08:00'),
            reasonCodes: [],
            latencyMs: 120,
            diagnostics: [
                'provider_name' => 'provider-x',
                'endpoint_uri' => 'https://ai.example.test/document',
            ],
        );

        $endpointB = new AiEndpointHealthSnapshot(
            endpointGroup: AiEndpointGroup::GOVERNANCE,
            health: AiHealth::DEGRADED,
            checkedAt: new DateTimeImmutable('2026-04-23T10:22:00+08:00'),
            reasonCodes: ['provider_timeout'],
            latencyMs: 204,
            diagnostics: [
                'provider_name' => 'provider-y',
                'endpoint_uri' => 'https://ai.example.test/governance',
            ],
        );

        $runtime = new AiRuntimeSnapshot(
            mode: AiMode::PROVIDER,
            globalHealth: AiHealth::DEGRADED,
            capabilityDefinitions: [$definitionB, $definitionA],
            capabilityStatuses: [
                'sourcing_ai_recommendation' => AiCapabilityStatus::fromArray([
                    'status' => AiHealth::DEGRADED,
                    'available' => false,
                    'fallback_ui_mode' => AiFallbackUiMode::SHOW_UNAVAILABLE_MESSAGE,
                    'message_key' => 'ai.sourcing.recommendation.degraded',
                    'reason_codes' => ['provider_timeout'],
                    'operator_critical' => false,
                ]),
                'procurement_ai_quote_summary' => AiCapabilityStatus::fromArray([
                    'status' => AiHealth::DEGRADED,
                    'available' => false,
                    'fallback_ui_mode' => AiFallbackUiMode::SHOW_MANUAL_CONTINUITY_BANNER,
                    'message_key' => 'ai.procurement.quote_summary.degraded',
                    'reason_codes' => ['endpoint_timeout'],
                    'operator_critical' => true,
                ]),
            ],
            endpointGroupHealthSnapshots: [$endpointB, $endpointA],
            reasonCodes: ['provider_timeout'],
            generatedAt: new DateTimeImmutable('2026-04-23T10:25:00+08:00'),
        );

        self::assertSame(
            [
                'mode' => 'provider',
                'global_health' => 'degraded',
                'reason_codes' => ['provider_timeout'],
                'generated_at' => '2026-04-23T10:25:00+08:00',
                'capability_definitions' => [
                    [
                        'feature_key' => 'procurement_ai_quote_summary',
                        'capability_group' => 'document_intelligence',
                        'requires_ai' => true,
                        'has_manual_fallback' => true,
                        'fallback_ui_mode' => 'show_manual_continuity_banner',
                        'degradation_message_key' => 'ai.procurement.quote_summary.degraded',
                        'operator_critical' => true,
                    ],
                    [
                        'feature_key' => 'sourcing_ai_recommendation',
                        'capability_group' => 'sourcing_recommendation_intelligence',
                        'requires_ai' => true,
                        'has_manual_fallback' => true,
                        'fallback_ui_mode' => 'show_unavailable_message',
                        'degradation_message_key' => 'ai.sourcing.recommendation.degraded',
                        'operator_critical' => false,
                    ],
                ],
                'capability_statuses' => [
                    'procurement_ai_quote_summary' => [
                        'status' => 'degraded',
                        'available' => false,
                        'fallback_ui_mode' => 'show_manual_continuity_banner',
                        'message_key' => 'ai.procurement.quote_summary.degraded',
                        'reason_codes' => ['endpoint_timeout'],
                        'operator_critical' => true,
                    ],
                    'sourcing_ai_recommendation' => [
                        'status' => 'degraded',
                        'available' => false,
                        'fallback_ui_mode' => 'show_unavailable_message',
                        'message_key' => 'ai.sourcing.recommendation.degraded',
                        'reason_codes' => ['provider_timeout'],
                        'operator_critical' => false,
                    ],
                ],
                'endpoint_groups' => [
                    [
                        'endpoint_group' => 'document',
                        'health' => 'healthy',
                        'checked_at' => '2026-04-23T10:20:00+08:00',
                        'reason_codes' => [],
                        'latency_ms' => 120,
                        'diagnostics' => [
                            'provider_name' => 'provider-x',
                            'endpoint_uri' => 'https://ai.example.test/document',
                        ],
                    ],
                    [
                        'endpoint_group' => 'governance',
                        'health' => 'degraded',
                        'checked_at' => '2026-04-23T10:22:00+08:00',
                        'reason_codes' => ['provider_timeout'],
                        'latency_ms' => 204,
                        'diagnostics' => [
                            'provider_name' => 'provider-y',
                            'endpoint_uri' => 'https://ai.example.test/governance',
                        ],
                    ],
                ],
            ],
            $runtime->toArray(),
        );
    }

    #[Test]
    public function it_serializes_endpoint_configuration_shape(): void
    {
        $config = new AiEndpointConfig(
            endpointGroup: AiEndpointGroup::GOVERNANCE,
            providerName: 'provider-x',
            endpointUri: 'https://ai.example.test/governance',
            timeoutSeconds: 25,
            enabled: true,
            metadata: [
                'region' => 'ap-southeast-1',
                'owner_team' => 'procurement-ops',
            ],
        );

        self::assertSame(
            [
                'endpoint_group' => 'governance',
                'provider_name' => 'provider-x',
                'endpoint_uri' => 'https://ai.example.test/governance',
                'timeout_seconds' => 25,
                'enabled' => true,
                'metadata' => [
                    'region' => 'ap-southeast-1',
                    'owner_team' => 'procurement-ops',
                ],
            ],
            $config->toArray(),
        );
    }

    #[Test]
    public function it_throws_a_package_exception_for_invalid_capability_status_shape(): void
    {
        $this->expectException(AiRuntimeContractException::class);

        AiCapabilityStatus::fromArray([
            'status' => AiHealth::HEALTHY,
            'available' => true,
            'fallback_ui_mode' => AiFallbackUiMode::HIDE_AI_CONTROLS,
            'reason_codes' => [],
            'operator_critical' => false,
        ]);
    }

    #[Test]
    public function it_throws_a_package_exception_for_invalid_mode_values(): void
    {
        $this->expectException(AiRuntimeContractException::class);

        AiMode::fromConfig('unsupported-mode');
    }
}
