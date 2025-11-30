<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Core\Providers;

use Nexus\MachineLearning\Contracts\HttpClientInterface;
use Nexus\MachineLearning\Core\Contracts\ProviderInterface;
use Nexus\MachineLearning\Exceptions\ProviderUnavailableException;
use Nexus\MachineLearning\Exceptions\FineTuningNotSupportedException;
use Nexus\MachineLearning\ValueObjects\UsageMetrics;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Anthropic provider for machine learning inference
 * 
 * Integrates with Anthropic's Claude API for:
 * - Natural language processing
 * - Anomaly detection via message completion
 * - Classification and prediction tasks
 * - Long-context reasoning (Claude models support up to 200K tokens)
 * 
 * Requires:
 * - Anthropic API key (via settings or constructor)
 * - HttpClientInterface for API communication
 * 
 * Configuration:
 * - Base URL: https://api.anthropic.com/v1
 * - Default model: claude-3-sonnet-20240229
 * - Temperature: 0.7
 * - Max tokens: 1000
 * 
 * Pricing (as of 2024):
 * - Claude 3 Opus: $15/$75 per MTok (input/output)
 * - Claude 3 Sonnet: $3/$15 per MTok (input/output)
 * - Claude 3 Haiku: $0.25/$1.25 per MTok (input/output)
 * 
 * Note: Anthropic does not currently support fine-tuning
 */
final class AnthropicProvider implements ProviderInterface
{
    private const BASE_URL = 'https://api.anthropic.com/v1';
    private const DEFAULT_MODEL = 'claude-3-sonnet-20240229';
    private const DEFAULT_TEMPERATURE = 0.7;
    private const DEFAULT_MAX_TOKENS = 1000;
    private const DEFAULT_TIMEOUT = 30;
    private const ANTHROPIC_VERSION = '2023-06-01';

    // Pricing per million tokens (USD) - Claude 3 Sonnet
    private const COST_PER_MTok_INPUT = 3.0;
    private const COST_PER_MTok_OUTPUT = 15.0;

    private UsageMetrics $lastUsageMetrics;

    /**
     * @param HttpClientInterface $httpClient HTTP client for API requests
     * @param string $apiKey Anthropic API key
     * @param string $model Model to use (default: claude-3-sonnet-20240229)
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $model = self::DEFAULT_MODEL,
        private readonly ?LoggerInterface $logger = null,
    ) {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('Anthropic API key cannot be empty');
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
            $response = $this->callMessagesAPI($request);
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Extract usage from response
            $usage = $response['usage'] ?? [];
            $inputTokens = $usage['input_tokens'] ?? 0;
            $outputTokens = $usage['output_tokens'] ?? 0;
            $totalTokens = $inputTokens + $outputTokens;

            // Calculate cost
            $costUsd = $this->calculateCost($inputTokens, $outputTokens);

            // Update metrics
            $this->lastUsageMetrics = new UsageMetrics(
                tokensUsed: $totalTokens,
                costUsd: $costUsd,
                latencyMs: $latencyMs,
                requestTimestamp: new \DateTimeImmutable()
            );

            // Extract result from response
            $content = $response['content'][0]['text'] ?? '';
            
            // Parse JSON response if possible
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $decoded['provider'] = 'anthropic';
                $decoded['model'] = $this->model;
                return $decoded;
            }

            // Return raw content if not JSON
            return [
                'result' => $content,
                'provider' => 'anthropic',
                'model' => $this->model,
            ];

        } catch (ClientExceptionInterface $e) {
            $this->logger?->error('Anthropic API request failed', [
                'error' => $e->getMessage(),
                'request' => $request,
            ]);

            throw ProviderUnavailableException::forProvider('anthropic', $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger?->error('Unexpected error in Anthropic provider', [
                'error' => $e->getMessage(),
            ]);

            throw ProviderUnavailableException::forProvider('anthropic', $e->getMessage());
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
        return false; // Claude doesn't expose feature importance
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFineTuning(): bool
    {
        return false; // Anthropic doesn't support fine-tuning yet
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'anthropic';
    }

    /**
     * {@inheritDoc}
     */
    public function getCostPerToken(): float
    {
        // Average of input and output costs (per token, not per million)
        return (self::COST_PER_MTok_INPUT + self::COST_PER_MTok_OUTPUT) / 2_000_000;
    }

    /**
     * {@inheritDoc}
     * 
     * @throws FineTuningNotSupportedException Always (Anthropic doesn't support fine-tuning)
     */
    public function submitFineTuningJob(array $trainingData, array $config): string
    {
        throw FineTuningNotSupportedException::forProvider($this->getName());
    }

    /**
     * {@inheritDoc}
     * 
     * @throws FineTuningNotSupportedException Always (Anthropic doesn't support fine-tuning)
     */
    public function getFineTuningStatus(string $jobId): string
    {
        throw FineTuningNotSupportedException::forProvider($this->getName());
    }

    /**
     * {@inheritDoc}
     * 
     * @throws FineTuningNotSupportedException Always (Anthropic doesn't support fine-tuning)
     */
    public function cancelFineTuningJob(string $jobId): void
    {
        throw FineTuningNotSupportedException::forProvider($this->getName());
    }

    /**
     * Call Anthropic Messages API
     * 
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws ClientExceptionInterface
     */
    private function callMessagesAPI(array $request): array
    {
        $prompt = $this->buildPrompt($request);
        
        $response = $this->httpClient->post(self::BASE_URL . '/messages', [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => self::ANTHROPIC_VERSION,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'system' => 'You are a machine learning assistant that analyzes data and returns structured JSON responses.',
                'temperature' => $request['temperature'] ?? self::DEFAULT_TEMPERATURE,
                'max_tokens' => $request['max_tokens'] ?? self::DEFAULT_MAX_TOKENS,
            ],
            'timeout' => $request['timeout'] ?? self::DEFAULT_TIMEOUT,
        ]);

        return json_decode($response->getBody()->getContents(), true);
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
     * Calculate cost based on token usage
     * 
     * @param int $inputTokens
     * @param int $outputTokens
     * @return float Cost in USD
     */
    private function calculateCost(int $inputTokens, int $outputTokens): float
    {
        $inputCost = ($inputTokens / 1_000_000) * self::COST_PER_MTok_INPUT;
        $outputCost = ($outputTokens / 1_000_000) * self::COST_PER_MTok_OUTPUT;
        
        return $inputCost + $outputCost;
    }
}
