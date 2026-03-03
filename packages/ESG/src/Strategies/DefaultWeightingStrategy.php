<?php

declare(strict_types=1);

namespace Nexus\ESG\Strategies;

/**
 * Default weighting strategy for ESG scoring.
 * 
 * Provides equal distribution (0.33 each) if no specific weights are provided.
 */
final readonly class DefaultWeightingStrategy
{
    public const DEFAULT_WEIGHTS = [
        'environmental' => 0.3333,
        'social' => 0.3333,
        'governance' => 0.3334,
    ];

    public function getWeights(): array
    {
        return self::DEFAULT_WEIGHTS;
    }
}
