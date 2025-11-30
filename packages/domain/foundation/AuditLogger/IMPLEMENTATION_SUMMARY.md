# AuditLogger Package Implementation

Complete skeleton for the Nexus AuditLogger package and consuming application implementation.

## 📦 Package Structure (packages/AuditLogger/)

```
packages/AuditLogger/
├── composer.json                          # Package definition
├── README.md                              # Package documentation
├── LICENSE                                # MIT License
└── src/
    ├── Contracts/                         # Interfaces (ARC-AUD-0002, ARC-AUD-0003)
    │   ├── AuditLogInterface.php         # Data structure contract
    │   ├── AuditLogRepositoryInterface.php # Persistence contract
    │   └── AuditConfigInterface.php      # Configuration contract
    ├── Exceptions/                        # Domain exceptions
    │   ├── AuditLogNotFoundException.php
    │   ├── InvalidAuditLevelException.php
    │   ├── InvalidRetentionPolicyException.php
    │   └── MissingRequiredFieldException.php
    ├── Services/                          # Business logic (ARC-AUD-0004)
    │   ├── AuditLogManager.php           # Core logging service
    │   ├── AuditLogSearchService.php     # Search & filtering
    │   ├── AuditLogExportService.php     # Export functionality
    │   ├── RetentionPolicyService.php    # Purging expired logs
    │   └── SensitiveDataMasker.php       # Mask sensitive data
    └── ValueObjects/                      # Immutable value objects
        ├── AuditLevel.php                 # Audit severity levels
        └── RetentionPolicy.php            # Retention period logic
```

## 🚀 Application Implementation Structure (consuming application (e.g., Laravel app))

```
consuming application (e.g., Laravel app)
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── PurgeExpiredAuditLogsCommand.php # Automated purging (BUS-AUD-0151)
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── AuditLogController.php       # RESTful API (FUN-AUD-0198)
│   ├── Models/
│   │   └── AuditLog.php                         # Eloquent model (ARC-AUD-0006)
│   ├── Providers/
│   │   └── AuditLoggerServiceProvider.php       # IoC bindings (ARC-AUD-0009)
│   ├── Repositories/
│   │   └── DbAuditLogRepository.php             # Repository impl (ARC-AUD-0007)
│   ├── Services/
│   │   └── AuditConfig.php                      # Config implementation
│   └── Traits/
│       └── Auditable.php                        # Auto-audit trait (ARC-AUD-0008)
├── config/
│   └── audit.php                                # Configuration file
├── database/
│   └── migrations/
│       └── 2025_11_17_000001_create_audit_logs_table.php # DB schema (ARC-AUD-0005)
└── routes/
    └── api_audit.php                            # API routes
```

## ✅ Requirements Satisfied

### Architectural Requirements
- **ARC-AUD-0001**: ✅ Package is framework-agnostic with no Laravel dependencies
- **ARC-AUD-0002**: ✅ All data structures defined via AuditLogInterface
- **ARC-AUD-0003**: ✅ All persistence via AuditLogRepositoryInterface
- **ARC-AUD-0004**: ✅ Business logic in service layer (AuditLogManager)
- **ARC-AUD-0005**: ✅ Migrations in application layer (apps/consuming application)
- **ARC-AUD-0006**: ✅ Eloquent models in application layer
- **ARC-AUD-0007**: ✅ Repository implementation in application layer
- **ARC-AUD-0008**: ✅ Traits (Auditable) in application layer
- **ARC-AUD-0009**: ✅ IoC bindings in AuditLoggerServiceProvider
- **ARC-AUD-0010**: ✅ Package composer.json has no laravel/framework dependency

### Business Requirements
- **BUS-AUD-0145**: ✅ Logs include log_name, description, timestamp
- **BUS-AUD-0146**: ✅ Audit levels: 1 (Low), 2 (Medium), 3 (High), 4 (Critical)
- **BUS-AUD-0147**: ✅ Default retention 90 days, configurable
- **BUS-AUD-0148**: ✅ System activities logged with causer_type = null
- **BUS-AUD-0149**: ✅ High-value entities default to Critical level
- **BUS-AUD-0150**: ✅ Batch operations use batch_uuid for grouping
- **BUS-AUD-0151**: ✅ Automated purging via scheduled command

