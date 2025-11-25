# Integration Guide: MachineLearning

**Version:** 2.0.0  
**Package:** `nexus/machinelearning`

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Service Provider Bindings](#service-provider-bindings)
4. [Repository Implementations](#repository-implementations)
5. [Common Patterns](#common-patterns)
6. [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### Step 1: Create Service Provider

Create `app/Providers/MachineLearningServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\MachineLearning\Contracts\{
    AnomalyDetectionServiceInterface,
    ProviderStrategyInterface,
    FeatureVersionManagerInterface,
    ModelLoaderInterface,
    InferenceEngineInterface,
    MLflowClientInterface,
    FeatureSchemaRepositoryInterface
};
use Nexus\MachineLearning\Services\{
    AnomalyDetectionService,
    FeatureVersionManager,
    MLModelManager
};
use Nexus\MachineLearning\Core\Strategy\ConfigBasedProviderStrategy;
use Nexus\MachineLearning\Infrastructure\Loaders\MLflowModelLoader;
use Nexus\MachineLearning\Infrastructure\Engines\{
    PyTorchInferenceEngine,
    OnnxInferenceEngine,
    RemoteAPIInferenceEngine
};
use Nexus\MachineLearning\Infrastructure\MLflow\HttpMLflowClient;
use App\Repositories\MachineLearning\{
    FeatureSchemaRepository,
    ProviderConfigRepository
};
use Psr\Log\LoggerInterface;

class MachineLearningServiceProvider extends ServiceProvider
{
    /**
     * Register ML services
     */
    public function register(): void
    {
        // Provider Strategy
        $this->app->singleton(ProviderStrategyInterface::class, function ($app) {
            return new ConfigBasedProviderStrategy(
                config('machinelearning.providers.default_strategy', []),
                $app->make(ProviderConfigRepository::class)
            );
        });
        
        // Feature Schema Repository
        $this->app->singleton(FeatureSchemaRepositoryInterface::class, function ($app) {
            return $app->make(FeatureSchemaRepository::class);
        });
        
        // Feature Version Manager
        $this->app->singleton(FeatureVersionManagerInterface::class, function ($app) {
            return new FeatureVersionManager(
                $app->make(FeatureSchemaRepositoryInterface::class)
            );
        });
        
        // Anomaly Detection Service
        $this->app->singleton(AnomalyDetectionServiceInterface::class, function ($app) {
            return new AnomalyDetectionService(
                $app->make(ProviderStrategyInterface::class),
                $app->make(FeatureVersionManagerInterface::class),
                $app->make(LoggerInterface::class)
            );
        });
        
        // MLflow Client (if enabled)
        if (config('machinelearning.mlflow.enabled', false)) {
            $this->app->singleton(MLflowClientInterface::class, function ($app) {
                return new HttpMLflowClient(
                    trackingUri: config('machinelearning.mlflow.tracking_uri'),
                    registryUri: config('machinelearning.mlflow.registry_uri'),
                    logger: $app->make(LoggerInterface::class)
                );
            });
            
            $this->app->singleton(ModelLoaderInterface::class, function ($app) {
                return new MLflowModelLoader(
                    $app->make(MLflowClientInterface::class)
                );
            });
        }
        
        // Inference Engines
        $this->registerInferenceEngines();
    }
    
    /**
     * Register inference engines based on configuration
     */
    private function registerInferenceEngines(): void
    {
        $engines = [];
        
        if (config('machinelearning.inference.engines.pytorch.enabled', false)) {
            $engines['pytorch'] = new PyTorchInferenceEngine(
                pythonPath: config('machinelearning.inference.engines.pytorch.python_path', '/usr/bin/python3')
            );
        }
        
        if (config('machinelearning.inference.engines.onnx.enabled', false)) {
            $engines['onnx'] = new OnnxInferenceEngine(
                pythonPath: config('machinelearning.inference.engines.onnx.python_path', '/usr/bin/python3')
            );
        }
        
        if (config('machinelearning.inference.engines.remote.enabled', false)) {
            $engines['remote'] = new RemoteAPIInferenceEngine(
                baseUrl: config('machinelearning.inference.engines.remote.base_url')
            );
        }
        
        // Bind primary inference engine (first available)
        if (!empty($engines)) {
            $this->app->singleton(InferenceEngineInterface::class, function () use ($engines) {
                return reset($engines);
            });
        }
    }
    
    /**
     * Bootstrap ML services
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/machinelearning.php' => config_path('machinelearning.php'),
            ], 'machinelearning-config');
        }
    }
}
```

### Step 2: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\MachineLearningServiceProvider::class,
],
```

### Step 3: Create Configuration File

Create `config/machinelearning.php`:

```php
<?php

return [
    'providers' => [
        'default_strategy' => [
            'receivable' => env('ML_PROVIDER_RECEIVABLE', 'openai'),
            'payable' => env('ML_PROVIDER_PAYABLE', 'anthropic'),
            'procurement' => env('ML_PROVIDER_PROCUREMENT', 'gemini'),
            'default' => env('ML_PROVIDER_DEFAULT', 'rulebased'),
        ],
        
        'openai' => [
            'enabled' => env('ML_OPENAI_ENABLED', true),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('ML_OPENAI_MODEL', 'gpt-4-turbo'),
            'temperature' => (float) env('ML_OPENAI_TEMPERATURE', 0.1),
            'max_tokens' => (int) env('ML_OPENAI_MAX_TOKENS', 1000),
            'timeout' => (int) env('ML_OPENAI_TIMEOUT', 30),
        ],
        
        'anthropic' => [
            'enabled' => env('ML_ANTHROPIC_ENABLED', true),
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ML_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
            'temperature' => (float) env('ML_ANTHROPIC_TEMPERATURE', 0.1),
            'max_tokens' => (int) env('ML_ANTHROPIC_MAX_TOKENS', 1000),
            'timeout' => (int) env('ML_ANTHROPIC_TIMEOUT', 30),
        ],
        
        'gemini' => [
            'enabled' => env('ML_GEMINI_ENABLED', false),
            'api_key' => env('GOOGLE_GEMINI_API_KEY'),
            'model' => env('ML_GEMINI_MODEL', 'gemini-1.5-pro'),
            'temperature' => (float) env('ML_GEMINI_TEMPERATURE', 0.1),
            'timeout' => (int) env('ML_GEMINI_TIMEOUT', 30),
        ],
        
        'rulebased' => [
            'enabled' => true,
            'thresholds' => [
                'z_score' => (float) env('ML_RULEBASED_ZSCORE', 3.0),
                'variance' => (float) env('ML_RULEBASED_VARIANCE', 0.25),
            ],
        ],
    ],
    
    'inference' => [
        'engines' => [
            'pytorch' => [
                'enabled' => env('ML_PYTORCH_ENABLED', false),
                'python_path' => env('ML_PYTORCH_PYTHON', '/usr/bin/python3'),
            ],
            'onnx' => [
                'enabled' => env('ML_ONNX_ENABLED', false),
                'python_path' => env('ML_ONNX_PYTHON', '/usr/bin/python3'),
            ],
            'remote' => [
                'enabled' => env('ML_REMOTE_ENABLED', false),
                'base_url' => env('ML_SERVING_URL', 'http://localhost:5000'),
            ],
        ],
    ],
    
    'mlflow' => [
        'enabled' => env('MLFLOW_ENABLED', false),
        'tracking_uri' => env('MLFLOW_TRACKING_URI', 'http://localhost:5000'),
        'registry_uri' => env('MLFLOW_REGISTRY_URI', 'http://localhost:5000'),
    ],
    
    'feature_schema' => [
        'versioning_enabled' => env('ML_FEATURE_VERSIONING', true),
        'strict_mode' => env('ML_FEATURE_STRICT', false),
    ],
];
```

### Step 4: Create Database Migration

Create migration for feature schemas:

```bash
php artisan make:migration create_feature_schemas_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 50)->index();
            $table->unsignedInteger('version');
            $table->json('schema_data');
            $table->timestamps();
            
            $table->unique(['domain', 'version']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('feature_schemas');
    }
};
```

### Step 5: Create Repository Implementation

Create `app/Repositories/MachineLearning/FeatureSchemaRepository.php`:

```php
<?php

declare(strict_types=1);

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
        FeatureSchema::updateOrCreate(
            ['domain' => $domain, 'version' => $version],
            ['schema_data' => $schema]
        );
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

### Step 6: Create Eloquent Model

Create `app/Models/FeatureSchema.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureSchema extends Model
{
    protected $fillable = [
        'domain',
        'version',
        'schema_data',
    ];
    
    protected $casts = [
        'schema_data' => 'array',
        'version' => 'integer',
    ];
}
```

---

## Symfony Integration

### Step 1: Create Service Configuration

Create `config/packages/machinelearning.yaml`:

```yaml
parameters:
    machinelearning.providers.default_strategy:
        receivable: 'openai'
        payable: 'anthropic'
        procurement: 'gemini'
        default: 'rulebased'
    
    machinelearning.providers.openai:
        enabled: true
        api_key: '%env(OPENAI_API_KEY)%'
        model: 'gpt-4-turbo'
        temperature: 0.1
        max_tokens: 1000
        timeout: 30
    
    machinelearning.providers.anthropic:
        enabled: true
        api_key: '%env(ANTHROPIC_API_KEY)%'
        model: 'claude-3-5-sonnet-20241022'
        temperature: 0.1
        max_tokens: 1000
        timeout: 30
    
    machinelearning.mlflow.enabled: false
    machinelearning.mlflow.tracking_uri: '%env(MLFLOW_TRACKING_URI)%'
    machinelearning.mlflow.registry_uri: '%env(MLFLOW_REGISTRY_URI)%'

services:
    _defaults:
        autowire: true
        autoconfigure: true
    
    # Feature Schema Repository (Doctrine implementation)
    Nexus\MachineLearning\Contracts\FeatureSchemaRepositoryInterface:
        class: App\Repository\MachineLearning\DoctrineFeatureSchemaRepository
    
    # Provider Strategy
    Nexus\MachineLearning\Contracts\ProviderStrategyInterface:
        class: Nexus\MachineLearning\Core\Strategy\ConfigBasedProviderStrategy
        arguments:
            $providerConfig: '%machinelearning.providers.default_strategy%'
    
    # Feature Version Manager
    Nexus\MachineLearning\Contracts\FeatureVersionManagerInterface:
        class: Nexus\MachineLearning\Services\FeatureVersionManager
    
    # Anomaly Detection Service
    Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface:
        class: Nexus\MachineLearning\Services\AnomalyDetectionService
    
    # MLflow Client (conditional)
    Nexus\MachineLearning\Contracts\MLflowClientInterface:
        class: Nexus\MachineLearning\Infrastructure\MLflow\HttpMLflowClient
        arguments:
            $trackingUri: '%machinelearning.mlflow.tracking_uri%'
            $registryUri: '%machinelearning.mlflow.registry_uri%'
```

### Step 2: Create Doctrine Entity

Create `src/Entity/FeatureSchema.php`:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeatureSchemaRepository::class)]
#[ORM\Table(name: 'feature_schemas')]
#[ORM\UniqueConstraint(name: 'domain_version_unique', columns: ['domain', 'version'])]
class FeatureSchema
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    
    #[ORM\Column(type: 'string', length: 50)]
    private string $domain;
    
    #[ORM\Column(type: 'integer')]
    private int $version;
    
    #[ORM\Column(type: 'json')]
    private array $schemaData;
    
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;
    
    public function __construct(string $domain, int $version, array $schemaData)
    {
        $this->domain = $domain;
        $this->version = $version;
        $this->schemaData = $schemaData;
        $this->createdAt = new \DateTimeImmutable();
    }
    
    // Getters and setters...
}
```

### Step 3: Create Doctrine Repository

Create `src/Repository/MachineLearning/DoctrineFeatureSchemaRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repository\MachineLearning;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\MachineLearning\Contracts\FeatureSchemaRepositoryInterface;
use App\Entity\FeatureSchema;

