<?php

declare(strict_types=1);

namespace Nexus\Sanctions\ValueObjects;

/**
 * Result of a sanctions screening operation
 * 
 * Immutable value object containing complete screening results
 */
final class ScreeningResult
{
    /**
     * @param string $screeningId Unique identifier for this screening
     * @param string $partyId Identifier of the party screened
     * @param string $partyName Name of the party screened
     * @param string $partyType Type of party (individual, organization, etc.)
     * @param bool $hasMatches Whether any sanctions matches were found
     * @param array<SanctionsMatch> $matches Array of sanctions matches found
     * @param array<PepProfile> $pepProfiles Array of PEP profiles found
     * @param bool $requiresBlocking Whether transaction should be blocked
     * @param bool $requiresReview Whether compliance review is required
     * @param string $overallRiskLevel Overall risk level (CRITICAL, HIGH, MEDIUM, LOW, NONE)
     * @param array<string, mixed> $metadata Additional screening metadata
     * @param \DateTimeImmutable $screenedAt When screening was performed
     * @param float $processingTimeMs Processing time in milliseconds
     */
    public function __construct(
        public readonly string $screeningId,
        public readonly string $partyId,
        public readonly string $partyName,
        public readonly string $partyType,
        public readonly bool $hasMatches,
        public readonly array $matches,
        public readonly array $pepProfiles,
        public readonly bool $requiresBlocking,
        public readonly bool $requiresReview,
        public readonly string $overallRiskLevel,
        public readonly array $metadata,
        public readonly \DateTimeImmutable $screenedAt,
        public readonly float $processingTimeMs
    ) {
        if (empty($this->screeningId)) {
            throw new \InvalidArgumentException('Screening ID cannot be empty');
        }
        
        if (empty($this->partyId)) {
            throw new \InvalidArgumentException('Party ID cannot be empty');
        }
        
        if (empty($this->partyName)) {
            throw new \InvalidArgumentException('Party name cannot be empty');
        }
        
        // Validate matches array contains only SanctionsMatch objects
        foreach ($this->matches as $match) {
            if (!$match instanceof SanctionsMatch) {
                throw new \InvalidArgumentException('Matches array must contain only SanctionsMatch objects');
            }
        }
        
        // Validate pepProfiles array contains only PepProfile objects
        foreach ($this->pepProfiles as $profile) {
            if (!$profile instanceof PepProfile) {
                throw new \InvalidArgumentException('PEP profiles array must contain only PepProfile objects');
            }
        }
        
        if ($this->processingTimeMs < 0) {
            throw new \InvalidArgumentException('Processing time cannot be negative');
        }
        
        // Validate risk level
        $validRiskLevels = ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'NONE'];
        if (!in_array($this->overallRiskLevel, $validRiskLevels, true)) {
            throw new \InvalidArgumentException('Invalid risk level: ' . $this->overallRiskLevel);
        }
    }
    
    /**
     * Create a clean result (no matches found)
     */
    public static function clean(
        string $screeningId,
        string $partyId,
        string $partyName,
        string $partyType,
        \DateTimeImmutable $screenedAt,
        float $processingTimeMs,
        array $metadata = []
    ): self {
        return new self(
            screeningId: $screeningId,
            partyId: $partyId,
            partyName: $partyName,
            partyType: $partyType,
            hasMatches: false,
            matches: [],
            pepProfiles: [],
            requiresBlocking: false,
            requiresReview: false,
            overallRiskLevel: 'NONE',
            metadata: $metadata,
            screenedAt: $screenedAt,
            processingTimeMs: $processingTimeMs
        );
    }
    
    /**
     * Get count of sanctions matches
     */
    public function getMatchesCount(): int
    {
        return count($this->matches);
    }
    
    /**
     * Get count of PEP profiles
     */
    public function getPepCount(): int
    {
        return count($this->pepProfiles);
    }
    
    /**
     * Check if screening is clean (no matches, no PEPs)
     */
    public function isClean(): bool
    {
        return !$this->hasMatches && count($this->pepProfiles) === 0;
    }
    
    /**
     * Check if has PEP matches
     */
    public function hasPepMatches(): bool
    {
        return count($this->pepProfiles) > 0;
    }
    
    /**
     * Get highest match strength from all matches
     */
    public function getHighestMatchStrength(): ?string
    {
        if (count($this->matches) === 0) {
            return null;
        }
        
        $strengths = array_map(
            fn(SanctionsMatch $match) => $match->matchStrength->value,
            $this->matches
        );
        
        // Order: exact > high > medium > low > none
        $order = ['exact', 'high', 'medium', 'low', 'none'];
        
        foreach ($order as $strength) {
            if (in_array($strength, $strengths, true)) {
                return $strength;
            }
        }
        
        return null;
    }
    
    /**
     * Get highest PEP level from all PEP profiles
     */
    public function getHighestPepLevel(): ?string
    {
        if (count($this->pepProfiles) === 0) {
            return null;
        }
        
        $levels = array_map(
            fn(PepProfile $profile) => $profile->level->value,
            $this->pepProfiles
        );
        
        // Order: high > medium > low > none
        $order = ['high', 'medium', 'low', 'none'];
        
        foreach ($order as $level) {
            if (in_array($level, $levels, true)) {
                return $level;
            }
        }
        
        return null;
    }
    
    /**
     * Get matches by sanctions list
     * 
     * @return array<string, array<SanctionsMatch>>
     */
    public function getMatchesByList(): array
    {
        $grouped = [];
        
        foreach ($this->matches as $match) {
            $listValue = $match->list->value;
            if (!isset($grouped[$listValue])) {
                $grouped[$listValue] = [];
            }
            $grouped[$listValue][] = $match;
        }
        
        return $grouped;
    }
    
    /**
     * Get lists that had matches
     * 
     * @return array<string>
     */
    public function getMatchedLists(): array
    {
        return array_keys($this->getMatchesByList());
    }
    
    /**
     * Get recommended action based on screening results
     */
    public function getRecommendedAction(): string
    {
        if ($this->requiresBlocking) {
            return 'BLOCK - Transaction must be blocked immediately due to exact sanctions match';
        }
        
        if ($this->requiresReview) {
            return 'REVIEW - Immediate compliance review required';
        }
        
        if ($this->hasPepMatches()) {
            return 'EDD - Enhanced Due Diligence required for PEP relationship';
        }
        
        return 'PROCEED - No significant matches found, proceed with transaction';
    }
    
    /**
     * Convert to array for serialization
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'screening_id' => $this->screeningId,
            'party_id' => $this->partyId,
            'party_name' => $this->partyName,
            'party_type' => $this->partyType,
            'has_matches' => $this->hasMatches,
            'is_clean' => $this->isClean(),
            'matches_count' => $this->getMatchesCount(),
            'pep_count' => $this->getPepCount(),
            'has_pep_matches' => $this->hasPepMatches(),
            'requires_blocking' => $this->requiresBlocking,
            'requires_review' => $this->requiresReview,
            'overall_risk_level' => $this->overallRiskLevel,
            'highest_match_strength' => $this->getHighestMatchStrength(),
            'highest_pep_level' => $this->getHighestPepLevel(),
            'matched_lists' => $this->getMatchedLists(),
            'recommended_action' => $this->getRecommendedAction(),
            'matches' => array_map(fn(SanctionsMatch $m) => $m->toArray(), $this->matches),
            'pep_profiles' => array_map(fn(PepProfile $p) => $p->toArray(), $this->pepProfiles),
            'metadata' => $this->metadata,
            'screened_at' => $this->screenedAt->format('c'),
            'processing_time_ms' => $this->processingTimeMs,
        ];
    }
}
