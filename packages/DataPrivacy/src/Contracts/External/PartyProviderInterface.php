<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts\External;

/**
 * External interface for retrieving party (customer/user) information.
 *
 * This interface must be implemented by the consuming application,
 * typically using the Nexus\Party package or similar.
 */
interface PartyProviderInterface
{
    /**
     * Check if a party exists.
     */
    public function partyExists(string $partyId): bool;

    /**
     * Get party email address.
     */
    public function getPartyEmail(string $partyId): ?string;

    /**
     * Get party full name.
     */
    public function getPartyName(string $partyId): ?string;

    /**
     * Get all personal data associated with a party.
     *
     * @return array<string, mixed> Key-value pairs of personal data
     */
    public function getPersonalData(string $partyId): array;

    /**
     * Delete all personal data for a party (right to erasure).
     */
    public function deletePersonalData(string $partyId): void;

    /**
     * Anonymize personal data for a party.
     * Used when deletion is not possible due to legal requirements.
     */
    public function anonymizePersonalData(string $partyId): void;

    /**
     * Export personal data in a portable format.
     *
     * @return array<string, mixed> Structured personal data for portability
     */
    public function exportPersonalData(string $partyId): array;

    /**
     * Rectify (update/correct) personal data.
     *
     * @param array<string, mixed> $corrections Key-value pairs of corrections
     */
    public function rectifyPersonalData(string $partyId, array $corrections): void;
}
