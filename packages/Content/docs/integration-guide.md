# Integration Guide: Nexus Content

This guide shows how to integrate `Nexus\Content` with popular PHP frameworks.

## Laravel Integration

### Step 1: Create Eloquent Models

```php
// app/Models/Article.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $primaryKey = 'article_id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'article_id', 'title', 'slug', 'category_id', 'is_public',
        'version_history', 'translation_group_id', 'language_code',
        'access_control_party_ids', 'edit_lock', 'metadata'
    ];
    
    protected $casts = [
        'is_public' => 'boolean',
        'version_history' => 'array',
        'access_control_party_ids' => 'array',
        'edit_lock' => 'array',
        'metadata' => 'array',
    ];
}
```

### Step 2: Create Repository Implementation

```php
// app/Repositories/EloquentContentRepository.php
namespace App\Repositories;

use Nexus\Content\Contracts\ContentRepositoryInterface;
use Nexus\Content\ValueObjects\Article;
use Nexus\Content\ValueObjects\ContentVersion;
use App\Models\Article as ArticleModel;

final class EloquentContentRepository implements ContentRepositoryInterface
{
    public function saveArticle(Article $article): void
    {
        ArticleModel::updateOrCreate(
            ['article_id' => $article->articleId],
            [
                'title' => $article->title,
                'slug' => $article->slug,
                'category_id' => $article->category->categoryId,
                'is_public' => $article->isPublic,
                'version_history' => $this->serializeVersions($article->versionHistory),
                'translation_group_id' => $article->translationGroupId,
                'language_code' => $article->languageCode,
                'access_control_party_ids' => $article->accessControlPartyIds,
                'edit_lock' => $article->editLock ? $this->serializeLock($article->editLock) : null,
                'metadata' => $article->metadata,
            ]
        );
    }
    
    public function findById(string $articleId): ?Article
    {
        $model = ArticleModel::find($articleId);
        return $model ? $this->hydrateArticle($model) : null;
    }
    
    // Implement other methods...
    
    private function hydrateArticle(ArticleModel $model): Article
    {
        // Reconstruct value object from database model
        // (Implementation details omitted for brevity)
    }
}
```

### Step 3: Create Meilisearch Integration

```php
// app/Services/MeilisearchContentSearch.php
namespace App\Services;

use Nexus\Content\Contracts\ContentSearchInterface;
use Nexus\Content\ValueObjects\Article;
use Nexus\Content\ValueObjects\SearchCriteria;
use MeiliSearch\Client;

final class MeilisearchContentSearch implements ContentSearchInterface
{
    public function __construct(private Client $client) {}
    
    public function indexArticle(Article $article): void
    {
        $activeVersion = $article->getActiveVersion();
        if ($activeVersion === null) {
            return;
        }
        
        $this->client->index('articles')->addDocuments([[
            'id' => $article->articleId,
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $activeVersion->textContent,
            'category_id' => $article->category->categoryId,
            'is_public' => $article->isPublic,
            'language_code' => $article->languageCode,
            'access_control_party_ids' => $article->accessControlPartyIds,
        ]]);
    }
    
    public function search(SearchCriteria $criteria): array
    {
        $filters = [];
        
        if ($criteria->publicOnly) {
            $filters[] = 'is_public = true';
        }
        
        if (!empty($criteria->categoryIds)) {
            $categoryFilter = implode(' OR ', array_map(
                fn($id) => "category_id = {$id}",
                $criteria->categoryIds
            ));
            $filters[] = "($categoryFilter)";
        }
        
        $results = $this->client->index('articles')->search($criteria->query ?? '', [
            'filter' => $filters,
            'limit' => $criteria->limit,
            'offset' => $criteria->offset,
        ]);
        
        return [
            'articles' => $results->getHits(),
            'total' => $results->getNbHits(),
        ];
    }
    
    // Implement other methods...
}
```

### Step 4: Register in Service Provider

```php
// app/Providers/ContentServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Content\Contracts\ContentRepositoryInterface;
use Nexus\Content\Contracts\ContentSearchInterface;
use App\Repositories\EloquentContentRepository;
use App\Services\MeilisearchContentSearch;

class ContentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ContentRepositoryInterface::class, EloquentContentRepository::class);
        $this->app->singleton(ContentSearchInterface::class, MeilisearchContentSearch::class);
    }
}
```

### Step 5: Usage in Controller

