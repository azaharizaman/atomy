<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Contracts;

/**
 * Feature set interface
 * 
 * Standardized data transfer object for ML features.
 */
interface FeatureSetInterface
{
    /**
     * Get features as associative array
     * 
     * @return array<string, mixed> Feature name => value
     */
    public function toArray(): array;

    /**
     * Get schema version for compatibility checking
     * 
     * @return string Semantic version (e.g., '1.0', '1.1')
     */
    public function getSchemaVersion(): string;

    /**
     * Get metadata about the feature set
     * 
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get unique hash of features for tracking
     * 
     * @return string xxh3 hash
     */
    public function getHash(): string;
}
