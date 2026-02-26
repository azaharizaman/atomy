# Getting Started with Nexus Monitoring

## Prerequisites

- PHP 8.3+
- Composer
- A PSR-3 compatible logger (e.g., Monolog)
- (Optional) A time-series database (Prometheus, InfluxDB, or any TSDB)
- (Optional) A cache backend (Redis, Memcached, or filesystem)

---

## Installation

Install the package via Composer:

```bash
composer require nexus/telemetry:"*@dev"
```

---

## Quick Start (5 Minutes)

### Step 1: Install Dependencies

```bash
composer require nexus/telemetry monolog/monolog
```

### Step 2: Create a Simple Implementation

For quick testing, create simple in-memory implementations:

```php
<?php
// app/Services/Monitoring/InMemoryMetricStorage.php

use Nexus\Telemetry\Contracts\MetricStorageInterface;
use Nexus\Telemetry\ValueObjects\Metric;
use Nexus\Telemetry\ValueObjects\QuerySpec;
use Nexus\Telemetry\ValueObjects\AggregationSpec;

final class InMemoryMetricStorage implements MetricStorageInterface
{
    private array $metrics = [];

    public function store(Metric $metric): void
    {
        $this->metrics[] = $metric;
    }

    public function query(QuerySpec $spec): array
    {
        // Simple filter by metric name
        return array_filter(
            $this->metrics,
            fn(Metric $m) => $m->key === $spec->metricName
        );
    }

    public function aggregate(AggregationSpec $spec): float|int
    {
        // Simple SUM aggregation
        $metrics = $this->query(new QuerySpec(
            metricName: $spec->metricName,
            from: $spec->from,
            to: $spec->to
        ));

        return match ($spec->function) {
            'SUM' => array_sum(array_map(fn($m) => $m->value, $metrics)),
            'COUNT' => count($metrics),
            default => throw new \Exception("Unsupported aggregation: {$spec->function}")
        };
    }

    public function deleteMetricsOlderThan(int $cutoffTimestamp, ?int $batchSize = null): int
    {
        $count = 0;
        $this->metrics = array_filter(
            $this->metrics,
            function ($metric) use ($cutoffTimestamp, &$count) {
                if ($metric->timestamp->getTimestamp() < $cutoffTimestamp) {
                    $count++;
                    return false;
                }
                return true;
            }
        );
        return $count;
    }

    public function deleteMetric(string $metricKey, int $cutoffTimestamp): int
    {
        $count = 0;
        $this->metrics = array_filter(
            $this->metrics,
            function ($metric) use ($metricKey, $cutoffTimestamp, &$count) {
                if ($metric->key === $metricKey && $metric->timestamp->getTimestamp() < $cutoffTimestamp) {
                    $count++;
                    return false;
                }
                return true;
            }
        );
        return $count;
    }

    public function countMetricsOlderThan(int $cutoffTimestamp): int
    {
        return count(array_filter(
            $this->metrics,
            fn($m) => $m->timestamp->getTimestamp() < $cutoffTimestamp
        ));
    }
}
```

### Step 3: Set Up Dependency Injection

```php
<?php
// bootstrap.php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Nexus\Telemetry\Services\TelemetryTracker;

// Create logger
$logger = new Logger('monitoring');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

// Create storage
$storage = new InMemoryMetricStorage();

// Create TelemetryTracker
$telemetry = new TelemetryTracker(
    storage: $storage,
    logger: $logger
);
```

### Step 4: Record Your First Metric

```php
<?php

// Record a counter (increments)
$telemetry->increment('page.views', tags: ['page' => '/home']);
$telemetry->increment('page.views', tags: ['page' => '/home']);
$telemetry->increment('page.views', tags: ['page' => '/about']);

// Record a gauge (point-in-time value)
$telemetry->gauge('memory.usage', 128.5, tags: ['unit' => 'MB']);

// Record a timing (duration in milliseconds)
$telemetry->timing('api.latency', 245.8, tags: ['endpoint' => '/api/users']);

// Record a histogram (distribution)
$telemetry->histogram('order.value', 1599.99, tags: ['currency' => 'USD']);

echo "Metrics recorded successfully!\n";
```

