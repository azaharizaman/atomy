<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\ValueObjects;

/**
 * Represents the ownership structure between entities.
 */
final readonly class OwnershipStructure
{
    /**
     * @param string $parentId
     * @param string $subsidiaryId
     * @param float $directOwnership
     * @param float $indirectOwnership
     * @param \DateTimeImmutable $effectiveFrom
     */
    public function __construct(
        private string $parentId,
        private string $subsidiaryId,
        private float $directOwnership,
        private float $indirectOwnership = 0.0,
        private ?\DateTimeImmutable $effectiveFrom = null,
    ) {}

    public function getParentId(): string
    {
        return $this->parentId;
    }

    public function getSubsidiaryId(): string
    {
        return $this->subsidiaryId;
    }

    public function getDirectOwnership(): float
    {
        return $this->directOwnership;
    }

    public function getIndirectOwnership(): float
    {
        return $this->indirectOwnership;
    }

    public function getEffectiveOwnership(): float
    {
        return $this->directOwnership + $this->indirectOwnership;
    }

    public function getEffectiveFrom(): ?\DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function getNciPercentage(): float
    {
        return 100.0 - $this->getEffectiveOwnership();
    }
}
