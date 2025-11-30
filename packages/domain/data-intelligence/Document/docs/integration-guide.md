# Integration Guide: Nexus\Document

**Purpose:** Complete application layer integration examples for Laravel and Symfony.

---

## Laravel Integration

### Step 1: Database Migrations

**Create migrations for documents, versions, and relationships:**

```php
// database/migrations/2025_01_01_000001_create_documents_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('type')->index();
            $table->string('state', 20);
            $table->string('storage_path');
            $table->json('metadata');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'type']);
        });
    }
};

// database/migrations/2025_01_01_000002_create_document_versions_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->ulid('document_id');
            $table->integer('version_number');
            $table->string('storage_path');
            $table->string('checksum', 64);
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->unique(['document_id', 'version_number']);
        });
    }
};

// database/migrations/2025_01_01_000003_create_document_relationships_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_relationships', function (Blueprint $table) {
            $table->id();
            $table->ulid('document_id');
            $table->ulid('related_document_id');
            $table->string('relationship_type');
            $table->timestamps();
            
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('related_document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->unique(['document_id', 'related_document_id', 'relationship_type'], 'doc_rel_unique');
        });
    }
};
```

### Step 2: Eloquent Models

```php
// app/Models/Document.php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\ValueObjects\DocumentMetadata;
use Nexus\Document\ValueObjects\DocumentState;

class Document extends Model implements DocumentInterface
{
    use HasUlids, SoftDeletes;
    
    protected $fillable = ['tenant_id', 'type', 'state', 'storage_path', 'metadata'];
    
    protected $casts = [
        'metadata' => 'array',
        'state' => DocumentState::class,
    ];
    
    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }
    
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
        return $this->state;
    }
    
    public function getMetadata(): DocumentMetadata
    {
        return new DocumentMetadata(
            title: $this->metadata['title'],
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
        return $this->versions()->orderByDesc('version_number')->first();
    }
}

// app/Models/DocumentVersion.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Document\Contracts\DocumentVersionInterface;

class DocumentVersion extends Model implements DocumentVersionInterface
{
    protected $fillable = [
        'document_id',
        'version_number',
        'storage_path',
        'checksum',
        'file_size',
        'mime_type',
        'notes',
    ];
    
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
    
    public function getVersionNumber(): int
    {
        return $this->version_number;
    }
    
    public function getStoragePath(): string
    {
        return $this->storage_path;
    }
    
    public function getChecksum(): string
    {
        return $this->checksum;
    }
    
    public function getFileSize(): int
    {
        return $this->file_size;
    }
    
    public function getMimeType(): string
    {
        return $this->mime_type;
    }
    
    public function getNotes(): ?string
    {
        return $this->notes;
    }
}

// app/Models/DocumentRelationship.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\Document\Contracts\DocumentRelationshipInterface;
use Nexus\Document\ValueObjects\RelationshipType;

class DocumentRelationship extends Model implements DocumentRelationshipInterface
{
    protected $fillable = ['document_id', 'related_document_id', 'relationship_type'];
    
    protected $casts = [
        'relationship_type' => RelationshipType::class,
    ];
    
    public function getDocumentId(): string
    {
        return $this->document_id;
    }
    
    public function getRelatedDocumentId(): string
    {
        return $this->related_document_id;
    }
    
    public function getRelationshipType(): RelationshipType
    {
        return $this->relationship_type;
    }
}
```

### Step 3: Repository Implementations

```php
// app/Repositories/EloquentDocumentRepository.php
namespace App\Repositories;

use App\Models\Document;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Exceptions\DocumentNotFoundException;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class EloquentDocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}
    
    public function findById(string $id): DocumentInterface
    {
        $document = Document::query()
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->find($id);
            
        if (!$document) {
            throw DocumentNotFoundException::forId($id);
        }
        
        return $document;
    }
    
    public function save(DocumentInterface $document): void
    {
        if ($document instanceof Document) {
            $document->save();
        }
    }
    
    public function delete(string $id): void
    {
        Document::query()
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('id', $id)
            ->delete();
    }
    
    public function findByTenantAndType(string $tenantId, string $type): array
    {
        return Document::query()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->get()
            ->all();
    }
}

// app/Repositories/EloquentDocumentVersionRepository.php
namespace App\Repositories;

use App\Models\DocumentVersion;
use Nexus\Document\Contracts\DocumentVersionInterface;
use Nexus\Document\Contracts\DocumentVersionRepositoryInterface;

final class EloquentDocumentVersionRepository implements DocumentVersionRepositoryInterface
{
    public function createVersion(
        string $documentId,
        int $versionNumber,
        string $storagePath,
        string $checksum,
        int $fileSize,
        string $mimeType,
        ?string $notes = null
    ): DocumentVersionInterface {
        return DocumentVersion::create([
            'document_id' => $documentId,
            'version_number' => $versionNumber,
            'storage_path' => $storagePath,
            'checksum' => $checksum,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'notes' => $notes,
        ]);
    }
    
    public function findVersionsForDocument(string $documentId): array
    {
        return DocumentVersion::query()
            ->where('document_id', $documentId)
            ->orderBy('version_number')
            ->get()
            ->all();
    }
    
    public function deleteOldVersions(string $documentId, int $keepCount): void
    {
        $versionsToDelete = DocumentVersion::query()
            ->where('document_id', $documentId)
            ->orderByDesc('version_number')
            ->skip($keepCount)
            ->pluck('id');
            
        DocumentVersion::destroy($versionsToDelete);
    }
}
```

