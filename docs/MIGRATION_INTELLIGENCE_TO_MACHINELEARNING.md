# Migration Guide: Intelligence → MachineLearning (v1.x to v2.0)

**Package:** `Nexus\MachineLearning` (formerly `Nexus\Intelligence`)  
**Version:** v1.x → v2.0.0  
**Migration Date:** November 2025  
**Breaking Changes:** Yes (namespace, service names, configuration)

---

## 🎯 Executive Summary

The **Intelligence** package has been refactored and renamed to **MachineLearning** in version 2.0. This migration reflects:

1. **Clearer Semantics:** "Machine Learning" is more precise than "Intelligence"
2. **Enhanced Architecture:** Provider strategy pattern for flexible AI/ML backends
3. **New Capabilities:** MLflow integration, inference engines, external AI providers
4. **Better Extensibility:** Support for PyTorch, ONNX, remote model serving

**Migration Effort:** Medium (namespace changes, configuration updates, service renaming)

---

## 🔄 What Changed

### 1. Package Namespace

| v1.x | v2.0 |
|------|------|
| `Nexus\Intelligence` | `Nexus\MachineLearning` |

**Impact:** All `use` statements must be updated.

### 2. Composer Package Name

| v1.x | v2.0 |
|------|------|
| `nexus/intelligence` | `nexus/machinelearning` |

**Impact:** `composer.json` dependencies must be updated.

### 3. Service Names

| v1.x Class | v2.0 Class | Purpose |
|------------|------------|---------|
| `IntelligenceManager` | `MLModelManager` | Main orchestrator for ML operations |
| `SchemaVersionManager` | `FeatureVersionManager` | Feature schema lifecycle management |
| `SchemaVersionManagerInterface` | `FeatureVersionManagerInterface` | Contract for feature versioning |

**Impact:** Service provider bindings and dependency injection must be updated.

### 4. Configuration Keys

| v1.x Setting Key | v2.0 Setting Key | Description |
|------------------|------------------|-------------|
| `intelligence.schema.{version}` | `machinelearning.feature_schema.{version}` | Feature schema storage |
| N/A | `machinelearning.providers.{domain}` | Provider configuration per domain |
| N/A | `machinelearning.mlflow.tracking_uri` | MLflow server URI |
| N/A | `machinelearning.inference.timeout` | Inference timeout (seconds) |

**Impact:** Settings migration required, new configuration for v2.0 features.

### 5. Exception Classes

**Renamed (Backward Compatible):**
- `IntelligenceException` → `MachineLearningException` (v1.x extends v2.0 for compatibility)

**New Exceptions:**
- `ProviderNotFoundException`
- `ProviderUnavailableException`
- `AllProvidersUnavailableException`
- `InferenceEngineUnavailableException`
- `ModelLoadException`
- `InferenceException`
- `InferenceTimeoutException`
- `FineTuningNotSupportedException`

**Impact:** Exception handling may need updates for new error cases.

### 6. New Architecture Components

**Provider Strategy Pattern:**
- `ProviderStrategyInterface` - Selects AI/ML provider per domain
- `DomainProviderStrategy` - Default implementation with fallback chains
- `ProviderConfig` - Immutable configuration value object

**External AI Providers:**
- `OpenAIProvider` - GPT-4, fine-tuning support
- `AnthropicProvider` - Claude models
- `GeminiProvider` - Google Gemini
- `RuleBasedProvider` - Fallback (Z-score anomaly detection)

**Inference Abstractions:**
- `ModelLoaderInterface` - Load models from MLflow/filesystem
- `InferenceEngineInterface` - Execute predictions
- `ModelCacheInterface` - Cache loaded models

**Inference Engines:**
- `PyTorchInferenceEngine` - PyTorch model execution
- `ONNXInferenceEngine` - ONNX Runtime integration
- `RemoteAPIInferenceEngine` - MLflow/TensorFlow Serving

**MLflow Integration:**
- `MLflowClientInterface` - Model registry and experiment tracking
- `MLflowClient` - REST API implementation
- `MLflowModelLoader` - Download models from registry

---

## 📋 Step-by-Step Migration

### Step 1: Update Composer Dependencies

**Before (v1.x):**
```json
{
    "require": {
        "nexus/intelligence": "^1.0"
    }
}
```

**After (v2.0):**
```json
{
    "require": {
        "nexus/machinelearning": "^2.0"
    }
}
```

