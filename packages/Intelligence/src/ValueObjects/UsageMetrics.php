<?php

declare(strict_types=1);

namespace Nexus\Intelligence\ValueObjects;

use DateTimeImmutable;

/**
 * Usage metrics value object
 */
final readonly class UsageMetrics
{
    /**
     * @param int $tokensUsed Number of tokens consumed
     * @param int $apiCalls Number of API calls made
     * @param float $apiCost Total cost in USD
     * @param DateTimeImmutable $measuredAt Measurement timestamp
     */
    public function __construct(
        private int $tokensUsed,
        private int $apiCalls,
        private float $apiCost,
        private DateTimeImmutable $measuredAt
    ) {}

    public function getTokensUsed(): int
    {
        return $this->tokensUsed;
    }

    public function getApiCalls(): int
    {
        return $this->apiCalls;
    }

    public function getApiCost(): float
    {
        return $this->apiCost;
    }

    public function getMeasuredAt(): DateTimeImmutable
    {
        return $this->measuredAt;
    }

    /**
     * Get cost per token
     * 
     * @return float
     */
    public function getCostPerToken(): float
    {
        return $this->tokensUsed > 0 ? $this->apiCost / $this->tokensUsed : 0.0;
    }
}
