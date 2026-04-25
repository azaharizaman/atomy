<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;
use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;

final readonly class ConfiguredAiEndpointRegistry implements AiEndpointRegistryInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private array $config,
    ) {
    }

    public function mode(): string
    {
        $mode = strtolower(trim((string) ($this->config['mode'] ?? AiStatusSchema::MODE_DETERMINISTIC)));

        return in_array($mode, AiStatusSchema::modes(), true)
            ? $mode
            : AiStatusSchema::MODE_OFF;
    }

    public function providerName(): string
    {
        $providerName = trim((string) ($this->providerSection()['name'] ?? ''));

        return $providerName !== '' ? $providerName : $this->providerKey();
    }

    /**
     * @return list<string>
     */
    public function endpointGroups(): array
    {
        return AiStatusSchema::endpointGroups();
    }

    public function endpointConfig(string $endpointGroup): ?AiEndpointConfig
    {
        $endpoint = $this->endpointSection($endpointGroup);
        $uri = trim((string) ($endpoint['uri'] ?? ''));

        if ($uri === '') {
            return null;
        }

        $provider = $this->providerSection();
        $defaultAuthToken = trim((string) ($provider['default_auth_token'] ?? ''));
        $defaultTimeout = (int) ($provider['default_timeout_seconds'] ?? 10);
        $defaultRetryAttempts = (int) (($provider['default_retry_attempts'] ?? 0) ?: 1);
        $defaultRetryBackoffMs = (int) (($provider['default_retry_backoff_ms'] ?? 0) ?: 0);
        $timeoutSeconds = (int) ($endpoint['timeout_seconds'] ?? $defaultTimeout);
        $authToken = trim((string) ($endpoint['auth_token'] ?? ''));

        return new AiEndpointConfig(
            endpointGroup: AiEndpointGroup::fromConfig($endpointGroup),
            providerName: $this->providerName(),
            endpointUri: $uri,
            timeoutSeconds: max(1, $timeoutSeconds),
            enabled: (bool) ($endpoint['enabled'] ?? true),
            metadata: [
                'auth_token' => $authToken !== '' ? $authToken : ($defaultAuthToken !== '' ? $defaultAuthToken : null),
                'probe_url' => $this->resolveProbeUrl($uri, $endpoint),
                'probe_method' => $this->resolveProbeMethod($endpoint),
                'retry_attempts' => max(1, (int) ($endpoint['retry_attempts'] ?? $defaultRetryAttempts)),
                'retry_backoff_ms' => max(0, (int) ($endpoint['retry_backoff_ms'] ?? $defaultRetryBackoffMs)),
                'model_id' => $this->nullableString($endpoint['model_id'] ?? null),
                'model_revision' => $this->nullableString($endpoint['model_revision'] ?? null),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function providerSection(): array
    {
        $provider = $this->config['provider'] ?? [];

        return is_array($provider) ? $provider : [];
    }

    private function providerKey(): string
    {
        $providerKey = strtolower(trim((string) ($this->providerSection()['key'] ?? '')));

        return $providerKey !== '' ? $providerKey : 'openrouter';
    }

    /**
     * @return array<string, mixed>
     */
    private function endpointSection(string $endpointGroup): array
    {
        $endpoints = $this->config['endpoints'] ?? [];

        if (! is_array($endpoints)) {
            return [];
        }

        return is_array($endpoints[$endpointGroup] ?? null)
            ? $endpoints[$endpointGroup]
            : [];
    }

    /**
     * @param array<string, mixed> $endpoint
     */
    private function resolveProbeUrl(string $endpointUri, array $endpoint): string
    {
        $healthUrl = trim((string) ($endpoint['health_url'] ?? ''));
        if ($healthUrl !== '') {
            return $healthUrl;
        }

        $healthPath = trim((string) ($endpoint['health_path'] ?? ''));
        if ($healthPath !== '') {
            if ($healthPath[0] !== '/') {
                $healthPath = '/' . $healthPath;
            }

            return rtrim($endpointUri, '/') . $healthPath;
        }

        $providerProbeUrl = $this->providerProbeUrl($endpointUri);
        if ($providerProbeUrl !== null) {
            return $providerProbeUrl;
        }

        return rtrim($endpointUri, '/') . '/health';
    }

    /**
     * @param array<string, mixed> $endpoint
     */
    private function resolveProbeMethod(array $endpoint): string
    {
        $method = strtoupper(trim((string) ($endpoint['health_method'] ?? '')));
        if ($method !== '') {
            return $method;
        }

        if (trim((string) ($endpoint['health_url'] ?? '')) === '') {
            $providerProbeMethod = $this->providerProbeMethod();
            if ($providerProbeMethod !== null) {
                return $providerProbeMethod;
            }
        }

        return 'GET';
    }

    private function providerProbeUrl(string $endpointUri): ?string
    {
        if ($this->providerKey() !== 'openrouter') {
            return null;
        }

        $parts = parse_url($endpointUri);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = isset($parts['scheme']) && is_string($parts['scheme']) ? $parts['scheme'] : null;
        $host = isset($parts['host']) && is_string($parts['host']) ? $parts['host'] : null;

        if ($scheme === null || $host === null || $scheme === '' || $host === '') {
            return null;
        }

        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

        return sprintf('%s://%s%s/api/v1/models', $scheme, $host, $port);
    }

    private function providerProbeMethod(): ?string
    {
        return $this->providerKey() === 'openrouter' ? 'GET' : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
