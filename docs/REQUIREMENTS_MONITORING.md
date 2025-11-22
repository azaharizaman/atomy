# Nexus\Monitoring Package Requirements

**Package:** `nexus/monitoring`  
**Version:** 1.0.0  
**Status:** 🚧 In Development

---

## Overview

The Monitoring package provides a framework-agnostic, atomic monitoring solution for all Nexus ERP packages. It offers real-time telemetry tracking, comprehensive health checks, intelligent alerting, SLO tracking, distributed tracing support, multi-tenancy awareness, and cardinality protection.

---

## Functional Requirements

### FR-1: Telemetry Tracking

#### FR-1.1: Metric Types Support
- **Requirement:** System MUST support four core metric types
  - **COUNTER:** Monotonically increasing values (e.g., total requests)
  - **GAUGE:** Instantaneous point-in-time values (e.g., queue size)
  - **TIMING:** Duration measurements in milliseconds (e.g., API latency)
  - **HISTOGRAM:** Distribution of values across buckets (e.g., response time distribution)

#### FR-1.2: Tag Support
- **Requirement:** System MUST allow arbitrary key-value tags on all metrics
- **Constraint:** Tag keys MUST be strings, tag values MUST be scalar (string, int, float, bool)
- **Validation:** System MUST reject metrics with invalid tag formats

#### FR-1.3: Distributed Tracing Integration
- **Requirement:** System MUST support OpenTelemetry trace context propagation
- **Properties:** Optional `traceId` (string) and `spanId` (string) on all metrics
- **Purpose:** Enable correlation between metrics and distributed traces

#### FR-1.4: Multi-Tenancy Auto-Tagging
- **Requirement:** System MUST automatically append `tenant_id` tag when tenant context exists
- **Implementation:** Optional injection of `TenantContextInterface` from `nexus/tenant`
- **Behavior:** If tenant context unavailable, metrics recorded without tenant tag

#### FR-1.5: Timestamp Precision
- **Requirement:** All metrics MUST be timestamped with microsecond precision
- **Format:** `DateTimeImmutable` with microseconds

---

### FR-2: Health Checks

#### FR-2.1: Health Check Registration
- **Requirement:** System MUST allow registration of arbitrary health checks
- **Interface:** All checks MUST implement `HealthCheckInterface`
- **Properties:** Each check has unique name, critical flag, check logic

#### FR-2.2: Scheduled Health Checks
- **Requirement:** System MUST support scheduled background health checks
- **Interface:** `ScheduledHealthCheckInterface` extends `HealthCheckInterface`
- **Property:** `getSchedule()` returns cron expression for execution timing

#### FR-2.3: Health Status Levels
- **Requirement:** System MUST define five health status levels with severity weights
  - **HEALTHY** (weight: 0): System operating normally
  - **WARNING** (weight: 25): Minor degradation, no user impact
  - **DEGRADED** (weight: 50): Significant degradation, partial functionality
  - **CRITICAL** (weight: 75): Major failure, service impacted
  - **OFFLINE** (weight: 100): Complete failure, service unavailable

#### FR-2.4: Critical Check Prioritization
- **Requirement:** Critical checks MUST execute before non-critical checks
- **Rationale:** Fail-fast for essential dependencies (database, cache)

#### FR-2.5: Timeout Enforcement
- **Requirement:** Each health check MUST have configurable timeout (default: 5000ms)
- **Behavior:** Checks exceeding timeout return CRITICAL status automatically

#### FR-2.6: Response Time Measurement
- **Requirement:** System MUST measure and return response time for each check
- **Precision:** Nanosecond measurement via `hrtime(true)`, returned in milliseconds

#### FR-2.7: Result Caching
- **Requirement:** System MUST support optional caching of health check results
- **Configuration:** Configurable TTL per check (default: disabled)
- **Purpose:** Reduce load for expensive checks (disk space, network tests)

#### FR-2.8: Aggregated Health Status
- **Requirement:** System MUST calculate overall system health from all checks
- **Logic:** Aggregated status = worst status from all executed checks

---

### FR-3: Alerting

