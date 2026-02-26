# Nexus Content

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

**Framework-agnostic knowledge base and content management package with versioning, workflow, and multi-language support.**

## Overview

`Nexus\Content` provides a comprehensive, stateless content management engine for building knowledge bases, documentation systems, help centers, and internal wikis. The package implements progressive disclosure across three levels:

- **Level 1 (MVP)**: Basic article creation, publishing, and search integration
- **Level 2 (Professional)**: Version control, review workflow, hierarchical categories
- **Level 3 (Enterprise)**: Scheduled publishing, content locking, multi-language support, access control

## Key Features

✨ **Comprehensive Version Control**
- Immutable version history with full audit trail
- Draft → Pending Review → Published → Archived workflow
- Compare any two versions with built-in diff generator

🌍 **Multi-Language Support**
- Translation groups linking related articles
- Language-specific content with fallback support
- Search filtering by language

🔒 **Enterprise-Grade Access Control**
- Public/private visibility toggle
- Party-based access control lists (integration with `Nexus\Party`)
- Content locking prevents simultaneous editing

📅 **Scheduled Publishing**
- Set future publish dates for content versions
- Automatic publishing via background workers (application layer)

🔍 **Powerful Search Integration**
- Framework-agnostic search interface
- Faceted search (category, language, permissions)
- Respects visibility and access control

📊 **Hierarchical Organization**
- Categories with up to 3 levels of nesting
- Logical content organization
- Category-based filtering

## Installation

```bash
composer require nexus/content:"*@dev"
```

## Quick Start

### 1. Create Your First Article

```php
use Nexus\Content\Services\ArticleManager;
use Nexus\Content\ValueObjects\ArticleCategory;

// Inject dependencies (see Integration Guide)
$articleManager = new ArticleManager($repository, $searchEngine);

// Create category
$category = ArticleCategory::createRoot(
    categoryId: 'cat-001',
    name: 'Getting Started',
    slug: 'getting-started',
    description: 'Beginner guides and tutorials'
);

// Create article with initial draft
$article = $articleManager->createArticle(
    articleId: 'art-001',
    title: 'How to Get Started',
    slug: 'how-to-get-started',
    category: $category,
    textContent: '# Getting Started\n\nWelcome to our platform...',
    authorId: 'user-123',
    isPublic: true
);
```

### 2. Publish the Article

```php
// Publish latest draft
$publishedArticle = $articleManager->publish('art-001');

// Article is now searchable via ContentSearchInterface
```

### 3. Update Content (Creates New Version)

```php
$updatedArticle = $articleManager->updateContent(
    articleId: 'art-001',
    textContent: '# Getting Started\n\nUpdated content...',
    authorId: 'user-123'
);

// New draft version created, previous version preserved in history
```

### 4. Review Workflow

```php
// Submit for review
$article = $articleManager->submitForReview('art-001');

// Approve and publish
$article = $articleManager->publish('art-001');
```

## Core Concepts

### Articles

Articles are the primary content containers. Each article:
- Has a unique slug for permanent URLs
- Belongs to a single category
- Contains multiple content versions (history)
- Can be public or restricted by access control
- Can be part of a translation group

### Content Versions

Every change creates a new immutable version:
- Sequential version numbers (1, 2, 3...)
- Tracks author and creation time
- Has a lifecycle status (Draft → Pending Review → Published → Archived)
- Only Draft versions can be edited
- Only one Published version active at a time

### Categories

Hierarchical organization up to 3 levels:
```
Level 1: Product Documentation
  Level 2: API Reference
    Level 3: Authentication
```

### Status Lifecycle

```
Draft → PendingReview → Published → Archived
  ↑           ↓              ↓
  └───────────┘──────────────┘
```

## Usage Examples

### Level 1: Basic Operations

See [docs/examples/basic-usage.php](docs/examples/basic-usage.php) for complete examples.

```php
// Create and publish in one flow
$article = $articleManager->createArticle(/* ... */);
$publishedArticle = $articleManager->publish($article->articleId);

// Get canonical URL
$url = $articleManager->getCanonicalUrl($article, 'https://kb.example.com');
// Returns: https://kb.example.com/kb/how-to-get-started
```

### Level 2: Version Control & Workflow

```php
// Create child category
$subCategory = ArticleCategory::createChild(
    categoryId: 'cat-002',
    name: 'API Reference',
    slug: 'api-reference',
    parentCategoryId: 'cat-001',
    parentLevel: 1
);

// Submit draft for review
$article = $articleManager->submitForReview('art-001');

// Compare versions
$diff = $articleManager->compareVersions(
    articleId: 'art-001',
    versionId1: 'v1',
    versionId2: 'v2'
);
// Returns: ['added' => [...], 'removed' => [...], 'unchanged' => [...]]
```

### Level 3: Enterprise Features

