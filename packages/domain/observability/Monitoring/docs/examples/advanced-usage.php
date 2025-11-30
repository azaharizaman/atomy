<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Nexus Monitoring
 *
 * This example demonstrates advanced features:
 * - Multi-tenancy auto-tagging
 * - Cardinality protection
 * - SLO tracking with automatic instrumentation
 * - Alert evaluation
 * - Metric retention and cleanup
 * - MonitoringAwareTrait integration
 */

require __DIR__ . '/../../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Nexus\Monitoring\Services\TelemetryTracker;
use Nexus\Monitoring\Services\AlertEvaluator;
use Nexus\Monitoring\Services\MetricRetentionService;
use Nexus\Monitoring\Core\SLOWrapper;
use Nexus\Monitoring\Core\TimeBasedRetentionPolicy;
use Nexus\Monitoring\Traits\MonitoringAwareTrait;
use Nexus\Monitoring\Contracts\TenantContextInterface;
use Nexus\Monitoring\Contracts\CardinalityGuardInterface;
use Nexus\Monitoring\Contracts\CardinalityStorageInterface;
use Nexus\Monitoring\Contracts\AlertDispatcherInterface;
use Nexus\Monitoring\Contracts\MetricStorageInterface;
use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;
use Nexus\Monitoring\ValueObjects\Metric;
use Nexus\Monitoring\ValueObjects\QuerySpec;
use Nexus\Monitoring\ValueObjects\AggregationSpec;
use Nexus\Monitoring\Exceptions\CardinalityLimitExceededException;

// ============================================================================
// Mock Implementations
// ============================================================================

final class InMemoryMetricStorage implements MetricStorageInterface
{
    private array $metrics = [];

    public function store(Metric $metric): void
    {
        $this->metrics[] = $metric;
    }

