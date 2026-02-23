# SystemAdministration Orchestrator - Architecture

> **Status:** Architecture Design  
> **Version:** 1.0.0  
> **Date:** 2026-02-22

---

## 1. Architecture Overview

### 1.1 Design Philosophy

The SystemAdministration orchestrator follows the **Advanced Orchestrator Pattern** as defined in [`ARCHITECTURE.md`](../../ARCHITECTURE.md#4-the-advanced-orchestrator-pattern). It coordinates platform-level administration and observability, providing unified health monitoring, audit aggregation, compliance reporting, and incident management across the Nexus ERP system.

### 1.2 Layer Positioning

```
┌─────────────────────────────────────────────────────────┐
│                    Adapters (L3)                        │
│   Implements orchestrator interfaces using atomic pkgs │
│   - Laravel adapters for SystemAdministration           │
└─────────────────────────────────────────────────────────┘
                            ▲ implements
┌─────────────────────────────────────────────────────────┐
│              SystemAdministration (L2)                   │
│   - Defines own interfaces in Contracts/               │
│   - Depends only on: php, psr/log, psr/event-dispatcher│
│   - Coordinates multi-package admin workflows           │
│   - Publishable as standalone composer package          │
└─────────────────────────────────────────────────────────┘
                            ▲ uses via interfaces
┌─────────────────────────────────────────────────────────┐
│                Atomic Packages (L1)                      │
│   - Monitoring, AuditLogger, Compliance                 │
│   - Notifier, Identity, Tenant                          │
└─────────────────────────────────────────────────────────┘
```

---

## 2. Directory Structure

```
orchestrators/SystemAdministration/
├── composer.json
├── README.md
├── REQUIREMENTS.md
├── ARCHITECTURAL.md
├── phpunit.xml
├── .gitignore
├── src/
│   ├── Contracts/                    # Own interfaces
│   │   ├── HealthMonitoringCoordinatorInterface.php
│   │   ├── AuditTrailCoordinatorInterface.php
│   │   ├── ComplianceCoordinatorInterface.php
│   │   ├── IncidentCoordinatorInterface.php
│   │   ├── MetricsCoordinatorInterface.php
│   │   ├── HealthDataProviderInterface.php
│   │   ├── AuditDataProviderInterface.php
│   │   └── IncidentDataProviderInterface.php
│   │
│   ├── Coordinators/                 # Traffic management
│   │   ├── HealthMonitoringCoordinator.php
│   │   ├── AuditTrailCoordinator.php
│   │   ├── ComplianceCoordinator.php
│   │   ├── IncidentCoordinator.php
│   │   └── MetricsCoordinator.php
│   │
│   ├── DataProviders/                # Cross-package aggregation
│   │   ├── HealthDataProvider.php
│   │   ├── AuditDataProvider.php
│   │   ├── ComplianceDataProvider.php
│   │   ├── MetricsDataProvider.php
│   │   └── IncidentDataProvider.php
│   │
│   ├── DTOs/                         # Request/Response objects
│   │   ├── HealthMonitoring/
│   │   │   ├── HealthCheckRequest.php
│   │   │   ├── HealthCheckResult.php
│   │   │   ├── AggregatedHealthRequest.php
│   │   │   ├── AggregatedHealthResult.php
│   │   │   └── ThresholdConfigRequest.php
│   │   ├── AuditTrail/
│   │   │   ├── LogAggregationRequest.php
│   │   │   ├── LogAggregationResult.php
│   │   │   ├── AuditReportRequest.php
│   │   │   ├── AuditReportResult.php
│   │   │   └── ArchiveRequest.php
│   │   ├── Compliance/
│   │   │   ├── ComplianceReportRequest.php
│   │   │   └── ComplianceReportResult.php
│   │   ├── Incident/
│   │   │   ├── IncidentCreateRequest.php
│   │   │   ├── IncidentCreateResult.php
│   │   │   ├── IncidentUpdateRequest.php
│   │   │   ├── IncidentUpdateResult.php
│   │   │   ├── IncidentResolveRequest.php
│   │   │   └── IncidentResolveResult.php
│   │   └── Metrics/
│   │       ├── MetricsCollectionRequest.php
│   │       ├── MetricsCollectionResult.php
│   │       └── DashboardDataRequest.php
│   │
│   ├── Rules/                        # Business validation
│   │   ├── HealthThresholdRule.php
│   │   ├── AuditRetentionRule.php
│   │   ├── ComplianceDataRule.php
│   │   ├── IncidentSeverityRule.php
│   │   └── AlertEscalationRule.php
│   │
│   ├── Services/                     # Complex orchestration logic
│   │   ├── HealthMonitoringService.php
│   │   ├── AuditAggregationService.php
│   │   ├── ComplianceReportingService.php
│   │   ├── IncidentManagementService.php
│   │   └── MetricsAggregationService.php
│   │
│   ├── Workflows/                    # Stateful processes
│   │   ├── IncidentResponse/
│   │   │   ├── IncidentResponseWorkflow.php
│   │   │   └── States/
│   │   │       ├── IncidentDetected.php
│   │   │       ├── IncidentAcknowledged.php
│   │   │       ├── IncidentInvestigating.php
│   │   │       └── IncidentResolved.php
│   │   └── AlertEscalation/
│   │       └── AlertEscalationWorkflow.php
│   │
│   ├── Listeners/                    # Event handlers
│   │   ├── OnHealthCheckFailed.php
│   │   ├── OnAlertTriggered.php
│   │   ├── OnIncidentCreated.php
│   │   ├── OnIncidentResolved.php
│   │   └── OnComplianceReportGenerated.php
│   │
│   └── Exceptions/                   # Domain errors
│       ├── SystemAdministrationException.php
│       ├── HealthCheckException.php
│       ├── AuditAggregationException.php
│       ├── ComplianceException.php
│       ├── IncidentException.php
│       └── MetricsException.php
│
└── tests/
    ├── Unit/
    │   ├── Rules/
    │   └── Services/
    └── Integration/
        └── Coordinators/
```

---

## 3. Interface Segregation

### 3.1 Contract Design Principles

Following [`ARCHITECTURE.md`](../../ARCHITECTURE.md#5-orchestrator-interface-segregation), SystemAdministration:

1. **Defines its own interfaces** in `Contracts/` - never depend on atomic package interfaces directly
2. **Depends only on PSR interfaces** (PSR-3 Logger, PSR-14 EventDispatcher)
3. **Allows adapters** to bridge to atomic package implementations
4. **Enables publishing** as a standalone Composer package

### 3.2 Core Interface Hierarchy

```php
// Base coordinator interface
interface SystemCoordinatorInterface
{
    public function getName(): string;
}

// Health monitoring extends base
interface HealthMonitoringCoordinatorInterface extends SystemCoordinatorInterface
{
    public function runHealthCheck(HealthCheckRequest $request): HealthCheckResult;
    public function getAggregatedHealth(AggregatedHealthRequest $request): AggregatedHealthResult;
}

// Audit trail extends base
interface AuditTrailCoordinatorInterface extends SystemCoordinatorInterface
{
    public function aggregateLogs(LogAggregationRequest $request): LogAggregationResult;
    public function generateReport(AuditReportRequest $request): AuditReportResult;
}
```

---

## 4. Coordinator Design

### 4.1 Coordinator Responsibilities

Each coordinator follows the principle: **"Coordinators are Traffic Cops, Not Workers"**

| Coordinator | Responsibility | Boundaries |
|------------|----------------|-------------|
| `HealthMonitoringCoordinator` | Orchestrates health checks | Does not execute; delegates to service |
| `AuditTrailCoordinator` | Manages audit aggregation | Does not query; delegates to data provider |
| `ComplianceCoordinator` | Manages compliance reporting | Does not calculate; delegates to service |
| `IncidentCoordinator` | Manages incident lifecycle | Does not detect; delegates to detector |
| `MetricsCoordinator` | Manages metrics aggregation | Does not collect; delegates to service |

### 4.2 Coordinator Flow Example: Health Check

```
HealthMonitoringCoordinator::runHealthCheck()
    │
    ├──► DataProvider: HealthDataProvider
    │       └──► Get registered health checks from Monitoring package
    │
    └──► Service: HealthMonitoringService
            │
            ├──► 1. Execute each health check
            ├──► 2. Aggregate results
            ├──► 3. Evaluate thresholds (via HealthThresholdRule)
            ├──► 4. If failed: create alert
            └──► 5. Return aggregated result
```

---

## 5. DataProvider Design

### 5.1 Data Aggregation Pattern

DataProviders aggregate cross-package data into context DTOs that coordinators can consume.

```php
// Example: AuditDataProvider aggregates audit logs
class AuditDataProvider implements AuditDataProviderInterface
{
    public function __construct(
        private AuditLogQueryInterface $auditQuery,
        private TenantQueryInterface $tenantQuery,
    ) {}

    public function aggregateAcrossTenants(
        LogAggregationRequest $request
    ): AggregatedAuditLogs {
        // Handle multi-tenant aggregation
        // Apply filters
        // Return paginated results
    }
}
```

---

## 6. Rule Design

### 6.1 Composable Validation

Rules are single-responsibility classes for validation and decision-making.

```php
// Example: Incident severity determination
class IncidentSeverityRule implements IncidentSeverityRuleInterface
{
    public function determineSeverity(IncidentContext $context): IncidentSeverity
    {
        if ($context->isCriticalComponent() && $context->isFullOutage()) {
            return IncidentSeverity::CRITICAL;
        }

        if ($context->isDegraded()) {
            return IncidentSeverity::HIGH;
        }

        return IncidentSeverity::MEDIUM;
    }
}
```

---

## 7. Service Design

### 7.1 Orchestration Services

Services contain the complex orchestration logic.

```php
class HealthMonitoringService implements HealthMonitoringServiceInterface
{
    public function __construct(
        private HealthCheckRegistryInterface $registry,
        private MetricsCollectorInterface $metrics,
        private AlertManagerInterface $alertManager,
        private AuditLogManagerInterface $auditLogger,
    ) {}

    public function runHealthCheck(HealthCheckRequest $request): HealthCheckResult
    {
        $results = [];

        foreach ($this->registry->getChecks($request->components) as $check) {
            $results[] = $this->executeCheck($check);
        }

        $aggregated = $this->aggregateResults($results);

        // Create alerts for failures
        foreach ($aggregated->failures as $failure) {
            $this->alertManager->createAlert($failure);
        }

        // Log the health check run
        $this->auditLogger->log('health_check.run', [
            'components' => $request->components,
            'status' => $aggregated->status,
        ]);

        return $aggregated;
    }
}
```

---

## 8. Dependency Injection

### 8.1 Constructor Injection Pattern

All dependencies are injected via constructors following the **Dependency Inversion Principle**.

```php
final readonly class HealthMonitoringCoordinator implements HealthMonitoringCoordinatorInterface
{
    public function __construct(
        private HealthMonitoringServiceInterface $service,
        private HealthDataProviderInterface $dataProvider,
    ) {}
}
```

---

## 9. Event-Driven Architecture

### 9.1 Domain Events

SystemAdministration emits domain events for reactive workflows:

| Event | Payload | Listeners |
|-------|---------|-----------|
| `HealthCheckCompletedEvent` | Check results | OnHealthCheckCompleted (update dashboard) |
| `AlertTriggeredEvent` | Alert details | OnAlertTriggered (notify) |
| `IncidentCreatedEvent` | Incident details | OnIncidentCreated (assign, notify) |
| `IncidentResolvedEvent` | Resolution details | OnIncidentResolved (notify, log) |
| `ComplianceReportGeneratedEvent` | Report details | OnComplianceReportGenerated (store) |

---

## 10. Incident Management Workflow

### 10.1 Incident Lifecycle

```
[Detected] → [Created] → [Acknowledged] → [Investigating] → [Resolved] → [Closed]
     ↓           ↓            ↓               ↓               ↓
  Auto via   Notify      Assign to        Track          Notify
  monitoring stakeholders  team          resolution     stakeholders
```

### 10.2 Incident State Machine

```php
class IncidentResponseWorkflow
{
    public function transition(Incident $incident, string $transition): void
    {
        $stateMachine = new StateMachine([
            'initial' => 'detected',
            'states' => ['detected', 'created', 'acknowledged', 'investigating', 'resolved', 'closed'],
            'transitions' => [
                'create' => ['from' => 'detected', 'to' => 'created'],
                'acknowledge' => ['from' => 'created', 'to' => 'acknowledged'],
                'start_investigation' => ['from' => 'acknowledged', 'to' => 'investigating'],
                'resolve' => ['from' => 'investigating', 'to' => 'resolved'],
                'close' => ['from' => 'resolved', 'to' => 'closed'],
            ],
        ]);

        $stateMachine->apply($incident, $transition);
    }
}
```

---

## 11. Error Handling

### 11.1 Domain-Specific Exceptions

```php
class SystemAdministrationException extends \RuntimeException
{
    private array $context;

    public function __construct(string $message, array $context = [], \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->context = $context;
    }
}
```

### 11.2 Exception Hierarchy

```
SystemAdministrationException (base)
├── HealthCheckException
│   ├── HealthCheckTimeoutException
│   └── HealthCheckFailureException
├── AuditAggregationException
│   ├── AuditQueryException
│   └── AuditArchiveException
├── ComplianceException
│   ├── ComplianceDataException
│   └── ComplianceReportException
├── IncidentException
│   ├── IncidentCreationException
│   └── IncidentTransitionException
└── MetricsException
    ├── MetricsCollectionException
    └── MetricsAggregationException
```

---

## 12. Testing Strategy

### 12.1 Unit Tests

- **Rules**: Test each rule in isolation
- **Services**: Test service logic with mocked dependencies

### 12.2 Integration Tests

- **Coordinators**: Test full workflow with in-memory implementations
- **Workflows**: Test state machine transitions

### 12.3 Performance Tests

- Health check execution time
- Audit log aggregation throughput
- Metrics collection latency

---

## 13. Framework Integration

### 13.1 Laravel Service Provider

```php
<?php

namespace Nexus\Laravel\SystemAdministration;

use Illuminate\Support\ServiceProvider;
use Nexus\SystemAdministration\Contracts\HealthMonitoringCoordinatorInterface;
use Nexus\SystemAdministration\Coordinators\HealthMonitoringCoordinator;

class SystemAdministrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register coordinators
        $this->app->singleton(
            HealthMonitoringCoordinatorInterface::class,
            HealthMonitoringCoordinator::class
        );

        // Register data providers
        // Register services
        // Register rules
    }
}
```

---

## 14. Monitoring and Observability

### 14.1 Self-Monitoring

SystemAdministration monitors itself:

| Metric | Type | Description |
|--------|------|-------------|
| `system_admin.health_check.duration` | Histogram | Health check execution time |
| `system_admin.audit.aggregation.duration` | Histogram | Log aggregation time |
| `system_admin.incidents.active` | Gauge | Active incident count |
| `system_admin.compliance.report.duration` | Histogram | Report generation time |

---

## 15. Security Considerations

### 15.1 Audit Log Security

- All audit logs are append-only
- Log integrity verified via hash chain
- Tamper detection alerts

### 15.2 Access Control

- Health check results require authentication
- Audit logs require authorization (admin only)
- Compliance reports require role-based access

---

## Appendix A: Package Dependencies

```
nexus/system-administration
├── nexus/common (^1.0)
├── nexus/monitoring (^1.0)
├── nexus/audit-logger (^1.0)
├── nexus/compliance (^1.0)
├── nexus/notifier (^1.0)
├── nexus/identity (^1.0)
├── nexus/tenant (^1.0)
├── psr/log (^3.0)
└── psr/event-dispatcher (^3.0)
```

---

*Document Version: 1.0.0*  
*Last Updated: 2026-02-22*