#### FR-3.1: Alert Severity Levels
- **Requirement:** System MUST define three alert severity levels
  - **INFO:** Informational, no action required
  - **WARNING:** Non-critical issue, review recommended
  - **CRITICAL:** Critical issue, immediate action required

#### FR-3.2: Exception-Based Alerting
- **Requirement:** System MUST support automatic severity mapping from exceptions
- **Mapping Logic:** 
  - `PDOException`, `ConnectionException` → CRITICAL
  - Domain-specific exceptions → WARNING
  - Generic `Exception` → WARNING

#### FR-3.3: Threshold-Based Alerting
- **Requirement:** System MUST support alert triggering when metrics exceed thresholds
- **Configuration:** Thresholds configured per metric key
- **Evaluation:** System evaluates threshold after each metric recording

#### FR-3.4: Alert Deduplication
- **Requirement:** System MUST prevent duplicate alerts within time window
- **Window:** Configurable (default: 300 seconds)
- **Fingerprint:** Calculated from severity + message + key context fields

#### FR-3.5: Alert Context Enrichment
- **Requirement:** All alerts MUST include enriched context
- **Exception Alerts:** class, message, code, file, line, trace digest
- **Metric Alerts:** metric name, current value, threshold, tags

#### FR-3.6: Alert Dispatching
- **Requirement:** System MUST support pluggable alert dispatchers
- **Interface:** `AlertDispatcherInterface` with `dispatch(AlertContext $alert)` method
- **Implementations:** Synchronous (direct notification) and Queued (async via job)

---

### FR-4: SLO Tracking

#### FR-4.1: SLO Wrapper
- **Requirement:** System MUST provide utility to wrap operations with SLO tracking
- **Functionality:**
  - Execute callable and measure duration
  - Automatically record timing metric
  - Compare duration to configured SLO threshold
  - Trigger alert if threshold breached

#### FR-4.2: SLO Configuration
- **Requirement:** SLO thresholds MUST be configurable per operation
- **Interface:** `SLOConfigurationInterface` with `getThreshold(string $operation): ?float`
- **Fallback:** Default threshold if operation-specific not configured

#### FR-4.3: Success/Failure Tagging
- **Requirement:** SLO metrics MUST include status tag
- **Values:** `status:success` or `status:failure`
- **Exception Tag:** On failure, add `exception:ClassName` tag

#### FR-4.4: Nested Operation Support
- **Requirement:** System MUST support nested SLO wrappers
- **Behavior:** Maintain operation stack in tags for drill-down

---

### FR-5: Cardinality Protection

#### FR-5.1: Cardinality Limit Detection
- **Requirement:** System MUST track unique values per tag key
- **Storage:** Delegated to `CardinalityStorageInterface` (e.g., Redis HyperLogLog)
- **Threshold:** Configurable per tag key (default: 1000 unique values)

#### FR-5.2: Threshold Breach Handling
- **Requirement:** System MUST log WARNING when cardinality approaches 80% of limit
- **Requirement:** System MUST throw `CardinalityLimitExceededException` at 100%
- **Behavior:** Exception logs warning but allows metric (graceful degradation)

#### FR-5.3: Tag Allowlist
- **Requirement:** Certain tags MUST bypass cardinality checks
- **Allowlist:** `tenant_id`, `metric_name`, `service_name`
- **Rationale:** These tags are known-bounded and essential

#### FR-5.4: Cardinality Reporting
- **Requirement:** System MUST provide current cardinality per tag key
- **Method:** `getCardinality(string $tagKey): int` on `CardinalityStorageInterface`

---

### FR-6: Metric Storage & Querying

#### FR-6.1: Metric Persistence
- **Requirement:** System MUST persist all recorded metrics to storage
- **Interface:** `MetricStorageInterface` with `store(Metric $metric): void`
- **Implementation:** Delegated to application layer (TSDB adapter)

#### FR-6.2: Time-Range Queries
- **Requirement:** System MUST support querying metrics by name and time range
- **Parameters:** `QuerySpec` VO with metricName, from, to, tags filter, limit, orderBy
- **Return:** Array of Metric VOs

