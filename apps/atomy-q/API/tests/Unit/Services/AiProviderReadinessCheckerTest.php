<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Services\Ai\AiProviderCheckFinding;
use App\Services\Ai\AiProviderCheckSeverity;
use App\Services\Ai\AiProviderEndpointCheck;
use App\Services\Ai\AiProviderReadinessChecker;
use App\Services\Ai\AiProviderReadinessResult;
use App\Services\Ai\Contracts\AiOperationalAlertPublisherInterface;
use App\Services\Ai\Contracts\ProviderContractVerifierInterface;
use App\Services\Ai\ProviderContractVerificationResult;
use DateTimeImmutable;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\MachineLearning\Contracts\AiHealthProbeInterface;
use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\MachineLearning\Enums\AiHealth;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Nexus\MachineLearning\ValueObjects\AiEndpointHealthSnapshot;
use RuntimeException;
use Tests\TestCase;

final class AiProviderReadinessCheckerTest extends TestCase
{
    public function test_readiness_result_rolls_up_failed_severity_and_serializes_payload(): void
    {
        $result = new AiProviderReadinessResult(
            checkedAt: '2026-05-02T00:00:00+00:00',
            mode: 'provider',
            provider: 'openrouter',
            deep: false,
            endpointGroups: [
                new AiProviderEndpointCheck(
                    endpointGroup: 'document',
                    configured: true,
                    enabled: true,
                    endpointUri: 'https://openrouter.ai/api/v1/chat/completions',
                    probeHealth: 'unavailable',
                    latencyMs: 120,
                    severity: AiProviderCheckSeverity::FAILED,
                    reasonCodes: ['health_probe_failed'],
                    diagnostics: ['provider_name' => 'openrouter'],
                ),
            ],
            operatorFindings: [
                new AiProviderCheckFinding(
                    severity: AiProviderCheckSeverity::WARNING,
                    area: 'security',
                    message: 'Endpoint [document] uses plain HTTP outside local development.',
                    endpointGroup: 'document',
                    reasonCode: 'plain_http_endpoint',
                ),
            ],
            publishedAlerts: [],
        );

        self::assertSame(AiProviderCheckSeverity::FAILED, $result->exitSeverity());

        $payload = $result->toArray();
        self::assertSame('provider', $payload['mode']);
        self::assertSame('openrouter', $payload['provider']);
        self::assertFalse($payload['deep']);
        self::assertSame(AiProviderCheckSeverity::FAILED, $payload['global_status']);
        self::assertSame(AiProviderCheckSeverity::FAILED, $payload['exit_severity']);
        self::assertSame('document', $payload['endpoint_groups'][0]['endpoint_group']);
        self::assertSame('security', $payload['operator_findings'][0]['area']);
    }

    public function test_safe_check_marks_off_mode_endpoints_as_skipped_without_deep_verifier(): void
    {
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('off', null),
            healthProbe: new FakeHealthProbe,
            runtimeStatus: FakeRuntimeStatus::withMode('off'),
            contractVerifier: new FailingContractVerifier,
        );

        $result = $checker->check(
            endpointGroups: ['document'],
            deep: false,
            publishAlerts: false,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertSame(AiProviderCheckSeverity::SKIPPED, $result->endpointGroups[0]->severity);
        self::assertSame(['ai_disabled_by_config'], $result->endpointGroups[0]->reasonCodes);
    }

    public function test_safe_check_marks_deterministic_mode_endpoints_as_skipped(): void
    {
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('deterministic', null),
            healthProbe: new FakeHealthProbe,
            runtimeStatus: FakeRuntimeStatus::withMode('deterministic'),
            contractVerifier: new FailingContractVerifier,
        );

        $result = $checker->check(
            endpointGroups: ['document'],
            deep: false,
            publishAlerts: false,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertSame(AiProviderCheckSeverity::SKIPPED, $result->endpointGroups[0]->severity);
        self::assertSame(['deterministic_fallback_mode'], $result->endpointGroups[0]->reasonCodes);
    }