final class DoctrineFeatureSchemaRepository extends ServiceEntityRepository implements FeatureSchemaRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureSchema::class);
    }
    
    public function findLatest(string $domain): ?array
    {
        $schema = $this->findOneBy(
            ['domain' => $domain],
            ['version' => 'DESC']
        );
        
        return $schema?->getSchemaData();
    }
    
    public function save(string $domain, int $version, array $schema): void
    {
        $existing = $this->findOneBy(['domain' => $domain, 'version' => $version]);
        
        if ($existing) {
            $existing->setSchemaData($schema);
        } else {
            $entity = new FeatureSchema($domain, $version, $schema);
            $this->getEntityManager()->persist($entity);
        }
        
        $this->getEntityManager()->flush();
    }
    
    public function findByVersion(string $domain, int $version): ?array
    {
        $schema = $this->findOneBy(['domain' => $domain, 'version' => $version]);
        
        return $schema?->getSchemaData();
    }
}
```

---

## Service Provider Bindings

### Provider Factory Pattern

Create a provider factory for flexible provider instantiation:

```php
<?php

declare(strict_types=1);

namespace App\Services\MachineLearning;

use Nexus\MachineLearning\Core\Contracts\ProviderInterface;
use Nexus\MachineLearning\Infrastructure\Providers\{
    OpenAIProvider,
    AnthropicProvider,
    GeminiProvider,
    RuleBasedProvider
};
use Psr\Log\LoggerInterface;

