<?php

declare(strict_types=1);

/**
 * Advanced usage examples for Nexus\Content package
 * Level 2 & 3 features: Workflow, Locking, Multi-language, Access Control
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Content\Services\ArticleManager;
use Nexus\Content\ValueObjects\ArticleCategory;
use Nexus\Content\ValueObjects\SearchCriteria;

echo "=== Nexus Content: Advanced Usage Examples ===\n\n";

// Example 1: Review Workflow
echo "1. Review Workflow (Level 2)...\n";
$article = $articleManager->createArticle(
    articleId: 'art-review-001',
    title: 'Draft Article',
    slug: 'draft-article',
    category: $category,
    textContent: '# Draft Content',
    authorId: 'user-author',
    isPublic: false
);

// Submit for review
$article = $articleManager->submitForReview($article->articleId);
echo "   ✓ Submitted for review: {$article->getLatestVersion()->status->value}\n";

// Approve and publish
$article = $articleManager->publish($article->articleId);
echo "   ✓ Approved and published\n\n";

// Example 2: Content Locking (Level 3)
echo "2. Content Locking (Level 3)...\n";
$article = $articleManager->lockForEditing(
    articleId: $article->articleId,
    userId: 'user-123',
    durationMinutes: 30
);
echo "   ✓ Article locked for editing by user-123\n";

// Try to edit as different user (will throw exception)
try {
    $articleManager->updateContent($article->articleId, 'Unauthorized edit', 'user-456');
} catch (\Nexus\Content\Exceptions\ContentLockedException $e) {
    echo "   ✗ Lock prevented unauthorized edit: {$e->getMessage()}\n";
}

// Edit as lock owner
$article = $articleManager->updateContent($article->articleId, 'Authorized edit', 'user-123');
echo "   ✓ Lock owner can edit\n";

// Release lock
$article = $articleManager->unlockForEditing($article->articleId, 'user-123');
echo "   ✓ Lock released\n\n";

// Example 3: Scheduled Publishing (Level 3)
echo "3. Scheduled Publishing (Level 3)...\n";
$futureDate = new \DateTimeImmutable('+7 days');
$scheduledArticle = $articleManager->createArticle(
    articleId: 'art-scheduled-001',
    title: 'Future Product Launch',
    slug: 'future-product-launch',
    category: $category,
    textContent: '# Product Launch Q2 2025',
    authorId: 'user-marketing',
    isPublic: true,
    options: [
        'scheduledPublishAt' => $futureDate,
    ]
);
echo "   ✓ Article scheduled for publish: {$futureDate->format('Y-m-d H:i')}\n";
echo "   ✓ Current status: {$scheduledArticle->getLatestVersion()->status->value}\n\n";

// Background worker would automatically publish using:
// $scheduledArticles = $repository->findScheduledForPublish(new \DateTimeImmutable());

// Example 4: Multi-Language Support (Level 3)
echo "4. Multi-Language Support (Level 3)...\n";

// Create English version
$englishArticle = $articleManager->createArticle(
    articleId: 'art-multilang-en',
    title: 'Getting Started',
    slug: 'getting-started-en',
    category: $category,
    textContent: '# Getting Started\n\nWelcome to our platform...',
    authorId: 'user-translator',
    isPublic: true,
    options: [
        'translationGroupId' => 'grp-getting-started',
        'languageCode' => 'en-US',
    ]
);
echo "   ✓ Created English version: {$englishArticle->languageCode}\n";

// Create French translation
$frenchArticle = $articleManager->createArticle(
    articleId: 'art-multilang-fr',
    title: 'Commencer',
    slug: 'commencer-fr',
    category: $category,
    textContent: '# Commencer\n\nBienvenue sur notre plateforme...',
    authorId: 'user-translator',
    isPublic: true,
    options: [
        'translationGroupId' => 'grp-getting-started',
        'languageCode' => 'fr-FR',
    ]
);
echo "   ✓ Created French version: {$frenchArticle->languageCode}\n";

// Get all translations
$translations = $articleManager->getTranslations($englishArticle->articleId);
echo "   ✓ Found " . count($translations) . " translation(s)\n\n";

// Example 5: Access Control (Level 3)
echo "5. Access Control (Level 3)...\n";
$restrictedArticle = $articleManager->createArticle(
    articleId: 'art-internal-001',
    title: 'Internal Sales Playbook',
    slug: 'internal-sales-playbook',
    category: $category,
    textContent: '# Sales Team Only\n\nConfidential pricing strategies...',
    authorId: 'user-sales-manager',
    isPublic: false,
    options: [
        'accessControlPartyIds' => ['party-sales-team', 'party-executives'],
    ]
);
echo "   ✓ Created restricted article\n";

// Check permissions
$canSalesView = $restrictedArticle->canBeViewedBy('party-sales-team');
$canPublicView = $restrictedArticle->canBeViewedBy('party-customers');
echo "   ✓ Sales team can view: " . ($canSalesView ? 'Yes' : 'No') . "\n";
echo "   ✓ Customers can view: " . ($canPublicView ? 'Yes' : 'No') . "\n\n";

// Example 6: Faceted Search (Level 3)
echo "6. Faceted Search (Level 3)...\n";
$criteria = new SearchCriteria(
    query: 'getting started',
    categoryIds: [$category->categoryId],
    languageCode: 'en-US',
    publicOnly: true,
    limit: 10
);
$results = $articleManager->search($criteria);
echo "   ✓ Faceted search results: {$results['total']} article(s)\n\n";

// Example 7: Version Comparison (Level 3)
echo "7. Version Comparison (Level 3)...\n";
$v1 = $article->versionHistory[0];
$v2 = $article->versionHistory[1] ?? $v1;

if (count($article->versionHistory) > 1) {
    $diff = $articleManager->compareVersions(
        articleId: $article->articleId,
        versionId1: $v1->versionId,
        versionId2: $v2->versionId
    );
    echo "   ✓ Version diff:\n";
    echo "     - Added lines: " . count($diff['added']) . "\n";
    echo "     - Removed lines: " . count($diff['removed']) . "\n";
    echo "     - Unchanged lines: " . count($diff['unchanged']) . "\n";
} else {
    echo "   ℹ Only one version exists, skipping diff\n";
}
echo "\n";

// Example 8: Archive Article
echo "8. Archive Article...\n";
$archivedArticle = $articleManager->archive($article->articleId);
echo "   ✓ Article archived: {$archivedArticle->getLatestVersion()->status->value}\n";
echo "   ✓ Removed from search index\n\n";

echo "=== Advanced examples complete! ===\n";
