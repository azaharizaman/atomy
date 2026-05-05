<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Contracts\WindowResolverInterface;
use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\ValueObjects\MetricSeries;
use Nexus\MetricEngine\ValueObjects\TimeWindow;

class WindowResolverService implements WindowResolverInterface
{
    public function resolve(MetricSeries $series, TimeWindow $window): MetricSeries
    {
        $filteredPoints = match ($window->type) {
            \Nexus\MetricEngine\Enums\WindowType::FIXED_ROLLING => $this->resolveFixedRolling($series, $window),
            \Nexus\MetricEngine\Enums\WindowType::EXPLICIT_RANGE => $this->resolveExplicitRange($series, $window),
        };

        return new MetricSeries($series->name, $filteredPoints, $series->unit);
    }

    /** @return list<\Nexus\MetricEngine\ValueObjects\TimeSeriesPoint> */
    private function resolveFixedRolling(MetricSeries $series, TimeWindow $window): array
    {
        $size = $window->size;

        if (count($series->points) < $size) {
            throw new InsufficientDataException('Series has fewer points than the requested window size.');
        }

        return array_slice($series->points, -$size);
    }

    /** @return list<\Nexus\MetricEngine\ValueObjects\TimeSeriesPoint> */
    private function resolveExplicitRange(MetricSeries $series, TimeWindow $window): array
    {
        $filtered = array_values(array_filter(
            $series->points,
            static fn ($point) => $point->periodKey >= $window->startPeriod && $point->periodKey <= $window->endPeriod
        ));

        if ($filtered === []) {
            throw new InsufficientDataException('No points match the explicit window range.');
        }

        return $filtered;
    }
}