final readonly class ProviderFactory
{
    public function __construct(
        private array $config,
        private LoggerInterface $logger
    ) {}
    
    public function create(string $providerName): ProviderInterface
    {
        $providerConfig = $this->config[$providerName] ?? [];
        
        return match ($providerName) {
            'openai' => new OpenAIProvider(
                apiKey: $providerConfig['api_key'] ?? '',
                model: $providerConfig['model'] ?? 'gpt-4-turbo',
                temperature: $providerConfig['temperature'] ?? 0.1,
                maxTokens: $providerConfig['max_tokens'] ?? 1000,
                timeout: $providerConfig['timeout'] ?? 30,
                logger: $this->logger
            ),
            
            'anthropic' => new AnthropicProvider(
                apiKey: $providerConfig['api_key'] ?? '',
                model: $providerConfig['model'] ?? 'claude-3-5-sonnet-20241022',
                temperature: $providerConfig['temperature'] ?? 0.1,
                maxTokens: $providerConfig['max_tokens'] ?? 1000,
                timeout: $providerConfig['timeout'] ?? 30,
                logger: $this->logger
            ),
            
            'gemini' => new GeminiProvider(
                apiKey: $providerConfig['api_key'] ?? '',
                model: $providerConfig['model'] ?? 'gemini-1.5-pro',
                temperature: $providerConfig['temperature'] ?? 0.1,
                timeout: $providerConfig['timeout'] ?? 30,
                logger: $this->logger
            ),
            
            'rulebased' => new RuleBasedProvider(
                thresholds: $providerConfig['thresholds'] ?? [],
                logger: $this->logger
            ),
            
            default => throw new \InvalidArgumentException("Unknown provider: {$providerName}"),
        };
    }
}
```

---

## Repository Implementations

### PostgreSQL Repository Example

```php
<?php

