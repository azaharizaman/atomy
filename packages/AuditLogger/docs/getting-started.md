# Getting Started with Nexus AuditLogger

## Prerequisites

- PHP 8.3 or higher
- Composer
- A persistence layer (database, Redis, etc.)

## Installation

```bash
composer require nexus/audit-logger:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ **User Activity Tracking** - Monitor who did what and when
- ✅ **CRUD Operation Logging** - Automatic tracking of create, update, delete operations
- ✅ **Compliance Requirements** - Meet SOX, GDPR, HIPAA audit trail requirements
- ✅ **Debugging & Forensics** - Investigate issues by reviewing activity history
- ✅ **Timeline Views** - Display user-facing activity feeds
- ✅ **Search & Export** - Find and export audit records

Do NOT use this package for:
- ❌ **Cryptographic Verification** - Use `Nexus\Audit` for hash chains and signatures
- ❌ **Immutable Audit Trails** - Use `Nexus\Audit` for tamper-evident logging
- ❌ **Financial Transaction Logging** - Use `Nexus\Audit` for GL/inventory event sourcing
- ❌ **High-Security Forensics** - Use `Nexus\Audit` for cryptographically verified logs

## Core Concepts

### Concept 1: Audit Logs vs Audit Engine

**AuditLogger (This Package):**
- User-friendly search, export, timeline views
- Masked data for display
- Primarily async logging
- Focus: Developer productivity and user experience

**Audit Engine (Nexus\Audit):**
- Cryptographic hash chains
- Immutable, verifiable audit trail
- Sync logging for critical events
- Focus: Legal defensibility and compliance

### Concept 2: Audit Levels

The package supports 4 audit levels for risk-based filtering:

- **Low (1):** Routine activities (e.g., user login, document view)
- **Medium (2):** Standard operations (e.g., record update, file upload)
- **High (3):** Sensitive operations (e.g., role assignment, configuration change)
- **Critical (4):** High-value activities (e.g., financial transaction, data export)

### Concept 3: Retention Policies

Audit logs can be automatically purged based on configurable retention periods:

- **30 days:** Low-priority logs (routine activities)
- **90 days:** Standard retention (default)
- **365 days:** Compliance retention (SOX, GDPR)
- **Custom:** Define your own retention period

### Concept 4: Sensitive Data Masking

The package automatically masks sensitive fields in audit logs:

- Passwords → `********`
- Tokens → `tok_****LAST4`
- API Keys → `sk_****LAST4`
- Credit Cards → `****-****-****-1234`

### Concept 5: Batch Operations

Related activities can be grouped using `batch_uuid`:

```php
$batchUuid = Str::uuid();

$auditManager->log(/* ... */, batchUuid: $batchUuid);
$auditManager->log(/* ... */, batchUuid: $batchUuid);
$auditManager->log(/* ... */, batchUuid: $batchUuid);
```

---

## Basic Configuration

### Step 1: Implement Required Interfaces

In your application layer (e.g., Laravel), implement the repository interface:

```php
<?php

namespace App\Repositories;

use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Nexus\AuditLogger\Contracts\AuditLogInterface;
use App\Models\AuditLog;