**Command:**
```bash
composer remove nexus/intelligence
composer require nexus/machinelearning:"^2.0"
```

---

### Step 2: Update Namespace Imports

**Find and replace across your codebase:**

```bash
# Linux/Mac
find . -type f -name "*.php" -exec sed -i 's/Nexus\\Intelligence/Nexus\\MachineLearning/g' {} +

# Or manually update each file
```

**Example Changes:**

**Before:**
```php
use Nexus\Intelligence\Contracts\AnomalyDetectionServiceInterface;
use Nexus\Intelligence\Exceptions\IntelligenceException;
use Nexus\Intelligence\ValueObjects\AnomalyResult;
```

**After:**
```php
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\MachineLearning\Exceptions\MachineLearningException;
use Nexus\MachineLearning\ValueObjects\AnomalyResult;
```

---

### Step 3: Update Service Bindings

**Before (v1.x Laravel Service Provider):**
```php
use Nexus\Intelligence\Services\IntelligenceManager;
use Nexus\Intelligence\Contracts\AnomalyDetectionServiceInterface;

$this->app->singleton(
    AnomalyDetectionServiceInterface::class,
    IntelligenceManager::class
);
```

**After (v2.0):**
```php
use Nexus\MachineLearning\Services\MLModelManager;
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\MachineLearning\Contracts\ProviderStrategyInterface;
use Nexus\MachineLearning\Services\DomainProviderStrategy;

// Bind provider strategy
$this->app->singleton(ProviderStrategyInterface::class, function ($app) {
    return new DomainProviderStrategy(
        $app->make(SettingsManagerInterface::class)
    );
});

// Bind main service
$this->app->singleton(
    AnomalyDetectionServiceInterface::class,
    MLModelManager::class
);
```

---

### Step 4: Update Schema Version Manager Bindings

**Before (v1.x):**
```php
use Nexus\Intelligence\Services\SchemaVersionManager;
use Nexus\Intelligence\Contracts\SchemaVersionManagerInterface;

$this->app->singleton(
    SchemaVersionManagerInterface::class,
    SchemaVersionManager::class
);
```

**After (v2.0):**
```php
use Nexus\MachineLearning\Services\FeatureVersionManager;
use Nexus\MachineLearning\Contracts\FeatureVersionManagerInterface;

$this->app->singleton(
    FeatureVersionManagerInterface::class,
    FeatureVersionManager::class
);
```

---

### Step 5: Migrate Configuration Settings

**v1.x Configuration:**
```php
// Settings stored as: intelligence.schema.{version}
$settingsManager->set('intelligence.schema.v1', $schemaData);
```

**v2.0 Configuration:**
```php
// Feature schemas: machinelearning.feature_schema.{version}
$settingsManager->set('machinelearning.feature_schema.v1', $schemaData);

// Provider configuration (new in v2.0)
$settingsManager->set('machinelearning.providers.receivable', [
    'primary' => 'openai',
    'fallback' => ['anthropic', 'rule_based'],
    'api_keys' => [
        'openai' => 'sk-...',
        'anthropic' => 'sk-ant-...',
    ],
    'parameters' => [
        'openai' => ['model' => 'gpt-4', 'temperature' => 0.1],
        'anthropic' => ['model' => 'claude-3-5-sonnet-20241022'],
    ],
]);

// MLflow configuration (optional, new in v2.0)
$settingsManager->set('machinelearning.mlflow.tracking_uri', 'http://localhost:5000');
$settingsManager->set('machinelearning.inference.timeout', 30);
```

---

### Step 6: Update Domain Package Extractors (If Applicable)

If you have custom feature extractors, update their namespace imports:

**Before:**
```php
namespace App\Receivable\MachineLearning;

use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Nexus\Intelligence\ValueObjects\FeatureSet;

final readonly class CustomExtractor implements FeatureExtractorInterface
{
    // ...
}
```

**After:**
```php
namespace App\Receivable\MachineLearning;

use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;
use Nexus\MachineLearning\ValueObjects\FeatureSet;

final readonly class CustomExtractor implements FeatureExtractorInterface
{
    // ...
}
```

---

### Step 7: Bind New Services (If Using Advanced Features)

**For External AI Providers:**
```php
use Nexus\MachineLearning\Contracts\HttpClientInterface;
use App\Services\PsrHttpClientAdapter; // Your PSR-18 adapter

$this->app->singleton(HttpClientInterface::class, function ($app) {
    return new PsrHttpClientAdapter(
        $app->make(\Psr\Http\Client\ClientInterface::class)
    );
});
```