declare(strict_types=1);

namespace App\Repositories\MachineLearning;

use Nexus\MachineLearning\Contracts\FeatureSchemaRepositoryInterface;

final readonly class PostgresFeatureSchemaRepository implements FeatureSchemaRepositoryInterface
{
    public function __construct(
        private \PDO $connection
    ) {}
    
    public function findLatest(string $domain): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT schema_data FROM feature_schemas 
             WHERE domain = :domain 
             ORDER BY version DESC 
             LIMIT 1'
        );
        
        $stmt->execute(['domain' => $domain]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ? json_decode($result['schema_data'], true) : null;
    }
    
    public function save(string $domain, int $version, array $schema): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO feature_schemas (domain, version, schema_data, created_at) 
             VALUES (:domain, :version, :schema_data, NOW())
             ON CONFLICT (domain, version) 
             DO UPDATE SET schema_data = :schema_data'
        );
        
        $stmt->execute([
            'domain' => $domain,
            'version' => $version,
            'schema_data' => json_encode($schema),
        ]);
    }
    
    public function findByVersion(string $domain, int $version): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT schema_data FROM feature_schemas 
             WHERE domain = :domain AND version = :version'
        );
        
        $stmt->execute(['domain' => $domain, 'version' => $version]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ? json_decode($result['schema_data'], true) : null;
    }
}
```

---

## Common Patterns

### Pattern 1: Multi-Provider Fallback

```php
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\MachineLearning\Exceptions\ProviderUnavailableException;

