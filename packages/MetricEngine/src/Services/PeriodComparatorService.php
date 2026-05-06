<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Services;

use Nexus\MetricEngine\Exceptions\InvalidWindowException;
use Nexus\MetricEngine\ValueObjects\PeriodKey;

class PeriodComparatorService
{
    public function compare(string $left, string $right): int
    {
        $leftKey = PeriodKey::fromString($left);
        $rightKey = PeriodKey::fromString($right);

        if ($leftKey->granularity !== $rightKey->granularity) {
            throw new InvalidWindowException('Period keys must use one granularity.');
        }

        return $leftKey->sortKey <=> $rightKey->sortKey;
    }

    public function lessThanOrEqual(string $left, string $right): bool
    {
        return $this->compare($left, $right) <= 0;
    }

    public function greaterThanOrEqual(string $left, string $right): bool
    {
        return $this->compare($left, $right) >= 0;
    }
}
