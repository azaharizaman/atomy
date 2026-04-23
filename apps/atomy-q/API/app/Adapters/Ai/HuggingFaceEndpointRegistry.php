<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use App\Adapters\Ai\Contracts\AiEndpointRegistryInterface;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\MachineLearning\ValueObjects\AiEndpointConfig;

final readonly class HuggingFaceEndpointRegistry implements AiEndpointRegistryInterface
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
        $provider = trim((string) ($this->providerSection()['name'] ?? 'huggingface'));

        return $provider === '' ? 'huggingface' : $provider;
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
        $timeoutSeconds = (int) ($endpoint['timeout_seconds'] ?? $defaultTimeout);
        $authToken = trim((string) ($endpoint['auth_token'] ?? ''));
        $probeUrl = $this->resolveProbeUrl($uri, $endpoint);
        $probeMethod = $this->resolveProbeMethod($endpoint);

        return new AiEndpointConfig(
            endpointGroup: AiEndpointGroup::fromConfig($endpointGroup),
            providerName: $this->providerName(),
            endpointUri: $uri,
            timeoutSeconds: max(1, $timeoutSeconds),
            enabled: (bool) ($endpoint['enabled'] ?? true),
            metadata: [
                'auth_token' => $authToken !== '' ? $authToken : ($defaultAuthToken !== '' ? $defaultAuthToken : null),
                'probe_url' => $probeUrl,
                'probe_method' => $probeMethod,
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

    /**
     * @return array<string, mixed>
     */
    private function endpointSection(string $endpointGroup): array
    {
        $endpoints = $this->config['endpoints'] ?? [];

        if (!is_array($endpoints)) {
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

        $healthPath = trim((string) ($endpoint['health_path'] ?? '/health'));
        if ($healthPath === '') {
            $healthPath = '/health';
        }

        if ($healthPath[0] !== '/') {
            $healthPath = '/' . $healthPath;
        }

        return rtrim($endpointUri, '/') . $healthPath;
    }

    /**
     * @param array<string, mixed> $endpoint
     */
    private function resolveProbeMethod(array $endpoint): string
    {
        $method = strtoupper(trim((string) ($endpoint['health_method'] ?? 'GET')));

        return $method === '' ? 'GET' : $method;
    }
}