```php
// app/Http/Controllers/KnowledgeBaseController.php
namespace App\Http\Controllers;

use Nexus\Content\Services\ArticleManager;
use Nexus\Content\ValueObjects\SearchCriteria;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        private ArticleManager $articleManager
    ) {}
    
    public function show(string $slug)
    {
        $article = $this->articleManager->repository->findBySlug($slug);
        
        if (!$article || !$article->canBeViewedBy(auth()->user()?->party_id)) {
            abort(404);
        }
        
        return view('kb.show', ['article' => $article]);
    }
    
    public function search(Request $request)
    {
        $results = $this->articleManager->search(
            SearchCriteria::forPublic($request->input('q'))
        );
        
        return view('kb.search', ['results' => $results]);
    }
}
```

---

## Symfony Integration

### Step 1: Create Doctrine Entity

```php
// src/Entity/Article.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'articles')]
class Article
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private string $articleId;
    
    #[ORM\Column(type: 'string')]
    private string $title;
    
    #[ORM\Column(type: 'string', unique: true)]
    private string $slug;
    
    #[ORM\Column(type: 'json')]
    private array $versionHistory;
    
    // ... other properties
}
```

### Step 2: Create Repository

```php
// src/Repository/DoctrineContentRepository.php
namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Nexus\Content\Contracts\ContentRepositoryInterface;
use Nexus\Content\ValueObjects\Article;

final class DoctrineContentRepository implements ContentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}
    
    public function saveArticle(Article $article): void
    {
        // Convert value object to entity and persist
        $entity = $this->em->getRepository(\App\Entity\Article::class)
            ->find($article->articleId) ?? new \App\Entity\Article();
        
        // Map properties...
        $this->em->persist($entity);
        $this->em->flush();
    }
    
    // Implement other methods...
}
```

### Step 3: Register in services.yaml

```yaml
# config/services.yaml
services:
    Nexus\Content\Contracts\ContentRepositoryInterface:
        class: App\Repository\DoctrineContentRepository
        
    Nexus\Content\Contracts\ContentSearchInterface:
        class: App\Service\ElasticsearchContentSearch
        
    Nexus\Content\Services\ArticleManager:
        arguments:
            $repository: '@Nexus\Content\Contracts\ContentRepositoryInterface'
            $searchEngine: '@Nexus\Content\Contracts\ContentSearchInterface'
```

---

## Vanilla PHP Integration

### PDO-Based Repository

```php
// src/Infrastructure/PdoContentRepository.php
namespace App\Infrastructure;

use Nexus\Content\Contracts\ContentRepositoryInterface;
use Nexus\Content\ValueObjects\Article;

final class PdoContentRepository implements ContentRepositoryInterface
{
    public function __construct(private \PDO $pdo) {}
    
    public function saveArticle(Article $article): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO articles (article_id, title, slug, category_id, is_public, version_history, created_at, updated_at)
            VALUES (:id, :title, :slug, :category_id, :is_public, :version_history, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                version_history = VALUES(version_history),
                updated_at = VALUES(updated_at)
        ");
        
        $stmt->execute([
            'id' => $article->articleId,
            'title' => $article->title,
            'slug' => $article->slug,
            'category_id' => $article->category->categoryId,
            'is_public' => $article->isPublic ? 1 : 0,
            'version_history' => json_encode($article->versionHistory),
            'created_at' => $article->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $article->updatedAt->format('Y-m-d H:i:s'),
        ]);
    }
    
    // Implement other methods...
}
```

---

## Optional Integrations

### With Nexus\AuditLogger

```php
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;

$articleManager = new ArticleManager(
    repository: $repository,
    searchEngine: $searchEngine,
    auditLogger: $auditLogger // Optional - logs all changes
);
```

### With Nexus\Telemetry

```php
use Nexus\Telemetry\Contracts\TelemetryTrackerInterface;

$articleManager = new ArticleManager(
    repository: $repository,
    searchEngine: $searchEngine,
    telemetry: $telemetry // Optional - tracks metrics
);
```

---

## Database Schema Examples

### MySQL

```sql
CREATE TABLE articles (
    article_id VARCHAR(255) PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    slug VARCHAR(500) UNIQUE NOT NULL,
    category_id VARCHAR(255) NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    version_history JSON NOT NULL,
    translation_group_id VARCHAR(255) NULL,
    language_code VARCHAR(10) NULL,
    access_control_party_ids JSON NULL,
    edit_lock JSON NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_translation_group (translation_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

For more examples, see [examples/](examples/) directory.
