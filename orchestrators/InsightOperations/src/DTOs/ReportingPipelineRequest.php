<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class ReportingPipelineRequest
{
    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $deliveryOptions
     */
    public function __construct(
        public string $pipelineId,
        public string $reportTemplateId,
        public array $parameters,
        public array $deliveryOptions,
    ) {}
}
