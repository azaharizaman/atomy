<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts;

use Nexus\KycVerification\ValueObjects\BeneficialOwner;

/**
 * Interface for beneficial ownership tracking.
 */
interface BeneficialOwnershipTrackerInterface
{
    /**
     * Register a beneficial owner for a party
     */
    public function registerBeneficialOwner(
        string $partyId,
        BeneficialOwner $beneficialOwner
    ): BeneficialOwner;

    /**
     * Update a beneficial owner
     */
    public function updateBeneficialOwner(
        string $partyId,
        string $beneficialOwnerId,
        BeneficialOwner $updatedOwner
    ): BeneficialOwner;

    /**
     * Remove a beneficial owner
     */
    public function removeBeneficialOwner(
        string $partyId,
        string $beneficialOwnerId,
        string $reason
    ): void;

    /**
     * Get all beneficial owners for a party
     * 
     * @return array<BeneficialOwner>
     */
    public function getBeneficialOwners(string $partyId): array;

    /**
     * Get beneficial owner by ID
     */
    public function getBeneficialOwner(
        string $partyId,
        string $beneficialOwnerId
    ): ?BeneficialOwner;

    /**
     * Get total ownership percentage identified
     */
    public function getTotalOwnershipIdentified(string $partyId): float;

    /**
     * Check if ownership chain is complete (100% identified)
     */
    public function isOwnershipComplete(string $partyId): bool;

    /**
     * Validate beneficial ownership structure
     * 
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateOwnershipStructure(string $partyId): array;

    /**
     * Detect circular ownership
     */
    public function detectCircularOwnership(string $partyId): bool;

    /**
     * Get unverified beneficial owners
     * 
     * @return array<BeneficialOwner>
     */
    public function getUnverifiedOwners(string $partyId): array;

    /**
     * Get PEP beneficial owners
     * 
     * @return array<BeneficialOwner>
     */
    public function getPepOwners(string $partyId): array;

    /**
     * Calculate effective ownership through chain
     * 
     * @param array<string> $ownershipChain List of party IDs in ownership chain
     */
    public function calculateEffectiveOwnership(
        string $ultimateOwnerId,
        array $ownershipChain
    ): float;

    /**
     * Get ownership hierarchy as tree structure
     * 
     * @return array<string, mixed>
     */
    public function getOwnershipHierarchy(string $partyId): array;

    /**
     * Check if UBO tracking is required for party
     */
    public function isUboTrackingRequired(string $partyId): bool;
}
