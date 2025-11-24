# Integration Guide: Audit

This guide shows how to integrate the Nexus\Audit package into Laravel and Symfony applications.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/audit:"*@dev"
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
        Schema::create('audit_records', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->unsignedBigInteger('sequence_number');
            $table->string('entity_id', 26)->index();
            $table->string('action', 50);
            $table->string('level', 20); // Info, Warning, Critical
            $table->string('previous_hash', 64)->nullable();
            $table->string('record_hash', 64);
            $table->text('signature')->nullable(); // Ed25519 signature
            $table->string('signed_by', 26)->nullable();
            $table->json('metadata')->nullable();
            $table->string('user_id', 26)->nullable();
            $table->timestamp('created_at');
            
            $table->unique(['tenant_id', 'sequence_number']);
            $table->index(['entity_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('audit_records');
    }
};
```

### Step 3: Create Eloquent Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Audit\Contracts\AuditRecordInterface;
use Nexus\Audit\ValueObjects\{AuditHash, AuditSignature, AuditLevel};

class AuditRecord extends Model implements AuditRecordInterface
{
    const UPDATED_AT = null; // Immutable - no updates
    
    protected $fillable = [
        'id', 'tenant_id', 'sequence_number', 'entity_id', 
        'action', 'level', 'previous_hash', 'record_hash',
        'signature', 'signed_by', 'metadata', 'user_id'
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime:immutable',
        'level' => AuditLevel::class,
    ];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }
    
    public function getSequenceNumber(): int
    {
        return $this->sequence_number;
    }
    
    public function getEntityId(): string
    {
        return $this->entity_id;
    }
    
    public function getAction(): string
    {
        return $this->action;
    }
    
    public function getLevel(): AuditLevel
    {
        return $this->level;
    }
    
    public function getPreviousHash(): ?AuditHash
    {
        return $this->previous_hash ? new AuditHash($this->previous_hash) : null;
    }
    
    public function getRecordHash(): AuditHash
    {
        return new AuditHash($this->record_hash);
    }
    
    public function getSignature(): ?AuditSignature
    {
        return $this->signature 
            ? new AuditSignature($this->signature, $this->signed_by)
            : null;
    }
    
    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }
    
    public function getUserId(): ?string
    {
        return $this->user_id;
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }
}
```

### Step 4: Create Repository Implementation

```php
<?php

namespace App\Repositories;

use Nexus\Audit\Contracts\{AuditStorageInterface, AuditRecordInterface};
use App\Models\AuditRecord;

final readonly class EloquentAuditStorage implements AuditStorageInterface
{
    public function append(AuditRecordInterface $record): void
    {
        AuditRecord::create([
            'id' => $record->getId(),
            'tenant_id' => $record->getTenantId(),
            'sequence_number' => $record->getSequenceNumber(),
            'entity_id' => $record->getEntityId(),
            'action' => $record->getAction(),
            'level' => $record->getLevel(),
            'previous_hash' => $record->getPreviousHash()?->toString(),
            'record_hash' => $record->getRecordHash()->toString(),
            'signature' => $record->getSignature()?->signature,
            'signed_by' => $record->getSignature()?->signedBy,
            'metadata' => $record->getMetadata(),
            'user_id' => $record->getUserId(),
            'created_at' => $record->getCreatedAt(),
        ]);
    }
    
    public function findByEntity(string $tenantId, string $entityId): array
    {
        return AuditRecord::where('tenant_id', $tenantId)
            ->where('entity_id', $entityId)
            ->orderBy('sequence_number')
            ->get()
            ->all();
    }
    
    public function getLastRecordHash(string $tenantId): ?string
    {
        $record = AuditRecord::where('tenant_id', $tenantId)
            ->orderBy('sequence_number', 'desc')
            ->first();
        
        return $record?->record_hash;
    }
    
    public function search(string $tenantId, array $criteria): array
    {
        $query = AuditRecord::where('tenant_id', $tenantId);
        
        if (isset($criteria['action'])) {
            $query->where('action', $criteria['action']);
        }
        
        if (isset($criteria['entity_id'])) {
            $query->where('entity_id', $criteria['entity_id']);
        }
        
        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }
        
        if (isset($criteria['from_date'])) {
            $query->where('created_at', '>=', $criteria['from_date']);
        }
        
        if (isset($criteria['to_date'])) {
            $query->where('created_at', '<=', $criteria['to_date']);
        }
        
        return $query->orderBy('sequence_number')->get()->all();
    }
    
    public function findAll(string $tenantId): array
    {
        return AuditRecord::where('tenant_id', $tenantId)
            ->orderBy('sequence_number')
            ->get()
            ->all();
    }
}
```

### Step 5: Create Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Audit\Contracts\{
    AuditEngineInterface,
    AuditStorageInterface,
    AuditVerifierInterface,
    AuditSequenceManagerInterface
};
use Nexus\Audit\Services\{
    AuditEngine,
    HashChainVerifier,
    AuditSequenceManager,
    RetentionPolicyService
};
use App\Repositories\EloquentAuditStorage;
use Nexus\Crypto\Contracts\CryptoManagerInterface;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            AuditStorageInterface::class,
            EloquentAuditStorage::class
        );
        
        $this->app->singleton(AuditSequenceManagerInterface::class, function ($app) {
            return new AuditSequenceManager(
                $app->make(AuditStorageInterface::class)
            );
        });
        
        $this->app->singleton(AuditVerifierInterface::class, function ($app) {
            return new HashChainVerifier(
                storage: $app->make(AuditStorageInterface::class),
                crypto: $app->make(CryptoManagerInterface::class)
            );
        });
        
        $this->app->singleton(AuditEngineInterface::class, function ($app) {
            return new AuditEngine(
                storage: $app->make(AuditStorageInterface::class),
                sequenceManager: $app->make(AuditSequenceManagerInterface::class),
                crypto: $app->make(CryptoManagerInterface::class)
            );
        });
        
        $this->app->singleton(RetentionPolicyService::class);
    }
}
```

### Step 6: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\AuditServiceProvider::class,
],
```

