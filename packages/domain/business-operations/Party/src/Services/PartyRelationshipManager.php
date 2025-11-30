<?php

declare(strict_types=1);

namespace Nexus\Party\Services;

use Nexus\Party\Contracts\PartyRepositoryInterface;
use Nexus\Party\Contracts\PartyRelationshipRepositoryInterface;
use Nexus\Party\Contracts\PartyRelationshipInterface;
use Nexus\Party\Enums\PartyType;
use Nexus\Party\Enums\RelationshipType;
use Nexus\Party\Exceptions\PartyNotFoundException;
use Nexus\Party\Exceptions\InvalidRelationshipException;
use Nexus\Party\Exceptions\CircularRelationshipException;
use Psr\Log\LoggerInterface;

/**
 * Party relationship management service.
 * 
 * Manages relationships between parties (employment, contact, hierarchy, etc.)
 * with validation for party types and circular references.
 */
final class PartyRelationshipManager
{
    public function __construct(
        private readonly PartyRepositoryInterface $partyRepository,
        private readonly PartyRelationshipRepositoryInterface $relationshipRepository,
        private readonly LoggerInterface $logger
    ) {}
    
    /**
     * Create a new relationship between two parties.
     *
     * @param string $tenantId Tenant ULID
     * @param string $fromPartyId Source party ULID
     * @param string $toPartyId Target party ULID
     * @param RelationshipType $type Relationship type
     * @param \DateTimeInterface $effectiveFrom Effective start date
     * @param \DateTimeInterface|null $effectiveTo Effective end date (null if ongoing)
     * @param string|null $role Role/title in this relationship
     * @param array<string, mixed> $metadata Additional metadata
     * @return PartyRelationshipInterface
     * @throws PartyNotFoundException
     * @throws InvalidRelationshipException
     * @throws CircularRelationshipException
     */
    public function createRelationship(
        string $tenantId,
        string $fromPartyId,
        string $toPartyId,
        RelationshipType $type,
        \DateTimeInterface $effectiveFrom,
        ?\DateTimeInterface $effectiveTo = null,
        ?string $role = null,
        array $metadata = []
    ): PartyRelationshipInterface {
        // Validate parties exist
        $fromParty = $this->partyRepository->findById($fromPartyId);
        if (!$fromParty) {
            throw PartyNotFoundException::forId($fromPartyId);
        }
        
        $toParty = $this->partyRepository->findById($toPartyId);
        if (!$toParty) {
            throw PartyNotFoundException::forId($toPartyId);
        }
        
        // Validate not linking party to itself
        if ($fromPartyId === $toPartyId) {
            throw InvalidRelationshipException::sameParty($fromPartyId);
        }
        
        // Validate party types for relationship
        $this->validatePartyTypes($fromParty->getPartyType(), $toParty->getPartyType(), $type);
        
        // Validate date range
        if ($effectiveTo && $effectiveTo <= $effectiveFrom) {
            throw InvalidRelationshipException::invalidDateRange($effectiveFrom, $effectiveTo);
        }
        
        // Check for circular references if required
        if ($type->requiresCircularCheck()) {
            $this->validateNoCircularReference($fromPartyId, $toPartyId);
        }
        
        $data = [
            'tenant_id' => $tenantId,
            'from_party_id' => $fromPartyId,
            'to_party_id' => $toPartyId,
            'relationship_type' => $type->value,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'role' => $role,
            'metadata' => $metadata,
        ];
        
        $relationship = $this->relationshipRepository->save($data);
        
        $this->logger->info("Party relationship created", [
            'relationship_id' => $relationship->getId(),
            'from_party_id' => $fromPartyId,
            'to_party_id' => $toPartyId,
            'type' => $type->value,
            'tenant_id' => $tenantId,
        ]);
        
        return $relationship;
    }
    
    /**
     * End a relationship by setting the effective_to date.
     *
     * @param string $relationshipId Relationship ULID
     * @param \DateTimeInterface $effectiveTo End date
     * @return PartyRelationshipInterface
     */
    public function endRelationship(
        string $relationshipId,
        \DateTimeInterface $effectiveTo
    ): PartyRelationshipInterface {
        $relationship = $this->relationshipRepository->findById($relationshipId);
        if (!$relationship) {
            throw new \RuntimeException("Relationship not found: {$relationshipId}");
        }
        
        if ($effectiveTo <= $relationship->getEffectiveFrom()) {
            throw InvalidRelationshipException::invalidDateRange(
                $relationship->getEffectiveFrom(),
                $effectiveTo
            );
        }
        
        $updated = $this->relationshipRepository->update($relationshipId, [
            'effective_to' => $effectiveTo,
        ]);
        
        $this->logger->info("Party relationship ended", [
            'relationship_id' => $relationshipId,
            'effective_to' => $effectiveTo->format('Y-m-d'),
        ]);
        
        return $updated;
    }
    
