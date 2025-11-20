# Nexus\Intelligence

A framework-agnostic AI orchestration engine providing resilient anomaly detection and predictive analytics for ERP systems.

## Purpose

The Intelligence package enables domain packages (Procurement, Sales, Finance) to leverage external AI services (OpenAI, Anthropic, Gemini) for real-time anomaly detection and predictive analytics while maintaining strict architectural decoupling and enterprise-grade reliability.

## Key Features

### Core Capabilities
- **Anomaly Detection**: Synchronous evaluation (<200ms SLA) for real-time process intervention
- **Predictive Analytics**: Asynchronous forecasting and classification
- **Feature Extraction**: Standardized interface for domain-specific feature engineering
- **Provider Agnosticism**: Swappable AI providers (OpenAI, Anthropic, Gemini)

### Enterprise Features
- **Circuit Breaker Fallback**: Automatic degradation to rule-based engine on provider failure
- **Feature Versioning**: Schema-based compatibility checking for model evolution
- **Training Data Collection**: GDPR-compliant supervised learning dataset building
- **Granular Cost Tracking**: Per-tenant, per-model, per-domain usage monitoring
- **Model Drift Detection**: Automated 30-day rolling accuracy monitoring
- **Audit Logging**: Complete decision trail for regulatory compliance (GDPR Article 22)
- **Explainable AI**: Feature importance scores (SHAP values) for transparency

### Advanced Features
- **A/B Testing**: Multi-model comparison with statistical significance testing
- **Human-in-the-Loop**: Review queue for low-confidence predictions
- **Tenant-Specific Fine-Tuning**: Custom model training with tenant data
- **Automated Confidence Calibration**: Monthly isotonic regression on historical outcomes
- **Adversarial Robustness Testing**: Quarterly vulnerability assessment
- **Blue-Green Model Deployments**: Atomic version promotion with auto-rollback
- **Cost Optimization**: Automated recommendations for cheaper model alternatives

## Architecture

### Package Structure

```
packages/Intelligence/
├── src/
│   ├── Contracts/              # Public API (30 interfaces)
│   │   ├── FeatureExtractorInterface.php
│   │   ├── FeatureSetInterface.php
│   │   ├── AnomalyDetectionServiceInterface.php
│   │   ├── PredictionServiceInterface.php
│   │   ├── ModelRepositoryInterface.php
│   │   ├── ReviewQueueInterface.php
│   │   ├── ModelHealthMonitorInterface.php
│   │   ├── ConfidenceCalibratorInterface.php
│   │   ├── ModelRetrainingInterface.php
│   │   ├── AdversarialTestingInterface.php
│   │   ├── ModelVersionManagerInterface.php
│   │   ├── CostOptimizerInterface.php
│   │   └── ...
│   ├── Core/                   # Internal engine
│   │   ├── Engine/
│   │   │   ├── PromptEngine.php
│   │   │   ├── ResponseParser.php
│   │   │   ├── QuotaTracker.php
│   │   │   ├── ConfidenceCalibrator.php
│   │   │   └── AdversarialGenerator.php
│   │   ├── Adapters/
│   │   │   ├── OpenAIAdapter.php
│   │   │   ├── AnthropicAdapter.php
│   │   │   ├── GeminiAdapter.php
│   │   │   └── RuleBasedAnomalyEngine.php
│   │   └── Contracts/
│   │       └── ProviderInterface.php
│   ├── Services/               # Orchestrators (10 services)
│   │   ├── IntelligenceManager.php
│   │   ├── ModelHealthMonitor.php
│   │   ├── ModelComparisonService.php
│   │   ├── ModelTrainingService.php
│   │   ├── PredictionService.php
│   │   ├── ReviewQueueService.php
│   │   ├── ConfidenceCalibrationService.php
│   │   ├── ModelRetrainingService.php
│   │   ├── ModelVersionManager.php
│   │   ├── AdversarialTestingService.php
│   │   └── CostOptimizerService.php
│   ├── ValueObjects/           # Immutable DTOs (20 VOs)
│   │   ├── FeatureSet.php
│   │   ├── AnomalyResult.php
│   │   ├── PredictionResult.php
│   │   ├── ModelVersion.php
│   │   ├── AdversarialTestResult.php
│   │   ├── CostRecommendation.php
│   │   └── ...
│   ├── Enums/                  # PHP 8.3 native enums
│   │   ├── ModelProvider.php
│   │   ├── TaskType.php
│   │   ├── SeverityLevel.php
│   │   ├── ModelHealthStatus.php
│   │   ├── ReviewStatus.php
│   │   ├── DeploymentStrategy.php
│   │   └── CostOptimizationAction.php
│   └── Exceptions/             # Domain errors (16 exceptions)
│       ├── IntelligenceException.php
│       ├── FeatureVersionMismatchException.php
│       ├── QuotaExceededException.php
│       ├── AdversarialAttackDetectedException.php
│       └── ...
```