### Step 5: Query Your Metrics

```php
<?php

use Nexus\Telemetry\ValueObjects\QuerySpec;

// Query all page views
$spec = new QuerySpec(
    metricName: 'page.views',
    from: new \DateTimeImmutable('-1 hour'),
    to: new \DateTimeImmutable()
);

$metrics = $storage->query($spec);

echo "Total page view metrics: " . count($metrics) . "\n";
```

---

## Using the MonitoringAwareTrait (Easiest Integration)

For service classes that need monitoring, use the trait pattern:

```php
<?php

use Nexus\Telemetry\Traits\MonitoringAwareTrait;
use Nexus\Telemetry\Contracts\TelemetryTrackerInterface;

final class OrderService
{
    use MonitoringAwareTrait;

    public function __construct(
        private readonly TelemetryTrackerInterface $telemetry
    ) {
        $this->setTelemetry($telemetry);
    }

    public function processOrder(array $orderData): void
    {
        // Track operation with automatic timing and error handling
        $this->trackOperation('order.process', function () use ($orderData) {
            // Business logic here
            sleep(1); // Simulating work
            
            // Record business metric
            $this->recordIncrement('orders.completed');
            $this->recordGauge('order.value', $orderData['total']);
        });
    }

    public function calculateTotal(array $items): float
    {
        // Measure operation duration
        return $this->timeOperation('order.calculate_total', function () use ($items) {
            return array_sum(array_column($items, 'price'));
        });
    }
}
```

Usage:

```php
$orderService = new OrderService($telemetry);

$orderService->processOrder([
    'id' => 'ORD-001',
    'total' => 1599.99,
]);

// Metrics automatically recorded:
// - order.process.timing (duration)
// - order.process.count (counter with status:success)
// - orders.completed (counter)
// - order.value (gauge)
```

---

## Health Checks

### Built-In Health Checks

The package includes 5 built-in health checks:

```php
<?php

use Nexus\Telemetry\Services\HealthCheckRunner;
use Nexus\Telemetry\HealthChecks\DatabaseHealthCheck;
use Nexus\Telemetry\HealthChecks\CacheHealthCheck;
use Nexus\Telemetry\HealthChecks\DiskSpaceHealthCheck;
use Nexus\Telemetry\HealthChecks\MemoryHealthCheck;
use Monolog\Logger;

$logger = new Logger('health');

// Create health check runner
$runner = new HealthCheckRunner(logger: $logger);

// Register checks
$pdo = new PDO('mysql:host=localhost;dbname=erp', 'user', 'password');
$runner->register(new DatabaseHealthCheck($pdo));

// PSR-16 cache (e.g., Symfony Cache, Laravel Cache)
$cache = /* your PSR-16 cache implementation */;
$runner->register(new CacheHealthCheck($cache));

$runner->register(new DiskSpaceHealthCheck('/var/www'));
$runner->register(new MemoryHealthCheck());

// Run all checks
$results = $runner->runAll();

foreach ($results as $result) {
    echo sprintf(
        "%s: %s (took %dms)\n",
        $result->name,
        $result->status->name,
        (int)$result->responseTimeMs
    );
}

// Get overall system health
$overallStatus = $runner->getOverallHealth();
echo "Overall System Health: {$overallStatus->name}\n";
```

---

## Alerting (Exception-Based)

The package can automatically generate alerts from exceptions:

