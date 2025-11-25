# Getting Started with Nexus MachineLearning

**Version:** 2.0.0  
**Package:** `nexus/machinelearning`  
**Formerly Known As:** `Nexus\Intelligence` (v1.x)

---

## Overview

The **Nexus MachineLearning** package provides a framework-agnostic machine learning orchestration engine for PHP 8.3+ applications. It enables:

- **Anomaly Detection** via external AI providers (OpenAI, Anthropic, Gemini)
- **Local Model Inference** via PyTorch, ONNX, or remote ML serving (MLflow, TensorFlow Serving)
- **Provider Strategy** for flexible backend selection per domain
- **Feature Versioning** with schema compatibility checking
- **MLflow Integration** for model registry and experiment tracking

---

## Prerequisites

### Required

- **PHP:** 8.3 or higher
- **Composer:** Latest version
- **Extensions:** `ext-json`, `ext-curl`

### Optional (depending on features used)

- **Python 3.8+:** For local PyTorch/ONNX inference
- **MLflow Server:** For model registry and experiment tracking
- **OpenAI API Key:** For OpenAI-based anomaly detection
- **Anthropic API Key:** For Claude-based anomaly detection
- **Google Gemini API Key:** For Gemini-based anomaly detection

---

## Installation

### Step 1: Install via Composer

For development (monorepo):

```bash
composer require nexus/machinelearning:"*@dev"
```

For production (published package):

```bash
composer require nexus/machinelearning:^2.0
```

### Step 2: Configure Provider Strategy

Create configuration for your ML providers:

**Example (Laravel - `config/machinelearning.php`):**

```php
<?php

return [
    'providers' => [
        // Default provider strategy per domain
        'default_strategy' => [
            'receivable' => 'openai',    // Invoice anomaly detection
            'payable' => 'anthropic',    // Vendor bill validation
            'procurement' => 'gemini',   // Purchase order anomalies
            'sales' => 'openai',         // Sales forecasting
            'default' => 'rulebased',    // Fallback for undefined domains
        ],
        
        // Provider configurations
        'openai' => [
            'enabled' => true,
            'api_key' => env('OPENAI_API_KEY'),
            'model' => 'gpt-4-turbo',
            'temperature' => 0.1,
            'max_tokens' => 1000,
            'timeout' => 30,
        ],
        
        'anthropic' => [
            'enabled' => true,
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => 'claude-3-5-sonnet-20241022',
            'temperature' => 0.1,
            'max_tokens' => 1000,
            'timeout' => 30,
        ],
        
        'gemini' => [
            'enabled' => true,
            'api_key' => env('GOOGLE_GEMINI_API_KEY'),
            'model' => 'gemini-1.5-pro',
            'temperature' => 0.1,
            'timeout' => 30,
        ],
        
        'rulebased' => [
            'enabled' => true,
            'thresholds' => [
                'z_score' => 3.0,
                'variance' => 0.25,
            ],
        ],
    ],
    
    'inference' => [
        'engines' => [
            'pytorch' => [
                'enabled' => false,
                'python_path' => '/usr/bin/python3',
            ],
            'onnx' => [
                'enabled' => false,
                'python_path' => '/usr/bin/python3',
            ],
            'remote' => [
                'enabled' => false,
                'base_url' => env('ML_SERVING_URL', 'http://localhost:5000'),
            ],
        ],
    ],
    
    'mlflow' => [
        'enabled' => false,
        'tracking_uri' => env('MLFLOW_TRACKING_URI', 'http://localhost:5000'),
        'registry_uri' => env('MLFLOW_REGISTRY_URI', 'http://localhost:5000'),
    ],
    
    'feature_schema' => [
        'versioning_enabled' => true,
        'strict_mode' => false,  // Allow backward-compatible changes
    ],
];
```

### Step 3: Set Environment Variables

Add to your `.env` file:

```env
# OpenAI Provider
OPENAI_API_KEY=sk-...

# Anthropic Provider
ANTHROPIC_API_KEY=sk-ant-...

# Google Gemini Provider
GOOGLE_GEMINI_API_KEY=AIza...

# MLflow (Optional)
MLFLOW_TRACKING_URI=http://localhost:5000
MLFLOW_REGISTRY_URI=http://localhost:5000

# ML Serving (Optional)
ML_SERVING_URL=http://localhost:8501
```

---

## Core Concepts

