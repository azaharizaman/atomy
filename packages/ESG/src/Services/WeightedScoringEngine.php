<?php

declare(strict_types=1);

namespace Nexus\ESG\Services;

use Nexus\ESG\Contracts\ScoringEngineInterface;
use Nexus\ESG\ValueObjects\SustainabilityScore;
use Nexus\ESG\Strategies\DefaultWeightingStrategy;
use Psr\Log\LoggerInterface;

/**
 * Weighted scoring engine for composite ESG ratings.
 */
final readonly class WeightedScoringEngine implements ScoringEngineInterface
{
    public function __construct(
        private DefaultWeightingStrategy $defaultStrategy,
        private ?LoggerInterface $logger = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function calculateCompositeScore(array $dimensionScores, ?array $weights = null): SustainabilityScore
    {
        $effectiveWeights = $this->getEffectiveWeights($weights);
        $totalValue = 0.0;

        foreach ($effectiveWeights as $dimension => $weight) {
            $score = $dimensionScores[$dimension] ?? 0.0;
            $totalValue += ($score * $weight);
        }

        $this->logger?->info('Calculated composite ESG score', [
            'scores' => $dimensionScores,
            'weights' => $effectiveWeights,
            'result' => $totalValue,
        ]);

        return new SustainabilityScore($totalValue);
    }

    /**
     * @inheritDoc
     */
    public function getEffectiveWeights(?array $weights = null): array
    {
        if ($weights === null || empty($weights)) {
            return $this->defaultStrategy->getWeights();
        }

        // Validate total weights sum to approx 1.0
        $sum = array_sum($weights);
        if (abs($sum - 1.0) > 0.001) {
            $this->logger?->warning('ESG weights do not sum to 1.0', ['sum' => $sum]);
        }

        return $weights;
    }
}
