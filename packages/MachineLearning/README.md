# Nexus\MachineLearning

**Version:** 2.0.0 (formerly `Nexus\Intelligence`)  
**Status:** Production Ready

A framework-agnostic machine learning orchestration engine providing:
- **Anomaly Detection** via external AI providers (OpenAI, Anthropic, Gemini)
- **Local Model Inference** via PyTorch, ONNX, remote serving (MLflow, TensorFlow Serving)
- **MLflow Integration** for model registry and experiment tracking
- **Provider Strategy Pattern** for flexible AI/ML backend selection

## Purpose

The MachineLearning package enables domain packages (Receivable, Payable, Procurement, etc.) to:

1. **Detect Anomalies** using external AI services or local ML models
2. **Load and Execute Models** from MLflow registry or filesystem
3. **Track Experiments** with automated metric logging
4. **Manage Feature Schemas** with versioning and compatibility checking
5. **Optimize Costs** by selecting appropriate AI providers per domain

## Key Features

### v2.0 New Capabilities

**Provider Strategy Pattern:**
- Configure AI provider per domain (receivable, payable, procurement, etc.)
- Fallback chains for resilience (OpenAI → Anthropic → Rule-Based)
- Per-tenant API key configuration via settings

**External AI Providers:**
- **OpenAI Provider:** GPT-4 with fine-tuning support
- **Anthropic Provider:** Claude 3.5 Sonnet
- **Google Gemini Provider:** Gemini Pro
- **Rule-Based Provider:** Statistical fallback (Z-score anomaly detection)

**Inference Engines:**
- **PyTorch Engine:** Execute .pth/.pt models via Python subprocess
- **ONNX Engine:** Cross-platform .onnx models via onnxruntime
- **Remote API Engine:** MLflow Serving, TensorFlow Serving, TorchServe

**MLflow Integration:**
- Load models from MLflow model registry
- Automatic model versioning (production, staging, archived stages)
- Experiment tracking (metrics, parameters, artifacts)
- Model format auto-detection (PyTorch, ONNX, TensorFlow)

### Core Capabilities (v1.x + v2.0)

- **Anomaly Detection**: Synchronous evaluation (<200ms SLA) for real-time intervention
- **Feature Extraction**: Standardized interface for domain-specific features
- **Feature Versioning**: Schema-based compatibility checking
- **Usage Tracking**: Per-domain, per-tenant cost and token monitoring
- **Audit Logging**: Complete decision trail for compliance (GDPR Article 22)

## Architecture

### Package Structure (v2.0)

```
packages/MachineLearning/
├── src/
│   ├── Contracts/              # Public API (23 interfaces)
│   │   ├── AnomalyDetectionServiceInterface.php
│   │   ├── FeatureExtractorInterface.php
│   │   ├── FeatureVersionManagerInterface.php
│   │   ├── ProviderStrategyInterface.php
│   │   ├── HttpClientInterface.php
│   │   ├── ModelLoaderInterface.php
│   │   ├── InferenceEngineInterface.php
│   │   ├── ModelCacheInterface.php
│   │   ├── MLflowClientInterface.php
│   │   └── ...
│   ├── Core/                   # Internal engine
│   │   ├── Providers/          # AI Provider implementations
│   │   │   ├── OpenAIProvider.php
│   │   │   ├── AnthropicProvider.php
│   │   │   ├── GeminiProvider.php
│   │   │   └── RuleBasedProvider.php
│   │   └── Engines/            # Inference engines
│   │       ├── PyTorchInferenceEngine.php
│   │       ├── ONNXInferenceEngine.php
│   │       └── RemoteAPIInferenceEngine.php
│   ├── Services/               # Orchestrators
│   │   ├── MLModelManager.php
│   │   ├── FeatureVersionManager.php
│   │   ├── DomainProviderStrategy.php
│   │   ├── MLflowClient.php
│   │   └── MLflowModelLoader.php
│   ├── ValueObjects/           # Immutable DTOs
│   │   ├── FeatureSet.php
│   │   ├── AnomalyResult.php
│   │   ├── UsageMetrics.php
│   │   ├── ProviderConfig.php
│   │   └── Model.php
│   ├── Enums/                  # PHP 8.3 native enums
│   │   ├── TaskType.php
│   │   ├── SeverityLevel.php
│   │   └── ModelProvider.php
```

## Installation

```bash
composer require nexus/machinelearning:"^2.0"
```

**Requirements:**
- PHP 8.3+
- For local inference: Python 3.8+, PyTorch or ONNX Runtime
- For MLflow: MLflow server accessible via HTTP

## Quick Start

### 1. Configure Provider Strategy

