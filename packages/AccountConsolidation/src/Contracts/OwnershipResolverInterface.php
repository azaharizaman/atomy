<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Contracts;

use Nexus\AccountConsolidation\ValueObjects\OwnershipStructure;
use Nexus\AccountConsolidation\Enums\ConsolidationMethod;

/**
 * Contract for resolving ownership relationships.
 */
interface OwnershipResolverInterface
{
    /**
     * Get the ownership structure for an entity.
     *
     * @param string $entityId
     * @return OwnershipStructure
     */
    public function getOwnershipStructure(string $entityId): OwnershipStructure;

    /**
     * Determine the consolidation method based on ownership.
     *
     * @param float $ownershipPercentage
     * @return ConsolidationMethod
     */
    public function determineConsolidationMethod(float $ownershipPercentage): ConsolidationMethod;

    /**
     * Get effective ownership percentage (including indirect holdings).
     *
     * @param string $parentId
     * @param string $subsidiaryId
     * @return float
     */
    public function getEffectiveOwnership(string $parentId, string $subsidiaryId): float;
}
