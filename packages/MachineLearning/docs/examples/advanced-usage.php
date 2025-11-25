<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Nexus MachineLearning Package
 * 
 * This example demonstrates advanced features:
 * - Provider strategy configuration
 * - MLflow model loading and inference
 * - Local model execution (PyTorch/ONNX)
 * - Batch processing
 * - Feature versioning
 * - Custom providers
 * 
 * @package Nexus\MachineLearning\Examples
 */

namespace Examples\MachineLearning\Advanced;

use Nexus\MachineLearning\Contracts\{
    AnomalyDetectionServiceInterface,
    ProviderStrategyInterface,
    ModelLoaderInterface,
    InferenceEngineInterface,
    FeatureVersionManagerInterface,
    MLflowClientInterface
};
use Nexus\MachineLearning\Core\Contracts\ProviderInterface;
use Nexus\MachineLearning\Enums\{ModelFormat, ProviderType};
use Psr\Log\LoggerInterface;

// ============================================================================
// Example 1: Custom Provider Strategy
// ============================================================================

/**
 * Cost-optimized provider strategy
 * 
 * Selects providers based on cost per 1K tokens
 */
final readonly class CostOptimizedProviderStrategy implements ProviderStrategyInterface
{
    private const PROVIDER_COSTS = [
        'openai' => 0.03,     // $0.03 per 1K tokens (gpt-4-turbo)
        'anthropic' => 0.015, // $0.015 per 1K tokens (claude-3-5-sonnet)
        'gemini' => 0.001,    // $0.001 per 1K tokens (gemini-1.5-pro)
        'rulebased' => 0.0,   // Free
    ];
    
    public function __construct(
        private ProviderFactoryInterface $factory,
        private LoggerInterface $logger
    ) {}
    
    public function selectProvider(
        string $domain, 
        string $taskType, 
        string $tenantId
    ): ProviderInterface {
        // Sort providers by cost
        $providers = self::PROVIDER_COSTS;
        asort($providers);
        
        // Try providers from cheapest to most expensive
        foreach (array_keys($providers) as $providerName) {
            $provider = $this->factory->create($providerName);
            
            if ($provider->isAvailable()) {
                $this->logger->info("Selected provider {$providerName}", [
                    'cost_per_1k' => $providers[$providerName],
                    'domain' => $domain,
                ]);
                
                return $provider;
            }
        }
        
        throw new ProviderNotFoundException('No available providers');
    }
    
    public function getProviderPriority(string $domain, string $taskType): array
    {
        return ['gemini', 'anthropic', 'openai', 'rulebased'];
    }
}

// ============================================================================
// Example 2: MLflow Model Loading and Inference
// ============================================================================

/**
 * Fraud detection service using MLflow-hosted models
 */
