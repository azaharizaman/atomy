<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Core\Contracts;

use Nexus\Intelligence\ValueObjects\UsageMetrics;

/**
 * AI provider interface
 * 
 * External AI service adapter contract.
 */
interface ProviderInterface
{
    /**
     * Send request to provider
     * 
     * @param array<string, mixed> $request Request payload
     * @return array<string, mixed> Provider response
     * @throws \Nexus\Intelligence\Exceptions\ProviderUnavailableException
     */
    public function sendRequest(array $request): array;

    /**
     * Get usage metrics from last request
     * 
     * @return UsageMetrics
     */
    public function getUsageMetrics(): UsageMetrics;

    /**
     * Check if provider supports feature importance
     * 
     * @return bool
     */
    public function supportsFeatureImportance(): bool;

    /**
     * Check if provider supports fine-tuning
     * 
     * @return bool
     */
    public function supportsFineTuning(): bool;

    /**
     * Get provider name
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Get cost per token in USD
     * 
     * @return float
     */
    public function getCostPerToken(): float;

    /**
     * Submit fine-tuning job
     * 
     * @param array<array<string, mixed>> $trainingData Training examples
     * @param array<string, mixed> $config Fine-tuning configuration
     * @return string Job ID
     * @throws \Nexus\Intelligence\Exceptions\FineTuningNotSupportedException
     */
    public function submitFineTuningJob(array $trainingData, array $config): string;

    /**
     * Get fine-tuning status
     * 
     * @param string $jobId Job identifier
     * @return string Status (pending, running, completed, failed)
     */
    public function getFineTuningStatus(string $jobId): string;

    /**
     * Cancel fine-tuning job
     * 
     * @param string $jobId Job identifier
     * @return void
     */
    public function cancelFineTuningJob(string $jobId): void;
}
