# Nexus\Document

**Framework-agnostic Enterprise Document Management (EDM) engine for Nexus ERP monorepo**

## Overview

`Nexus\Document` is a pure PHP, atomic package providing comprehensive document lifecycle management for the Nexus ERP ecosystem. It serves as the centralized authority for document storage, versioning, access control, integrity verification, and compliance-aware retention across all vertical packages (FieldService, Finance, HR, Procurement, etc.).

This package follows the **"Logic in Packages, Implementation in Applications"** principle, providing framework-agnostic contracts and services that are implemented by the consuming application (`Nexus\Atomy`).

## Key Features

- **S3-Optimized Nested Storage**: Year/month partitioned paths for millions of documents with optimal performance
- **Version Control**: Complete version history with rollback capabilities and manual pruning
- **Checksum Integrity**: SHA-256 verification on every download to prevent data corruption
- **Access Control**: Permission-based document access (view, edit, delete, share)
- **ML Classification**: Optional content processing for auto-classification and metadata extraction
- **Retention Policies**: Compliance-aware retention and purging via `Nexus\Compliance` integration
- **Document Relationships**: Link documents (amendment, supersedes, related, attachment)
- **Temporary URLs**: Secure, time-limited download links for external sharing
- **Batch Operations**: Efficient multi-document uploads with transaction safety
- **Audit Trail**: Complete activity logging via `Nexus\AuditLogger` integration
- **Multi-Tenancy**: Full tenant isolation via `Nexus\Tenant` integration

## Architecture

### Framework-Agnostic Design

This package contains **zero Laravel dependencies**. All logic is pure PHP 8.3+ code that defines:

- **Contracts (Interfaces)**: What the package needs from the outside world
- **Services**: Business logic and orchestration
- **Value Objects**: Immutable data structures
- **Exceptions**: Domain-specific error handling

The consuming application (`apps/Atomy`) provides concrete implementations of all contracts using Laravel Eloquent, migrations, and service providers.

### Package Structure

```
packages/Document/
├── composer.json              # Package definition with dependencies
├── LICENSE                    # MIT License
├── README.md                  # This file
└── src/
    ├── Contracts/             # REQUIRED: Interfaces
    │   ├── DocumentInterface.php
    │   ├── DocumentRepositoryInterface.php
    │   ├── DocumentVersionInterface.php
    │   ├── DocumentVersionRepositoryInterface.php
    │   ├── DocumentRelationshipInterface.php
    │   ├── DocumentRelationshipRepositoryInterface.php
    │   ├── PermissionCheckerInterface.php
    │   ├── DocumentSearchInterface.php
    │   ├── ContentProcessorInterface.php
    │   └── RetentionPolicyInterface.php
    │
    ├── Services/              # Business logic
    │   ├── DocumentManager.php
    │   ├── VersionManager.php
    │   ├── RelationshipManager.php
    │   ├── DocumentSearchService.php
    │   └── RetentionService.php
    │
    ├── Core/                  # Internal engine
    │   └── PathGenerator.php  # S3-optimized path generation
    │
    ├── ValueObjects/          # Immutable data structures
    │   ├── DocumentType.php           # Enum
    │   ├── DocumentState.php          # Enum
    │   ├── RelationshipType.php       # Enum
    │   ├── DocumentFormat.php         # Enum
    │   ├── DocumentMetadata.php
    │   ├── VersionMetadata.php
    │   └── ContentAnalysisResult.php
    │
    └── Exceptions/            # Domain exceptions
        ├── DocumentNotFoundException.php
        ├── VersionNotFoundException.php
        ├── PermissionDeniedException.php
        ├── ChecksumMismatchException.php
        ├── InvalidDocumentTypeException.php
        ├── StorageException.php
        ├── DocumentRenderingException.php
        ├── ContentAnalysisException.php
        └── RetentionPolicyViolationException.php
```

## Core Contracts

### Entity Interfaces

- **`DocumentInterface`**: Primary document metadata (ID, tenant, owner, type, state, storage path, checksum, version)
- **`DocumentVersionInterface`**: Version history record (version number, change description, creator, checksum)
- **`DocumentRelationshipInterface`**: Document relationship link (source, target, type)

