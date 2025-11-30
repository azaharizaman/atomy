# Integration Guide: Monitoring

This guide shows how to integrate Nexus Monitoring into your application with complete, working examples for Laravel, Symfony, and vanilla PHP.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/monitoring:"*@dev"
```

### Step 2: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=monitoring-config
```

### Step 3: Create Database Migration

```bash
php artisan make:migration create_monitoring_metrics_table
```

```php
<?php
// database/migrations/2025_01_25_000000_create_monitoring_metrics_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 36)->nullable()->index();
            $table->string('key', 255)->index();
            $table->enum('type', ['counter', 'gauge', 'timing', 'histogram'])->index();
            $table->decimal('value', 20, 4);
            $table->json('tags')->nullable();
            $table->string('trace_id', 32)->nullable()->index();
            $table->string('span_id', 16)->nullable();
            $table->timestamp('recorded_at', 6)->index(); // Microsecond precision
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['key', 'recorded_at']);
            $table->index(['tenant_id', 'key', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_metrics');
    }
};
```

Run migration:

```bash
php artisan migrate
```

### Step 4: Create Eloquent Model

```php
<?php
// app/Models/MonitoringMetric.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Monitoring\ValueObjects\MetricType;

final class MonitoringMetric extends Model
{
    protected $fillable = [
        'tenant_id',
        'key',
        'type',
        'value',
        'tags',
        'trace_id',
        'span_id',
        'recorded_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'value' => 'float',
        'recorded_at' => 'datetime:Y-m-d H:i:s.u',
    ];

    public function getTypeAttribute(string $value): MetricType
    {
        return MetricType::from($value);
    }
}
```

### Step 5: Create Repository (MetricStorageInterface)

```php
<?php
// app/Repositories/Monitoring/DbMetricRepository.php

namespace App\Repositories\Monitoring;

use App\Models\MonitoringMetric;
use Nexus\Monitoring\Contracts\MetricStorageInterface;
use Nexus\Monitoring\ValueObjects\Metric;
use Nexus\Monitoring\ValueObjects\QuerySpec;
use Nexus\Monitoring\ValueObjects\AggregationSpec;
use Nexus\Monitoring\Exceptions\UnsupportedAggregationException;

final readonly class DbMetricRepository implements MetricStorageInterface
{
    public function store(Metric $metric): void
    {
        MonitoringMetric::create([
            'tenant_id' => $metric->tags['tenant_id'] ?? null,
            'key' => $metric->key,
            'type' => $metric->type->value,
            'value' => $metric->value,
            'tags' => $metric->tags,
            'trace_id' => $metric->traceId,
            'span_id' => $metric->spanId,
            'recorded_at' => $metric->timestamp,
        ]);
    }

    public function query(QuerySpec $spec): array
    {
        $query = MonitoringMetric::where('key', $spec->metricName)
            ->whereBetween('recorded_at', [
                $spec->from->format('Y-m-d H:i:s.u'),
                $spec->to->format('Y-m-d H:i:s.u'),
            ]);

        foreach ($spec->tags as $key => $value) {
            $query->whereRaw("JSON_EXTRACT(tags, '$.{$key}') = ?", [$value]);
        }

        if ($spec->limit) {
            $query->limit($spec->limit);
        }

        if ($spec->orderBy) {
            $query->orderBy($spec->orderBy);
        }

        return $query->get()->map(function ($record) {
            return new Metric(
                key: $record->key,
                type: MetricType::from($record->type),
                value: $record->value,
                tags: $record->tags ?? [],
                timestamp: $record->recorded_at,
                traceId: $record->trace_id,
                spanId: $record->span_id
            );
        })->toArray();
    }

    public function aggregate(AggregationSpec $spec): float|int
    {
        $query = MonitoringMetric::where('key', $spec->metricName)
            ->whereBetween('recorded_at', [
                $spec->from->format('Y-m-d H:i:s.u'),
                $spec->to->format('Y-m-d H:i:s.u'),
            ]);

        return match ($spec->function) {
            'AVG' => (float) $query->avg('value'),
            'SUM' => (float) $query->sum('value'),
            'MIN' => (float) $query->min('value'),
            'MAX' => (float) $query->max('value'),
            'COUNT' => $query->count(),
            default => throw new UnsupportedAggregationException($spec->function),
        };
    }

    public function deleteMetricsOlderThan(int $cutoffTimestamp, ?int $batchSize = null): int
    {
        $query = MonitoringMetric::where('recorded_at', '<', date('Y-m-d H:i:s', $cutoffTimestamp));

        if ($batchSize) {
            $query->limit($batchSize);
        }

        return $query->delete();
    }

    public function deleteMetric(string $metricKey, int $cutoffTimestamp): int
    {
        return MonitoringMetric::where('key', $metricKey)
            ->where('recorded_at', '<', date('Y-m-d H:i:s', $cutoffTimestamp))
            ->delete();
    }

    public function countMetricsOlderThan(int $cutoffTimestamp): int
    {
        return MonitoringMetric::where('recorded_at', '<', date('Y-m-d H:i:s', $cutoffTimestamp))
            ->count();
    }
}
```

