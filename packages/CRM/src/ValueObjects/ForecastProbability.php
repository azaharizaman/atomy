<?php

declare(strict_types=1);

namespace Nexus\CRM\ValueObjects;

/**
 * Forecast Probability Value Object
 * 
 * Represents the probability of winning an opportunity.
 * Used for sales forecasting and weighted pipeline calculations.
 * 
 * @package Nexus\CRM\ValueObjects
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class ForecastProbability
{
    /**
     * @param int $percentage Probability percentage (0-100)
     * @param string|null $reason Reason for the probability value
     */
    public function __construct(
        public int $percentage,
        public ?string $reason = null
    ) {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Probability must be between 0 and 100');
        }
    }

    /**
     * Create from decimal (0.0 - 1.0)
     */
    public static function fromDecimal(float $decimal, ?string $reason = null): self
    {
        if ($decimal < 0.0 || $decimal > 1.0) {
            throw new \InvalidArgumentException('Decimal probability must be between 0.0 and 1.0');
        }

        return new self((int) round($decimal * 100), $reason);
    }

    /**
     * Create a guaranteed win (100%)
     */
    public static function guaranteed(?string $reason = null): self
    {
        return new self(100, $reason ?? 'Deal closed won');
    }

    /**
     * Create a guaranteed loss (0%)
     */
    public static function lost(?string $reason = null): self
    {
        return new self(0, $reason ?? 'Deal closed lost');
    }

    /**
     * Get probability percentage
     */
    public function getPercentage(): int
    {
        return $this->percentage;
    }

    /**
     * Get probability as decimal (0.0 - 1.0)
     */
    public function getDecimal(): float
    {
        return $this->percentage / 100;
    }

    /**
     * Get reason for probability
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Calculate weighted value
     */
    public function calculateWeightedValue(int $value): int
    {
        return (int) round($value * $this->getDecimal());
    }

    /**
     * Check if this is a guaranteed win
     */
    public function isGuaranteed(): bool
    {
        return $this->percentage === 100;
    }

    /**
     * Check if this is a guaranteed loss
     */
    public function isLost(): bool
    {
        return $this->percentage === 0;
    }

    /**
     * Check if probability is high (>= 70%)
     */
    public function isHigh(): bool
    {
        return $this->percentage >= 70;
    }

    /**
     * Check if probability is medium (40-69%)
     */
    public function isMedium(): bool
    {
        return $this->percentage >= 40 && $this->percentage < 70;
    }

    /**
     * Check if probability is low (< 40%)
     */
    public function isLow(): bool
    {
        return $this->percentage < 40;
    }

    /**
     * Get confidence category
     */
    public function getCategory(): string
    {
        return match (true) {
            $this->isGuaranteed() => 'Won',
            $this->isLost() => 'Lost',
            $this->isHigh() => 'High Confidence',
            $this->isMedium() => 'Medium Confidence',
            default => 'Low Confidence',
        };
    }

    /**
     * Compare with another probability
     */
    public function isHigherThan(self $other): bool
    {
        return $this->percentage > $other->percentage;
    }

    /**
     * Compare with another probability
     */
    public function isLowerThan(self $other): bool
    {
        return $this->percentage < $other->percentage;
    }

    /**
     * Create a new probability with updated value
     */
    public function withPercentage(int $percentage, ?string $reason = null): self
    {
        return new self($percentage, $reason ?? $this->reason);
    }

    public function __toString(): string
    {
        return $this->percentage . '%';
    }
}