#### FR-6.3: Metric Aggregation
- **Requirement:** System MUST support standard aggregation functions
- **Functions:** AVG, SUM, MIN, MAX, COUNT, P50, P95, P99, STDDEV, RATE
- **Advanced:** EWMA (Exponential Weighted Moving Average) - optional TSDB support
- **Parameters:** `AggregationSpec` VO with function, time range, groupBy dimensions
- **Error:** Throw `UnsupportedAggregationException` if TSDB can't compute function

#### FR-6.4: Metric Retention
- **Requirement:** System MUST support configurable retention policies
- **Interface:** `RetentionPolicyInterface` with `getRetentionDays(string $metricKey): int`
- **Service:** `MetricRetentionService` purges metrics older than retention period
- **Defaults:** COUNTER/TIMING=90 days, GAUGE=30 days, HISTOGRAM=7 days

#### FR-6.5: Dry-Run Purging
- **Requirement:** Retention service MUST support dry-run mode
- **Behavior:** Returns count of metrics to be purged without deletion

---

### FR-7: Metric Export

#### FR-7.1: Export Format Support
- **Requirement:** System MUST support exporting metrics in standard formats
- **Formats:** Prometheus Exposition, OpenMetrics, JSON
- **Interface:** `MetricExporterInterface` with `export(QuerySpec $spec, ExportFormat $format): string`

#### FR-7.2: Pull-Based Scraping
- **Requirement:** System MUST expose HTTP endpoint for metric scraping
- **Endpoint:** `GET /api/monitoring/export/{format}`
- **Authentication:** Optional (configurable)

---

### FR-8: Metric Sampling

#### FR-8.1: Sampling Strategy
- **Requirement:** System MUST support optional metric sampling
- **Interface:** `SamplingStrategyInterface` with `shouldSample(Metric $metric): bool`
- **Strategies:** Probabilistic (random %), Deterministic (hash-based), Adaptive (cardinality-based)
- **Default:** `NoSamplingStrategy` (always returns true)

#### FR-8.2: Sampling Integration
- **Requirement:** `TelemetryTracker` MUST evaluate sampling before storage
- **Behavior:** If `shouldSample()` returns false, metric not stored

---

### FR-9: Built-in Health Checks

#### FR-9.1: Database Health Check
- **Requirement:** System MUST provide database connectivity check
- **Implementation:** `DatabaseHealthCheck` executes `SELECT 1`
- **Schedule:** Every 5 minutes
- **Critical:** Yes
- **Status Logic:**
  - Connection success + < 100ms → HEALTHY
  - Connection success + >= 100ms → DEGRADED
  - Connection failure → CRITICAL

#### FR-9.2: Cache Health Check
- **Requirement:** System MUST provide cache connectivity check
- **Implementation:** `CacheHealthCheck` performs ping/get operation
- **Schedule:** Every 5 minutes
- **Critical:** No
- **Status Logic:**
  - Success + < 100ms → HEALTHY
  - Success + >= 100ms → DEGRADED
  - Failure → CRITICAL

#### FR-9.3: Queue Health Check
- **Requirement:** System MUST provide queue status check
- **Implementation:** `QueueHealthCheck` checks pending job count
- **Schedule:** Every 10 minutes
- **Critical:** No
- **Status Logic:**
  - Pending < threshold → HEALTHY
  - Pending >= threshold → WARNING

#### FR-9.4: Disk Space Health Check
- **Requirement:** System MUST provide disk space check
- **Implementation:** `DiskSpaceHealthCheck` uses `disk_free_space()`
- **Schedule:** Every 15 minutes
- **Critical:** Yes
- **Status Logic:**
  - Free >= 10% → HEALTHY
  - Free < 10% → CRITICAL

#### FR-9.5: Memory Health Check
- **Requirement:** System MUST provide memory usage check
- **Implementation:** `MemoryHealthCheck` uses `memory_get_usage(true)`
- **Schedule:** Every 5 minutes
- **Critical:** No
- **Status Logic:**
  - Usage < 80% of limit → HEALTHY
  - Usage >= 80% → WARNING

