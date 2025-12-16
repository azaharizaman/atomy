<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Contracts;

use Nexus\Sanctions\Enums\SanctionsList;
use Nexus\Sanctions\ValueObjects\ScreeningResult;

/**
 * Interface for sanctions screening operations.
 * 
 * Provides contract for screening parties against international sanctions lists
 * using fuzzy matching algorithms (Levenshtein distance, phonetic matching).
 * 
 * Implementing classes should provide:
 * - Multi-list concurrent screening
 * - Fuzzy name matching with configurable thresholds
 * - Phonetic matching (Soundex/Metaphone)
 * - Token-based comparison for multi-word names
 * - Performance optimization (caching, batch processing)
 * 
 * @package Nexus\Sanctions\Contracts
 */
interface SanctionsScreenerInterface
{
    /**
     * Screen a party against specified sanctions lists.
     *
     * Performs fuzzy matching against all specified lists concurrently.
     * Returns comprehensive result with all matches and risk assessment.
     *
     * @param PartyInterface $party Party to screen
     * @param array<SanctionsList> $lists Sanctions lists to screen against
     * @param array<string, mixed> $options Screening options:
     *        - 'similarity_threshold' => float (default: 0.85)
     *        - 'phonetic_matching' => bool (default: true)
     *        - 'token_based' => bool (default: true)
     *        - 'include_aliases' => bool (default: true)
     * @return ScreeningResult
     * @throws \Nexus\Sanctions\Exceptions\InvalidPartyException If party data invalid
     * @throws \Nexus\Sanctions\Exceptions\ScreeningFailedException If screening fails
     * @throws \Nexus\Sanctions\Exceptions\SanctionsListUnavailableException If list unavailable
     */
    public function screen(
        PartyInterface $party,
        array $lists,
        array $options = []
    ): ScreeningResult;

    /**
     * Screen multiple parties in batch.
     *
     * More efficient than individual screening for large volumes.
     * Processes parties concurrently when possible.
     *
     * @param array<PartyInterface> $parties Parties to screen
     * @param array<SanctionsList> $lists Sanctions lists to screen against
     * @param array<string, mixed> $options Screening options (same as screen())
     * @return array<string, ScreeningResult> Results keyed by party ID
     * @throws \Nexus\Sanctions\Exceptions\ScreeningFailedException If batch screening fails
     */
    public function screenMultiple(
        array $parties,
        array $lists,
        array $options = []
    ): array;

    /**
     * Screen party name against a specific list.
     *
     * Lower-level method for targeted screening against single list.
     * Useful for incremental screening or specific list checks.
     *
     * @param string $name Name to screen
     * @param SanctionsList $list Sanctions list to screen against
     * @param array<string, mixed> $options Screening options
     * @return array<\Nexus\Sanctions\ValueObjects\SanctionsMatch>
     * @throws \Nexus\Sanctions\Exceptions\ScreeningFailedException If screening fails
     */
    public function screenName(
        string $name,
        SanctionsList $list,
        array $options = []
    ): array;

    /**
     * Calculate similarity score between two names.
     *
     * Uses fuzzy matching algorithms (Levenshtein, phonetic).
     * Score ranges from 0.0 (no match) to 1.0 (exact match).
     *
     * @param string $name1 First name
     * @param string $name2 Second name
     * @return float Similarity score (0.0-1.0)
     */
    public function calculateSimilarity(string $name1, string $name2): float;

    /**
     * Get recommended screening frequency for a party.
     *
     * Based on party risk profile, transaction volume, and jurisdiction.
     *
     * @param PartyInterface $party Party to assess
     * @return \Nexus\Sanctions\Enums\ScreeningFrequency
     */
    public function getRecommendedFrequency(PartyInterface $party): \Nexus\Sanctions\Enums\ScreeningFrequency;
}