## Usage Example

### 1. Define Feature Extractor (in Domain Package)

```php
// In packages/Procurement/src/Intelligence/ProcurementPOQtyExtractor.php
use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Nexus\Intelligence\Contracts\FeatureSetInterface;
use Nexus\Intelligence\ValueObjects\FeatureSet;

final class ProcurementPOQtyExtractor implements FeatureExtractorInterface
{
    public function __construct(
        private readonly HistoricalDataRepositoryInterface $historicalRepo
    ) {}

    public function extract(object $poLine): FeatureSetInterface
    {
        $currentQty = $poLine->getQuantity()->getValue();
        $avgQty = $this->historicalRepo->getAverageQty($poLine->getProductId());
        
        return new FeatureSet(
            features: [
                'quantity_ordered' => (float) $currentQty,
                'historical_avg_qty' => (float) $avgQty,
                'qty_ratio_to_avg' => $currentQty / max(1, $avgQty),
                'vendor_transaction_count' => $this->historicalRepo->getTransactionCountByVendor($poLine->getVendorPartyId()),
            ],
            schemaVersion: '1.0',
            metadata: ['entity_type' => 'purchase_order_line']
        );
    }

    public function getFeatureKeys(): array
    {
        return ['quantity_ordered', 'historical_avg_qty', 'qty_ratio_to_avg', 'vendor_transaction_count'];
    }

    public function getSchemaVersion(): string
    {
        return '1.0';
    }
}
```

### 2. Use in Event Listener

```php
// In packages/Procurement/src/Listeners/PurchaseOrderLineCreatingListener.php
use Nexus\Intelligence\Contracts\AnomalyDetectionServiceInterface;

final class PurchaseOrderLineCreatingListener
{
    public function __construct(
        private readonly AnomalyDetectionServiceInterface $intelligence,
        private readonly ProcurementPOQtyExtractor $extractor
    ) {}

    public function handle(PurchaseOrderLineCreatingEvent $event): void
    {
        $features = $this->extractor->extract($event->poLine);
        $result = $this->intelligence->evaluate('procurement_po_qty_check', $features);
        
        if ($result->requiresHumanReview()) {
            // Create PO with "pending_ai_review" status
            $event->poLine->markForReview($result->getReason());
            return;
        }
        
        if ($result->isFlagged() 
            && $result->getSeverity()->value >= SeverityLevel::CRITICAL->value 
            && $result->getCalibratedConfidence() >= 0.85
        ) {
            throw new AnomalyDetectedException(
                "Purchase blocked by AI: {$result->getReason()}. " .
                "Top factors: " . implode(', ', array_keys(array_slice($result->getFeatureImportance(), 0, 3))) .
                ". Confidence: {$result->getCalibratedConfidence()}"
            );
        }
    }
}
```

## Configuration

### Model Configuration (via Atomy)

```php
// Database seeder or admin UI
IntelligenceModel::create([
    'tenant_id' => $tenant->id,
    'name' => 'procurement_po_qty_check',
    'type' => 'anomaly_detection',
    'provider' => ModelProvider::OPENAI,
    'endpoint_url' => 'https://api.openai.com/v1/chat/completions',
    'expected_feature_version' => '1.0',
    'drift_threshold' => 0.15,
    'ab_test_enabled' => true,
    'ab_test_model_b' => 'anthropic_claude',
    'ab_test_weight' => 0.5,
    'calibration_enabled' => true,
    'adversarial_testing_enabled' => true,
]);
```

### Settings (via Nexus\Setting)