---

### FR-10: Optional Monitoring Injection

#### FR-10.1: Nullable Dependency Injection
- **Requirement:** Package services MUST support optional monitoring injection
- **Pattern:** Constructor parameters `?TelemetryTrackerInterface $telemetry = null`
- **Behavior:** All monitoring calls wrapped in null-safe operator `?->`

#### FR-10.2: MonitoringAwareTrait
- **Requirement:** System MUST provide trait for safe monitoring integration
- **Methods:**
  - `trackTiming(string $key, callable $callback, array $tags = []): mixed`
  - `recordMetric(string $key, float $value, MetricType $type, array $tags = []): void`
  - `alertCritical(string $message, array $context = []): void`
  - `hasMonitoring(): bool`
- **Behavior:** All methods perform null checks before calling monitoring services

---

## Non-Functional Requirements

### NFR-1: Performance
- **Metric Recording:** < 1ms overhead per metric (in-memory operations)
- **Health Check Execution:** Individual checks timeout at 5000ms
- **Cardinality Validation:** < 5ms overhead (Redis HyperLogLog lookup)

### NFR-2: Scalability
- **Stateless Services:** All state delegated to storage interfaces
- **Horizontal Scalability:** Package services scale across PHP-FPM workers
- **TSDB Independence:** Package agnostic to underlying time-series database

### NFR-3: Reliability
- **Graceful Degradation:** Monitoring failures MUST NOT crash application
- **Exception Isolation:** All monitoring calls wrapped in try-catch
- **Null Safety:** Optional dependencies handle missing bindings

### NFR-4: Observability
- **Logging:** All monitoring operations logged via PSR-3 `LoggerInterface`
- **Self-Monitoring:** Monitoring package tracks its own performance metrics

### NFR-5: Security
- **Sensitive Data:** No PII or sensitive data in metric tags
- **Tag Validation:** Prevent injection attacks via tag sanitization
- **Access Control:** Health/metrics endpoints support authentication

### NFR-6: Compatibility
- **PHP Version:** >= 8.3
- **Framework Agnostic:** Zero Laravel dependencies in package layer
- **PSR Compliance:** PSR-3 (Logging), PSR-14 (Event Dispatcher - optional)

---

## Dependencies

### Required
- `php: ^8.3`
- `psr/log: ^3.0`

### Optional (Suggested)
- `nexus/notifier: *@dev` - Alert notification delivery
- `nexus/audit-logger: *@dev` - Audit trail integration
- `nexus/tenant: *@dev` - Multi-tenancy metric tagging

### Development
- `phpunit/phpunit: ^11.0`

---

## Constraints

### C-1: Framework Agnosticism
- Package MUST NOT use Laravel facades, global helpers, or Eloquent
- All external dependencies via constructor-injected interfaces

### C-2: Immutability
- All Value Objects MUST be readonly
- No mutable state in service classes (except internal caches with TTL)

### C-3: Modern PHP
- Use PHP 8.3+ features: enums, readonly, match expressions, constructor property promotion
- Use native attributes (`#[Test]`) instead of docblock annotations in tests

### C-4: Interface-Driven
- All service dependencies MUST be interfaces, never concrete classes
- Application layer provides all concrete implementations

---

## Out of Scope (Phase 1)

- Real-time metric streaming (WebSockets/SSE) - Atomy layer concern
- Advanced anomaly detection - Future integration with `Nexus\Intelligence`
- Custom dashboard UI - Separate frontend application
- Metric visualization - Delegated to external tools (Grafana)

---

## Success Criteria

1. ✅ All interfaces defined and documented
2. ✅ All Value Objects implemented with tests
3. ✅ All services implemented with 100% test coverage
4. ✅ Built-in health checks operational
5. ✅ Cardinality protection active
6. ✅ Atomy integration complete (TSDB adapter, service provider)
7. ✅ API endpoints functional
8. ✅ Documentation complete (README, Integration Guide)
9. ✅ Package publishable (composer.json valid, MIT license)
10. ✅ Zero architectural violations (no facades, no Laravel dependencies)
