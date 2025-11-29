# Requirements: Reporting

**Total Requirements:** 42

---

## Architectural Requirements

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Reporting` | Architectural Requirement | ARC-REP-0001 | Package MUST be framework-agnostic with no Laravel/Symfony dependencies | composer.json | ✅ Complete | Only PSR and Symfony UID | 2025-11-30 |
| `Nexus\Reporting` | Architectural Requirement | ARC-REP-0002 | All dependencies MUST be injected via interfaces | src/Services/ReportManager.php | ✅ Complete | All constructor deps are interfaces | 2025-11-30 |
| `Nexus\Reporting` | Architectural Requirement | ARC-REP-0003 | All properties MUST be readonly | src/ | ✅ Complete | All classes use readonly | 2025-11-30 |
| `Nexus\Reporting` | Architectural Requirement | ARC-REP-0004 | Package MUST require PHP 8.3+ | composer.json | ✅ Complete | "php": "^8.3" | 2025-11-30 |
| `Nexus\Reporting` | Architectural Requirement | ARC-REP-0005 | Package MUST use native PHP enums for fixed value sets | src/ValueObjects/ | ✅ Complete | 4 enums implemented | 2025-11-30 |
| `Nexus\Reporting` | Architectural Requirement | ARC-REP-0006 | Package MUST be an Orchestration Layer (no query/render logic duplication) | src/Core/Engine/ | ✅ Complete | Delegates to Analytics/Export | 2025-11-30 |
| `Nexus\Reporting` | Architectural Requirement | ARC-REP-0007 | Package MUST follow CQRS pattern with separate Query and Persist interfaces | src/Contracts/ | ✅ Complete | ReportRepositoryInterface | 2025-11-30 |

---

## Business Requirements

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Reporting` | Business Requirement | BUS-REP-0001 | System MUST support multiple output formats (PDF, Excel, CSV, JSON, HTML) | src/ValueObjects/ReportFormat.php | ✅ Complete | Enum with 5 formats | 2025-11-30 |
| `Nexus\Reporting` | Business Requirement | BUS-REP-0002 | System MUST support scheduled report generation (ONCE, DAILY, WEEKLY, MONTHLY, YEARLY, CRON) | src/ValueObjects/ScheduleType.php | ✅ Complete | Enum with 6 schedule types | 2025-11-30 |
| `Nexus\Reporting` | Business Requirement | BUS-REP-0003 | System MUST provide 3-tier retention lifecycle (Active → Archived → Purged) | src/ValueObjects/RetentionTier.php | ✅ Complete | 90d active, 7yr archived | 2025-11-30 |
| `Nexus\Reporting` | Business Requirement | BUS-REP-0004 | System MUST track distribution status (PENDING, SENT, DELIVERED, FAILED, BOUNCED, READ) | src/ValueObjects/DistributionStatus.php | ✅ Complete | Enum with 6 statuses | 2025-11-30 |
| `Nexus\Reporting` | Business Requirement | BUS-REP-0005 | System MUST support multi-tenant isolation for all reports | src/Contracts/ReportRepositoryInterface.php | ✅ Complete | tenant_id in all operations | 2025-11-30 |
| `Nexus\Reporting` | Business Requirement | BUS-REP-0006 | System MUST preserve generated files on distribution failure for retry | src/Core/Engine/ReportDistributor.php | ✅ Complete | REL-REP-0305 compliance | 2025-11-30 |
| `Nexus\Reporting` | Business Requirement | BUS-REP-0007 | System MUST provide format fallback to JSON on export failure | src/Core/Engine/ReportGenerator.php | ✅ Complete | Logs at MEDIUM severity | 2025-11-30 |

---

## Functional Requirements

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0001 | Provide ReportManager service for report lifecycle management | src/Services/ReportManager.php | ✅ Complete | Main public API | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0002 | Provide createReport() to define new report templates | src/Services/ReportManager.php | ✅ Complete | Links to Analytics query | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0003 | Provide generateReport() to execute report generation | src/Services/ReportManager.php | ✅ Complete | Returns ReportResult | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0004 | Provide previewReport() for ad-hoc dashboard exports | src/Services/ReportManager.php | ✅ Complete | No persistent record | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0005 | Provide generateBatch() for bulk report generation with concurrency limit | src/Services/ReportManager.php | ✅ Complete | 10 concurrent jobs/tenant | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0006 | Provide distributeReport() to send reports to recipients | src/Services/ReportManager.php | ✅ Complete | Multi-channel distribution | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0007 | Provide scheduleReport() to configure automated generation | src/Services/ReportManager.php | ✅ Complete | Creates Scheduler jobs | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0008 | ReportGenerator MUST orchestrate Analytics query execution | src/Core/Engine/ReportGenerator.php | ✅ Complete | Calls executeQuery() | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0009 | ReportGenerator MUST orchestrate Export rendering | src/Core/Engine/ReportGenerator.php | ✅ Complete | Calls render() | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0010 | ReportDistributor MUST integrate with Notifier for delivery | src/Core/Engine/ReportDistributor.php | ✅ Complete | Email attachments | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0011 | ReportDistributor MUST track per-recipient delivery status | src/Core/Engine/ReportDistributor.php | ✅ Complete | Distribution log | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0012 | ReportDistributor MUST support retry for failed distributions | src/Core/Engine/ReportDistributor.php | ✅ Complete | retryFailedDistributions() | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0013 | ReportRetentionManager MUST implement applyRetentionPolicy() | src/Core/Engine/ReportRetentionManager.php | ✅ Complete | Daily scheduled job | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0014 | ReportRetentionManager MUST send expiration warnings 7 days before transition | src/Core/Engine/ReportRetentionManager.php | ✅ Complete | Notifier integration | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0015 | ReportJobHandler MUST process EXPORT_REPORT jobs from Scheduler | src/Core/Engine/ReportJobHandler.php | ✅ Complete | Async execution | 2025-11-30 |
| `Nexus\Reporting` | Functional Requirement | FUN-REP-0016 | ReportJobHandler MUST classify errors as transient or permanent | src/Core/Engine/ReportJobHandler.php | ✅ Complete | Retry strategy | 2025-11-30 |