final readonly class MLflowFraudDetector
{
    public function __construct(
        private ModelLoaderInterface $loader,
        private InferenceEngineInterface $engine,
        private MLflowClientInterface $mlflow,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Predict fraud using production model from MLflow
     */
    public function predictFraud(array $transactionData): array
    {
        // Load production model from MLflow registry
        $model = $this->loader->load(
            modelName: 'fraud_detection_v2',
            stage: 'production'  // Only production models
        );
        
        $this->logger->info('Loaded fraud detection model', [
            'model_name' => $model->getName(),
            'model_version' => $model->getVersion(),
            'format' => $model->getFormat(),
        ]);
        
        // Prepare input features
        $features = $this->prepareFeatures($transactionData);
        
        // Run inference
        $startTime = microtime(true);
        $prediction = $this->engine->predict($model, $features);
        $duration = (microtime(true) - $startTime) * 1000;
        
        $this->logger->info('Inference completed', [
            'duration_ms' => $duration,
            'prediction' => $prediction,
        ]);
        
        return [
            'is_fraud' => $prediction['class'] === 'fraud',
            'confidence' => $prediction['probability'][1],  // Fraud probability
            'risk_score' => $prediction['risk_score'],
            'model_version' => $model->getVersion(),
            'inference_time_ms' => $duration,
        ];
    }
    
    /**
     * Batch fraud detection with MLflow experiment tracking
     */
    public function predictBatch(array $transactions): array
    {
        $runId = $this->mlflow->startRun('fraud_detection_production');
        
        try {
            $model = $this->loader->load('fraud_detection_v2', stage: 'production');
            
            // Log model parameters
            $this->mlflow->logParams($runId, [
                'model_name' => $model->getName(),
                'model_version' => $model->getVersion(),
                'batch_size' => count($transactions),
            ]);
            
            // Prepare batch inputs
            $inputs = array_map(
                fn($tx) => $this->prepareFeatures($tx), 
                $transactions
            );
            
            // Batch inference
            $predictions = $this->engine->predictBatch($model, $inputs);
            
            // Calculate metrics
            $fraudCount = count(array_filter($predictions, fn($p) => $p['class'] === 'fraud'));
            $avgConfidence = array_sum(array_column($predictions, 'probability')) / count($predictions);
            
            // Log metrics to MLflow
            $this->mlflow->logMetrics($runId, [
                'total_predictions' => count($predictions),
                'fraud_detected' => $fraudCount,
                'fraud_rate' => $fraudCount / count($predictions),
                'avg_confidence' => $avgConfidence,
            ]);
            
            $this->mlflow->endRun($runId, 'FINISHED');
            
            return $predictions;
            
        } catch (\Throwable $e) {
            $this->mlflow->endRun($runId, 'FAILED');
            throw $e;
        }
    }
    
    private function prepareFeatures(array $transaction): array
    {
        return [
            'amount' => (float) $transaction['amount'],
            'merchant_category' => $transaction['merchant_category'],
            'hour_of_day' => (int) date('H', $transaction['timestamp']),
            'day_of_week' => (int) date('N', $transaction['timestamp']),
            'customer_age_days' => $transaction['customer_age_days'],
            'transaction_count_24h' => $transaction['recent_transaction_count'],
            'avg_transaction_amount' => (float) $transaction['customer_avg_amount'],
        ];
    }
}

// ============================================================================
// Example 3: Local PyTorch Model Inference
// ============================================================================

/**
 * Inventory demand forecasting using local PyTorch models
 */
final readonly class DemandForecaster
{
    public function __construct(
        private InferenceEngineInterface $pytorchEngine,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Forecast demand for next 30 days
     */
    public function forecast(string $productId, array $historicalData): array
    {
        // Load locally-stored PyTorch model
        $model = $this->loadLocalModel('demand_forecast_lstm');
        
        // Prepare time series data
        $features = $this->prepareTimeSeries($historicalData);
        
        // Run inference
        $prediction = $this->pytorchEngine->predict($model, $features);
        
        return [
            'product_id' => $productId,
            'forecast_horizon_days' => 30,
            'predicted_demand' => $prediction['forecast'],
            'confidence_interval' => [
                'lower' => $prediction['lower_bound'],
                'upper' => $prediction['upper_bound'],
            ],
            'model_accuracy' => $prediction['historical_accuracy'],
        ];
    }
    
    private function loadLocalModel(string $name): ModelInterface
    {
        // Manual model construction for local files
        return new Model(
            name: $name,
            version: '1.0.0',
            format: ModelFormat::PYTORCH->value,
            path: storage_path("ml_models/{$name}.pt"),
            metadata: [
                'input_shape' => [30, 5],  // 30 days, 5 features
                'output_shape' => [30],     // 30-day forecast
            ]
        );
    }
    
    private function prepareTimeSeries(array $data): array
    {
        // Transform historical data into model input format
        return [
            'sales_history' => array_column($data, 'quantity'),
            'price_history' => array_column($data, 'price'),
            'promotion_flags' => array_column($data, 'is_promotion'),
            'day_of_week' => array_column($data, 'day_of_week'),
            'holiday_flags' => array_column($data, 'is_holiday'),
        ];
    }
}

// ============================================================================
// Example 4: Feature Versioning and Migration
// ============================================================================

/**
 * Feature version manager for handling schema evolution
 */
final readonly class FeatureSchemaManager
{
    public function __construct(
        private FeatureVersionManagerInterface $versionManager,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Migrate features from v1 to v2 schema
     */
    public function migrateFeatures(string $domain, array $featuresV1): array
    {
        $schemaV1 = $this->versionManager->getSchema($domain, 1);
        $schemaV2 = $this->versionManager->getSchema($domain, 2);
        
        $this->logger->info('Migrating features', [
            'domain' => $domain,
            'from_version' => 1,
            'to_version' => 2,
        ]);
        
        $featuresV2 = $featuresV1;
        
        // Add new required fields with defaults
        foreach ($schemaV2['fields'] as $field => $definition) {
            if ($definition['required'] && !isset($featuresV2[$field])) {
                $featuresV2[$field] = $this->getDefaultValue($definition['type']);
            }
        }
        
        // Remove deprecated fields
        $validFields = array_keys($schemaV2['fields']);
        $featuresV2 = array_intersect_key($featuresV2, array_flip($validFields));
        
        return $featuresV2;
    }
    
    /**
     * Register new schema version
     */
    public function registerNewVersion(string $domain, array $newSchema): void
    {
        $currentVersion = $this->versionManager->getLatestVersion($domain);
        $newVersion = $currentVersion + 1;
        
        // Validate backward compatibility
        if (!$this->isBackwardCompatible($domain, $currentVersion, $newSchema)) {
            throw new \RuntimeException("Schema v{$newVersion} breaks backward compatibility");
        }
        
        $this->versionManager->registerSchema($domain, $newVersion, $newSchema);
        
        $this->logger->info('Registered new feature schema', [
            'domain' => $domain,
            'version' => $newVersion,
        ]);
    }
    
    private function isBackwardCompatible(string $domain, int $oldVersion, array $newSchema): bool
    {
        $oldSchema = $this->versionManager->getSchema($domain, $oldVersion);
        
        // Check that all required fields from old schema still exist
        foreach ($oldSchema['fields'] as $field => $definition) {
            if ($definition['required'] && !isset($newSchema['fields'][$field])) {
                return false;  // Required field removed
            }
        }
        
        return true;
    }
    
    private function getDefaultValue(string $type): mixed
    {
        return match ($type) {
            'string' => '',
            'integer' => 0,
            'float' => 0.0,
            'boolean' => false,
            'array' => [],
            'object' => [],
            default => null,
        };
    }
}

// ============================================================================
// Example 5: Multi-Provider Anomaly Detection with Fallback
// ============================================================================

/**
 * Resilient anomaly detector with provider fallback chain
 */
final readonly class ResilientAnomalyDetector
{
    public function __construct(
        private AnomalyDetectionServiceInterface $mlService,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Detect anomalies with automatic provider fallback
     */
    public function detect(string $domain, array $features): AnomalyResultInterface
    {
        $providers = $this->getProviderFallbackChain($domain);
        $lastException = null;
        
        foreach ($providers as $provider) {
            try {
                $this->logger->debug("Attempting anomaly detection", [
                    'provider' => $provider,
                    'domain' => $domain,
                ]);
                
                $result = $this->mlService->detectAnomalies($domain, $features, [
                    'provider_override' => $provider,
                ]);
                
                $this->logger->info("Anomaly detection successful", [
                    'provider' => $provider,
                    'is_anomaly' => $result->isAnomaly(),
                    'confidence' => $result->getConfidence(),
                ]);
                
                return $result;
                
            } catch (\Throwable $e) {
                $this->logger->warning("Provider failed, trying next", [
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
                
                $lastException = $e;
                continue;
            }
        }
        
        throw new \RuntimeException(
            'All providers failed', 
            previous: $lastException
        );
    }
    
    private function getProviderFallbackChain(string $domain): array
    {
        // Domain-specific fallback chains
        return match ($domain) {
            'receivable', 'payable' => ['openai', 'anthropic', 'rulebased'],
            'procurement' => ['gemini', 'anthropic', 'rulebased'],
            default => ['rulebased'],
        };
    }
}

// ============================================================================
// Example 6: Custom Provider Implementation
// ============================================================================

/**
 * Custom rule-based provider for invoice validation
 */
final readonly class InvoiceRuleBasedProvider implements ProviderInterface
{
    private const Z_SCORE_THRESHOLD = 3.0;
    
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    public function getName(): string
    {
        return 'invoice_rulebased';
    }
    
    public function isAvailable(): bool
    {
        return true;  // Always available
    }
    
    public function evaluate(string $processContext, array $features): AnomalyResultInterface
    {
        $anomalies = [];
        
        // Rule 1: Amount significantly higher than customer average
        if (isset($features['total_amount'], $features['customer_history']['average_amount'])) {
            $zScore = $this->calculateZScore(
                $features['total_amount'],
                $features['customer_history']['average_amount']
            );
            
            if (abs($zScore) > self::Z_SCORE_THRESHOLD) {
                $anomalies[] = sprintf(
                    'Invoice amount %.2f is %.1f standard deviations from customer average',
                    $features['total_amount'],
                    $zScore
                );
            }
        }
        
        // Rule 2: Too many line items
        if (isset($features['line_items_count']) && $features['line_items_count'] > 100) {
            $anomalies[] = sprintf(
                'Unusually high number of line items: %d',
                $features['line_items_count']
            );
        }
        
        // Rule 3: Overdue customer creating new invoice
        if (isset($features['customer_history']['overdue_count']) 
            && $features['customer_history']['overdue_count'] > 3) {
            $anomalies[] = 'Customer has ' . $features['customer_history']['overdue_count'] . ' overdue invoices';
        }
        
        $isAnomaly = count($anomalies) > 0;
        $confidence = min(1.0, count($anomalies) * 0.3);
        
        return new AnomalyResult(
            isAnomaly: $isAnomaly,
            confidence: $confidence,
            reason: $isAnomaly ? implode('; ', $anomalies) : null,
            provider: $this->getName(),
            metadata: ['rules_triggered' => count($anomalies)]
        );
    }
    
    private function calculateZScore(float $value, float $mean): float
    {
        // Simplified Z-score (assumes std dev = 20% of mean)
        $stdDev = $mean * 0.2;
        
        return $stdDev > 0 ? ($value - $mean) / $stdDev : 0.0;
    }
}

// ============================================================================
// Example 7: Async Batch Processing with Queue
// ============================================================================

/**
 * Batch anomaly detection job (Laravel Queue)
 */
final class BatchAnomalyDetectionJob
{
    public function __construct(
        private readonly array $entityIds,
        private readonly string $domain
    ) {}
    
    public function handle(
        AnomalyDetectionServiceInterface $mlService,
        EntityRepositoryInterface $repository,
        FeatureExtractorInterface $extractor,
        LoggerInterface $logger
    ): void {
        $logger->info('Starting batch anomaly detection', [
            'domain' => $this->domain,
            'entity_count' => count($this->entityIds),
        ]);
        
        $results = [];
        
        foreach ($this->entityIds as $entityId) {
            $entity = $repository->findById($entityId);
            
            if (!$entity) {
                $logger->warning("Entity not found: {$entityId}");
                continue;
            }
            
            $features = $extractor->extract($entity);
            
            try {
                $result = $mlService->detectAnomalies($this->domain, $features);
                
                $results[$entityId] = [
                    'is_anomaly' => $result->isAnomaly(),
                    'confidence' => $result->getConfidence(),
                    'reason' => $result->getReason(),
                ];
                
                // Update entity status
                if ($result->isAnomaly() && $result->getConfidence() >= 0.85) {
                    $entity->flagForReview($result->getReason());
                }
                
            } catch (\Throwable $e) {
                $logger->error("Anomaly detection failed for {$entityId}", [
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Rate limiting
            usleep(100000);  // 100ms delay
        }
        
        $logger->info('Batch anomaly detection completed', [
            'total_processed' => count($results),
            'anomalies_detected' => count(array_filter($results, fn($r) => $r['is_anomaly'])),
        ]);
    }
}

// ============================================================================
// Usage Examples
// ============================================================================

/*

// === MLflow Model Loading ===

$fraudDetector = new MLflowFraudDetector($loader, $engine, $mlflow, $logger);

$result = $fraudDetector->predictFraud([
    'amount' => 9999.99,
    'merchant_category' => 'electronics',
    'timestamp' => time(),
    'customer_age_days' => 30,
    'recent_transaction_count' => 5,
    'customer_avg_amount' => 150.00,
]);

if ($result['is_fraud']) {
    blockTransaction($transactionId);
}

// === Feature Versioning ===

$schemaManager = new FeatureSchemaManager($versionManager, $logger);

// Migrate old features to new schema
$featuresV2 = $schemaManager->migrateFeatures('receivable', $oldFeatures);

// === Custom Provider ===

$customProvider = new InvoiceRuleBasedProvider($logger);
$result = $customProvider->evaluate('invoice_validation', $features);

// === Batch Processing ===

dispatch(new BatchAnomalyDetectionJob($invoiceIds, 'receivable'));

*/
