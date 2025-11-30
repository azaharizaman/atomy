<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Services;

use DateTimeImmutable;
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\MachineLearning\Contracts\AnomalyResultInterface;
use Nexus\MachineLearning\Contracts\FeatureSetInterface;
use Nexus\MachineLearning\Contracts\IntelligenceContextInterface;
use Nexus\MachineLearning\Contracts\ModelRepositoryInterface;
use Nexus\MachineLearning\Core\Adapters\RuleBasedAnomalyEngine;
use Nexus\MachineLearning\Core\Contracts\ProviderInterface;
use Nexus\MachineLearning\Enums\SeverityLevel;
use Nexus\MachineLearning\Exceptions\FeatureVersionMismatchException;
use Nexus\MachineLearning\Exceptions\ModelNotFoundException;
use Nexus\MachineLearning\ValueObjects\AnomalyResult;
use Psr\Log\LoggerInterface;

/**
 * ML Model Manager (main orchestrator)
 * 
 * Coordinates ML operations with validation, fallback, and monitoring.
 */
final readonly class MLModelManager implements AnomalyDetectionServiceInterface
{
    /**
     * @param array<string, ProviderInterface> $providers Provider instances keyed by name
     * @param RuleBasedAnomalyEngine $fallbackEngine Rule-based fallback
     * @param ModelRepositoryInterface $repository Model persistence
     * @param IntelligenceContextInterface $context Runtime context
     * @param LoggerInterface $logger PSR-3 logger
     */
    public function __construct(
        private array $providers,
        private RuleBasedAnomalyEngine $fallbackEngine,
        private ModelRepositoryInterface $repository,
        private IntelligenceContextInterface $context,
        private LoggerInterface $logger
    ) {}

    public function evaluate(string $processContext, FeatureSetInterface $features): AnomalyResultInterface
    {
        $tenantId = $this->context->getCurrentTenantId();
        $startTime = microtime(true);
        
        try {
            // 1. Validate schema version
            $model = $this->getModelConfiguration($tenantId, $processContext);
            $this->validateFeatureVersion($processContext, $features, $model);
            
            // 2. Check for adversarial input (simplified for initial implementation)
            // TODO: Implement full adversarial detection in Phase 2
            
            // 3. Select provider (simplified - use configured provider or fallback)
            $provider = $this->selectProvider($model);
            
            // 4. Execute evaluation
            $result = $this->executeEvaluation($provider, $processContext, $features, $model);
            
            // 5. Record usage
            if ($provider !== null) {
                $this->recordUsage($tenantId, $processContext, $provider);
            }
            
            // 6. Log decision
            $this->logDecision($processContext, $features, $result, $tenantId);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Intelligence evaluation failed', [
                'process_context' => $processContext,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'features_hash' => $features->getHash(),
            ]);
            
            // Fallback to rule-based
            return $this->fallbackEngine->evaluate($processContext, $features);
        } finally {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->logger->info('Intelligence evaluation completed', [
                'process_context' => $processContext,
                'duration_ms' => round($duration, 2),
            ]);
        }
    }

    /**
     * Get model configuration
     * 
     * @param string $tenantId
     * @param string $processContext
     * @return array<string, mixed>
     * @throws ModelNotFoundException
     */
    private function getModelConfiguration(string $tenantId, string $processContext): array
    {
        $model = $this->repository->findModelByName($tenantId, $processContext);
        
        if ($model === null) {
            throw ModelNotFoundException::forName($tenantId, $processContext);
        }
        
        return $model;
    }

    /**
     * Validate feature schema version
     * 
     * @param string $modelName
     * @param FeatureSetInterface $features
     * @param array<string, mixed> $model
     * @throws FeatureVersionMismatchException
     */
    private function validateFeatureVersion(string $modelName, FeatureSetInterface $features, array $model): void
    {
        $expected = $model['expected_feature_version'] ?? '1.0';
        $actual = $features->getSchemaVersion();
        
        if ($expected !== $actual) {
            throw FeatureVersionMismatchException::forMismatch($modelName, $expected, $actual);
        }
    }

    /**
     * Select provider (fallback if unavailable)
     * 
     * @param array<string, mixed> $model
     * @return ProviderInterface|null
     */
    private function selectProvider(array $model): ?ProviderInterface
    {
        $providerName = $model['provider'] ?? 'openai';
        
        // Check if provider is available
        if (isset($this->providers[$providerName])) {
            // TODO: Add circuit breaker check here
            return $this->providers[$providerName];
        }
        
        // Provider not available, will use fallback
        return null;
    }

    /**
     * Execute evaluation
     * 
     * @param ProviderInterface|null $provider
     * @param string $processContext
     * @param FeatureSetInterface $features
     * @param array<string, mixed> $model
     * @return AnomalyResultInterface
     */
    private function executeEvaluation(
        ?ProviderInterface $provider,
        string $processContext,
        FeatureSetInterface $features,
        array $model
    ): AnomalyResultInterface {
        // Use fallback if no provider available
        if ($provider === null) {
            $this->logger->warning('Using rule-based fallback', [
                'process_context' => $processContext,
                'reason' => 'No provider available',
            ]);
            
            return $this->fallbackEngine->evaluate($processContext, $features);
        }
        
        // Call external provider (simplified for initial implementation)
        // TODO: Implement full provider integration in next phase
        $this->logger->info('Provider evaluation not yet implemented, using fallback', [
            'provider' => $provider->getName(),
            'process_context' => $processContext,
        ]);
        
        return $this->fallbackEngine->evaluate($processContext, $features);
    }

    /**
     * Record usage metrics
     * 
     * @param string $tenantId
     * @param string $processContext
     * @param ProviderInterface $provider
     */
    private function recordUsage(string $tenantId, string $processContext, ProviderInterface $provider): void
    {
        try {
            $metrics = $provider->getUsageMetrics();
            $domainContext = $this->extractDomainContext($processContext);
            
            $this->repository->recordUsage(
                $tenantId,
                $processContext,
                $domainContext,
                [
                    'tokens_used' => $metrics->getTokensUsed(),
                    'api_calls' => $metrics->getApiCalls(),
                    'api_cost' => $metrics->getApiCost(),
                    'measured_at' => $metrics->getMeasuredAt()->format('Y-m-d H:i:s'),
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to record usage', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'process_context' => $processContext,
            ]);
        }
    }

    /**
     * Extract domain context from process context
     * 
     * @param string $processContext e.g., 'procurement_po_qty_check'
     * @return string e.g., 'procurement'
     */
    private function extractDomainContext(string $processContext): string
    {
        $parts = explode('_', $processContext);
        return $parts[0] ?? 'unknown';
    }

    /**
     * Log AI decision
     * 
     * @param string $processContext
     * @param FeatureSetInterface $features
     * @param AnomalyResultInterface $result
     * @param string $tenantId
     */
    private function logDecision(
        string $processContext,
        FeatureSetInterface $features,
        AnomalyResultInterface $result,
        string $tenantId
    ): void {
        // TODO: Integrate with Nexus\AuditLogger in next phase
        $this->logger->info('AI decision made', [
            'tenant_id' => $tenantId,
            'process_context' => $processContext,
            'features_hash' => $features->getHash(),
            'flagged' => $result->isFlagged(),
            'severity' => $result->getSeverity()->value,
            'confidence' => $result->getCalibratedConfidence(),
            'requires_review' => $result->requiresHumanReview(),
            'model_version' => $result->getModelVersion(),
        ]);
    }
}
