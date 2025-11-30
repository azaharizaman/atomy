<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Services;

use Nexus\MachineLearning\Contracts\HttpClientInterface;
use Nexus\MachineLearning\Contracts\MLflowClientInterface;
use Nexus\MachineLearning\Exceptions\ModelLoadException;
use Nexus\MachineLearning\Exceptions\ModelNotFoundException;
use Nexus\MachineLearning\Exceptions\ProviderUnavailableException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * MLflow client implementation
 * 
 * Communicates with MLflow REST API for model registry and experiment tracking.
 * 
 * Configuration:
 * - tracking_uri: MLflow server URL (e.g., http://mlflow:5000)
 * - timeout: Request timeout in seconds (default: 30)
 * - retry_attempts: Number of retries on failure (default: 3)
 */
final readonly class MLflowClient implements MLflowClientInterface
{
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_RETRY_ATTEMPTS = 3;

    /**
     * @param HttpClientInterface $httpClient HTTP client for API requests
     * @param string $trackingUri MLflow server URL
     * @param int $timeout Request timeout in seconds
     * @param int $retryAttempts Number of retry attempts
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $trackingUri,
        private int $timeout = self::DEFAULT_TIMEOUT,
        private int $retryAttempts = self::DEFAULT_RETRY_ATTEMPTS,
        private ?LoggerInterface $logger = null,
    ) {
        if (empty($trackingUri)) {
            throw new \InvalidArgumentException('MLflow tracking URI cannot be empty');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getModel(string $modelName, ?string $version = null): array
    {
        try {
            // If version is a stage name (production, staging, etc.), get latest version in that stage
            if ($version !== null && in_array(strtolower($version), ['production', 'staging', 'archived', 'none'], true)) {
                return $this->getModelByStage($modelName, $version);
            }

            // Get specific version or latest
            $endpoint = $version 
                ? "/api/2.0/mlflow/model-versions/get?name={$modelName}&version={$version}"
                : "/api/2.0/mlflow/registered-models/get-latest-versions?name={$modelName}";

            $response = $this->requestWithRetry('GET', $endpoint);
            
            if ($version === null) {
                // Get latest version from array
                $versions = $response['model_versions'] ?? [];
                if (empty($versions)) {
                    throw ModelNotFoundException::forModel($modelName);
                }
                return $versions[0];
            }

            return $response['model_version'] ?? [];

        } catch (ClientExceptionInterface $e) {
            $this->logger?->error('Failed to get model from MLflow', [
                'model' => $modelName,
                'version' => $version,
                'error' => $e->getMessage(),
            ]);

            throw ProviderUnavailableException::forProvider('mlflow', $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function listModelVersions(string $modelName): array
    {
        try {
            $endpoint = "/api/2.0/mlflow/model-versions/search";
            
            $response = $this->requestWithRetry('GET', $endpoint, [
                'query' => [
                    'filter' => "name='{$modelName}'",
                    'max_results' => 100,
                ],
            ]);

            return $response['model_versions'] ?? [];

        } catch (ClientExceptionInterface $e) {
            $this->logger?->error('Failed to list model versions from MLflow', [
                'model' => $modelName,
                'error' => $e->getMessage(),
            ]);

            throw ProviderUnavailableException::forProvider('mlflow', $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function downloadModel(string $modelName, string $version, string $destinationPath): string
    {
        try {
            // Get model metadata first
            $modelMeta = $this->getModel($modelName, $version);
            $artifactUri = $modelMeta['source'] ?? null;

            if (!$artifactUri) {
                throw ModelLoadException::forModel($modelName, 'No artifact URI found in model metadata');
            }

            // Download artifact
            // Note: For production, use MLflow's artifact download API or direct storage access
            $artifactPath = str_replace('mlflow-artifacts:', $this->trackingUri . '/mlflow-artifacts', $artifactUri);
            
            $this->logger?->info('Downloading model from MLflow', [
                'model' => $modelName,
                'version' => $version,
                'source' => $artifactUri,
                'destination' => $destinationPath,
            ]);

            // Create destination directory
            if (!is_dir(dirname($destinationPath))) {
                mkdir(dirname($destinationPath), 0755, true);
            }

            // For file:// URIs, copy directly
            if (str_starts_with($artifactUri, 'file://')) {
                $sourcePath = str_replace('file://', '', $artifactUri);
                if (is_dir($sourcePath)) {
                    $this->copyDirectory($sourcePath, $destinationPath);
                } else {
                    copy($sourcePath, $destinationPath);
                }
                return $destinationPath;
            }

            // For HTTP URIs, download via HTTP
            $response = $this->httpClient->get($artifactPath, [
                'timeout' => $this->timeout * 3, // Longer timeout for downloads
            ]);

            file_put_contents($destinationPath, $response->getBody()->getContents());

            return $destinationPath;

        } catch (ClientExceptionInterface $e) {
            throw ModelLoadException::forModel($modelName, "Download failed: {$e->getMessage()}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createRun(string $experimentName, array $tags = []): string
    {
        try {
            // Get or create experiment
            $experiment = $this->getOrCreateExperiment($experimentName);
            
            $response = $this->requestWithRetry('POST', '/api/2.0/mlflow/runs/create', [
                'json' => [
                    'experiment_id' => $experiment['experiment_id'],
                    'tags' => $this->formatTags($tags),
                ],
            ]);

            return $response['run']['info']['run_id'] ?? '';

        } catch (ClientExceptionInterface $e) {
            $this->logger?->error('Failed to create MLflow run', [
                'experiment' => $experimentName,
                'error' => $e->getMessage(),
            ]);

            throw ProviderUnavailableException::forProvider('mlflow', $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function logMetric(string $runId, string $key, float $value, ?int $step = null, ?int $timestamp = null): void
    {
        try {
            $this->requestWithRetry('POST', '/api/2.0/mlflow/runs/log-metric', [
                'json' => [
                    'run_id' => $runId,
                    'key' => $key,
                    'value' => $value,
                    'timestamp' => $timestamp ?? time() * 1000,
                    'step' => $step ?? 0,
                ],
            ]);
        } catch (ClientExceptionInterface $e) {
            $this->logger?->warning('Failed to log metric to MLflow', [
                'run_id' => $runId,
                'metric' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function logParameter(string $runId, string $key, string $value): void
    {
        try {
            $this->requestWithRetry('POST', '/api/2.0/mlflow/runs/log-parameter', [
                'json' => [
                    'run_id' => $runId,
                    'key' => $key,
                    'value' => $value,
                ],
            ]);
        } catch (ClientExceptionInterface $e) {
            $this->logger?->warning('Failed to log parameter to MLflow', [
                'run_id' => $runId,
                'parameter' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function endRun(string $runId, string $status = 'FINISHED'): void
    {
        try {
            $this->requestWithRetry('POST', '/api/2.0/mlflow/runs/update', [
                'json' => [
                    'run_id' => $runId,
                    'status' => $status,
                    'end_time' => time() * 1000,
                ],
            ]);
        } catch (ClientExceptionInterface $e) {
            $this->logger?->warning('Failed to end MLflow run', [
                'run_id' => $runId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        try {
            $this->httpClient->get($this->trackingUri . '/health', [
                'timeout' => 5,
            ]);
            return true;
        } catch (ClientExceptionInterface $e) {
            $this->logger?->debug('MLflow health check failed', [
                'error' => $e->getMessage(),
                'type' => $e::class,
            ]);
            return false;
        } catch (\Throwable $e) {
            $this->logger?->debug('MLflow health check failed with unexpected error', [
                'error' => $e->getMessage(),
                'type' => $e::class,
            ]);
            return false;
        }
    }

    /**
     * Get model by stage
     */
    private function getModelByStage(string $modelName, string $stage): array
    {
        $endpoint = "/api/2.0/mlflow/registered-models/get-latest-versions";
        
        $response = $this->requestWithRetry('GET', $endpoint, [
            'query' => [
                'name' => $modelName,
                'stages' => [strtoupper($stage)],
            ],
        ]);

        $versions = $response['model_versions'] ?? [];
        if (empty($versions)) {
            throw ModelNotFoundException::forModel($modelName, $stage);
        }

        return $versions[0];
    }

    /**
     * Get or create experiment
     */
    private function getOrCreateExperiment(string $name): array
    {
        try {
            $response = $this->requestWithRetry('GET', '/api/2.0/mlflow/experiments/get-by-name', [
                'query' => ['experiment_name' => $name],
            ]);

            return $response['experiment'] ?? [];
        } catch (ClientExceptionInterface) {
            // Create if not exists
            $response = $this->requestWithRetry('POST', '/api/2.0/mlflow/experiments/create', [
                'json' => ['name' => $name],
            ]);

            return ['experiment_id' => $response['experiment_id']];
        }
    }

    /**
     * Format tags for MLflow API
     */
    private function formatTags(array $tags): array
    {
        $formatted = [];
        foreach ($tags as $key => $value) {
            $formatted[] = ['key' => $key, 'value' => (string) $value];
        }
        return $formatted;
    }

    /**
     * Make HTTP request with retry logic
     */
    private function requestWithRetry(string $method, string $endpoint, array $options = []): array
    {
        $url = rtrim($this->trackingUri, '/') . $endpoint;
        $options['timeout'] = $options['timeout'] ?? $this->timeout;

        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            try {
                $response = $this->httpClient->request($method, $url, $options);
                return json_decode($response->getBody()->getContents(), true) ?? [];
            } catch (ClientExceptionInterface $e) {
                $lastException = $e;
                
                if ($attempt < $this->retryAttempts) {
                    $backoff = $attempt * 100000; // 100ms, 200ms, 300ms
                    usleep($backoff);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $source . '/' . $file;
            $dstPath = $destination . '/' . $file;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
        closedir($dir);
    }
}
