<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class ReportingPipelineResult
{
    /**
     * @param array<string, mixed> $reportData
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $pipelineId,
        public string $storagePath,
        public array $reportData,
        public array $metadata,
    ) {}
}
