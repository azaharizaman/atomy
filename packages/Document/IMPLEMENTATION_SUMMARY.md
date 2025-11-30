# Nexus\Document Package - Implementation Summary

## Overview

The `Nexus\Document` package provides a **comprehensive, framework-agnostic Enterprise Document Management (EDM) system** with S3-optimized storage, multi-tenant isolation, version control, relationship management, and compliance-aware retention policies.

**Status:** ✅ Phase 1 Complete (Core Implementation)  
**Package Version:** 1.0.0  
**PHP Version Required:** 8.3+  
**Created:** 2025-01-21  
**Branch:** `feature/nexus-document-implementation`

---

## Architecture Overview

### Core Philosophy

The Document package follows the Nexus architectural principle: **"Logic in Packages, Implementation in Applications."**

- **Package Layer (`packages/Document/`)**: Framework-agnostic business logic with zero Laravel dependencies
- **Application Layer (`consuming application (e.g., Laravel app)`)**: Laravel Eloquent implementations with database migrations

### Storage Strategy

The package uses an **S3-optimized nested path structure** to avoid hot partitions and enable efficient lifecycle policies:

```
{tenantId}/{year}/{month}/{uuid}/v{version}.{extension}

Example:
01HQXYZ9ABCDEFGHIJKLMNOPQR/2025/01/01HQXYZ9XYZ1234567890ABCD/v1.pdf
```

**Benefits:**
- Distributes files across date-based prefixes (no hot partitions)
- Supports S3 lifecycle policies (archive by date prefix)
- Enables temporal queries without database lookups
- Human-readable structure for debugging

---

## Package Structure

```
packages/Document/
├── composer.json              # Package definition
├── LICENSE                    # MIT License
├── README.md                  # Package documentation
└── src/
    ├── Contracts/             # 10 interfaces
    │   ├── DocumentInterface.php
    │   ├── DocumentVersionInterface.php
    │   ├── DocumentRelationshipInterface.php
    │   ├── DocumentRepositoryInterface.php
    │   ├── DocumentVersionRepositoryInterface.php
    │   ├── DocumentRelationshipRepositoryInterface.php
    │   ├── PermissionCheckerInterface.php
    │   ├── DocumentSearchInterface.php
    │   ├── ContentProcessorInterface.php
    │   └── RetentionPolicyInterface.php
    ├── ValueObjects/          # 7 value objects and enums
    │   ├── DocumentType.php (enum)
    │   ├── DocumentState.php (enum with transition validation)
    │   ├── RelationshipType.php (enum)
    │   ├── DocumentFormat.php (enum)
    │   ├── DocumentMetadata.php (readonly class)
    │   ├── VersionMetadata.php (readonly class)
    │   └── ContentAnalysisResult.php (readonly class)
    ├── Core/                  # Internal engine
    │   └── PathGenerator.php (S3-optimized path generation)
    ├── Exceptions/            # 9 domain exceptions
    │   ├── DocumentNotFoundException.php
    │   ├── DocumentVersionNotFoundException.php
    │   ├── DocumentRelationshipNotFoundException.php
    │   ├── ChecksumMismatchException.php
    │   ├── InvalidDocumentStateException.php
    │   ├── PermissionDeniedException.php
    │   ├── InvalidStoragePathException.php
    │   ├── DuplicateRelationshipException.php
    │   └── InvalidMetadataException.php
    └── Services/              # 5 manager classes
        ├── DocumentManager.php
        ├── VersionManager.php
        ├── RelationshipManager.php
        ├── DocumentSearchService.php
        └── RetentionService.php
```

---

## Application Implementation

```
consuming application (e.g., Laravel app)
├── app/
│   ├── Models/
│   │   ├── Document.php (implements DocumentInterface)
│   │   ├── DocumentVersion.php (implements DocumentVersionInterface)
│   │   └── DocumentRelationship.php (implements DocumentRelationshipInterface)
│   ├── Repositories/
│   │   ├── DbDocumentRepository.php
│   │   ├── DbDocumentVersionRepository.php
│   │   └── DbDocumentRelationshipRepository.php
│   ├── Services/
│   │   ├── DocumentPermissionChecker.php (implements PermissionCheckerInterface)
│   │   ├── NullContentProcessor.php (implements ContentProcessorInterface)
│   │   └── DefaultRetentionPolicy.php (implements RetentionPolicyInterface)
│   └── Providers/
│       └── DocumentServiceProvider.php
└── database/migrations/
    ├── 2025_01_21_000001_create_documents_table.php
    ├── 2025_01_21_000002_create_document_versions_table.php
    └── 2025_01_21_000003_create_document_relationships_table.php
```