    public function query(QuerySpec $spec): array
    {
        return array_filter(
            $this->metrics,
            fn(Metric $m) => $m->key === $spec->metricName
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
            'COUNT' => count($values),
            default => 0,
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

    public function deleteMetric(string $metricKey, int $cutoffTimestamp): int { return 0; }
    public function countMetricsOlderThan(int $cutoffTimestamp): int
    {
        return count(array_filter(
            $this->metrics,
            fn($m) => $m->timestamp->getTimestamp() < $cutoffTimestamp
        ));
    }

    public function getAll(): array { return $this->metrics; }
}

final class SimpleTenantContext implements TenantContextInterface
{
    public function __construct(
        private string $currentTenantId = 'tenant-acme-corp'
    ) {}

    public function getCurrentTenantId(): string
    {
        return $this->currentTenantId;
    }

    public function setCurrentTenantId(string $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    public function hasTenantContext(): bool
    {
        return true;
    }
}

final class InMemoryCardinalityStorage implements CardinalityStorageInterface
{
    private array $storage = [];

    public function add(string $tagKey, string $tagValue): void
    {
        if (!isset($this->storage[$tagKey])) {
            $this->storage[$tagKey] = [];
        }
        $this->storage[$tagKey][$tagValue] = true;
    }

    public function count(string $tagKey): int
    {
        return count($this->storage[$tagKey] ?? []);
    }

    public function reset(string $tagKey): void
    {
        unset($this->storage[$tagKey]);
    }
}

final readonly class SimpleCardinalityGuard implements CardinalityGuardInterface
{
    public function __construct(
        private CardinalityStorageInterface $storage,
        private int $globalLimit = 1000,
        private int $perMetricLimit = 100
    ) {}

    public function checkCardinality(string $tagKey, string $tagValue): bool
    {
        $this->storage->add($tagKey, $tagValue);
        $count = $this->storage->count($tagKey);

        if ($count > $this->perMetricLimit) {
            throw CardinalityLimitExceededException::metricLimit($tagKey, $count, $this->perMetricLimit);
        }

        return true;
    }

    public function getCardinality(string $tagKey): int
    {
        return $this->storage->count($tagKey);
    }

    public function getLimit(string $tagKey): int
    {
        return $this->perMetricLimit;
    }
}

final class ConsoleAlertDispatcher implements AlertDispatcherInterface
{
    public function dispatch(array $context): void
    {
        echo sprintf(
            "🚨 ALERT [%s]: %s\n",
            $context['severity'],
            $context['message']
        );
    }
}

// ============================================================================
// Example Service Using MonitoringAwareTrait
// ============================================================================

final class PaymentService
{
    use MonitoringAwareTrait;

    public function __construct(
        private readonly TelemetryTrackerInterface $telemetry
    ) {
        $this->setTelemetry($telemetry);
    }

    public function processPayment(array $payment): array
    {
        return $this->trackOperation('payment.process', function () use ($payment) {
            // Simulate payment processing
            usleep(100000); // 100ms

            // Record business metrics
            $this->recordIncrement('payments.processed');
            $this->recordGauge('payment.amount', $payment['amount'], tags: [
                'currency' => $payment['currency'],
                'gateway' => $payment['gateway'],
            ]);

            return [
                'id' => 'pay_' . uniqid(),
                'status' => 'success',
                'amount' => $payment['amount'],
            ];
        });
    }

    public function refundPayment(string $paymentId): array
    {
        $result = $this->timeOperation('payment.refund', function () use ($paymentId) {
            usleep(50000); // 50ms
            return ['id' => $paymentId, 'status' => 'refunded'];
        });

        $this->recordIncrement('payments.refunded');

        return $result;
    }
}

// ============================================================================
// Main Example
// ============================================================================

echo "========================================\n";
echo "Nexus Monitoring - Advanced Usage\n";
echo "========================================\n\n";

$logger = new Logger('monitoring');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

$storage = new InMemoryMetricStorage();
$tenantContext = new SimpleTenantContext();
$cardinalityStorage = new InMemoryCardinalityStorage();
$cardinalityGuard = new SimpleCardinalityGuard($cardinalityStorage, globalLimit: 1000, perMetricLimit: 10);

$telemetry = new TelemetryTracker(
    storage: $storage,
    logger: $logger,
    tenantContext: $tenantContext,
    cardinalityGuard: $cardinalityGuard
);

// ============================================================================
// 1. Multi-Tenancy Auto-Tagging
// ============================================================================

echo "--- 1. Multi-Tenancy Auto-Tagging ---\n";

$telemetry->increment('api.requests', tags: ['endpoint' => '/users']);

$allMetrics = $storage->getAll();
$lastMetric = end($allMetrics);

echo sprintf(
    "Metric automatically tagged with tenant_id: %s\n",
    $lastMetric->tags['tenant_id'] ?? 'N/A'
);

// Change tenant
$tenantContext->setCurrentTenantId('tenant-globex-corp');
$telemetry->increment('api.requests', tags: ['endpoint' => '/orders']);

$lastMetric = end($storage->getAll());
echo sprintf(
    "New metric tagged with tenant_id: %s\n",
    $lastMetric->tags['tenant_id'] ?? 'N/A'
);

echo "\n";

// ============================================================================
// 2. Cardinality Protection
// ============================================================================

echo "--- 2. Cardinality Protection ---\n";

// This will work fine (within limit)
for ($i = 1; $i <= 8; $i++) {
    $telemetry->increment('user.action', tags: [
        'action_type' => "action_{$i}",
    ]);
}

echo sprintf(
    "Recorded 8 unique action_type values (limit: %d)\n",
    $cardinalityGuard->getLimit('action_type')
);

// This will trigger cardinality limit exceeded
echo "Attempting to exceed cardinality limit...\n";

try {
    for ($i = 9; $i <= 15; $i++) {
        $telemetry->increment('user.action', tags: [
            'action_type' => "action_{$i}",
        ]);
    }
} catch (CardinalityLimitExceededException $e) {
    echo "✓ Cardinality protection triggered: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// 3. SLO Tracking (Automatic Instrumentation)
// ============================================================================

echo "--- 3. SLO Tracking ---\n";

$wrapper = SLOWrapper::for($telemetry, 'database.query', tags: [
    'table' => 'orders',
]);

$result = $wrapper->execute(function () {
    usleep(50000); // 50ms query
    return ['count' => 42];
});

echo sprintf("Query returned: %d rows\n", $result['count']);

// Find SLO metrics
$sloMetrics = array_filter(
    $storage->getAll(),
    fn($m) => str_contains($m->key, 'database.query')
);

echo sprintf("SLO metrics recorded: %d (timing + counter)\n", count($sloMetrics));

echo "\n";

// ============================================================================
// 4. Alert Evaluation
// ============================================================================

echo "--- 4. Alert Evaluation ---\n";

$dispatcher = new ConsoleAlertDispatcher();
$evaluator = new AlertEvaluator(
    dispatcher: $dispatcher,
    logger: $logger
);

// Trigger an alert
try {
    throw new \RuntimeException("Database connection failed", 500);
} catch (\Throwable $e) {
    $evaluator->evaluate($e, context: [
        'tenant_id' => $tenantContext->getCurrentTenantId(),
        'request_id' => 'req-' . uniqid(),
    ]);
}

echo "\n";

// ============================================================================
// 5. MonitoringAwareTrait Integration
// ============================================================================

echo "--- 5. MonitoringAwareTrait Integration ---\n";

$paymentService = new PaymentService($telemetry);

$payment = [
    'amount' => 1599.99,
    'currency' => 'USD',
    'gateway' => 'stripe',
];

$result = $paymentService->processPayment($payment);

echo sprintf(
    "Payment processed: %s (status: %s)\n",
    $result['id'],
    $result['status']
);

$refund = $paymentService->refundPayment($result['id']);

echo sprintf(
    "Payment refunded: %s (status: %s)\n",
    $refund['id'],
    $refund['status']
);

echo "\n";

// ============================================================================
// 6. Metric Retention and Cleanup
// ============================================================================

echo "--- 6. Metric Retention and Cleanup ---\n";

$retentionPolicy = TimeBasedRetentionPolicy::hours(1);
$retentionService = new MetricRetentionService(
    storage: $storage,
    retentionPolicy: $retentionPolicy,
    logger: $logger
);

$stats = $retentionService->getRetentionStats();

echo sprintf(
    "Retention Policy: %.1f hours (%.1f days)\n",
    $stats['retention_period_seconds'] / 3600,
    $stats['retention_period_days']
);

echo sprintf(
    "Cutoff Date: %s\n",
    $stats['cutoff_date']
);

echo sprintf(
    "Metrics eligible for cleanup: %d\n",
    $stats['metrics_eligible_for_cleanup']
);

// Simulate old metrics (manually set timestamp to 2 hours ago)
$oldMetric = new Metric(
    key: 'old.metric',
    type: \Nexus\Monitoring\ValueObjects\MetricType::COUNTER,
    value: 1.0,
    tags: [],
    timestamp: new \DateTimeImmutable('-2 hours')
);

$storage->store($oldMetric);

echo "Added 1 old metric (2 hours ago)\n";

$updatedStats = $retentionService->getRetentionStats();
echo sprintf(
    "Metrics now eligible for cleanup: %d\n",
    $updatedStats['metrics_eligible_for_cleanup']
);

if ($retentionService->needsCleanup(threshold: 1)) {
    $deleted = $retentionService->pruneExpiredMetrics();
    echo sprintf("Pruned %d expired metrics\n", $deleted);
}

echo "\n";

// ============================================================================
// 7. Summary
// ============================================================================

echo "--- Summary ---\n";

$totalMetrics = count($storage->getAll());
echo sprintf("Total metrics in storage: %d\n", $totalMetrics);

$metricsByKey = [];
foreach ($storage->getAll() as $metric) {
    $metricsByKey[$metric->key] = ($metricsByKey[$metric->key] ?? 0) + 1;
}

echo "Metrics by key:\n";
foreach ($metricsByKey as $key => $count) {
    echo sprintf("  - %s: %d\n", $key, $count);
}

echo "\n";
echo "Tenant distribution:\n";
$tenantMetrics = array_filter(
    $storage->getAll(),
    fn($m) => isset($m->tags['tenant_id'])
);

$tenantCounts = [];
foreach ($tenantMetrics as $metric) {
    $tenant = $metric->tags['tenant_id'];
    $tenantCounts[$tenant] = ($tenantCounts[$tenant] ?? 0) + 1;
}

foreach ($tenantCounts as $tenant => $count) {
    echo sprintf("  - %s: %d metrics\n", $tenant, $count);
}

echo "\n========================================\n";
echo "Advanced example completed!\n";
echo "========================================\n";