```php
// API keys (encrypted via Nexus\Crypto)
$crypto->encryptWithKey($apiKey, "tenant-{$tenantId}-intelligence-openai");

// Feature flags
intelligence.training_consent = true
intelligence.training_retention_days = 365
intelligence.ab_testing.enabled = true
intelligence.cost_optimization.enabled = true
```

## Integration with Nexus Packages

### Dependencies
- **Nexus\Connector**: HTTP resilience (circuit breaker, retry, rate limiting)
- **Nexus\Crypto**: API key encryption
- **Nexus\Setting**: Configuration management
- **Nexus\AuditLogger**: Decision audit trail (GDPR compliance)

### Domain Package Integration
- **Nexus\Procurement**: PO quantity anomaly detection
- **Nexus\Sales**: Sales forecast prediction
- **Nexus\Finance**: Unusual transaction detection
- **Nexus\Payable**: Duplicate payment detection

## Scheduled Commands

```bash
# Daily health monitoring
php artisan intelligence:monitor-health

# Monthly confidence calibration
php artisan intelligence:calibrate-confidence

# Quarterly adversarial testing
php artisan intelligence:test-adversarial

# Monthly cost optimization
php artisan intelligence:optimize-costs

# Weekly training data cleanup (respects retention policies)
php artisan intelligence:cleanup-training-data

# Hourly rollback check (for new deployments)
php artisan intelligence:check-rollback

# Manual version promotion
php artisan intelligence:version-promote {model} {version}
```

## GraphQL API (via Atomy)

```graphql
# Get decision explanation
query {
  intelligenceDecisionExplanation(predictionId: "abc123") {
    featureImportance
    reason
    modelVersion
    confidence
    calibratedConfidence
    reviewStatus
  }
}

# Get review queue
query {
  intelligenceReviewQueue(status: PENDING) {
    id
    processContext
    aiDecision
    entitySnapshot
    assignedTo
  }
}

# Get A/B test report
query {
  intelligenceABTestReport(
    modelA: "openai_gpt4"
    modelB: "anthropic_claude"
    since: "2025-01-01"
  ) {
    modelACorrect
    modelBCorrect
    significanceLevel
    isStatisticallySignificant
  }
}

# Get cost recommendations
query {
  intelligenceCostRecommendations(status: PENDING) {
    modelName
    recommendedModel
    estimatedSavings
    accuracyImpact
    rationale
  }
}
```

## Security & Compliance

### GDPR Article 22 Compliance
- All AI decisions logged to `Nexus\AuditLogger`
- Feature hash stored for reproducibility
- Human review option for contestation
- Training data collection requires explicit consent

### Data Retention
- Training data respects tenant-specific retention policies
- Automatic cleanup via scheduled command
- Anonymization options for sensitive features

### Adversarial Protection
- Quarterly robustness testing
- Real-time adversarial input detection
- Automatic blocking on detection

## Performance

### Synchronous Anomaly Detection
- **SLA**: <200ms response time
- **Fallback**: Rule-based engine on circuit breaker open
- **Resilience**: Circuit breaker, retry, rate limiting via Nexus\Connector

### Asynchronous Prediction
- **Execution**: Laravel queue jobs
- **Tracking**: Job ID for status polling
- **Results**: Stored in `intelligence_predictions` table

## Cost Management

### Granular Tracking
- Per-tenant cost tracking
- Per-model cost tracking
- Per-domain context tracking
- Monthly aggregation for chargeback

### Optimization
- Automated recommendations for cheaper models
- Accuracy impact analysis
- Manual approval workflow

## Model Lifecycle

### Training & Fine-Tuning
1. Collect training data (requires consent)
2. Validate minimum 1000 examples
3. Submit fine-tuning job to provider
4. Store custom endpoint per tenant
5. Automatic usage on subsequent requests

### Versioning & Deployment
1. Create new model version
2. Deploy via blue-green strategy
3. Monitor accuracy for 24 hours
4. Auto-rollback if accuracy drops >10%
5. Promote to active if successful

### Health Monitoring
1. Daily health check
2. 30-day rolling accuracy calculation
3. Drift detection (>15% threshold)
4. Automatic retraining request creation
5. Manual approval workflow

## License

MIT License - see LICENSE file for details.

## Author

Azahari Zaman