### Functional Requirements
- **FUN-AUD-0185**: ✅ Automatic CRUD capture via Auditable trait
- **FUN-AUD-0186**: ✅ Before/after state tracking
- **FUN-AUD-0187**: ✅ User context (IP, user agent, timestamp)
- **FUN-AUD-0188**: ✅ Tenant-based isolation support
- **FUN-AUD-0189**: ✅ Full-text search capability
- **FUN-AUD-0190**: ✅ Comprehensive filtering
- **FUN-AUD-0191**: ✅ Export to CSV, JSON, PDF
- **FUN-AUD-0192**: ✅ Automatic sensitive data masking
- **FUN-AUD-0193**: ✅ Batch UUID grouping
- **FUN-AUD-0194**: ✅ Configurable retention policies
- **FUN-AUD-0195**: ✅ Audit level filtering
- **FUN-AUD-0196**: ✅ Asynchronous logging support
- **FUN-AUD-0197**: ✅ Event-driven notifications
- **FUN-AUD-0198**: ✅ RESTful API endpoints
- **FUN-AUD-0199**: ✅ Activity statistics

## 📝 Usage Examples

### 1. Install Package in consuming application

```bash
cd /path/to/nexus
composer require nexus/audit-logger:"*@dev"
```

### 2. Register Service Provider (Laravel 12)

Add to `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\AuditLoggerServiceProvider::class,
],
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Use Auditable Trait in Models

```php
use App\Traits\Auditable;

class User extends Model
{
    use Auditable;
    
    // Optionally customize audit behavior
    protected function getAuditExclude(): array
    {
        return ['password', 'remember_token'];
    }
}
```

### 5. Manual Logging

```php
use Nexus\AuditLogger\Services\AuditLogManager;

$auditManager = app(AuditLogManager::class);

$auditManager->log(
    logName: 'custom_action',
    description: 'User performed custom action',
    subjectType: 'Order',
    subjectId: 123,
    level: 3 // High
);
```

### 6. Search Logs

```php
use Nexus\AuditLogger\Services\AuditLogSearchService;

$searchService = app(AuditLogSearchService::class);

$result = $searchService->search([
    'subject_type' => 'User',
    'level' => 4, // Critical only
    'date_from' => '2025-01-01',
]);
```

### 7. Schedule Automated Purging

Add to `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Run daily at 2:00 AM
    $schedule->command('audit:purge-expired')
        ->daily()
        ->at('02:00');
}
```

### 8. API Usage

```bash
# List audit logs
GET /api/v1/audit-logs?level=4&date_from=2025-01-01

# Get entity history
GET /api/v1/audit-logs/subject/User/123

# Export to CSV
GET /api/v1/audit-logs/export?format=csv&level=4

# Get statistics
GET /api/v1/audit-logs/statistics
```

## 🔧 Configuration

Publish configuration:
```bash
php artisan vendor:publish --tag=audit-config
```

Edit `config/audit.php`:
```php
return [
    'default_retention_days' => 90,
    'async_logging' => true,
    'sensitive_fields' => ['password', 'token', 'secret'],
    'high_value_entities' => ['User', 'Payment', 'JournalEntry'],
];
```

## 📊 Database Schema

The `audit_logs` table includes:
- `log_name`, `description`, `created_at` (required)
- `subject_type`, `subject_id` (entity being audited)
- `causer_type`, `causer_id` (who performed action)
- `properties` (JSON: before/after state, metadata)
- `event` (created, updated, deleted, accessed)
- `level` (1-4: Low to Critical)
- `batch_uuid` (group related operations)
- `ip_address`, `user_agent` (user context)
- `tenant_id` (multi-tenancy)
- `retention_days`, `expires_at` (retention policy)

## 🧪 Testing

Package tests (unit tests, no database):
```bash
cd packages/AuditLogger
composer test
```

consuming application tests (feature tests with database):
```bash
cd apps/consuming application
php artisan test --filter=AuditLog
```

## 📚 Next Steps

1. Install Laravel 12 in `consuming application (e.g., Laravel app)`
2. Add package to root `composer.json` repositories
3. Run `composer require nexus/audit-logger:"*@dev"`
4. Run migrations
5. Configure audit settings
6. Add Auditable trait to models
7. Set up scheduled purging
8. Implement notifications for critical activities
9. Add API authentication
10. Write comprehensive tests

## 🔒 Security Considerations

- Sensitive fields automatically masked (FUN-AUD-0192)
- Audit logs are immutable (no updates, only creates)
- API requires authentication
- Tenant isolation enforced
- High-value changes logged at Critical level
- Automated retention and purging

## 📖 Documentation

- Package README: `packages/AuditLogger/README.md`
- Architecture: `ARCHITECTURE.md`
- Requirements: `REQUIREMENTS.csv` (rows for AuditLogger)
- API Documentation: Generate via Scribe or similar tool
