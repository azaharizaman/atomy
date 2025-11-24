# Nexus AuditLogger

A framework-agnostic audit logging package for tracking CRUD operations, system activities, and user actions with comprehensive retention policies and filtering capabilities.

## Features

- **Automatic CRUD Tracking**: Capture create, read, update, delete operations
- **Before/After State**: Record model state changes for updates
- **User Context**: Track who performed actions with IP, user agent, timestamp
- **Audit Levels**: Low (1), Medium (2), High (3), Critical (4) for risk-based filtering
- **Batch Operations**: Group related activities with UUID
- **Retention Policies**: Configurable retention with automatic purging
- **Tenant Isolation**: Multi-tenant support with data isolation
- **Search & Filter**: Full-text search, date range, entity type filtering
- **Export**: CSV, JSON, PDF export capabilities
- **Sensitive Data Masking**: Automatic masking of passwords, tokens, secrets
- **Asynchronous Logging**: Queue-based logging to prevent performance impact
- **Event-Driven**: Notifications for high-value activities

## Installation

```bash
composer require nexus/audit-logger:"*@dev"
```

## Architecture

This package follows the Nexus architecture pattern:

- **Pure PHP**: No Laravel dependencies in core services
- **Contract-Driven**: All persistence via interfaces
- **Framework-Agnostic**: Can be used in any PHP application

## Package Structure

```
packages/AuditLogger/
├── composer.json
├── README.md
├── LICENSE
└── src/
    ├── Contracts/              # Interfaces
    │   ├── AuditLogInterface.php
    │   ├── AuditLogRepositoryInterface.php
    │   └── AuditConfigInterface.php
    ├── Exceptions/             # Domain exceptions
    │   ├── AuditLogNotFoundException.php
    │   ├── InvalidAuditLevelException.php
    │   └── InvalidRetentionPolicyException.php
    ├── Services/               # Business logic
    │   ├── AuditLogManager.php
    │   ├── AuditLogSearchService.php
    │   ├── AuditLogExportService.php
    │   ├── RetentionPolicyService.php
    │   └── SensitiveDataMasker.php
    ├── ValueObjects/           # Immutable value objects
    │   ├── AuditLevel.php
    │   └── RetentionPolicy.php
    └── AuditLoggerServiceProvider.php  # Optional Laravel integration
```

## Usage

### Define Repository Implementation

In your application layer (e.g., Laravel), implement the repository interface:

```php
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;

class DbAuditLogRepository implements AuditLogRepositoryInterface
{
    // Implement all contract methods using your persistence layer
}
```

### Log Activities

```php
use Nexus\AuditLogger\Services\AuditLogManager;

$auditManager = new AuditLogManager($repository, $config);

$auditManager->log(
    logName: 'user_update',
    description: 'User profile updated',
    subjectType: 'User',
    subjectId: 123,
    causerType: 'User',
    causerId: 456,
    properties: ['old' => [...], 'new' => [...]],
    level: 3, // High
    batchUuid: 'uuid-here'
);
```

### Search Logs

```php
$searchService = new AuditLogSearchService($repository);

$logs = $searchService->search([
    'date_from' => '2025-01-01',
    'date_to' => '2025-12-31',
    'causer_id' => 456,
    'subject_type' => 'User',
    'level' => 4, // Critical
    'log_name' => 'user_update'
]);
```

### Export Logs

```php
$exportService = new AuditLogExportService($repository);

$csv = $exportService->exportToCsv($filters);
$json = $exportService->exportToJson($filters);
$pdf = $exportService->exportToPdf($filters);
```

## Requirements

See `REQUIREMENTS.csv` for complete list of architectural, business, and functional requirements.

### Key Requirements

- **ARC-AUD-0001**: Framework-agnostic with no Laravel dependencies
- **ARC-AUD-0002**: All data structures via interfaces
- **BUS-AUD-0145**: Logs must include log_name, description, timestamp
- **BUS-AUD-0146**: Audit levels: 1 (Low), 2 (Medium), 3 (High), 4 (Critical)
- **BUS-AUD-0147**: Default retention 90 days
- **FUN-AUD-0185**: Automatic CRUD capture
- **FUN-AUD-0192**: Automatic sensitive data masking

## Implementation in Atomy

To use in Laravel (Atomy app):

1. Create migration for `audit_logs` table
2. Create Eloquent model implementing `AuditLogInterface`
3. Create repository implementing `AuditLogRepositoryInterface`
4. Bind contracts in service provider
5. Create audit trait for models
6. Register API endpoints

See `apps/Atomy/` for implementation examples.

## Documentation

### Quick Links

- **[Getting Started Guide](docs/getting-started.md)** - Installation and basic configuration
- **[API Reference](docs/api-reference.md)** - Complete API documentation
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Examples](docs/examples/)** - Runnable code examples

### Package Documentation

- **[Requirements](REQUIREMENTS.md)** - Comprehensive requirements traceability
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Package structure and metrics
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - Test coverage and strategy
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package valuation and ROI analysis

### Additional Resources

- **Package Overview**: User-friendly audit logging for CRUD tracking and compliance
- **Key Differentiator**: Search/export/retention focus vs Nexus\Audit's cryptographic verification
- **Total Files**: 14 PHP files, 1,363 lines of code
- **Test Coverage**: 58 tests planned (unit, integration, feature)
- **Package Value**: $65,000 (158% ROI, compliance infrastructure)

## Testing

```bash
composer test
```

## License

MIT
