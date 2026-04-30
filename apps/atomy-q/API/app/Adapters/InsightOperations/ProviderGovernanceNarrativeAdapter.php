<?php

declare(strict_types=1);

namespace App\Adapters\InsightOperations;

use App\Adapters\Ai\Contracts\ProviderGovernanceClientInterface;
use App\Adapters\Ai\DTOs\GovernanceNarrativeRequest;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\InsightOperations\Contracts\InsightNarrativePortInterface;
use Nexus\InsightOperations\DTOs\AiArtifactDto;
use Nexus\InsightOperations\DTOs\AiArtifactProvenanceDto;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

final readonly class ProviderGovernanceNarrativeAdapter implements InsightNarrativePortInterface
{
    public function __construct(
        private ProviderGovernanceClientInterface $client,
        private ClockInterface $clock,
    ) {
    }

    public function generate(string $featureKey, string $tenantId, string $subjectType, string $actorId, array $facts): AiArtifactDto
    {
        unset($subjectType, $actorId);

        $response = $this->client->narrate(new GovernanceNarrativeRequest(
            featureKey: $featureKey,
            tenantId: $tenantId,
            vendorId: $this->vendorIdFromFacts($facts),
            facts: $facts,
        ));
        $payload = $this->unwrapPayload($response);

        return AiArtifactDto::available(
            featureKey: $featureKey,
            capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
            payload: $payload,
            provenance: $this->provenance($response, $payload),
        );
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function unwrapPayload(array $response): array
    {
        $payload = $response['payload'] ?? $response;

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param array<string, mixed> $response
     * @param array<string, mixed> $payload
     */
    private function provenance(array $response, array $payload): AiArtifactProvenanceDto
    {
        $provenance = is_array($response['provenance'] ?? null) ? $response['provenance'] : [];

        return new AiArtifactProvenanceDto(
            providerName: $this->stringOrNull($provenance['provider_name'] ?? null) ?? $this->providerName(),
            endpointGroup: $this->stringOrNull($provenance['endpoint_group'] ?? null) ?? AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
            model: $this->stringOrNull($provenance['model'] ?? null),
            promptVersion: $this->stringOrNull($provenance['prompt_version'] ?? null),
            providerRequestId: $this->stringOrNull($provenance['provider_request_id'] ?? null),
            inputHash: $this->stringOrNull($provenance['input_hash'] ?? null),
            outputHash: $this->stringOrNull($provenance['output_hash'] ?? null) ?? $this->payloadHash($payload),
            latencyMs: is_numeric($provenance['latency_ms'] ?? null) ? (int) $provenance['latency_ms'] : null,
            generatedAt: $this->stringOrNull($provenance['generated_at'] ?? null) ?? $this->clock->now()->format(DATE_ATOM),
            actorId: $this->stringOrNull($provenance['actor_id'] ?? null),
            actorHash: $this->stringOrNull($provenance['actor_hash'] ?? null),
        );
    }

    private function vendorIdFromFacts(array $facts): string
    {
        $vendorId = $facts['vendor_id'] ?? $facts['vendor_id_hash'] ?? 'vendor_governance';

        return trim((string) $vendorId);
    }

    private function providerName(): ?string
    {
        $providerName = config('atomy.ai.provider.name');
        if (is_string($providerName) && trim($providerName) !== '') {
            return trim($providerName);
        }

        $providerKey = config('atomy.ai.provider.key');
        if (! is_string($providerKey) || trim($providerKey) === '') {
            return null;
        }

        return strtolower(trim($providerKey));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function payloadHash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
