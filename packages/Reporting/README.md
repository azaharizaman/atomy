# Nexus\Reporting

**Presentation layer package for generating scheduled, multi-format reports with automated distribution and compliance-driven retention management.**

## Overview

`Nexus\Reporting` transforms raw analytics data from `Nexus\Analytics` into user-facing reports with:

- **Scheduled Generation**: Daily, weekly, monthly, and cron-based report scheduling
- **Multi-Format Export**: PDF, Excel, CSV, JSON, HTML via `Nexus\Export`
- **Automated Distribution**: Email, SFTP, storage via `Nexus\Notifier`
- **Tiered Retention**: 90-day active → 7-year archive → purge lifecycle
- **Custom Templates**: Brand-aligned reports with logo/CSS customization
- **Batch Processing**: High-volume report generation with concurrency control
- **Permission Inheritance**: RBAC enforcement from underlying Analytics queries

## Architecture

### 4-Tier Report Lifecycle

```
1. Definition → 2. Generation → 3. Distribution → 4. Retention
   (Template)      (Analytics)      (Notifier)       (Storage Tiers)
```

### Core Components

- **`ReportManager`**: Public API orchestrator
- **`ReportGenerator`**: Query execution → Export rendering
- **`ReportDistributor`**: Multi-channel delivery with failure resilience
- **`ReportJobHandler`**: Scheduler integration for recurring jobs
- **`ReportRetentionManager`**: Automated tier transitions (Active → Archive → Purge)

## Key Features

### Security (SEC-REP-0401)
- Permission checks inherit from `AnalyticsAuthorizerInterface`
- Users can only generate reports from authorized queries
- Tenant isolation enforced via `TenantContextInterface`

### Resilience (REL-REP-0305)
- Failed distributions preserve PDF for manual retry
- Scheduler-based retry with exponential backoff
- Transient failure detection and auto-recovery

### Performance (PER-REP-0301)
- Jobs >5 seconds offloaded to queue workers
- Streaming export for datasets >10K rows
- Batch generation with configurable concurrency (max 10/tenant)

### Compliance
- **Tier 1 (Active)**: 90 days in hot storage
- **Tier 2 (Archive)**: 7 years in deep archive (S3 Glacier)
- **Tier 3 (Purge)**: Irreversible deletion
- Full audit trail via `AuditLogManagerInterface` (High severity)

## Installation

```bash
composer require nexus/reporting:*@dev
```

## Usage

### Create Report Definition

```php
use Nexus\Reporting\Services\ReportManager;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\ScheduleType;
use Nexus\Reporting\ValueObjects\ReportSchedule;

$reportId = $reportManager->createReport([
    'name' => 'Monthly Sales Report',
    'query_id' => $analyticsQueryId,
    'owner_id' => $userId,
    'format' => ReportFormat::PDF,
    'schedule' => new ReportSchedule(
        type: ScheduleType::MONTHLY,
        cronExpression: '0 9 1 * *', // 9 AM on 1st of month
        startsAt: new \DateTimeImmutable('2025-12-01'),
        endsAt: null,
        maxOccurrences: null
    ),
    'recipients' => [$customer, $manager],
    'template_config' => [
        'logo_path' => 'storage://logos/company.png',
        'css_path' => 'storage://templates/invoice.css'
    ]
]);
```

### Generate On-Demand Report

```php
$result = $reportManager->generateReport($reportId, [
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31'
]);

if ($result->isSuccessful()) {
    $filePath = $result->getFilePath(); // storage://reports/active/xyz.pdf
    $fileSize = $result->getFileSize();
}
```

### Interactive Preview (FUN-REP-0213)

```php
// Returns QueryResultInterface without storage
$queryResult = $reportManager->previewReport($reportId, [
    'customer_id' => '12345'
]);

// Send to AJAX endpoint for real-time dashboard
return response()->json($queryResult->getData());
```

### Batch Generation

```php
// Generate invoices for 1,000 customers
$jobIds = $reportManager->generateBatch($reportId, $customerIds);

// Scheduler processes with max 10 concurrent workers
// Each job tracked independently with retry logic
```

## Integration Points

### Analytics
- Consumes `QueryResultInterface` from `AnalyticsManager::runQuery()`
- Enforces guards and RBAC via `AnalyticsAuthorizerInterface`

