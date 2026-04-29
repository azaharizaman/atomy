<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class AiArtifactDto
{
    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, mixed> $sourceFacts
     * @param list<string> $reasonCodes
     */
    public function __construct(
        public string $featureKey,
        public string $capabilityGroup,
        public bool $available,
        public string $status,
        public ?array $payload = null,
        public ?AiArtifactProvenanceDto $provenance = null,
        public array $sourceFacts = [],
        public ?string $sourceFactsHash = null,
        public array $reasonCodes = [],
    ) {}

    /**
     * @param array<string, mixed>|null $payload
     */
    public static function available(
        string $featureKey,
        string $capabilityGroup,
        ?array $payload,
        ?AiArtifactProvenanceDto $provenance = null,
    ): self {
        return new self(
            featureKey: $featureKey,
            capabilityGroup: $capabilityGroup,
            available: true,
            status: 'available',
            payload: $payload,
            provenance: $provenance,
        );
    }

    /**
     * @param array<string, mixed> $sourceFacts
     * @param list<string> $reasonCodes
     */
    public static function unavailable(
        string $featureKey,
        string $capabilityGroup,
        array $sourceFacts,
        string $sourceFactsHash,
        array $reasonCodes,
    ): self {
        return new self(
            featureKey: $featureKey,
            capabilityGroup: $capabilityGroup,
            available: false,
            status: 'unavailable',
            payload: null,
            provenance: null,
            sourceFacts: $sourceFacts,
            sourceFactsHash: $sourceFactsHash,
            reasonCodes: $reasonCodes,
        );
    }

    /**
     * @param array<string, mixed> $sourceFacts
     */
    public function withSourceFacts(array $sourceFacts, string $sourceFactsHash, ?string $actorId = null): self
    {
        $provenance = $this->provenance;
        if ($actorId !== null && $actorId !== '') {
            $provenance = new AiArtifactProvenanceDto(
                providerName: $provenance?->providerName,
                endpointGroup: $provenance?->endpointGroup,
                model: $provenance?->model,
                promptVersion: $provenance?->promptVersion,
                providerRequestId: $provenance?->providerRequestId,
                inputHash: $provenance?->inputHash ?? $sourceFactsHash,
                outputHash: $provenance?->outputHash,
                latencyMs: $provenance?->latencyMs,
                generatedAt: $provenance?->generatedAt,
                actorId: $provenance?->actorId,
                actorHash: $provenance?->actorHash ?? hash('sha256', strtolower(trim($actorId))),
            );
        }

        return new self(
            featureKey: $this->featureKey,
            capabilityGroup: $this->capabilityGroup,
            available: $this->available,
            status: $this->status,
            payload: $this->payload,
            provenance: $provenance,
            sourceFacts: $sourceFacts,
            sourceFactsHash: $sourceFactsHash,
            reasonCodes: $this->reasonCodes,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'feature_key' => $this->featureKey,
            'capability_group' => $this->capabilityGroup,
            'available' => $this->available,
            'status' => $this->status,
            'payload' => $this->payload,
            'provenance' => $this->provenance?->toArray(),
            'source_facts' => $this->sourceFacts,
            'source_facts_hash' => $this->sourceFactsHash,
            'reason_codes' => $this->reasonCodes,
        ];
    }
}