---

## Key Features

### 1. Document Upload with Integrity Verification

```php
use Nexus\Document\Services\DocumentManager;
use Nexus\Document\ValueObjects\DocumentType;

$document = $documentManager->upload(
    stream: $fileStream,
    name: 'Contract_2025.pdf',
    type: DocumentType::CONTRACT,
    ownerId: $userId,
    metadata: ['customer_id' => '123', 'contract_number' => 'CTR-2025-001'],
    tags: ['legal', 'urgent']
);

// Returns DocumentInterface with:
// - Unique ULID identifier
// - SHA-256 checksum for integrity verification
// - S3-optimized storage path
// - Metadata and tags
```

### 2. Version Control

```php
use Nexus\Document\Services\VersionManager;

// Create new version
$version = $versionManager->createVersion(
    documentId: $documentId,
    stream: $newFileStream,
    createdBy: $userId,
    changeDescription: 'Updated pricing terms'
);

// Get version history
$versions = $versionManager->getVersionHistory($documentId);

// Rollback to previous version
$document = $versionManager->rollbackToVersion($documentId, 3);
```

### 3. Document Relationships

```php
use Nexus\Document\Services\RelationshipManager;
use Nexus\Document\ValueObjects\RelationshipType;

// Link amendment to original contract
$relationship = $relationshipManager->createRelationship(
    sourceDocumentId: $originalContractId,
    targetDocumentId: $amendmentId,
    type: RelationshipType::PARENT_CHILD,
    createdBy: $userId,
    description: 'Contract amendment #1'
);

// Get all related documents (one-level deep)
$relatedDocs = $relationshipManager->getRelatedDocuments($documentId);
```

### 4. Permission-Based Search

```php
use Nexus\Document\Services\DocumentSearchService;

// Search by tags (permission-filtered)
$documents = $searchService->findByTags(['urgent', 'legal'], $userId);

// Search by type
$invoices = $searchService->findByType(DocumentType::INVOICE, $userId);

// Search by metadata
$customerDocs = $searchService->findByMetadata(['customer_id' => '123'], $userId);

// Search by date range
$recentDocs = $searchService->findByDateRange($startDate, $endDate, $userId);
```

### 5. Retention and Compliance

```php
use Nexus\Document\Services\RetentionService;

// Purge expired documents (respects legal holds)
$purgedCount = $retentionService->purgeExpiredDocuments();

// Check retention compliance
$isCompliant = $retentionService->checkRetentionCompliance($documentId);

// Apply retention policy (auto-archive expired)
$archivedCount = $retentionService->applyRetentionPolicy();
```

### 6. Content Processing (Extensible)

```php
use Nexus\Document\Contracts\ContentProcessorInterface;

// PDF rendering (when Intelligence package is available)
$pdfContent = $contentProcessor->render(
    templateName: 'invoice',
    data: ['invoice' => $invoiceData],
    format: DocumentFormat::PDF
);

// ML-based analysis (OCR, auto-tagging)
$analysis = $contentProcessor->analyze($documentPath);

// Text extraction for search indexing
$text = $contentProcessor->extractText($documentPath);

// PII redaction for GDPR compliance
$redactedPath = $contentProcessor->redact($documentPath, ['/\b\d{3}-\d{2}-\d{4}\b/']);
```

---

## Database Schema

### `documents` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | CHAR(26) | ULID primary key |
| `tenant_id` | CHAR(26) | Tenant identifier |
| `owner_id` | CHAR(26) | User who uploaded the document |
| `name` | STRING | Document name |
| `storage_path` | STRING | S3 storage path (unique) |
| `size_bytes` | BIGINT | File size |
| `mime_type` | STRING | MIME type |
| `type` | ENUM | Document type (contract, invoice, report, etc.) |
| `state` | ENUM | Document state (active, archived, deleted) |
| `checksum_algo` | STRING | Checksum algorithm (sha256) |
| `checksum_value` | STRING | SHA-256 checksum |
| `metadata` | JSON | Free-form metadata |
| `tags` | JSON | Array of tags |
| `archived_at` | TIMESTAMP | Archive timestamp |
| `purge_at` | TIMESTAMP | Scheduled purge timestamp |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Update timestamp |
| `deleted_at` | TIMESTAMP | Soft delete timestamp |

