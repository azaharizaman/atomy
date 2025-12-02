<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\ValueObjects;

use Nexus\FinancialRatios\Enums\RatioCategory;

/**
 * Represents a financial health indicator based on multiple ratios.
 */
final readonly class HealthIndicator
{
    /**
     * @param array<string, RatioResult> $ratioResults
     * @param array<string, string> $warnings
     * @param array<string, string> $strengths
     */
    public function __construct(
        public RatioCategory $category,
        public float $overallScore,
        public string $healthStatus,
        public array $ratioResults,
        public array $warnings = [],
        public array $strengths = [],
        public ?string $recommendation = null,
    ) {}

    /**
     * Check if health status is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->overallScore >= 70.0;
    }

    /**
     * Check if health status is at risk.
     */
    public function isAtRisk(): bool
    {
        return $this->overallScore < 50.0;
    }

    /**
     * Get the number of warnings.
     */
    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    /**
     * Get the number of strengths.
     */
    public function getStrengthCount(): int
    {
        return count($this->strengths);
    }

    /**
     * Get health status as a color code.
     */
    public function getStatusColor(): string
    {
        if ($this->overallScore >= 80.0) {
            return 'green';
        }

        if ($this->overallScore >= 60.0) {
            return 'yellow';
        }

        if ($this->overallScore >= 40.0) {
            return 'orange';
        }

        return 'red';
    }
}
