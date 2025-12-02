<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\ValueObjects;

/**
 * Attribution of variance to a contributing factor.
 */
final readonly class VarianceAttribution
{
    public function __construct(
        private string $factorName,
        private float $contribution,
        private float $percentage,
        private ?string $description = null,
    ) {}

    public function getFactorName(): string
    {
        return $this->factorName;
    }

    public function getContribution(): float
    {
        return $this->contribution;
    }

    public function getPercentage(): float
    {
        return $this->percentage;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