    /**
     * Update relationship metadata or role.
     *
     * @param string $relationshipId Relationship ULID
     * @param array<string, mixed> $data Updated data
     * @return PartyRelationshipInterface
     */
    public function updateRelationship(string $relationshipId, array $data): PartyRelationshipInterface
    {
        return $this->relationshipRepository->update($relationshipId, $data);
    }
    
    /**
     * Get current employer for an individual.
     *
     * @param string $individualPartyId Individual party ULID
     * @param \DateTimeInterface|null $asOf Date to check (defaults to now)
     * @return PartyRelationshipInterface|null
     */
    public function getCurrentEmployer(
        string $individualPartyId,
        ?\DateTimeInterface $asOf = null
    ): ?PartyRelationshipInterface {
        return $this->relationshipRepository->getCurrentEmployer($individualPartyId, $asOf);
    }
    
    /**
     * Get employment history for an individual.
     *
     * @param string $individualPartyId Individual party ULID
     * @return array<PartyRelationshipInterface>
     */
    public function getEmploymentHistory(string $individualPartyId): array
    {
        return $this->relationshipRepository->getRelationshipsFrom(
            $individualPartyId,
            RelationshipType::EMPLOYMENT_AT
        );
    }
    
    /**
     * Get all subsidiaries for a parent organization.
     *
     * @param string $parentPartyId Parent organization party ULID
     * @return array<PartyRelationshipInterface>
     */
    public function getSubsidiaries(string $parentPartyId): array
    {
        return $this->relationshipRepository->getSubsidiaries($parentPartyId);
    }
    
    /**
     * Get parent organization for a subsidiary.
     *
     * @param string $subsidiaryPartyId Subsidiary party ULID
     * @return PartyRelationshipInterface|null
     */
    public function getParentOrganization(string $subsidiaryPartyId): ?PartyRelationshipInterface
    {
        return $this->relationshipRepository->getParentOrganization($subsidiaryPartyId);
    }
    
    /**
     * Get the full organizational hierarchy chain.
     *
     * @param string $partyId Party ULID
     * @param int $maxDepth Maximum depth to traverse
     * @return array<PartyRelationshipInterface>
     */
    public function getOrganizationalChain(string $partyId, int $maxDepth = 50): array
    {
        return $this->relationshipRepository->getOrganizationalChain($partyId, $maxDepth);
    }
    
    /**
     * Get all active relationships for a party.
     *
     * @param string $partyId Party ULID
     * @param \DateTimeInterface|null $asOf Date to check (defaults to now)
     * @return array<PartyRelationshipInterface>
     */
    public function getActiveRelationships(
        string $partyId,
        ?\DateTimeInterface $asOf = null
    ): array {
        return $this->relationshipRepository->getActiveRelationships($partyId, $asOf);
    }
    
    /**
     * Validate party types are compatible with relationship type.
     *
     * @throws InvalidRelationshipException
     */
    private function validatePartyTypes(
        PartyType $fromType,
        PartyType $toType,
        RelationshipType $relationshipType
    ): void {
        // Check if relationship requires individual as source
        if ($relationshipType->requiresIndividualFrom() && !$fromType->isIndividual()) {
            throw InvalidRelationshipException::individualRequired($relationshipType->value);
        }
        
        // Check if relationship requires organization as target
        if ($relationshipType->requiresOrganizationTo() && !$toType->isOrganization()) {
            throw InvalidRelationshipException::organizationRequired($relationshipType->value);
        }
    }
    
    /**
     * Validate no circular reference exists in organizational hierarchy.
     *
     * @param string $fromPartyId Source party (subsidiary)
     * @param string $toPartyId Target party (proposed parent)
     * @throws CircularRelationshipException
     */
    private function validateNoCircularReference(string $fromPartyId, string $toPartyId): void
    {
        // Walk up the parent chain from proposed parent
        $currentId = $toPartyId;
        $depth = 0;
        $maxDepth = 50;
        
        while ($currentId && $depth < $maxDepth) {
            // If we encounter the subsidiary in the parent chain, it's circular
            if ($currentId === $fromPartyId) {
                throw CircularRelationshipException::detected($fromPartyId, $toPartyId);
            }
            
            // Get parent of current organization
            $parentRelationship = $this->relationshipRepository->getParentOrganization($currentId);
            $currentId = $parentRelationship?->getToPartyId();
            
            $depth++;
        }
        
        if ($depth >= $maxDepth) {
            throw CircularRelationshipException::maxDepthExceeded($toPartyId, $maxDepth);
        }
    }
}