### Repository Interfaces

- **`DocumentRepositoryInterface`**: CRUD operations for documents
- **`DocumentVersionRepositoryInterface`**: Version history persistence
- **`DocumentRelationshipRepositoryInterface`**: Relationship management

### Service Interfaces

- **`PermissionCheckerInterface`**: Access control (canView, canEdit, canDelete, canShare)
- **`DocumentSearchInterface`**: Metadata-based search (tags, type, owner, date range)
- **`ContentProcessorInterface`**: Content transformation (PDF rendering, ML classification)
- **`RetentionPolicyInterface`**: Compliance-aware retention rules

## Value Objects

### Enums (Native PHP 8.3)

```php
enum DocumentType: string {
    case CONTRACT = 'contract';
    case INVOICE = 'invoice';
    case REPORT = 'report';
    case IMAGE = 'image';
    case SPREADSHEET = 'spreadsheet';
    case PRESENTATION = 'presentation';
    case PDF = 'pdf';
    case OTHER = 'other';
}

enum DocumentState: string {
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';
}

enum RelationshipType: string {
    case AMENDMENT = 'amendment';
    case SUPERSEDES = 'supersedes';
    case RELATED = 'related';
    case ATTACHMENT = 'attachment';
}

enum DocumentFormat: string {
    case PDF = 'pdf';
    case HTML = 'html';
    case DOCX = 'docx';
}
```

### Readonly Classes

- **`DocumentMetadata`**: Original filename, MIME type, file size, checksum, tags, custom fields
- **`VersionMetadata`**: Version number, change description, creator, timestamp
- **`ContentAnalysisResult`**: ML predictions (type, confidence, extracted metadata, PII detection, suggested tags)

## Usage Examples

### 1. Upload a Document

```php
use Nexus\Document\Services\DocumentManager;
use Nexus\Document\ValueObjects\DocumentType;

$documentManager = app(DocumentManager::class);

$stream = fopen('/path/to/invoice.pdf', 'r');
$document = $documentManager->upload($stream, [
    'type' => DocumentType::INVOICE,
    'original_filename' => 'invoice-2025-001.pdf',
    'mime_type' => 'application/pdf',
    'tags' => ['finance', 'vendor-acme'],
    'custom_fields' => [
        'vendor_id' => 'VND-001',
        'invoice_number' => 'INV-2025-001',
        'amount' => 1500.00
    ]
], $ownerId);

// Document is stored at: {tenantId}/2025/11/{uuid}/v1.pdf
// Checksum calculated and verified
// Audit log created: "Document 'invoice-2025-001.pdf' uploaded"
```

### 2. Upload with Auto-Classification

```php
$document = $documentManager->upload($stream, [
    'original_filename' => 'scanned-document.pdf',
    'mime_type' => 'application/pdf'
], $ownerId, autoAnalyze: true);

// ContentProcessorInterface::analyze() is called
// ML predicts DocumentType::INVOICE with 0.98 confidence
// Metadata automatically populated with extracted fields
```

### 3. Download with Permission Check

```php
try {
    $stream = $documentManager->download($documentId, $userId);
    
    // Permission verified via PermissionCheckerInterface::canView()
    // Checksum verified against stored value
    // Audit log created: "User downloaded document"
    
    return response()->stream(function() use ($stream) {
        fpassthru($stream);
    });
} catch (PermissionDeniedException $e) {
    // User lacks permission
} catch (ChecksumMismatchException $e) {
    // Data corruption detected - security alert!
}
```

### 4. Generate Temporary Download URL

```php
$url = $documentManager->getTemporaryDownloadUrl($documentId, $userId, ttl: 3600);

// Permission check performed first
// Delegates to StorageDriverInterface::getTemporaryUrl()
// Returns signed URL valid for 1 hour
// Audit log created: "Temporary URL generated"
```

### 5. Create a New Version

