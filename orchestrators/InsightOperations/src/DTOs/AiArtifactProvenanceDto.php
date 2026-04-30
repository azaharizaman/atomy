<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class AiArtifactProvenanceDto
{
    public function __construct(
        public ?string $providerName = null,
        public ?string $endpointGroup = null,
        public ?string $model = null,
        public ?string $promptVersion = null,
        public ?string $providerRequestId = null,
        public ?string $inputHash = null,
        public ?string $outputHash = null,
        public ?int $latencyMs = null,
        public ?string $generatedAt = null,
        public ?string $actorId = null,
        public ?string $actorHash = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                "provider_name" => $this->providerName,
                "endpoint_group" => $this->endpointGroup,
                "model" => $this->model,
                "prompt_version" => $this->promptVersion,
                "provider_request_id" => $this->providerRequestId,
                "input_hash" => $this->inputHash,
                "output_hash" => $this->outputHash,
                "latency_ms" => $this->latencyMs,
                "generated_at" => $this->generatedAt,
                "actor_id" => $this->actorId,
                "actor_hash" => $this->actorHash,
            ],
            static fn($value): bool => $value !== null,
        );
    }
}
