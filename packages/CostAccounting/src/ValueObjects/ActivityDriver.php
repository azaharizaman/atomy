<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\ValueObjects;

/**
 * Activity Driver Value Object
 * 
 * Represents an activity-based costing driver used
 * for allocating overhead costs.
 */
final readonly class ActivityDriver
{
    public function __construct(
        private string $id,
        private string $name,
        private string $type,
        private string $unitOfMeasure
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUnitOfMeasure(): string
    {
        return $this->unitOfMeasure;
    }

    public function isQuantityBased(): bool
    {
        return $this->type === 'quantity';
    }

    public function isTimeBased(): bool
    {
        return $this->type === 'time';
    }

    public function isValueBased(): bool
    {
        return $this->type === 'value';
    }
}
