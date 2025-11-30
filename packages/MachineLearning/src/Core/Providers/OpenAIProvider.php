<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Core\Providers;

use Nexus\MachineLearning\Contracts\HttpClientInterface;
use Nexus\MachineLearning\Core\Contracts\ProviderInterface;
use Nexus\MachineLearning\Exceptions\ProviderUnavailableException;
use Nexus\MachineLearning\Exceptions\InferenceTimeoutException;
use Nexus\MachineLearning\ValueObjects\UsageMetrics;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * OpenAI provider for machine learning inference
 * 
 * Integrates with OpenAI's API (GPT-4, GPT-3.5, etc.) for:
 * - Natural language processing
 * - Anomaly detection via chat completion
 * - Classification and prediction tasks
 * - Fine-tuning custom models
 * 
 * Requires:
 * - OpenAI API key (via settings or constructor)
 * - HttpClientInterface for API communication
 * 
 * Configuration:
 * - Base URL: https://api.openai.com/v1
 * - Default model: gpt-4
 * - Temperature: 0.7
 * - Max tokens: 1000
 * 
 * Pricing (as of 2024):
 * - GPT-4: $0.03/1K tokens (input), $0.06/1K tokens (output)
 * - GPT-3.5-turbo: $0.0015/1K tokens (input), $0.002/1K tokens (output)
 */
final class OpenAIProvider implements ProviderInterface
{
    private const BASE_URL = 'https://api.openai.com/v1';
    private const DEFAULT_MODEL = 'gpt-4';
    private const DEFAULT_TEMPERATURE = 0.7;
    private const DEFAULT_MAX_TOKENS = 1000;
    private const DEFAULT_TIMEOUT = 30;

    // Pricing per 1K tokens (USD) - GPT-4
    private const COST_PER_1K_INPUT_TOKENS = 0.03;
    private const COST_PER_1K_OUTPUT_TOKENS = 0.06;

    private UsageMetrics $lastUsageMetrics;

    /**
     * @param HttpClientInterface $httpClient HTTP client for API requests
     * @param string $apiKey OpenAI API key
     * @param string $model Model to use (default: gpt-4)
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $model = self::DEFAULT_MODEL,
        private readonly ?LoggerInterface $logger = null,
    ) {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('OpenAI API key cannot be empty');
        }

        $this->lastUsageMetrics = new UsageMetrics(
            tokensUsed: 0,
            costUsd: 0.0,
            latencyMs: 0,
            requestTimestamp: new \DateTimeImmutable()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function sendRequest(array $request): array
    {
        $startTime = microtime(true);

        try {
            $response = $this->callCompletionAPI($request);
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Extract usage from response
            $usage = $response['usage'] ?? [];
            $totalTokens = $usage['total_tokens'] ?? 0;
            $promptTokens = $usage['prompt_tokens'] ?? 0;
            $completionTokens = $usage['completion_tokens'] ?? 0;

            // Calculate cost
            $costUsd = $this->calculateCost($promptTokens, $completionTokens);

            // Update metrics
            $this->lastUsageMetrics = new UsageMetrics(
                tokensUsed: $totalTokens,
                costUsd: $costUsd,
                latencyMs: $latencyMs,
                requestTimestamp: new \DateTimeImmutable()
            );

            // Extract result from response
            $content = $response['choices'][0]['message']['content'] ?? '';
            
            // Parse JSON response if possible
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $decoded['provider'] = 'openai';
                $decoded['model'] = $this->model;
                return $decoded;
            }

            // Return raw content if not JSON
            return [
                'result' => $content,
                'provider' => 'openai',
                'model' => $this->model,
            ];

        } catch (ClientExceptionInterface $e) {
            $this->logger?->error('OpenAI API request failed', [
                'error' => $e->getMessage(),
                'request' => $request,
            ]);

            throw ProviderUnavailableException::forProvider('openai', $e->getMessage());
        } catch (\JsonException $e) {
            $this->logger?->error('Failed to parse OpenAI response', [
                'error' => $e->getMessage(),
            ]);

            throw ProviderUnavailableException::forProvider('openai', 'Invalid JSON response: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getUsageMetrics(): UsageMetrics
    {
        return $this->lastUsageMetrics;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFeatureImportance(): bool
    {
        return false; // OpenAI doesn't expose feature importance
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFineTuning(): bool
    {
        return true; // OpenAI supports fine-tuning
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'openai';
    }

    /**
     * {@inheritDoc}
     */
    public function getCostPerToken(): float
    {
        // Average of input and output costs
        return (self::COST_PER_1K_INPUT_TOKENS + self::COST_PER_1K_OUTPUT_TOKENS) / 2000;
    }

