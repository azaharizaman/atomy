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
 * Google Gemini provider for machine learning inference
 * 
 * Integrates with Google's Gemini API for:
 * - Natural language processing
 * - Multimodal reasoning (text, images, video)
 * - Anomaly detection and classification
 * - Code generation and analysis
 * 
 * Requires:
 * - Google API key (via settings or constructor)
 * - HttpClientInterface for API communication
 * 
 * Configuration:
 * - Base URL: https://generativelanguage.googleapis.com/v1beta
 * - Default model: gemini-pro
 * - Temperature: 0.7
 * 
 * Pricing (as of 2024):
 * - Gemini Pro: Free tier available, then $0.50/$1.50 per MTok (input/output)
 * - Gemini Pro Vision: $0.25/$0.75 per MTok (input/output)
 * 
 * Note: Google does not currently support fine-tuning via API
 */
final class GeminiProvider implements ProviderInterface
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';
    private const DEFAULT_MODEL = 'gemini-pro';
    private const DEFAULT_TEMPERATURE = 0.7;
    private const DEFAULT_TIMEOUT = 30;

    // Pricing per million tokens (USD) - Gemini Pro
    private const COST_PER_MTok_INPUT = 0.5;
    private const COST_PER_MTok_OUTPUT = 1.5;

    private UsageMetrics $lastUsageMetrics;

    /**
     * @param HttpClientInterface $httpClient HTTP client for API requests
     * @param string $apiKey Google API key
     * @param string $model Model to use (default: gemini-pro)
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $model = self::DEFAULT_MODEL,
        private readonly ?LoggerInterface $logger = null,
    ) {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('Google API key cannot be empty');
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
            $response = $this->callGenerateContentAPI($request);
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Extract usage from response (Gemini uses different structure)
            $usageMetadata = $response['usageMetadata'] ?? [];
            $promptTokenCount = $usageMetadata['promptTokenCount'] ?? 0;
            $candidatesTokenCount = $usageMetadata['candidatesTokenCount'] ?? 0;
            $totalTokens = $promptTokenCount + $candidatesTokenCount;

            // Calculate cost
            $costUsd = $this->calculateCost($promptTokenCount, $candidatesTokenCount);

            // Update metrics
            $this->lastUsageMetrics = new UsageMetrics(
                tokensUsed: $totalTokens,
                costUsd: $costUsd,
                latencyMs: $latencyMs,
                requestTimestamp: new \DateTimeImmutable()
            );

            // Extract result from response
            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Parse JSON response if possible
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $decoded['provider'] = 'gemini';
                $decoded['model'] = $this->model;
                return $decoded;
            }

            // Return raw content if not JSON
            return [
                'result' => $content,
                'provider' => 'gemini',
                'model' => $this->model,
            ];

        } catch (ClientExceptionInterface $e) {
            $this->logger?->error('Gemini API request failed', [
                'error' => $e->getMessage(),
                'request' => $request,
            ]);

            throw ProviderUnavailableException::forProvider('gemini', $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger?->error('Unexpected error in Gemini provider', [
                'error' => $e->getMessage(),
            ]);

            throw ProviderUnavailableException::forProvider('gemini', $e->getMessage());
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
        return false; // Gemini doesn't expose feature importance
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFineTuning(): bool
    {
        return false; // Gemini doesn't support fine-tuning via API
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'gemini';
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
     * @throws FineTuningNotSupportedException Always (Gemini doesn't support fine-tuning)
     */
    public function submitFineTuningJob(array $trainingData, array $config): string
    {
        throw FineTuningNotSupportedException::forProvider($this->getName());
    }

    /**
     * {@inheritDoc}
     * 
     * @throws FineTuningNotSupportedException Always (Gemini doesn't support fine-tuning)
     */
    public function getFineTuningStatus(string $jobId): string
    {
        throw FineTuningNotSupportedException::forProvider($this->getName());
    }

    /**
     * {@inheritDoc}
     * 
     * @throws FineTuningNotSupportedException Always (Gemini doesn't support fine-tuning)
     */
    public function cancelFineTuningJob(string $jobId): void
    {
        throw FineTuningNotSupportedException::forProvider($this->getName());
    }

    /**
     * Call Gemini Generate Content API
     * 
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     * @throws ClientExceptionInterface
     */
    private function callGenerateContentAPI(array $request): array
    {
        $prompt = $this->buildPrompt($request);
        
        // Gemini uses query parameter for API key
        $url = self::BASE_URL . "/models/{$this->model}:generateContent?key={$this->apiKey}";
        
        $response = $this->httpClient->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $request['temperature'] ?? self::DEFAULT_TEMPERATURE,
                    'maxOutputTokens' => $request['max_tokens'] ?? 1000,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                ],
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

        $prompt = "You are a machine learning assistant that analyzes data and returns structured JSON responses.\n\n";
        $prompt .= "Task: {$taskType}\n\n";
        
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
