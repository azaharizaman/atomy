<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Contracts;

/**
 * Sanctions screening result interface
 * 
 * This interface defines the sanctions check result contract required by AmlCompliance.
 * The orchestrator layer (or consuming application) must provide an adapter that
 * implements this interface, bridging to the actual Nexus\Sanctions package.
 * 
 * This ensures atomicity - AmlCompliance doesn't directly depend on Nexus\Sanctions.
 */
interface SanctionsResultInterface
{
    /**
     * Get the screened party identifier
     */
    public function getPartyId(): string;

    /**
     * Check if party has any sanctions matches
     */
    public function hasMatches(): bool;

    /**
     * Get the number of sanctions matches
     */
    public function getMatchCount(): int;

    /**
     * Get the highest match score (0-100)
     * Higher score = more confident match
     */
    public function getHighestMatchScore(): int;

    /**
     * Check if any match is confirmed (not just potential)
     */
    public function hasConfirmedMatch(): bool;

    /**
     * Get risk score based on sanctions findings (0-100)
     * 
     * Typical scoring:
     * - No matches: 0
     * - Low-confidence matches: 10-30
     * - Medium-confidence matches: 40-60
     * - High-confidence matches: 70-90
     * - Confirmed sanctions hit: 100
     */
    public function getRiskScore(): int;

    /**
     * Get list of matched sanctions lists
     * 
     * @return array<string> List identifiers (e.g., OFAC_SDN, UN_CONSOLIDATED, EU_SANCTIONS)
     */
    public function getMatchedLists(): array;

    /**
     * Get screening timestamp
     */
    public function getScreenedAt(): \DateTimeImmutable;

    /**
     * Check if screening is stale (older than specified hours)
     */
    public function isStale(int $maxAgeHours = 24): bool;

    /**
     * Get summary of matches for reporting
     * 
     * @return array<array{
     *     list_id: string,
     *     match_score: int,
     *     matched_name: string,
     *     match_type: string,
     *     is_confirmed: bool
     * }>
     */
    public function getMatchSummary(): array;

    /**
     * Check if party requires enhanced due diligence based on sanctions
     */
    public function requiresEnhancedDueDiligence(): bool;

    /**
     * Check if party is completely blocked (sanctions hit)
     */
    public function isBlocked(): bool;

    /**
     * Get detailed findings for compliance review
     * 
     * @return array<string, mixed>
     */
    public function getFindings(): array;
}
