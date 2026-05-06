<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Contracts;

use Nexus\MetricEngine\Enums\InputMode;
use Nexus\MetricEngine\Enums\ValueType;
use Nexus\MetricEngine\ValueObjects\ComparisonDefinition;
use Nexus\MetricEngine\ValueObjects\PrecisionPolicy;
use Nexus\MetricEngine\ValueObjects\TimeWindow;

interface MetricResultInterface
{
    public function value(): int|float|string|array;

    public function valueType(): ValueType;

    public function formulaIdentifier(): string;

    public function inputMode(): InputMode;

    public function precisionPolicy(): PrecisionPolicy;

    public function unit(): ?string;

    public function window(): ?TimeWindow;

    public function comparison(): ?ComparisonDefinition;
}
