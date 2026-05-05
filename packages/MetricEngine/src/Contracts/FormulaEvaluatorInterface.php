<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Contracts;

use Nexus\MetricEngine\ValueObjects\MetricInput;
use Nexus\MetricEngine\ValueObjects\MetricSeries;

interface FormulaEvaluatorInterface
{
    /** @param array<string, MetricInput|MetricSeries> $inputs */
    public function evaluate(FormulaInterface $formula, array $inputs): MetricResultInterface;
}
