# Getting Started with Nexus\Document

## Prerequisites

- PHP 8.3 or higher
- Composer
- S3-compatible storage (AWS S3, MinIO, LocalStack) or local filesystem
- Database (MySQL 8.0+, PostgreSQL 13+, or SQLite)
- Nexus\Tenant package (for multi-tenancy)
- Nexus\Storage package (for file storage abstraction)

## Installation

```bash
composer require nexus/document:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Enterprise document management across ERP modules
- ✅ Version-controlled document storage
- ✅ Compliance-aware retention policies
- ✅ Multi-tenant document isolation
- ✅ Relationship management (amendments, attachments, supersedes)
- ✅ Secure document sharing with temporary URLs
- ✅ Checksum-verified downloads
- ✅ Audit trails for document lifecycle

Do NOT use this package for:
- ❌ Simple file uploads (use Nexus\Storage directly)
- ❌ Single-tenant applications without versioning needs
- ❌ Real-time collaborative editing (use Google Docs, Notion, etc.)
- ❌ Media streaming (videos, audio)

## Core Concepts

### Concept 1: S3-Optimized Nested Path Structure

The Document package uses a **year/month partitioned storage path** to avoid S3 hot partitions and enable efficient lifecycle policies:

```
{tenantId}/{year}/{month}/{documentId}/v{version}.{extension}

Example:
01HQXYZ9ABCDEFGHIJKLMNOPQR/2025/11/01HQXYZ9XYZ1234567890ABCD/v1.pdf
01HQXYZ9ABCDEFGHIJKLMNOPQR/2025/11/01HQXYZ9XYZ1234567890ABCD/v2.pdf
```

**Benefits:**
- Distributes files across date-based prefixes (prevents hot partitions at scale)
- Enables S3 lifecycle policies (e.g., archive files older than 7 years to Glacier)
- Human-readable paths for debugging
- Temporal queries without database lookups

### Concept 2: Document Lifecycle & State Machine

Every document transitions through predefined states:

```
Draft → Active → Archived → Purged
  ↓        ↓
Deleted  Deleted
```

**States:**
- **Draft:** Newly created, not finalized
- **Active:** Finalized and available
- **Archived:** Retained for compliance, read-only
- **Deleted:** Soft-deleted (can be restored)
- **Purged:** Permanently deleted (cannot be restored)

**Transition Rules:**
- Draft → Active (finalize document)
- Active → Archived (retention policy)
- Any → Deleted (soft delete)
- Deleted → Active/Draft (restore)
- Archived → Purged (after retention period + legal hold release)

### Concept 3: Version Control with Rollback

Documents support unlimited versions:

```php
$document = $manager->create('invoice', $file, ['title' => 'Invoice #12345']);
// Creates version 1

$manager->createVersion($document->getId(), $updatedFile, 'Corrected amount');
// Creates version 2

$manager->rollbackToVersion($document->getId(), 1);
// Restores version 1 as version 3 (non-destructive rollback)
```

**Version Metadata:**
- Version number (auto-incremented)
- SHA-256 checksum (integrity verification)
- File size
- MIME type
- Upload timestamp
- Notes (reason for version)

### Concept 4: Document Relationships

Documents can be linked via relationship types:

- **Amendment:** Document A amends Document B
- **Supersedes:** Document A replaces Document B
- **Related:** Documents are related (general)
- **Attachment:** Document A is attached to Document B

**Use Cases:**
- Contract amendments
- Invoice corrections
- Purchase order revisions
- Policy updates

### Concept 5: Retention Policies & Compliance

Documents can have retention policies:

```php
$retentionService->applyPolicy(
    documentId: $invoiceDoc->getId(),
    retainUntil: now()->addYears(7), // Tax law retention
    reason: 'Tax compliance (IRAS)'
);
```

**Features:**
- Prevent deletion during retention period
- Legal hold override (ongoing litigation)
- Auto-purge after retention expires
- Compliance reporting

### Concept 6: Permission-Based Access Control

Documents enforce permissions:

- **View:** Can read document
- **Edit:** Can update metadata or create versions
- **Delete:** Can soft-delete document
- **Share:** Can generate temporary URLs

**Integration:**
Permissions are checked via `PermissionCheckerInterface` (implemented by consuming application using Nexus\Domain\Identity or custom RBAC).

## Configuration Steps

### Step 1: Run Database Migrations

The consuming application must create database tables for documents:

```php
// database/migrations/xxxx_create_documents_table.php

