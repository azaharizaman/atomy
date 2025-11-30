<?php

declare(strict_types=1);

namespace Nexus\Party\Contracts;

use Nexus\Party\Enums\PartyType;
use Nexus\Party\ValueObjects\TaxIdentity;

/**
 * Party entity interface.
 * 
 * Represents a universal entity abstraction (individual or organization)
 * that serves as the master data for all business relationships.
 */
interface PartyInterface
{
    /**
     * Get the unique identifier for the party.
     */
    public function getId(): string;
    
    /**
     * Get the tenant identifier.
     */
    public function getTenantId(): string;
    
    /**
     * Get the party type (INDIVIDUAL or ORGANIZATION).
     */
    public function getPartyType(): PartyType;
    
    /**
     * Get the legal name (full registered name).
     */
    public function getLegalName(): string;
    
    /**
     * Get the trading name (DBA - Doing Business As).
     * 
     * For individuals, this might be their preferred name or nickname.
     * For organizations, this is their brand/trading name.
     */
    public function getTradingName(): ?string;
    
    /**
     * Get the tax identity (if applicable).
     */
    public function getTaxIdentity(): ?TaxIdentity;
    
    /**
     * Get the date of birth (for individuals only).
     */
    public function getDateOfBirth(): ?\DateTimeInterface;
    
    /**
     * Get the date of incorporation/registration (for organizations only).
     */
    public function getRegistrationDate(): ?\DateTimeInterface;
    
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
    
    /**
     * Check if this party is an individual (natural person).
     */
    public function isIndividual(): bool;
    
    /**
     * Check if this party is an organization (legal entity).
     */
    public function isOrganization(): bool;
}
