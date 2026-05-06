<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;

final readonly class BandDefinition
{
    public function __construct(
        public string $label,
        public int|float|string $minimum,
        public int|float|string $maximum
    ) {
        if (trim($label) === '') {
            throw new FormulaValidationException('Band label is required.');
        }
    }
}
