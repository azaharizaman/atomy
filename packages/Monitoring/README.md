# Nexus\Monitoring

[![Tests](https://img.shields.io/badge/tests-188%20passing-success)](./TEST_SUITE_SUMMARY.md)
[![PHP](https://img.shields.io/badge/php-%5E8.3-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue)](./LICENSE)

> **Comprehensive observability package for the Nexus ERP monorepo**  
> Production-grade monitoring with metrics, health checks, alerting, and automated retention.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Core Components](#core-components)
- [Advanced Usage](#advanced-usage)
- [Testing](#testing)
- [Architecture](#architecture)
- [Contributing](#contributing)

---

## Overview

The **Nexus\Monitoring** package provides a complete observability solution for the Nexus ERP system. Built with framework-agnostic design principles, it offers:

- **Telemetry Tracking** - Record metrics with cardinality protection and multi-tenancy support
- **Health Checks** - Monitor system components with built-in checks and custom implementations
- **Alerting** - Evaluate and dispatch alerts with deduplication and severity mapping
- **SLO Tracking** - Automatic Service Level Objective instrumentation
- **Metric Retention** - Automated cleanup with configurable policies
- **Easy Integration** - Trait-based pattern for seamless adoption

### Design Philosophy

1. **Framework Agnostic** - Pure PHP with PSR interfaces
2. **Test-Driven** - 188 tests with 476 assertions (100% passing)
3. **Zero Infrastructure Coupling** - Implementations injected via interfaces
4. **Production Ready** - Battle-tested patterns with comprehensive error handling

---

## Features

### ✅ Metric Tracking

- **Multiple metric types**: Counter, Gauge, Timer, Histogram
- **Cardinality protection**: Prevent tag explosion
- **Sampling support**: Reduce storage costs for high-volume metrics
- **Multi-tenancy**: Automatic tenant context tagging
- **Trace context**: OpenTelemetry-compatible trace/span IDs

### ✅ Health Checks

- **Built-in checks**: Database, Cache, Disk Space, Memory
- **Template method pattern**: Easy custom check creation
- **Priority-based execution**: Critical checks first
- **Timeout handling**: Graceful degradation
- **Result caching**: Configurable TTL per check

### ✅ Alerting

- **Exception-to-severity mapping**: Automatic alert classification
- **Fingerprint deduplication**: Prevent alert storms
- **Time-window deduplication**: Configurable silence periods
- **Metadata enrichment**: Stack traces, exception details
- **Channel dispatching**: Email, SMS, Slack, PagerDuty

### ✅ Utilities

- **SLO Wrapper**: Automatic success/failure/latency tracking
- **Monitoring Trait**: 6 convenience methods for quick integration
- **Custom Exceptions**: Domain-specific error handling
- **Retention Service**: Automated metric cleanup with policies

---

## Installation

### Requirements

- PHP 8.3 or higher
- Composer

### Install via Composer

```bash
composer require nexus/monitoring:"*@dev"
```

*Note: Package is currently in development. Use `@dev` stability flag.*

---

## Quick Start

### 1. Basic Metric Tracking

```php
use Nexus\Monitoring\Services\TelemetryTracker;
use Psr\Log\LoggerInterface;

// Inject dependencies
$tracker = new TelemetryTracker(
    $metricStorage,      // Your storage implementation
    $cardinalityGuard,   // Cardinality protection
    $logger,             // PSR-3 logger
    $tenantContext,      // Optional: multi-tenancy
    $samplingStrategy    // Optional: sampling
);

// Record metrics
$tracker->increment('api.requests', tags: ['endpoint' => '/users']);
$tracker->gauge('memory.usage', 128.5, tags: ['unit' => 'MB']);
$tracker->timing('db.query', 45.2, tags: ['table' => 'users']);
$tracker->histogram('response.size', 1024, tags: ['endpoint' => '/api']);
```

### 2. Health Checks

```php
use Nexus\Monitoring\Services\HealthCheckRunner;
use Nexus\Monitoring\HealthChecks\DatabaseHealthCheck;

// Register health checks
$runner = new HealthCheckRunner($cache, $logger);
$runner->register(new DatabaseHealthCheck($pdo));

// Execute all checks
$results = $runner->runAll();

// Check specific component
$dbHealth = $runner->runCheck('database');
echo $dbHealth->getStatus()->value; // HEALTHY, DEGRADED, OFFLINE, CRITICAL
```

### 3. Easy Integration with Trait

```php
use Nexus\Monitoring\Traits\MonitoringAwareTrait;

class OrderService
{
    use MonitoringAwareTrait;
    
    public function __construct(TelemetryTrackerInterface $telemetry)
    {
        $this->setTelemetry($telemetry);
    }
    
    public function processOrder(Order $order): void
    {
        // Automatic SLO tracking
        $this->trackOperation('order.process', function() use ($order) {
            // Your business logic
            $this->saveOrder($order);
            $this->notifyCustomer($order);
        }, tags: ['payment_method' => $order->paymentMethod]);
        
        // Manual metrics
        $this->recordIncrement('orders.completed');
        $this->recordGauge('orders.value', $order->total);
    }
}
```

### 4. Automated Metric Retention

```php
use Nexus\Monitoring\Services\MetricRetentionService;
use Nexus\Monitoring\Core\TimeBasedRetentionPolicy;

// Create retention policy
$policy = TimeBasedRetentionPolicy::days(30);

$retentionService = new MetricRetentionService(
    $metricStorage,
    $policy,
    $logger
);

// Schedule cleanup (e.g., Laravel)
$schedule->call(function() use ($retentionService) {
    if ($retentionService->needsCleanup(threshold: 10000)) {
        $pruned = $retentionService->pruneExpiredMetrics(batchSize: 1000);
        Log::info("Pruned {$pruned} expired metrics");
    }
})->daily();
```

---

## Core Components

### TelemetryTracker

Records metrics with protection and enrichment.

**Key Features:**
- Cardinality limit enforcement
- Automatic tenant tagging
- OpenTelemetry trace context
- Sampling for high-volume metrics

**Methods:**
```php
increment(string $key, float $value = 1.0, array $tags = []): void
gauge(string $key, float $value, array $tags = []): void
timing(string $key, float $milliseconds, array $tags = []): void
histogram(string $key, float $value, array $tags = []): void
```

### HealthCheckRunner

Orchestrates health checks with intelligent execution.

**Key Features:**
- Priority-based ordering (critical first)
- Timeout protection with circuit breaking
- Result caching with configurable TTL
- Scheduled vs on-demand execution

**Methods:**
```php
register(HealthCheckInterface $check): void
runAll(): array<HealthCheckResult>
runCheck(string $name): HealthCheckResult
```

### AlertEvaluator

Processes exceptions into alerts with deduplication.

**Key Features:**
- Automatic severity mapping
- Fingerprint-based deduplication
- Time-window silence periods
- Metadata enrichment (stack traces)

**Methods:**
```php
evaluate(\Throwable $exception, array $context = []): void
```

### MetricRetentionService

Manages metric lifecycle and cleanup.

**Key Features:**
- Policy-driven retention (time-based, tiered)
- Batch pruning with size limits
- Threshold-based automatic cleanup
- Comprehensive statistics

**Methods:**
```php
pruneExpiredMetrics(?int $batchSize = null): int
pruneMetric(string $metricKey): int
getRetentionStats(): array
needsCleanup(int $threshold = 1000): bool
```

---

## Advanced Usage

### Custom Health Check

```php
use Nexus\Monitoring\HealthChecks\AbstractHealthCheck;
use Nexus\Monitoring\ValueObjects\HealthStatus;

class RedisHealthCheck extends AbstractHealthCheck
{
    public function __construct(
        private readonly \Redis $redis,
        string $name = 'redis',
        int $priority = 8,
        int $timeoutSeconds = 3,
        int $cacheTtlSeconds = 60
    ) {
        parent::__construct($name, $priority, $timeoutSeconds, $cacheTtlSeconds);
    }
    
    protected function performCheck(): HealthStatus
    {
        $startTime = microtime(true);
        
        // Test connectivity
        $this->redis->ping();
        
        // Test read/write
        $testKey = 'health_check_' . uniqid();
        $this->redis->set($testKey, '1', 10);
        $this->redis->get($testKey);
        $this->redis->del($testKey);
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        return match(true) {
            $duration > 500 => $this->degraded("Slow response: {$duration}ms"),
            default => $this->healthy()
        };
    }
}
```

### Custom Retention Policy

```php
use Nexus\Monitoring\Contracts\MetricRetentionInterface;

class TieredRetentionPolicy implements MetricRetentionInterface
{
    public function __construct(
        private readonly array $tiers = [
            'critical.*' => 86400 * 90,  // 90 days
            'business.*' => 86400 * 30,  // 30 days
            'debug.*' => 86400 * 7,      // 7 days
        ],
        private readonly int $defaultPeriod = 86400 * 14
    ) {}
    
    public function getRetentionPeriod(): int
    {
        return $this->defaultPeriod;
    }
    
    public function shouldRetain(string $metricKey, int $timestamp): bool
    {
        foreach ($this->tiers as $pattern => $period) {
            if (fnmatch($pattern, $metricKey)) {
                return $timestamp >= (time() - $period);
            }
        }
        
        return $timestamp >= (time() - $this->defaultPeriod);
    }
}
```

### SLO Tracking Pattern

```php
use Nexus\Monitoring\Core\SLOWrapper;

class PaymentGateway
{
    public function charge(Payment $payment): void
    {
        $wrapper = SLOWrapper::for(
            $this->telemetry,
            'payment.charge',
            tags: ['gateway' => $payment->gateway]
        );
        
        // Automatically tracks:
        // - slo.payment.charge.success (on success)
        // - slo.payment.charge.failure (on exception)
        // - slo.payment.charge.latency (always)
        // - slo.payment.charge.total (always)
        $wrapper->execute(function() use ($payment) {
            return $this->gateway->processPayment($payment);
        });
    }
}
```

---

## Testing

### Run All Tests

```bash
cd packages/Monitoring
vendor/bin/phpunit
```

### Run Specific Test Suite

```bash
# Services only
vendor/bin/phpunit tests/Unit/Services/

# Health checks only
vendor/bin/phpunit tests/Unit/Core/

# With coverage (requires xdebug)
vendor/bin/phpunit --coverage-html coverage-report
```

### Test Statistics

- **Total Tests:** 188
- **Total Assertions:** 476
- **Coverage:** Comprehensive (all production code tested)
- **Runtime:** ~2 seconds
- **Status:** ✅ All passing

See [TEST_SUITE_SUMMARY.md](./TEST_SUITE_SUMMARY.md) for detailed breakdown.

---

## Architecture

### Package Structure

```
packages/Monitoring/
├── src/
│   ├── Contracts/           # 15 interfaces (framework-agnostic)
│   ├── Services/            # 4 core services
│   ├── HealthChecks/        # Built-in health checks
│   ├── Core/                # Utilities (SLOWrapper, Policies)
│   ├── ValueObjects/        # Immutable data objects
│   ├── Exceptions/          # 5 custom exceptions
│   └── Traits/              # MonitoringAwareTrait
├── tests/
│   └── Unit/                # 188 comprehensive tests
├── composer.json
├── phpunit.xml
└── README.md
```

### Key Design Patterns

1. **Dependency Injection** - All dependencies via constructor
2. **Interface Segregation** - Small, focused contracts
3. **Template Method** - AbstractHealthCheck pattern
4. **Strategy Pattern** - Pluggable sampling, retention policies
5. **Decorator Pattern** - SLOWrapper for cross-cutting concerns
6. **Trait Composition** - MonitoringAwareTrait for easy integration

### Framework Integration

The package is **framework-agnostic** by design. Integration examples:

**Laravel:**
```php
// app/Providers/MonitoringServiceProvider.php
$this->app->singleton(MetricStorageInterface::class, RedisMetricStorage::class);
$this->app->singleton(TelemetryTrackerInterface::class, TelemetryTracker::class);
```

**Symfony:**
```yaml
# config/services.yaml
services:
  Nexus\Monitoring\Contracts\MetricStorageInterface:
    class: App\Infrastructure\RedisMetricStorage
  
  Nexus\Monitoring\Services\TelemetryTracker:
    autowire: true
```

---

## Contributing

### Development Setup

```bash
# Clone monorepo
git clone https://github.com/azaharizaman/atomy.git
cd atomy/packages/Monitoring

# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run static analysis (if configured)
vendor/bin/phpstan analyse
```

### Coding Standards

- **PHP Version:** 8.3+
- **Style Guide:** PSR-12
- **Type Safety:** Strict types enabled (`declare(strict_types=1)`)
- **Modern PHP:** Property promotion, readonly properties, enums
- **Testing:** TDD approach with comprehensive coverage

### Pull Request Process

1. Create feature branch from `main`
2. Write tests first (TDD)
3. Implement feature with strict types
4. Ensure all tests pass
5. Update documentation
6. Submit PR with clear description

---

## Documentation

- **[Architecture](../../ARCHITECTURE.md)** - Monorepo design principles
- **[Implementation Summary](../../docs/MONITORING_IMPLEMENTATION_SUMMARY.md)** - Detailed feature breakdown
- **[Test Suite Summary](./TEST_SUITE_SUMMARY.md)** - Test coverage details
- **[Metric Retention Guide](./METRIC_RETENTION_IMPLEMENTATION.md)** - Retention policies

---

## License

MIT License - see [LICENSE](./LICENSE) file for details.

---

## Roadmap

### Completed ✅

- [x] TelemetryTracker with cardinality protection
- [x] HealthCheckRunner with priority-based execution
- [x] AlertEvaluator with deduplication
- [x] Built-in health checks (Database, Cache, DiskSpace, Memory)
- [x] SLOWrapper utility
- [x] MonitoringAwareTrait
- [x] Custom exceptions with factory methods
- [x] MetricRetentionService with time-based policies

### Planned 🎯

- [ ] Additional health checks (Queue, Storage, External APIs)
- [ ] Metric aggregation service (hourly → daily → monthly)
- [ ] Dashboard data export (Grafana, Prometheus format)
- [ ] Anomaly detection with ML
- [ ] Distributed tracing integration
- [ ] Custom alert channels (Teams, Discord, Telegram)

---

## Support

For issues, questions, or contributions:

- **Issues:** [GitHub Issues](https://github.com/azaharizaman/atomy/issues)
- **Discussions:** [GitHub Discussions](https://github.com/azaharizaman/atomy/discussions)
- **Documentation:** [docs/](../../docs/)

---

**Built with ❤️ for the Nexus ERP ecosystem**