```php
<?php

use Nexus\Telemetry\Services\AlertEvaluator;
use Nexus\Telemetry\Contracts\AlertDispatcherInterface;

// Create a simple console alert dispatcher
$dispatcher = new class implements AlertDispatcherInterface {
    public function dispatch(array $context): void {
        echo "[ALERT] {$context['severity']} - {$context['message']}\n";
    }
};

$evaluator = new AlertEvaluator(
    dispatcher: $dispatcher,
    logger: $logger
);

// Simulate an exception
try {
    throw new \RuntimeException("Database connection failed", 500);
} catch (\Throwable $e) {
    // Automatically dispatch alert with severity mapping
    $evaluator->evaluate($e, context: [
        'user_id' => 'user-123',
        'request_id' => 'req-abc',
    ]);
}

// Output: [ALERT] CRITICAL - Database connection failed
```

---

## Multi-Tenancy Support

If you're using `nexus/tenant`, the package automatically tags metrics with `tenant_id`:

```php
<?php

use Nexus\Telemetry\Services\TelemetryTracker;
use Nexus\Tenant\Contracts\TenantContextInterface;

// Create tenant context (example implementation)
$tenantContext = new class implements TenantContextInterface {
    public function getCurrentTenantId(): string {
        return 'tenant-acme-corp';
    }
    
    public function setCurrentTenantId(string $tenantId): void {}
    public function hasTenantContext(): bool { return true; }
};

// Inject tenant context
$telemetry = new TelemetryTracker(
    storage: $storage,
    logger: $logger,
    tenantContext: $tenantContext
);

// Record metric
$telemetry->increment('api.requests');

// Metric automatically tagged with: tenant_id=tenant-acme-corp
```

---

## SLO Tracking (Automatic Instrumentation)

Track Service Level Objectives with automatic timing and alerting:

```php
<?php

use Nexus\Telemetry\Core\SLOWrapper;

// Wrap an operation with SLO tracking
$wrapper = SLOWrapper::for($telemetry, 'payment.charge', tags: [
    'gateway' => 'stripe'
]);

$result = $wrapper->execute(function () {
    // Simulate payment processing
    sleep(2);
    return ['status' => 'success', 'id' => 'ch_123'];
});

// Automatically records:
// - payment.charge.timing (duration)
// - payment.charge.count (counter with status:success/failure)
```

---

## Next Steps

- **API Reference:** See [api-reference.md](api-reference.md) for all interfaces and methods
- **Integration Guide:** See [integration-guide.md](integration-guide.md) for Laravel/Symfony integration
- **Examples:** See [examples/](examples/) for working code samples
- **Advanced Features:** Metric retention, cardinality protection, sampling

---

## Common Patterns

### Pattern 1: Simple Counter

```php
$telemetry->increment('user.login', tags: ['method' => 'password']);
```

### Pattern 2: Gauge with Current State

```php
$telemetry->gauge('queue.size', $queue->count(), tags: ['queue' => 'emails']);
```

### Pattern 3: Timing an Operation

```php
$start = hrtime(true);
// ... operation ...
$durationMs = (hrtime(true) - $start) / 1_000_000;
$telemetry->timing('db.query', $durationMs, tags: ['table' => 'orders']);
```

### Pattern 4: Histogram for Distribution

```php
$telemetry->histogram('order.items_count', count($order->items), tags: [
    'customer_segment' => 'premium'
]);
```

---

## Troubleshooting

### No metrics are being stored

**Check:**
1. Is `MetricStorageInterface` bound correctly?
2. Is the storage implementation actually persisting?
3. Are exceptions being thrown and swallowed?

**Solution:** Add logging to your storage implementation to debug.

### Health checks timing out

**Check:**
1. Is the default timeout (5000ms) too short?
2. Is the dependency (database, cache) slow?

**Solution:** Increase timeout per check or optimize the dependency.

### Cardinality errors

**Check:**
1. Are you using unbounded tag values (user IDs, timestamps)?
2. Is the cardinality limit too low?

**Solution:** Use bounded tag values or increase cardinality limits in your CardinalityGuard implementation.

---

## Support

- **Documentation:** [README.md](../README.md)
- **Requirements:** [REQUIREMENTS.md](../REQUIREMENTS.md)
- **GitHub Issues:** https://github.com/your-org/nexus-monitoring/issues
