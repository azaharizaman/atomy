<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Contracts;

/**
 * Interface for sanctions check adapter.
 *
 * This interface defines the contract for the orchestrator's sanctions screening
 * needs. Adapters implement this interface using the Sanctions package.
 *
 * Following Interface Segregation from ARCHITECTURE.md:
 * Orchestrators define their own interfaces, not depending on atomic package interfaces.
 */
interface SanctionsCheckAdapterInterface
{
    /**
     * Screen a party against sanctions lists.
     *
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data for screening
     * @param array<string>|null $lists Specific lists to screen (null for default)
     * @return array<string, mixed> Screening result
     */
    public function screen(string $partyId, array $partyData, ?array $lists = null): array;

    /**
     * Screen a party for PEP status.
     *
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data for screening
     * @return array<int, array<string, mixed>> PEP profiles found
     */
    public function screenForPep(string $partyId, array $partyData): array;

    /**
     * Check if party has sanctions matches.
     *
     * @param string $partyId Party identifier
     */
    public function hasMatches(string $partyId): bool;

    /**
     * Check if party is a PEP.
     *
     * @param string $partyId Party identifier
     */
    public function isPep(string $partyId): bool;

    /**
     * Get screening result for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed>|null Screening result or null if not screened
     */
    public function getScreeningResult(string $partyId): ?array;

    /**
     * Get recommended screening frequency.
     *
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data
     * @return string Recommended frequency
     */
    public function getRecommendedFrequency(string $partyId, array $partyData): string;

    /**
     * Calculate similarity between two names.
     *
     * @param string $name1 First name
     * @param string $name2 Second name
     * @return float Similarity score (0.0-1.0)
     */
    public function calculateSimilarity(string $name1, string $name2): float;
}
