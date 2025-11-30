# Getting Started with Nexus Audit

## Prerequisites

- PHP 8.3 or higher
- Composer
- Nexus\Crypto package (for digital signatures - optional)
- Nexus\Tenant package (for multi-tenancy support)

## Installation

```bash
composer require nexus/audit:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Immutable audit trails for financial transactions
- ✅ SOX/GDPR/HIPAA compliance logging
- ✅ Tamper detection and forensic investigation
- ✅ Non-repudiation with digital signatures
- ✅ Multi-tenant audit isolation
- ✅ Critical event logging (synchronous)
- ✅ Bulk event logging (asynchronous)

Do NOT use this package for:
- ❌ Application logging (use PSR-3 logger instead)
- ❌ Debug logging (use Monolog or similar)
- ❌ Performance metrics (use Nexus\Monitoring)
- ❌ User activity feeds (use Nexus\AuditLogger for timeline views)

## Core Concepts

### 1. Hash Chain Integrity

Every audit record contains:
- **Current Hash:** SHA-256 of the record's data
- **Previous Hash:** Links to the previous record in the chain
- **Sequence Number:** Monotonic per-tenant sequence

This creates an immutable chain where tampering with any record breaks the entire chain.

```
Record 1: hash=abc123, prev=null, seq=1
         ↓
Record 2: hash=def456, prev=abc123, seq=2
         ↓
Record 3: hash=ghi789, prev=def456, seq=3
```

If Record 2 is tampered, its hash changes to `def999`:
- Record 3's `prev` still points to `def456`
- Chain verification **fails** ✗

### 2. Dual-Mode Logging

**Synchronous Logging (Critical Events):**
- Blocks until audit record is persisted
- Hash chain is updated immediately
- Use for financial transactions, security events

**Asynchronous Logging (Bulk Events):**
- Dispatches to queue for background processing
- Does not block application flow
- Use for user activity, bulk imports

### 3. Per-Tenant Isolation

Each tenant has its own hash chain:
- Tenant A: Chain with sequences 1, 2, 3...
- Tenant B: Chain with sequences 1, 2, 3...
- No cross-tenant contamination

### 4. Digital Signatures (Optional)

For high-compliance environments:
- Ed25519 signatures via Nexus\Crypto
- Provides non-repudiation
- Cryptographically proves who created the record

### 5. Retention Policies

GDPR/SOX compliance through automatic purging:
- Define retention period (e.g., 7 years for SOX)
- Auto-purge records older than retention period
- Maintains compliance without manual intervention

---

## Basic Configuration

### Step 1: Implement AuditStorageInterface

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use Nexus\Audit\Contracts\AuditStorageInterface;
use Nexus\Audit\Contracts\AuditRecordInterface;

final readonly class DatabaseAuditStorage implements AuditStorageInterface
{
    public function __construct(
        private \PDO $connection
    ) {}
    
    public function append(AuditRecordInterface $record): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO audit_records (id, tenant_id, sequence_number, entity_id, action, previous_hash, record_hash, signature, signed_by, metadata, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        
        $stmt->execute([
            $record->getId(),
            $record->getTenantId(),
            $record->getSequenceNumber(),
            $record->getEntityId(),
            $record->getAction(),
            $record->getPreviousHash()?->toString() ?? null,
            $record->getRecordHash()->toString(),
            $record->getSignature()?->signature ?? null,
            $record->getSignature()?->signedBy ?? null,
            json_encode($record->getMetadata()),
            $record->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
    
    public function findByEntity(string $tenantId, string $entityId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM audit_records WHERE tenant_id = ? AND entity_id = ? ORDER BY sequence_number ASC'
        );
        $stmt->execute([$tenantId, $entityId]);
        
        return array_map(
            fn(array $row) => $this->hydrate($row),
            $stmt->fetchAll(\PDO::FETCH_ASSOC)
        );
    }
    
    public function getLastRecordHash(string $tenantId): ?string
    {
        $stmt = $this->connection->prepare(
            'SELECT record_hash FROM audit_records WHERE tenant_id = ? ORDER BY sequence_number DESC LIMIT 1'
        );
        $stmt->execute([$tenantId]);
        
        return $stmt->fetchColumn() ?: null;
    }
    
    // Implement other methods...
}
```