### Step 6: Create Redis Cardinality Storage

```php
<?php
// app/Services/Monitoring/RedisCardinalityStorage.php

namespace App\Services\Monitoring;

use Nexus\Monitoring\Contracts\CardinalityStorageInterface;
use Illuminate\Support\Facades\Redis;

final readonly class RedisCardinalityStorage implements CardinalityStorageInterface
{
    public function add(string $tagKey, string $tagValue): void
    {
        Redis::command('PFADD', ["monitoring:cardinality:{$tagKey}", $tagValue]);
    }

    public function count(string $tagKey): int
    {
        return (int) Redis::command('PFCOUNT', ["monitoring:cardinality:{$tagKey}"]);
    }

    public function reset(string $tagKey): void
    {
        Redis::del("monitoring:cardinality:{$tagKey}");
    }
}
```

### Step 7: Create Alert Dispatcher

```php
<?php
// app/Services/Monitoring/LaravelAlertDispatcher.php

namespace App\Services\Monitoring;

use Nexus\Monitoring\Contracts\AlertDispatcherInterface;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MonitoringAlert;

final readonly class LaravelAlertDispatcher implements AlertDispatcherInterface
{
    public function dispatch(array $context): void
    {
        // Log alert
        \Log::warning('Monitoring Alert', $context);

        // Send notification (Slack, email, etc.)
        if ($context['severity'] === 'CRITICAL') {
            Notification::route('slack', config('monitoring.slack_webhook'))
                ->notify(new MonitoringAlert($context));
        }

        // Queue for async processing if needed
        // dispatch(new ProcessAlert($context));
    }
}
```

### Step 8: Create Service Provider

```php
<?php
// app/Providers/MonitoringServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Nexus\Monitoring\Contracts\MetricStorageInterface;
use Nexus\Monitoring\Contracts\CardinalityStorageInterface;
use Nexus\Monitoring\Contracts\CardinalityGuardInterface;
use Nexus\Monitoring\Contracts\AlertDispatcherInterface;
use Nexus\Monitoring\Contracts\CacheRepositoryInterface;
use Nexus\Monitoring\Services\TelemetryTracker;
use Nexus\Monitoring\Services\HealthCheckRunner;
use Nexus\Monitoring\Services\AlertEvaluator;
use Nexus\Monitoring\Services\MetricRetentionService;
use Nexus\Monitoring\Core\TimeBasedRetentionPolicy;
use App\Repositories\Monitoring\DbMetricRepository;
use App\Services\Monitoring\RedisCardinalityStorage;
use App\Services\Monitoring\LaravelAlertDispatcher;
use App\Services\Monitoring\LaravelCacheAdapter;
use App\Services\Monitoring\SimpleCardinalityGuard;
use Psr\Log\LoggerInterface;

final class MonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind MetricStorageInterface
        $this->app->singleton(MetricStorageInterface::class, DbMetricRepository::class);

        // Bind CardinalityStorageInterface
        $this->app->singleton(CardinalityStorageInterface::class, RedisCardinalityStorage::class);

        // Bind CardinalityGuardInterface
        $this->app->singleton(CardinalityGuardInterface::class, SimpleCardinalityGuard::class);

        // Bind AlertDispatcherInterface
        $this->app->singleton(AlertDispatcherInterface::class, LaravelAlertDispatcher::class);

        // Bind CacheRepositoryInterface
        $this->app->singleton(CacheRepositoryInterface::class, LaravelCacheAdapter::class);

        // Bind TelemetryTrackerInterface
        $this->app->singleton(TelemetryTrackerInterface::class, function ($app) {
            return new TelemetryTracker(
                storage: $app->make(MetricStorageInterface::class),
                logger: $app->make(LoggerInterface::class),
                tenantContext: $app->make(\Nexus\Tenant\Contracts\TenantContextInterface::class),
                cardinalityGuard: $app->make(CardinalityGuardInterface::class),
            );
        });

        // Bind HealthCheckRunner
        $this->app->singleton(HealthCheckRunner::class, function ($app) {
            return new HealthCheckRunner(
                logger: $app->make(LoggerInterface::class),
                cache: $app->make(CacheRepositoryInterface::class),
            );
        });

        // Bind AlertEvaluator
        $this->app->singleton(AlertEvaluator::class, function ($app) {
            return new AlertEvaluator(
                dispatcher: $app->make(AlertDispatcherInterface::class),
                logger: $app->make(LoggerInterface::class),
                cache: $app->make(CacheRepositoryInterface::class),
            );
        });

        // Bind MetricRetentionService
        $this->app->singleton(MetricRetentionService::class, function ($app) {
            return new MetricRetentionService(
                storage: $app->make(MetricStorageInterface::class),
                retentionPolicy: TimeBasedRetentionPolicy::days(90),
                logger: $app->make(LoggerInterface::class),
            );
        });
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/monitoring.php' => config_path('monitoring.php'),
        ], 'monitoring-config');

        // Register health checks
        $runner = $this->app->make(HealthCheckRunner::class);
        // Health checks registered in AppServiceProvider or here
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\MonitoringServiceProvider::class,
],
```