**For MLflow Integration:**
```php
use Nexus\MachineLearning\Contracts\MLflowClientInterface;
use Nexus\MachineLearning\Services\MLflowClient;
use Nexus\MachineLearning\Contracts\ModelLoaderInterface;
use Nexus\MachineLearning\Services\MLflowModelLoader;
use Nexus\MachineLearning\Contracts\StorageInterface;

// Bind MLflow client
$this->app->singleton(MLflowClientInterface::class, function ($app) {
    return new MLflowClient(
        trackingUri: config('machinelearning.mlflow.tracking_uri'),
        httpClient: $app->make(HttpClientInterface::class),
        logger: $app->make(LoggerInterface::class)
    );
});

// Bind model loader
$this->app->singleton(ModelLoaderInterface::class, function ($app) {
    return new MLflowModelLoader(
        mlflowClient: $app->make(MLflowClientInterface::class),
        storage: $app->make(StorageInterface::class),
        logger: $app->make(LoggerInterface::class)
    );
});
```

**For Inference Engines:**
```php
use Nexus\MachineLearning\Contracts\InferenceEngineInterface;
use Nexus\MachineLearning\Core\Engines\PyTorchInferenceEngine;
use Nexus\MachineLearning\Core\Engines\ONNXInferenceEngine;
use Nexus\MachineLearning\Core\Engines\RemoteAPIInferenceEngine;

// Bind PyTorch engine (example)
$this->app->singleton('inference.pytorch', function ($app) {
    return new PyTorchInferenceEngine(
        pythonPath: '/usr/bin/python3',
        timeout: 30,
        logger: $app->make(LoggerInterface::class)
    );
});

// Or bind based on availability
$this->app->singleton(InferenceEngineInterface::class, function ($app) {
    $pytorch = new PyTorchInferenceEngine(
        pythonPath: '/usr/bin/python3',
        timeout: 30,
        logger: $app->make(LoggerInterface::class)
    );
    
    if ($pytorch->isAvailable()) {
        return $pytorch;
    }
    
    // Fallback to remote API
    return new RemoteAPIInferenceEngine(
        httpClient: $app->make(HttpClientInterface::class),
        logger: $app->make(LoggerInterface::class)
    );
});
```

---

### Step 8: Update Exception Handling

**Before (v1.x):**
```php
try {
    $result = $intelligenceManager->detectAnomalies($data);
} catch (IntelligenceException $e) {
    // Handle error
}
```

**After (v2.0):**
```php
use Nexus\MachineLearning\Exceptions\MachineLearningException;
use Nexus\MachineLearning\Exceptions\AllProvidersUnavailableException;
use Nexus\MachineLearning\Exceptions\InferenceTimeoutException;

try {
    $result = $mlModelManager->detectAnomalies($data);
} catch (AllProvidersUnavailableException $e) {
    // All AI providers failed, log and use default logic
    Log::error('All ML providers unavailable', ['error' => $e->getMessage()]);
} catch (InferenceTimeoutException $e) {
    // Inference took too long
    Log::warning('ML inference timeout', ['timeout' => $e->getTimeout()]);
} catch (MachineLearningException $e) {
    // General ML error
    Log::error('ML error', ['error' => $e->getMessage()]);
}
```

---

### Step 9: Test the Migration

**Automated Tests:**
```bash
# Run existing tests to verify no regressions
composer test

# Run specific package tests
./vendor/bin/phpunit packages/MachineLearning/tests
```

**Manual Verification:**
1. **Check Autoloading:**
   ```bash
   composer dump-autoload
   php artisan list # Should not show errors
   ```

2. **Verify Services Are Bound:**
   ```php
   // In Laravel tinker
   php artisan tinker
   >>> app(Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface::class)
   >>> app(Nexus\MachineLearning\Contracts\FeatureVersionManagerInterface::class)
   ```

3. **Test Anomaly Detection:**
   ```php
   use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
   
   $service = app(AnomalyDetectionServiceInterface::class);
   $result = $service->detectAnomalies('receivable', ['amount' => 10000]);
   dd($result);
   ```

---

## 🆕 New Features in v2.0

### 1. Provider Strategy Pattern

**Configure AI provider per domain:**