Schema::create('documents', function (Blueprint $table) {
    $table->char('id', 26)->primary(); // ULID
    $table->char('tenant_id', 26)->index();
    $table->string('type'); // invoice, contract, report, etc.
    $table->string('state'); // draft, active, archived, deleted
    $table->json('metadata'); // {title, description, tags}
    $table->string('storage_path'); // S3 path
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['tenant_id', 'type']);
    $table->index(['tenant_id', 'state']);
});

Schema::create('document_versions', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->char('document_id', 26);
    $table->unsignedInteger('version_number');
    $table->string('storage_path');
    $table->string('checksum', 64); // SHA-256
    $table->unsignedBigInteger('file_size');
    $table->string('mime_type');
    $table->text('notes')->nullable();
    $table->timestamps();
    
    $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
    $table->unique(['document_id', 'version_number']);
});

Schema::create('document_relationships', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->char('document_id', 26);
    $table->char('related_document_id', 26);
    $table->string('relationship_type'); // amendment, supersedes, related, attachment
    $table->timestamps();
    
    $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
    $table->foreign('related_document_id')->references('id')->on('documents')->onDelete('cascade');
    $table->unique(['document_id', 'related_document_id', 'relationship_type']);
});
```

### Step 2: Create Eloquent Models

```php
// app/Models/Document.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\ValueObjects\DocumentMetadata;
use Nexus\Document\ValueObjects\DocumentState;

class Document extends Model implements DocumentInterface
{
    use SoftDeletes;

    protected $casts = [
        'metadata' => 'array',
    ];

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getState(): DocumentState
    {
        return DocumentState::from($this->state);
    }

    public function getMetadata(): DocumentMetadata
    {
        return new DocumentMetadata(
            title: $this->metadata['title'] ?? 'Untitled',
            description: $this->metadata['description'] ?? null,
            tags: $this->metadata['tags'] ?? []
        );
    }

    public function getStoragePath(): string
    {
        return $this->storage_path;
    }

    public function getCurrentVersion(): DocumentVersionInterface
    {
        return $this->versions()->latest('version_number')->first();
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }
}
```

### Step 3: Implement Repository Interfaces

```php
// app/Repositories/DbDocumentRepository.php

namespace App\Repositories;

use App\Models\Document;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Exceptions\DocumentNotFoundException;

final readonly class DbDocumentRepository implements DocumentRepositoryInterface
{
    public function findById(string $id): DocumentInterface
    {
        $document = Document::find($id);
        
        if (!$document) {
            throw DocumentNotFoundException::forId($id);
        }
        
        return $document;
    }

    public function save(DocumentInterface $document): void
    {
        $document->save();
    }

    public function delete(string $id): void
    {
        Document::find($id)?->delete();
    }

    public function findByTenantAndType(string $tenantId, string $type): array
    {
        return Document::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->get()
            ->all();
    }
}
```

### Step 4: Bind Interfaces in Service Provider

```php
// app/Providers/DocumentServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\DocumentVersionRepositoryInterface;
use Nexus\Document\Contracts\DocumentRelationshipRepositoryInterface;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Document\Contracts\ContentProcessorInterface;
use Nexus\Document\Contracts\RetentionPolicyInterface;
use App\Repositories\DbDocumentRepository;
use App\Repositories\DbDocumentVersionRepository;
use App\Repositories\DbDocumentRelationshipRepository;
use App\Services\DocumentPermissionChecker;
use App\Services\NullContentProcessor;
use App\Services\DefaultRetentionPolicy;

class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(DocumentRepositoryInterface::class, DbDocumentRepository::class);
        $this->app->singleton(DocumentVersionRepositoryInterface::class, DbDocumentVersionRepository::class);
        $this->app->singleton(DocumentRelationshipRepositoryInterface::class, DbDocumentRelationshipRepository::class);
        
        // Service bindings
        $this->app->singleton(PermissionCheckerInterface::class, DocumentPermissionChecker::class);
        $this->app->singleton(ContentProcessorInterface::class, NullContentProcessor::class);
        $this->app->singleton(RetentionPolicyInterface::class, DefaultRetentionPolicy::class);
        
        // Manager bindings (auto-wired via constructor injection)
        $this->app->singleton(\Nexus\Document\Services\DocumentManager::class);
        $this->app->singleton(\Nexus\Document\Services\VersionManager::class);
        $this->app->singleton(\Nexus\Document\Services\RelationshipManager::class);
        $this->app->singleton(\Nexus\Document\Services\RetentionService::class);
    }
}
```

Register in `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\DocumentServiceProvider::class,
],
```

### Step 5: Configure Storage

Ensure `Nexus\Storage` is configured for S3 or local filesystem:

```php
// config/filesystems.php

