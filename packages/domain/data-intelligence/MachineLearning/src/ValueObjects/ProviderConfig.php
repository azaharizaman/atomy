<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

/**
 * Immutable value object representing provider configuration
 * 
 * Contains the priority chain of providers to attempt for a specific domain or task,
 * along with provider-specific parameters (API keys, endpoints, model names, etc.).
 * 
 * Example:
 * ```php
 * $config = new ProviderConfig(
 *     providers: ['openai', 'anthropic', 'rule_based'],
 *     parameters: [
 *         'openai' => ['model' => 'gpt-4', 'temperature' => 0.7],
 *         'anthropic' => ['model' => 'claude-3-sonnet', 'max_tokens' => 1000],
 *     ]
 * );
 * ```
 */
final readonly class ProviderConfig
{
    /**
     * @param string[] $providers Ordered array of provider names to attempt (priority order)
     * @param array<string, array<string, mixed>> $parameters Provider-specific parameters indexed by provider name
     * @param int $timeoutSeconds Maximum time allowed for inference operation (default: 30)
     * @param int $retryAttempts Number of retry attempts before moving to next provider (default: 2)
     */
    public function __construct(
        public array $providers,
        public array $parameters = [],
        public int $timeoutSeconds = 30,
        public int $retryAttempts = 2,
    ) {
        if (empty($providers)) {
            throw new \InvalidArgumentException('Provider list cannot be empty');
        }

        if ($timeoutSeconds < 1) {
            throw new \InvalidArgumentException('Timeout must be at least 1 second');
        }

        if ($retryAttempts < 0) {
            throw new \InvalidArgumentException('Retry attempts cannot be negative');
        }
    }

    /**
     * Get the primary (highest priority) provider
     * 
     * @return string The name of the first provider in the priority chain
     */
    public function getPrimaryProvider(): string
    {
        return $this->providers[0];
    }

    /**
     * Get fallback providers (all except primary)
     * 
     * @return string[] Array of fallback provider names
     */
    public function getFallbackProviders(): array
    {
        return array_slice($this->providers, 1);
    }

    /**
     * Get parameters for a specific provider
     * 
     * @param string $providerName The provider name
     * 
     * @return array<string, mixed> Provider-specific parameters, or empty array if none configured
     */
    public function getProviderParameters(string $providerName): array
    {
        return $this->parameters[$providerName] ?? [];
    }

    /**
     * Check if a specific provider is configured
     * 
     * @param string $providerName The provider name to check
     * 
     * @return bool True if the provider is in the priority chain
     */
    public function hasProvider(string $providerName): bool
    {
        return in_array($providerName, $this->providers, true);
    }

    /**
     * Create a new instance with additional provider parameters
     * 
     * @param string $providerName The provider name
     * @param array<string, mixed> $parameters Parameters to merge with existing
     * 
     * @return self New instance with updated parameters
     */
    public function withProviderParameters(string $providerName, array $parameters): self
    {
        $updatedParameters = $this->parameters;
        $updatedParameters[$providerName] = array_merge(
            $this->parameters[$providerName] ?? [],
            $parameters
        );

        return new self(
            providers: $this->providers,
            parameters: $updatedParameters,
            timeoutSeconds: $this->timeoutSeconds,
            retryAttempts: $this->retryAttempts,
        );
    }

    /**
     * Create a new instance with modified timeout
     * 
     * @param int $timeoutSeconds New timeout in seconds
     * 
     * @return self New instance with updated timeout
     */
    public function withTimeout(int $timeoutSeconds): self
    {
        return new self(
            providers: $this->providers,
            parameters: $this->parameters,
            timeoutSeconds: $timeoutSeconds,
            retryAttempts: $this->retryAttempts,
        );
    }

    /**
     * Create default configuration for rule-based provider only
     * 
     * @return self Configuration with only rule-based provider
     */
    public static function ruleBasedOnly(): self
    {
        return new self(
            providers: ['rule_based'],
            parameters: [],
            timeoutSeconds: 5,
            retryAttempts: 0,
        );
    }

    /**
     * Create default configuration with full external AI provider chain
     * 
     * @return self Configuration with OpenAI → Anthropic → Gemini → Rule-based fallback
     */
    public static function defaultChain(): self
    {
        return new self(
            providers: ['openai', 'anthropic', 'gemini', 'rule_based'],
            parameters: [],
            timeoutSeconds: 30,
            retryAttempts: 2,
        );
    }
}
