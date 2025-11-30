<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

/**
 * MLflow client interface for model registry and experiment tracking
 * 
 * Integrates with MLflow server for:
 * - Model registry (list, get, download models)
 * - Experiment tracking (log metrics, parameters, artifacts)
 * - Model versioning and stage transitions
 * 
 * MLflow server can be:
 * - Self-hosted (local or cloud)
 * - Databricks Community Edition
 * - Managed MLflow (AWS, Azure, GCP)
 * 
 * Example usage:
 * ```php
 * $client = new MLflowClient('http://mlflow:5000');
 * $model = $client->getModel('fraud_detection', 'production');
 * ```
 */
interface MLflowClientInterface
{
    /**
     * Get model from registry
     * 
     * @param string $modelName Model name in registry
     * @param string|null $version Model version or stage (null for latest)
     * 
     * @return array<string, mixed> Model metadata
     * 
     * @throws \Nexus\MachineLearning\Exceptions\ModelNotFoundException
     * @throws \Nexus\MachineLearning\Exceptions\ProviderUnavailableException
     */
    public function getModel(string $modelName, ?string $version = null): array;

    /**
     * List all versions of a model
     * 
     * @param string $modelName Model name in registry
     * 
     * @return array<array<string, mixed>> Array of model version metadata
     */
    public function listModelVersions(string $modelName): array;

    /**
     * Download model artifact to local path
     * 
     * @param string $modelName Model name
     * @param string $version Model version
     * @param string $destinationPath Local path to save model
     * 
     * @return string Path to downloaded model
     * 
     * @throws \Nexus\MachineLearning\Exceptions\ModelLoadException
     */
    public function downloadModel(string $modelName, string $version, string $destinationPath): string;

    /**
     * Create experiment run
     * 
     * @param string $experimentName Experiment name
     * @param array<string, mixed> $tags Run tags
     * 
     * @return string Run ID
     */
    public function createRun(string $experimentName, array $tags = []): string;

    /**
     * Log metric for a run
     * 
     * @param string $runId Run identifier
     * @param string $key Metric name
     * @param float $value Metric value
     * @param int|null $step Step number
     * @param int|null $timestamp Unix timestamp
     * 
     * @return void
     */
    public function logMetric(string $runId, string $key, float $value, ?int $step = null, ?int $timestamp = null): void;

    /**
     * Log parameter for a run
     * 
     * @param string $runId Run identifier
     * @param string $key Parameter name
     * @param string $value Parameter value
     * 
     * @return void
     */
    public function logParameter(string $runId, string $key, string $value): void;

    /**
     * End experiment run
     * 
     * @param string $runId Run identifier
     * @param string $status Run status ('FINISHED', 'FAILED', 'KILLED')
     * 
     * @return void
     */
    public function endRun(string $runId, string $status = 'FINISHED'): void;

    /**
     * Check if MLflow server is reachable
     * 
     * @return bool True if server is available
     */
    public function isAvailable(): bool;
}
