<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use Nexus\MachineLearning\Core\Contracts\ProviderInterface;
use Nexus\MachineLearning\Exceptions\ProviderNotFoundException;

/**
 * Provider strategy interface for selecting ML providers based on domain and task type
 * 
 * This interface defines the contract for provider selection strategies, enabling
 * configurable selection logic (per-domain, per-task, fallback chains, etc.).
 * 
 * Implementations must be stateless and retrieve configuration via injected dependencies.
 * 
 * Example implementations:
 * - DomainProviderStrategy: Selects provider based on domain-specific configuration
 * - CostOptimizedStrategy: Selects cheapest provider first
 * - PerformanceStrategy: Selects fastest provider based on historical metrics
 */
interface ProviderStrategyInterface
{
    /**
     * Select an appropriate provider for the given domain, task type, and tenant
     * 
     * @param string $domain The domain requiring ML inference (e.g., 'procurement', 'receivable')
     * @param string $taskType The type of ML task (e.g., 'anomaly_detection', 'forecasting')
     * @param string $tenantId The tenant identifier for multi-tenant contexts
     * 
     * @return ProviderInterface The selected provider instance
     * 
     * @throws ProviderNotFoundException If no provider is configured for the given domain/task
     */
    public function selectProvider(string $domain, string $taskType, string $tenantId): ProviderInterface;

    /**
     * Get the provider selection priority for a given domain and task
     * 
     * Returns an ordered array of provider names that will be attempted in sequence.
     * Useful for logging and debugging provider selection decisions.
     * 
     * @param string $domain The domain name
     * @param string $taskType The task type
     * 
     * @return string[] Array of provider names in priority order (e.g., ['openai', 'anthropic', 'rule_based'])
     */
    public function getProviderPriority(string $domain, string $taskType): array;
}