    public function test_empty_endpoint_groups_default_to_registry_endpoint_groups(): void
    {
        $verifier = new RecordingContractVerifier([]);
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('off', null, ['document', 'normalization']),
            healthProbe: new FakeHealthProbe,
            runtimeStatus: FakeRuntimeStatus::withMode('off'),
            contractVerifier: $verifier,
        );

        $result = $checker->check(
            endpointGroups: [],
            deep: true,
            publishAlerts: false,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertSame(['document', 'normalization'], array_map(
            static fn (AiProviderEndpointCheck $endpointCheck): string => $endpointCheck->endpointGroup,
            $result->endpointGroups,
        ));
        self::assertSame([['document', 'normalization'], 'plan6-tenant', 'plan6-rfq'], $verifier->verifiedWith);
    }

    public function test_safe_check_reports_missing_provider_endpoint_as_failed(): void
    {
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('provider', null),
            healthProbe: new FakeHealthProbe,
            runtimeStatus: FakeRuntimeStatus::withMode('provider'),
            contractVerifier: new FailingContractVerifier,
        );

        $result = $checker->check(
            endpointGroups: ['document'],
            deep: false,
            publishAlerts: false,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertSame(AiProviderCheckSeverity::FAILED, $result->endpointGroups[0]->severity);
        self::assertSame(['endpoint_not_configured'], $result->endpointGroups[0]->reasonCodes);
    }

    public function test_safe_check_uses_probe_health_and_warns_about_plain_http_without_leaking_token(): void
    {
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('provider', $this->endpointConfig(
                endpointUri: 'http://provider.example.test/ai',
                timeoutSeconds: 10,
                metadata: ['auth_token' => 'secret-token'],
            )),
            healthProbe: new FakeHealthProbe(AiHealth::HEALTHY),
            runtimeStatus: FakeRuntimeStatus::withMode('provider'),
            contractVerifier: new FailingContractVerifier,
        );

        $result = $checker->check(
            endpointGroups: ['document'],
            deep: false,
            publishAlerts: false,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertSame(AiProviderCheckSeverity::OK, $result->endpointGroups[0]->severity);
        self::assertSame('healthy', $result->endpointGroups[0]->probeHealth);
        self::assertSame('plain_http_endpoint', $result->operatorFindings[0]->reasonCode);
        self::assertStringNotContainsString(
            'secret-token',
            json_encode($result->toArray(), JSON_THROW_ON_ERROR),
        );
    }

    public function test_endpoint_uri_query_secrets_are_redacted_without_removing_safe_query_params(): void
    {
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('provider', $this->endpointConfig(
                endpointUri: 'https://provider.example.test/ai?api_key=secret-token&model=x',
                timeoutSeconds: 10,
                metadata: ['auth_token' => 'configured-token'],
            )),
            healthProbe: new FakeHealthProbe(AiHealth::HEALTHY),
            runtimeStatus: FakeRuntimeStatus::withMode('provider'),
            contractVerifier: new FailingContractVerifier,
        );

        $result = $checker->check(
            endpointGroups: ['document'],
            deep: false,
            publishAlerts: false,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertSame('https://provider.example.test/ai?api_key=[redacted]&model=x', $result->endpointGroups[0]->endpointUri);
        self::assertStringNotContainsString(
            'secret-token',
            json_encode($result->toArray(), JSON_THROW_ON_ERROR),
        );
    }

    public function test_endpoint_uri_fragments_are_dropped_without_leaking_fragment_secrets(): void
    {
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('provider', $this->endpointConfig(
                endpointUri: 'https://provider.example.test/callback#access_token=secret-token',
                timeoutSeconds: 10,
                metadata: ['auth_token' => 'configured-token'],
            )),
            healthProbe: new FakeHealthProbe(AiHealth::HEALTHY),
            runtimeStatus: FakeRuntimeStatus::withMode('provider'),
            contractVerifier: new FailingContractVerifier,
        );

        $result = $checker->check(
            endpointGroups: ['document'],
            deep: false,
            publishAlerts: false,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertSame('https://provider.example.test/callback', $result->endpointGroups[0]->endpointUri);
        self::assertStringNotContainsString(
            'secret-token',
            json_encode($result->toArray(), JSON_THROW_ON_ERROR),
        );
    }