**Indexes:**
- Primary key: `id`
- Unique: `storage_path`
- Composite: `(tenant_id, owner_id)`, `(tenant_id, type)`, `(tenant_id, state)`
- Single: `created_at`, `purge_at`

---

### `document_versions` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | CHAR(26) | ULID primary key |
| `document_id` | CHAR(26) | Foreign key (cascade delete) |
| `version_number` | INT | Sequential version number |
| `storage_path` | STRING | S3 storage path (unique) |
| `size_bytes` | BIGINT | File size |
| `checksum_algo` | STRING | Checksum algorithm |
| `checksum_value` | STRING | SHA-256 checksum |
| `created_by` | CHAR(26) | User who created this version |
| `change_description` | TEXT | Description of changes |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Update timestamp |

**Indexes:**
- Primary key: `id`
- Unique: `storage_path`, `(document_id, version_number)`
- Single: `created_at`

---

### `document_relationships` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | CHAR(26) | ULID primary key |
| `source_document_id` | CHAR(26) | Foreign key (cascade delete) |
| `target_document_id` | CHAR(26) | Foreign key (cascade delete) |
| `type` | ENUM | Relationship type (parent_child, attachment, reference, superseded_by) |
| `description` | TEXT | Relationship description |
| `created_by` | CHAR(26) | User who created relationship |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Update timestamp |

**Indexes:**
- Primary key: `id`
- Unique: `(source_document_id, target_document_id, type)` (prevents duplicates)
- Single: `type`

---

## Dependencies

### Package Dependencies (composer.json)

```json
{
    "require": {
        "php": "^8.3",
        "psr/log": "^3.0",
        "nexus/storage": "*@dev",
        "nexus/crypto": "*@dev"
    },
    "require-dev": {
        "nexus/audit-logger": "*@dev",
        "nexus/tenant": "*@dev"
    }
}
```

### Integration Points

| Package | Relationship | Usage |
|---------|-------------|-------|
| **Nexus\Storage** | MANDATORY | File upload, download, deletion, temporary URLs |
| **Nexus\Crypto** | MANDATORY | SHA-256 checksum calculation and verification |
| **Nexus\AuditLogger** | RECOMMENDED | Audit logging for compliance (upload, download, delete, state changes) |
| **Nexus\Tenant** | RECOMMENDED | Multi-tenant context and isolation |
| **Nexus\Domain\Identity** | RECOMMENDED | User context and role-based permissions |
| **Nexus\Intelligence** | OPTIONAL | ML-based content processing (OCR, auto-tagging, PDF rendering) |
| **Nexus\Compliance** | OPTIONAL | Advanced retention policies and legal hold management |

---

## Service Provider Bindings

### `consuming application (e.g., Laravel app)app/Providers/DocumentServiceProvider.php`

```php
// Repository bindings
$this->app->singleton(DocumentRepositoryInterface::class, DbDocumentRepository::class);
$this->app->singleton(DocumentVersionRepositoryInterface::class, DbDocumentVersionRepository::class);
$this->app->singleton(DocumentRelationshipRepositoryInterface::class, DbDocumentRelationshipRepository::class);

// Service bindings
$this->app->singleton(PermissionCheckerInterface::class, DocumentPermissionChecker::class);
$this->app->singleton(ContentProcessorInterface::class, NullContentProcessor::class);
$this->app->singleton(RetentionPolicyInterface::class, DefaultRetentionPolicy::class);

// Manager bindings
$this->app->singleton(DocumentManager::class);
$this->app->singleton(VersionManager::class);
$this->app->singleton(RelationshipManager::class);
$this->app->singleton(RetentionService::class);
$this->app->singleton(DocumentSearchInterface::class, DocumentSearchService::class);
```

