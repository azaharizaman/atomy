# Integration Guide: AuditLogger

This guide shows how to integrate the AuditLogger package into your application.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/audit-logger:"*@dev"
```

### Step 2: Create Database Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->nullable()->index();
            $table->string('log_name');
            $table->text('description');
            $table->string('subject_type')->nullable()->index();
            $table->string('subject_id', 26)->nullable()->index();
            $table->string('causer_type')->nullable();
            $table->string('causer_id', 26)->nullable()->index();
            $table->json('properties')->nullable();
            $table->unsignedTinyInteger('level')->default(2); // 1-4
            $table->string('batch_uuid', 36)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');
            
            // Indexes
            $table->index('log_name');
            $table->index('level');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

### Step 3: Create Eloquent Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\AuditLogger\Contracts\AuditLogInterface;

class AuditLog extends Model implements AuditLogInterface
{
    const UPDATED_AT = null; // No updated_at column
    
    protected $fillable = [
        'id',
        'tenant_id',
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'level',
        'batch_uuid',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'properties' => 'array',
        'level' => 'integer',
        'created_at' => 'datetime',
    ];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }
    
    public function getLogName(): string
    {
        return $this->log_name;
    }
    
    public function getDescription(): string
    {
        return $this->description;
    }
    
    public function getSubjectType(): ?string
    {
        return $this->subject_type;
    }
    
    public function getSubjectId(): ?string
    {
        return $this->subject_id;
    }
    
    public function getCauserType(): ?string
    {
        return $this->causer_type;
    }
    
    public function getCauserId(): ?string
    {
        return $this->causer_id;
    }
    
    public function getProperties(): array
    {
        return $this->properties ?? [];
    }
    
    public function getLevel(): int
    {
        return $this->level;
    }
    
    public function getBatchUuid(): ?string
    {
        return $this->batch_uuid;
    }
    
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }
    
    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->created_at);
    }
}
```

### Step 4: Create Repository Implementation

```php
<?php

namespace App\Repositories;

use Nexus\AuditLogger\Contracts\{AuditLogRepositoryInterface, AuditLogInterface};
use Nexus\AuditLogger\Exceptions\AuditLogNotFoundException;
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
        $model = AuditLog::find($id);
        
        if (!$model) {
            throw AuditLogNotFoundException::forId($id);
        }
        
        return $model;
    }
    
    public function findAll(?string $tenantId = null): array
    {
        $query = AuditLog::query();
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query->orderBy('created_at', 'desc')->get()->all();
    }
    
    public function search(
        ?string $tenantId = null,
        ?string $keyword = null,
        ?string $entityType = null,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
        ?int $level = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $query = AuditLog::query();
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('description', 'like', "%{$keyword}%")
                  ->orWhere('log_name', 'like', "%{$keyword}%");
            });
        }
        
        if ($entityType) {
            $query->where('subject_type', $entityType);
        }
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        if ($level !== null) {
            $query->where('level', $level);
        }
        
        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->all();
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
}
```

### Step 5: Create Configuration Implementation

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
        return config('audit.sensitive_fields', [
            'password',
            'password_confirmation',
            'token',
            'api_token',
            'api_key',
            'secret',
            'credit_card',
            'ssn',
        ]);
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

### Step 6: Create Service Provider

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

### Step 7: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\AuditLoggerServiceProvider::class,
],
```

### Step 8: Create Configuration File

Create `config/audit.php`:

```php
<?php