'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ],
],
```

### Step 6: Use the Document Manager

```php
use Nexus\Document\Services\DocumentManager;
use Illuminate\Http\UploadedFile;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly DocumentManager $documentManager
    ) {}

    public function uploadInvoice(Request $request)
    {
        $file = $request->file('invoice');
        
        $document = $this->documentManager->create(
            type: 'invoice',
            file: $file,
            metadata: [
                'title' => 'Invoice #12345',
                'description' => 'Customer payment invoice',
                'tags' => ['invoice', 'finance', 'customer-12345'],
            ]
        );
        
        return response()->json([
            'document_id' => $document->getId(),
            'version' => $document->getCurrentVersion()->getVersionNumber(),
        ]);
    }
}
```

## Your First Integration

Complete working example:

```php
<?php

use Nexus\Document\Services\DocumentManager;
use Nexus\Document\Services\VersionManager;
use Nexus\Document\Services\RelationshipManager;
use Nexus\Document\ValueObjects\RelationshipType;

// Inject via DI
/** @var DocumentManager $documentManager */
/** @var VersionManager $versionManager */
/** @var RelationshipManager $relationshipManager */

// 1. Upload a contract document
$contractFile = new \Illuminate\Http\UploadedFile(
    '/path/to/contract.pdf',
    'contract.pdf',
    'application/pdf',
    null,
    true
);

$contract = $documentManager->create(
    type: 'contract',
    file: $contractFile,
    metadata: [
        'title' => 'Employment Contract - John Doe',
        'description' => 'Standard employment agreement',
        'tags' => ['hr', 'contract', 'employment'],
    ]
);

echo "Contract created: {$contract->getId()}\n";
echo "Version: {$contract->getCurrentVersion()->getVersionNumber()}\n";

// 2. Create an amended version
$amendedFile = new \Illuminate\Http\UploadedFile(
    '/path/to/contract_amended.pdf',
    'contract_amended.pdf',
    'application/pdf',
    null,
    true
);

$amendedContract = $documentManager->create(
    type: 'contract',
    file: $amendedFile,
    metadata: [
        'title' => 'Employment Contract Amendment - John Doe',
        'description' => 'Salary adjustment amendment',
        'tags' => ['hr', 'contract', 'amendment'],
    ]
);

// 3. Link amendment relationship
$relationshipManager->createRelationship(
    documentId: $amendedContract->getId(),
    relatedDocumentId: $contract->getId(),
    type: RelationshipType::Amendment
);

echo "Amendment linked to original contract\n";

// 4. Download original contract with checksum verification
$fileContent = $documentManager->download($contract->getId());

// Checksum is verified internally - will throw ChecksumMismatchException if corrupted
file_put_contents('/tmp/downloaded_contract.pdf', $fileContent);

echo "Contract downloaded and verified\n";

// 5. Apply retention policy (7 years for employment contracts)
$retentionService->applyPolicy(
    documentId: $contract->getId(),
    retainUntil: now()->addYears(7),
    reason: 'Employment law compliance'
);

