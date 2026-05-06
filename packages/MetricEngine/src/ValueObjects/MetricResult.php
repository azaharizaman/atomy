<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Contracts\MetricResultInterface;
use Nexus\MetricEngine\Enums\InputMode;
use Nexus\MetricEngine\Enums\ValueType;

final readonly class MetricResult implements MetricResultInterface
{
    public function __construct(
        private string $formulaIdentifier,
        private int|float|string|array $value,
        private ValueType $valueType,
        private InputMode $inputMode,
        private PrecisionPolicy $precisionPolicy,
        private ?string $unit = null,
        private ?TimeWindow $window = null,
        private ?ComparisonDefinition $comparison = null
    ) {}

    public function value(): int|float|string|array
    {
        return $this->value;
    }

    public function valueType(): ValueType
    {
        return $this->valueType;
    }

    public function formulaIdentifier(): string
    {
        return $this->formulaIdentifier;
    }

    public function inputMode(): InputMode
    {
        return $this->inputMode;
    }

    public function precisionPolicy(): PrecisionPolicy
    {
        return $this->precisionPolicy;
    }

    public function unit(): ?string
    {
        return $this->unit;
    }

    public function window(): ?TimeWindow
    {
        return $this->window;
    }

    public function comparison(): ?ComparisonDefinition
    {
        return $this->comparison;
    }
}
