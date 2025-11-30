<?php

declare(strict_types=1);

namespace Nexus\Content\Contracts;

use Nexus\Content\ValueObjects\Article;

/**
 * Repository interface for article persistence
 * 
 * Defines all persistence operations needed by the Content package.
 * Consuming applications must implement this interface.
 */
interface ContentRepositoryInterface
{
    /**
     * Save article (create or update) (L1.3)
     * 
     * @param Article $article
     * @return void
     * @throws \Nexus\Content\Exceptions\DuplicateSlugException
     */
    public function saveArticle(Article $article): void;

    /**
     * Find article by ID (L1.4)
     * 
     * @param string $articleId
     * @return Article|null
     */
    public function findById(string $articleId): ?Article;

    /**
     * Find article by slug (L2.7)
     * 
     * @param string $slug
     * @return Article|null
     */
    public function findBySlug(string $slug): ?Article;

    /**
     * Find specific content version by ID (L2.3)
     * 
     * @param string $versionId
     * @return \Nexus\Content\ValueObjects\ContentVersion|null
     */
    public function findVersionById(string $versionId): ?\Nexus\Content\ValueObjects\ContentVersion;

    /**
     * Find all translations of an article (L3.4)
     * 
     * @param string $translationGroupId
     * @return array<Article>
     */
    public function findTranslations(string $translationGroupId): array;

    /**
     * Check if slug is available (unique within tenant)
     * 
     * @param string $slug
     * @param string|null $excludeArticleId Optional article ID to exclude from check
     * @return bool
     */
    public function isSlugAvailable(string $slug, ?string $excludeArticleId = null): bool;

    /**
     * Find articles by category
     * 
     * @param string $categoryId
     * @param int $limit
     * @param int $offset
     * @return array<Article>
     */
    public function findByCategory(string $categoryId, int $limit = 20, int $offset = 0): array;

    /**
     * Find articles scheduled for auto-publish
     * 
     * @param \DateTimeImmutable $currentTime
     * @return array<Article>
     */
    public function findScheduledForPublish(\DateTimeImmutable $currentTime): array;
}