final readonly class DbAuditLogRepository implements AuditLogRepositoryInterface
{
    public function save(AuditLogInterface $auditLog): void
    {
        AuditLog::create([
            'id' => $auditLog->getId(),
            'tenant_id' => $auditLog->getTenantId(),
            'log_name' => $auditLog->getLogName(),
            'description' => $auditLog->getDescription(),
            'subject_type' => $auditLog->getSubjectType(),
            'subject_id' => $auditLog->getSubjectId(),
            'causer_type' => $auditLog->getCauserType(),
            'causer_id' => $auditLog->getCauserId(),
            'properties' => $auditLog->getProperties(),
            'level' => $auditLog->getLevel(),
            'batch_uuid' => $auditLog->getBatchUuid(),
            'ip_address' => $auditLog->getIpAddress(),
            'user_agent' => $auditLog->getUserAgent(),
            'created_at' => $auditLog->getCreatedAt(),
        ]);
    }
    
    public function findById(string $id): AuditLogInterface
    {
        $model = AuditLog::findOrFail($id);
        return $this->modelToInterface($model);
    }
    
    public function findAll(?string $tenantId = null): array
    {
        $query = AuditLog::query();
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query->get()->map(fn($m) => $this->modelToInterface($m))->all();
    }
    
    public function delete(string $id): void
    {
        AuditLog::where('id', $id)->delete();
    }
    
    public function deleteOlderThan(\DateTimeImmutable $date, ?string $tenantId = null): int
    {
        $query = AuditLog::where('created_at', '<', $date);
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query->delete();
    }
    
    private function modelToInterface(AuditLog $model): AuditLogInterface
    {
        return new \App\ValueObjects\AuditLogRecord(
            id: $model->id,
            tenantId: $model->tenant_id,
            logName: $model->log_name,
            description: $model->description,
            subjectType: $model->subject_type,
            subjectId: $model->subject_id,
            causerType: $model->causer_type,
            causerId: $model->causer_id,
            properties: $model->properties ?? [],
            level: $model->level,
            batchUuid: $model->batch_uuid,
            ipAddress: $model->ip_address,
            userAgent: $model->user_agent,
            createdAt: new \DateTimeImmutable($model->created_at)
        );
    }
}
```

### Step 2: Implement Configuration Interface

```php
<?php

namespace App\Services;

use Nexus\AuditLogger\Contracts\AuditConfigInterface;

final readonly class AuditConfig implements AuditConfigInterface
{
    public function getDefaultRetentionDays(): int
    {
        return config('audit.retention_days', 90);
    }
    
    public function getSensitiveFields(): array
    {
        return [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'ssn',
        ];
    }
    
    public function isAsyncEnabled(): bool
    {
        return config('audit.async_logging', true);
    }
    
    public function getExportFormats(): array
    {
        return ['csv', 'json', 'pdf'];
    }
}
```

### Step 3: Bind Interfaces in Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\AuditLogger\Contracts\{
    AuditLogRepositoryInterface,
    AuditConfigInterface
};
use App\Repositories\DbAuditLogRepository;
use App\Services\AuditConfig;

class AuditLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            AuditLogRepositoryInterface::class,
            DbAuditLogRepository::class
        );
        
        // Bind config
        $this->app->singleton(
            AuditConfigInterface::class,
            AuditConfig::class
        );
    }
}
```

### Step 4: Create Configuration File

Create `config/audit.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Retention Period (Days)
    |--------------------------------------------------------------------------
    |
    | Audit logs older than this many days will be automatically purged.
    | Set to 0 to disable automatic purging.
    |
    */
    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
    
    /*
    |--------------------------------------------------------------------------
    | Asynchronous Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, audit logs are queued for background processing.
    | This prevents audit logging from impacting user-facing request latency.
    |
    */
    'async_logging' => env('AUDIT_ASYNC_LOGGING', true),
    
    /*
    |--------------------------------------------------------------------------
    | Sensitive Fields
    |--------------------------------------------------------------------------
    |
    | Fields that should be automatically masked in audit logs.
    |
    */
    'sensitive_fields' => [
        'password',
        'password_confirmation',
        'token',
        'api_token',
        'api_key',
        'secret',
        'credit_card',
        'ssn',
    ],
];
```

---

## Your First Integration

Here's a complete example of logging a user update:

```php
<?php

namespace App\Services;

use Nexus\AuditLogger\Services\AuditLogManager;
use Nexus\AuditLogger\ValueObjects\AuditLevel;

final readonly class UserService
{
    public function __construct(
        private AuditLogManager $auditManager
    ) {}
    
    public function updateUser(string $userId, array $data, string $currentUserId): void
    {
        // Get old user data
        $user = User::findOrFail($userId);
        $oldData = $user->toArray();
        
        // Update user
        $user->update($data);
        
        // Log the update
        $this->auditManager->log(
            logName: 'user_updated',
            description: "User {$user->name} updated by " . auth()->user()->name,
            subjectType: 'User',
            subjectId: $userId,
            causerType: 'User',
            causerId: $currentUserId,
            properties: [
                'old' => $oldData,
                'new' => $user->toArray(),
            ],
            level: AuditLevel::Medium->value,
            tenantId: $user->tenant_id,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent()
        );
    }
}
```

