<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Core\Engines;

use Nexus\MachineLearning\Contracts\HttpClientInterface;
use Nexus\MachineLearning\Contracts\InferenceEngineInterface;
use Nexus\MachineLearning\Exceptions\InferenceException;
use Nexus\MachineLearning\Exceptions\InferenceTimeoutException;
use Nexus\MachineLearning\ValueObjects\Model;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Remote API inference engine for calling external model serving endpoints
 * 
 * Supports common model serving frameworks:
 * - MLflow Model Serving
 * - TensorFlow Serving
 * - TorchServe
 * - Seldon Core
 * - KServe (formerly KFServing)
 * - Custom REST APIs
 * 
 * Requirements:
 * - HttpClientInterface implementation
 * - Model endpoint URL in metadata
 * 
 * Performance:
 * - Network latency dependent
 * - Scalable (inference runs on remote servers)
 * - Ideal for production deployments with auto-scaling
 */
final readonly class RemoteAPIInferenceEngine implements InferenceEngineInterface
{
    private const DEFAULT_TIMEOUT = 30;

    /**
     * @param HttpClientInterface $httpClient HTTP client for API calls
     * @param int $timeout Maximum execution time in seconds
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private int $timeout = self::DEFAULT_TIMEOUT,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function predict(Model $model, array $input): array
    {
        $endpoint = $this->getEndpoint($model);
        
        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => ['instances' => [$input]],
                'timeout' => $this->timeout,
                'headers' => $this->getHeaders($model),
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw InferenceException::forModel($model->getIdentifier(), 'Invalid JSON response from API');
            }

            // Handle different API response formats
            return $this->extractPrediction($body, 0);
            
        } catch (ClientExceptionInterface $e) {
            $this->logger?->error('Remote API inference failed', [
                'model' => $model->getIdentifier(),
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            
            throw InferenceException::forModel($model->getIdentifier(), "API request failed: {$e->getMessage()}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function batchPredict(Model $model, array $inputs): array
    {
        $endpoint = $this->getEndpoint($model);
        
        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => ['instances' => $inputs],
                'timeout' => $this->timeout,
                'headers' => $this->getHeaders($model),
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw InferenceException::forModel($model->getIdentifier(), 'Invalid JSON response from API');
            }

            // Extract all predictions
            $results = [];
            $predictions = $body['predictions'] ?? $body['outputs'] ?? $body;
            
            foreach ($predictions as $idx => $pred) {
                $results[] = $this->extractPrediction(['predictions' => [$pred]], 0);
            }
            
            return $results;
            
        } catch (ClientExceptionInterface $e) {
            $this->logger?->error('Remote API batch inference failed', [
                'model' => $model->getIdentifier(),
                'endpoint' => $endpoint,
                'batch_size' => count($inputs),
                'error' => $e->getMessage(),
            ]);
            
            throw InferenceException::forModel($model->getIdentifier(), "API request failed: {$e->getMessage()}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFormat(string $modelFormat): bool
    {
        return in_array($modelFormat, ['remote_api', 'rest_api', 'http'], true);
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        // Remote API engine is always "available" since it doesn't depend on local runtime
        // Actual availability checked during inference
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'remote_api';
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(Model $model): void
    {
        try {
            $dummyInput = $this->generateDummyInput($model);
            $this->predict($model, $dummyInput);
            
            $this->logger?->info('Remote API endpoint warmed up', [
                'model' => $model->getIdentifier(),
                'endpoint' => $this->getEndpoint($model),
            ]);
        } catch (\Throwable $e) {
            $this->logger?->warning('Failed to warm up remote API endpoint', [
                'model' => $model->getIdentifier(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get API endpoint from model metadata
     * 
     * @param Model $model
     * 
     * @return string API endpoint URL
     * 
     * @throws InferenceException If endpoint not configured
     */
    private function getEndpoint(Model $model): string
    {
        $endpoint = $model->getMetadata('endpoint') 
            ?? $model->getMetadata('api_url')
            ?? $model->artifactPath;

        if (empty($endpoint) || !filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw InferenceException::forModel(
                $model->getIdentifier(),
                'Invalid or missing API endpoint. Configure via metadata.endpoint or metadata.api_url'
            );
        }

        return $endpoint;
    }

    /**
     * Get HTTP headers from model metadata
     * 
     * @param Model $model
     * 
     * @return array<string, string>
     */
    private function getHeaders(Model $model): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Add authentication if configured
        $apiKey = $model->getMetadata('api_key');
        if ($apiKey) {
            $headers['Authorization'] = "Bearer {$apiKey}";
        }

        // Add custom headers
        $customHeaders = $model->getMetadata('headers');
        if (is_array($customHeaders)) {
            $headers = array_merge($headers, $customHeaders);
        }

        return $headers;
    }

    /**
     * Extract prediction from API response
     * 
     * Handles multiple response formats:
     * - MLflow: {"predictions": [...]}
     * - TensorFlow Serving: {"outputs": [...]}
     * - Direct array: [...]
     * 
     * @param array<string, mixed> $response
     * @param int $index
     * 
     * @return array<string, mixed>
     */
    private function extractPrediction(array $response, int $index): array
    {
        // Try different response formats
        $predictions = $response['predictions'] 
            ?? $response['outputs'] 
            ?? $response['results']
            ?? $response;

        if (isset($predictions[$index])) {
            $pred = $predictions[$index];
            
            // If prediction is scalar, wrap in array
            if (is_scalar($pred)) {
                return ['prediction' => $pred];
            }
            
            // If prediction is already array, return as-is
            if (is_array($pred)) {
                return $pred;
            }
        }

        return [];
    }

    /**
     * Generate dummy input for warm-up
     * 
     * @param Model $model
     * 
     * @return array<string, mixed>
     */
    private function generateDummyInput(Model $model): array
    {
        $inputSchema = $model->getInputSchema();
        
        if (empty($inputSchema)) {
            return ['feature1' => 0.0, 'feature2' => 0.0];
        }
        
        $dummy = [];
        foreach ($inputSchema as $feature => $type) {
            $dummy[$feature] = 0.0;
        }
        
        return $dummy;
    }
}