```php
// In your Laravel/Symfony service provider
use Nexus\MachineLearning\Contracts\ProviderStrategyInterface;
use Nexus\MachineLearning\Services\DomainProviderStrategy;
use Nexus\MachineLearning\Contracts\SettingsManagerInterface;

$this->app->singleton(ProviderStrategyInterface::class, function ($app) {
    return new DomainProviderStrategy(
        $app->make(SettingsManagerInterface::class)
    );
});

// Configure via settings
$settings->set('machinelearning.providers.receivable', [
    'primary' => 'openai',
    'fallback' => ['anthropic', 'rule_based'],
    'api_keys' => [
        'openai' => env('OPENAI_API_KEY'),
        'anthropic' => env('ANTHROPIC_API_KEY'),
    ],
    'parameters' => [
        'openai' => ['model' => 'gpt-4', 'temperature' => 0.1],
    ],
]);
```

### 2. Define Feature Extractor (in Domain Package)

```php
// In packages/Receivable/src/MachineLearning/InvoiceAnomalyExtractor.php
use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;
use Nexus\MachineLearning\ValueObjects\FeatureSet;

final readonly class InvoiceAnomalyExtractor implements FeatureExtractorInterface
{
    public function __construct(
        private HistoricalDataRepositoryInterface $historicalRepo
    ) {}

    public function extract(object $invoice): FeatureSet
    {
        $avgAmount = $this->historicalRepo->getAverageInvoiceAmount(
            $invoice->getCustomerId()
        );
        
        return new FeatureSet(
            features: [
                'amount' => (float) $invoice->getAmount()->getValue(),
                'historical_avg_amount' => (float) $avgAmount,
                'amount_ratio' => $invoice->getAmount()->getValue() / max(1, $avgAmount),
                'days_until_due' => $invoice->getDueDate()->diff(new \DateTimeImmutable())->days,
            ],
            schemaVersion: '1.0',
            metadata: ['customer_id' => $invoice->getCustomerId()]
        );
    }

    public function getFeatureKeys(): array
    {
        return ['amount', 'historical_avg_amount', 'amount_ratio', 'days_until_due'];
    }

    public function getSchemaVersion(): string
    {
        return '1.0';
    }
}
```

### 3. Use in Event Listener

```php
// In packages/Receivable/src/Listeners/InvoiceCreatingListener.php
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\MachineLearning\Exceptions\AllProvidersUnavailableException;

final readonly class InvoiceCreatingListener
{
    public function __construct(
        private AnomalyDetectionServiceInterface $mlService,
        private InvoiceAnomalyExtractor $extractor
    ) {}

    public function handle(InvoiceCreatingEvent $event): void
    {
        try {
            $features = $this->extractor->extract($event->invoice);
            $result = $this->mlService->detectAnomalies('receivable', $features);
            
            if ($result->isAnomaly() && $result->getConfidence() >= 0.85) {
                throw new AnomalyDetectedException(
                    "Invoice blocked: {$result->getReason()}. " .
                    "Confidence: {$result->getConfidence()}"
                );
            }
        } catch (AllProvidersUnavailableException $e) {
            // Fail gracefully - allow invoice creation
            Log::warning('ML providers unavailable, skipping anomaly check');
        }
    }
}
```

## Usage Examples

### Using External AI Providers

```php
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;

$mlService = app(AnomalyDetectionServiceInterface::class);

// Detect anomalies using configured provider (OpenAI, Anthropic, etc.)
$result = $mlService->detectAnomalies('receivable', $features);

if ($result->isAnomaly()) {
    echo "Anomaly detected: " . $result->getReason();
    echo "\nConfidence: " . $result->getConfidence();
    echo "\nSeverity: " . $result->getSeverity()->value;
}
```

### Using Local ML Models with MLflow

```php
use Nexus\MachineLearning\Contracts\ModelLoaderInterface;
use Nexus\MachineLearning\Contracts\InferenceEngineInterface;

$loader = app(ModelLoaderInterface::class);
$engine = app(InferenceEngineInterface::class);

// Load model from MLflow registry (production stage)
$model = $loader->load('invoice_anomaly_detector', version: null, stage: 'production');

// Run inference
$prediction = $engine->predict($model, [
    'amount' => 10000.0,
    'historical_avg_amount' => 5000.0,
    'amount_ratio' => 2.0,
    'days_until_due' => 30,
]);

// Batch predictions
$predictions = $engine->batchPredict($model, $batchData);
```

### Fine-Tuning OpenAI Models

```php
use Nexus\MachineLearning\Core\Providers\OpenAIProvider;
use Nexus\MachineLearning\ValueObjects\ProviderConfig;

$provider = new OpenAIProvider(
    httpClient: $httpClient,
    config: ProviderConfig::create(
        name: 'openai',
        apiKey: env('OPENAI_API_KEY'),
        parameters: ['model' => 'gpt-4']
    ),
    logger: $logger
);

// Submit fine-tuning job
$jobId = $provider->submitFineTuningJob(
    trainingFileId: 'file-xyz123',
    modelName: 'gpt-4',
    suffix: 'receivable-anomaly'
);
```