### Step 2: Implement AuditRecordInterface

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Nexus\Audit\Contracts\AuditRecordInterface;
use Nexus\Audit\ValueObjects\AuditHash;
use Nexus\Audit\ValueObjects\AuditSignature;
use Nexus\Audit\ValueObjects\AuditLevel;

final readonly class AuditRecord implements AuditRecordInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private int $sequenceNumber,
        private string $entityId,
        private string $action,
        private AuditLevel $level,
        private ?AuditHash $previousHash,
        private AuditHash $recordHash,
        private ?AuditSignature $signature,
        private array $metadata,
        private \DateTimeImmutable $createdAt
    ) {}
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenantId;
    }
    
    public function getSequenceNumber(): int
    {
        return $this->sequenceNumber;
    }
    
    // Implement all other interface methods...
}
```

### Step 3: Bind Interfaces in Service Provider

**Laravel Example:**

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Audit\Contracts\AuditEngineInterface;
use Nexus\Audit\Contracts\AuditStorageInterface;
use Nexus\Audit\Contracts\AuditVerifierInterface;
use Nexus\Audit\Contracts\AuditSequenceManagerInterface;
use Nexus\Audit\Services\AuditEngine;
use Nexus\Audit\Services\HashChainVerifier;
use Nexus\Audit\Services\AuditSequenceManager;
use App\Repositories\DatabaseAuditStorage;
use Nexus\Crypto\Contracts\CryptoManagerInterface;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind storage
        $this->app->singleton(
            AuditStorageInterface::class,
            DatabaseAuditStorage::class
        );
        
        // Bind sequence manager
        $this->app->singleton(
            AuditSequenceManagerInterface::class,
            AuditSequenceManager::class
        );
        
        // Bind verifier
        $this->app->singleton(
            AuditVerifierInterface::class,
            HashChainVerifier::class
        );
        
        // Bind audit engine
        $this->app->singleton(AuditEngineInterface::class, function ($app) {
            return new AuditEngine(
                storage: $app->make(AuditStorageInterface::class),
                sequenceManager: $app->make(AuditSequenceManagerInterface::class),
                crypto: $app->make(CryptoManagerInterface::class), // Optional
            );
        });
    }
}
```

### Step 4: Use the Audit Engine

```php
<?php

use Nexus\Audit\Contracts\AuditEngineInterface;
use Nexus\Audit\ValueObjects\AuditLevel;

final readonly class InvoiceService
{
    public function __construct(
        private AuditEngineInterface $audit
    ) {}
    
    public function createInvoice(array $data): Invoice
    {
        $invoice = $this->repository->create($data);
        
        // Log critical event synchronously
        $this->audit->logSync(
            tenantId: $invoice->getTenantId(),
            entityId: $invoice->getId(),
            action: 'invoice_created',
            level: AuditLevel::Info,
            metadata: [
                'invoice_number' => $invoice->getNumber(),
                'customer_id' => $invoice->getCustomerId(),
                'amount' => $invoice->getTotal(),
            ],
            userId: auth()->id(),
            sign: false // Set to true for digital signature
        );
        
        return $invoice;
    }
}
```

---

## Your First Integration

### Complete Example: Invoice Status Change Audit

```php
<?php

declare(strict_types=1);

use Nexus\Audit\Contracts\AuditEngineInterface;
use Nexus\Audit\ValueObjects\AuditLevel;

// Assume: Invoice status changed from 'Draft' to 'Approved'
final readonly class InvoiceStatusChangeHandler
{
    public function __construct(
        private AuditEngineInterface $audit
    ) {}
    
    public function handle(Invoice $invoice, string $oldStatus, string $newStatus): void
    {
        // Log status change synchronously (critical event)
        $auditRecordId = $this->audit->logSync(
            tenantId: $invoice->getTenantId(),
            entityId: $invoice->getId(),
            action: 'status_changed',
            level: AuditLevel::Info,
            metadata: [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'invoice_number' => $invoice->getNumber(),
                'amount' => $invoice->getTotal(),
                'changed_at' => now()->toIso8601String(),
            ],
            userId: auth()->id(),
            sign: true // Enable digital signature for non-repudiation
        );
        
        // Audit record ID can be stored for reference
        $invoice->setAuditRecordId($auditRecordId);
    }
}
```

**What happens behind the scenes:**

