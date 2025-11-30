<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Services;

use Nexus\MachineLearning\Contracts\ProviderStrategyInterface;
use Nexus\MachineLearning\Core\Contracts\ProviderInterface;
use Nexus\MachineLearning\Exceptions\ProviderNotFoundException;
use Nexus\MachineLearning\ValueObjects\ProviderConfig;
use Nexus\Setting\Contracts\SettingsManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Domain-based provider selection strategy
 * 
 * Selects ML providers based on per-domain configuration stored in the settings system.
 * Falls back to global default configuration if domain-specific config is not found.
 * 
 * Configuration Structure (via Nexus\Setting):
 * - `ml.provider.{domain}` → Primary provider for domain (e.g., "openai")
 * - `ml.provider.{domain}.chain` → Full priority chain (e.g., ["openai", "anthropic", "rule_based"])
 * - `ml.provider.{domain}.{provider}.params` → Provider-specific parameters
 * - `ml.provider.fallback` → Global fallback provider (default: "rule_based")
 * - `ml.provider.timeout` → Global timeout in seconds (default: 30)
 * 
 * Example configuration:
 * ```php
 * // Procurement domain uses OpenAI with Anthropic fallback
 * $settings->set('ml.provider.procurement.chain', ['openai', 'anthropic', 'rule_based']);
 * $settings->set('ml.provider.procurement.openai.params', ['model' => 'gpt-4']);
 * 
 * // Receivable domain uses rule-based only
 * $settings->set('ml.provider.receivable', 'rule_based');
 * ```
 */
