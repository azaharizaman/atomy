# API Reference: Nexus\Document

## Interfaces

### DocumentInterface

**Location:** `src/Contracts/DocumentInterface.php`

**Purpose:** Represents a document entity with metadata, state, and version tracking.

**Methods:**

#### getId()
```php
public function getId(): string;
```
Returns the document's unique identifier (ULID).

#### getType()
```php
public function getType(): string;
```
Returns the document type (e.g., 'invoice', 'contract', 'report').

#### getState()
```php
public function getState(): DocumentState;
```
Returns the current document state (Draft, Active, Archived, Deleted, Purged).

#### getMetadata()
```php
public function getMetadata(): DocumentMetadata;
```
Returns document metadata (title, description, tags).

#### getStoragePath()
```php
public function getStorage Path(): string;
```
Returns the base S3 storage path for this document.

#### getCurrentVersion()
```php
public function getCurrentVersion(): DocumentVersionInterface;
```
Returns the latest version of the document.

---

### DocumentVersionInterface

**Location:** `src/Contracts/DocumentVersionInterface.php`

**Purpose:** Represents a specific version of a document with file metadata.

**Methods:**

#### getVersionNumber()
```php
public function getVersionNumber(): int;
```
Returns version number (1, 2, 3, ...).

#### getStoragePath()
```php
public function getStoragePath(): string;
```
Returns full S3 path to this version's file.

#### getChecksum()
```php
public function getChecksum(): string;
```
Returns SHA-256 checksum for integrity verification.

#### getFileSize()
```php
public function getFileSize(): int;
```
Returns file size in bytes.

#### getMimeType()
```php
public function getMimeType(): string;
```
Returns MIME type (e.g., 'application/pdf').

#### getNotes()
```php
public function getNotes(): ?string;
```
Returns notes explaining this version (nullable).

---

### DocumentRelationshipInterface

**Location:** `src/Contracts/DocumentRelationshipInterface.php`

**Purpose:** Represents a relationship between two documents.

**Methods:**

#### getDocumentId()
```php
public function getDocumentId(): string;
```
Returns the source document ID.

#### getRelatedDocumentId()
```php
public function getRelatedDocumentId(): string;
```
Returns the target document ID.

#### getRelationshipType()
```php
public function getRelationshipType(): RelationshipType;
```
Returns the relationship type enum (Amendment, Supersedes, Related, Attachment).

---

### DocumentRepositoryInterface

**Location:** `src/Contracts/DocumentRepositoryInterface.php`

**Purpose:** CRUD operations for documents.

**Methods:**

#### findById()
```php
public function findById(string $id): DocumentInterface;
```
**Throws:** `DocumentNotFoundException`

#### save()
```php
public function save(DocumentInterface $document): void;
```

#### delete()
```php
public function delete(string $id): void;
```

#### findByTenantAndType()
```php
public function findByTenantAndType(string $tenantId, string $type): array;
```
Returns array of `DocumentInterface`.

---

### DocumentVersionRepositoryInterface

**Location:** `src/Contracts/DocumentVersionRepositoryInterface.php`

**Purpose:** Version management operations.

**Methods:**

#### createVersion()
```php
public function createVersion(
    string $documentId,
    int $versionNumber,
    string $storagePath,
    string $checksum,
    int $fileSize,
    string $mimeType,
    ?string $notes = null
): DocumentVersionInterface;
```

#### findVersionsForDocument()
```php
public function findVersionsForDocument(string $documentId): array;
```

#### deleteOldVersions()
```php
public function deleteOldVersions(string $documentId, int $keepCount): void;
```

---

### DocumentRelationshipRepositoryInterface

**Location:** `src/Contracts/DocumentRelationshipRepositoryInterface.php`

**Purpose:** Relationship management.

**Methods:**

#### createRelationship()
```php
public function createRelationship(
    string $documentId,
    string $relatedDocumentId,
    RelationshipType $type
): DocumentRelationshipInterface;
```
**Throws:** `DuplicateRelationshipException`

#### findRelationships()
```php
public function findRelationships(string $documentId, ?RelationshipType $type = null): array;
```

#### deleteRelationship()
```php
public function deleteRelationship(string $documentId, string $relatedDocumentId, RelationshipType $type): void;
```

---

### PermissionCheckerInterface

**Location:** `src/Contracts/PermissionCheckerInterface.php`

**Purpose:** Document access control.

**Methods:**

#### canView()
```php
public function canView(string $userId, string $documentId): bool;
```

#### canEdit()
```php
public function canEdit(string $userId, string $documentId): bool;
```

#### canDelete()
```php
public function canDelete(string $userId, string $documentId): bool;
```

#### canShare()
```php
public function canShare(string $userId, string $documentId): bool;
```

---

### ContentProcessorInterface

**Location:** `src/Contracts/ContentProcessorInterface.php`

**Purpose:** Optional ML-based content analysis.

**Methods:**

#### processContent()
```php
public function processContent(string $filePath, string $mimeType): ContentAnalysisResult;
```
Extracts metadata, classifies content, performs OCR if needed.

---

### RetentionPolicyInterface

**Location:** `src/Contracts/RetentionPolicyInterface.php`

**Purpose:** Compliance-aware retention logic.

**Methods:**

#### canDelete()
```php
public function canDelete(string $documentId): bool;
```
Returns false if document is under retention or legal hold.

#### getRetentionPeriod()
```php
public function getRetentionPeriod(string $documentType): ?\DateInterval;
```
Returns retention period for document type (nullable).

---

### DocumentSearchInterface

**Location:** `src/Contracts/DocumentSearchInterface.php`

**Purpose:** Document search operations.

**Methods:**

