<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Rules;

use Nexus\InsightOperations\DTOs\ReportingPipelineRequest;

final class ReportingPipelineRule
{
    public function assert(ReportingPipelineRequest $request): void
    {
        $reportTemplateId = trim((string) $request->reportTemplateId);
        if ($reportTemplateId === '') {
            throw new \InvalidArgumentException('reportTemplateId is required.');
        }

        $format = strtolower(trim((string) ($request->deliveryOptions['format'] ?? 'pdf')));
        if (!in_array($format, ['pdf', 'csv', 'json', 'xlsx'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported report format: %s', $format));
        }
    }
}
