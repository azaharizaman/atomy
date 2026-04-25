<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;
use App\Adapters\Ai\Exceptions\AiTransportFailedException;
use App\Adapters\Ai\Exceptions\AiTransportInvalidResponseException;
use App\Adapters\Ai\Exceptions\AiTransportUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Log\LogManager;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;

final readonly class ProviderAiTransport implements ProviderAiTransportInterface
{
    private const MAX_RETRY_ATTEMPTS = 5;

    private const MAX_RETRY_BACKOFF_MS = 5000;

    public function __construct(
        private AiEndpointRegistryInterface $endpointRegistry,
        private HttpFactory $http,
        private LogManager $logs,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function invoke(string $endpointGroup, array $payload): array
    {
        $endpointConfig = $this->endpointRegistry->endpointConfig($endpointGroup);
        if ($endpointConfig === null || $endpointConfig->enabled === false) {
            $this->logOutcome($endpointGroup, $payload, null, 'unavailable', 'endpoint_not_configured', 0);
            throw new AiTransportUnavailableException(sprintf('AI endpoint [%s] is unavailable.', $endpointGroup));
        }

        $request = $this->http
            ->acceptJson()
            ->asJson()
            ->timeout($endpointConfig->timeoutSeconds);

        $authToken = $endpointConfig->metadata['auth_token'] ?? null;
        if (is_string($authToken) && trim($authToken) !== '') {
            $request = $request->withToken(trim($authToken));
        }

        $retryAttempts = min(self::MAX_RETRY_ATTEMPTS, max(1, (int) ($endpointConfig->metadata['retry_attempts'] ?? 1)));
        $retryBackoffMs = min(self::MAX_RETRY_BACKOFF_MS, max(0, (int) ($endpointConfig->metadata['retry_backoff_ms'] ?? 0)));
        $startedAt = hrtime(true);
        $response = null;
        $lastReasonCode = 'provider_unavailable';

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                $response = $request->post($endpointConfig->endpointUri, $payload);
            } catch (ConnectionException $exception) {
                $lastReasonCode = 'provider_timeout';
                if ($attempt < $retryAttempts) {
                    usleep($retryBackoffMs * 1000);
                    continue;
                }

                $this->logOutcome(
                    $endpointGroup,
                    $payload,
                    $endpointConfig,
                    'failed',
                    $lastReasonCode,
                    $this->latencyMs($startedAt),
                    [
                        'attempts' => $attempt,
                        'error_type' => $exception::class,
                        'error_code' => is_int($exception->getCode()) ? $exception->getCode() : 0,
                    ],
                );

                throw new AiTransportUnavailableException(
                    sprintf('AI endpoint [%s] is unavailable.', $endpointGroup),
                    0,
                    $exception,
                );
            }

            if ($response->successful()) {
                break;
            }

            $lastReasonCode = $this->reasonCodeForResponse($response);
            if ($attempt < $retryAttempts && $this->shouldRetry($response)) {
                usleep($retryBackoffMs * 1000);
                continue;
            }

            break;
        }

        if (! $response instanceof Response || ! $response->successful()) {
            $this->logOutcome(
                $endpointGroup,
                $payload,
                $endpointConfig,
                'failed',
                $lastReasonCode,
                $this->latencyMs($startedAt),
                [
                    'attempts' => $retryAttempts,
                    'http_status' => $response?->status(),
                ],
            );

            throw new AiTransportFailedException(sprintf('AI endpoint [%s] returned an unsuccessful response.', $endpointGroup));
        }

        $data = $response->json();
        if (!is_array($data) || array_is_list($data)) {
            $this->logOutcome(
                $endpointGroup,
                $payload,
                $endpointConfig,
                'failed',
                'provider_invalid_payload',
                $this->latencyMs($startedAt),
                [
                    'http_status' => $response->status(),
                ],
            );

            throw new AiTransportInvalidResponseException(sprintf('AI endpoint [%s] returned an invalid JSON payload.', $endpointGroup));
        }

        $this->logOutcome(
            $endpointGroup,
            $payload,
            $endpointConfig,
            'succeeded',
            'provider_available',
            $this->latencyMs($startedAt),
            [
                'http_status' => $response->status(),
                'retry_attempts' => $retryAttempts,
            ],
        );

        /** @var array<string, mixed> $data */
        return $data;
    }

    private function shouldRetry(Response $response): bool
    {
        return in_array($response->status(), [408, 425, 429, 500, 502, 503, 504], true);
    }

    private function reasonCodeForResponse(Response $response): string
    {
        return match ($response->status()) {
            401, 403 => 'provider_auth_failed',
            408, 425, 504 => 'provider_timeout',
            429 => 'provider_quota_exceeded',
            500, 502, 503 => 'provider_retry_exhausted',
            default => 'provider_unavailable',
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $extra
     */
    private function logOutcome(
        string $endpointGroup,
        array $payload,
        ?AiEndpointConfig $endpointConfig,
        string $outcome,
        string $reasonCode,
        int $latencyMs,
        array $extra = [],
    ): void {
        $context = array_replace([
            'ai_mode' => $this->endpointRegistry->mode(),
            'capability_group' => $this->capabilityGroup($endpointGroup, $payload),
            'feature_key' => $this->featureKey($endpointGroup, $payload),
            'provider' => $endpointConfig?->providerName ?? $this->endpointRegistry->providerName(),
            'endpoint_group' => $endpointGroup,
            'tenant_id' => $this->scalarString($payload['tenant_id'] ?? null),
            'rfq_id' => $this->scalarString($payload['rfq_id'] ?? null),
            'outcome' => $outcome,
            'reason_code' => $reasonCode,
            'latency_ms' => $latencyMs,
            'model_id' => $this->scalarString($endpointConfig?->metadata['model_id'] ?? null),
            'model_revision' => $this->scalarString($endpointConfig?->metadata['model_revision'] ?? null),
        ], $extra);

        $logger = $this->logs->channel((string) config('atomy.ai.operations.log_channel', 'stack'));
        if ($outcome === 'succeeded') {
            $logger->info('AI provider invocation succeeded.', $context);

            return;
        }

        $logger->warning('AI provider invocation failed.', $context);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function capabilityGroup(string $endpointGroup, array $payload): string
    {
        $action = $this->scalarString($payload['action'] ?? null);

        return match ($action) {
            'comparison_overlay' => AiStatusSchema::CAPABILITY_GROUP_COMPARISON_INTELLIGENCE,
            'award_guidance', 'award_debrief_draft' => AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
            'approval_summary' => AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
            default => match ($endpointGroup) {
                AiStatusSchema::ENDPOINT_GROUP_DOCUMENT => AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
                AiStatusSchema::ENDPOINT_GROUP_NORMALIZATION => AiStatusSchema::CAPABILITY_GROUP_NORMALIZATION_INTELLIGENCE,
                AiStatusSchema::ENDPOINT_GROUP_SOURCING_RECOMMENDATION => AiStatusSchema::CAPABILITY_GROUP_SOURCING_RECOMMENDATION_INTELLIGENCE,
                AiStatusSchema::ENDPOINT_GROUP_INSIGHT => AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE => AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                default => AiStatusSchema::CAPABILITY_GROUP_COMPARISON_INTELLIGENCE,
            },
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function featureKey(string $endpointGroup, array $payload): string
    {
        $featureKey = $this->scalarString($payload['feature_key'] ?? null);
        if ($featureKey !== null) {
            return $featureKey;
        }

        $action = $this->scalarString($payload['action'] ?? null);

        return match ($action) {
            'comparison_overlay' => 'comparison_ai_overlay',
            'award_guidance', 'award_debrief_draft' => 'award_ai_guidance',
            'approval_summary' => 'approval_ai_summary',
            default => match ($endpointGroup) {
                AiStatusSchema::ENDPOINT_GROUP_DOCUMENT => 'quote_document_extraction',
                AiStatusSchema::ENDPOINT_GROUP_NORMALIZATION => 'normalization_suggestions',
                AiStatusSchema::ENDPOINT_GROUP_SOURCING_RECOMMENDATION => 'vendor_ai_ranking',
                AiStatusSchema::ENDPOINT_GROUP_INSIGHT => 'dashboard_ai_summary',
                AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE => 'governance_ai_narrative',
                default => 'comparison_ai_overlay',
            },
        };
    }

    private function scalarString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function latencyMs(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