### Step 9: Create Artisan Commands

**Health Check Command:**

```bash
php artisan make:command RunHealthChecksCommand
```

```php
<?php
// app/Console/Commands/RunHealthChecksCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Monitoring\Services\HealthCheckRunner;

final class RunHealthChecksCommand extends Command
{
    protected $signature = 'monitoring:health-check';
    protected $description = 'Run all registered health checks';

    public function handle(HealthCheckRunner $runner): int
    {
        $this->info('Running health checks...');

        $results = $runner->runAll();

        foreach ($results as $result) {
            $status = match ($result->status->name) {
                'HEALTHY' => '<fg=green>✓</>',
                'WARNING' => '<fg=yellow>⚠</>',
                'DEGRADED' => '<fg=yellow>⚠</>',
                'CRITICAL' => '<fg=red>✗</>',
                'OFFLINE' => '<fg=red>✗</>',
            };

            $this->line(sprintf(
                '%s %s: %s (took %dms)',
                $status,
                $result->name,
                $result->message,
                (int)$result->responseTimeMs
            ));
        }

        $overallHealth = $runner->getOverallHealth();
        $this->newLine();
        $this->info("Overall Health: {$overallHealth->name}");

        return $overallHealth === \Nexus\Monitoring\ValueObjects\HealthStatus::HEALTHY ? 0 : 1;
    }
}
```

**Metrics Pruning Command:**

```bash
php artisan make:command PruneMetricsCommand
```

```php
<?php
// app/Console/Commands/PruneMetricsCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Monitoring\Services\MetricRetentionService;

final class PruneMetricsCommand extends Command
{
    protected $signature = 'monitoring:prune {--dry-run}';
    protected $description = 'Prune expired metrics based on retention policy';

    public function handle(MetricRetentionService $service): int
    {
        $stats = $service->getRetentionStats();

        $this->info("Retention Policy: {$stats['retention_period_days']} days");
        $this->info("Cutoff Date: {$stats['cutoff_date']}");
        $this->info("Eligible for cleanup: {$stats['metrics_eligible_for_cleanup']} metrics");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No metrics will be deleted');
            return 0;
        }

        if (!$this->confirm('Proceed with deletion?')) {
            $this->warn('Cancelled');
            return 1;
        }

        $deleted = $service->pruneExpiredMetrics();

        $this->info("Deleted {$deleted} metrics");

        return 0;
    }
}
```

### Step 10: Create HTTP Endpoints

```php
<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Nexus\Monitoring\Services\HealthCheckRunner;
use Nexus\Monitoring\Contracts\MetricStorageInterface;
use Nexus\Monitoring\ValueObjects\QuerySpec;

// Public health check endpoint
Route::get('/healthz', function (HealthCheckRunner $runner) {
    $results = $runner->runAll();
    $overallHealth = $runner->getOverallHealth();

    $statusCode = match ($overallHealth->name) {
        'HEALTHY' => 200,
        'WARNING', 'DEGRADED' => 200,
        'CRITICAL', 'OFFLINE' => 503,
    };

    return response()->json([
        'status' => $overallHealth->name,
        'checks' => array_map(fn($r) => [
            'name' => $r->name,
            'status' => $r->status->name,
            'message' => $r->message,
            'response_time_ms' => $r->responseTimeMs,
        ], $results),
    ], $statusCode);
});

// Metrics export endpoint (protected)
Route::middleware('auth:sanctum')->get('/monitoring/metrics', function (Request $request, MetricStorageInterface $storage) {
    $spec = new QuerySpec(
        metricName: $request->input('metric', 'api.requests'),
        from: new \DateTimeImmutable($request->input('from', '-1 hour')),
        to: new \DateTimeImmutable($request->input('to', 'now')),
        limit: (int) $request->input('limit', 1000)
    );

    $metrics = $storage->query($spec);

    return response()->json([
        'count' => count($metrics),
        'metrics' => array_map(fn($m) => [
            'key' => $m->key,
            'type' => $m->type->value,
            'value' => $m->value,
            'tags' => $m->tags,
            'timestamp' => $m->timestamp->format('Y-m-d H:i:s.u'),
        ], $metrics),
    ]);
});
```