1. **Sequence Number Assigned:** AuditSequenceManager generates next number (e.g., 1043)
2. **Previous Hash Retrieved:** Last record's hash fetched from storage
3. **Current Hash Calculated:** SHA-256 of current record data
4. **Digital Signature Created:** Ed25519 signature via Nexus\Crypto (if `sign: true`)
5. **Record Persisted:** Immutable record saved to storage
6. **Hash Chain Updated:** New record becomes the chain head

---

## Verifying Audit Integrity

### Check Hash Chain

```php
use Nexus\Audit\Contracts\AuditVerifierInterface;

final readonly class AuditVerificationService
{
    public function __construct(
        private AuditVerifierInterface $verifier
    ) {}
    
    public function verifyTenantAudit(string $tenantId): bool
    {
        try {
            return $this->verifier->verifyChainIntegrity($tenantId);
        } catch (\Nexus\Audit\Exceptions\AuditTamperedException $e) {
            // Hash chain has been compromised!
            logger()->critical('Audit tampering detected', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
}
```

### Verify Individual Record

```php
use Nexus\Audit\Contracts\AuditVerifierInterface;
use Nexus\Audit\Contracts\AuditStorageInterface;

public function verifyInvoiceAudit(string $invoiceId): bool
{
    $auditRecord = $this->storage->findByEntity($tenantId, $invoiceId)[0];
    
    return $this->verifier->verifyRecord($auditRecord);
}
```

---

## Asynchronous Logging

For bulk operations or non-critical events:

```php
// Log user activity asynchronously
$this->audit->logAsync(
    tenantId: $tenantId,
    entityId: $userId,
    action: 'user_logged_in',
    level: AuditLevel::Info,
    metadata: [
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ],
    userId: $userId,
    sign: false
);

// Does not block - queued for background processing
```

**Note:** Async logging requires queue system integration (Laravel Queue, Symfony Messenger, etc.)

---

## Retention Policy

### Apply GDPR Retention (7 years)

```php
use Nexus\Audit\ValueObjects\RetentionPolicy;
use Nexus\Audit\Services\RetentionPolicyService;

final readonly class AuditRetentionJob
{
    public function __construct(
        private RetentionPolicyService $retentionService
    ) {}
    
    public function handle(): void
    {
        $policy = new RetentionPolicy(
            retentionDays: 2555 // 7 years × 365 days
        );
        
        // Purge records older than 7 years
        $deletedCount = $this->retentionService->purgeExpiredRecords(
            tenantId: $tenantId,
            policy: $policy
        );
        
        logger()->info("Purged {$deletedCount} expired audit records");
    }
}
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for Laravel/Symfony examples
- See [Basic Usage Example](examples/basic-usage.php) for simple patterns
- See [Advanced Usage Example](examples/advanced-usage.php) for signatures and verification

---

## Troubleshooting

### Issue 1: "Hash chain verification failed"

**Cause:** A record in the chain has been tampered with

**Solution:**
- Investigate which record was modified (check logs)
- Restore from backup if tampering was malicious
- If legitimate, rebuild hash chain from that point forward (contact support)

### Issue 2: "Sequence gap detected"

**Cause:** Missing audit records (sequence jumps from 100 to 102, skipping 101)

**Solution:**
- Check for failed transactions during high load
- Verify database transaction isolation level
- Use `AuditSequenceManager::detectGaps()` to identify missing sequences

### Issue 3: "Signature verification failed"

**Cause:** Digital signature does not match record data

**Solution:**
- Ensure Nexus\Crypto is properly configured
- Verify the signing key hasn't changed
- Check that record data wasn't modified after signing

### Issue 4: "Slow synchronous logging"

**Cause:** Hash calculation or signature generation taking too long

**Solution:**
- Benchmark hash calculation (should be <10ms)
- Move signature generation to async queue
- Use asynchronous logging for non-critical events

---

**Quick Reference:**

| Operation | Method | Mode | Use Case |
|-----------|--------|------|----------|
| Log critical event | `logSync()` | Synchronous | Financial transactions, security events |
| Log bulk events | `logAsync()` | Asynchronous | User activity, bulk imports |
| Verify chain | `verifyChainIntegrity()` | - | Periodic integrity check |
| Verify record | `verifyRecord()` | - | Single record validation |
| Purge old records | `purgeExpiredRecords()` | - | GDPR/SOX compliance |
| Detect gaps | `detectGaps()` | - | Forensic investigation |

---

**You're now ready to implement SOX/GDPR-compliant audit trails!** 🎉