---

## Security Requirements

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Reporting` | Security Requirement | SEC-REP-0001 | User MUST have Analytics query permission to generate report | src/Services/ReportManager.php | ✅ Complete | Permission inheritance | 2025-11-30 |
| `Nexus\Reporting` | Security Requirement | SEC-REP-0002 | Defense-in-depth tenant validation MUST occur before permission check | src/Services/ReportManager.php | ✅ Complete | Tenant mismatch check | 2025-11-30 |
| `Nexus\Reporting` | Security Requirement | SEC-REP-0003 | Cross-tenant access MUST throw UnauthorizedReportException | src/Exceptions/UnauthorizedReportException.php | ✅ Complete | HTTP 403 | 2025-11-30 |

---

## Performance Requirements

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Reporting` | Performance Requirement | PER-REP-0001 | Report generation >5s MUST be offloaded to Scheduler queue | src/Services/ReportManager.php | ✅ Complete | Async execution | 2025-11-30 |
| `Nexus\Reporting` | Performance Requirement | PER-REP-0002 | Batch operations MUST enforce 10 concurrent jobs per tenant | src/Services/ReportManager.php | ✅ Complete | Resource exhaustion prevention | 2025-11-30 |
| `Nexus\Reporting` | Performance Requirement | PER-REP-0003 | ReportJobHandler MUST use exponential backoff for retries (5m, 15m, 1h) | src/Core/Engine/ReportJobHandler.php | ✅ Complete | calculateBackoff() | 2025-11-30 |

---

## Reliability Requirements

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Reporting` | Reliability Requirement | REL-REP-0001 | Generated files MUST be preserved on distribution failure | src/Core/Engine/ReportDistributor.php | ✅ Complete | Never deleted on failure | 2025-11-30 |
| `Nexus\Reporting` | Reliability Requirement | REL-REP-0002 | Failed distributions MUST be retryable | src/Core/Engine/ReportDistributor.php | ✅ Complete | retryFailedDistributions() | 2025-11-30 |
| `Nexus\Reporting` | Reliability Requirement | REL-REP-0003 | Export failures MUST fall back to JSON format | src/Core/Engine/ReportGenerator.php | ✅ Complete | Graceful degradation | 2025-11-30 |

---

## Integration Requirements

| Package Namespace | Requirements Type | Code | Requirement Statements | Files/Folders | Status | Notes on Status | Date Last Updated |
|-------------------|-------------------|------|------------------------|---------------|--------|-----------------|-------------------|
| `Nexus\Reporting` | Integration Requirement | INT-REP-0001 | MUST integrate with Nexus\Analytics for data retrieval | src/Core/Engine/ReportGenerator.php | ✅ Complete | AnalyticsManagerInterface | 2025-11-30 |
| `Nexus\Reporting` | Integration Requirement | INT-REP-0002 | MUST integrate with Nexus\Export for file rendering | src/Core/Engine/ReportGenerator.php | ✅ Complete | ExportManagerInterface | 2025-11-30 |
| `Nexus\Reporting` | Integration Requirement | INT-REP-0003 | MUST integrate with Nexus\Notifier for distribution | src/Core/Engine/ReportDistributor.php | ✅ Complete | NotificationManagerInterface | 2025-11-30 |
| `Nexus\Reporting` | Integration Requirement | INT-REP-0004 | MUST integrate with Nexus\Scheduler for automation | src/Core/Engine/ReportJobHandler.php | ✅ Complete | ScheduleManagerInterface | 2025-11-30 |
| `Nexus\Reporting` | Integration Requirement | INT-REP-0005 | MUST integrate with Nexus\Storage for file persistence | src/Core/Engine/ReportGenerator.php | ✅ Complete | StorageInterface | 2025-11-30 |
| `Nexus\Reporting` | Integration Requirement | INT-REP-0006 | MUST integrate with Nexus\AuditLogger for compliance tracking | src/Services/ReportManager.php | ✅ Complete | AuditLoggerInterface | 2025-11-30 |

---

## Summary

| Category | Total | Complete | Pending |
|----------|-------|----------|---------|
| Architectural | 7 | 7 | 0 |
| Business | 7 | 7 | 0 |
| Functional | 16 | 16 | 0 |
| Security | 3 | 3 | 0 |
| Performance | 3 | 3 | 0 |
| Reliability | 3 | 3 | 0 |
| Integration | 6 | 6 | 0 |
| **TOTAL** | **42** | **42** | **0** |
