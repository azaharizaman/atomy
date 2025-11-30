<?php

declare(strict_types=1);

/**
 * Basic usage examples for Nexus\Content package
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Content\Services\ArticleManager;
use Nexus\Content\ValueObjects\ArticleCategory;
use Nexus\Content\ValueObjects\SearchCriteria;

// Note: You need to implement ContentRepositoryInterface and ContentSearchInterface
// See docs/getting-started.md for implementation examples

echo "=== Nexus Content: Basic Usage Examples ===\n\n";

// Example 1: Create a category
echo "1. Creating a category...\n";
$category = ArticleCategory::createRoot(
    categoryId: 'cat-getting-started',
    name: 'Getting Started',
    slug: 'getting-started',
    description: 'Beginner guides and tutorials'
);
echo "   ✓ Category created: {$category->name}\n\n";

// Example 2: Create an article
echo "2. Creating an article with initial draft...\n";
$article = $articleManager->createArticle(
    articleId: 'art-001',
    title: 'How to Get Started',
    slug: 'how-to-get-started',
    category: $category,
    textContent: <<<MARKDOWN
# Getting Started

Welcome to our knowledge base!

## First Steps

1. Create an account
2. Verify your email
3. Complete your profile

## Need Help?

Contact support@example.com
MARKDOWN,
    authorId: 'user-123',
    isPublic: true
);
echo "   ✓ Article created: {$article->title}\n";
echo "   ✓ Current status: {$article->getLatestVersion()->status->value}\n\n";

// Example 3: Publish the article
echo "3. Publishing the article...\n";
$publishedArticle = $articleManager->publish($article->articleId);
echo "   ✓ Article published!\n";
echo "   ✓ Active version: {$publishedArticle->getActiveVersion()->versionNumber}\n\n";

// Example 4: Update content (creates new draft)
echo "4. Updating article content...\n";
$updatedArticle = $articleManager->updateContent(
    articleId: $article->articleId,
    textContent: <<<MARKDOWN
# Getting Started (Updated)

Welcome to our knowledge base! This guide has been updated with new information.

## First Steps

1. Create an account
2. Verify your email
3. Complete your profile
4. **NEW:** Set up two-factor authentication

## Need Help?

Contact support@example.com or visit our community forum.
MARKDOWN,
    authorId: 'user-456'
);
echo "   ✓ Content updated\n";
echo "   ✓ New version created: {$updatedArticle->getLatestVersion()->versionNumber}\n";
echo "   ✓ Active version is still: {$updatedArticle->getActiveVersion()->versionNumber}\n\n";

// Example 5: Publish the updated version
echo "5. Publishing updated version...\n";
$articleManager->publish($article->articleId);
echo "   ✓ Updated version published\n\n";

// Example 6: Search for articles
echo "6. Searching for articles...\n";
$searchResults = $articleManager->search(
    SearchCriteria::forPublic('getting started')
);
echo "   ✓ Found {$searchResults['total']} article(s)\n";
foreach ($searchResults['articles'] as $result) {
    echo "   - {$result->title} ({$result->slug})\n";
}
echo "\n";

// Example 7: Get canonical URL
echo "7. Getting canonical URL...\n";
$url = $articleManager->getCanonicalUrl($article, 'https://kb.example.com');
echo "   ✓ Canonical URL: {$url}\n\n";

// Example 8: Create child category
echo "8. Creating child category...\n";
$subCategory = ArticleCategory::createChild(
    categoryId: 'cat-api-reference',
    name: 'API Reference',
    slug: 'api-reference',
    parentCategoryId: $category->categoryId,
    parentLevel: $category->level,
    description: 'API documentation and guides'
);
echo "   ✓ Child category created: {$subCategory->name} (Level {$subCategory->level})\n\n";

echo "=== Examples complete! ===\n";