    public function test_malformed_endpoint_uri_uses_placeholder_without_leaking_raw_token(): void
    {
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('provider', $this->endpointConfig(
                endpointUri: 'http:///path?token=secret-token',
                timeoutSeconds: 10,
                metadata: ['auth_token' => 'configured-token'],
            )),
            healthProbe: new FakeHealthProbe(AiHealth::HEALTHY),
            runtimeStatus: FakeRuntimeStatus::withMode('provider'),
            contractVerifier: new FailingContractVerifier,
        );

        $result = $checker->check(
            endpointGroups: ['document'],
            deep: false,
            publishAlerts: false,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertSame('[invalid-endpoint-uri]', $result->endpointGroups[0]->endpointUri);
        self::assertStringNotContainsString(
            'secret-token',
            json_encode($result->toArray(), JSON_THROW_ON_ERROR),
        );
    }

    public function test_safe_check_reports_missing_token_and_low_timeout_warnings(): void
    {
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('provider', $this->endpointConfig(
                endpointUri: 'https://provider.example.test/ai',
                timeoutSeconds: 2,
                metadata: [],
            )),
            healthProbe: new FakeHealthProbe(AiHealth::DEGRADED),
            runtimeStatus: FakeRuntimeStatus::withMode('provider'),
            contractVerifier: new FailingContractVerifier,
        );

        $result = $checker->check(
            endpointGroups: ['document'],
            deep: false,
            publishAlerts: false,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertSame(AiProviderCheckSeverity::WARNING, $result->endpointGroups[0]->severity);
        self::assertSame(['missing_auth_token', 'low_timeout_seconds'], array_map(
            static fn (AiProviderCheckFinding $finding): ?string => $finding->reasonCode,
            $result->operatorFindings,
        ));
    }

    public function test_deep_check_appends_verifier_findings(): void
    {
        $verifier = new RecordingContractVerifier([
            new ProviderContractVerificationResult(
                endpointGroup: 'document',
                severity: AiProviderCheckSeverity::FAILED,
                verified: false,
                reasonCodes: ['provider_invalid_payload'],
                message: 'Provider contract verification failed for document.',
            ),
        ]);
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('provider', $this->endpointConfig()),
            healthProbe: new FakeHealthProbe(AiHealth::HEALTHY),
            runtimeStatus: FakeRuntimeStatus::withMode('provider'),
            contractVerifier: $verifier,
        );

        $result = $checker->check(
            endpointGroups: ['document'],
            deep: true,
            publishAlerts: false,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertSame([['document'], 'plan6-tenant', 'plan6-rfq'], $verifier->verifiedWith);
        self::assertSame('deep_contract', $result->operatorFindings[0]->area);
        self::assertSame('provider_invalid_payload', $result->operatorFindings[0]->reasonCode);
    }

    public function test_publish_alerts_includes_published_alerts_when_publisher_is_available(): void
    {
        $publisher = new FakeAlertPublisher([['type' => 'ai_provider_check', 'status' => 'published']]);
        $checker = new AiProviderReadinessChecker(
            endpointRegistry: new FakeEndpointRegistry('provider', $this->endpointConfig()),
            healthProbe: new FakeHealthProbe(AiHealth::HEALTHY),
            runtimeStatus: FakeRuntimeStatus::withMode('provider'),
            contractVerifier: new FailingContractVerifier,
            alertPublisher: $publisher,
        );

        $result = $checker->check(
            endpointGroups: ['document'],
            deep: false,
            publishAlerts: true,
            tenantId: 'plan6-tenant',
            rfqId: 'plan6-rfq',
        );

        self::assertTrue($publisher->published);
        self::assertSame([['type' => 'ai_provider_check', 'status' => 'published']], $result->publishedAlerts);
    }