**Result:** An audit log is created with:
- Who made the change (causer)
- What was changed (subject)
- Old vs new values (properties)
- When it happened (timestamp)
- Where it happened (IP address)
- Audit level (Medium)

---

## Automatic CRUD Tracking with Auditable Trait

For even easier integration, use the `Auditable` trait in your Eloquent models:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Invoice extends Model
{
    use Auditable;
    
    // Optionally customize audit behavior
    protected function getAuditExclude(): array
    {
        return ['updated_at']; // Don't log timestamp changes
    }
    
    protected function getAuditLevel(): int
    {
        return 3; // High level for invoices
    }
}
```

Now every create, update, and delete operation on `Invoice` is automatically logged!

---

## Searching Audit Logs

```php
use Nexus\AuditLogger\Services\AuditLogSearchService;

$searchService = app(AuditLogSearchService::class);

$results = $searchService->search(
    tenantId: 'tenant-123',
    keyword: 'invoice',
    entityType: 'Invoice',
    startDate: new \DateTimeImmutable('2025-01-01'),
    endDate: new \DateTimeImmutable('2025-12-31'),
    level: 3, // High level only
    limit: 100,
    offset: 0
);

foreach ($results as $log) {
    echo "{$log->getDescription()} at {$log->getCreatedAt()->format('Y-m-d H:i:s')}\n";
}
```

---

## Exporting Audit Logs

```php
use Nexus\AuditLogger\Services\AuditLogExportService;

$exportService = app(AuditLogExportService::class);

// Export to CSV
$csv = $exportService->exportToCsv(
    tenantId: 'tenant-123',
    startDate: new \DateTimeImmutable('2025-01-01'),
    endDate: new \DateTimeImmutable('2025-12-31')
);

// Download CSV
return response()->streamDownload(function() use ($csv) {
    echo $csv;
}, 'audit-logs.csv');

// Export to JSON
$json = $exportService->exportToJson(/* ... */);

// Export to PDF
$pdf = $exportService->exportToPdf(/* ... */);
```

---

## Retention Policy Enforcement

```php
use Nexus\AuditLogger\Services\RetentionPolicyService;
use Nexus\AuditLogger\ValueObjects\RetentionPolicy;

$retentionService = app(RetentionPolicyService::class);

// Purge logs older than 90 days
$deletedCount = $retentionService->purgeExpiredLogs(
    tenantId: 'tenant-123',
    policy: RetentionPolicy::days90()
);

echo "Deleted {$deletedCount} expired audit logs\n";
```

**Automate with Scheduled Command:**

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('audit:purge')->daily();
}
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples

---

## Troubleshooting

### Common Issues

**Issue 1: Audit logs not appearing**
- **Cause:** Repository interface not bound in service provider
- **Solution:** Verify `AuditLogRepositoryInterface` is bound in `AuditLoggerServiceProvider`

**Issue 2: Sensitive data not being masked**
- **Cause:** Sensitive fields not configured
- **Solution:** Add field names to `AuditConfigInterface::getSensitiveFields()`

**Issue 3: Performance degradation**
- **Cause:** Synchronous logging blocking requests
- **Solution:** Enable async logging in `config/audit.php`: `'async_logging' => true`

**Issue 4: Audit logs growing too large**
- **Cause:** No retention policy enforcement
- **Solution:** Schedule `audit:purge` command to run daily

**Issue 5: Cannot search by entity type**
- **Cause:** Repository not implementing search filters correctly
- **Solution:** Verify repository `search()` method applies all filters

---

## Best Practices

1. **Use Async Logging for High-Volume Operations:** Enable `async_logging` to prevent performance impact
2. **Set Appropriate Audit Levels:** Use Low for routine activities, Critical for financial transactions
3. **Mask Sensitive Data:** Always configure `sensitive_fields` to prevent leaking secrets
4. **Implement Retention Policies:** Automatically purge old logs to manage database size
5. **Use Batch UUID for Related Operations:** Group multi-step operations for better traceability
6. **Add Contextual Information:** Include IP address, user agent, and descriptive messages
7. **Test Audit Logging:** Verify logs are created correctly in your test suite

---

**Quick Start Complete!** You now have a working audit logging system integrated into your application.
