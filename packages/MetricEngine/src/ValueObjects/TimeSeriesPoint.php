<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Exceptions\InvalidWindowException;

final readonly class TimeSeriesPoint
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $periodKey,
        public int|float|string $value,
        public array $metadata = []
    ) {
        if (trim($periodKey) === '') {
            throw new InvalidWindowException('Time-series period key is required.');
        }
    }
}
