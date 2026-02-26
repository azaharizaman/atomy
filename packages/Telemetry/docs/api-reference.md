# API Reference: Monitoring

## Core Interfaces

### TelemetryTrackerInterface

**Purpose:** Primary interface for recording real-time performance metrics.

**Location:** `src/Contracts/TelemetryTrackerInterface.php`

**Methods:**

```php
/**
 * Record an instantaneous numerical value (point-in-time).
 * Use for metrics that can go up or down (e.g., queue size, memory usage).
 */
public function gauge(
    string $key,
    float $value,
    array $tags = [],
    ?string $traceId = null,
    ?string $spanId = null
): void;

/**
 * Increment a counter by a specific amount.
 * Use for monotonically increasing values (e.g., total requests).
 */
public function increment(
    string $key,
    float $value = 1.0,
    array $tags = [],
    ?string $traceId = null,
    ?string $spanId = null
): void;

/**
 * Record a duration measurement in milliseconds.
 * Use for latency tracking (e.g., API response time, database query time).
 */
public function timing(
    string $key,
    float $milliseconds,
    array $tags = [],
    ?string $traceId = null,
    ?string $spanId = null
): void;

/**
 * Record a value for distribution analysis.
 * Use for histograms (e.g., order values, response sizes).
 */
public function histogram(
    string $key,
    float $value,
    array $tags = [],
    ?string $traceId = null,
    ?string $spanId = null
): void;
```

**Example:**

```php
$telemetry->increment('api.requests', tags: ['endpoint' => '/users']);
$telemetry->gauge('memory.usage', 128.5, tags: ['unit' => 'MB']);
$telemetry->timing('db.query', 45.2, tags: ['table' => 'orders']);
$telemetry->histogram('order.value', 1599.99, tags: ['currency' => 'USD']);
```

---

### HealthCheckerInterface

**Purpose:** Orchestrate health checks with priority execution and result aggregation.

**Location:** `src/Contracts/HealthCheckerInterface.php`

**Methods:**

```php
/**
 * Register a health check.
 */
public function register(HealthCheckInterface $check): void;

/**
 * Run all registered health checks in priority order.
 * Returns array of HealthCheckResult objects.
 *
 * @return array<HealthCheckResult>
 */
public function runAll(): array;

/**
 * Run a specific health check by name.
 *
 * @throws HealthCheckNotFoundException
 */
public function runCheck(string $name): HealthCheckResult;

/**
 * Get overall system health (worst status from all checks).
 */
public function getOverallHealth(): HealthStatus;
```

**Example:**

```php
$runner->register(new DatabaseHealthCheck($pdo));
$runner->register(new CacheHealthCheck($cache));

$results = $runner->runAll();
$overallHealth = $runner->getOverallHealth();

if ($overallHealth === HealthStatus::CRITICAL) {
    // Trigger incident response
}
```

---

### AlertGatewayInterface

**Purpose:** Evaluate exceptions and dispatch alerts with deduplication.

**Location:** `src/Contracts/AlertGatewayInterface.php`

**Methods:**

```php
/**
 * Evaluate an exception and dispatch alert if needed.
 * Automatically maps exception to severity and deduplicates.
 *
 * @param \Throwable $exception The exception to evaluate
 * @param array $context Additional context (user_id, request_id, etc.)
 */
public function evaluate(\Throwable $exception, array $context = []): void;
```

**Example:**

```php
try {
    $this->processPayment($payment);
} catch (\Throwable $e) {
    $evaluator->evaluate($e, context: [
        'payment_id' => $payment->id,
        'user_id' => $payment->userId,
    ]);
    throw $e;
}
```

---

### MetricStorageInterface

**Purpose:** Abstract persistence layer for metrics (TSDB adapter).

**Location:** `src/Contracts/MetricStorageInterface.php`

**Methods:**

```php
/**
 * Store a metric.
 */
public function store(Metric $metric): void;

/**
 * Query metrics by name and time range.
 *
 * @return array<Metric>
 */
public function query(QuerySpec $spec): array;

/**
 * Aggregate metrics using a function (AVG, SUM, MIN, MAX, COUNT, etc.).
 *
 * @throws UnsupportedAggregationException
 */
public function aggregate(AggregationSpec $spec): float|int;

/**
 * Delete metrics older than cutoff timestamp.
 * Returns count of deleted metrics.
 */
public function deleteMetricsOlderThan(int $cutoffTimestamp, ?int $batchSize = null): int;

/**
 * Delete specific metric older than cutoff timestamp.
 */
public function deleteMetric(string $metricKey, int $cutoffTimestamp): int;

/**
 * Count metrics eligible for cleanup.
 */
public function countMetricsOlderThan(int $cutoffTimestamp): int;
```

