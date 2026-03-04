<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DataProviders;

use Nexus\InsightOperations\DTOs\ReportingPipelineRequest;

final class PipelineContextDataProvider
{
    /**
     * @param array<string, mixed> $historicalData
     * @param array<string, mixed>|null $forecastData
     * @return array<string, mixed>
     */
    public function build(ReportingPipelineRequest $request, array $historicalData, ?array $forecastData): array
    {
        return [
            'pipeline_id' => $request->pipelineId,
            'template_id' => $request->reportTemplateId,
            'generated_at' => gmdate(DATE_ATOM),
            'historical' => $historicalData,
            'forecast' => $forecastData,
            'parameters' => $request->parameters,
        ];
    }
}