### Step 11: Schedule Commands

```php
<?php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule): void
{
    // Run health checks every 5 minutes
    $schedule->command('monitoring:health-check')->everyFiveMinutes();

    // Prune old metrics daily at 3 AM
    $schedule->command('monitoring:prune')->dailyAt('03:00');
}
```

### Step 12: Use in Controllers/Services

```php
<?php
// app/Http/Controllers/OrderController.php

namespace App\Http\Controllers;

use Nexus\Monitoring\Traits\MonitoringAwareTrait;
use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;

final class OrderController extends Controller
{
    use MonitoringAwareTrait;

    public function __construct(
        private readonly TelemetryTrackerInterface $telemetry
    ) {
        $this->setTelemetry($telemetry);
    }

    public function store(Request $request)
    {
        return $this->trackOperation('order.create', function () use ($request) {
            $order = Order::create($request->validated());

            $this->recordIncrement('orders.created');
            $this->recordGauge('order.value', $order->total, tags: [
                'currency' => $order->currency,
            ]);

            return response()->json($order, 201);
        });
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/monitoring:"*@dev"
```

### Step 2: Create Doctrine Entity

```php
<?php
// src/Entity/MonitoringMetric.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Monitoring\ValueObjects\MetricType;

#[ORM\Entity]
#[ORM\Table(name: 'monitoring_metrics')]
#[ORM\Index(columns: ['key', 'recorded_at'])]
#[ORM\Index(columns: ['tenant_id', 'key', 'recorded_at'])]
class MonitoringMetric
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $tenantId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $key;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'decimal', precision: 20, scale: 4)]
    private float $value;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tags = [];

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $traceId = null;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private ?string $spanId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $recordedAt;

    // Getters and setters...
}
```

Run migration:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Step 3: Create Repository

```php
<?php
// src/Repository/DoctrineMetricRepository.php

namespace App\Repository;

use App\Entity\MonitoringMetric;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\Monitoring\Contracts\MetricStorageInterface;
use Nexus\Monitoring\ValueObjects\Metric;
use Nexus\Monitoring\ValueObjects\MetricType;
use Nexus\Monitoring\ValueObjects\QuerySpec;
use Nexus\Monitoring\ValueObjects\AggregationSpec;

final readonly class DoctrineMetricRepository implements MetricStorageInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function store(Metric $metric): void
    {
        $entity = new MonitoringMetric();
        $entity->setTenantId($metric->tags['tenant_id'] ?? null);
        $entity->setKey($metric->key);
        $entity->setType($metric->type->value);
        $entity->setValue($metric->value);
        $entity->setTags($metric->tags);
        $entity->setTraceId($metric->traceId);
        $entity->setSpanId($metric->spanId);
        $entity->setRecordedAt($metric->timestamp);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function query(QuerySpec $spec): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
            ->from(MonitoringMetric::class, 'm')
            ->where('m.key = :key')
            ->andWhere('m.recordedAt BETWEEN :from AND :to')
            ->setParameter('key', $spec->metricName)
            ->setParameter('from', $spec->from)
            ->setParameter('to', $spec->to);

        if ($spec->limit) {
            $qb->setMaxResults($spec->limit);
        }

        $results = $qb->getQuery()->getResult();

        return array_map(function ($entity) {
            return new Metric(
                key: $entity->getKey(),
                type: MetricType::from($entity->getType()),
                value: $entity->getValue(),
                tags: $entity->getTags() ?? [],
                timestamp: $entity->getRecordedAt(),
                traceId: $entity->getTraceId(),
                spanId: $entity->getSpanId()
            );
        }, $results);
    }

    // ... implement other methods similarly
}
```

### Step 4: Configure Services

