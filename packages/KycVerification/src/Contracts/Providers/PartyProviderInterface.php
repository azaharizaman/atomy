<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts\Providers;

use Nexus\KycVerification\Enums\PartyType;

/**
 * Interface for party data access.
 * 
 * This interface abstracts party information access.
 * The orchestrator layer must implement this using Nexus\Party or similar.
 */
interface PartyProviderInterface
{
    /**
     * Find party by ID
     * 
     * @return array<string, mixed>|null Party data or null if not found
     */
    public function findById(string $partyId): ?array;

    /**
     * Get party type
     */
    public function getPartyType(string $partyId): ?PartyType;

    /**
     * Get party name
     */
    public function getPartyName(string $partyId): ?string;

    /**
     * Get party registration/incorporation country
     */
    public function getPartyCountry(string $partyId): ?string;

    /**
     * Get party industry/sector
     */
    public function getPartyIndustry(string $partyId): ?string;

    /**
     * Get party registration date
     */
    public function getPartyRegistrationDate(string $partyId): ?\DateTimeImmutable;

    /**
     * Check if party is active
     */
    public function isPartyActive(string $partyId): bool;

    /**
     * Get related parties (parent company, subsidiaries, etc.)
     * 
     * @return array<string> Party IDs of related parties
     */
    public function getRelatedParties(string $partyId): array;

    /**
     * Get party's primary contact information
     * 
     * @return array{email?: string, phone?: string, address?: array<string, mixed>}|null
     */
    public function getContactInformation(string $partyId): ?array;
}
