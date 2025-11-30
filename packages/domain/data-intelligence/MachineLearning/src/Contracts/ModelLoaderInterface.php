<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use Nexus\MachineLearning\ValueObjects\Model;

/**
 * Model loader interface for loading ML models from various sources
 * 
 * Implementations can load models from:
 * - Local filesystem (pickled models, ONNX files, SavedModel directories)
 * - MLflow model registry
 * - Cloud storage (S3, Azure Blob, GCS)
 * - HTTP endpoints (model serving APIs)
 * 
 * Loaders are responsible for:
 * - Model discovery and validation
 * - Dependency resolution (Python packages, C libraries)
 * - Model metadata extraction
 * - Version management
 * 
 * Example usage:
 * ```php
 * $model = $loader->load('fraud_detection', '1.0.0');
 * $inference = $engine->predict($model, $features);
 * ```
 */
interface ModelLoaderInterface
{
    /**
     * Load a model by name and version
     * 
     * @param string $modelName The model identifier
     * @param string|null $version The model version (null for latest)
     * 
     * @return Model Loaded model with metadata
     * 
     * @throws \Nexus\MachineLearning\Exceptions\ModelNotFoundException If model doesn't exist
     * @throws \Nexus\MachineLearning\Exceptions\ModelLoadException If loading fails
     */
    public function load(string $modelName, ?string $version = null): Model;

    /**
     * Check if a model exists
     * 
     * @param string $modelName The model identifier
     * @param string|null $version The model version (null for latest)
     * 
     * @return bool True if the model exists
     */
    public function exists(string $modelName, ?string $version = null): bool;

    /**
     * List all available versions of a model
     * 
     * @param string $modelName The model identifier
     * 
     * @return string[] Array of version strings (sorted newest first)
     */
    public function listVersions(string $modelName): array;

    /**
     * Get the latest version of a model
     * 
     * @param string $modelName The model identifier
     * 
     * @return string|null The latest version, or null if no versions exist
     */
    public function getLatestVersion(string $modelName): ?string;

    /**
     * Unload a model from memory (if cached)
     * 
     * @param string $modelName The model identifier
     * @param string|null $version The model version (null for all versions)
     * 
     * @return void
     */
    public function unload(string $modelName, ?string $version = null): void;
}
