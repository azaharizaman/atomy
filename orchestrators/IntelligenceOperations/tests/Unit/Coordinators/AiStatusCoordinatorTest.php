<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Tests\Unit\Coordinators;

use DateTimeImmutable;
use Nexus\IntelligenceOperations\Coordinators\AiStatusCoordinator;
use Nexus\IntelligenceOperations\Contracts\AiCapabilityCatalogInterface;
use Nexus\IntelligenceOperations\Contracts\AiStatusCoordinatorInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityDefinition;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiEndpointHealthSnapshot;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\Exceptions\AiStatusContractException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AiStatusCoordinatorTest extends TestCase
{
    #[Test]
    public function it_aggregates_partial_endpoint_failure_without_leaking_endpoint_secrets(): void
    {
        $coordinator = $this->buildCoordinator([
            'procurement_ai_quote_summary' => new AiCapabilityDefinition(
                featureKey: 'procurement_ai_quote_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.procurement.quote_summary.degraded',
                operatorCritical: true,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
            ),
            'normalization_ai_autofill' => new AiCapabilityDefinition(
                featureKey: 'normalization_ai_autofill',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_NORMALIZATION_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: false,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                degradationMessageKey: 'ai.normalization.autofill.degraded',
                operatorCritical: false,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_NORMALIZATION,
            ),
            'governance_ai_review' => new AiCapabilityDefinition(
                featureKey: 'governance_ai_review',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.governance.review.degraded',
                operatorCritical: false,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
            ),
        ]);

        $documentSnapshot = new AiEndpointHealthSnapshot(
            endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
            health: AiStatusSchema::HEALTH_HEALTHY,
            checkedAt: new DateTimeImmutable('2026-04-23T10:00:00+08:00'),
            reasonCodes: ['health_probe_timeout', 'eyJhbGciOiJIUzI1NiJ9', 'eyjhbgcioijiuzi1nij9', 'eyjhbGciOiJIUzI1NiJ9', 'mF_9.B5f-4.1JqM'],
            latencyMs: 120,
            diagnostics: [
                'provider_name' => 'provider-a',
                'endpoint_uri' => 'https://secret.example.test/document',
                'auth_token' => 'token-should-not-leak',
            ],
        );
        $normalizationSnapshot = new AiEndpointHealthSnapshot(
            endpointGroup: AiStatusSchema::ENDPOINT_GROUP_NORMALIZATION,
            health: AiStatusSchema::HEALTH_UNAVAILABLE,
            checkedAt: new DateTimeImmutable('2026-04-23T10:00:05+08:00'),
            reasonCodes: ['health_probe_timeout', 'endpoint/key=unsafe', 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0In0'],
            latencyMs: 890,
            diagnostics: [
                'provider_name' => 'provider-b',
                'endpoint_uri' => 'https://secret.example.test/normalization',
                'auth_token' => 'token-should-not-leak',
            ],
        );
        $governanceSnapshot = new AiEndpointHealthSnapshot(
            endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
            health: AiStatusSchema::HEALTH_HEALTHY,
            checkedAt: new DateTimeImmutable('2026-04-23T10:00:10+08:00'),
            reasonCodes: [],
            latencyMs: 90,
            diagnostics: [
                'provider_name' => 'provider-c',
                'endpoint_uri' => 'https://secret.example.test/governance',
                'auth_token' => 'token-should-not-leak',
            ],
        );

        self::assertSame(['health_probe_timeout', 'endpoint_reason_redacted'], $documentSnapshot->reasonCodes);
        self::assertSame(['provider_name' => 'provider-a'], $documentSnapshot->diagnostics);
        self::assertSame(['health_probe_timeout', 'endpoint_reason_redacted'], $normalizationSnapshot->reasonCodes);
        self::assertSame(['provider_name' => 'provider-b'], $normalizationSnapshot->diagnostics);

        $snapshot = $coordinator->snapshot(
            AiStatusSchema::MODE_PROVIDER,
            [
                $documentSnapshot,
                $normalizationSnapshot,
                $governanceSnapshot,
            ],
            new DateTimeImmutable('2026-04-23T10:05:00+08:00'),
        );

        $array = $snapshot->toArray();

        self::assertSame(AiStatusSchema::MODE_PROVIDER, $array['mode']);
        self::assertSame(AiStatusSchema::HEALTH_DEGRADED, $array['global_health']);
        self::assertContains('provider_unavailable', $array['reason_codes']);
        self::assertSame('available', $array['capability_statuses']['procurement_ai_quote_summary']['status']);
        self::assertTrue($array['capability_statuses']['procurement_ai_quote_summary']['available']);
        self::assertSame('unavailable', $array['capability_statuses']['normalization_ai_autofill']['status']);
        self::assertFalse($array['capability_statuses']['normalization_ai_autofill']['available']);
        self::assertSame('available', $array['capability_statuses']['governance_ai_review']['status']);

        self::assertArrayNotHasKey('endpoint_uri', $array['endpoint_groups'][0]['diagnostics']);
        self::assertArrayNotHasKey('auth_token', $array['endpoint_groups'][0]['diagnostics']);
        self::assertContains('endpoint_reason_redacted', $array['endpoint_groups'][0]['reason_codes']);
        self::assertNotContains('eyJhbGciOiJIUzI1NiJ9', $array['endpoint_groups'][0]['reason_codes']);
        self::assertNotContains('eyjhbgcioijiuzi1nij9', $array['endpoint_groups'][0]['reason_codes']);
        self::assertNotContains('eyjhbGciOiJIUzI1NiJ9', $array['endpoint_groups'][0]['reason_codes']);
        self::assertNotContains('mF_9.B5f-4.1JqM', $array['endpoint_groups'][0]['reason_codes']);
        self::assertNotContains('endpoint/key=unsafe', $array['endpoint_groups'][1]['reason_codes']);
        self::assertNotContains('eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0In0', $array['endpoint_groups'][1]['reason_codes']);
        self::assertContains('endpoint_reason_redacted', $array['endpoint_groups'][2]['reason_codes']);
    }

    #[Test]
    public function it_sanitizes_snapshot_level_reason_codes(): void
    {
        $snapshot = new AiStatusSnapshot(
            mode: AiStatusSchema::MODE_PROVIDER,
            globalHealth: AiStatusSchema::HEALTH_HEALTHY,
            capabilityDefinitions: [],
            capabilityStatuses: [],
            endpointGroupHealthSnapshots: [],
            reasonCodes: ['eyJhbGciOiJIUzI1NiJ9', 'eyjhbgcioijiuzi1nij9', 'provider_available'],
            generatedAt: new DateTimeImmutable('2026-04-23T09:00:00+00:00'),
        );

        $array = $snapshot->toArray();

        self::assertContains('provider_available', $array['reason_codes']);
        self::assertContains('endpoint_reason_redacted', $array['reason_codes']);
        self::assertNotContains('eyJhbGciOiJIUzI1NiJ9', $array['reason_codes']);
        self::assertNotContains('eyjhbgcioijiuzi1nij9', $array['reason_codes']);
    }

    #[Test]
    public function it_redacts_unsafe_allowed_diagnostic_values_before_assignment(): void
    {
        $snapshot = new AiEndpointHealthSnapshot(
            endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
            health: AiStatusSchema::HEALTH_HEALTHY,
            checkedAt: new DateTimeImmutable('2026-04-23T09:30:00+00:00'),
            reasonCodes: ['health_probe_failed'],
            latencyMs: 15,
            diagnostics: [
                'provider_name' => " https://secret.example.test/token\nnext ",
                'requires_ai' => true,
                'status' => 'healthy',
                'latency_ms' => 15,
            ],
        );

        self::assertSame(AiStatusSchema::DIAGNOSTIC_VALUE_REDACTED, $snapshot->diagnostics['provider_name']);
        self::assertTrue($snapshot->diagnostics['requires_ai']);
        self::assertSame('healthy', $snapshot->diagnostics['status']);
        self::assertSame(15, $snapshot->diagnostics['latency_ms']);

        $array = $snapshot->toArray();

        self::assertSame(AiStatusSchema::DIAGNOSTIC_VALUE_REDACTED, $array['diagnostics']['provider_name']);
        self::assertTrue($array['diagnostics']['requires_ai']);
        self::assertSame('healthy', $array['diagnostics']['status']);
        self::assertSame(15, $array['diagnostics']['latency_ms']);
    }

    #[Test]
    public function it_rejects_duplicate_capability_feature_keys(): void
    {
        $catalog = new class implements AiCapabilityCatalogInterface {
            public function all(): array
            {
                return [
                    new AiCapabilityDefinition(
                        featureKey: 'duplicate_feature',
                        capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                        requiresAi: true,
                        hasManualFallback: true,
                        fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                        degradationMessageKey: 'ai.duplicate.one',
                        operatorCritical: false,
                        endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                    ),
                    new AiCapabilityDefinition(
                        featureKey: 'duplicate_feature',
                        capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                        requiresAi: true,
                        hasManualFallback: false,
                        fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                        degradationMessageKey: 'ai.duplicate.two',
                        operatorCritical: false,
                        endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
                    ),
                ];
            }

            public function findByFeatureKey(string $featureKey): ?AiCapabilityDefinition
            {
                return null;
            }
        };

        $coordinator = new AiStatusCoordinator($catalog);

        $this->expectException(AiStatusContractException::class);
        $coordinator->snapshot(AiStatusSchema::MODE_PROVIDER, []);
    }

    #[Test]
    public function it_ignores_unused_endpoint_groups_when_computing_global_health(): void
    {
        $coordinator = $this->buildCoordinator([
            'procurement_ai_quote_summary' => new AiCapabilityDefinition(
                featureKey: 'procurement_ai_quote_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.procurement.quote_summary.degraded',
                operatorCritical: true,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
            ),
        ]);

        $snapshot = $coordinator->snapshot(
            AiStatusSchema::MODE_PROVIDER,
            [
                new AiEndpointHealthSnapshot(
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                    health: AiStatusSchema::HEALTH_HEALTHY,
                    checkedAt: new DateTimeImmutable('2026-04-23T14:00:00+08:00'),
                    reasonCodes: ['provider_timeout'],
                    latencyMs: 55,
                    diagnostics: ['endpoint_uri' => 'https://secret.example.test/document'],
                ),
                new AiEndpointHealthSnapshot(
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_SOURCING_RECOMMENDATION,
                    health: AiStatusSchema::HEALTH_UNAVAILABLE,
                    checkedAt: new DateTimeImmutable('2026-04-23T14:00:05+08:00'),
                    reasonCodes: ['provider_timeout', 'endpoint/key=unsafe'],
                    latencyMs: 970,
                    diagnostics: ['endpoint_uri' => 'https://secret.example.test/unused'],
                ),
            ],
        );

        $array = $snapshot->toArray();

        self::assertSame(AiStatusSchema::HEALTH_HEALTHY, $array['global_health']);
        self::assertSame('available', $array['capability_statuses']['procurement_ai_quote_summary']['status']);
        self::assertContains('provider_available', $array['reason_codes']);
        self::assertContains('endpoint_reason_redacted', $array['endpoint_groups'][1]['reason_codes']);
    }

    #[Test]
    public function it_disables_every_capability_in_off_mode_without_using_endpoint_diagnostics(): void
    {
        $coordinator = $this->buildCoordinator([
            'procurement_ai_quote_summary' => new AiCapabilityDefinition(
                featureKey: 'procurement_ai_quote_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.procurement.quote_summary.degraded',
                operatorCritical: true,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
            ),
            'governance_ai_review' => new AiCapabilityDefinition(
                featureKey: 'governance_ai_review',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: false,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                degradationMessageKey: 'ai.governance.review.degraded',
                operatorCritical: false,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
            ),
        ]);

        $snapshot = $coordinator->snapshot(
            AiStatusSchema::MODE_OFF,
            [
                new AiEndpointHealthSnapshot(
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                    health: AiStatusSchema::HEALTH_HEALTHY,
                    checkedAt: new DateTimeImmutable('2026-04-23T11:00:00+08:00'),
                    reasonCodes: ['provider_name=provider-a'],
                    latencyMs: 12,
                    diagnostics: [
                        'endpoint_uri' => 'https://secret.example.test/off-mode',
                        'client_secret' => 'super-secret',
                    ],
                ),
            ],
            new DateTimeImmutable('2026-04-23T11:05:00+08:00'),
        );

        $array = $snapshot->toArray();

        self::assertSame(AiStatusSchema::MODE_OFF, $array['mode']);
        self::assertSame(AiStatusSchema::HEALTH_DISABLED, $array['global_health']);
        self::assertSame(['ai_disabled_by_config'], $array['reason_codes']);
        self::assertSame(AiCapabilityStatus::STATUS_DISABLED, $array['capability_statuses']['procurement_ai_quote_summary']['status']);
        self::assertSame(AiCapabilityStatus::STATUS_DISABLED, $array['capability_statuses']['governance_ai_review']['status']);
        self::assertFalse($array['capability_statuses']['governance_ai_review']['available']);
        self::assertArrayNotHasKey('endpoint_uri', $array['endpoint_groups'][0]['diagnostics']);
        self::assertArrayNotHasKey('client_secret', $array['endpoint_groups'][0]['diagnostics']);
        self::assertSame(['endpoint_reason_redacted'], $array['endpoint_groups'][0]['reason_codes']);
    }

    #[Test]
    public function it_keeps_deterministic_fallback_usable_for_manual_capabilities_only(): void
    {
        $coordinator = $this->buildCoordinator([
            'manual_continuity_feature' => new AiCapabilityDefinition(
                featureKey: 'manual_continuity_feature',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.manual_continuity.degraded',
                operatorCritical: false,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
            ),
            'ai_only_feature' => new AiCapabilityDefinition(
                featureKey: 'ai_only_feature',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                requiresAi: true,
                hasManualFallback: false,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                degradationMessageKey: 'ai.ai_only.unavailable',
                operatorCritical: false,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
            ),
        ]);

        $snapshot = $coordinator->snapshot(
            AiStatusSchema::MODE_DETERMINISTIC,
            [
                new AiEndpointHealthSnapshot(
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                    health: AiStatusSchema::HEALTH_HEALTHY,
                    checkedAt: new DateTimeImmutable('2026-04-23T12:00:00+08:00'),
                    reasonCodes: [],
                    latencyMs: 30,
                    diagnostics: ['endpoint_uri' => 'https://secret.example.test/deterministic'],
                ),
                new AiEndpointHealthSnapshot(
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
                    health: AiStatusSchema::HEALTH_HEALTHY,
                    checkedAt: new DateTimeImmutable('2026-04-23T12:00:05+08:00'),
                    reasonCodes: [],
                    latencyMs: 28,
                    diagnostics: ['endpoint_uri' => 'https://secret.example.test/deterministic-governance'],
                ),
            ],
            new DateTimeImmutable('2026-04-23T12:05:00+08:00'),
        );

        $array = $snapshot->toArray();

        self::assertSame(AiStatusSchema::MODE_DETERMINISTIC, $array['mode']);
        self::assertSame(AiStatusSchema::HEALTH_DEGRADED, $array['global_health']);
        self::assertContains('deterministic_fallback_mode', $array['reason_codes']);
        self::assertContains('manual_fallback_available', $array['reason_codes']);
        self::assertSame(AiCapabilityStatus::STATUS_DEGRADED, $array['capability_statuses']['manual_continuity_feature']['status']);
        self::assertFalse($array['capability_statuses']['manual_continuity_feature']['available']);
        self::assertContains('deterministic_fallback_mode', $array['capability_statuses']['manual_continuity_feature']['reason_codes']);
        self::assertContains('manual_fallback_available', $array['capability_statuses']['manual_continuity_feature']['reason_codes']);
        self::assertSame(AiCapabilityStatus::STATUS_UNAVAILABLE, $array['capability_statuses']['ai_only_feature']['status']);
        self::assertFalse($array['capability_statuses']['ai_only_feature']['available']);
        self::assertContains('deterministic_fallback_mode', $array['capability_statuses']['ai_only_feature']['reason_codes']);
        self::assertContains('provider_unavailable', $array['capability_statuses']['ai_only_feature']['reason_codes']);
    }

    #[Test]
    public function it_keeps_non_ai_capabilities_available_when_ai_is_off(): void
    {
        $coordinator = $this->buildCoordinator([
            'manual_workflow_feature' => new AiCapabilityDefinition(
                featureKey: 'manual_workflow_feature',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                requiresAi: false,
                hasManualFallback: true,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                degradationMessageKey: 'ai.manual_workflow.available',
                operatorCritical: false,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
            ),
        ]);

        $snapshot = $coordinator->snapshot(
            AiStatusSchema::MODE_OFF,
            [
                new AiEndpointHealthSnapshot(
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_DOCUMENT,
                    health: AiStatusSchema::HEALTH_UNAVAILABLE,
                    checkedAt: new DateTimeImmutable('2026-04-23T13:00:00+08:00'),
                    reasonCodes: ['endpoint/key=unsafe', 'provider_timeout'],
                    latencyMs: 61,
                    diagnostics: [
                        'endpoint_uri' => 'https://secret.example.test/manual',
                        'auth_token' => 'token-should-not-leak',
                    ],
                ),
            ],
        );

        $array = $snapshot->toArray();

        self::assertSame(AiStatusSchema::MODE_OFF, $array['mode']);
        self::assertSame(AiStatusSchema::HEALTH_DISABLED, $array['global_health']);
        self::assertStringEndsWith('+00:00', $array['generated_at']);
        self::assertSame(AiCapabilityStatus::STATUS_AVAILABLE, $array['capability_statuses']['manual_workflow_feature']['status']);
        self::assertTrue($array['capability_statuses']['manual_workflow_feature']['available']);
        self::assertContains('ai_not_required', $array['capability_statuses']['manual_workflow_feature']['reason_codes']);
        self::assertNotContains('provider_timeout', $array['capability_statuses']['manual_workflow_feature']['reason_codes']);
        self::assertContains('ai_not_required', $array['reason_codes']);
        self::assertContains('ai_disabled_by_config', $array['reason_codes']);
    }

    /**
     * @param array<string, AiCapabilityDefinition> $definitions
     */
    private function buildCoordinator(array $definitions): AiStatusCoordinatorInterface
    {
        $catalog = new class ($definitions) implements AiCapabilityCatalogInterface {
            /**
             * @param array<string, AiCapabilityDefinition> $definitions
             */
            public function __construct(
                private readonly array $definitions,
            ) {
            }

            public function all(): array
            {
                return array_values($this->definitions);
            }

            public function findByFeatureKey(string $featureKey): ?AiCapabilityDefinition
            {
                return $this->definitions[$featureKey] ?? null;
            }
        };

        return new AiStatusCoordinator($catalog);
    }
}
