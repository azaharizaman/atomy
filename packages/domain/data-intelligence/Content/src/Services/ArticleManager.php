<?php

declare(strict_types=1);

namespace Nexus\Content\Services;

use Nexus\Content\Contracts\ContentRepositoryInterface;
use Nexus\Content\Contracts\ContentSearchInterface;
use Nexus\Content\Enums\ContentStatus;
use Nexus\Content\Exceptions\ArticleNotFoundException;
use Nexus\Content\Exceptions\InvalidStatusTransitionException;
use Nexus\Content\Exceptions\InvalidContentException;
use Nexus\Content\Exceptions\DuplicateSlugException;
use Nexus\Content\ValueObjects\Article;
use Nexus\Content\ValueObjects\ArticleCategory;
use Nexus\Content\ValueObjects\ContentVersion;
use Nexus\Content\ValueObjects\EditLock;
use Nexus\Content\ValueObjects\SearchCriteria;

/**
 * Article management service
 * 
 * Provides high-level business logic for content management.
 */
final readonly class ArticleManager
{
    /**
     * @param ContentRepositoryInterface $repository
     * @param ContentSearchInterface $searchEngine
     * @param ClockInterface|null $clock Optional clock for testing
     * @param AuditLoggerInterface|null $auditLogger Optional audit logging
     * @param TelemetryTrackerInterface|null $telemetry Optional metrics tracking
     */
    public function __construct(
        private ContentRepositoryInterface $repository,
        private ContentSearchInterface $searchEngine,
        private ?ClockInterface $clock = null,
        private ?AuditLoggerInterface $auditLogger = null,
        private ?TelemetryTrackerInterface $telemetry = null,
    ) {
    }

    /**
     * Create new article with initial draft (L1.1)
     * 
     * @param string $articleId
     * @param string $title
     * @param string $slug
     * @param ArticleCategory $category
     * @param string $textContent
     * @param string $authorId
     * @param bool $isPublic
     * @param array<string, mixed> $options
     * @return Article
     * @throws DuplicateSlugException
     */
    public function createArticle(
        string $articleId,
        string $title,
        string $slug,
        ArticleCategory $category,
        string $textContent,
        string $authorId,
        bool $isPublic = false,
        array $options = [],
    ): Article {
        // Verify slug is unique
        if (!$this->repository->isSlugAvailable($slug)) {
            throw DuplicateSlugException::forSlug($slug);
        }

        // Create initial draft version
        $initialVersion = ContentVersion::createDraft(
            versionId: $this->generateVersionId(),
            textContent: $textContent,
            authorId: $authorId,
            scheduledPublishAt: $options['scheduledPublishAt'] ?? null,
            metadata: $options['versionMetadata'] ?? [],
        );

        // Create article
        $article = Article::create(
            articleId: $articleId,
            title: $title,
            slug: $slug,
            category: $category,
            initialVersion: $initialVersion,
            isPublic: $isPublic,
            translationGroupId: $options['translationGroupId'] ?? null,
            languageCode: $options['languageCode'] ?? null,
            accessControlPartyIds: $options['accessControlPartyIds'] ?? [],
            metadata: $options['metadata'] ?? [],
        );

        $this->repository->saveArticle($article);

        $this->auditLogger?->log(
            entityId: $articleId,
            action: 'article_created',
            description: "Article '{$title}' created",
            metadata: ['slug' => $slug, 'author_id' => $authorId]
        );

        $this->telemetry?->increment('content.articles.created');

        return $article;
    }

    /**
     * Update article content (creates new draft version) (L2.1)
     * 
     * @param string $articleId
     * @param string $textContent
     * @param string $authorId
     * @param array<string, mixed> $options
     * @return Article
     * @throws ArticleNotFoundException
     */
    public function updateContent(
        string $articleId,
        string $textContent,
        string $authorId,
        array $options = [],
    ): Article {
        $article = $this->repository->findById($articleId);
        if ($article === null) {
            throw ArticleNotFoundException::forId($articleId);
        }

        // Verify user can edit
        $currentTime = $this->getCurrentTime();
        $article->ensureEditableBy($authorId, $currentTime);

        $latestVersion = $article->getLatestVersion();

        // Create new draft version
        $newVersion = ContentVersion::createNext(
            versionId: $this->generateVersionId(),
            previousVersion: $latestVersion,
            textContent: $textContent,
            authorId: $authorId,
            scheduledPublishAt: $options['scheduledPublishAt'] ?? null,
            metadata: $options['metadata'] ?? [],
        );

        $updatedArticle = $article->withNewVersion($newVersion);
        $this->repository->saveArticle($updatedArticle);

        $this->auditLogger?->log(
            entityId: $articleId,
            action: 'content_updated',
            description: "Content updated (version {$newVersion->versionNumber})",
            metadata: ['version_id' => $newVersion->versionId, 'author_id' => $authorId]
        );

        $this->telemetry?->increment('content.versions.created');

        return $updatedArticle;
    }

    /**
     * Publish draft version (L1.5, L1.6)
     * 
     * @param string $articleId
     * @param string|null $versionId If null, publishes latest draft
     * @return Article
     * @throws ArticleNotFoundException
     * @throws InvalidStatusTransitionException
     */
    public function publish(string $articleId, ?string $versionId = null): Article
    {
        $article = $this->repository->findById($articleId);
        if ($article === null) {
            throw ArticleNotFoundException::forId($articleId);
        }

        // Find version to publish
        if ($versionId === null) {
            $versionToPublish = $article->getLatestDraft();
            if ($versionToPublish === null) {
                throw new InvalidContentException('No draft version available to publish');
            }
        } else {
            $versionToPublish = $this->findVersionInArticle($article, $versionId);
        }

        // Validate status transition
        if (!$versionToPublish->status->canTransitionTo(ContentStatus::Published)) {
            throw InvalidStatusTransitionException::fromTo(
                $versionToPublish->status,
                ContentStatus::Published
            );
        }

        // Archive current published version if exists
        $currentPublished = $article->getActiveVersion();
        if ($currentPublished !== null) {
            $archivedVersion = $currentPublished->withStatus(ContentStatus::Archived);
            $article = $article->withUpdatedVersion($archivedVersion);
        }

        // Publish the new version
        $publishedVersion = $versionToPublish->withStatus(ContentStatus::Published);
        $article = $article->withUpdatedVersion($publishedVersion);

        $this->repository->saveArticle($article);

        // Index for search (L1.6)
        $this->searchEngine->indexArticle($article);

        $this->auditLogger?->log(
            entityId: $articleId,
            action: 'article_published',
            description: "Article published (version {$publishedVersion->versionNumber})",
            metadata: ['version_id' => $publishedVersion->versionId]
        );

        $this->telemetry?->increment('content.articles.published');

        return $article;
    }

    /**
     * Submit draft for review (L2.4)
     * 
     * @param string $articleId
     * @param string|null $versionId
     * @return Article
     * @throws ArticleNotFoundException
     */
    public function submitForReview(string $articleId, ?string $versionId = null): Article
    {
        $article = $this->repository->findById($articleId);
        if ($article === null) {
            throw ArticleNotFoundException::forId($articleId);
        }

        if ($versionId === null) {
            $version = $article->getLatestDraft();
            if ($version === null) {
                throw new InvalidContentException('No draft version available');
            }
        } else {
            $version = $this->findVersionInArticle($article, $versionId);
        }

        if (!$version->status->canTransitionTo(ContentStatus::PendingReview)) {
            throw InvalidStatusTransitionException::fromTo(
                $version->status,
                ContentStatus::PendingReview
            );
        }

        $reviewVersion = $version->withStatus(ContentStatus::PendingReview);
        $article = $article->withUpdatedVersion($reviewVersion);

        $this->repository->saveArticle($article);

        $this->auditLogger?->log(
            entityId: $articleId,
            action: 'submitted_for_review',
            description: "Article submitted for review (version {$reviewVersion->versionNumber})",
            metadata: ['version_id' => $reviewVersion->versionId]
        );

        return $article;
    }

    /**
     * Archive published article
     * 
     * @param string $articleId
     * @return Article
     * @throws ArticleNotFoundException
     */
    public function archive(string $articleId): Article
    {
        $article = $this->repository->findById($articleId);
        if ($article === null) {
            throw ArticleNotFoundException::forId($articleId);
        }

        $activeVersion = $article->getActiveVersion();
        if ($activeVersion === null) {
            throw new InvalidContentException('No published version to archive');
        }

        $archivedVersion = $activeVersion->withStatus(ContentStatus::Archived);
        $article = $article->withUpdatedVersion($archivedVersion);

        $this->repository->saveArticle($article);
        $this->searchEngine->removeArticle($articleId);

        $this->auditLogger?->log(
            entityId: $articleId,
            action: 'article_archived',
            description: 'Article archived',
            metadata: ['version_id' => $archivedVersion->versionId]
        );

        return $article;
    }

    /**
     * Lock article for editing (L3.2)
     * 
     * @param string $articleId
     * @param string $userId
     * @param int $durationMinutes
     * @return Article
     * @throws ArticleNotFoundException
     */
    public function lockForEditing(
        string $articleId,
        string $userId,
        int $durationMinutes = 30
    ): Article {
        $article = $this->repository->findById($articleId);
        if ($article === null) {
            throw ArticleNotFoundException::forId($articleId);
        }

        $currentTime = $this->getCurrentTime();
        $article->ensureEditableBy($userId, $currentTime);

        $lock = EditLock::create($userId, $durationMinutes);
        $article = $article->withLock($lock);

        $this->repository->saveArticle($article);

        return $article;
    }

    /**
     * Release edit lock
     * 
     * @param string $articleId
     * @param string $userId
     * @return Article
     * @throws ArticleNotFoundException
     */
    public function unlockForEditing(string $articleId, string $userId): Article
    {
        $article = $this->repository->findById($articleId);
        if ($article === null) {
            throw ArticleNotFoundException::forId($articleId);
        }

        $currentTime = $this->getCurrentTime();
        
        // Only lock owner or expired lock can be removed
        if ($article->editLock !== null) {
            if (!$article->editLock->isOwnedBy($userId) && !$article->editLock->isExpired($currentTime)) {
                throw new InvalidContentException('Cannot unlock article owned by another user');
            }
        }

        $article = $article->withoutLock();
        $this->repository->saveArticle($article);

        return $article;
    }

    /**
     * Get canonical URL for article (L2.7)
     * 
     * @param Article $article
     * @param string $baseUrl
     * @return string
     */
    public function getCanonicalUrl(Article $article, string $baseUrl = ''): string
    {
        return rtrim($baseUrl, '/') . '/kb/' . $article->slug;
    }

    /**
     * Compare two versions and generate diff (L3.7)
     * 
     * @param string $articleId
     * @param string $versionId1
     * @param string $versionId2
     * @return array{added: array<string>, removed: array<string>, unchanged: array<string>}
     * @throws ArticleNotFoundException
     */
    public function compareVersions(
        string $articleId,
        string $versionId1,
        string $versionId2
    ): array {
        $article = $this->repository->findById($articleId);
        if ($article === null) {
            throw ArticleNotFoundException::forId($articleId);
        }

        $version1 = $this->findVersionInArticle($article, $versionId1);
        $version2 = $this->findVersionInArticle($article, $versionId2);

        return $this->generateDiff($version1->textContent, $version2->textContent);
    }

    /**
     * Search articles
     * 
     * @param SearchCriteria $criteria
     * @return array{articles: array<Article>, total: int}
     */
    public function search(SearchCriteria $criteria): array
    {
        return $this->searchEngine->search($criteria);
    }

    /**
     * Get translations of an article (L3.4)
     * 
     * @param string $articleId
     * @return array<Article>
     * @throws ArticleNotFoundException
     */
    public function getTranslations(string $articleId): array
    {
        $article = $this->repository->findById($articleId);
        if ($article === null) {
            throw ArticleNotFoundException::forId($articleId);
        }

        if ($article->translationGroupId === null) {
            return [];
        }

        $translations = $this->repository->findTranslations($article->translationGroupId);

        // Exclude the current article
        return array_filter(
            $translations,
            fn(Article $a) => $a->articleId !== $articleId
        );
    }

    /**
     * Find version in article by ID
     * 
     * @param Article $article
     * @param string $versionId
     * @return ContentVersion
     * @throws InvalidContentException
     */
    private function findVersionInArticle(Article $article, string $versionId): ContentVersion
    {
        foreach ($article->versionHistory as $version) {
            if ($version->versionId === $versionId) {
                return $version;
            }
        }

        throw new InvalidContentException("Version {$versionId} not found in article");
    }

    /**
     * Generate simple line-based diff
     * 
     * @param string $text1
     * @param string $text2
     * @return array{added: array<string>, removed: array<string>, unchanged: array<string>}
     */
    private function generateDiff(string $text1, string $text2): array
    {
        $lines1 = explode("\n", $text1);
        $lines2 = explode("\n", $text2);

        $added = array_diff($lines2, $lines1);
        $removed = array_diff($lines1, $lines2);
        $unchanged = array_intersect($lines1, $lines2);

        return [
            'added' => array_values($added),
            'removed' => array_values($removed),
            'unchanged' => array_values($unchanged),
        ];
    }

    /**
     * Generate unique version ID
     */
    private function generateVersionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Get current time (for testing support)
     */
    private function getCurrentTime(): \DateTimeImmutable
    {
        if ($this->clock !== null) {
            return $this->clock->getCurrentTime();
        }

        return new \DateTimeImmutable();
    }
}

/**
 * Clock interface for time abstraction
 */
interface ClockInterface
{
    public function getCurrentTime(): \DateTimeImmutable;
}

/**
 * Audit logger interface for tracking changes
 */
interface AuditLoggerInterface
{
    public function log(string $entityId, string $action, string $description, array $metadata = []): void;
}

/**
 * Telemetry tracker interface for metrics
 */
interface TelemetryTrackerInterface
{
    public function increment(string $metric, array $tags = []): void;
}