```php
// Schedule future publish
$article = $articleManager->createArticle(
    articleId: 'art-002',
    title: 'Product Launch',
    slug: 'product-launch',
    category: $category,
    textContent: '# New Product\n\nAvailable Q2 2025...',
    authorId: 'user-123',
    isPublic: true,
    options: [
        'scheduledPublishAt' => new \DateTimeImmutable('2025-06-01 09:00:00'),
    ]
);

// Lock for editing
$lockedArticle = $articleManager->lockForEditing(
    articleId: 'art-001',
    userId: 'user-123',
    durationMinutes: 30
);

// Create translation
$frenchArticle = $articleManager->createArticle(
    articleId: 'art-001-fr',
    title: 'Comment Commencer',
    slug: 'comment-commencer',
    category: $category,
    textContent: '# Comment Commencer...',
    authorId: 'user-123',
    isPublic: true,
    options: [
        'translationGroupId' => 'grp-001',
        'languageCode' => 'fr-FR',
    ]
);

// Access control
$restrictedArticle = $articleManager->createArticle(
    articleId: 'art-003',
    title: 'Internal Sales Guide',
    slug: 'internal-sales-guide',
    category: $category,
    textContent: '# Sales Team Only...',
    authorId: 'user-123',
    isPublic: false,
    options: [
        'accessControlPartyIds' => ['party-sales-team', 'party-management'],
    ]
);

// Faceted search
use Nexus\Content\ValueObjects\SearchCriteria;

$results = $articleManager->search(
    SearchCriteria::forParty('party-sales-team', 'pricing')
);
```

## Available Interfaces

### `ContentRepositoryInterface`

Repository for article persistence. Must be implemented by consuming application.

**Key Methods:**
- `saveArticle(Article $article): void` - Persist article
- `findById(string $articleId): ?Article` - Retrieve by ID
- `findBySlug(string $slug): ?Article` - Retrieve by slug
- `findVersionById(string $versionId): ?ContentVersion` - Get specific version
- `findTranslations(string $groupId): array` - Get all translations
- `isSlugAvailable(string $slug): bool` - Check slug uniqueness

### `ContentSearchInterface`

Search engine integration. Implement with Elasticsearch, Algolia, Meilisearch, etc.

**Key Methods:**
- `indexArticle(Article $article): void` - Add/update search index
- `removeArticle(string $articleId): void` - Remove from index
- `search(SearchCriteria $criteria): array` - Faceted search
- `reindexAll(): int` - Batch reindexing

### `ArticleManager`

Main service for article operations.

**Key Methods:**
- `createArticle(...)` - Create new article
- `updateContent(...)` - Update (creates new version)
- `publish(...)` - Publish draft
- `submitForReview(...)` - Request approval
- `archive(...)` - Archive published article
- `lockForEditing(...)` - Prevent concurrent edits
- `compareVersions(...)` - Generate diff
- `search(...)` - Search articles
- `getTranslations(...)` - Get language versions

## Application Layer Integration

### Laravel Example

```php
// app/Repositories/EloquentContentRepository.php
namespace App\Repositories;

use Nexus\Content\Contracts\ContentRepositoryInterface;
use Nexus\Content\ValueObjects\Article;
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
                'version_history' => json_encode($article->versionHistory),
                // ... other fields
            ]
        );
    }
    
    public function findById(string $articleId): ?Article
    {
        $model = ArticleModel::where('article_id', $articleId)->first();
        
        if (!$model) {
            return null;
        }
        
        // Reconstruct Article value object from model
        return $this->hydrateArticle($model);
    }
    
    // ... implement other methods
}

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

See [docs/integration-guide.md](docs/integration-guide.md) for complete examples.

## Configuration

The package is stateless and requires no configuration files. All behavior is controlled via:

1. **Repository Implementation** - How articles are stored
2. **Search Implementation** - Which search engine to use
3. **Optional Integrations** - Audit logging, metrics, etc.

## Testing

```bash
# Run package tests
cd packages/Content
composer test

# With coverage
composer test:coverage
```

## Progressive Disclosure

The package implements three levels of functionality:

| Level | Features | Use Case |
|-------|----------|----------|
| **L1: MVP** | Create, publish, search | Simple knowledge base |
| **L2: Professional** | Version control, review workflow, categories | Content team collaboration |
| **L3: Enterprise** | Scheduled publish, locking, multi-language, ACL | Large organizations |

You can adopt features progressively as your needs grow.

## Package Architecture

### Value Objects (Immutable)
- `Article` - Aggregate root
- `ContentVersion` - Immutable version
- `ArticleCategory` - Hierarchical category
- `EditLock` - Concurrent editing prevention
- `SearchCriteria` - Faceted search parameters

### Services
- `ArticleManager` - Main business logic orchestrator

### Contracts (Interfaces)
- `ContentRepositoryInterface` - Persistence abstraction
- `ContentSearchInterface` - Search engine abstraction

### Enums
- `ContentStatus` - Lifecycle states with transition validation

### Exceptions
- `ArticleNotFoundException`
- `InvalidStatusTransitionException`
- `ContentLockedException`
- `DuplicateSlugException`
- `InvalidContentException`
- `InvalidCategoryException`

## Integration with Other Nexus Packages

- **`Nexus\Party`** - Access control via Party IDs (L3.5)
- **`Nexus\AuditLogger`** - Audit trail for all changes (optional)
- **`Nexus\Telemetry`** - Metrics tracking (optional)
- **`Nexus\Tenant`** - Multi-tenant scoping (via repository)

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Documentation

- [Getting Started Guide](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Integration Guide](docs/integration-guide.md)
- [Basic Usage Examples](docs/examples/basic-usage.php)
- [Advanced Usage Examples](docs/examples/advanced-usage.php)

## Requirements

- PHP 8.3+
- No external dependencies (pure PHP package)

---

**Package Status:** ✅ Production Ready  
**Version:** 1.0.0  
**Last Updated:** 2025-11-24
