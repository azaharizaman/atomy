# API Reference: MachineLearning

**Version:** 2.0.0  
**Package:** `nexus/machinelearning`  
**Namespace:** `Nexus\MachineLearning`

---

## Table of Contents

1. [Anomaly Detection](#anomaly-detection)
2. [Provider Strategy](#provider-strategy)
3. [Feature Management](#feature-management)
4. [Model Inference](#model-inference)
5. [MLflow Integration](#mlflow-integration)
6. [Value Objects](#value-objects)
7. [Enums](#enums)
8. [Exceptions](#exceptions)

---

## Anomaly Detection

### AnomalyDetectionServiceInterface

Main service for detecting anomalies in business processes.

```php
namespace Nexus\MachineLearning\Contracts;

interface AnomalyDetectionServiceInterface
{
    /**
     * Detect anomalies in provided features
     * 
     * @param string $domain Domain context (e.g., 'receivable', 'payable')
     * @param array $features Extracted feature data
     * @param array $options Optional configuration overrides
     * 
     * @return AnomalyResultInterface Detection result with confidence
     * 
     * @throws FeatureVersionMismatchException If feature schema incompatible
     * @throws ProviderNotFoundException If no provider configured
     * @throws QuotaExceededException If API rate limit exceeded
     */
    public function detectAnomalies(
        string $domain, 
        array $features, 
        array $options = []
    ): AnomalyResultInterface;
    
    /**
     * Batch anomaly detection for multiple entities
     * 
     * @param string $domain Domain context
     * @param array[] $featuresBatch Array of feature sets
     * @param array $options Optional configuration
     * 
     * @return AnomalyResultInterface[] Array of results
     */
    public function detectAnomaliesBatch(
        string $domain, 
        array $featuresBatch, 
        array $options = []
    ): array;
}
```

**Usage Example:**

```php
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;

final readonly class InvoiceValidator
{
    public function __construct(
        private AnomalyDetectionServiceInterface $mlService
    ) {}
    
    public function validate(Invoice $invoice): void
    {
        $features = [
            'invoice_number' => $invoice->number,
            'total_amount' => (float) $invoice->total_amount,
            'customer_id' => $invoice->customer_id,
            'line_items_count' => $invoice->lineItems->count(),
        ];
        
        $result = $this->mlService->detectAnomalies('receivable', $features);
        
        if ($result->isAnomaly() && $result->getConfidence() >= 0.85) {
            throw new InvoiceAnomalyException($result->getReason());
        }
    }
}
```

---

### AnomalyResultInterface

Result object containing anomaly detection outcome.

```php
namespace Nexus\MachineLearning\Contracts;

interface AnomalyResultInterface
{
    /**
     * Check if anomaly was detected
     * 
     * @return bool True if anomaly detected
     */
    public function isAnomaly(): bool;
    
    /**
     * Get confidence score (0.0 to 1.0)
     * 
     * @return float Confidence level
     */
    public function getConfidence(): float;
    
    /**
     * Get human-readable reason for anomaly
     * 
     * @return string|null Explanation text
     */
    public function getReason(): ?string;
    
    /**
     * Get provider that generated this result
     * 
     * @return string Provider name (e.g., 'openai', 'anthropic')
     */
    public function getProvider(): string;
    
    /**
     * Get raw provider response data
     * 
     * @return array Additional metadata
     */
    public function getMetadata(): array;
}
```

---

## Provider Strategy

### ProviderStrategyInterface

Strategy for selecting ML providers based on domain and task type.

```php
namespace Nexus\MachineLearning\Contracts;

interface ProviderStrategyInterface
{
    /**
     * Select an appropriate provider for the given context
     * 
     * @param string $domain The domain requiring ML inference (e.g., 'procurement')
     * @param string $taskType The type of ML task (e.g., 'anomaly_detection')
     * @param string $tenantId The tenant identifier
     * 
     * @return ProviderInterface The selected provider instance
     * 
     * @throws ProviderNotFoundException If no provider configured
     */
    public function selectProvider(
        string $domain, 
        string $taskType, 
        string $tenantId
    ): ProviderInterface;
    
    /**
     * Get the provider selection priority for a given domain and task
     * 
     * @param string $domain The domain name
     * @param string $taskType The task type
     * 
     * @return string[] Array of provider names in priority order
     */
    public function getProviderPriority(string $domain, string $taskType): array;
}
```

**Implementation Example:**

```php
use Nexus\MachineLearning\Contracts\ProviderStrategyInterface;
use Nexus\MachineLearning\Core\Contracts\ProviderInterface;

final readonly class ConfigBasedProviderStrategy implements ProviderStrategyInterface
{
    public function __construct(
        private array $providerConfig,
        private ProviderFactoryInterface $providerFactory
    ) {}
    
    public function selectProvider(
        string $domain, 
        string $taskType, 
        string $tenantId
    ): ProviderInterface {
        $providerName = $this->providerConfig[$domain] 
            ?? $this->providerConfig['default'] 
            ?? 'rulebased';
        
        return $this->providerFactory->create($providerName);
    }
    
    public function getProviderPriority(string $domain, string $taskType): array
    {
        return [
            $this->providerConfig[$domain] ?? 'rulebased',
            'rulebased',  // Always fallback to rule-based
        ];
    }
}
```

---

### ProviderInterface

Base interface for all ML providers.

```php
namespace Nexus\MachineLearning\Core\Contracts;

interface ProviderInterface
{
    /**
     * Get provider name
     * 
     * @return string Provider identifier
     */
    public function getName(): string;
    
    /**
     * Check if provider is available (API key set, service reachable)
     * 
     * @return bool True if provider can be used
     */
    public function isAvailable(): bool;
    
    /**
     * Evaluate anomaly for given features
     * 
     * @param string $processContext Context description
     * @param array $features Feature data
     * 
     * @return AnomalyResultInterface Detection result
     * 
     * @throws ProviderUnavailableException If provider cannot be reached
     */
    public function evaluate(string $processContext, array $features): AnomalyResultInterface;
}
```

---

## Feature Management

### FeatureExtractorInterface

Extract features from domain entities.

```php
namespace Nexus\MachineLearning\Contracts;

interface FeatureExtractorInterface
{
    /**
     * Extract features from domain entity
     * 
     * @param mixed $entity Domain entity (Invoice, PurchaseOrder, etc.)
     * 
     * @return array Feature data as associative array
     */
    public function extract(mixed $entity): array;
    
    /**
     * Get feature schema definition
     * 
     * @return array Schema with version and field definitions
     */
    public function getFeatureSchema(): array;
}
```

**Implementation Example:**

```php
use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;

final class InvoiceAnomalyExtractor implements FeatureExtractorInterface
{
    public function extract(mixed $entity): array
    {
        assert($entity instanceof Invoice);
        
        return [
            'invoice_number' => $entity->number,
            'total_amount' => (float) $entity->total_amount,
            'currency' => $entity->currency,
            'line_items_count' => $entity->lineItems->count(),
            'customer_history' => [
                'total_invoices' => $entity->customer->invoices()->count(),
                'average_amount' => $entity->customer->invoices()->avg('total_amount'),
            ],
        ];
    }
    
    public function getFeatureSchema(): array
    {
        return [
            'version' => 1,
            'fields' => [
                'invoice_number' => ['type' => 'string', 'required' => true],
                'total_amount' => ['type' => 'float', 'required' => true],
                'currency' => ['type' => 'string', 'required' => true],
                'line_items_count' => ['type' => 'integer', 'required' => true],
                'customer_history' => [
                    'type' => 'object',
                    'required' => true,
                    'fields' => [
                        'total_invoices' => ['type' => 'integer', 'required' => true],
                        'average_amount' => ['type' => 'float', 'required' => true],
                    ],
                ],
            ],
        ];
    }
}
```

---

### FeatureVersionManagerInterface

Manage feature schema versioning and compatibility.

```php
namespace Nexus\MachineLearning\Contracts;

interface FeatureVersionManagerInterface
{
    /**
     * Register a feature schema version
     * 
     * @param string $domain Domain name
     * @param int $version Schema version
     * @param array $schema Schema definition
     * 
     * @throws SchemaAlreadyExistsException If version already registered
     */
    public function registerSchema(string $domain, int $version, array $schema): void;
    
    /**
     * Check if a feature set is compatible with current schema
     * 
     * @param string $domain Domain name
     * @param array $features Feature data
     * 
     * @return bool True if compatible
     */
    public function isCompatible(string $domain, array $features): bool;
    
    /**
     * Get latest schema version for domain
     * 
     * @param string $domain Domain name
     * 
     * @return int Latest version number
     * 
     * @throws SchemaNotFoundException If no schema exists
     */
    public function getLatestVersion(string $domain): int;
    
    /**
     * Get schema definition for specific version
     * 
     * @param string $domain Domain name
     * @param int $version Version number
     * 
     * @return array Schema definition
     * 
     * @throws SchemaNotFoundException If version not found
     */
    public function getSchema(string $domain, int $version): array;
}
```

---

## Model Inference

### ModelLoaderInterface

Load ML models from various sources.

```php
namespace Nexus\MachineLearning\Contracts;

interface ModelLoaderInterface
{
    /**
     * Load a model by name and version
     * 
     * @param string $modelName The model identifier
     * @param string|null $version The model version (null for latest)
     * @param string|null $stage MLflow stage filter ('production', 'staging', etc.)
     * 
     * @return ModelInterface Loaded model with metadata
     * 
     * @throws ModelNotFoundException If model doesn't exist
     * @throws ModelLoadException If loading fails
     */
    public function load(
        string $modelName, 
        ?string $version = null,
        ?string $stage = null
    ): ModelInterface;
    
    /**
     * Check if a model exists
     * 
     * @param string $modelName The model identifier
     * @param string|null $version The model version (null for latest)
     * 
     * @return bool True if model exists
     */
    public function exists(string $modelName, ?string $version = null): bool;
    
    /**
     * List all available models
     * 
     * @return array Array of model names
     */
    public function listModels(): array;
}
```

---

### InferenceEngineInterface

Execute predictions on loaded models.

```php
namespace Nexus\MachineLearning\Contracts;

interface InferenceEngineInterface
{
    /**
     * Execute prediction on a single input
     * 
     * @param ModelInterface $model The loaded model to use for inference
     * @param array<string, mixed> $input Input features as key-value pairs
     * 
     * @return array<string, mixed> Prediction result with confidence scores
     * 
     * @throws InferenceException If prediction fails
     * @throws InferenceTimeoutException If execution exceeds timeout
     */
    public function predict(ModelInterface $model, array $input): array;
    
    /**
     * Execute batch prediction on multiple inputs
     * 
     * @param ModelInterface $model The loaded model
     * @param array<array<string, mixed>> $inputs Array of input feature sets
     * 
     * @return array<array<string, mixed>> Array of prediction results
     * 
     * @throws InferenceException If prediction fails
     */
    public function predictBatch(ModelInterface $model, array $inputs): array;
    
    /**
     * Get supported model formats for this engine
     * 
     * @return string[] Array of supported formats (e.g., ['pytorch', 'onnx'])
     */
    public function getSupportedFormats(): array;
}
```

**Usage Example:**

```php
use Nexus\MachineLearning\Contracts\ModelLoaderInterface;
use Nexus\MachineLearning\Contracts\InferenceEngineInterface;

final readonly class FraudDetectionService
{
    public function __construct(
        private ModelLoaderInterface $loader,
        private InferenceEngineInterface $engine
    ) {}
    
    public function predictFraud(array $transactionData): array
    {
        $model = $this->loader->load('fraud_detection', stage: 'production');
        
        $prediction = $this->engine->predict($model, $transactionData);
        
        return [
            'is_fraud' => $prediction['class'] === 'fraud',
            'confidence' => $prediction['confidence'],
            'risk_score' => $prediction['score'],
        ];
    }
}
```

---

## MLflow Integration

### MLflowClientInterface

Interface to MLflow tracking and registry.

```php
namespace Nexus\MachineLearning\Contracts;

interface MLflowClientInterface
{
    /**
     * Get model from registry by name and stage
     * 
     * @param string $modelName Model name in registry
     * @param string $stage Stage filter ('production', 'staging', etc.)
     * 
     * @return ModelInterface Model metadata
     * 
     * @throws ModelNotFoundException If model not found
     */
    public function getModel(string $modelName, string $stage = 'production'): ModelInterface;
    
    /**
     * Log metrics to MLflow experiment
     * 
     * @param string $runId MLflow run ID
     * @param array $metrics Metrics to log (key => value)
     * 
     * @throws MLflowException If logging fails
     */
    public function logMetrics(string $runId, array $metrics): void;
    
    /**
     * Log parameters to MLflow experiment
     * 
     * @param string $runId MLflow run ID
     * @param array $params Parameters to log (key => value)
     * 
     * @throws MLflowException If logging fails
     */
    public function logParams(string $runId, array $params): void;
    
    /**
     * Start a new MLflow run
     * 
     * @param string $experimentName Experiment name
     * 
     * @return string Run ID
     * 
     * @throws MLflowException If run creation fails
     */
    public function startRun(string $experimentName): string;
    
    /**
     * End an MLflow run
     * 
     * @param string $runId Run ID to end
     * @param string $status Run status ('FINISHED', 'FAILED', etc.)
     * 
     * @throws MLflowException If ending run fails
     */
    public function endRun(string $runId, string $status = 'FINISHED'): void;
}
```

**Usage Example:**

```php
use Nexus\MachineLearning\Contracts\MLflowClientInterface;

final readonly class ModelTrainingService
{
    public function __construct(
        private MLflowClientInterface $mlflow
    ) {}
    
    public function trainAndLogModel(array $trainingData): void
    {
        $runId = $this->mlflow->startRun('fraud_detection_experiment');
        
        try {
            $this->mlflow->logParams($runId, [
                'learning_rate' => 0.001,
                'epochs' => 100,
                'batch_size' => 32,
            ]);
            
            // Train model...
            $metrics = [
                'accuracy' => 0.95,
                'precision' => 0.92,
                'recall' => 0.89,
                'f1_score' => 0.90,
            ];
            
            $this->mlflow->logMetrics($runId, $metrics);
            $this->mlflow->endRun($runId, 'FINISHED');
            
        } catch (\Throwable $e) {
            $this->mlflow->endRun($runId, 'FAILED');
            throw $e;
        }
    }
}
```

---

## Value Objects

### Model

Represents a loaded ML model with metadata.

```php
namespace Nexus\MachineLearning\ValueObjects;

final readonly class Model implements ModelInterface
{
    public function __construct(
        private string $name,
        private string $version,
        private string $format,          // 'pytorch', 'onnx', 'remote', etc.
        private string $path,            // File path or API endpoint
        private array $metadata = []     // Additional model info
    ) {}
    
    public function getName(): string;
    public function getVersion(): string;
    public function getFormat(): string;
    public function getPath(): string;
    public function getMetadata(): array;
    public function getInputSchema(): array;
    public function getOutputSchema(): array;
}
```

---

### AnomalyResult

Concrete implementation of AnomalyResultInterface.

```php
namespace Nexus\MachineLearning\ValueObjects;

final readonly class AnomalyResult implements AnomalyResultInterface
{
    public function __construct(
        private bool $isAnomaly,
        private float $confidence,
        private ?string $reason = null,
        private string $provider = 'unknown',
        private array $metadata = []
    ) {}
    
    public function isAnomaly(): bool;
    public function getConfidence(): float;
    public function getReason(): ?string;
    public function getProvider(): string;
    public function getMetadata(): array;
}
```

---

## Enums

### ModelFormat

Supported model formats.

```php
namespace Nexus\MachineLearning\Enums;

enum ModelFormat: string
{
    case PYTORCH = 'pytorch';
    case ONNX = 'onnx';
    case TENSORFLOW = 'tensorflow';
    case REMOTE_API = 'remote_api';
    case MLFLOW = 'mlflow';
    
    public function getFileExtension(): string
    {
        return match ($this) {
            self::PYTORCH => '.pt',
            self::ONNX => '.onnx',
            self::TENSORFLOW => '',  // Directory-based
            self::REMOTE_API, self::MLFLOW => '',
        };
    }
}
```

---

### ProviderType

Available ML provider types.

```php
namespace Nexus\MachineLearning\Enums;

enum ProviderType: string
{
    case OPENAI = 'openai';
    case ANTHROPIC = 'anthropic';
    case GEMINI = 'gemini';
    case RULE_BASED = 'rulebased';
    case CUSTOM = 'custom';
    
    public function requiresApiKey(): bool
    {
        return match ($this) {
            self::OPENAI, self::ANTHROPIC, self::GEMINI => true,
            self::RULE_BASED, self::CUSTOM => false,
        };
    }
}
```

---

### InferenceStatus

Status of inference execution.

```php
namespace Nexus\MachineLearning\Enums;

enum InferenceStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case TIMEOUT = 'timeout';
}
```

---

## Exceptions

All exceptions in `Nexus\MachineLearning\Exceptions` namespace:

### AnomalyDetectionException

Base exception for anomaly detection errors.

```php
namespace Nexus\MachineLearning\Exceptions;

class AnomalyDetectionException extends \RuntimeException
{
    // Base exception for anomaly detection failures
}
```

---

### FeatureVersionMismatchException

Thrown when feature schema version is incompatible.

```php
class FeatureVersionMismatchException extends AnomalyDetectionException
{
    public function __construct(
        string $domain,
        int $expectedVersion,
        int $actualVersion
    ) {
        $message = "Feature schema mismatch for domain '{$domain}': " .
                   "expected version {$expectedVersion}, got {$actualVersion}";
        parent::__construct($message);
    }
}
```

---

### ProviderNotFoundException

Thrown when no provider is configured for a domain.

```php
class ProviderNotFoundException extends AnomalyDetectionException
{
    public function __construct(string $domain)
    {
        parent::__construct("No ML provider configured for domain '{$domain}'");
    }
}
```

---

### ModelNotFoundException

Thrown when a requested model cannot be found.

```php
class ModelNotFoundException extends \RuntimeException
{
    public function __construct(string $modelName, ?string $version = null)
    {
        $message = $version 
            ? "Model '{$modelName}' version '{$version}' not found"
            : "Model '{$modelName}' not found";
        parent::__construct($message);
    }
}
```

---

### InferenceException

Thrown when model inference fails.

```php
class InferenceException extends \RuntimeException
{
    public function __construct(string $reason, ?\Throwable $previous = null)
    {
        parent::__construct("Inference failed: {$reason}", 0, $previous);
    }
}
```

---

### InferenceTimeoutException

Thrown when inference execution exceeds timeout.

```php
class InferenceTimeoutException extends InferenceException
{
    public function __construct(int $timeoutSeconds)
    {
        parent::__construct("Inference exceeded timeout of {$timeoutSeconds} seconds");
    }
}
```

---

### MLflowException

Thrown when MLflow operations fail.

```php
class MLflowException extends \RuntimeException
{
    public function __construct(string $operation, string $reason)
    {
        parent::__construct("MLflow {$operation} failed: {$reason}");
    }
}
```

---

## Complete Interface Summary

### Anomaly Detection
- `AnomalyDetectionServiceInterface` - Main service
- `AnomalyResultInterface` - Detection result

### Provider Management
- `ProviderStrategyInterface` - Provider selection
- `ProviderInterface` - Base provider contract

### Feature Management
- `FeatureExtractorInterface` - Feature extraction
- `FeatureVersionManagerInterface` - Schema versioning
- `FeatureSchemaRepositoryInterface` - Schema persistence

### Model Inference
- `ModelLoaderInterface` - Load models
- `InferenceEngineInterface` - Execute predictions
- `ModelInterface` - Model representation

### MLflow Integration
- `MLflowClientInterface` - MLflow API client

### Repositories
- `FeatureSchemaRepositoryInterface` - Feature schema storage

---

## See Also

- **[Getting Started Guide](getting-started.md)** - Quick start and basic usage
- **[Integration Guide](integration-guide.md)** - Laravel and Symfony examples
- **[Code Examples](examples/)** - Working PHP code examples
- **[Migration Guide](../MIGRATION_INTELLIGENCE_TO_MACHINELEARNING.md)** - Upgrade from v1.x
