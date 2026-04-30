<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class DashboardFactsDto
{
    /**
     * @param list<MetricFactDto> $metrics
     * @param array<int, array<string, mixed>> $recentActivity
     * @param array<int, array<string, mixed>> $riskAlerts
     */
    public function __construct(
        public array $metrics,
        public array $recentActivity = [],
        public array $riskAlerts = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $metrics = array_map(
            static fn(MetricFactDto $metric): array => $metric->toArray(),
            $this->metrics,
        );
        $metricValues = [];
        $reservedKeys = ["metrics", "recent_activity", "risk_alerts"];

        foreach ($metrics as $metric) {
            $key = (string) $metric["key"];
            if (in_array($key, $reservedKeys, true)) {
                continue;
            }
            $metricValues[$key] = $metric["value"];
        }

        return [
            ...$metricValues,
            "metrics" => $metrics,
            "recent_activity" => $this->recentActivity,
            "risk_alerts" => $this->riskAlerts,
        ];
    }
}
