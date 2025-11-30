<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Core\Decorators;

use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagEvaluatorInterface;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;

/**
 * Request-level memoization decorator for FlagEvaluatorInterface.
 *
 * Caches evaluation results in memory for the duration of a single request
 * to avoid redundant evaluations of the same flag + context combination.
 *
 * Cache key: xxh3(flagName|tenantId|stableIdentifier|checksum)
 *
 * Thread-safe: Yes (request-scoped, no shared state across processes)
 * TTL: Request lifetime (discarded after request completes)
 */
final class InMemoryMemoizedEvaluator implements FlagEvaluatorInterface
{
    /**
     * Cache storage: [cacheKey => bool]
     *
     * @var array<string, bool>
     */
    private array $cache = [];

    public function __construct(
        private readonly FlagEvaluatorInterface $inner
    ) {
    }

    public function evaluate(FlagDefinitionInterface $flag, EvaluationContext $context): bool
    {
        $cacheKey = $this->buildCacheKey($flag, $context);

        // Return cached result if available
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        // Evaluate and cache result
        $result = $this->inner->evaluate($flag, $context);
        $this->cache[$cacheKey] = $result;

        return $result;
    }

    public function evaluateMany(array $flags, EvaluationContext $context): array
    {
        // Separate cached and uncached flags
        $cached = [];
        $uncached = [];

        foreach ($flags as $name => $flag) {
            $cacheKey = $this->buildCacheKey($flag, $context);

            if (array_key_exists($cacheKey, $this->cache)) {
                $cached[$name] = $this->cache[$cacheKey];
            } else {
                $uncached[$name] = $flag;
            }
        }

        // Evaluate uncached flags in bulk
        $results = [];
        if (!empty($uncached)) {
            $results = $this->inner->evaluateMany($uncached, $context);

            // Cache the new results
            foreach ($results as $name => $result) {
                $cacheKey = $this->buildCacheKey($uncached[$name], $context);
                $this->cache[$cacheKey] = $result;
            }
        }

        // Merge cached and newly evaluated results
        return array_merge($cached, $results);
    }

    /**
     * Build cache key using xxHash3 for speed.
     *
     * Key components:
     * - Flag name (identity)
     * - Tenant ID (scoping)
     * - Stable identifier (user/session/tenant for bucketing)
     * - Checksum (cache invalidation when flag changes)
     *
     * @param FlagDefinitionInterface $flag
     * @param EvaluationContext $context
     * @return string
     */
    private function buildCacheKey(FlagDefinitionInterface $flag, EvaluationContext $context): string
    {
        $parts = [
            $flag->getName(),
            $context->tenantId ?? 'global',
            $context->getStableIdentifier() ?? 'anonymous',
            $flag->getChecksum(),
        ];

        $raw = implode('|', $parts);

        return hash('xxh3', $raw);
    }

    /**
     * Clear the in-memory cache.
     *
     * Useful for testing or forcing re-evaluation.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Get cache statistics for debugging.
     *
     * @return array{size: int, keys: array<string>}
     */
    public function getCacheStats(): array
    {
        return [
            'size' => count($this->cache),
            'keys' => array_keys($this->cache),
        ];
    }
}
