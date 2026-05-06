<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\ValueObjects;

use Nexus\MetricEngine\Contracts\FormulaInterface;
use Nexus\MetricEngine\Enums\AggregationType;

final readonly class FormulaDefinition implements FormulaInterface
{
    /**
     * @param list<mixed> $operands
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $identifier,
        private AggregationType $operation,
        private array $operands,
        private PrecisionPolicy $precisionPolicy,
        private ?TimeWindow $window = null,
        private ?ComparisonDefinition $comparison = null,
        private ?string $unit = null,
        private array $metadata = []
    ) {}

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function operation(): AggregationType
    {
        return $this->operation;
    }

    /** @return list<mixed> */
    public function operands(): array
    {
        return $this->operands;
    }

    public function precisionPolicy(): PrecisionPolicy
    {
        return $this->precisionPolicy;
    }

    public function window(): ?TimeWindow
    {
        return $this->window;
    }

    public function comparison(): ?ComparisonDefinition
    {
        return $this->comparison;
    }

    public function unit(): ?string
    {
        return $this->unit;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
