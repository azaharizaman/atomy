<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Contracts;

use Nexus\MetricEngine\Enums\AggregationType;
use Nexus\MetricEngine\ValueObjects\ComparisonDefinition;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeWindow;

interface FormulaInterface
{
    public function identifier(): string;

    public function operation(): AggregationType;

    /** @return list<mixed> */
    public function operands(): array;

    public function precisionPolicy(): PrecisionPolicy;

    public function window(): ?TimeWindow;

    public function comparison(): ?ComparisonDefinition;
}