    /**
     * {@inheritDoc}
     */
    public function submitFineTuningJob(array $trainingData, array $config): string
    {
        try {
            // Upload training data first
            $fileId = $this->uploadTrainingFile($trainingData);

            // Create fine-tuning job
            $response = $this->httpClient->post(self::BASE_URL . '/fine_tuning/jobs', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'training_file' => $fileId,
                    'model' => $config['base_model'] ?? $this->model,
                    'hyperparameters' => $config['hyperparameters'] ?? [],
                ],
                'timeout' => self::DEFAULT_TIMEOUT,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            return $body['id'] ?? throw new \RuntimeException('Fine-tuning job ID not found in response');

        } catch (ClientExceptionInterface $e) {
            throw ProviderUnavailableException::forProvider('openai', 'Fine-tuning job submission failed: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getFineTuningStatus(string $jobId): string
    {
        try {
            $response = $this->httpClient->get(self::BASE_URL . "/fine_tuning/jobs/{$jobId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'timeout' => self::DEFAULT_TIMEOUT,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            return $body['status'] ?? 'unknown';

        } catch (ClientExceptionInterface $e) {
            throw ProviderUnavailableException::forProvider('openai', 'Failed to get fine-tuning status: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function cancelFineTuningJob(string $jobId): void
    {
        try {
            $this->httpClient->post(self::BASE_URL . "/fine_tuning/jobs/{$jobId}/cancel", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'timeout' => self::DEFAULT_TIMEOUT,
            ]);
        } catch (ClientExceptionInterface $e) {
            throw ProviderUnavailableException::forProvider('openai', 'Failed to cancel fine-tuning job: ' . $e->getMessage());
        }
    }

    /**
     * Call OpenAI Chat Completion API
     * 
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws ClientExceptionInterface
     */
    private function callCompletionAPI(array $request): array
    {
        $prompt = $this->buildPrompt($request);
        
        $response = $this->httpClient->post(self::BASE_URL . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a machine learning assistant that analyzes data and returns structured JSON responses.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => $request['temperature'] ?? self::DEFAULT_TEMPERATURE,
                'max_tokens' => $request['max_tokens'] ?? self::DEFAULT_MAX_TOKENS,
            ],
            'timeout' => $request['timeout'] ?? self::DEFAULT_TIMEOUT,
        ]);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Build prompt from request data
     * 
     * @param array<string, mixed> $request
     * @return string
     */
    private function buildPrompt(array $request): string
    {
        $taskType = $request['task_type'] ?? 'analysis';
        $features = $request['features'] ?? [];
        $context = $request['context'] ?? '';

        $prompt = "Task: {$taskType}\n\n";
        
        if (!empty($context)) {
            $prompt .= "Context: {$context}\n\n";
        }

        $prompt .= "Features:\n";
        foreach ($features as $key => $value) {
            $prompt .= "- {$key}: {$value}\n";
        }

        $prompt .= "\nPlease analyze the data and return a JSON response with your findings.";

        return $prompt;
    }

    /**
     * Upload training file for fine-tuning
     * 
     * @param array<array<string, mixed>> $trainingData
     * @return string File ID
     * @throws ClientExceptionInterface
     */
    private function uploadTrainingFile(array $trainingData): string
    {
        // Convert to JSONL format
        $jsonl = '';
        foreach ($trainingData as $example) {
            $jsonl .= json_encode($example) . "\n";
        }

        $response = $this->httpClient->post(self::BASE_URL . '/files', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'multipart' => [
                [
                    'name' => 'purpose',
                    'contents' => 'fine-tune',
                ],
                [
                    'name' => 'file',
                    'contents' => $jsonl,
                    'filename' => 'training_data.jsonl',
                ],
            ],
            'timeout' => self::DEFAULT_TIMEOUT,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        return $body['id'] ?? throw new \RuntimeException('File ID not found in upload response');
    }

    /**
     * Calculate cost based on token usage
     * 
     * @param int $promptTokens
     * @param int $completionTokens
     * @return float Cost in USD
     */
    private function calculateCost(int $promptTokens, int $completionTokens): float
    {
        $inputCost = ($promptTokens / 1000) * self::COST_PER_1K_INPUT_TOKENS;
        $outputCost = ($completionTokens / 1000) * self::COST_PER_1K_OUTPUT_TOKENS;
        
        return $inputCost + $outputCost;
    }
}