```php
use Nexus\MachineLearning\Contracts\ProviderStrategyInterface;

$strategy = app(ProviderStrategyInterface::class);

// Get provider for domain
$provider = $strategy->getProviderForDomain('receivable');

// Set domain configuration
$strategy->setDomainProvider('receivable', 'openai', ['model' => 'gpt-4']);
```

### 2. External AI Providers

**Use OpenAI for anomaly detection:**

```php
use Nexus\MachineLearning\Core\Providers\OpenAIProvider;
use Nexus\MachineLearning\ValueObjects\ProviderConfig;

$provider = new OpenAIProvider(
    httpClient: $httpClient,
    config: ProviderConfig::create(
        name: 'openai',
        apiKey: 'sk-...',
        parameters: ['model' => 'gpt-4', 'temperature' => 0.1]
    ),
    logger: $logger
);

$result = $provider->analyzeAnomaly('receivable', $features);
```

### 3. MLflow Integration

**Load model from MLflow registry:**

```php
use Nexus\MachineLearning\Contracts\ModelLoaderInterface;

$loader = app(ModelLoaderInterface::class);

// Load production model
$model = $loader->load('invoice_anomaly_detector', version: null, stage: 'production');

// Use inference engine
$engine = app(Nexus\MachineLearning\Contracts\InferenceEngineInterface::class);
$prediction = $engine->predict($model, ['amount' => 10000, 'age_days' => 45]);
```

### 4. Custom Inference Engines

**Run local PyTorch models:**

```php
use Nexus\MachineLearning\Core\Engines\PyTorchInferenceEngine;

$engine = new PyTorchInferenceEngine(
    pythonPath: '/usr/bin/python3',
    timeout: 30,
    logger: $logger
);

if ($engine->isAvailable()) {
    $predictions = $engine->batchPredict($model, $batchData);
}
```

---

## ⚠️ Breaking Changes Summary

| Change | Impact | Action Required |
|--------|--------|-----------------|
| Package namespace | High | Update all `use` statements |
| Composer package name | High | Update `composer.json` |
| Service class names | High | Update service provider bindings |
| Configuration keys | Medium | Migrate settings data |
| `IntelligenceManager` → `MLModelManager` | Medium | Update constructor injections |
| `SchemaVersionManager` → `FeatureVersionManager` | Medium | Update constructor injections |

---

## 🔄 Backward Compatibility

**What Remains Compatible:**

1. **Value Objects:** `AnomalyResult`, `FeatureSet`, `UsageMetrics` (no changes)
2. **Interfaces:** `AnomalyDetectionServiceInterface`, `FeatureExtractorInterface` (signatures unchanged)
3. **Exceptions:** `IntelligenceException` still exists (extends `MachineLearningException`)
4. **Core Logic:** Anomaly detection algorithms unchanged (same statistical methods)

**Deprecation Notice:**

- `IntelligenceException` is **deprecated** but will remain for v2.x lifecycle
- Use `MachineLearningException` in new code
- v3.0 will remove `IntelligenceException`

---

## 🛠️ Troubleshooting

### Issue: "Class 'Nexus\Intelligence\...' not found"

**Cause:** Namespace not updated.

**Solution:**
```bash
# Search for old namespace references
grep -r "Nexus\\\\Intelligence" app/ packages/

# Update each file with new namespace
```

### Issue: "Interface 'SchemaVersionManagerInterface' not found"

**Cause:** Service renamed.

**Solution:** Update to `FeatureVersionManagerInterface`.

### Issue: "Setting key 'intelligence.schema.v1' not found"

**Cause:** Configuration keys changed.

**Solution:** Migrate settings:
```php
$oldValue = $settings->get('intelligence.schema.v1');
$settings->set('machinelearning.feature_schema.v1', $oldValue);
$settings->delete('intelligence.schema.v1');
```

### Issue: "All providers unavailable"

**Cause:** Provider configuration not set.

**Solution:** Configure at least the rule-based fallback:
```php
$settings->set('machinelearning.providers.receivable', [
    'primary' => 'rule_based',
]);
```

---

## 📞 Support

**Documentation:**
- Package README: `packages/MachineLearning/README.md`
- API Reference: `packages/MachineLearning/docs/api-reference.md`
- Integration Guide: `packages/MachineLearning/docs/integration-guide.md`

**Questions:**
- Create an issue on GitHub with `[Migration]` prefix
- Check `NEXUS_PACKAGES_REFERENCE.md` for package overview

---

**Migration Author:** Nexus Architecture Team  
**Last Updated:** November 25, 2025  
**Version:** 2.0.0