### Managing Feature Schemas

```php
use Nexus\MachineLearning\Contracts\FeatureVersionManagerInterface;

$versionManager = app(FeatureVersionManagerInterface::class);

// Register feature schema
$versionManager->registerSchema('1.0', [
    'amount' => 'float',
    'historical_avg_amount' => 'float',
    'amount_ratio' => 'float',
    'days_until_due' => 'int',
]);

// Validate compatibility
if (!$versionManager->isCompatible('1.0', $features->getFeatureKeys())) {
    throw new FeatureVersionMismatchException('Feature schema incompatible');
}
```

## Configuration

### Provider Configuration (via Settings)

```php
// Configure OpenAI provider for receivable domain
$settings->set('machinelearning.providers.receivable', [
    'primary' => 'openai',
    'fallback' => ['anthropic', 'gemini', 'rule_based'],
    'api_keys' => [
        'openai' => env('OPENAI_API_KEY'),
        'anthropic' => env('ANTHROPIC_API_KEY'),
        'gemini' => env('GEMINI_API_KEY'),
    ],
    'parameters' => [
        'openai' => [
            'model' => 'gpt-4',
            'temperature' => 0.1,
            'max_tokens' => 1000,
        ],
        'anthropic' => [
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 1000,
        ],
        'gemini' => [
            'model' => 'gemini-pro',
            'temperature' => 0.1,
        ],
    ],
]);

// MLflow configuration
$settings->set('machinelearning.mlflow.tracking_uri', 'http://localhost:5000');
$settings->set('machinelearning.inference.timeout', 30);

// Feature schema storage
$settings->set('machinelearning.feature_schema.v1', [
    'amount' => 'float',
    'historical_avg_amount' => 'float',
    'amount_ratio' => 'float',
    'days_until_due' => 'int',
]);
```

## Integration with Nexus Packages

### Dependencies
- **Nexus\Setting**: Configuration and feature schema storage
- **Nexus\AuditLogger**: Decision audit trail (GDPR compliance)
- **Nexus\Storage**: Model artifact storage (for MLflow downloads)
- **Nexus\Crypto** (optional): API key encryption
- **Nexus\Monitoring** (optional): Metrics tracking

### Domain Package Integration
- **Nexus\Receivable**: Invoice anomaly detection (InvoiceAnomalyExtractor)
- **Nexus\Payable**: Vendor bill validation (VendorBillExtractor)
- **Nexus\Procurement**: PO quantity anomaly detection (ProcurementPOQtyExtractor)
- **Nexus\Sales**: Sales forecast prediction (SalesOpportunityExtractor)
- **Nexus\Inventory**: Stock level anomaly detection (StockLevelExtractor)

## Migration from v1.x

**See:** `docs/MIGRATION_INTELLIGENCE_TO_MACHINELEARNING.md`

**Summary of Breaking Changes:**
- Package namespace: `Nexus\Intelligence` → `Nexus\MachineLearning`
- Composer package: `nexus/intelligence` → `nexus/machinelearning`
- Service names: `IntelligenceManager` → `MLModelManager`, `SchemaVersionManager` → `FeatureVersionManager`
- Configuration keys: `intelligence.schema.*` → `machinelearning.feature_schema.*`

**Migration effort:** Medium (namespace updates, service bindings, configuration migration)

## Testing

```bash
# Run package tests
./vendor/bin/phpunit packages/MachineLearning/tests

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage packages/MachineLearning/tests
```

## License

MIT License - See LICENSE file for details.

---

**Package Version:** 2.0.0  
**Last Updated:** November 2025  
**Maintainer:** Nexus Architecture Team


# Get review queue
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

---

## 📖 Documentation

Comprehensive documentation is available in the `docs/` folder:

### Getting Started
- **[Getting Started Guide](docs/getting-started.md)** - Quick start, prerequisites, basic configuration
- **[Migration from v1.x](MIGRATION_INTELLIGENCE_TO_MACHINELEARNING.md)** - Upgrade guide for existing users

### Technical Reference
- **[API Reference](docs/api-reference.md)** - Complete interface and method documentation
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Requirements](REQUIREMENTS.md)** - Detailed requirements specification (52 requirements)

### Code Examples
- **[Basic Usage](docs/examples/basic-usage.php)** - Simple anomaly detection examples
- **[Advanced Usage](docs/examples/advanced-usage.php)** - MLflow, custom providers, batch processing

### Project Documentation
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Development progress, metrics, decisions
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - Test coverage, results, strategy
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package value assessment and ROI analysis

---

## License

MIT License - see LICENSE file for details.

## Author

Azahari Zaman
