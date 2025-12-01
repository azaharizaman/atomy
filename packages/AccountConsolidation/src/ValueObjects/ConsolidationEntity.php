<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\ValueObjects;

/**
 * Represents an entity in the consolidation group.
 */
final readonly class ConsolidationEntity
{
    public function __construct(
        private string $id,
        private string $name,
        private string $currency,
        private ?string $parentId = null,
        private float $ownershipPercentage = 100.0,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function getOwnershipPercentage(): float
    {
        return $this->ownershipPercentage;
    }

    public function isSubsidiary(): bool
    {
        return $this->parentId !== null;
    }
}