final readonly class ResilientAnomalyDetector
{
    public function __construct(
        private AnomalyDetectionServiceInterface $mlService
    ) {}
    
    public function detect(string $domain, array $features): AnomalyResultInterface
    {
        $providers = ['openai', 'anthropic', 'rulebased'];
        $lastException = null;
        
        foreach ($providers as $provider) {
            try {
                return $this->mlService->detectAnomalies($domain, $features, [
                    'provider_override' => $provider,
                ]);
            } catch (ProviderUnavailableException $e) {
                $lastException = $e;
                continue;  // Try next provider
            }
        }
        
        throw $lastException ?? new \RuntimeException('All providers failed');
    }
}
```

### Pattern 2: Batch Processing with Rate Limiting

```php
use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;

final readonly class BatchAnomalyProcessor
{
    private const BATCH_SIZE = 10;
    private const RATE_LIMIT_DELAY_MS = 100;
    
    public function __construct(
        private AnomalyDetectionServiceInterface $mlService
    ) {}
    
    public function processBatch(string $domain, array $entities): array
    {
        $results = [];
        $batches = array_chunk($entities, self::BATCH_SIZE);
        
        foreach ($batches as $batch) {
            foreach ($batch as $entity) {
                $features = $this->extractFeatures($entity);
                $results[] = $this->mlService->detectAnomalies($domain, $features);
                
                usleep(self::RATE_LIMIT_DELAY_MS * 1000);  // Rate limiting
            }
        }
        
        return $results;
    }
    
    private function extractFeatures(mixed $entity): array
    {
        // Feature extraction logic
    }
}
```

### Pattern 3: Cached Predictions

```php
use Nexus\MachineLearning\Contracts\{AnomalyDetectionServiceInterface, AnomalyResultInterface};
use Psr\SimpleCache\CacheInterface;

final readonly class CachedAnomalyDetector
{
    private const TTL_SECONDS = 3600;  // 1 hour
    
    public function __construct(
        private AnomalyDetectionServiceInterface $mlService,
        private CacheInterface $cache
    ) {}
    
    public function detect(string $domain, array $features): AnomalyResultInterface
    {
        $cacheKey = $this->generateCacheKey($domain, $features);
        
        $cached = $this->cache->get($cacheKey);
        if ($cached instanceof AnomalyResultInterface) {
            return $cached;
        }
        
        $result = $this->mlService->detectAnomalies($domain, $features);
        
        $this->cache->set($cacheKey, $result, self::TTL_SECONDS);
        
        return $result;
    }
    
    private function generateCacheKey(string $domain, array $features): string
    {
        return 'ml:anomaly:' . $domain . ':' . md5(json_encode($features));
    }
}
```

---

## Troubleshooting

### Issue: "No provider configured for domain"

**Cause:** Missing domain configuration in provider strategy.

**Solution:**

```php
// Add default fallback
'default_strategy' => [
    'receivable' => 'openai',
    'default' => 'rulebased',  // Always provide fallback
],
```

### Issue: "Feature schema version mismatch"

**Cause:** Extractor schema version doesn't match database schema.

**Solution:**

```php
// Update extractor schema version
public function getFeatureSchema(): array
{
    return [
        'version' => 2,  // Increment version
        'fields' => [
            // Updated fields
        ],
    ];
}

// Register new schema
$versionManager->registerSchema('receivable', 2, $extractor->getFeatureSchema());
```

### Issue: "Inference timeout"

**Cause:** Model execution exceeds configured timeout.

**Solution:**

```php
// Increase timeout in configuration
'inference' => [
    'engines' => [
        'pytorch' => [
            'timeout' => 60,  // Increase from default 30s
        ],
    ],
],
```

### Issue: "OpenAI API rate limit exceeded"

**Cause:** Too many requests to OpenAI API.

**Solution:**

```php
// Configure fallback provider
'default_strategy' => [
    'receivable' => 'openai',
    'receivable_fallback' => 'anthropic',  // Auto-fallback
],

// Or implement rate limiting (see Pattern 2 above)
```

---

## See Also

- **[Getting Started Guide](getting-started.md)** - Quick start guide
- **[API Reference](api-reference.md)** - Complete interface documentation
- **[Code Examples](examples/)** - Working PHP code samples
- **[Migration Guide](../MIGRATION_INTELLIGENCE_TO_MACHINELEARNING.md)** - Upgrade from v1.x