### Step 7: Use in Controller

```php
<?php

namespace App\Http\Controllers;

use Nexus\Audit\Contracts\{AuditEngineInterface, AuditStorageInterface};
use Nexus\Audit\ValueObjects\AuditLevel;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function __construct(
        private readonly AuditEngineInterface $auditEngine,
        private readonly AuditStorageInterface $auditStorage
    ) {}
    
    public function getEntityAudit(Request $request, string $entityId)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $records = $this->auditStorage->findByEntity($tenantId, $entityId);
        
        return response()->json($records);
    }
    
    public function search(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $records = $this->auditStorage->search($tenantId, [
            'action' => $request->input('action'),
            'user_id' => $request->input('user_id'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ]);
        
        return response()->json($records);
    }
}
```

### Step 8: Async Logging Queue Job

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\{SerializesModels, InteractsWithQueue};
use Illuminate\Contracts\Queue\ShouldQueue;
use Nexus\Audit\Contracts\AuditEngineInterface;
use Nexus\Audit\ValueObjects\AuditLevel;

class LogAuditRecordJob implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;
    
    public function __construct(
        private readonly string $tenantId,
        private readonly string $entityId,
        private readonly string $action,
        private readonly AuditLevel $level,
        private readonly array $metadata,
        private readonly ?string $userId
    ) {}
    
    public function handle(AuditEngineInterface $auditEngine): void
    {
        $auditEngine->logSync(
            tenantId: $this->tenantId,
            entityId: $this->entityId,
            action: $this->action,
            level: $this->level,
            metadata: $this->metadata,
            userId: $this->userId,
            sign: false
        );
    }
}
```

### Step 9: Scheduled Retention Policy

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Audit\Services\RetentionPolicyService;
use Nexus\Audit\ValueObjects\RetentionPolicy;

class PurgeExpiredAuditRecords extends Command
{
    protected $signature = 'audit:purge {tenant-id}';
    protected $description = 'Purge expired audit records per retention policy';
    
    public function handle(RetentionPolicyService $retentionService): int
    {
        $tenantId = $this->argument('tenant-id');
        
        $policy = new RetentionPolicy(retentionDays: 2555); // 7 years
        
        $deletedCount = $retentionService->purgeExpiredRecords($tenantId, $policy);
        
        $this->info("Purged {$deletedCount} expired audit records for tenant {$tenantId}");
        
        return 0;
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/audit:"*@dev"
```

