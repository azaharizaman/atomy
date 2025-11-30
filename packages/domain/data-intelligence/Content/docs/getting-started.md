# Getting Started with Nexus Content

This guide will help you integrate and use the `Nexus\Content` package in your application.

## Prerequisites

- PHP 8.3 or higher
- Composer
- A framework or application to integrate with (Laravel, Symfony, or vanilla PHP)

## Installation

### Step 1: Install Package

```bash
composer require nexus/content:"*@dev"
```

### Step 2: Implement Required Interfaces

The package requires two interface implementations:

1. **ContentRepositoryInterface** - How articles are stored/retrieved
2. **ContentSearchInterface** - How articles are indexed/searched

### Step 3: Register Implementations

Bind your implementations in your framework's service container.

---

## Quick Start Example

### 1. Create Repository Implementation (Simplified)

```php
// app/Repositories/InMemoryContentRepository.php
namespace App\Repositories;

use Nexus\Content\Contracts\ContentRepositoryInterface;
use Nexus\Content\ValueObjects\Article;

final class InMemoryContentRepository implements ContentRepositoryInterface
{
    private array $articles = [];
    
    public function saveArticle(Article $article): void
    {
        $this->articles[$article->articleId] = $article;
    }
    
    public function findById(string $articleId): ?Article
    {
        return $this->articles[$articleId] ?? null;
    }
    
    public function findBySlug(string $slug): ?Article
    {
        foreach ($this->articles as $article) {
            if ($article->slug === $slug) {
                return $article;
            }
        }
        return null;
    }
    
    public function findVersionById(string $versionId): ?\Nexus\Content\ValueObjects\ContentVersion
    {
        foreach ($this->articles as $article) {
            foreach ($article->versionHistory as $version) {
                if ($version->versionId === $versionId) {
                    return $version;
                }
            }
        }
        return null;
    }
    
    public function findTranslations(string $translationGroupId): array
    {
        return array_filter(
            $this->articles,
            fn($a) => $a->translationGroupId === $translationGroupId
        );
    }
    
    public function isSlugAvailable(string $slug, ?string $excludeArticleId = null): bool
    {
        foreach ($this->articles as $article) {
            if ($article->slug === $slug && $article->articleId !== $excludeArticleId) {
                return false;
            }
        }
        return true;
    }
    
    public function findByCategory(string $categoryId, int $limit = 20, int $offset = 0): array
    {
        $filtered = array_filter(
            $this->articles,
            fn($a) => $a->category->categoryId === $categoryId
        );
        return array_slice($filtered, $offset, $limit);
    }
    
    public function findScheduledForPublish(\DateTimeImmutable $currentTime): array
    {
        $scheduled = [];
        foreach ($this->articles as $article) {
            foreach ($article->versionHistory as $version) {
                if ($version->shouldAutoPublish($currentTime)) {
                    $scheduled[] = $article;
                    break;
                }
            }
        }
        return $scheduled;
    }
}
```

### 2. Create Search Implementation (Simplified)

```php
// app/Services/InMemoryContentSearch.php
namespace App\Services;

use Nexus\Content\Contracts\ContentSearchInterface;
use Nexus\Content\ValueObjects\Article;
use Nexus\Content\ValueObjects\SearchCriteria;

final class InMemoryContentSearch implements ContentSearchInterface
{
    private array $index = [];
    
    public function indexArticle(Article $article): void
    {
        $this->index[$article->articleId] = $article;
    }
    
    public function removeArticle(string $articleId): void
    {
        unset($this->index[$articleId]);
    }
    
    public function search(SearchCriteria $criteria): array
    {
        $results = $this->index;
        
        // Filter by query
        if ($criteria->query !== null) {
            $results = array_filter($results, function(Article $article) use ($criteria) {
                $activeVersion = $article->getActiveVersion();
                return $activeVersion !== null &&
                       str_contains(strtolower($activeVersion->textContent), strtolower($criteria->query));
            });
        }
        
        // Filter by visibility
        if ($criteria->publicOnly) {
            $results = array_filter($results, fn(Article $a) => $a->isPublic);
        }
        
        // Filter by category
        if (!empty($criteria->categoryIds)) {
            $results = array_filter(
                $results,
                fn(Article $a) => in_array($a->category->categoryId, $criteria->categoryIds, true)
            );
        }
        
        // Filter by language
        if ($criteria->languageCode !== null) {
            $results = array_filter(
                $results,
                fn(Article $a) => $a->languageCode === $criteria->languageCode
            );
        }
        
        $total = count($results);
        $results = array_slice($results, $criteria->offset, $criteria->limit);
        
        return [
            'articles' => array_values($results),
            'total' => $total,
        ];
    }
    
    public function reindexAll(): int
    {
        return count($this->index);
    }
}
```

### 3. Create Your First Article

