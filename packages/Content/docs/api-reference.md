# API Reference: Nexus Content

Complete API documentation for all public interfaces, services, and value objects.

## Services

### ArticleManager

Main service for article operations.

#### Constructor

```php
public function __construct(
    private ContentRepositoryInterface $repository,
    private ContentSearchInterface $searchEngine,
    private ?ClockInterface $clock = null,
    private ?AuditLoggerInterface $auditLogger = null,
    private ?TelemetryTrackerInterface $telemetry = null,
)
```

#### createArticle()

Create new article with initial draft version.

```php
public function createArticle(
    string $articleId,
    string $title,
    string $slug,
    ArticleCategory $category,
    string $textContent,
    string $authorId,
    bool $isPublic = false,
    array $options = [],
): Article
```

**Options:**
- `scheduledPublishAt` (\DateTimeImmutable) - Future publish date
- `translationGroupId` (string) - Link to translation group
- `languageCode` (string) - Language code (e.g., 'en-US')
- `accessControlPartyIds` (array) - Party IDs for access control
- `metadata` (array) - Additional metadata
- `versionMetadata` (array) - Version-specific metadata

**Throws:** `DuplicateSlugException`

#### publish()

Publish draft version (makes it active).

```php
public function publish(string $articleId, ?string $versionId = null): Article
```

**Throws:** `ArticleNotFoundException`, `InvalidStatusTransitionException`

#### updateContent()

Update content (creates new draft version).

```php
public function updateContent(
    string $articleId,
    string $textContent,
    string $authorId,
    array $options = [],
): Article
```

**Throws:** `ArticleNotFoundException`, `ContentLockedException`

#### submitForReview()

Submit draft for review.

```php
public function submitForReview(string $articleId, ?string $versionId = null): Article
```

#### compareVersions()

Generate diff between two versions.

```php
public function compareVersions(
    string $articleId,
    string $versionId1,
    string $versionId2
): array
```

**Returns:** `['added' => [...], 'removed' => [...], 'unchanged' => [...]]`

See [README.md](../README.md) for full service method list.

---

## Value Objects

### Article

```php
final readonly class Article
{
    public string $articleId;
    public string $title;
    public string $slug;
    public ArticleCategory $category;
    public bool $isPublic;
    public array $versionHistory;
    public ?string $translationGroupId;
    public ?string $languageCode;
    public array $accessControlPartyIds;
    public ?EditLock $editLock;
    public \DateTimeImmutable $createdAt;
    public \DateTimeImmutable $updatedAt;
    public array $metadata;
    
    // Methods
    public static function create(...): self;
    public function getActiveVersion(): ?ContentVersion;
    public function getLatestDraft(): ?ContentVersion;
    public function isLocked(\DateTimeImmutable $currentTime): bool;
    public function canBeViewedBy(?string $partyId): bool;
}
```

### ContentVersion

```php
final readonly class ContentVersion
{
    public string $versionId;
    public int $versionNumber;
    public string $textContent;
    public ContentStatus $status;
    public string $authorId;
    public \DateTimeImmutable $createdAt;
    public ?\DateTimeImmutable $publishedAt;
    public ?\DateTimeImmutable $scheduledPublishAt;
    public array $metadata;
    
    // Methods
    public static function createDraft(...): self;
    public static function createNext(...): self;
    public function withStatus(ContentStatus $newStatus): self;
    public function isScheduled(): bool;
}
```

### ArticleCategory

```php
final readonly class ArticleCategory
{
    public string $categoryId;
    public string $name;
    public string $slug;
    public ?string $parentCategoryId;
    public int $level;
    public ?string $description;
    
    // Methods
    public static function createRoot(...): self;
    public static function createChild(...): self;
    public function canHaveChildren(): bool;
}
```

---

## Contracts

### ContentRepositoryInterface

```php
interface ContentRepositoryInterface
{
    public function saveArticle(Article $article): void;
    public function findById(string $articleId): ?Article;
    public function findBySlug(string $slug): ?Article;
    public function findVersionById(string $versionId): ?ContentVersion;
    public function findTranslations(string $translationGroupId): array;
    public function isSlugAvailable(string $slug, ?string $excludeArticleId = null): bool;
    public function findByCategory(string $categoryId, int $limit = 20, int $offset = 0): array;
    public function findScheduledForPublish(\DateTimeImmutable $currentTime): array;
}
```

### ContentSearchInterface

```php
interface ContentSearchInterface
{
    public function indexArticle(Article $article): void;
    public function removeArticle(string $articleId): void;
    public function search(SearchCriteria $criteria): array;
    public function reindexAll(): int;
}
```

---

## Enums

### ContentStatus

```php
enum ContentStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Archived = 'archived';
    
    public function isEditable(): bool;
    public function isPubliclyVisible(): bool;
    public function canTransitionTo(self $target): bool;
}
```

---

## Exceptions

All exceptions extend `ContentException`:

- `ArticleNotFoundException` - Article not found
- `ContentVersionNotFoundException` - Version not found
- `InvalidStatusTransitionException` - Invalid workflow transition
- `InvalidContentException` - Invalid data
- `InvalidCategoryException` - Invalid category data
- `ContentLockedException` - Article locked by another user
- `DuplicateSlugException` - Slug already exists

---

For usage examples, see [getting-started.md](getting-started.md).
