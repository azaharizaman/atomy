# Metric Retention Service Implementation

**Package:** `Nexus\Telemetry`  
**Component:** MetricRetentionService  
**Status:** ✅ Complete (21 tests, 77 assertions)  
**Last Updated:** January 2025

---

## Overview

The `MetricRetentionService` provides automated metric lifecycle management with policy-driven cleanup. It manages the deletion of expired metrics based on configurable retention periods while maintaining comprehensive audit logs.

---

## Architecture

### Core Components

1. **MetricRetentionService**
   - Location: `src/Services/MetricRetentionService.php`
   - Purpose: Orchestrates metric cleanup operations
   - Dependencies:
     - `MetricStorageInterface` - Storage backend
     - `MetricRetentionInterface` - Retention policy
     - `LoggerInterface` - Audit logging

2. **MetricRetentionInterface**
   - Location: `src/Contracts/MetricRetentionInterface.php`
   - Purpose: Defines retention policy contract
   - Methods:
     - `getRetentionPeriod(): int` - Get period in seconds
     - `shouldRetain(string $metricKey, int $timestamp): bool` - Check retention

3. **TimeBasedRetentionPolicy**
   - Location: `src/Core/TimeBasedRetentionPolicy.php`
   - Purpose: Simple time-based retention implementation
   - Factories:
     - `TimeBasedRetentionPolicy::days(int $days)`
     - `TimeBasedRetentionPolicy::hours(int $hours)`

### Extended Interface

**MetricStorageInterface** additions:
```php
// Delete metrics older than timestamp
public function deleteMetricsOlderThan(int $cutoffTimestamp, ?int $batchSize = null): int;

// Delete specific metric older than timestamp
public function deleteMetric(string $metricKey, int $cutoffTimestamp): int;

// Count metrics eligible for cleanup
public function countMetricsOlderThan(int $cutoffTimestamp): int;
```

---

## Features

### 1. Automated Cleanup

**Batch Pruning:**
```php
use Nexus\Telemetry\Services\MetricRetentionService;

// Prune all expired metrics
$prunedCount = $retentionService->pruneExpiredMetrics();

// Prune with batch size limit
$prunedCount = $retentionService->pruneExpiredMetrics(batchSize: 1000);
```

**Metric-Specific Cleanup:**
```php
// Prune specific metric
$prunedCount = $retentionService->pruneMetric('api.requests');
```

### 2. Retention Statistics

**Get Cleanup Metrics:**
```php
$stats = $retentionService->getRetentionStats();

// Returns:
// [
//   'retention_period_seconds' => 2592000,
//   'retention_period_days' => 30.0,
//   'cutoff_timestamp' => 1704067200,
//   'cutoff_date' => '2024-01-01 00:00:00',
//   'metrics_eligible_for_cleanup' => 1500
// ]
```

### 3. Conditional Cleanup

**Threshold-Based Execution:**
```php
// Check if cleanup needed (default threshold: 1000)
if ($retentionService->needsCleanup()) {
    $retentionService->pruneExpiredMetrics();
}

// Custom threshold
if ($retentionService->needsCleanup(threshold: 5000)) {
    $retentionService->pruneExpiredMetrics(batchSize: 2000);
}
```

### 4. Comprehensive Logging

All operations are logged:

**Starting Cleanup:**
```
[info] Starting metric retention cleanup
  - cutoff_timestamp: 1704067200
  - cutoff_date: 2024-01-01 00:00:00
  - batch_size: 1000
```

**Completion:**
```
[info] Metric retention cleanup completed
  - pruned_count: 1500
  - cutoff_timestamp: 1704067200
```

**Errors:**
```
[error] Metric retention cleanup failed
  - error: Storage connection lost
  - exception: RuntimeException
```

---

## Policy Configuration

### Time-Based Policy

**Daily Retention:**
```php
use Nexus\Telemetry\Core\TimeBasedRetentionPolicy;

// Retain for 30 days
$policy = TimeBasedRetentionPolicy::days(30);

$service = new MetricRetentionService(
    $storage,
    $policy,
    $logger
);
```

**Hourly Retention:**
```php
// Retain for 72 hours
$policy = TimeBasedRetentionPolicy::hours(72);
```

**Custom Seconds:**
```php
// Retain for 1 week (604800 seconds)
$policy = new TimeBasedRetentionPolicy(604800);
```

### Custom Policy

Implement `MetricRetentionInterface`:

```php
use Nexus\Telemetry\Contracts\MetricRetentionInterface;

final readonly class TieredRetentionPolicy implements MetricRetentionInterface
{
    public function __construct(
        private array $tiers // ['api.*' => 86400 * 7, 'cache.*' => 86400]
    ) {}

    public function getRetentionPeriod(): int
    {
        // Return default period
        return 86400 * 30;
    }

    public function shouldRetain(string $metricKey, int $timestamp): bool
    {
        foreach ($this->tiers as $pattern => $period) {
            if (fnmatch($pattern, $metricKey)) {
                return $timestamp >= (time() - $period);
            }
        }
        
        return $timestamp >= (time() - $this->getRetentionPeriod());
    }
}
```

---