### Export
- Transforms `QueryResult` → `ExportDefinition` → formatted output
- Supports streaming for large datasets

### Notifier
- Multi-channel distribution (Email with PDF attachment, SMS alert, In-App)
- Delivery status tracking in `reports_distribution_log`

### Scheduler
- Registers `ReportJobHandler` for `JobType::EXPORT_REPORT`
- Cron-based recurring report execution
- Daily retention policy job for tier transitions

### Storage
- Stores generated PDFs with temporary URLs for secure access
- Supports multi-tier storage (hot → archive → purge)

### AuditLogger
- Logs generation, distribution, access, and retention events
- High severity for compliance (report_generated, report_purged)

## Database Schema

### `reports_definitions`
- Report templates with scheduling configuration
- Links to Analytics queries via `query_id`
- Custom template assets in `template_config` JSON

### `reports_generated`
- Immutable execution history
- Storage paths and retention tiers
- Performance metrics (duration_ms, file_size_bytes)

### `reports_distribution_log`
- Delivery tracking per recipient
- Links to Notifier notifications
- Failure diagnostics for retry

## Retention Policy

| Tier | Duration | Storage | Purpose |
|------|----------|---------|---------|
| **Active** | 90 days | Hot (S3 Standard) | Frequent access |
| **Archive** | 7 years | Deep (S3 Glacier) | Compliance/Legal |
| **Purged** | Permanent | Deleted | GDPR/Data minimization |

Automated transitions run daily via scheduled job.

## Template Customization

### Default Templates (Option A)
Clean, professional formatting via `Nexus\Export` defaults.

### Custom Templates (Option C - Advanced)
```php
'template_config' => [
    'logo_path' => 'storage://assets/logo-v2.png',
    'css_path' => 'storage://templates/branded.css',
    'header_html' => '<div class="header">Company Inc.</div>',
    'footer_html' => '<div class="footer">Page {page}</div>',
    'template_version' => '2.1.0' // Version control for rollback
]
```

Template assets stored in `Nexus\Storage` with version audit trail.

## Error Handling

### Exceptions
- `ReportNotFoundException`: Report ID not found
- `ReportGenerationException`: Analytics or Export failure
- `ReportDistributionException`: Notifier delivery failure
- `UnauthorizedReportException`: Permission violation (SEC-REP-0401)
- `InvalidReportScheduleException`: Malformed cron or date range

### Resilience Patterns
- Scheduler retry with exponential backoff (transient failures)
- PDF preservation on distribution failure (manual retry)
- Format fallback (PDF fail → CSV succeed with warning notification)
- Double tenant validation (defense-in-depth)

## Performance Considerations

- **Preview Mode**: No storage overhead, ideal for dashboards
- **Streaming Export**: Prevents memory exhaustion for >10K rows
- **Batch Concurrency**: Max 10 concurrent jobs per tenant
- **Queue Offloading**: Jobs >5s run asynchronously

## Requirements Coverage

| Code | Requirement | Implementation |
|------|-------------|----------------|
| FUN-REP-0201 | Create/Edit Report Templates | `ReportManager::createReport()` |
| FUN-REP-0207 | Scheduled Report Delivery | `ReportSchedule` + `ReportJobHandler` |
| FUN-REP-0213 | Interactive Dashboard Generation | `ReportManager::previewReport()` |
| BUS-REP-0105 | Output Format Control | `ReportFormat` enum (PDF/Excel/CSV/JSON/HTML) |
| SEC-REP-0401 | Permission Inheritance | `AnalyticsAuthorizerInterface::can()` checks |
| REL-REP-0305 | Report Generation Resilience | PDF storage on distribution failure |
| PER-REP-0301 | Scheduled Report Offloading | Scheduler queue for >5s jobs |

## 📖 Documentation

### Package Documentation
- [Getting Started Guide](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Integration Guide](docs/integration-guide.md)
- [Examples](docs/examples/)

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress
- `REQUIREMENTS.md` - Requirements
- `TEST_SUITE_SUMMARY.md` - Tests
- `VALUATION_MATRIX.md` - Valuation


## License

MIT License - See LICENSE file for details.
