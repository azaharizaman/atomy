<?php

declare(strict_types=1);

namespace Nexus\ESG\Contracts;

use Nexus\ESG\ValueObjects\Dimension;
use Nexus\ESG\ValueObjects\SustainabilityScore;

/**
 * Interface for the ESG scoring engine.
 */
interface ScoringEngineInterface
{
    /**
     * Calculate a composite ESG score based on dimension scores and weights.
     * 
     * @param array<string, float> $dimensionScores Map of dimension name to 0-100 score
     * @param array<string, float>|null $weights Map of dimension name to 0.0-1.0 weight
     * 
     * @return SustainabilityScore
     */
    public function calculateCompositeScore(array $dimensionScores, ?array $weights = null): SustainabilityScore;

    /**
     * Get the effective weights used for calculation.
     * 
     * @return array<string, float>
     */
    public function getEffectiveWeights(?array $weights = null): array;
}
