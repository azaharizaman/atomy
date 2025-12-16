<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Services;

use Nexus\KycVerification\Contracts\BeneficialOwnershipTrackerInterface;
use Nexus\KycVerification\Contracts\KycProfilePersistInterface;
use Nexus\KycVerification\Contracts\KycProfileQueryInterface;
use Nexus\KycVerification\Contracts\Providers\AuditLoggerProviderInterface;
use Nexus\KycVerification\Contracts\Providers\PartyProviderInterface;
use Nexus\KycVerification\Enums\PartyType;
use Nexus\KycVerification\Exceptions\BeneficialOwnershipException;
use Nexus\KycVerification\Exceptions\KycVerificationException;
use Nexus\KycVerification\ValueObjects\BeneficialOwner;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Beneficial Ownership Tracker - manages UBO identification and verification.
 */
final readonly class BeneficialOwnershipTracker implements BeneficialOwnershipTrackerInterface
{
    /**
     * Minimum ownership percentage threshold for UBO identification.
     */
    private const float UBO_THRESHOLD = 25.0;

    /**
     * Maximum depth for ownership chain analysis.
     */
    private const int MAX_OWNERSHIP_DEPTH = 10;

    public function __construct(
        private KycProfileQueryInterface $profileQuery,
        private KycProfilePersistInterface $profilePersist,
        private PartyProviderInterface $partyProvider,
        private AuditLoggerProviderInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function registerBeneficialOwner(
        string $partyId,
        BeneficialOwner $beneficialOwner
    ): BeneficialOwner {
        // Validate no circular ownership before registering
        if ($beneficialOwner->ownerId === $partyId) {
            throw BeneficialOwnershipException::circularOwnership(
                $partyId,
                [$partyId, $beneficialOwner->ownerId]
            );
        }

        $profile = $this->profileQuery->findByPartyId($partyId);
        if ($profile === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'KYC profile not found'
            );
        }

        // Check for existing owner with same ID
        foreach ($profile->beneficialOwners as $existing) {
            if ($existing->ownerId === $beneficialOwner->ownerId) {
                throw KycVerificationException::forParty(
                    $partyId,
                    'Beneficial owner already registered: ' . $beneficialOwner->ownerId
                );
            }
        }

        // Validate total ownership doesn't exceed 100%
        $currentTotal = $this->getTotalOwnershipIdentified($partyId);
        if ($currentTotal + $beneficialOwner->ownershipPercentage > 100.0) {
            throw BeneficialOwnershipException::withErrors([
                sprintf(
                    'Total ownership would exceed 100%% (current: %.2f%%, adding: %.2f%%)',
                    $currentTotal,
                    $beneficialOwner->ownershipPercentage
                ),
            ]);
        }

        // Persist the beneficial owner
        $this->profilePersist->saveBeneficialOwner($partyId, $beneficialOwner);

        // Log audit event
        $this->auditLogger->logVerificationEvent(
            partyId: $partyId,
            eventType: 'beneficial_owner_registered',
            details: [
                'owner_id' => $beneficialOwner->ownerId,
                'owner_name' => $beneficialOwner->name,
                'ownership_percentage' => $beneficialOwner->ownershipPercentage,
                'is_pep' => $beneficialOwner->isPep,
            ],
            performedBy: null
        );

        $this->logger->info('Beneficial owner registered', [
            'party_id' => $partyId,
            'owner_id' => $beneficialOwner->ownerId,
            'ownership_percentage' => $beneficialOwner->ownershipPercentage,
        ]);

        return $beneficialOwner;
    }

    public function updateBeneficialOwner(
        string $partyId,
        string $beneficialOwnerId,
        BeneficialOwner $updatedOwner
    ): BeneficialOwner {
        $existingOwner = $this->getBeneficialOwner($partyId, $beneficialOwnerId);
        if ($existingOwner === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'Beneficial owner not found: ' . $beneficialOwnerId
            );
        }

        // Validate ownership percentage change
        $currentTotal = $this->getTotalOwnershipIdentified($partyId);
        $newTotal = $currentTotal - $existingOwner->ownershipPercentage + $updatedOwner->ownershipPercentage;
        
        if ($newTotal > 100.0) {
            throw BeneficialOwnershipException::withErrors([
                sprintf('Total ownership would exceed 100%% (would be: %.2f%%)', $newTotal),
            ]);
        }

        // Delete old and save updated
        $this->profilePersist->deleteBeneficialOwner($partyId, $beneficialOwnerId);
        $this->profilePersist->saveBeneficialOwner($partyId, $updatedOwner);

        $this->auditLogger->logVerificationEvent(
            partyId: $partyId,
            eventType: 'beneficial_owner_updated',
            details: [
                'owner_id' => $beneficialOwnerId,
                'changes' => $this->calculateChanges($existingOwner, $updatedOwner),
            ],
            performedBy: null
        );

        return $updatedOwner;
    }

    public function removeBeneficialOwner(
        string $partyId,
        string $beneficialOwnerId,
        string $reason
    ): void {
        $existingOwner = $this->getBeneficialOwner($partyId, $beneficialOwnerId);
        if ($existingOwner === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'Beneficial owner not found: ' . $beneficialOwnerId
            );
        }

        $this->profilePersist->deleteBeneficialOwner($partyId, $beneficialOwnerId);

        $this->auditLogger->logVerificationEvent(
            partyId: $partyId,
            eventType: 'beneficial_owner_removed',
            details: [
                'owner_id' => $beneficialOwnerId,
                'owner_name' => $existingOwner->name,
                'reason' => $reason,
            ],
            performedBy: null
        );

        $this->logger->info('Beneficial owner removed', [
            'party_id' => $partyId,
            'owner_id' => $beneficialOwnerId,
            'reason' => $reason,
        ]);
    }

    public function getBeneficialOwners(string $partyId): array
    {
        $profile = $this->profileQuery->findByPartyId($partyId);
        return $profile?->beneficialOwners ?? [];
    }

    public function getBeneficialOwner(
        string $partyId,
        string $beneficialOwnerId
    ): ?BeneficialOwner {
        $owners = $this->getBeneficialOwners($partyId);
        
        foreach ($owners as $owner) {
            if ($owner->ownerId === $beneficialOwnerId) {
                return $owner;
            }
        }

        return null;
    }

    public function getTotalOwnershipIdentified(string $partyId): float
    {
        $owners = $this->getBeneficialOwners($partyId);
        
        return array_sum(
            array_map(
                fn(BeneficialOwner $o) => $o->ownershipPercentage,
                $owners
            )
        );
    }

    public function isOwnershipComplete(string $partyId): bool
    {
        // Complete if >= 100% identified or all significant owners (>= 25%) verified
        $totalIdentified = $this->getTotalOwnershipIdentified($partyId);
        
        if ($totalIdentified >= 100.0) {
            return true;
        }

        // Also check if remaining ownership is below UBO threshold
        $remainingOwnership = 100.0 - $totalIdentified;
        if ($remainingOwnership < self::UBO_THRESHOLD) {
            // Remaining ownership is below threshold - considered complete
            return true;
        }

        return false;
    }

    public function validateOwnershipStructure(string $partyId): array
    {
        $errors = [];
        $owners = $this->getBeneficialOwners($partyId);

        // Check total ownership
        $totalOwnership = $this->getTotalOwnershipIdentified($partyId);
        if ($totalOwnership > 100.0) {
            $errors[] = sprintf('Total ownership exceeds 100%% (%.2f%%)', $totalOwnership);
        }

        // Check for duplicate owners
        $ownerIds = array_map(fn(BeneficialOwner $o) => $o->ownerId, $owners);
        $duplicates = array_filter(
            array_count_values($ownerIds),
            fn(int $count) => $count > 1
        );
        if (!empty($duplicates)) {
            $errors[] = 'Duplicate beneficial owners detected: ' . implode(', ', array_keys($duplicates));
        }

        // Check for circular ownership
        if ($this->detectCircularOwnership($partyId)) {
            $errors[] = 'Circular ownership structure detected';
        }

        // Check unverified significant owners
        $unverifiedOwners = $this->getUnverifiedOwners($partyId);
        $unverifiedSignificant = array_filter(
            $unverifiedOwners,
            fn(BeneficialOwner $o) => $o->ownershipPercentage >= self::UBO_THRESHOLD
        );
        if (!empty($unverifiedSignificant)) {
            $errors[] = sprintf(
                '%d significant beneficial owner(s) (>=25%%) not verified',
                count($unverifiedSignificant)
            );
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function detectCircularOwnership(string $partyId): bool
    {
        return $this->checkCircularOwnership($partyId, [$partyId], 0);
    }

    public function getUnverifiedOwners(string $partyId): array
    {
        $owners = $this->getBeneficialOwners($partyId);
        
        return array_filter(
            $owners,
            fn(BeneficialOwner $o) => !$o->isVerified
        );
    }

    public function getPepOwners(string $partyId): array
    {
        $owners = $this->getBeneficialOwners($partyId);
        
        return array_filter(
            $owners,
            fn(BeneficialOwner $o) => $o->isPep
        );
    }

    public function calculateEffectiveOwnership(
        string $ultimateOwnerId,
        array $ownershipChain
    ): float {
        if (empty($ownershipChain)) {
            return 0.0;
        }

        $effectiveOwnership = 100.0;

        foreach ($ownershipChain as $partyId) {
            $owners = $this->getBeneficialOwners($partyId);
            
            // Find the next party in chain or the ultimate owner
            $nextPartyIndex = array_search($partyId, $ownershipChain);
            $targetId = $nextPartyIndex < count($ownershipChain) - 1
                ? $ownershipChain[$nextPartyIndex + 1]
                : $ultimateOwnerId;

            $ownerRecord = null;
            foreach ($owners as $owner) {
                if ($owner->ownerId === $targetId) {
                    $ownerRecord = $owner;
                    break;
                }
            }

            if ($ownerRecord === null) {
                return 0.0; // Chain broken
            }

            $effectiveOwnership *= ($ownerRecord->ownershipPercentage / 100.0);
        }

        return round($effectiveOwnership, 4);
    }

    public function getOwnershipHierarchy(string $partyId): array
    {
        return $this->buildOwnershipTree($partyId, 0);
    }

    public function isUboTrackingRequired(string $partyId): bool
    {
        $partyTypeValue = $this->partyProvider->getPartyType($partyId);
        if ($partyTypeValue === null) {
            return false;
        }

        $partyType = PartyType::tryFrom($partyTypeValue);
        return $partyType?->requiresUboTracking() ?? false;
    }

    /**
     * Check for circular ownership recursively.
     * 
     * @param array<string> $visited
     */
    private function checkCircularOwnership(
        string $partyId,
        array $visited,
        int $depth
    ): bool {
        if ($depth > self::MAX_OWNERSHIP_DEPTH) {
            return false; // Treat as no circular ownership if too deep
        }

        $owners = $this->getBeneficialOwners($partyId);
        
        foreach ($owners as $owner) {
            // Check if this owner is in the visited chain
            if (in_array($owner->ownerId, $visited, true)) {
                return true;
            }

            // Check if this owner (if corporate) has circular ownership
            $ownerType = $this->partyProvider->getPartyType($owner->ownerId);
            if ($ownerType !== null) {
                $partyType = PartyType::tryFrom($ownerType);
                if ($partyType?->requiresUboTracking() ?? false) {
                    $newVisited = array_merge($visited, [$owner->ownerId]);
                    if ($this->checkCircularOwnership($owner->ownerId, $newVisited, $depth + 1)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Build ownership tree structure.
     * 
     * @return array<string, mixed>
     */
    private function buildOwnershipTree(string $partyId, int $depth): array
    {
        if ($depth > self::MAX_OWNERSHIP_DEPTH) {
            return ['max_depth_exceeded' => true];
        }

        $owners = $this->getBeneficialOwners($partyId);
        
        $tree = [];
        foreach ($owners as $owner) {
            $node = [
                'owner_id' => $owner->ownerId,
                'name' => $owner->name,
                'ownership_percentage' => $owner->ownershipPercentage,
                'is_pep' => $owner->isPep,
                'is_verified' => $owner->isVerified,
                'nationality' => $owner->nationality,
            ];

            // Check if owner requires UBO tracking (corporate)
            $ownerType = $this->partyProvider->getPartyType($owner->ownerId);
            if ($ownerType !== null) {
                $partyType = PartyType::tryFrom($ownerType);
                if ($partyType?->requiresUboTracking() ?? false) {
                    $node['sub_owners'] = $this->buildOwnershipTree($owner->ownerId, $depth + 1);
                }
            }

            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * Calculate changes between old and new owner records.
     * 
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function calculateChanges(
        BeneficialOwner $old,
        BeneficialOwner $new
    ): array {
        $changes = [];

        if ($old->ownershipPercentage !== $new->ownershipPercentage) {
            $changes['ownership_percentage'] = [
                'old' => $old->ownershipPercentage,
                'new' => $new->ownershipPercentage,
            ];
        }

        if ($old->isPep !== $new->isPep) {
            $changes['is_pep'] = [
                'old' => $old->isPep,
                'new' => $new->isPep,
            ];
        }

        if ($old->isVerified !== $new->isVerified) {
            $changes['is_verified'] = [
                'old' => $old->isVerified,
                'new' => $new->isVerified,
            ];
        }

        return $changes;
    }
}
