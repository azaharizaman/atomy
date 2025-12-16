<?php

declare(strict_types=1);

namespace Nexus\Sanctions\ValueObjects;

use Nexus\Sanctions\Enums\MatchStrength;
use Nexus\Sanctions\Enums\SanctionsList;

/**
 * Represents a single sanctions match from screening
 * 
 * Immutable value object containing all details of a sanctions list match
 */
final class SanctionsMatch
{
    /**
     * @param string $listEntryId Unique identifier in the sanctions list
     * @param SanctionsList $list The sanctions list where match was found
     * @param string $matchedName Name from sanctions list that matched
     * @param MatchStrength $matchStrength Strength of the match
     * @param float $similarityScore Similarity score (0-100)
     * @param array<string, mixed> $additionalInfo Additional information from sanctions list (aliases, DOB, nationality, etc.)
     * @param \DateTimeImmutable $matchedAt When the match was detected
     */
    public function __construct(
        public readonly string $listEntryId,
        public readonly SanctionsList $list,
        public readonly string $matchedName,
        public readonly MatchStrength $matchStrength,
        public readonly float $similarityScore,
        public readonly array $additionalInfo,
        public readonly \DateTimeImmutable $matchedAt
    ) {
        if (empty($this->listEntryId)) {
            throw new \InvalidArgumentException('List entry ID cannot be empty');
        }
        
        if (empty($this->matchedName)) {
            throw new \InvalidArgumentException('Matched name cannot be empty');
        }
        
        if ($this->similarityScore < 0 || $this->similarityScore > 100) {
            throw new \InvalidArgumentException('Similarity score must be between 0 and 100');
        }
    }
    
    /**
     * Get aliases from additional info
     * 
     * @return array<string>
     */
    public function getAliases(): array
    {
        return $this->additionalInfo['aliases'] ?? [];
    }
    
    /**
     * Get date of birth from additional info
     */
    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        $dob = $this->additionalInfo['date_of_birth'] ?? null;
        
        if ($dob === null) {
            return null;
        }
        
        if ($dob instanceof \DateTimeImmutable) {
            return $dob;
        }
        
        if (is_string($dob)) {
            try {
                return new \DateTimeImmutable($dob);
            } catch (\Exception) {
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Get nationality from additional info
     */
    public function getNationality(): ?string
    {
        return $this->additionalInfo['nationality'] ?? null;
    }
    
    /**
     * Get passport number from additional info
     */
    public function getPassportNumber(): ?string
    {
        return $this->additionalInfo['passport_number'] ?? null;
    }
    
    /**
     * Get national ID from additional info
     */
    public function getNationalId(): ?string
    {
        return $this->additionalInfo['national_id'] ?? null;
    }
    
    /**
     * Get address from additional info
     */
    public function getAddress(): ?string
    {
        return $this->additionalInfo['address'] ?? null;
    }
    
    /**
     * Get program/reason for sanctions from additional info
     */
    public function getProgram(): ?string
    {
        return $this->additionalInfo['program'] ?? null;
    }
    
    /**
     * Get remarks/notes from additional info
     */
    public function getRemarks(): ?string
    {
        return $this->additionalInfo['remarks'] ?? null;
    }
    
    /**
     * Check if match requires immediate blocking
     */
    public function requiresBlocking(): bool
    {
        return $this->matchStrength->requiresBlocking();
    }
    
    /**
     * Check if match requires compliance review
     */
    public function requiresReview(): bool
    {
        return $this->matchStrength->requiresReview();
    }
    
    /**
     * Get risk level
     */
    public function getRiskLevel(): string
    {
        return $this->matchStrength->getRiskLevel();
    }
    
    /**
     * Get recommended action
     */
    public function getRecommendedAction(): string
    {
        return $this->matchStrength->getRecommendedAction();
    }
    
    /**
     * Convert to array for serialization
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'list_entry_id' => $this->listEntryId,
            'list' => $this->list->value,
            'list_name' => $this->list->getName(),
            'matched_name' => $this->matchedName,
            'match_strength' => $this->matchStrength->value,
            'similarity_score' => $this->similarityScore,
            'risk_level' => $this->getRiskLevel(),
            'requires_blocking' => $this->requiresBlocking(),
            'requires_review' => $this->requiresReview(),
            'recommended_action' => $this->getRecommendedAction(),
            'additional_info' => $this->additionalInfo,
            'matched_at' => $this->matchedAt->format('c'),
        ];
    }
}