## Integration Patterns

### Laravel Scheduled Task

**Command:**
```php
namespace App\Console\Commands;

use Nexus\Telemetry\Services\MetricRetentionService;

class PruneMetrics extends Command
{
    protected $signature = 'metrics:prune {--batch-size=1000}';
    
    public function handle(MetricRetentionService $service): int
    {
        $this->info('Starting metric retention cleanup...');
        
        $batchSize = (int) $this->option('batch-size');
        $prunedCount = $service->pruneExpiredMetrics($batchSize);
        
        $this->info("Pruned {$prunedCount} expired metrics");
        
        return self::SUCCESS;
    }
}
```

**Scheduler:**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Run daily at 2 AM
    $schedule->command('metrics:prune')->dailyAt('02:00');
    
    // Run every 6 hours
    $schedule->command('metrics:prune --batch-size=500')->everySixHours();
}
```

### Conditional Background Job

```php
use Nexus\Telemetry\Services\MetricRetentionService;

class CheckMetricRetention
{
    public function __construct(
        private MetricRetentionService $retentionService
    ) {}
    
    public function handle(): void
    {
        // Only run cleanup if needed
        if (!$this->retentionService->needsCleanup(threshold: 10000)) {
            return;
        }
        
        // Dispatch background job
        PruneExpiredMetrics::dispatch($this->retentionService);
    }
}
```

---

## Error Handling

### Storage Failures

```php
try {
    $prunedCount = $retentionService->pruneExpiredMetrics();
} catch (\RuntimeException $e) {
    // Storage backend error
    Log::critical('Metric pruning failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Alert operations team
    $alertService->dispatch(
        AlertSeverity::CRITICAL,
        'Metric retention service failure'
    );
}
```

### Graceful Degradation

```php
// Use try-catch to continue operations
foreach ($criticalMetrics as $metricKey) {
    try {
        $retentionService->pruneMetric($metricKey);
    } catch (\Throwable $e) {
        // Log but continue with next metric
        Log::warning("Failed to prune {$metricKey}", [
            'error' => $e->getMessage()
        ]);
    }
}
```

---

## Performance Considerations

### Batch Processing

**Large Datasets:**
```php
// Process in smaller batches to avoid memory issues
$batchSize = 500;
$totalPruned = 0;

while ($retentionService->needsCleanup(threshold: 100)) {
    $pruned = $retentionService->pruneExpiredMetrics($batchSize);
    $totalPruned += $pruned;
    
    if ($pruned < $batchSize) {
        // No more to prune
        break;
    }
    
    // Optional: Add delay to reduce load
    usleep(100000); // 100ms
}
```

### Monitoring Cleanup

**Track Cleanup Performance:**
```php
use Nexus\Telemetry\Traits\MonitoringAwareTrait;

class MetricCleanupJob
{
    use MonitoringAwareTrait;
    
    public function handle(MetricRetentionService $service): void
    {
        $this->trackOperation('metric.cleanup', function() use ($service) {
            return $service->pruneExpiredMetrics(1000);
        }, tags: ['component' => 'retention']);
    }
}
```

---

## Test Coverage

### MetricRetentionService (12 tests)

1. ✅ Prunes expired metrics with correct cutoff calculation
2. ✅ Respects batch size parameter
3. ✅ Logs pruning activity with context
4. ✅ Handles and propagates storage errors
5. ✅ Prunes specific metrics by key
6. ✅ Logs metric-specific pruning
7. ✅ Handles metric-specific errors
8. ✅ Provides comprehensive retention statistics
9. ✅ Correctly detects cleanup threshold
10. ✅ Supports custom cleanup thresholds
11. ✅ Exposes retention policy instance

### TimeBasedRetentionPolicy (9 tests)

1. ✅ Creates policy with seconds
2. ✅ Factory method for days
3. ✅ Factory method for hours
4. ✅ Rejects zero retention period
5. ✅ Rejects negative retention period
6. ✅ Retains recent metrics
7. ✅ Expires old metrics
8. ✅ Handles exact cutoff timestamp
9. ✅ Metric key agnostic behavior

**Total:** 21 tests, 77 assertions

---

## Future Enhancements

### Planned Features

1. **Tiered Retention Policies**
   - Different periods per metric pattern
   - Priority-based retention (keep critical metrics longer)

2. **Compression Before Deletion**
   - Archive to cold storage
   - Aggregate before purging (hourly → daily → monthly)

3. **Retention Policies per Tenant**
   - Multi-tenant retention configuration
   - Tenant-specific cleanup schedules

4. **Smart Retention**
   - ML-based anomaly preservation
   - Event-driven retention extensions

5. **Incremental Cleanup**
   - Cursor-based pagination for large datasets
   - Resumable cleanup operations

---

## Related Components

- **TelemetryTracker** - Records metrics for storage
- **MetricStorageInterface** - Underlying storage backend
- **MonitoringAwareTrait** - Integration helper
- **LoggerInterface** - Audit trail

---

## References

- Package Architecture: `ARCHITECTURE.md`
- Test Suite Summary: `TEST_SUITE_SUMMARY.md`
- Interface Contracts: `src/Contracts/`
