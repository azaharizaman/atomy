<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Enums;

/**
 * Strength of sanctions match based on fuzzy matching algorithms
 * 
 * Uses combination of Levenshtein distance, phonetic matching (Soundex/Metaphone),
 * and token-based comparison for accuracy
 */
enum MatchStrength: string
{
    /**
     * Exact match (100% similarity)
     * - Name matches exactly
     * - No false positive risk
     * - Immediate block required
     */
    case EXACT = 'exact';
    
    /**
     * High confidence match (85-99% similarity)
     * - Very close match with minor differences
     * - Possible typos, abbreviations, or transliterations
     * - Requires immediate review
     */
    case HIGH = 'high';
    
    /**
     * Medium confidence match (70-84% similarity)
     * - Significant similarities with notable differences
     * - May be false positive
     * - Requires thorough investigation
     */
    case MEDIUM = 'medium';
    
    /**
     * Low confidence match (50-69% similarity)
     * - Some similarities but likely different entities
     * - High false positive probability
     * - Requires manual review to rule out
     */
    case LOW = 'low';
    
    /**
     * No match (<50% similarity)
     * - Insufficient similarity
     * - Different entity
     * - No action required
     */
    case NONE = 'none';
    
    /**
     * Get similarity percentage range
     * 
     * @return array{min: int, max: int}
     */
    public function getSimilarityRange(): array
    {
        return match ($this) {
            self::EXACT => ['min' => 100, 'max' => 100],
            self::HIGH => ['min' => 85, 'max' => 99],
            self::MEDIUM => ['min' => 70, 'max' => 84],
            self::LOW => ['min' => 50, 'max' => 69],
            self::NONE => ['min' => 0, 'max' => 49],
        };
    }
    
    /**
     * Get recommended action based on match strength
     */
    public function getRecommendedAction(): string
    {
        return match ($this) {
            self::EXACT => 'BLOCK - Exact match, transaction must be blocked',
            self::HIGH => 'REVIEW - High confidence match, requires immediate compliance review',
            self::MEDIUM => 'INVESTIGATE - Medium confidence, requires thorough investigation',
            self::LOW => 'VERIFY - Low confidence, manual verification recommended',
            self::NONE => 'PROCEED - No significant match found',
        };
    }
    
    /**
     * Check if match requires blocking transaction
     */
    public function requiresBlocking(): bool
    {
        return $this === self::EXACT;
    }
    
    /**
     * Check if match requires compliance review
     */
    public function requiresReview(): bool
    {
        return match ($this) {
            self::EXACT, self::HIGH, self::MEDIUM => true,
            default => false,
        };
    }
    
    /**
     * Get risk level associated with this match strength
     */
    public function getRiskLevel(): string
    {
        return match ($this) {
            self::EXACT => 'CRITICAL',
            self::HIGH => 'HIGH',
            self::MEDIUM => 'MEDIUM',
            self::LOW => 'LOW',
            self::NONE => 'NONE',
        };
    }
    
    /**
     * Create MatchStrength from similarity score (0-100)
     */
    public static function fromSimilarityScore(float $score): self
    {
        return match (true) {
            $score >= 100 => self::EXACT,
            $score >= 85 => self::HIGH,
            $score >= 70 => self::MEDIUM,
            $score >= 50 => self::LOW,
            default => self::NONE,
        };
    }
}
