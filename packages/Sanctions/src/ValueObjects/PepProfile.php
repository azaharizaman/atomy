<?php

declare(strict_types=1);

namespace Nexus\Sanctions\ValueObjects;

use Nexus\Sanctions\Enums\PepLevel;

/**
 * Represents a PEP (Politically Exposed Person) profile
 * 
 * Immutable value object containing PEP classification and details
 */
final class PepProfile
{
    /**
     * @param string $pepId Unique PEP identifier
     * @param string $name Full name of the PEP
     * @param PepLevel $level Risk level of political exposure
     * @param string $position Political position/role
     * @param string $country Country of political exposure
     * @param string|null $organization Government body or organization
     * @param \DateTimeImmutable|null $startDate Date position started
     * @param \DateTimeImmutable|null $endDate Date position ended (null if current)
     * @param array<string> $relatedPersons Family members or close associates
     * @param array<string, mixed> $additionalInfo Additional PEP information
     * @param \DateTimeImmutable $identifiedAt When PEP status was identified
     */
    public function __construct(
        public readonly string $pepId,
        public readonly string $name,
        public readonly PepLevel $level,
        public readonly string $position,
        public readonly string $country,
        public readonly ?string $organization,
        public readonly ?\DateTimeImmutable $startDate,
        public readonly ?\DateTimeImmutable $endDate,
        public readonly array $relatedPersons,
        public readonly array $additionalInfo,
        public readonly \DateTimeImmutable $identifiedAt
    ) {
        if (empty($this->pepId)) {
            throw new \InvalidArgumentException('PEP ID cannot be empty');
        }
        
        if (empty($this->name)) {
            throw new \InvalidArgumentException('PEP name cannot be empty');
        }
        
        if (empty($this->position)) {
            throw new \InvalidArgumentException('Position cannot be empty');
        }
        
        if (empty($this->country)) {
            throw new \InvalidArgumentException('Country cannot be empty');
        }
        
        if ($this->startDate !== null && $this->endDate !== null && $this->endDate < $this->startDate) {
            throw new \InvalidArgumentException('End date cannot be before start date');
        }
    }
    
    /**
     * Check if PEP is currently active
     */
    public function isActive(): bool
    {
        return $this->endDate === null;
    }
    
    /**
     * Check if PEP is former (left position >12 months ago per FATF guidance)
     */
    public function isFormer(): bool
    {
        if ($this->endDate === null) {
            return false;
        }
        
        $twelveMonthsAgo = new \DateTimeImmutable('-12 months');
        return $this->endDate < $twelveMonthsAgo;
    }
    
    /**
     * Check if Enhanced Due Diligence is required
     */
    public function requiresEdd(): bool
    {
        return $this->level->requiresEdd();
    }
    
    /**
     * Check if senior management approval is required
     */
    public function requiresSeniorApproval(): bool
    {
        return $this->level->requiresSeniorApproval();
    }
    
    /**
     * Get risk score (0-100)
     */
    public function getRiskScore(): int
    {
        $baseScore = $this->level->getRiskScore();
        
        // Reduce risk for former PEPs
        if ($this->isFormer()) {
            return (int) ($baseScore * 0.6); // 40% reduction for former PEPs
        }
        
        return $baseScore;
    }
    
    /**
     * Get Enhanced Due Diligence requirements
     */
    public function getEddRequirements(): array
    {
        return $this->level->getEddRequirements();
    }
    
    /**
     * Get monitoring frequency in days
     */
    public function getMonitoringFrequencyDays(): int
    {
        return $this->level->getMonitoringFrequencyDays();
    }
    
    /**
     * Get duration of position tenure (in days)
     */
    public function getTenureDays(): ?int
    {
        if ($this->startDate === null) {
            return null;
        }
        
        $endDate = $this->endDate ?? new \DateTimeImmutable();
        return $this->startDate->diff($endDate)->days;
    }
    
    /**
     * Get years since left position (for former PEPs)
     */
    public function getYearsSinceEnd(): ?float
    {
        if ($this->endDate === null) {
            return null;
        }
        
        $now = new \DateTimeImmutable();
        $diff = $this->endDate->diff($now);
        
        return $diff->y + ($diff->m / 12) + ($diff->d / 365);
    }
    
    /**
     * Check if has related persons (family/close associates)
     */
    public function hasRelatedPersons(): bool
    {
        return count($this->relatedPersons) > 0;
    }
    
    /**
     * Get related persons count
     */
    public function getRelatedPersonsCount(): int
    {
        return count($this->relatedPersons);
    }
    
    /**
     * Convert to array for serialization
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pep_id' => $this->pepId,
            'name' => $this->name,
            'level' => $this->level->value,
            'position' => $this->position,
            'country' => $this->country,
            'organization' => $this->organization,
            'start_date' => $this->startDate?->format('Y-m-d'),
            'end_date' => $this->endDate?->format('Y-m-d'),
            'is_active' => $this->isActive(),
            'is_former' => $this->isFormer(),
            'risk_score' => $this->getRiskScore(),
            'requires_edd' => $this->requiresEdd(),
            'requires_senior_approval' => $this->requiresSeniorApproval(),
            'monitoring_frequency_days' => $this->getMonitoringFrequencyDays(),
            'tenure_days' => $this->getTenureDays(),
            'years_since_end' => $this->getYearsSinceEnd(),
            'related_persons' => $this->relatedPersons,
            'related_persons_count' => $this->getRelatedPersonsCount(),
            'additional_info' => $this->additionalInfo,
            'identified_at' => $this->identifiedAt->format('c'),
        ];
    }
}
