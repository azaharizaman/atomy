<?php

declare(strict_types=1);

namespace Nexus\Laravel\InsightOperations\Adapters;

use Nexus\InsightOperations\Contracts\ReportDataQueryPortInterface;
use Nexus\QueryEngine\Services\AnalyticsManager;

final readonly class ReportDataQueryPortAdapter implements ReportDataQueryPortInterface
{
    public function __construct(private AnalyticsManager $analyticsManager) {}

    public function query(string $reportTemplateId, array $parameters): array
    {
        $modelType = (string) ($parameters['model_type'] ?? 'report');
        $modelId = (string) ($parameters['model_id'] ?? $reportTemplateId);
        $queryName = (string) ($parameters['query_name'] ?? $reportTemplateId);
        $queryParameters = is_array($parameters['query_parameters'] ?? null)
            ? $parameters['query_parameters']
            : $parameters;

        return $this->analyticsManager
            ->runQuery($queryName, $modelType, $modelId, $queryParameters)
            ->getData();
    }
}