#### search()
```php
public function search(array $criteria): array;
```
Criteria: `['type' => '...', 'tags' => [...], 'dateFrom' => '...', ...]`

Returns array of `DocumentInterface`.

---

## Services

### DocumentManager

**Location:** `src/Services/DocumentManager.php`

**Purpose:** High-level document lifecycle management.

**Key Methods:**

#### create()
```php
public function create(string $type, $file, array $metadata): DocumentInterface;
```

#### download()
```php
public function download(string $documentId): string;
```
Returns file content. Verifies checksum. **Throws:** `ChecksumMismatchException`

#### updateMetadata()
```php
public function updateMetadata(string $documentId, array $metadata): void;
```

#### transitionState()
```php
public function transitionState(string $documentId, DocumentState $newState): void;
```
**Throws:** `InvalidDocumentStateException` if transition not allowed.

---

### VersionManager

**Location:** `src/Services/VersionManager.php`

**Purpose:** Version control operations.

**Key Methods:**

#### createVersion()
```php
public function createVersion(string $documentId, $file, ?string $notes = null): DocumentVersionInterface;
```

#### rollbackToVersion()
```php
public function rollbackToVersion(string $documentId, int $versionNumber): DocumentVersionInterface;
```
Non-destructive rollback (creates new version with old content).

#### pruneOldVersions()
```php
public function pruneOldVersions(string $documentId, int $keepCount): void;
```

---

### RelationshipManager

**Location:** `src/Services/RelationshipManager.php`

**Purpose:** Manage document relationships.

**Key Methods:**

#### createRelationship()
```php
public function createRelationship(
    string $documentId,
    string $relatedDocumentId,
    RelationshipType $type
): DocumentRelationshipInterface;
```

#### getRelationshipGraph()
```php
public function getRelationshipGraph(string $documentId): array;
```
Returns nested array of all related documents.

---

### RetentionService

**Location:** `src/Services/RetentionService.php`

**Purpose:** Compliance retention enforcement.

**Key Methods:**

#### applyPolicy()
```php
public function applyPolicy(string $documentId, \DateTimeImmutable $retainUntil, string $reason): void;
```

#### enforceRetention()
```php
public function enforceRetention(): array;
```
Purges eligible documents. Returns array of purged document IDs.

---

## Value Objects

### DocumentMetadata

**Location:** `src/ValueObjects/DocumentMetadata.php`

**Purpose:** Immutable document metadata.

**Properties:**
- `title` (string, required)
- `description` (?string)
- `tags` (array)

**Example:**
```php
$metadata = new DocumentMetadata(
    title: 'Invoice #12345',
    description: 'January 2025 invoice',
    tags: ['invoice', 'finance', 'paid']
);
```

---

### VersionMetadata

**Location:** `src/ValueObjects/VersionMetadata.php`

**Purpose:** Version-specific metadata.

**Properties:**
- `checksum` (string, SHA-256)
- `fileSize` (int, bytes)
- `mimeType` (string)
- `uploadedAt` (DateTimeImmutable)

---

### ContentAnalysisResult

**Location:** `src/ValueObjects/ContentAnalysisResult.php`

**Purpose:** ML content analysis output.

**Properties:**
- `extractedText` (?string)
- `classification` (?string, e.g., 'invoice', 'contract')
- `confidence` (float, 0.0-1.0)
- `metadata` (array, additional extracted data)

---

## Enums

### DocumentState

**Location:** `src/ValueObjects/DocumentState.php`

**Cases:**
- `Draft`
- `Active`
- `Archived`
- `Deleted`
- `Purged`

**Methods:**

#### canTransitionTo()
```php
public function canTransitionTo(DocumentState $newState): bool;
```

---

### DocumentType

**Location:** `src/ValueObjects/DocumentType.php`

**Cases:**
- `Invoice`
- `Contract`
- `Report`
- `Receipt`
- `General`

---

### DocumentFormat

**Location:** `src/ValueObjects/DocumentFormat.php`

**Cases:**
- `PDF`
- `Word`
- `Excel`
- `Image`
- `Text`

---

### RelationshipType

**Location:** `src/ValueObjects/RelationshipType.php`

**Cases:**
- `Amendment`
- `Supersedes`
- `Related`
- `Attachment`

---

## Exceptions

### DocumentNotFoundException

**Extends:** `\RuntimeException`

**Factory Methods:**
```php
public static function forId(string $id): self;
```

---

### ChecksumMismatchException

**Extends:** `\RuntimeException`

**Factory Methods:**
```php
public static function forDocument(string $documentId, string $expected, string $actual): self;
```

---

### InvalidDocumentStateException

**Extends:** `\DomainException`

**Factory Methods:**
```php
public static function invalidTransition(string $from, string $to): self;
```

---

### PermissionDeniedException

**Extends:** `\RuntimeException`

**Factory Methods:**
```php
public static function forAction(string $action, string $documentId): self;
```

---

### RetentionPolicyViolationException

**Extends:** `\DomainException`

**Factory Methods:**
```php
public static function cannotDelete(string $documentId, string $reason): self;
```

---

## Core Utilities

### PathGenerator

**Location:** `src/Core/PathGenerator.php`

**Purpose:** Generate S3-optimized storage paths.

**Methods:**

#### generateStoragePath()
```php
public function generateStoragePath(
    string $tenantId,
    string $documentId,
    int $version,
    string $extension
): string;
```

**Example:**
```php
$generator = new PathGenerator();
$path = $generator->generateStoragePath('TENANT001', 'DOC123', 1, 'pdf');
// Returns: TENANT001/2025/11/DOC123/v1.pdf
```

---

**Last Updated:** November 24, 2025  
**Package Version:** 1.0.0
