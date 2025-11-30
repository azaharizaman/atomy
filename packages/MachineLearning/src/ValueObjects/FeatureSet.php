<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

use Nexus\MachineLearning\Contracts\FeatureSetInterface;

/**
 * Feature set value object
 * 
 * Immutable container for ML features with versioning and hashing.
 */
final class FeatureSet implements FeatureSetInterface
{
    private readonly string $hash;

    /**
     * @param array<string, mixed> $features Feature name => value
     * @param string $schemaVersion Semantic version
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        private readonly array $features,
        private readonly string $schemaVersion,
        private readonly array $metadata = []
    ) {
        $this->hash = hash('xxh3', json_encode($features));
    }

    public function toArray(): array
    {
        return $this->features;
    }

    public function getSchemaVersion(): string
    {
        return $this->schemaVersion;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Get feature value by key
     * 
     * @param string $key Feature name
     * @return mixed|null
     */
    public function get(string $key): mixed
    {
        return $this->features[$key] ?? null;
    }

    /**
     * Check if feature exists
     * 
     * @param string $key Feature name
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->features);
    }

    /**
     * Get count of features
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->features);
    }
}
