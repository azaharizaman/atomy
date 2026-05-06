<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\Exceptions\InsufficientDataException;
use Nexus\MetricEngine\Exceptions\InvalidWindowException;

final readonly class MetricSeries
{
    /** @param list<TimeSeriesPoint> $points */
    public function __construct(
        public string $name,
        public array $points,
        public ?string $unit = null
    ) {
        if (trim($name) === '') {
            throw new FormulaValidationException('Metric series name is required.');
        }

        if ($points === []) {
            throw new InsufficientDataException('Metric series requires at least one point.');
        }

        $previousPeriodKey = null;
        $periodKeys = [];

        foreach ($points as $point) {
            if (in_array($point->periodKey, $periodKeys, true)) {
                throw new InvalidWindowException('Metric series period keys must be unique.');
            }

            if ($previousPeriodKey !== null && $point->periodKey < $previousPeriodKey) {
                throw new InvalidWindowException('Metric series period keys must be sorted in ascending order.');
            }

            $periodKeys[] = $point->periodKey;
            $previousPeriodKey = $point->periodKey;
        }
    }
}
