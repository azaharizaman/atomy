<?php

declare(strict_types=1);

namespace Nexus\Geo\ValueObjects;

/**
 * Immutable value object representing geocoding performance metrics
 * 
 * Used for cost monitoring and cache optimization
 */
final readonly class GeoMetrics implements \JsonSerializable
{
    public function __construct(
        public int $totalRequests,
        public int $cacheHits,
        public int $cacheMisses,
        public array $providerUsage = [],
        public float $averageLatencyMs = 0.0,
        public ?\DateTimeImmutable $periodStart = null,
        public ?\DateTimeImmutable $periodEnd = null
    ) {
    }

    /**
     * Calculate cache hit rate percentage
     */
    public function getCacheHitRate(): float
    {
        if ($this->totalRequests === 0) {
            return 0.0;
        }

        return ($this->cacheHits / $this->totalRequests) * 100;
    }

    /**
     * Check if cache hit rate meets target (default 80%)
     */
    public function meetsTargetHitRate(float $targetPercentage = 80.0): bool
    {
        return $this->getCacheHitRate() >= $targetPercentage;
    }

    /**
     * Estimate cost based on provider pricing
     * Google Maps: $5 per 1000 requests
     */
    public function estimateCost(array $providerPricing = ['google' => 0.005, 'nominatim' => 0.0]): float
    {
        $totalCost = 0.0;

        foreach ($this->providerUsage as $provider => $count) {
            $pricePerRequest = $providerPricing[$provider] ?? 0.0;
            $totalCost += ($count * $pricePerRequest);
        }

        return $totalCost;
    }

    /**
     * Get most used provider
     */
    public function getMostUsedProvider(): ?string
    {
        if (empty($this->providerUsage)) {
            return null;
        }

        return array_key_first(
            array_slice(
                arsort($this->providerUsage) ? $this->providerUsage : [],
                0,
                1,
                true
            )
        );
    }

    public function toArray(): array
    {
        return [
            'total_requests' => $this->totalRequests,
            'cache_hits' => $this->cacheHits,
            'cache_misses' => $this->cacheMisses,
            'cache_hit_rate' => round($this->getCacheHitRate(), 2),
            'provider_usage' => $this->providerUsage,
            'average_latency_ms' => round($this->averageLatencyMs, 2),
            'estimated_cost_usd' => round($this->estimateCost(), 2),
            'period_start' => $this->periodStart?->format('Y-m-d H:i:s'),
            'period_end' => $this->periodEnd?->format('Y-m-d H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
