<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Enums\WindowType;
use Nexus\MetricEngine\Exceptions\InvalidWindowException;

final readonly class TimeWindow
{
    private function __construct(
        public WindowType $type,
        public ?int $size,
        public ?string $startPeriod,
        public ?string $endPeriod
    ) {}

    public static function fixedRolling(int $size): self
    {
        if ($size <= 0) {
            throw new InvalidWindowException('Fixed rolling window size must be greater than zero.');
        }

        return new self(WindowType::FIXED_ROLLING, $size, null, null);
    }

    public static function explicitRange(string $startPeriod, string $endPeriod): self
    {
        if (trim($startPeriod) === '' || trim($endPeriod) === '') {
            throw new InvalidWindowException('Explicit window range requires start and end periods.');
        }

        if ($startPeriod > $endPeriod) {
            throw new InvalidWindowException('Explicit window start period must be before or equal to end period.');
        }

        return new self(WindowType::EXPLICIT_RANGE, null, $startPeriod, $endPeriod);
    }
}