**Implementation Examples:**
- Prometheus HTTP API adapter
- InfluxDB client adapter
- Database (MySQL/PostgreSQL) repository
- Redis time-series adapter

---

### MetricRetentionInterface

**Purpose:** Define retention policies for automatic metric cleanup.

**Location:** `src/Contracts/MetricRetentionInterface.php`

**Methods:**

```php
/**
 * Get retention period in seconds.
 */
public function getRetentionPeriod(): int;

/**
 * Determine if a specific metric should be retained.
 *
 * @param string $metricKey The metric name
 * @param int $timestamp The metric timestamp (Unix seconds)
 * @return bool True if should be retained
 */
public function shouldRetain(string $metricKey, int $timestamp): bool;
```

**Built-in Implementation:**

```php
use Nexus\Telemetry\Core\TimeBasedRetentionPolicy;

// 30 days
$policy = TimeBasedRetentionPolicy::days(30);

// 72 hours
$policy = TimeBasedRetentionPolicy::hours(72);
```

**Custom Implementation Example:**

```php
final readonly class MetricTypeRetentionPolicy implements MetricRetentionInterface
{
    public function getRetentionPeriod(): int
    {
        return 90 * 86400; // 90 days default
    }
    
    public function shouldRetain(string $metricKey, int $timestamp): bool
    {
        $age = time() - $timestamp;
        
        return match (true) {
            str_starts_with($metricKey, 'counter') => $age < (90 * 86400),
            str_starts_with($metricKey, 'gauge') => $age < (30 * 86400),
            str_starts_with($metricKey, 'histogram') => $age < (7 * 86400),
            default => $age < (90 * 86400),
        };
    }
}
```

---

### CardinalityGuardInterface

**Purpose:** Protect against unbounded tag cardinality (prevent TSDB cost explosions).

**Location:** `src/Contracts/CardinalityGuardInterface.php`

**Methods:**

```php
/**
 * Check if adding a tag value would exceed cardinality limit.
 *
 * @param string $tagKey The tag key (e.g., 'user_id')
 * @param string $tagValue The tag value (e.g., 'user-12345')
 * @return bool True if safe to add
 */
public function checkCardinality(string $tagKey, string $tagValue): bool;

/**
 * Get current unique value count for a tag key.
 */
public function getCardinality(string $tagKey): int;

/**
 * Get configured limit for a tag key.
 */
public function getLimit(string $tagKey): int;
```

**Example:**

```php
if (!$cardinalityGuard->checkCardinality('user_id', $userId)) {
    // Log warning, skip metric, or use fallback tag
    $tags['user_id'] = 'HIGH_CARDINALITY_TRUNCATED';
}
```

---

### CardinalityStorageInterface

**Purpose:** Store cardinality state (typically Redis HyperLogLog).

**Location:** `src/Contracts/CardinalityStorageInterface.php`

**Methods:**

```php
/**
 * Add a tag value to the cardinality tracker.
 */
public function add(string $tagKey, string $tagValue): void;

/**
 * Get estimated unique value count for a tag key.
 */
public function count(string $tagKey): int;

/**
 * Reset cardinality tracking for a tag key.
 */
public function reset(string $tagKey): void;
```

**Redis Implementation Example:**

```php
final readonly class RedisCardinalityStorage implements CardinalityStorageInterface
{
    public function __construct(
        private \Redis $redis
    ) {}
    
    public function add(string $tagKey, string $tagValue): void
    {
        $this->redis->pfAdd("cardinality:{$tagKey}", [$tagValue]);
    }
    
    public function count(string $tagKey): int
    {
        return (int) $this->redis->pfCount("cardinality:{$tagKey}");
    }
    
    public function reset(string $tagKey): void
    {
        $this->redis->del("cardinality:{$tagKey}");
    }
}
```

---

### AlertDispatcherInterface

**Purpose:** Dispatch alerts to notification channels.

**Location:** `src/Contracts/AlertDispatcherInterface.php`

**Methods:**

```php
/**
 * Dispatch an alert to configured channels.
 *
 * @param array $context Alert context (severity, message, exception, etc.)
 */
public function dispatch(array $context): void;
```

**Example Implementation (Multi-Channel):**