### 1. Anomaly Detection Service

The `AnomalyDetectionServiceInterface` provides AI-powered anomaly detection for business processes.

**How it works:**
1. Extract features from domain entities (e.g., invoice data)
2. Send features to configured AI provider
3. Receive structured anomaly analysis
4. Make business decisions based on results

**Example domains:**
- **Receivable:** Detect unusual invoice amounts, suspicious payment patterns
- **Payable:** Identify duplicate vendor bills, fraudulent invoices
- **Procurement:** Flag abnormal purchase quantities, unauthorized vendors
- **Sales:** Detect pricing anomalies, discount abuse

### 2. Provider Strategy

The `ProviderStrategyInterface` allows configuring different AI providers per domain:

- **OpenAI:** Best for general-purpose anomaly detection
- **Anthropic:** Better for complex reasoning, multi-step validation
- **Gemini:** Cost-effective, good for high-volume processing
- **RuleBased:** Fallback, no external API, deterministic results

### 3. Feature Extraction

Domain packages implement `FeatureExtractorInterface` to convert business entities into ML-ready features:

```php
interface FeatureExtractorInterface
{
    public function extract(mixed $entity): array;
    public function getFeatureSchema(): array;
}
```

### 4. Inference Engine

The `InferenceEngineInterface` provides local model execution:

- **PyTorch:** Execute `.pt` model files
- **ONNX:** Cross-platform model format
- **RemoteAPI:** Call external model serving endpoints

### 5. MLflow Integration

`MLflowClientInterface` connects to MLflow for:

- **Model Registry:** Load production models by name/version
- **Experiment Tracking:** Log metrics, parameters, artifacts
- **Model Versioning:** Promote models through stages (staging → production)

---

## Basic Configuration

### Laravel Service Provider Binding

Create `app/Providers/MachineLearningServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\MachineLearning\Contracts\ProviderStrategyInterface;
use Nexus\MachineLearning\Contracts\FeatureVersionManagerInterface;
use Nexus\MachineLearning\Services\AnomalyDetectionService;
use Nexus\MachineLearning\Core\Strategy\ConfigBasedProviderStrategy;
use Nexus\MachineLearning\Services\FeatureVersionManager;
use App\Repositories\MachineLearning\FeatureSchemaRepository;

class MachineLearningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Provider Strategy
        $this->app->singleton(ProviderStrategyInterface::class, function ($app) {
            return new ConfigBasedProviderStrategy(
                config('machinelearning.providers.default_strategy')
            );
        });
        
        // Feature Version Manager
        $this->app->singleton(FeatureVersionManagerInterface::class, function ($app) {
            return new FeatureVersionManager(
                $app->make(FeatureSchemaRepository::class)
            );
        });
        
        // Anomaly Detection Service
        $this->app->singleton(AnomalyDetectionServiceInterface::class, function ($app) {
            return new AnomalyDetectionService(
                $app->make(ProviderStrategyInterface::class),
                $app->make(FeatureVersionManagerInterface::class)
            );
        });
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\MachineLearningServiceProvider::class,
],
```

### Repository Implementation

Create `app/Repositories/MachineLearning/FeatureSchemaRepository.php`:

```php
<?php

namespace App\Repositories\MachineLearning;

use Nexus\MachineLearning\Contracts\FeatureSchemaRepositoryInterface;
use App\Models\FeatureSchema;

final readonly class FeatureSchemaRepository implements FeatureSchemaRepositoryInterface
{
    public function findLatest(string $domain): ?array
    {
        $schema = FeatureSchema::where('domain', $domain)
            ->orderBy('version', 'desc')
            ->first();
        
        return $schema?->schema_data;
    }
    
    public function save(string $domain, int $version, array $schema): void
    {
        FeatureSchema::create([
            'domain' => $domain,
            'version' => $version,
            'schema_data' => $schema,
        ]);
    }
    
    public function findByVersion(string $domain, int $version): ?array
    {
        $schema = FeatureSchema::where('domain', $domain)
            ->where('version', $version)
            ->first();
        
        return $schema?->schema_data;
    }
}
```

---

## Your First Integration

### Example: Invoice Anomaly Detection

#### Step 1: Create Feature Extractor

