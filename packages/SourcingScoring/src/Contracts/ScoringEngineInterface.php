<?php

declare(strict_types=1);

namespace Nexus\SourcingScoring\Contracts;

/**
 * Interface for scoring engines used in vendor evaluation.
 */
interface ScoringEngineInterface
{
    /**
     * Calculate a score for a given dataset.
     *
     * @param array<string, mixed> $data
     * @return float
     */
    public function calculateScore(array $data): float;
}
