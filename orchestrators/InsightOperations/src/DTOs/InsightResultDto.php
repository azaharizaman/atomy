<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

use Nexus\InsightOperations\DTOs\AiArtifactDto;

final readonly class InsightResultDto
{
    /**
     * @param array<string, mixed> $facts
     */
    public function __construct(
        public array $facts,
        public AiArtifactDto $artifact,
        public string $artifactField,
    ) {
        if (array_key_exists($this->artifactField, $this->facts)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot merge AI artifact: fact key "%s" already exists.',
                    $this->artifactField,
                ),
            );
        }
    }

    /**
     * @return array{data: array<string, mixed>}
     */
    public function toResponseArray(): array
    {
        return [
            "data" => [
                ...$this->facts,
                $this->artifactField => $this->artifact->toArray(),
            ],
        ];
    }
}
