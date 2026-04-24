<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Nexus\MachineLearning\Contracts\AiHealthProbeInterface;
use Nexus\MachineLearning\Enums\AiHealth;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use Nexus\MachineLearning\ValueObjects\AiEndpointHealthSnapshot;
use Throwable;

final readonly class ConfiguredAiHealthProbe implements AiHealthProbeInterface
{
    public function __construct(
        private Factory $http,
    ) {
    }

    public function probe(AiEndpointConfig $endpointConfig): AiEndpointHealthSnapshot
    {
        $checkedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if ($endpointConfig->enabled === false) {
            return new AiEndpointHealthSnapshot(
                endpointGroup: $endpointConfig->endpointGroup,
                health: AiHealth::DISABLED,
                checkedAt: $checkedAt,
                reasonCodes: ['endpoint_disabled_by_config'],
                diagnostics: [
                    'provider_name' => $endpointConfig->providerName,
                ],
            );
        }

        $startedAt = microtime(true);
        $request = $this->http
            ->acceptJson()
            ->timeout($endpointConfig->timeoutSeconds);

        $authToken = $this->authToken($endpointConfig);
        if ($authToken !== null) {
            $request = $request->withToken($authToken);
        }

        try {
            $response = $request->send(
                $this->probeMethod($endpointConfig),
                $this->probeUrl($endpointConfig),
            );
            $latencyMs = $this->latencyMs($startedAt);

            if ($response->successful()) {
                return new AiEndpointHealthSnapshot(
                    endpointGroup: $endpointConfig->endpointGroup,
                    health: AiHealth::HEALTHY,
                    checkedAt: $checkedAt,
                    reasonCodes: ['provider_available'],
                    latencyMs: $latencyMs,
                    diagnostics: [
                        'provider_name' => $endpointConfig->providerName,
                        'latency_ms' => $latencyMs,
                    ],
                );
            }

            return new AiEndpointHealthSnapshot(
                endpointGroup: $endpointConfig->endpointGroup,
                health: $response->serverError() ? AiHealth::DEGRADED : AiHealth::UNAVAILABLE,
                checkedAt: $checkedAt,
                reasonCodes: ['health_probe_failed'],
                latencyMs: $latencyMs,
                diagnostics: [
                    'provider_name' => $endpointConfig->providerName,
                    'latency_ms' => $latencyMs,
                ],
            );
        } catch (ConnectionException) {
            return new AiEndpointHealthSnapshot(
                endpointGroup: $endpointConfig->endpointGroup,
                health: AiHealth::UNAVAILABLE,
                checkedAt: $checkedAt,
                reasonCodes: ['health_probe_timeout'],
                latencyMs: $this->latencyMs($startedAt),
                diagnostics: [
                    'provider_name' => $endpointConfig->providerName,
                ],
            );
        } catch (Throwable) {
            return new AiEndpointHealthSnapshot(
                endpointGroup: $endpointConfig->endpointGroup,
                health: AiHealth::UNAVAILABLE,
                checkedAt: $checkedAt,
                reasonCodes: ['health_probe_failed'],
                latencyMs: $this->latencyMs($startedAt),
                diagnostics: [
                    'provider_name' => $endpointConfig->providerName,
                ],
            );
        }
    }

    private function authToken(AiEndpointConfig $endpointConfig): ?string
    {
        $token = $endpointConfig->metadata['auth_token'] ?? null;

        if (! is_string($token)) {
            return null;
        }

        $token = trim($token);

        return $token === '' ? null : $token;
    }

    private function probeUrl(AiEndpointConfig $endpointConfig): string
    {
        $probeUrl = $endpointConfig->metadata['probe_url'] ?? null;

        if (! is_string($probeUrl)) {
            return $endpointConfig->endpointUri;
        }

        $probeUrl = trim($probeUrl);

        return $probeUrl === '' ? $endpointConfig->endpointUri : $probeUrl;
    }

    private function probeMethod(AiEndpointConfig $endpointConfig): string
    {
        $method = $endpointConfig->metadata['probe_method'] ?? null;

        if (! is_string($method)) {
            return 'GET';
        }

        $method = strtoupper(trim($method));

        return $method === '' ? 'GET' : $method;
    }

    private function latencyMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }
}