```php
use Nexus\Content\Services\ArticleManager;
use Nexus\Content\ValueObjects\ArticleCategory;
use App\Repositories\InMemoryContentRepository;
use App\Services\InMemoryContentSearch;

// Setup
$repository = new InMemoryContentRepository();
$searchEngine = new InMemoryContentSearch();
$articleManager = new ArticleManager($repository, $searchEngine);

// Create category
$category = ArticleCategory::createRoot(
    categoryId: 'getting-started',
    name: 'Getting Started',
    slug: 'getting-started',
    description: 'Beginner guides'
);

// Create article
$article = $articleManager->createArticle(
    articleId: 'article-001',
    title: 'Welcome to Our Platform',
    slug: 'welcome',
    category: $category,
    textContent: <<<MARKDOWN
# Welcome

This is your first article in the knowledge base.

## Getting Started

Follow these steps...
MARKDOWN,
    authorId: 'user-123',
    isPublic: true
);

echo "Article created: {$article->articleId}\n";
```

### 4. Publish the Article

```php
// Publish the article
$publishedArticle = $articleManager->publish('article-001');

echo "Article published! Status: {$publishedArticle->getActiveVersion()->status->value}\n";

// Article is now searchable
$searchResults = $articleManager->search(
    \Nexus\Content\ValueObjects\SearchCriteria::forPublic('welcome')
);

echo "Search found {$searchResults['total']} article(s)\n";
```

### 5. Update Content (Creates New Version)

```php
$updatedArticle = $articleManager->updateContent(
    articleId: 'article-001',
    textContent: <<<MARKDOWN
# Welcome (Updated)

This is your first article - now with updated content!

## Getting Started

Follow these steps...

## New Section

Additional information added in version 2.
MARKDOWN,
    authorId: 'user-456'
);

echo "New version created: version {$updatedArticle->getLatestVersion()->versionNumber}\n";
echo "Active version is still: version {$updatedArticle->getActiveVersion()->versionNumber}\n";

// Publish new version
$articleManager->publish('article-001');
```

---

## Next Steps

1. **Production Repository:** Replace in-memory implementation with database-backed repository (Eloquent, Doctrine, etc.)
2. **Production Search:** Integrate with Elasticsearch, Algolia, or Meilisearch
3. **Review Workflow:** Implement approval process using `submitForReview()` method
4. **Multi-Language:** Create translation groups for international content
5. **Access Control:** Integrate with `Nexus\Party` for permission-based access

---

## Common Patterns

### Pattern 1: Complete Article Lifecycle

```php
// Create draft
$article = $articleManager->createArticle(/* ... */);

// Update content
$article = $articleManager->updateContent($article->articleId, 'New content', 'user-123');

// Submit for review
$article = $articleManager->submitForReview($article->articleId);

// Approve and publish
$article = $articleManager->publish($article->articleId);

// Later... archive
$article = $articleManager->archive($article->articleId);
```

### Pattern 2: Lock for Editing

```php
// User starts editing
$article = $articleManager->lockForEditing('article-001', 'user-123', 30);

// Make changes
$article = $articleManager->updateContent('article-001', 'Updated...', 'user-123');

// Release lock
$article = $articleManager->unlockForEditing('article-001', 'user-123');
```

### Pattern 3: Scheduled Publishing

```php
$article = $articleManager->createArticle(
    articleId: 'promo-001',
    title: 'Summer Sale',
    slug: 'summer-sale',
    category: $category,
    textContent: '# Sale starts June 1st!',
    authorId: 'user-123',
    isPublic: true,
    options: [
        'scheduledPublishAt' => new \DateTimeImmutable('2025-06-01 00:00:00'),
    ]
);

// Background worker automatically publishes when time arrives
```

---

## Troubleshooting

### Issue: Duplicate Slug Error

```php
try {
    $article = $articleManager->createArticle(/* ... slug: 'duplicate' ... */);
} catch (\Nexus\Content\Exceptions\DuplicateSlugException $e) {
    echo "Slug already exists! Use a different slug.";
}
```

### Issue: Content Locked

```php
try {
    $article = $articleManager->updateContent('article-001', 'New content', 'user-456');
} catch (\Nexus\Content\Exceptions\ContentLockedException $e) {
    echo "Article is locked by another user: {$e->getMessage()}";
}
```

### Issue: Invalid Status Transition

```php
try {
    // Cannot publish archived article directly
    $articleManager->publish('archived-article-id');
} catch (\Nexus\Content\Exceptions\InvalidStatusTransitionException $e) {
    echo "Invalid status transition: {$e->getMessage()}";
}
```

---

## Full Integration Examples

See [integration-guide.md](integration-guide.md) for:
- Laravel + Eloquent + Meilisearch
- Symfony + Doctrine + Elasticsearch
- Vanilla PHP + PDO + Database full-text search

---

**Next:** Read the [API Reference](api-reference.md) for complete method documentation.