final readonly class DomainProviderStrategy implements ProviderStrategyInterface
{
    /**
     * @param SettingsManagerInterface $settings Settings manager for retrieving configuration
     * @param array<string, ProviderInterface> $availableProviders Map of provider name → provider instance
     * @param LoggerInterface|null $logger Optional logger for debugging provider selection
     */
    public function __construct(
        private SettingsManagerInterface $settings,
        private array $availableProviders,
        private ?LoggerInterface $logger = null,
    ) {
        if (empty($availableProviders)) {
            throw new \InvalidArgumentException('At least one provider must be available');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function selectProvider(string $domain, string $taskType, string $tenantId): ProviderInterface
    {
        $config = $this->resolveProviderConfig($domain, $taskType);
        $primaryProviderName = $config->getPrimaryProvider();

        if (!isset($this->availableProviders[$primaryProviderName])) {
            $this->logger?->warning('Primary provider not available, checking fallbacks', [
                'domain' => $domain,
                'task_type' => $taskType,
                'primary_provider' => $primaryProviderName,
                'available_providers' => array_keys($this->availableProviders),
            ]);

            // Try fallback providers
            foreach ($config->getFallbackProviders() as $fallbackName) {
                if (isset($this->availableProviders[$fallbackName])) {
                    $this->logger?->info('Using fallback provider', [
                        'domain' => $domain,
                        'task_type' => $taskType,
                        'provider' => $fallbackName,
                    ]);
                    return $this->availableProviders[$fallbackName];
                }
            }

            throw ProviderNotFoundException::forDomain($domain, $primaryProviderName);
        }

        $this->logger?->debug('Selected primary provider', [
            'domain' => $domain,
            'task_type' => $taskType,
            'provider' => $primaryProviderName,
        ]);

        return $this->availableProviders[$primaryProviderName];
    }

    /**
     * {@inheritDoc}
     */
    public function getProviderPriority(string $domain, string $taskType): array
    {
        $config = $this->resolveProviderConfig($domain, $taskType);
        return $config->providers;
    }

    /**
     * Resolve provider configuration for a specific domain and task type
     * 
     * Resolution order:
     * 1. Domain + task specific: ml.provider.{domain}.{taskType}.chain
     * 2. Domain-level chain: ml.provider.{domain}.chain
     * 3. Domain-level single provider: ml.provider.{domain}
     * 4. Global fallback: ml.provider.fallback (default: "rule_based")
     * 
     * @param string $domain The domain name
     * @param string $taskType The task type
     * 
     * @return ProviderConfig Resolved provider configuration
     */
    private function resolveProviderConfig(string $domain, string $taskType): ProviderConfig
    {
        // 1. Try domain + task specific chain
        $domainTaskKey = "ml.provider.{$domain}.{$taskType}.chain";
        $chain = $this->settings->get($domainTaskKey);
        if (is_array($chain) && !empty($chain)) {
            return new ProviderConfig(
                providers: $chain,
                parameters: $this->resolveProviderParameters($domain, $taskType),
                timeoutSeconds: $this->settings->getInt('ml.provider.timeout', 30),
                retryAttempts: $this->settings->getInt('ml.provider.retry_attempts', 2),
            );
        }

        // 2. Try domain-level chain
        $domainChainKey = "ml.provider.{$domain}.chain";
        $chain = $this->settings->get($domainChainKey);
        if (is_array($chain) && !empty($chain)) {
            return new ProviderConfig(
                providers: $chain,
                parameters: $this->resolveProviderParameters($domain, $taskType),
                timeoutSeconds: $this->settings->getInt('ml.provider.timeout', 30),
                retryAttempts: $this->settings->getInt('ml.provider.retry_attempts', 2),
            );
        }

        // 3. Try domain-level single provider
        $domainProviderKey = "ml.provider.{$domain}";
        $provider = $this->settings->getString($domainProviderKey);
        if ($provider !== null && $provider !== '') {
            return new ProviderConfig(
                providers: [$provider, $this->getFallbackProvider()],
                parameters: $this->resolveProviderParameters($domain, $taskType),
                timeoutSeconds: $this->settings->getInt('ml.provider.timeout', 30),
                retryAttempts: $this->settings->getInt('ml.provider.retry_attempts', 2),
            );
        }

        // 4. Use global fallback
        $fallbackProvider = $this->getFallbackProvider();
        
        $this->logger?->info('No domain-specific provider configured, using fallback', [
            'domain' => $domain,
            'task_type' => $taskType,
            'fallback_provider' => $fallbackProvider,
        ]);

        return new ProviderConfig(
            providers: [$fallbackProvider],
            parameters: [],
            timeoutSeconds: $this->settings->getInt('ml.provider.timeout', 30),
            retryAttempts: $this->settings->getInt('ml.provider.retry_attempts', 2),
        );
    }

    /**
     * Resolve provider-specific parameters for a domain and task
     * 
     * Checks for parameters at multiple levels:
     * - ml.provider.{domain}.{taskType}.{providerName}.params
     * - ml.provider.{domain}.{providerName}.params
     * - ml.provider.{providerName}.params (global default)
     * 
     * @param string $domain The domain name
     * @param string $taskType The task type
     * 
     * @return array<string, array<string, mixed>> Provider parameters indexed by provider name
     */
    private function resolveProviderParameters(string $domain, string $taskType): array
    {
        $parameters = [];

        foreach ($this->availableProviders as $providerName => $_) {
            // Task-specific params (highest priority)
            $taskParams = $this->settings->get("ml.provider.{$domain}.{$taskType}.{$providerName}.params");
            
            // Domain-level params
            $domainParams = $this->settings->get("ml.provider.{$domain}.{$providerName}.params");
            
            // Global provider params (lowest priority)
            $globalParams = $this->settings->get("ml.provider.{$providerName}.params");

            // Merge with priority: task > domain > global
            $merged = array_merge(
                is_array($globalParams) ? $globalParams : [],
                is_array($domainParams) ? $domainParams : [],
                is_array($taskParams) ? $taskParams : [],
            );

            if (!empty($merged)) {
                $parameters[$providerName] = $merged;
            }
        }

        return $parameters;
    }

    /**
     * Get the global fallback provider name
     * 
     * @return string Fallback provider name (default: "rule_based")
     */
    private function getFallbackProvider(): string
    {
        return $this->settings->getString('ml.provider.fallback', 'rule_based');
    }
}