### Step 4: Service Provider

```php
// app/Providers/DocumentServiceProvider.php
namespace App\Providers;

use App\Repositories\EloquentDocumentRepository;
use App\Repositories\EloquentDocumentVersionRepository;
use App\Repositories\EloquentDocumentRelationshipRepository;
use App\Services\LaravelPermissionChecker;
use App\Services\LaravelStorageAdapter;
use Illuminate\Support\ServiceProvider;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\DocumentVersionRepositoryInterface;
use Nexus\Document\Contracts\DocumentRelationshipRepositoryInterface;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Document\Services\DocumentManager;
use Nexus\Document\Services\VersionManager;
use Nexus\Document\Services\RelationshipManager;
use Nexus\Storage\Contracts\StorageInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;

class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(DocumentRepositoryInterface::class, EloquentDocumentRepository::class);
        $this->app->singleton(DocumentVersionRepositoryInterface::class, EloquentDocumentVersionRepository::class);
        $this->app->singleton(DocumentRelationshipRepositoryInterface::class, EloquentDocumentRelationshipRepository::class);
        
        // Bind application-specific implementations
        $this->app->singleton(PermissionCheckerInterface::class, LaravelPermissionChecker::class);
        
        // Bind services
        $this->app->singleton(DocumentManager::class, function ($app) {
            return new DocumentManager(
                documentRepository: $app->make(DocumentRepositoryInterface::class),
                versionRepository: $app->make(DocumentVersionRepositoryInterface::class),
                storage: $app->make(StorageInterface::class),
                tenantContext: $app->make(TenantContextInterface::class),
                permissionChecker: $app->make(PermissionCheckerInterface::class)
            );
        });
        
        $this->app->singleton(VersionManager::class);
        $this->app->singleton(RelationshipManager::class);
    }
}
```

### Step 5: Usage in Controllers

```php
// app/Http/Controllers/DocumentController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nexus\Document\Services\DocumentManager;
use Nexus\Document\ValueObjects\DocumentState;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentManager $documentManager
    ) {}
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'file' => 'required|file',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'tags' => 'array',
        ]);
        
        $document = $this->documentManager->create(
            type: $validated['type'],
            file: $request->file('file'),
            metadata: [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'tags' => $validated['tags'] ?? [],
            ]
        );
        
        return response()->json([
            'id' => $document->getId(),
            'message' => 'Document created successfully',
        ], 201);
    }
    
    public function download(string $id)
    {
        $content = $this->documentManager->download($id);
        $document = $this->documentManager->findById($id);
        
        return response($content)
            ->header('Content-Type', $document->getCurrentVersion()->getMimeType())
            ->header('Content-Disposition', 'attachment; filename="' . $document->getMetadata()->title . '"');
    }
    
    public function archive(string $id)
    {
        $this->documentManager->transitionState($id, DocumentState::Archived);
        
        return response()->json(['message' => 'Document archived']);
    }
}
```

---

## Symfony Integration

### Step 1: Doctrine Entities

```php
// src/Entity/Document.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\ValueObjects\DocumentMetadata;
use Nexus\Document\ValueObjects\DocumentState;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'documents')]
class Document implements DocumentInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;
    
    #[ORM\Column(type: 'string')]
    private string $tenantId;
    
    #[ORM\Column(type: 'string')]
    private string $type;
    
    #[ORM\Column(type: 'string', enumType: DocumentState::class)]
    private DocumentState $state;
    
    #[ORM\Column(type: 'string')]
    private string $storagePath;
    
    #[ORM\Column(type: 'json')]
    private array $metadata;
    
    public function __construct()
    {
        $this->id = new Ulid();
        $this->state = DocumentState::Draft;
    }
    
    public function getId(): string
    {
        return $this->id->toRfc4122();
    }
    
    // Implement other interface methods...
}
```