```php
<?php

namespace App\Services\Receivable;

use Nexus\MachineLearning\Contracts\FeatureExtractorInterface;
use App\Models\Invoice;

final class InvoiceAnomalyExtractor implements FeatureExtractorInterface
{
    public function extract(mixed $entity): array
    {
        assert($entity instanceof Invoice);
        
        return [
            'invoice_number' => $entity->number,
            'customer_id' => $entity->customer_id,
            'invoice_date' => $entity->invoice_date->format('Y-m-d'),
            'due_date' => $entity->due_date->format('Y-m-d'),
            'total_amount' => (float) $entity->total_amount,
            'currency' => $entity->currency,
            'line_items_count' => $entity->lineItems->count(),
            'customer_history' => [
                'total_invoices' => $entity->customer->invoices()->count(),
                'average_amount' => $entity->customer->invoices()->avg('total_amount'),
                'overdue_count' => $entity->customer->invoices()->where('status', 'overdue')->count(),
            ],
        ];
    }
    
    public function getFeatureSchema(): array
    {
        return [
            'version' => 1,
            'fields' => [
                'invoice_number' => ['type' => 'string', 'required' => true],
                'customer_id' => ['type' => 'string', 'required' => true],
                'invoice_date' => ['type' => 'date', 'required' => true],
                'due_date' => ['type' => 'date', 'required' => true],
                'total_amount' => ['type' => 'float', 'required' => true],
                'currency' => ['type' => 'string', 'required' => true],
                'line_items_count' => ['type' => 'integer', 'required' => true],
                'customer_history' => [
                    'type' => 'object',
                    'required' => true,
                    'fields' => [
                        'total_invoices' => ['type' => 'integer', 'required' => true],
                        'average_amount' => ['type' => 'float', 'required' => true],
                        'overdue_count' => ['type' => 'integer', 'required' => true],
                    ],
                ],
            ],
        ];
    }
}
```

#### Step 2: Use Anomaly Detection

```php
<?php

namespace App\Services\Receivable;

use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use App\Models\Invoice;

final readonly class InvoiceValidator
{
    public function __construct(
        private AnomalyDetectionServiceInterface $mlService,
        private InvoiceAnomalyExtractor $extractor
    ) {}
    
    public function validate(Invoice $invoice): void
    {
        $features = $this->extractor->extract($invoice);
        
        $result = $this->mlService->detectAnomalies('receivable', $features);
        
        if ($result->isAnomaly() && $result->getConfidence() >= 0.85) {
            throw new InvoiceAnomalyDetectedException(
                "Invoice {$invoice->number} flagged as anomaly: {$result->getReason()}"
            );
        }
    }
}
```

#### Step 3: Integrate into Invoice Creation

```php
<?php

namespace App\Http\Controllers;

use App\Services\Receivable\InvoiceValidator;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceValidator $validator
    ) {}
    
    public function store(Request $request)
    {
        $invoice = Invoice::create($request->validated());
        
        try {
            $this->validator->validate($invoice);
        } catch (InvoiceAnomalyDetectedException $e) {
            // Flag for manual review
            $invoice->update(['requires_review' => true, 'review_reason' => $e->getMessage()]);
        }
        
        return response()->json($invoice, 201);
    }
}
```

---

## Next Steps

Now that you have the basics working, explore advanced features:

1. **[API Reference](api-reference.md)** - Complete interface documentation
2. **[Integration Guide](integration-guide.md)** - Laravel and Symfony examples
3. **[Advanced Examples](examples/advanced-usage.php)** - Provider configuration, MLflow integration
4. **[Migration from v1.x](../MIGRATION_INTELLIGENCE_TO_MACHINELEARNING.md)** - Upgrade guide

---

## Common Issues

### Issue: "Provider not configured for domain"

**Solution:** Ensure `default_strategy` in config includes your domain or has a `'default'` key:

```php
'default_strategy' => [
    'receivable' => 'openai',
    'default' => 'rulebased',  // Fallback
],
```

### Issue: "Feature schema version mismatch"

**Solution:** Update feature extractor schema version and ensure database has matching schema record.

### Issue: "OpenAI API rate limit exceeded"

**Solution:** Configure fallback provider or add rate limiting:

```php
'providers' => [
    'default_strategy' => [
        'receivable' => 'openai',
        'receivable_fallback' => 'anthropic',  // Automatic fallback
    ],
],
```

---

## Support

- **Documentation:** `docs/` folder
- **Examples:** `docs/examples/`
- **Issues:** GitHub Issues
- **License:** MIT License