### Step 2: Create Doctrine Entity

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Audit\Contracts\AuditRecordInterface;
use Nexus\Audit\ValueObjects\{AuditHash, AuditSignature, AuditLevel};

#[ORM\Entity]
#[ORM\Table(name: 'audit_records')]
#[ORM\UniqueConstraint(columns: ['tenant_id', 'sequence_number'])]
#[ORM\Index(columns: ['entity_id', 'created_at'])]
class AuditRecord implements AuditRecordInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;
    
    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;
    
    #[ORM\Column(type: 'bigint')]
    private int $sequenceNumber;
    
    #[ORM\Column(type: 'string', length: 26)]
    private string $entityId;
    
    #[ORM\Column(type: 'string', length: 50)]
    private string $action;
    
    #[ORM\Column(type: 'string', length: 20, enumType: AuditLevel::class)]
    private AuditLevel $level;
    
    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $previousHash = null;
    
    #[ORM\Column(type: 'string', length: 64)]
    private string $recordHash;
    
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $signature = null;
    
    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $signedBy = null;
    
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;
    
    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $userId = null;
    
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;
    
    // Implement all interface methods...
}
```

### Step 3: Configure Services

`config/services.yaml`:

```yaml
services:
    # Audit storage
    Nexus\Audit\Contracts\AuditStorageInterface:
        class: App\Repository\DoctrineAuditStorage
        arguments:
            $entityManager: '@doctrine.orm.entity_manager'
    
    # Sequence manager
    Nexus\Audit\Contracts\AuditSequenceManagerInterface:
        class: Nexus\Audit\Services\AuditSequenceManager
        arguments:
            $storage: '@Nexus\Audit\Contracts\AuditStorageInterface'
    
    # Verifier
    Nexus\Audit\Contracts\AuditVerifierInterface:
        class: Nexus\Audit\Services\HashChainVerifier
        arguments:
            $storage: '@Nexus\Audit\Contracts\AuditStorageInterface'
            $crypto: '@Nexus\Crypto\Contracts\CryptoManagerInterface'
    
    # Audit engine
    Nexus\Audit\Contracts\AuditEngineInterface:
        class: Nexus\Audit\Services\AuditEngine
        arguments:
            $storage: '@Nexus\Audit\Contracts\AuditStorageInterface'
            $sequenceManager: '@Nexus\Audit\Contracts\AuditSequenceManagerInterface'
            $crypto: '@Nexus\Crypto\Contracts\CryptoManagerInterface'
```

---

## Testing

### Unit Test Example (Laravel)

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Nexus\Audit\Services\AuditEngine;
use Nexus\Audit\Contracts\{AuditStorageInterface, AuditSequenceManagerInterface};
use Nexus\Audit\ValueObjects\AuditLevel;

class AuditEngineTest extends TestCase
{
    public function test_logSync_creates_audit_record(): void
    {
        $storage = $this->createMock(AuditStorageInterface::class);
        $sequenceManager = $this->createMock(AuditSequenceManagerInterface::class);
        
        $sequenceManager->method('getNextSequence')->willReturn(1);
        $storage->method('getLastRecordHash')->willReturn(null);
        $storage->expects($this->once())->method('append');
        
        $engine = new AuditEngine($storage, $sequenceManager, null);
        
        $recordId = $engine->logSync(
            'tenant-123',
            'entity-456',
            'created',
            AuditLevel::Info,
            ['key' => 'value'],
            'user-789',
            false
        );
        
        $this->assertNotEmpty($recordId);
    }
}
```

---

**Audit package is now fully integrated!** You can log events, verify integrity, and maintain compliance.