### Step 2: Doctrine Repositories

```php
// src/Repository/DoctrineDocumentRepository.php
namespace App\Repository;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Exceptions\DocumentNotFoundException;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class DoctrineDocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TenantContextInterface $tenantContext
    ) {}
    
    public function findById(string $id): DocumentInterface
    {
        $document = $this->entityManager
            ->getRepository(Document::class)
            ->findOneBy([
                'id' => $id,
                'tenantId' => $this->tenantContext->getCurrentTenantId(),
            ]);
            
        if (!$document) {
            throw DocumentNotFoundException::forId($id);
        }
        
        return $document;
    }
    
    public function save(DocumentInterface $document): void
    {
        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }
    
    // Implement other methods...
}
```

### Step 3: Services Configuration

```yaml
# config/services.yaml
services:
    # Repositories
    Nexus\Document\Contracts\DocumentRepositoryInterface:
        class: App\Repository\DoctrineDocumentRepository
        
    Nexus\Document\Contracts\DocumentVersionRepositoryInterface:
        class: App\Repository\DoctrineDocumentVersionRepository
        
    # Services
    Nexus\Document\Services\DocumentManager:
        arguments:
            $documentRepository: '@Nexus\Document\Contracts\DocumentRepositoryInterface'
            $versionRepository: '@Nexus\Document\Contracts\DocumentVersionRepositoryInterface'
            $storage: '@Nexus\Storage\Contracts\StorageInterface'
            $tenantContext: '@Nexus\Tenant\Contracts\TenantContextInterface'
            $permissionChecker: '@Nexus\Document\Contracts\PermissionCheckerInterface'
```

---

## Common Integration Patterns

### Pattern 1: Multi-Tenant Document Upload

```php
// Automatic tenant scoping
public function uploadDocument(UploadedFile $file): DocumentInterface
{
    // TenantContext automatically scopes to current tenant
    return $this->documentManager->create(
        type: 'invoice',
        file: $file,
        metadata: [
            'title' => $file->getClientOriginalName(),
            'tags' => ['uploaded'],
        ]
    );
}
```

### Pattern 2: Document Versioning Workflow

```php
public function updateDocument(string $documentId, UploadedFile $newFile, string $notes): void
{
    // Create new version (non-destructive)
    $this->versionManager->createVersion(
        documentId: $documentId,
        file: $newFile,
        notes: $notes
    );
    
    // Optionally prune old versions (keep last 5)
    $this->versionManager->pruneOldVersions($documentId, keepCount: 5);
}
```

### Pattern 3: Document Relationship Chain

```php
public function amendContract(string $originalContractId, UploadedFile $amendmentFile): DocumentInterface
{
    // Create amendment document
    $amendment = $this->documentManager->create(
        type: 'contract',
        file: $amendmentFile,
        metadata: ['title' => 'Contract Amendment']
    );
    
    // Link to original
    $this->relationshipManager->createRelationship(
        documentId: $amendment->getId(),
        relatedDocumentId: $originalContractId,
        type: RelationshipType::Amendment
    );
    
    return $amendment;
}
```

### Pattern 4: Permission-Aware Downloads

```php
public function secureDownload(string $documentId, string $userId): Response
{
    if (!$this->permissionChecker->canView($userId, $documentId)) {
        throw PermissionDeniedException::forAction('view', $documentId);
    }
    
    $content = $this->documentManager->download($documentId);
    
    // Return file response
}
```

---

## Troubleshooting

### Issue: Interface Not Bound

**Error:** `Target interface [Nexus\Document\Contracts\DocumentRepositoryInterface] is not instantiable.`

**Solution:** Register repository binding in service provider:
```php
$this->app->singleton(DocumentRepositoryInterface::class, EloquentDocumentRepository::class);
```

### Issue: Tenant Context Missing

**Error:** `Call to a member function getCurrentTenantId() on null`

**Solution:** Ensure `Nexus\Tenant` package is installed and tenant middleware is active.

### Issue: Storage Path Not Found

**Error:** `File not found at path: TENANT001/2025/11/...`

**Solution:** Verify S3 bucket configuration in `.env`:
```env
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

---

**Last Updated:** November 24, 2025  
**Package Version:** 1.0.0