```yaml
# config/services.yaml

services:
    # MetricStorage
    Nexus\Monitoring\Contracts\MetricStorageInterface:
        class: App\Repository\DoctrineMetricRepository
        arguments:
            - '@doctrine.orm.entity_manager'

    # TelemetryTracker
    Nexus\Monitoring\Contracts\TelemetryTrackerInterface:
        class: Nexus\Monitoring\Services\TelemetryTracker
        arguments:
            $storage: '@Nexus\Monitoring\Contracts\MetricStorageInterface'
            $logger: '@logger'
            $tenantContext: '@Nexus\Tenant\Contracts\TenantContextInterface'

    # Health Check Runner
    Nexus\Monitoring\Services\HealthCheckRunner:
        arguments:
            $logger: '@logger'
            $cache: '@cache.app'

    # Alert Evaluator
    Nexus\Monitoring\Services\AlertEvaluator:
        arguments:
            $dispatcher: '@App\Service\Monitoring\SymfonyAlertDispatcher'
            $logger: '@logger'
            $cache: '@cache.app'
```

### Step 5: Create Console Commands

```php
<?php
// src/Command/HealthCheckCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nexus\Monitoring\Services\HealthCheckRunner;

final class HealthCheckCommand extends Command
{
    protected static $defaultName = 'monitoring:health-check';

    public function __construct(
        private readonly HealthCheckRunner $runner
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Running health checks...');

        $results = $runner->runAll();

        foreach ($results as $result) {
            $output->writeln(sprintf(
                '%s: %s (took %dms)',
                $result->name,
                $result->status->name,
                (int)$result->responseTimeMs
            ));
        }

        $overallHealth = $runner->getOverallHealth();
        $output->writeln("Overall Health: {$overallHealth->name}");

        return Command::SUCCESS;
    }
}
```

---

## Vanilla PHP Integration

For projects without a framework:

### Step 1: Install Package

```bash
composer require nexus/monitoring:"*@dev" monolog/monolog
```

### Step 2: Create Bootstrap File

```php
<?php
// bootstrap.php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Nexus\Monitoring\Services\TelemetryTracker;
use App\Monitoring\InMemoryMetricStorage;

// Create logger
$logger = new Logger('monitoring');
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/monitoring.log', Logger::DEBUG));

// Create storage
$storage = new InMemoryMetricStorage();

// Create TelemetryTracker
$telemetry = new TelemetryTracker(
    storage: $storage,
    logger: $logger
);

return [
    'logger' => $logger,
    'storage' => $storage,
    'telemetry' => $telemetry,
];
```

### Step 3: Use in Application

```php
<?php
// public/index.php

$container = require __DIR__ . '/../bootstrap.php';
$telemetry = $container['telemetry'];

// Track request
$telemetry->increment('http.requests', tags: [
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $_SERVER['REQUEST_URI'],
]);

// Track memory
$telemetry->gauge('memory.usage', memory_get_usage(true) / 1024 / 1024, tags: [
    'unit' => 'MB',
]);

// Your application logic...
```

---

## Best Practices

### 1. Tag Naming Conventions

```php
// ✅ GOOD: Snake_case, bounded values
$telemetry->increment('api.requests', tags: [
    'endpoint' => '/api/users',
    'method' => 'GET',
    'status_code' => '200',
]);

// ❌ BAD: Unbounded values (user IDs, timestamps)
$telemetry->increment('api.requests', tags: [
    'user_id' => 'user-12345',  // High cardinality!
    'timestamp' => time(),      // Infinite cardinality!
]);
```

### 2. Metric Naming Conventions

```php
// ✅ GOOD: Descriptive, hierarchical
$telemetry->increment('orders.created');
$telemetry->increment('payments.stripe.succeeded');
$telemetry->timing('db.query.users');

// ❌ BAD: Generic, flat
$telemetry->increment('count');
$telemetry->timing('query');
```

### 3. Error Handling

```php
// ✅ GOOD: Use AlertEvaluator for exceptions
try {
    $result = $service->processPayment($payment);
} catch (\Throwable $e) {
    $evaluator->evaluate($e, context: [
        'payment_id' => $payment->id,
        'amount' => $payment->amount,
    ]);
    throw $e; // Re-throw for normal error handling
}
```

### 4. Performance: Async Metric Storage

For high-throughput applications, queue metrics for async processing:

```php
// Laravel example
$telemetry->increment('api.requests'); // Sync (fast)

// Queue for async storage
dispatch(new StoreMetricJob($metric));
```

---

## Troubleshooting

### Metrics Not Showing Up

1. Check that `MetricStorageInterface` is bound correctly
2. Verify database permissions
3. Check logs for exceptions

### High Cardinality Warnings

1. Review tag values (avoid user IDs, timestamps)
2. Increase cardinality limits if justified
3. Use sampling for high-cardinality metrics

### Slow Health Checks

1. Increase timeout per check
2. Enable result caching
3. Optimize dependency (database, cache)

---

**Last Updated:** 2025-01-25
