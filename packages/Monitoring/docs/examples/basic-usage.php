<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Nexus Monitoring
 *
 * This example demonstrates the fundamental usage of the Monitoring package:
 * - Recording metrics (counter, gauge, timing, histogram)
 * - Running health checks
 * - Basic error handling
 */

require __DIR__ . '/../../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Nexus\Monitoring\Services\TelemetryTracker;
use Nexus\Monitoring\Services\HealthCheckRunner;
use Nexus\Monitoring\HealthChecks\DatabaseHealthCheck;
use Nexus\Monitoring\HealthChecks\MemoryHealthCheck;
use Nexus\Monitoring\Contracts\MetricStorageInterface;
use Nexus\Monitoring\ValueObjects\Metric;
use Nexus\Monitoring\ValueObjects\MetricType;
use Nexus\Monitoring\ValueObjects\QuerySpec;
use Nexus\Monitoring\ValueObjects\AggregationSpec;

// ============================================================================
// STEP 1: Create Simple In-Memory Storage (for demo purposes)
// ============================================================================

final class InMemoryMetricStorage implements MetricStorageInterface
{
    private array $metrics = [];

    public function store(Metric $metric): void
    {
        $this->metrics[] = $metric;
        echo sprintf(
            "✓ Stored %s: %s = %.2f %s\n",
            $metric->type->value,
            $metric->key,
            $metric->value,
            !empty($metric->tags) ? json_encode($metric->tags) : ''
        );
    }

    public function query(QuerySpec $spec): array
    {
        return array_filter(
            $this->metrics,
            fn(Metric $m) => $m->key === $spec->metricName
                && $m->timestamp >= $spec->from
                && $m->timestamp <= $spec->to
        );
    }

    public function aggregate(AggregationSpec $spec): float|int
    {
        $metrics = $this->query(new QuerySpec(
            metricName: $spec->metricName,
            from: $spec->from,
            to: $spec->to
        ));

        $values = array_map(fn(Metric $m) => $m->value, $metrics);

        return match ($spec->function) {
            'SUM' => array_sum($values),
            'AVG' => count($values) > 0 ? array_sum($values) / count($values) : 0,
            'COUNT' => count($values),
            'MIN' => count($values) > 0 ? min($values) : 0,
            'MAX' => count($values) > 0 ? max($values) : 0,
            default => throw new \Exception("Unsupported aggregation: {$spec->function}"),
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

    public function getAll(): array
    {
        return $this->metrics;
    }
}

// ============================================================================
// STEP 2: Set Up Dependencies
// ============================================================================

$logger = new Logger('monitoring');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$storage = new InMemoryMetricStorage();

$telemetry = new TelemetryTracker(
    storage: $storage,
    logger: $logger
);

echo "========================================\n";
echo "Nexus Monitoring - Basic Usage Example\n";
echo "========================================\n\n";

// ============================================================================
// STEP 3: Record Metrics (All 4 Types)
// ============================================================================

echo "--- Recording Metrics ---\n";

// Counter: Increment values (monotonically increasing)
$telemetry->increment('page.views', tags: ['page' => '/home']);
$telemetry->increment('page.views', tags: ['page' => '/home']);
$telemetry->increment('page.views', tags: ['page' => '/about']);
$telemetry->increment('api.requests', tags: ['endpoint' => '/api/users', 'method' => 'GET']);

// Gauge: Point-in-time values (can go up or down)
$telemetry->gauge('memory.usage', 128.5, tags: ['unit' => 'MB']);
$telemetry->gauge('queue.size', 42, tags: ['queue' => 'emails']);

// Timing: Duration measurements in milliseconds
$telemetry->timing('api.latency', 245.8, tags: ['endpoint' => '/api/users']);
$telemetry->timing('db.query', 15.3, tags: ['table' => 'orders', 'operation' => 'SELECT']);

// Histogram: Distribution of values
$telemetry->histogram('order.value', 1599.99, tags: ['currency' => 'USD']);
$telemetry->histogram('order.value', 299.50, tags: ['currency' => 'USD']);
$telemetry->histogram('order.items_count', 5, tags: ['customer_segment' => 'premium']);

echo "\n";

// ============================================================================
// STEP 4: Query Metrics
// ============================================================================

echo "--- Querying Metrics ---\n";

$spec = new QuerySpec(
    metricName: 'page.views',
    from: new \DateTimeImmutable('-1 hour'),
    to: new \DateTimeImmutable()
);

$pageViewMetrics = $storage->query($spec);
echo sprintf("Total page.views metrics: %d\n", count($pageViewMetrics));

// Aggregate page views
$aggregationSpec = new AggregationSpec(
    metricName: 'page.views',
    function: 'SUM',
    from: new \DateTimeImmutable('-1 hour'),
    to: new \DateTimeImmutable()
);

$totalPageViews = $storage->aggregate($aggregationSpec);
echo sprintf("Total page views (SUM): %.0f\n", $totalPageViews);

echo "\n";

// ============================================================================
// STEP 5: Health Checks
// ============================================================================

echo "--- Running Health Checks ---\n";

$runner = new HealthCheckRunner(logger: $logger);

// Register built-in health checks
$runner->register(new MemoryHealthCheck());

// You can also register database health check if you have PDO:
// $pdo = new PDO('mysql:host=localhost;dbname=test', 'user', 'password');
// $runner->register(new DatabaseHealthCheck($pdo));

// Run all checks
$results = $runner->runAll();

foreach ($results as $result) {
    echo sprintf(
        "%s: %s (took %dms) - %s\n",
        $result->name,
        $result->status->name,
        (int)$result->responseTimeMs,
        $result->message
    );
}

$overallHealth = $runner->getOverallHealth();
echo sprintf("\nOverall System Health: %s\n", $overallHealth->name);

echo "\n";

// ============================================================================
// STEP 6: Track Operation Duration (Real-World Pattern)
// ============================================================================

echo "--- Tracking Operation Duration ---\n";

function processOrder(): array
{
    // Simulate work
    usleep(50000); // 50ms
    return ['id' => 'ORD-001', 'status' => 'completed'];
}

$start = hrtime(true);
$order = processOrder();
$durationMs = (hrtime(true) - $start) / 1_000_000;

$telemetry->timing('order.process', $durationMs, tags: [
    'status' => $order['status'],
]);

echo sprintf("Order processed in %.2fms\n", $durationMs);

echo "\n";

// ============================================================================
// STEP 7: Show Summary
// ============================================================================

echo "--- Summary ---\n";
echo sprintf("Total metrics recorded: %d\n", count($storage->getAll()));

$metricsByType = [];
foreach ($storage->getAll() as $metric) {
    $type = $metric->type->value;
    $metricsByType[$type] = ($metricsByType[$type] ?? 0) + 1;
}

foreach ($metricsByType as $type => $count) {
    echo sprintf("  - %s: %d\n", ucfirst($type), $count);
}

echo "\n========================================\n";
echo "Example completed successfully!\n";
echo "========================================\n";
