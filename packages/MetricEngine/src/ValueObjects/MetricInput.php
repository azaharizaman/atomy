<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;

final readonly class MetricInput
{
    public function __construct(
        public string $name,
        public int|float|string $value,
        public ?string $unit = null
    ) {
        if (trim($name) === '') {
            throw new FormulaValidationException('Metric input name is required.');
        }
    }
}