```php
use Nexus\Document\Services\VersionManager;

$versionManager = app(VersionManager::class);

$newStream = fopen('/path/to/invoice-revised.pdf', 'r');
$version = $versionManager->createVersion(
    $documentId,
    $newStream,
    'Updated vendor address and payment terms',
    $userId
);

// New version created: {tenantId}/2025/11/{uuid}/v2.pdf
// Document version incremented to 2
// Version history preserved
// Audit log created: "Version 2 created by User X"
```

### 6. Batch Upload

```php
$files = [
    ['stream' => $stream1, 'metadata' => ['original_filename' => 'photo1.jpg', ...]],
    ['stream' => $stream2, 'metadata' => ['original_filename' => 'photo2.jpg', ...]],
    ['stream' => $stream3, 'metadata' => ['original_filename' => 'photo3.jpg', ...]],
];

$documents = $documentManager->uploadBatch($files);

// All files processed sequentially
// Transaction ensures all-or-nothing persistence
// Single audit log with batch count and total size
```

### 7. Create Document Relationship

```php
use Nexus\Document\Services\RelationshipManager;
use Nexus\Document\ValueObjects\RelationshipType;

$relationshipManager = app(RelationshipManager::class);

$relationship = $relationshipManager->createRelationship(
    $invoiceDocId,
    $receiptDocId,
    RelationshipType::RELATED,
    $userId
);

// Link created between invoice and receipt
// Permission verified on source document
// Audit log created: "Relationship created"
```

### 8. Search Documents

```php
use Nexus\Document\Services\DocumentSearchService;
use Nexus\Document\ValueObjects\DocumentType;

$searchService = app(DocumentSearchService::class);

// Search by tags
$documents = $searchService->findByTags(['finance', 'urgent'], $userId);

// Search by type with filters
$documents = $searchService->findByType(
    DocumentType::INVOICE,
    [
        'dateFrom' => '2025-01-01',
        'dateTo' => '2025-12-31',
        'ownerId' => $userId
    ],
    $userId
);

// Search by metadata
$documents = $searchService->findByMetadata([
    'customFields.vendor_id' => 'VND-001',
    'customFields.amount' => ['>=', 1000.00]
], $userId);

// All results filtered by permissions and tenant scope
```

## Storage Path Strategy

### Nested S3-Optimized Structure

The package uses a **year/month partitioned** path structure for optimal object storage performance:

```
{tenantId}/{year}/{month}/{uuid}/v{version}.{extension}

Example:
TEN123456789/2025/11/DOC987654321/v1.pdf
TEN123456789/2025/11/DOC987654321/v2.pdf
TEN123456789/2025/12/DOC111222333/v1.jpg
```

### Performance Benefits

1. **Hot Partition Avoidance**: Distributes writes across multiple prefixes instead of all new documents going to one flat folder
2. **Lifecycle Policies**: Easy to archive/delete all documents older than N years using prefix-based S3 lifecycle rules
3. **Query Performance**: Object storage systems index by prefix - nested structure improves list/search operations
4. **Cost Optimization**: Archive old year/month prefixes to Glacier storage class automatically

### PathGenerator Service

The `Core/PathGenerator` service encapsulates all path logic:

```php
use Nexus\Document\Core\PathGenerator;

$pathGenerator = app(PathGenerator::class);

$path = $pathGenerator->generateStoragePath(
    $tenantId,
    $uuid,
    $version,
    $extension
);
// Returns: "TEN123/2025/11/DOC456/v1.pdf"

$components = $pathGenerator->parseStoragePath($path);
// Returns: ['tenant' => 'TEN123', 'year' => '2025', 'month' => '11', 'uuid' => 'DOC456', 'version' => 1]
```

## Content Processing

### ContentProcessorInterface Integration

The package defines a `ContentProcessorInterface` for extensibility:

```php
interface ContentProcessorInterface
{
    /**
     * Render data into a document format (e.g., Service Report to PDF)
     */
    public function render(
        string $templateName,
        array $data,
        DocumentFormat $format
    ): string;

    /**
     * Analyze document content using ML (OCR, classification, metadata extraction)
     */
    public function analyze(string $documentPath): ContentAnalysisResult;
}
```

