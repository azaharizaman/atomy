<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Services;

use Nexus\AccountConsolidation\Contracts\OwnershipResolverInterface;
use Nexus\AccountConsolidation\Enums\ConsolidationMethod;
use Nexus\AccountConsolidation\ValueObjects\OwnershipStructure;

/**
 * Pure logic for resolving ownership relationships.
 */
final readonly class OwnershipResolver implements OwnershipResolverInterface
{
    /**
     * @param array<string, OwnershipStructure> $ownershipData
     */
    public function __construct(
        private array $ownershipData = [],
    ) {}

    public function getOwnershipStructure(string $entityId): OwnershipStructure
    {
        return $this->ownershipData[$entityId] ?? new OwnershipStructure(
            parentId: '',
            subsidiaryId: $entityId,
            directOwnership: 100.0
        );
    }

    public function determineConsolidationMethod(float $ownershipPercentage): ConsolidationMethod
    {
        return match (true) {
            $ownershipPercentage > 50.0 => ConsolidationMethod::FULL,
            $ownershipPercentage >= 20.0 => ConsolidationMethod::EQUITY,
            default => ConsolidationMethod::COST,
        };
    }

    public function getEffectiveOwnership(string $parentId, string $subsidiaryId): float
    {
        $key = $parentId . '_' . $subsidiaryId;
        
        if (isset($this->ownershipData[$key])) {
            return $this->ownershipData[$key]->getEffectiveOwnership();
        }

        return 0.0;
    }
}
