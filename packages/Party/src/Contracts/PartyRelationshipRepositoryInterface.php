<?php

declare(strict_types=1);

namespace Nexus\Party\Contracts;

use Nexus\Party\Enums\RelationshipType;

/**
 * Repository interface for party relationship persistence operations.
 */
interface PartyRelationshipRepositoryInterface
{
    /**
     * Find relationship by ID.
     *
     * @param string $id Relationship ULID
     * @return PartyRelationshipInterface|null
     */
    public function findById(string $id): ?PartyRelationshipInterface;
    
    /**
     * Get relationships where party is the source (from).
     *
     * @param string $partyId Party ULID
     * @param RelationshipType|null $type Optional type filter
     * @return array<PartyRelationshipInterface>
     */
    public function getRelationshipsFrom(string $partyId, ?RelationshipType $type = null): array;
    
    /**
     * Get relationships where party is the target (to).
     *
     * @param string $partyId Party ULID
     * @param RelationshipType|null $type Optional type filter
     * @return array<PartyRelationshipInterface>
     */
    public function getRelationshipsTo(string $partyId, ?RelationshipType $type = null): array;
    
    /**
     * Get active relationships for a party.
     *
     * @param string $partyId Party ULID
     * @param \DateTimeInterface|null $asOf Date to check (defaults to now)
     * @return array<PartyRelationshipInterface>
     */
    public function getActiveRelationships(string $partyId, ?\DateTimeInterface $asOf = null): array;
    
    /**
     * Get current employer for an individual.
     *
     * @param string $individualPartyId Individual party ULID
     * @param \DateTimeInterface|null $asOf Date to check (defaults to now)
     * @return PartyRelationshipInterface|null
     */
    public function getCurrentEmployer(string $individualPartyId, ?\DateTimeInterface $asOf = null): ?PartyRelationshipInterface;
    
    /**
     * Get parent organization for a subsidiary.
     *
     * @param string $subsidiaryPartyId Subsidiary party ULID
     * @return PartyRelationshipInterface|null
     */
    public function getParentOrganization(string $subsidiaryPartyId): ?PartyRelationshipInterface;
    
    /**
     * Get all subsidiaries for a parent organization.
     *
     * @param string $parentPartyId Parent organization party ULID
     * @return array<PartyRelationshipInterface>
     */
    public function getSubsidiaries(string $parentPartyId): array;
    
    /**
     * Get the full organizational hierarchy chain.
     *
     * @param string $partyId Party ULID
     * @param int $maxDepth Maximum depth to traverse
     * @return array<PartyRelationshipInterface>
     */
    public function getOrganizationalChain(string $partyId, int $maxDepth = 50): array;
    
    /**
     * Save relationship.
     *
     * @param array<string, mixed> $data Relationship data
     * @return PartyRelationshipInterface
     */
    public function save(array $data): PartyRelationshipInterface;
    
    /**
     * Update relationship.
     *
     * @param string $id Relationship ULID
     * @param array<string, mixed> $data Updated data
     * @return PartyRelationshipInterface
     */
    public function update(string $id, array $data): PartyRelationshipInterface;
    
    /**
     * Delete relationship.
     *
     * @param string $id Relationship ULID
     * @return bool
     */
    public function delete(string $id): bool;
}
