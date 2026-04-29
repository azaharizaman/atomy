<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class InsightResultDto
{
    /**
     * @param array<string, mixed> $facts
     */
    public function __construct(
        public array $facts,
        public AiArtifactDto $artifact,
        public string $artifactField,
    ) {}

    /**
     * @return array{data: array<string, mixed>}
     */
    public function toResponseArray(): array
    {
        return [
            'data' => [
                ...$this->facts,
                $this->artifactField => $this->artifact->toArray(),
            ],
        ];
    }
}