**Note:** Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\DocumentServiceProvider::class,
],
```

---

## Implementation Highlights

### 1. S3-Optimized PathGenerator

The `PathGenerator` class provides a stateless, dependency-free engine for generating and parsing storage paths:

```php
use Nexus\Document\Core\PathGenerator;

$generator = new PathGenerator();

// Generate storage path
$path = $generator->generateStoragePath(
    tenantId: '01HQXYZ9ABCDEFGHIJKLMNOPQR',
    documentId: '01HQXYZ9XYZ1234567890ABCD',
    version: 1,
    extension: 'pdf'
);
// Result: 01HQXYZ9ABCDEFGHIJKLMNOPQR/2025/01/01HQXYZ9XYZ1234567890ABCD/v1.pdf

// Parse storage path
$parsed = $generator->parseStoragePath($path);
// Returns: ['tenant_id' => ..., 'year' => 2025, 'month' => 1, 'document_id' => ..., 'version' => 1, 'extension' => 'pdf']

// Check tenant ownership
$belongs = $generator->belongsToTenant($path, '01HQXYZ9ABCDEFGHIJKLMNOPQR');
// Returns: true

// Generate archive prefix for lifecycle policies
$archivePrefix = $generator->getArchivePrefix('01HQXYZ9ABCDEFGHIJKLMNOPQR', 2023);
// Result: 01HQXYZ9ABCDEFGHIJKLMNOPQR/2023/
```

### 2. DocumentState Enum with Transition Validation

```php
use Nexus\Document\ValueObjects\DocumentState;

// Valid transitions
$state = DocumentState::ACTIVE;
$state->canTransitionTo(DocumentState::ARCHIVED); // true

// Invalid transitions
$state = DocumentState::ARCHIVED;
$state->canTransitionTo(DocumentState::ACTIVE); // false (archived is read-only)

// Check editability
$state = DocumentState::ACTIVE;
$state->isEditable(); // true

$state = DocumentState::ARCHIVED;
$state->isEditable(); // false
```

### 3. ContentAnalysisResult for ML Integration

```php
use Nexus\Document\ValueObjects\ContentAnalysisResult;

// ML prediction result
$result = ContentAnalysisResult::create(
    documentType: 'invoice',
    confidence: 0.95,
    tags: ['finance', 'billing'],
    extractedText: 'Invoice #INV-2025-001...',
    metadata: ['vendor' => 'Acme Corp', 'total' => 1500.00]
);

// Null result (when processor is not configured)
$nullResult = ContentAnalysisResult::null();
```

### 4. Checksum Verification

```php
use Nexus\Document\Services\DocumentManager;
use Nexus\Document\Exceptions\ChecksumMismatchException;

try {
    $document = $documentManager->download($documentId);
    // Checksum verification happens automatically
} catch (ChecksumMismatchException $e) {
    // Data corruption or tampering detected
    $this->logger->critical('Checksum mismatch detected', [
        'document_id' => $documentId,
        'expected' => $e->expectedChecksum,
        'actual' => $e->actualChecksum,
    ]);
}
```

---

## Default Retention Periods

The `DefaultRetentionPolicy` implements industry-standard retention periods:

| Document Type | Retention Period | Days |
|--------------|------------------|------|
| **Contract** | 7 years | 2,555 |
| **Invoice** | 7 years | 2,555 |
| **Report** | 5 years | 1,825 |
| **Spreadsheet** | 5 years | 1,825 |
| **PDF** | 5 years | 1,825 |
| **Image** | 1 year | 365 |
| **Presentation** | 1 year | 365 |
| **Other** | 1 year (default) | 365 |

**Legal Hold Support:**
- Documents with legal hold cannot be purged
- Legal hold table (future implementation)
- Manual review required for release

---

## Testing Strategy

### Unit Tests (Package Layer)

```bash
# Test path generator
vendor/bin/phpunit packages/Document/tests/Core/PathGeneratorTest.php

# Test state transitions
vendor/bin/phpunit packages/Document/tests/ValueObjects/DocumentStateTest.php

# Test service logic (with mocked repositories)
vendor/bin/phpunit packages/Document/tests/Services/DocumentManagerTest.php
```

### Integration Tests (consuming application Layer)

```bash
# Test repository implementations
vendor/bin/phpunit consuming application (e.g., Laravel app)tests/Repositories/DbDocumentRepositoryTest.php