```php
final readonly class MultiChannelAlertDispatcher implements AlertDispatcherInterface
{
    public function __construct(
        private SlackNotifier $slack,
        private PagerDutyNotifier $pagerDuty,
        private EmailNotifier $email,
        private LoggerInterface $logger
    ) {}
    
    public function dispatch(array $context): void
    {
        $severity = $context['severity'];
        
        // Always log
        $this->logger->warning('Alert dispatched', $context);
        
        // Slack for all alerts
        $this->slack->send($context['message'], $context);
        
        // PagerDuty only for CRITICAL
        if ($severity === 'CRITICAL') {
            $this->pagerDuty->createIncident($context);
        }
        
        // Email for WARNING and CRITICAL
        if (in_array($severity, ['WARNING', 'CRITICAL'])) {
            $this->email->send($context);
        }
    }
}
```

---

### HealthCheckInterface

**Purpose:** Contract for individual health checks.

**Location:** `src/Contracts/HealthCheckInterface.php`

**Methods:**

```php
/**
 * Get unique name for this health check.
 */
public function getName(): string;

/**
 * Execute the health check and return result.
 */
public function check(): HealthCheckResult;

/**
 * Get timeout in milliseconds (default: 5000ms).
 */
public function getTimeout(): int;

/**
 * Is this check critical? Critical checks run first and fail-fast.
 */
public function isCritical(): bool;

/**
 * Get execution priority (higher = runs earlier).
 * Critical checks automatically get priority 10.
 */
public function getPriority(): int;
```

**Example Custom Health Check:**

```php
final readonly class ApiHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}
    
    public function getName(): string
    {
        return 'external_api';
    }
    
    public function check(): HealthCheckResult
    {
        $start = hrtime(true);
        
        try {
            $response = $this->httpClient->get('https://api.example.com/health');
            $durationMs = (hrtime(true) - $start) / 1_000_000;
            
            return new HealthCheckResult(
                name: $this->getName(),
                status: $response->getStatusCode() === 200 
                    ? HealthStatus::HEALTHY 
                    : HealthStatus::DEGRADED,
                message: "API responded with {$response->getStatusCode()}",
                responseTimeMs: $durationMs
            );
        } catch (\Throwable $e) {
            $durationMs = (hrtime(true) - $start) / 1_000_000;
            
            return new HealthCheckResult(
                name: $this->getName(),
                status: HealthStatus::CRITICAL,
                message: "API unreachable: {$e->getMessage()}",
                responseTimeMs: $durationMs
            );
        }
    }
    
    public function getTimeout(): int
    {
        return 10000; // 10 seconds
    }
    
    public function isCritical(): bool
    {
        return false;
    }
    
    public function getPriority(): int
    {
        return 5;
    }
}
```

---

### ScheduledHealthCheckInterface

**Purpose:** Health checks that should run on a schedule (background jobs).

**Location:** `src/Contracts/ScheduledHealthCheckInterface.php`

**Extends:** `HealthCheckInterface`

**Additional Methods:**

```php
/**
 * Get cron expression for scheduled execution.
 * Examples: '*/5 * * * *' (every 5 minutes), '0 * * * *' (hourly)
 */
public function getSchedule(): string;
```

**Example:**

```php
final readonly class QueueHealthCheck implements ScheduledHealthCheckInterface
{
    public function getSchedule(): string
    {
        return '*/10 * * * *'; // Every 10 minutes
    }
    
    // ... implement other HealthCheckInterface methods
}
```

---

### SamplingStrategyInterface

**Purpose:** Define metric sampling logic to reduce TSDB write load.

**Location:** `src/Contracts/SamplingStrategyInterface.php`

**Methods:**

```php
/**
 * Determine if a metric should be sampled (stored).
 *
 * @param Metric $metric The metric to evaluate
 * @return bool True if should be stored
 */
public function shouldSample(Metric $metric): bool;
```

**Example Implementations:**

```php
// Probabilistic: 10% sample rate
final readonly class ProbabilisticSampling implements SamplingStrategyInterface
{
    public function __construct(
        private float $sampleRate = 0.1
    ) {}
    
    public function shouldSample(Metric $metric): bool
    {
        return mt_rand(0, 99) < ($this->sampleRate * 100);
    }
}

// Hash-based deterministic: Sample based on hash of metric key
final readonly class DeterministicSampling implements SamplingStrategyInterface
{
    public function __construct(
        private int $modulo = 10
    ) {}
    
    public function shouldSample(Metric $metric): bool
    {
        return (crc32($metric->key) % $this->modulo) === 0;
    }
}

// Adaptive: Sample low-cardinality metrics less
final readonly class AdaptiveSampling implements SamplingStrategyInterface
{
    public function __construct(
        private CardinalityStorageInterface $storage
    ) {}
    
    public function shouldSample(Metric $metric): bool
    {
        $cardinality = $this->storage->count($metric->key);
        
        return match (true) {
            $cardinality < 100 => true,        // Always sample low-cardinality
            $cardinality < 1000 => mt_rand(0, 9) < 5,  // 50% for medium
            default => mt_rand(0, 9) < 1,      // 10% for high-cardinality
        };
    }
}
```

