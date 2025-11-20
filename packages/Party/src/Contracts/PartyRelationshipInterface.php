<?php

declare(strict_types=1);

namespace Nexus\Party\Contracts;

use Nexus\Party\Enums\RelationshipType;

/**
 * Party relationship interface.
 * 
 * Represents a relationship between two parties with effective dates.
 * Examples: employment, contact person, subsidiary, partnership.
 */
interface PartyRelationshipInterface
{
    /**
     * Get the unique identifier for this relationship.
     */
    public function getId(): string;
    
    /**
     * Get the tenant identifier.
     */
    public function getTenantId(): string;
    
    /**
     * Get the "from" party identifier (source of the relationship).
     * 
     * For EMPLOYMENT_AT: the employee (individual)
     * For CONTACT_FOR: the contact person (individual)
     * For SUBSIDIARY_OF: the subsidiary (organization)
     */
    public function getFromPartyId(): string;
    
    /**
     * Get the "to" party identifier (target of the relationship).
     * 
     * For EMPLOYMENT_AT: the employer (organization)
     * For CONTACT_FOR: the organization being represented
     * For SUBSIDIARY_OF: the parent company (organization)
     */
    public function getToPartyId(): string;
    
    /**
     * Get the relationship type.
     */
    public function getRelationshipType(): RelationshipType;
    
    /**
     * Get the effective start date of this relationship.
     */
    public function getEffectiveFrom(): \DateTimeInterface;
    
    /**
     * Get the effective end date of this relationship (null if currently active).
     */
    public function getEffectiveTo(): ?\DateTimeInterface;
    
    /**
     * Check if this relationship is currently active.
     */
    public function isActive(?\DateTimeInterface $asOf = null): bool;
    
    /**
     * Get the role/title in this relationship (e.g., "CEO", "Primary Contact").
     */
    public function getRole(): ?string;
    
    /**
     * Get additional metadata as key-value pairs.
     * 
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
    
    /**
     * Get the creation timestamp.
     */
    public function getCreatedAt(): \DateTimeInterface;
    
    /**
     * Get the last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeInterface;
}