# Test Eloquent models
vendor/bin/phpunit consuming application (e.g., Laravel app)tests/Models/DocumentTest.php

# Test migrations
php artisan migrate --path=database/migrations/2025_01_21_000001_create_documents_table.php
```

### Feature Tests

```bash
# Test complete upload-download flow
vendor/bin/phpunit consuming application (e.g., Laravel app)tests/Feature/DocumentUploadTest.php

# Test version control workflow
vendor/bin/phpunit consuming application (e.g., Laravel app)tests/Feature/DocumentVersioningTest.php

# Test relationship management
vendor/bin/phpunit consuming application (e.g., Laravel app)tests/Feature/DocumentRelationshipTest.php
```

---

## Installation

### 1. Install Package

```bash
cd apps/consuming application
composer require nexus/document:"*@dev"
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\DocumentServiceProvider::class,
],
```

### 4. Configure Storage

Ensure `Nexus\Storage` is configured with S3-compatible backend:

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

---

## Usage Examples

### Basic Document Upload

```php
use Nexus\Document\Services\DocumentManager;
use Nexus\Document\ValueObjects\DocumentType;

class InvoiceController
{
    public function __construct(
        private readonly DocumentManager $documentManager
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $file = $request->file('invoice');
        
        $document = $this->documentManager->upload(
            stream: fopen($file->getRealPath(), 'r'),
            name: $file->getClientOriginalName(),
            type: DocumentType::INVOICE,
            ownerId: auth()->id(),
            metadata: [
                'customer_id' => $request->input('customer_id'),
                'invoice_number' => $request->input('invoice_number'),
                'amount' => $request->input('amount'),
            ],
            tags: ['billing', 'Q1-2025']
        );

        return response()->json([
            'id' => $document->getId(),
            'name' => $document->getName(),
            'storage_path' => $document->getStoragePath(),
            'checksum' => $document->getChecksumValue(),
        ]);
    }
}
```

### Document Download with Permission Check

```php
use Nexus\Document\Services\DocumentManager;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Document\Exceptions\PermissionDeniedException;

class DownloadController
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly PermissionCheckerInterface $permissionChecker
    ) {}

    public function download(string $documentId): StreamedResponse
    {
        if (!$this->permissionChecker->canView(auth()->id(), $documentId)) {
            throw new PermissionDeniedException("User cannot view document {$documentId}");
        }

        $document = $this->documentManager->download($documentId);

        return response()->stream(function() use ($document) {
            // Stream document content
        }, 200, [
            'Content-Type' => $document->getMimeType(),
            'Content-Disposition' => "attachment; filename=\"{$document->getName()}\"",
        ]);
    }
}
```

### Version Control Workflow

```php
use Nexus\Document\Services\VersionManager;

class ContractController
{
    public function __construct(
        private readonly VersionManager $versionManager
    ) {}

    public function updateContract(string $documentId, Request $request): JsonResponse
    {
        $file = $request->file('contract');
        
        $version = $this->versionManager->createVersion(
            documentId: $documentId,
            stream: fopen($file->getRealPath(), 'r'),
            createdBy: auth()->id(),
            changeDescription: $request->input('change_description')
        );

        return response()->json([
            'version' => $version->getVersionNumber(),
            'created_at' => $version->getCreatedAt(),
            'change_description' => $version->getChangeDescription(),
        ]);
    }

    public function getVersionHistory(string $documentId): JsonResponse
    {
        $versions = $this->versionManager->getVersionHistory($documentId);

        return response()->json([
            'versions' => array_map(fn($v) => [
                'version' => $v->getVersionNumber(),
                'created_by' => $v->getCreatedBy(),
                'created_at' => $v->getCreatedAt(),
                'change_description' => $v->getChangeDescription(),
                'size_bytes' => $v->getSizeBytes(),
            ], $versions),
        ]);
    }
}
```

---

## Performance Optimization

### 1. Indexed Queries

All repository methods use indexed columns:

```php
// Efficient: Uses (tenant_id, type) composite index
$documents = $repository->findByType(DocumentType::INVOICE, $tenantId);

// Efficient: Uses (tenant_id, owner_id) composite index
$documents = $repository->findByOwner($userId, $tenantId);