echo "Retention policy applied: must retain until " . now()->addYears(7)->format('Y-m-d') . "\n";
```

**Output:**
```
Contract created: 01HQXYZ9ABCDEFGHIJKLMNOPQR
Version: 1
Amendment linked to original contract
Contract downloaded and verified
Retention policy applied: must retain until 2032-11-24
```

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples

## Troubleshooting

### Issue 1: "Interface not instantiable"

**Error:**
```
Target [Nexus\Document\Contracts\DocumentRepositoryInterface] is not instantiable.
```

**Cause:** DocumentServiceProvider not registered or interfaces not bound.

**Solution:**
1. Ensure `App\Providers\DocumentServiceProvider` is listed in `config/app.php`
2. Verify all interfaces are bound in `DocumentServiceProvider::register()`
3. Clear config cache: `php artisan config:clear`

---

### Issue 2: "Checksum mismatch"

**Error:**
```
Nexus\Document\Exceptions\ChecksumMismatchException: Checksum mismatch for document
```

**Cause:** File corrupted in storage or tampered with.

**Solution:**
1. Check S3 bucket integrity
2. Verify storage provider connection
3. Re-upload document
4. If persistent, investigate storage infrastructure

---

### Issue 3: "Storage path not found"

**Error:**
```
File not found at path: {tenant}/{year}/{month}/{id}/v1.pdf
```

**Cause:** Storage configuration incorrect or file not uploaded properly.

**Solution:**
1. Verify S3 credentials in `.env`
2. Check bucket permissions (read/write)
3. Verify `Nexus\Storage` is properly configured
4. Check file was uploaded successfully (check S3 console)

---

### Issue 4: "Tenant isolation not working"

**Problem:** Users can see other tenant's documents.

**Cause:** Repository not scoping queries by tenant.

**Solution:**
Ensure repository filters by tenant:
```php
public function findByTenantAndType(string $tenantId, string $type): array
{
    return Document::where('tenant_id', $tenantId) // MUST filter by tenant
        ->where('type', $type)
        ->get()
        ->all();
}
```

---

### Issue 5: "Version number conflict"

**Error:**
```
UNIQUE constraint failed: document_versions.document_id, version_number
```

**Cause:** Race condition in concurrent version creation.

**Solution:**
Use database transaction with lock:
```php
DB::transaction(function () use ($documentId, $file) {
    $document = Document::lockForUpdate()->find($documentId);
    $nextVersion = $document->versions()->max('version_number') + 1;
    
    // Create version with $nextVersion
});
```

---

### Issue 6: "Permission denied"

**Error:**
```
Nexus\Document\Exceptions\PermissionDeniedException: User cannot perform action on document
```

**Cause:** User lacks required permission.

**Solution:**
1. Verify user has correct role/permissions in Nexus\Domain\Identity
2. Check `PermissionCheckerInterface` implementation
3. For testing, temporarily bypass permissions:
```php
// Only for testing!
class AlwaysAllowPermissionChecker implements PermissionCheckerInterface
{
    public function canView(string $userId, string $documentId): bool { return true; }
    public function canEdit(string $userId, string $documentId): bool { return true; }
    public function canDelete(string $userId, string $documentId): bool { return true; }
    public function canShare(string $userId, string $documentId): bool { return true; }
}
```

---

### Issue 7: "Retention policy prevents deletion"

**Error:**
```
Nexus\Document\Exceptions\RetentionPolicyViolationException: Document is under retention and cannot be deleted
```

**Cause:** Document has active retention policy.

**Solution:**
This is expected behavior. Options:
1. Wait until retention period expires
2. Remove retention policy (if authorized):
   ```php
   $retentionService->removePolicy($documentId);
   ```
3. Apply legal hold release (if litigation ended)

---

### Issue 8: "S3 hot partition warnings"

**Problem:** AWS CloudWatch shows high request rates on single S3 prefix.

**Cause:** Not using PathGenerator year/month partitioning.

**Solution:**
Ensure using `PathGenerator` for storage paths:
```php
use Nexus\Document\Core\PathGenerator;

$generator = new PathGenerator();
$path = $generator->generateStoragePath(
    tenantId: $tenantId,
    documentId: $documentId,
    version: $version,
    extension: 'pdf'
);
// Result: {tenant}/2025/11/{id}/v1.pdf (distributes across month prefixes)
```

---

## Performance Tips

### 1. Enable S3 Transfer Acceleration
For large file uploads (> 100MB):
```php
// config/filesystems.php
's3' => [
    'use_accelerate_endpoint' => true,
],
```

### 2. Use Batch Operations
Upload multiple documents efficiently:
```php
$documents = $documentManager->createBatch([
    ['type' => 'invoice', 'file' => $file1, 'metadata' => [...]],
    ['type' => 'invoice', 'file' => $file2, 'metadata' => [...]],
    ['type' => 'invoice', 'file' => $file3, 'metadata' => [...]],
]);
```

### 3. Configure S3 Lifecycle Policies
Archive old documents automatically:
```json
{
  "Rules": [{
    "Id": "ArchiveOldDocuments",
    "Prefix": "*/202[0-3]/", 
    "Status": "Enabled",
    "Transitions": [{
      "Days": 2555,
      "StorageClass": "GLACIER"
    }]
  }]
}
```

### 4. Cache Document Metadata
Avoid repeated database queries:
```php
$document = Cache::remember(
    "document.{$id}",
    3600,
    fn() => $documentManager->findById($id)
);
```

---

**Last Updated:** November 24, 2025  
**Package Version:** 1.0.0  
**Maintained By:** Nexus Architecture Team
