<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Enums\RoundingMode;
use Nexus\MetricEngine\Exceptions\FormulaValidationException;

final readonly class PrecisionPolicy
{
    public function __construct(
        public int $scale = 2,
        public RoundingMode $roundingMode = RoundingMode::HALF_UP
    ) {
        if ($scale < 0) {
            throw new FormulaValidationException('Precision scale must be zero or greater.');
        }
    }

    public static function default(): self
    {
        return new self();
    }
}
