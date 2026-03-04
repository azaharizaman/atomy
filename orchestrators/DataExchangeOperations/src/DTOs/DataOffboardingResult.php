<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\DTOs;

final readonly class DataOffboardingResult
{
    /**
     * @param array<string, mixed> $exportMetadata
     */
    public function __construct(
        public string $taskId,
        public string $sourcePath,
        public string $storedUri,
        public int $sizeBytes,
        public array $exportMetadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'source_path' => $this->sourcePath,
            'stored_uri' => $this->storedUri,
            'size_bytes' => $this->sizeBytes,
            'export_metadata' => $this->exportMetadata,
        ];
    }
}
