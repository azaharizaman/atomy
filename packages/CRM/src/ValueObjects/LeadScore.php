<?php

declare(strict_types=1);

namespace Nexus\CRM\ValueObjects;

/**
 * Lead Score Value Object
 * 
 * Represents a calculated lead score with contributing factors.
 * Immutable value object for lead quality measurement.
 * 
 * @package Nexus\CRM\ValueObjects
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class LeadScore
{
    /**
     * @param int $value The calculated score (0-100)
     * @param array<string, int> $factors Contributing factors and their scores
     * @param \DateTimeImmutable $calculatedAt When the score was calculated
     */
    public function __construct(
        public int $value,
        public array $factors = [],
        public \DateTimeImmutable $calculatedAt = new \DateTimeImmutable()
    ) {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Lead score must be between 0 and 100');
        }
    }

    /**
     * Create a lead score from factors
     * 
     * @param array<string, int> $factors
     */
    public static function fromFactors(array $factors): self
    {
        $total = array_sum($factors);
        $cappedTotal = min(100, max(0, $total));
        return new self($cappedTotal, $factors);
    }

    /**
     * Get the score value
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Get contributing factors
     * 
     * @return array<string, int>
     */
    public function getFactors(): array
    {
        return $this->factors;
    }

    /**
     * Get when the score was calculated
     */
    public function getCalculatedAt(): \DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    /**
     * Check if this is a high-quality lead (score >= 70)
     */
    public function isHighQuality(): bool
    {
        return $this->value >= 70;
    }

    /**
     * Check if this is a medium-quality lead (score 40-69)
     */
    public function isMediumQuality(): bool
    {
        return $this->value >= 40 && $this->value < 70;
    }

    /**
     * Check if this is a low-quality lead (score < 40)
     */
    public function isLowQuality(): bool
    {
        return $this->value < 40;
    }

    /**
     * Get quality tier label
     */
    public function getQualityTier(): string
    {
        return match (true) {
            $this->isHighQuality() => 'High',
            $this->isMediumQuality() => 'Medium',
            default => 'Low',
        };
    }

    /**
     * Get a specific factor's contribution
     */
    public function getFactor(string $name): ?int
    {
        return $this->factors[$name] ?? null;
    }

    /**
     * Check if score needs recalculation (older than specified hours)
     */
    public function needsRecalculation(int $maxAgeHours = 24): bool
    {
        $threshold = (new \DateTimeImmutable())->modify("-{$maxAgeHours} hours");
        return $this->calculatedAt < $threshold;
    }

    /**
     * Compare with another score
     */
    public function isHigherThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    /**
     * Compare with another score
     */
    public function isLowerThan(self $other): bool
    {
        return $this->value < $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}