---

### SLOConfigurationInterface

**Purpose:** Define Service Level Objective thresholds per operation.

**Location:** `src/Contracts/SLOConfigurationInterface.php`

**Methods:**

```php
/**
 * Get SLO threshold in milliseconds for an operation.
 * Returns null if no SLO configured (no threshold enforcement).
 *
 * @param string $operation The operation name (e.g., 'payment.charge')
 * @return float|null Threshold in milliseconds or null
 */
public function getThreshold(string $operation): ?float;
```

**Example:**

```php
final readonly class FileSLOConfiguration implements SLOConfigurationInterface
{
    private array $thresholds;
    
    public function __construct(string $configPath)
    {
        $this->thresholds = json_decode(
            file_get_contents($configPath),
            true
        );
    }
    
    public function getThreshold(string $operation): ?float
    {
        return $this->thresholds[$operation] ?? null;
    }
}

// config/slo.json
{
    "payment.charge": 3000,     // 3 seconds
    "order.process": 5000,      // 5 seconds
    "report.generate": 30000    // 30 seconds
}
```

---

### CacheRepositoryInterface

**Purpose:** Abstract caching layer for health check results.

**Location:** `src/Contracts/CacheRepositoryInterface.php`

**Methods:**

```php
/**
 * Get cached value.
 *
 * @return mixed|null Cached value or null if not found
 */
public function get(string $key): mixed;

/**
 * Store value in cache with TTL.
 *
 * @param int $ttl Time-to-live in seconds
 */
public function set(string $key, mixed $value, int $ttl): void;

/**
 * Check if key exists in cache.
 */
public function has(string $key): bool;

/**
 * Delete cached value.
 */
public function delete(string $key): void;
```

---

## Value Objects

### Metric

**Purpose:** Immutable metric data container.

**Location:** `src/ValueObjects/Metric.php`

**Properties:**

```php
final readonly class Metric
{
    public function __construct(
        public string $key,              // Metric name
        public MetricType $type,         // COUNTER, GAUGE, TIMING, HISTOGRAM
        public float $value,             // Numeric value
        public array $tags,              // Contextual tags
        public \DateTimeImmutable $timestamp, // Microsecond precision
        public ?string $traceId = null,  // OpenTelemetry trace ID
        public ?string $spanId = null    // OpenTelemetry span ID
    ) {}
}
```

---

### HealthCheckResult

**Purpose:** Health check execution result.

**Location:** `src/ValueObjects/HealthCheckResult.php`

**Properties:**

```php
final readonly class HealthCheckResult
{
    public function __construct(
        public string $name,                // Check name
        public HealthStatus $status,        // HEALTHY, WARNING, DEGRADED, CRITICAL, OFFLINE
        public string $message,             // Human-readable message
        public float $responseTimeMs,       // Execution time
        public array $metadata = []         // Additional context
    ) {}
}
```

---

### QuerySpec

**Purpose:** Metric query specification.

**Location:** `src/ValueObjects/QuerySpec.php`

**Properties:**

```php
final readonly class QuerySpec
{
    public function __construct(
        public string $metricName,
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
        public array $tags = [],
        public ?int $limit = null,
        public ?string $orderBy = null
    ) {}
}
```

---

### AggregationSpec

**Purpose:** Metric aggregation specification.

**Location:** `src/ValueObjects/AggregationSpec.php`

**Properties:**

```php
final readonly class AggregationSpec
{
    public function __construct(
        public string $metricName,
        public string $function,             // AVG, SUM, MIN, MAX, COUNT, P50, P95, P99
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
        public array $groupBy = []
    ) {}
}
```

---

## Enums

### MetricType

```php
enum MetricType: string
{
    case COUNTER = 'counter';      // Monotonically increasing
    case GAUGE = 'gauge';          // Point-in-time value
    case TIMING = 'timing';        // Duration in milliseconds
    case HISTOGRAM = 'histogram';  // Distribution of values
}
```

### HealthStatus

```php
enum HealthStatus: string
{
    case HEALTHY = 'healthy';      // Normal operation
    case WARNING = 'warning';      // Minor degradation
    case DEGRADED = 'degraded';    // Significant degradation
    case CRITICAL = 'critical';    // Major failure
    case OFFLINE = 'offline';      // Complete failure
    
    public function weight(): int; // 0, 25, 50, 75, 100
}
```