return [
    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
    'async_logging' => env('AUDIT_ASYNC_LOGGING', true),
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

### Step 9: Use in Controller

```php
<?php

namespace App\Http\Controllers;

use Nexus\AuditLogger\Services\AuditLogManager;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogManager $auditManager
    ) {}
    
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
        ]);
        
        $user = User::findOrFail($id);
        $oldData = $user->toArray();
        
        $user->update($validated);
        
        // Log the update
        $this->auditManager->log(
            logName: 'user_updated',
            description: "User {$user->name} updated",
            subjectType: 'User',
            subjectId: $id,
            causerType: 'User',
            causerId: auth()->id(),
            properties: [
                'old' => $oldData,
                'new' => $user->toArray(),
            ],
            level: 2,
            tenantId: $user->tenant_id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );
        
        return response()->json($user);
    }
}
```

### Step 10: Create Auditable Trait (Optional)

```php
<?php

namespace App\Traits;

use Nexus\AuditLogger\Services\AuditLogManager;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            app(AuditLogManager::class)->log(
                logName: strtolower(class_basename($model)) . '_created',
                description: class_basename($model) . " created",
                subjectType: class_basename($model),
                subjectId: $model->id,
                causerType: 'User',
                causerId: auth()->id(),
                properties: ['attributes' => $model->toArray()],
                level: $model->getAuditLevel(),
                tenantId: $model->tenant_id ?? null
            );
        });
        
        static::updated(function ($model) {
            app(AuditLogManager::class)->log(
                logName: strtolower(class_basename($model)) . '_updated',
                description: class_basename($model) . " updated",
                subjectType: class_basename($model),
                subjectId: $model->id,
                causerType: 'User',
                causerId: auth()->id(),
                properties: [
                    'old' => $model->getOriginal(),
                    'new' => $model->toArray(),
                ],
                level: $model->getAuditLevel(),
                tenantId: $model->tenant_id ?? null
            );
        });
        
        static::deleted(function ($model) {
            app(AuditLogManager::class)->log(
                logName: strtolower(class_basename($model)) . '_deleted',
                description: class_basename($model) . " deleted",
                subjectType: class_basename($model),
                subjectId: $model->id,
                causerType: 'User',
                causerId: auth()->id(),
                properties: ['attributes' => $model->toArray()],
                level: $model->getAuditLevel(),
                tenantId: $model->tenant_id ?? null
            );
        });
    }
    
    protected function getAuditLevel(): int
    {
        return 2; // Medium by default
    }
}
```

### Step 11: Create Scheduled Command for Purging

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\AuditLogger\Services\RetentionPolicyService;
use Nexus\AuditLogger\ValueObjects\RetentionPolicy;

class PurgeExpiredAuditLogsCommand extends Command
{
    protected $signature = 'audit:purge';
    protected $description = 'Purge expired audit logs based on retention policy';
    
    public function handle(RetentionPolicyService $retentionService): int
    {
        $this->info('Purging expired audit logs...');
        
        $retentionDays = config('audit.retention_days', 90);
        $policy = new RetentionPolicy($retentionDays);
        
        $deletedCount = $retentionService->purgeExpiredLogs(policy: $policy);
        
        $this->info("Purged {$deletedCount} expired audit logs.");
        
        return self::SUCCESS;
    }
}
```

Register in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('audit:purge')->daily();
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/audit-logger:"*@dev"
```

### Step 2: Create Doctrine Entity

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\AuditLogger\Contracts\AuditLogInterface;

#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
class AuditLog implements AuditLogInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;
    
    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $tenantId = null;
    
    #[ORM\Column(type: 'string')]
    private string $logName;
    
    #[ORM\Column(type: 'text')]
    private string $description;
    
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $subjectType = null;
    
    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $subjectId = null;
    
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $causerType = null;
    
    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $causerId = null;
    
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $properties = [];
    
    #[ORM\Column(type: 'smallint')]
    private int $level = 2;
    
    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $batchUuid = null;
    
    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;
    
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userAgent = null;
    
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;
    
    public function __construct(
        string $id,
        string $logName,
        string $description,
        int $level = 2
    ) {
        $this->id = $id;
        $this->logName = $logName;
        $this->description = $description;
        $this->level = $level;
        $this->createdAt = new \DateTimeImmutable();
    }
    
    // Implement all interface methods...
    public function getId(): string { return $this->id; }
    public function getTenantId(): ?string { return $this->tenantId; }
    public function getLogName(): string { return $this->logName; }
    public function getDescription(): string { return $this->description; }
    public function getSubjectType(): ?string { return $this->subjectType; }
    public function getSubjectId(): ?string { return $this->subjectId; }
    public function getCauserType(): ?string { return $this->causerType; }
    public function getCauserId(): ?string { return $this->causerId; }
    public function getProperties(): array { return $this->properties ?? []; }
    public function getLevel(): int { return $this->level; }
    public function getBatchUuid(): ?string { return $this->batchUuid; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    
    // Setters for building the entity...
    public function setTenantId(?string $tenantId): void { $this->tenantId = $tenantId; }
    public function setSubjectType(?string $subjectType): void { $this->subjectType = $subjectType; }
    public function setSubjectId(?string $subjectId): void { $this->subjectId = $subjectId; }
    public function setCauserType(?string $causerType): void { $this->causerType = $causerType; }
    public function setCauserId(?string $causerId): void { $this->causerId = $causerId; }
    public function setProperties(?array $properties): void { $this->properties = $properties; }
    public function setBatchUuid(?string $batchUuid): void { $this->batchUuid = $batchUuid; }
    public function setIpAddress(?string $ipAddress): void { $this->ipAddress = $ipAddress; }
    public function setUserAgent(?string $userAgent): void { $this->userAgent = $userAgent; }
}
```

### Step 3: Create Repository

```php
<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\AuditLogger\Contracts\{AuditLogRepositoryInterface, AuditLogInterface};
use Nexus\AuditLogger\Exceptions\AuditLogNotFoundException;
use App\Entity\AuditLog;

class AuditLogRepository extends ServiceEntityRepository implements AuditLogRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }
    
    public function save(AuditLogInterface $auditLog): void
    {
        $this->getEntityManager()->persist($auditLog);
        $this->getEntityManager()->flush();
    }
    
    public function findById(string $id): AuditLogInterface
    {
        $log = $this->find($id);
        
        if (!$log) {
            throw AuditLogNotFoundException::forId($id);
        }
        
        return $log;
    }
    
    public function findAll(?string $tenantId = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');
        
        if ($tenantId) {
            $qb->where('a.tenantId = :tenantId')
               ->setParameter('tenantId', $tenantId);
        }
        
        return $qb->getQuery()->getResult();
    }
    
    public function search(
        ?string $tenantId = null,
        ?string $keyword = null,
        ?string $entityType = null,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
        ?int $level = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('a');
        
        if ($tenantId) {
            $qb->andWhere('a.tenantId = :tenantId')
               ->setParameter('tenantId', $tenantId);
        }
        
        if ($keyword) {
            $qb->andWhere('a.description LIKE :keyword OR a.logName LIKE :keyword')
               ->setParameter('keyword', "%{$keyword}%");
        }
        
        if ($entityType) {
            $qb->andWhere('a.subjectType = :entityType')
               ->setParameter('entityType', $entityType);
        }
        
        if ($startDate) {
            $qb->andWhere('a.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }
        
        if ($endDate) {
            $qb->andWhere('a.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }
        
        if ($level !== null) {
            $qb->andWhere('a.level = :level')
               ->setParameter('level', $level);
        }
        
        return $qb->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
    
    public function delete(string $id): void
    {
        $log = $this->find($id);
        
        if ($log) {
            $this->getEntityManager()->remove($log);
            $this->getEntityManager()->flush();
        }
    }
    
    public function deleteOlderThan(\DateTimeImmutable $date, ?string $tenantId = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->delete()
            ->where('a.createdAt < :date')
            ->setParameter('date', $date);
        
        if ($tenantId) {
            $qb->andWhere('a.tenantId = :tenantId')
               ->setParameter('tenantId', $tenantId);
        }
        
        return $qb->getQuery()->execute();
    }
}
```

### Step 4: Configure Services

`config/services.yaml`:

```yaml
services:
    # Repository binding
    Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface:
        class: App\Repository\AuditLogRepository
        
    # Config binding
    Nexus\AuditLogger\Contracts\AuditConfigInterface:
        class: App\Service\AuditConfig
        
    # Services
    Nexus\AuditLogger\Services\AuditLogManager:
        arguments:
            $repository: '@Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface'
            $config: '@Nexus\AuditLogger\Contracts\AuditConfigInterface'
```

---

## Testing

### Unit Testing (PHPUnit)

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nexus\AuditLogger\Services\AuditLogManager;
use Nexus\AuditLogger\Contracts\{AuditLogRepositoryInterface, AuditConfigInterface};

class AuditLogManagerTest extends TestCase
{
    public function test_log_creates_audit_record(): void
    {
        $repository = $this->createMock(AuditLogRepositoryInterface::class);
        $config = $this->createMock(AuditConfigInterface::class);
        
        $repository->expects($this->once())
            ->method('save');
        
        $manager = new AuditLogManager($repository, $config);
        
        $logId = $manager->log(
            logName: 'test_log',
            description: 'Test description',
            level: 2
        );
        
        $this->assertIsString($logId);
    }
}
```

### Integration Testing (Laravel)

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Nexus\AuditLogger\Services\AuditLogManager;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_update_creates_audit_log(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->putJson("/api/users/{$user->id}", [
                'name' => 'Updated Name',
            ])
            ->assertOk();
        
        $this->assertDatabaseHas('audit_logs', [
            'log_name' => 'user_updated',
            'subject_type' => 'User',
            'subject_id' => $user->id,
        ]);
    }
}
```

---

## Troubleshooting

### Issue: Audit logs not appearing

**Error:** No records in audit_logs table

**Solution:**
1. Verify service provider is registered in `config/app.php`
2. Verify repository interface is bound: `php artisan tinker` → `app(AuditLogRepositoryInterface::class)`
3. Check database migration was run: `php artisan migrate:status`

---

### Issue: Sensitive data not masked

**Error:** Passwords appearing in audit logs

**Solution:**
1. Verify `AuditConfigInterface::getSensitiveFields()` returns correct field names
2. Ensure `SensitiveDataMasker` is being used in `AuditLogManager`
3. Check field names match exactly (case-sensitive)

---

### Issue: Performance degradation

**Error:** Slow response times

**Solution:**
1. Enable async logging: `config('audit.async_logging', true)`
2. Add database indexes on frequently queried columns
3. Implement retention policy to purge old logs

---

## Performance Optimization

### Database Indexes

Ensure indexes exist on frequently queried columns:

```php
$table->index('tenant_id');
$table->index('log_name');
$table->index('subject_type');
$table->index('subject_id');
$table->index('causer_id');
$table->index('level');
$table->index('batch_uuid');
$table->index('created_at');
```

### Async Logging (Laravel)

Create queue job:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Nexus\AuditLogger\Contracts\AuditLogInterface;

class ProcessAuditLogJob implements ShouldQueue
{
    use Queueable;
    
    public function __construct(
        private readonly AuditLogInterface $auditLog
    ) {}
    
    public function handle(AuditLogRepositoryInterface $repository): void
    {
        $repository->save($this->auditLog);
    }
}
```

---

**Integration Complete!** Your application now has comprehensive audit logging capabilities.
