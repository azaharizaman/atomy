<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Services;

use Nexus\MachineLearning\Contracts\MLflowClientInterface;
use Nexus\MachineLearning\Contracts\ModelCacheInterface;
use Nexus\MachineLearning\Contracts\ModelLoaderInterface;
use Nexus\MachineLearning\Exceptions\ModelLoadException;
use Nexus\MachineLearning\Exceptions\ModelNotFoundException;
use Nexus\MachineLearning\ValueObjects\Model;
use Psr\Log\LoggerInterface;

/**
 * MLflow model loader
 * 
 * Loads models from MLflow model registry with caching support.
 * 
 * Features:
 * - Download models from MLflow registry
 * - Cache downloaded models locally
 * - Support for model stages (production, staging, etc.)
 * - Automatic version resolution
 * 
 * Example usage:
 * ```php
 * $loader = new MLflowModelLoader($mlflowClient, $cache, '/var/models');
 * $model = $loader->load('fraud_detection', 'production');
 * ```
 */
final readonly class MLflowModelLoader implements ModelLoaderInterface
{
    /**
     * @param MLflowClientInterface $mlflowClient MLflow client for registry access
     * @param ModelCacheInterface|null $cache Optional model cache
     * @param string $localStoragePath Local directory for downloaded models
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private MLflowClientInterface $mlflowClient,
        private ?ModelCacheInterface $cache,
        private string $localStoragePath,
        private ?LoggerInterface $logger = null,
    ) {
        if (!is_dir($localStoragePath)) {
            mkdir($localStoragePath, 0755, true);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(string $modelName, ?string $version = null): Model
    {
        // Try cache first
        if ($version !== null && $this->cache?->has($modelName, $version)) {
            $cached = $this->cache->get($modelName, $version);
            if ($cached !== null) {
                $this->logger?->debug('Loaded model from cache', [
                    'model' => $modelName,
                    'version' => $version,
                ]);
                return $cached;
            }
        }

        // Get model metadata from MLflow
        $modelMeta = $this->mlflowClient->getModel($modelName, $version);
        
        if (empty($modelMeta)) {
            throw ModelNotFoundException::forModel($modelName, $version);
        }

        $actualVersion = $modelMeta['version'] ?? $version ?? 'latest';
        $format = $this->detectModelFormat($modelMeta);

        // Download model if not already local
        $localPath = $this->getLocalPath($modelName, $actualVersion);
        
        if (!file_exists($localPath)) {
            $this->logger?->info('Downloading model from MLflow', [
                'model' => $modelName,
                'version' => $actualVersion,
            ]);

            $localPath = $this->mlflowClient->downloadModel($modelName, $actualVersion, $localPath);
        }

        // Create Model object
        $model = new Model(
            name: $modelName,
            version: $actualVersion,
            format: $format,
            artifactPath: $localPath,
            metadata: $this->extractMetadata($modelMeta),
            createdAt: isset($modelMeta['creation_timestamp']) 
                ? new \DateTimeImmutable('@' . intval($modelMeta['creation_timestamp'] / 1000))
                : null,
            loadedAt: new \DateTimeImmutable(),
        );

        // Cache the model
        if ($this->cache !== null) {
            $this->cache->set($modelName, $actualVersion, $model, ttl: 3600);
        }

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $modelName, ?string $version = null): bool
    {
        try {
            $this->mlflowClient->getModel($modelName, $version);
            return true;
        } catch (ModelNotFoundException) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function listVersions(string $modelName): array
    {
        $versions = $this->mlflowClient->listModelVersions($modelName);
        
        return array_map(
            fn($v) => $v['version'] ?? 'unknown',
            $versions
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getLatestVersion(string $modelName): ?string
    {
        $versions = $this->listVersions($modelName);
        
        if (empty($versions)) {
            return null;
        }

        // Sort versions in descending order
        usort($versions, fn($a, $b) => version_compare($b, $a));
        
        return $versions[0];
    }

    /**
     * {@inheritDoc}
     */
    public function unload(string $modelName, ?string $version = null): void
    {
        if ($this->cache === null) {
            return;
        }

        if ($version !== null) {
            $this->cache->delete($modelName, $version);
        } else {
            // Unload all versions
            $versions = $this->listVersions($modelName);
            foreach ($versions as $v) {
                $this->cache->delete($modelName, $v);
            }
        }
    }

    /**
     * Get local filesystem path for model storage
     * 
     * @param string $modelName
     * @param string $version
     * 
     * @return string Local path
     */
    private function getLocalPath(string $modelName, string $version): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $modelName);
        $safeVersion = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $version);
        
        return $this->localStoragePath . '/' . $safeName . '/' . $safeVersion;
    }

    /**
     * Detect model format from metadata
     * 
     * @param array<string, mixed> $modelMeta
     * 
     * @return string Model format
     */
    private function detectModelFormat(array $modelMeta): string
    {
        // Check for flavors in MLflow metadata
        $flavors = $modelMeta['flavors'] ?? [];
        
        if (isset($flavors['pytorch'])) {
            return 'pytorch';
        }
        
        if (isset($flavors['onnx'])) {
            return 'onnx';
        }
        
        if (isset($flavors['tensorflow'])) {
            return 'tensorflow';
        }
        
        if (isset($flavors['sklearn'])) {
            return 'sklearn';
        }

        // Check tags
        $tags = $modelMeta['tags'] ?? [];
        foreach ($tags as $tag) {
            if (isset($tag['key']) && $tag['key'] === 'model_format') {
                return $tag['value'];
            }
        }

        // Default to onnx (most portable)
        return 'onnx';
    }

    /**
     * Extract metadata from MLflow model
     * 
     * @param array<string, mixed> $modelMeta
     * 
     * @return array<string, mixed>
     */
    private function extractMetadata(array $modelMeta): array
    {
        return [
            'mlflow_version' => $modelMeta['version'] ?? null,
            'run_id' => $modelMeta['run_id'] ?? null,
            'source' => $modelMeta['source'] ?? null,
            'status' => $modelMeta['status'] ?? null,
            'current_stage' => $modelMeta['current_stage'] ?? null,
            'description' => $modelMeta['description'] ?? null,
            'tags' => $modelMeta['tags'] ?? [],
            'flavors' => $modelMeta['flavors'] ?? [],
        ];
    }
}
