<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Enums\PeriodGranularity;
use Nexus\MetricEngine\Exceptions\InvalidWindowException;

final readonly class PeriodKey
{
    private function __construct(
        public string $value,
        public PeriodGranularity $granularity,
        public int $sortKey
    ) {}

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            [$year, $month, $day] = array_map('intval', explode('-', $value));

            if (! checkdate($month, $day, $year)) {
                throw new InvalidWindowException("Unsupported period key [{$value}].");
            }

            return new self($value, PeriodGranularity::DATE, ($year * 10000) + ($month * 100) + $day);
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            [$year, $month] = array_map('intval', explode('-', $value));

            if ($month < 1 || $month > 12) {
                throw new InvalidWindowException("Unsupported period key [{$value}].");
            }

            return new self($value, PeriodGranularity::MONTH, ($year * 100) + $month);
        }

        if (preg_match('/^(\d{4})-Q([1-4])$/', $value, $matches) === 1) {
            $year = (int) $matches[1];
            $quarter = (int) $matches[2];

            return new self($value, PeriodGranularity::QUARTER, ($year * 10) + $quarter);
        }

        if (preg_match('/^\d{4}$/', $value) === 1) {
            return new self($value, PeriodGranularity::YEAR, (int) $value);
        }

        throw new InvalidWindowException("Unsupported period key [{$value}].");
    }
}