### Default Implementation (No-Op)

By default, `Nexus\Atomy` binds a `NullContentProcessor` that logs warnings:

```php
// No Intelligence package configured
$result = $contentProcessor->analyze($documentPath);
// Returns: ContentAnalysisResult with null predictions and 0.0 confidence
```

### Future Intelligence Integration

When `Nexus\Intelligence` is available, simply rebind in `DocumentServiceProvider`:

```php
$this->app->singleton(
    ContentProcessorInterface::class,
    IntelligenceContentProcessor::class
);

// Now analyze() calls ML models for real predictions
// Now render() generates professional PDFs from templates
```

## Integration with Applications

### Atomy Implementation

The `apps/Atomy` application provides concrete implementations:

#### Models

```php
// apps/Atomy/app/Models/Document.php
class Document extends Model implements DocumentInterface
{
    use SoftDeletes;
    
    protected $casts = [
        'metadata' => 'array',
        'type' => DocumentType::class,
        'state' => DocumentState::class
    ];
    
    // Tenant scoping applied automatically in booted()
}
```

#### Repositories

```php
// apps/Atomy/app/Repositories/DbDocumentRepository.php
class DbDocumentRepository implements DocumentRepositoryInterface
{
    public function findById(string $id): ?DocumentInterface
    {
        return Document::find($id);
    }
    
    // ... other methods using Eloquent
}
```

#### Service Provider

```php
// apps/Atomy/app/Providers/DocumentServiceProvider.php
class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            DocumentRepositoryInterface::class,
            DbDocumentRepository::class
        );
        
        // Bind services
        $this->app->singleton(
            PermissionCheckerInterface::class,
            DocumentPermissionChecker::class
        );
        
        $this->app->singleton(
            ContentProcessorInterface::class,
            NullContentProcessor::class // Override when Intelligence available
        );
        
        // Register package services
        $this->app->singleton(DocumentManager::class);
        $this->app->singleton(VersionManager::class);
        $this->app->singleton(RelationshipManager::class);
    }
}
```

## Dependencies

This package requires:

- **`nexus/storage`** (MANDATORY): File operations and temporary URL generation
- **`nexus/crypto`** (MANDATORY): SHA-256 checksum calculation via HasherInterface
- **`nexus/audit-logger`** (RECOMMENDED): Complete audit trail for all document operations
- **`nexus/tenant`** (RECOMMENDED): Multi-tenancy isolation and context
- **`psr/log`** (MANDATORY): Logging interface for error/debug logging

## Installation

```bash
# In the monorepo root
composer require nexus/document:"*@dev"

# In apps/Atomy
composer require nexus/document:"*@dev"
```

## Requirements Coverage

This package fulfills all requirements documented in `docs/REQUIREMENTS_DOCUMENT.md` including:

- Document CRUD operations with tenant isolation
- Version control with complete history
- Permission-based access control
- Checksum integrity verification
- S3-optimized storage paths
- Batch upload operations
- Document relationship management
- Metadata-based search
- Retention policy integration
- Audit logging for compliance
- Content processing extensibility

## Documentation

### Core Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start with prerequisites, core concepts, and troubleshooting
- **[API Reference](docs/api-reference.md)** - Complete interface and service documentation
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples

### Implementation Details
- **[Requirements](REQUIREMENTS.md)** - Complete requirements tracking with status
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Progress metrics and design decisions
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - Test coverage and testing strategy
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package valuation metrics

### Code Examples
- **[Basic Usage Examples](docs/examples/basic-usage.php)** - 15 common document operations
- **[Advanced Usage Examples](docs/examples/advanced-usage.php)** - 18 advanced scenarios

## Quick Links

- **Package Reference**: [`docs/NEXUS_PACKAGES_REFERENCE.md`](../../docs/NEXUS_PACKAGES_REFERENCE.md)
- **Architecture Overview**: [`ARCHITECTURE.md`](../../ARCHITECTURE.md)
- **Coding Standards**: [`.github/copilot-instructions.md`](../../.github/copilot-instructions.md)

## License

MIT License - see LICENSE file for details.