    /**
     * @param  array<string, scalar|null>  $metadata
     */
    private function endpointConfig(
        string $endpointUri = 'https://provider.example.test/ai',
        int $timeoutSeconds = 10,
        array $metadata = ['auth_token' => 'secret-token'],
    ): AiEndpointConfig {
        return new AiEndpointConfig(
            endpointGroup: AiEndpointGroup::DOCUMENT,
            providerName: 'openrouter',
            endpointUri: $endpointUri,
            timeoutSeconds: $timeoutSeconds,
            enabled: true,
            metadata: $metadata,
        );
    }
}

final readonly class FakeEndpointRegistry implements AiEndpointRegistryInterface
{
    /**
     * @param  list<string>  $endpointGroups
     */
    public function __construct(
        private string $mode,
        private ?AiEndpointConfig $config,
        private array $endpointGroups = ['document'],
    ) {}

    public function mode(): string
    {
        return $this->mode;
    }

    public function providerName(): string
    {
        return 'openrouter';
    }

    public function endpointGroups(): array
    {
        return $this->endpointGroups;
    }

    public function endpointConfig(string $endpointGroup): ?AiEndpointConfig
    {
        return $endpointGroup === 'document' ? $this->config : null;
    }
}

final readonly class FakeHealthProbe implements AiHealthProbeInterface
{
    public function __construct(private AiHealth $health = AiHealth::UNAVAILABLE) {}

    public function probe(AiEndpointConfig $endpointConfig): AiEndpointHealthSnapshot
    {
        return new AiEndpointHealthSnapshot(
            endpointGroup: $endpointConfig->endpointGroup,
            health: $this->health,
            checkedAt: new DateTimeImmutable('2026-05-02T00:00:00+00:00'),
            reasonCodes: [$this->health === AiHealth::HEALTHY ? 'provider_available' : 'health_probe_failed'],
            latencyMs: 25,
            diagnostics: ['provider_name' => $endpointConfig->providerName],
        );
    }
}

final readonly class FakeRuntimeStatus implements AiRuntimeStatusInterface
{
    public function __construct(private AiStatusSnapshot $snapshot) {}

    public static function withMode(string $mode): self
    {
        return new self(new AiStatusSnapshot(
            mode: $mode,
            globalHealth: $mode === AiStatusSchema::MODE_PROVIDER
                ? AiStatusSchema::HEALTH_HEALTHY
                : AiStatusSchema::HEALTH_DISABLED,
            capabilityDefinitions: [],
            capabilityStatuses: [],
            endpointGroupHealthSnapshots: [],
            reasonCodes: [],
            generatedAt: new DateTimeImmutable('2026-05-02T00:00:00+00:00'),
        ));
    }

    public function snapshot(): AiStatusSnapshot
    {
        return $this->snapshot;
    }

    public function capabilityStatus(string $featureKey): ?AiCapabilityStatus
    {
        return null;
    }

    public function providerName(): string
    {
        return 'openrouter';
    }
}

final class FailingContractVerifier implements ProviderContractVerifierInterface
{
    public function assertEndpointGroups(array $endpointGroups): void {}

    public function verify(array $endpointGroups, string $tenantId, string $rfqId): array
    {
        throw new RuntimeException('Deep verifier should not run during safe checks.');
    }
}

final class RecordingContractVerifier implements ProviderContractVerifierInterface
{
    /**
     * @var array{list<string>, string, string}|null
     */
    public ?array $verifiedWith = null;

    /**
     * @param  list<ProviderContractVerificationResult>  $results
     */
    public function __construct(private array $results) {}

    public function assertEndpointGroups(array $endpointGroups): void {}

    public function verify(array $endpointGroups, string $tenantId, string $rfqId): array
    {
        $this->verifiedWith = [$endpointGroups, $tenantId, $rfqId];

        return $this->results;
    }
}

final class FakeAlertPublisher implements AiOperationalAlertPublisherInterface
{
    public bool $published = false;

    /**
     * @param  list<array<string, mixed>>  $alerts
     */
    public function __construct(private array $alerts) {}

    public function publishSnapshot(AiStatusSnapshot $snapshot): array
    {
        $this->published = true;

        return $this->alerts;
    }
}