// Efficient: Uses JSON column index
$documents = $repository->findByTags(['urgent'], $tenantId);
```

### 2. Eager Loading

Repositories use eager loading to prevent N+1 queries:

```php
// DbDocumentRepository.php
public function findByOwner(string $ownerId): array
{
    return Document::with(['versions', 'relationships'])
        ->where('owner_id', $ownerId)
        ->get()
        ->all();
}
```

### 3. Pagination Support (Future)

```php
// Add pagination to search methods
$documents = $searchService->findByTags(
    tags: ['urgent'],
    userId: $userId,
    page: 1,
    perPage: 50
);
```

---

## Security Considerations

### 1. Checksum Verification

Every download operation verifies the SHA-256 checksum:

```php
// DocumentManager.php
public function download(string $documentId): DocumentInterface
{
    $document = $this->repository->findById($documentId);
    
    $actualChecksum = $this->crypto->hash($storagePath);
    
    if ($actualChecksum !== $document->getChecksumValue()) {
        throw new ChecksumMismatchException(
            expected: $document->getChecksumValue(),
            actual: $actualChecksum
        );
    }
    
    return $document;
}
```

### 2. Permission Enforcement

All operations check permissions before execution:

```php
// DocumentManager.php
public function delete(string $documentId, string $userId): void
{
    if (!$this->permissionChecker->canDelete($userId, $documentId)) {
        throw new PermissionDeniedException("User cannot delete document {$documentId}");
    }
    
    // ... perform deletion
}
```

### 3. Tenant Isolation

Global scopes prevent cross-tenant access:

```php
// Document.php (Eloquent model)
protected static function booted(): void
{
    static::addGlobalScope('tenant', function ($query) {
        $tenantId = app(TenantContextManager::class)->getCurrentTenantId();
        $query->where('tenant_id', $tenantId);
    });
}
```

### 4. Opaque Storage Paths

Storage paths use UUIDs to prevent enumeration attacks:

```
Bad:  /documents/1/file.pdf (sequential, guessable)
Good: /01HQXYZ9ABCDEFGHIJKLMNOPQR/2025/01/01HQXYZ9XYZ1234567890ABCD/v1.pdf
```

---

## Future Enhancements

### Phase 2: Integration (Next Steps)

- [ ] Integrate with `Nexus\AuditLogger` for audit logging
- [ ] Integrate with `Nexus\Tenant` for multi-tenancy
- [ ] Integrate with `Nexus\Domain\Identity` for user context
- [ ] Add API endpoints (REST/GraphQL)
- [ ] Add ULID generation service (centralized)
- [ ] Add factory pattern for document creation

### Phase 3: Advanced Features

- [ ] Full-text search with Elasticsearch
- [ ] Document previews and thumbnails
- [ ] Explicit document sharing with permissions
- [ ] Document templates
- [ ] Digital signatures
- [ ] Document encryption
- [ ] Annotations and comments
- [ ] Collaborative editing

### Phase 4: Intelligence Integration

- [ ] PDF rendering for invoices/reports
- [ ] OCR for scanned documents
- [ ] ML-based auto-tagging
- [ ] Content classification
- [ ] PII redaction for GDPR compliance

---

## Known Limitations

### 1. ULID Generation

Currently, repository implementations use `Str::ulid()` directly. This should be centralized via a `UlidGeneratorInterface`.

**Workaround:** Use Laravel's `Str::ulid()` in consuming application layer.

### 2. Tenant Context Access

The `DocumentPermissionChecker` currently allows same-tenant access. This should be refined with `Nexus\Tenant` integration.

**Workaround:** Manual tenant checks in application layer.

### 3. Async Processing

Batch upload is sequential. Async dispatch should be handled in the application layer (e.g., Laravel Queues).

**Workaround:** Dispatch jobs for batch uploads.

### 4. Legal Hold Table

Legal hold functionality is stubbed in `DefaultRetentionPolicy`. Requires database table implementation.

**Workaround:** Manual purge prevention in application layer.

---

## Migration Notes

### From Legacy System

If migrating from an existing document management system:

1. **Map Document Types:** Convert legacy types to `DocumentType` enum values
2. **Calculate Checksums:** Recompute SHA-256 checksums for all existing files
3. **Migrate Storage Paths:** Use `PathGenerator` to generate new paths
4. **Version History:** Import version records with correct version numbers
5. **Metadata Transformation:** Convert metadata to JSON format
6. **Tenant Assignment:** Assign documents to tenants based on ownership

### Database Seeder Example

```php
use Nexus\Document\Core\PathGenerator;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $generator = new PathGenerator();
        
        $legacyDocuments = DB::connection('legacy')->table('documents')->get();
        
        foreach ($legacyDocuments as $doc) {
            $newPath = $generator->generateStoragePath(
                tenantId: $this->mapTenantId($doc->company_id),
                documentId: Str::ulid(),
                version: 1,
                extension: $doc->extension
            );
            
            Document::create([
                'id' => Str::ulid(),
                'tenant_id' => $this->mapTenantId($doc->company_id),
                'owner_id' => $this->mapUserId($doc->user_id),
                'name' => $doc->name,
                'storage_path' => $newPath,
                'type' => $this->mapDocumentType($doc->type),
                'checksum_value' => hash_file('sha256', $doc->legacy_path),
                // ... other fields
            ]);
        }
    }
}
```

---

## Troubleshooting

### Issue: Checksum Mismatch Exception

**Cause:** File corruption or tampering detected.

**Solution:**
1. Check S3 bucket integrity
2. Verify file transfer process
3. Check for storage backend errors
4. Re-upload document if corruption confirmed

### Issue: Permission Denied Exception

**Cause:** User does not have required permissions.

**Solution:**
1. Verify user ownership
2. Check role-based permissions
3. Check tenant isolation
4. Verify share permissions (if implemented)

### Issue: Document Not Found Exception

**Cause:** Document ID does not exist or tenant mismatch.

**Solution:**
1. Verify document ID is correct ULID
2. Check tenant context
3. Check soft delete status
4. Verify database consistency

### Issue: Duplicate Relationship Exception

**Cause:** Attempting to create relationship that already exists.

**Solution:**
1. Check existing relationships with `exists()` method
2. Use `try-catch` block for idempotent operations
3. Update description instead of creating duplicate

---

## Compliance and Audit

### Audit Log Events

When integrated with `Nexus\AuditLogger`, the following events are logged:

| Event | Description | Metadata |
|-------|-------------|----------|
| `document.uploaded` | Document uploaded | owner_id, type, size_bytes, checksum |
| `document.downloaded` | Document downloaded | user_id, timestamp |
| `document.deleted` | Document deleted | user_id, reason |
| `document.state_changed` | State transition | old_state, new_state, user_id |
| `version.created` | New version created | version_number, created_by, change_description |
| `relationship.created` | Relationship created | source_id, target_id, type |

### Retention Compliance

The package supports compliance with:

- **SOX (Sarbanes-Oxley):** 7-year retention for financial documents
- **GDPR:** Right to erasure with legal hold override
- **HIPAA:** Secure storage with checksum verification
- **ISO 27001:** Audit logging and access control

---

## Contributing

### Code Standards

- PHP 8.3+ with strict types
- PSR-12 coding standards
- Readonly classes for services
- Native enums for fixed values
- Comprehensive docblocks

### Testing Requirements

- Unit test coverage: > 80%
- Integration tests for repositories
- Feature tests for complete workflows
- Performance benchmarks for large datasets

### Pull Request Checklist

- [ ] All tests pass
- [ ] Code follows PSR-12 standards
- [ ] Docblocks complete with @param, @return, @throws
- [ ] No Laravel dependencies in package layer
- [ ] Migrations include proper indexes
- [ ] README.md updated with new features

---

## License

MIT License - See `packages/Document/LICENSE`

---

## Support

For questions or issues:

- **GitHub Issues:** [nexus/monorepo/issues](https://github.com/nexus/monorepo/issues)
- **Documentation:** `packages/Document/README.md`
- **Requirements:** `docs/REQUIREMENTS_DOCUMENT.md`

---

**End of Implementation Summary**

**Next Steps:**
1. Run migrations: `php artisan migrate`
2. Register service provider in `config/app.php`
3. Run composer update: `composer update`
4. Test basic upload/download workflow
5. Integrate with `Nexus\AuditLogger` for compliance logging
