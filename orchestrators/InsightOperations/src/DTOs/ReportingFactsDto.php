<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class ReportingFactsDto
{
    /**
     * @param list<MetricFactDto> $metrics
     * @param array<int, array<string, mixed>> $series
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, array<string, mixed>> $schedules
     */
    public function __construct(
        public string $subjectType,
        public array $metrics,
        public array $series = [],
        public array $rows = [],
        public array $schedules = [],
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
        $reservedKeys = [
            "subject_type",
            "metrics",
            "series",
            "rows",
            "schedules",
        ];

        foreach ($metrics as $metric) {
            $key = (string) $metric["key"];
            if (in_array($key, $reservedKeys, true)) {
                continue;
            }
            $metricValues[$key] = $metric["value"];
        }

        return [
            ...$metricValues,
            "subject_type" => $this->subjectType,
            "metrics" => $metrics,
            "series" => $this->series,
            "rows" => $this->rows,
            "schedules" => $this->schedules,
        ];
    }
}