### AlertSeverity

```php
enum AlertSeverity: string
{
    case INFO = 'info';            // Informational
    case WARNING = 'warning';      // Non-critical issue
    case CRITICAL = 'critical';    // Critical issue
}
```

---

## Exceptions

### MonitoringException

**Base exception with context support.**

```php
final class MonitoringException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
```

### CardinalityLimitExceededException

```php
final class CardinalityLimitExceededException extends MonitoringException
{
    public static function globalLimit(int $currentCount, int $limit): self;
    public static function metricLimit(string $metricKey, int $currentCount, int $limit): self;
}
```

### HealthCheckFailedException

```php
final class HealthCheckFailedException extends MonitoringException
{
    public static function forCheck(string $checkName, string $reason): self;
    public static function timeout(string $checkName, int $timeoutMs): self;
}
```

### InvalidMetricException

```php
final class InvalidMetricException extends MonitoringException
{
    public static function invalidName(string $name): self;
    public static function invalidValue(float $value): self;
    public static function invalidTags(array $tags): self;
}
```

### AlertDispatchException

```php
final class AlertDispatchException extends MonitoringException
{
    public static function dispatchFailed(string $reason): self;
    public static function noChannelsConfigured(): self;
}
```

### UnsupportedAggregationException

```php
final class UnsupportedAggregationException extends MonitoringException
{
    public function __construct(string $function) {
        parent::__construct("Aggregation function '{$function}' is not supported by the TSDB backend");
    }
}
```

---

## Traits

### MonitoringAwareTrait

**Purpose:** Convenience methods for services that use monitoring.

**Location:** `src/Traits/MonitoringAwareTrait.php`

**Methods:**

```php
public function setTelemetry(?TelemetryTrackerInterface $telemetry): void;
public function getTelemetry(): ?TelemetryTrackerInterface;
public function recordIncrement(string $key, array $tags = []): void;
public function recordGauge(string $key, float $value, array $tags = []): void;
public function recordTiming(string $key, float $milliseconds, array $tags = []): void;
public function recordHistogram(string $key, float $value, array $tags = []): void;
public function trackOperation(string $operation, callable $callback, array $tags = []): mixed;
public function timeOperation(string $operation, callable $callback): mixed;
```

---

## Services

### TelemetryTracker

**Implementation:** `src/Services/TelemetryTracker.php`

**Constructor:**

```php
public function __construct(
    private readonly MetricStorageInterface $storage,
    private readonly LoggerInterface $logger,
    private readonly ?TenantContextInterface $tenantContext = null,
    private readonly ?CardinalityGuardInterface $cardinalityGuard = null,
    private readonly ?SamplingStrategyInterface $samplingStrategy = null
);
```

### HealthCheckRunner

**Implementation:** `src/Services/HealthCheckRunner.php`

**Constructor:**

```php
public function __construct(
    private readonly LoggerInterface $logger,
    private readonly ?CacheRepositoryInterface $cache = null
);
```

### AlertEvaluator

**Implementation:** `src/Services/AlertEvaluator.php`

**Constructor:**

```php
public function __construct(
    private readonly AlertDispatcherInterface $dispatcher,
    private readonly LoggerInterface $logger,
    private readonly ?CacheRepositoryInterface $cache = null,
    private readonly int $deduplicationWindow = 300
);
```

### MetricRetentionService

**Implementation:** `src/Services/MetricRetentionService.php`

**Constructor:**

```php
public function __construct(
    private readonly MetricStorageInterface $storage,
    private readonly MetricRetentionInterface $retentionPolicy,
    private readonly LoggerInterface $logger
);
```

---

## Core Utilities

### SLOWrapper

**Purpose:** Automatic SLO instrumentation.

**Location:** `src/Core/SLOWrapper.php`

**Static Factory:**

```php
public static function for(
    TelemetryTrackerInterface $telemetry,
    string $operation,
    array $tags = [],
    ?SLOConfigurationInterface $sloConfig = null
): self;
```

**Execute Method:**

```php
public function execute(callable $operation): mixed;
```

### TimeBasedRetentionPolicy

**Purpose:** Simple time-based retention.

**Location:** `src/Core/TimeBasedRetentionPolicy.php`

**Static Factories:**

```php
public static function days(int $days): self;
public static function hours(int $hours): self;
```

---

**Last Updated:** 2025-01-25  
**Package Version:** 1.0.0
