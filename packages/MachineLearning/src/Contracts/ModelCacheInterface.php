<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use Nexus\MachineLearning\ValueObjects\Model;

/**
 * Model cache interface for caching loaded models in memory
 * 
 * Caching strategies:
 * - In-memory array (simple, process-scoped)
 * - PSR-16 cache (Redis, Memcached, APCu)
 * - File-based cache (for large models)
 * 
 * Cache keys are generated from:
 * - Model name
 * - Model version
 * - Model format
 * 
 * Example usage:
 * ```php
 * if ($cache->has('fraud_detection', '1.0.0')) {
 *     $model = $cache->get('fraud_detection', '1.0.0');
 * } else {
 *     $model = $loader->load('fraud_detection', '1.0.0');
 *     $cache->set('fraud_detection', '1.0.0', $model, ttl: 3600);
 * }
 * ```
 */
interface ModelCacheInterface
{
    /**
     * Check if a model is cached
     * 
     * @param string $modelName The model identifier
     * @param string $version The model version
     * 
     * @return bool True if the model is cached
     */
    public function has(string $modelName, string $version): bool;

    /**
     * Get a cached model
     * 
     * @param string $modelName The model identifier
     * @param string $version The model version
     * 
     * @return Model|null The cached model, or null if not found
     */
    public function get(string $modelName, string $version): ?Model;

    /**
     * Cache a model
     * 
     * @param string $modelName The model identifier
     * @param string $version The model version
     * @param Model $model The model to cache
     * @param int|null $ttl Time-to-live in seconds (null for no expiration)
     * 
     * @return void
     */
    public function set(string $modelName, string $version, Model $model, ?int $ttl = null): void;

    /**
     * Remove a model from cache
     * 
     * @param string $modelName The model identifier
     * @param string $version The model version
     * 
     * @return void
     */
    public function delete(string $modelName, string $version): void;

    /**
     * Clear all cached models
     * 
     * @return void
     */
    public function clear(): void;

    /**
     * Get cache statistics
     * 
     * @return array{hits: int, misses: int, size: int, count: int}
     */
    public function getStats(): array;
}
