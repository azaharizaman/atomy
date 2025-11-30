<?php

declare(strict_types=1);

namespace Nexus\Content\ValueObjects;

use Nexus\Content\Enums\ContentStatus;
use Nexus\Content\Exceptions\InvalidContentException;
use Nexus\Content\Exceptions\ContentLockedException;

/**
 * Article aggregate root value object
 * 
 * Represents a knowledge base article with versioning, categorization,
 * multi-language support, and access control.
 * 
 * @property-read string $articleId
 * @property-read string $title
 * @property-read string $slug
 * @property-read ArticleCategory $category
 * @property-read bool $isPublic
 * @property-read array<ContentVersion> $versionHistory
 * @property-read string|null $translationGroupId
 * @property-read string|null $languageCode
 * @property-read array<string> $accessControlPartyIds
 * @property-read EditLock|null $editLock
 * @property-read \DateTimeImmutable $createdAt
 * @property-read \DateTimeImmutable $updatedAt
 * @property-read array<string, mixed> $metadata
 */
final readonly class Article
{
    /**
     * @param string $articleId
     * @param string $title
     * @param string $slug
     * @param ArticleCategory $category
     * @param bool $isPublic
     * @param array<ContentVersion> $versionHistory
     * @param string|null $translationGroupId
     * @param string|null $languageCode
     * @param array<string> $accessControlPartyIds
     * @param EditLock|null $editLock
     * @param \DateTimeImmutable $createdAt
     * @param \DateTimeImmutable $updatedAt
     * @param array<string, mixed> $metadata
     */
    private function __construct(
        public string $articleId,
        public string $title,
        public string $slug,
        public ArticleCategory $category,
        public bool $isPublic,
        public array $versionHistory,
        public ?string $translationGroupId = null,
        public ?string $languageCode = null,
        public array $accessControlPartyIds = [],
        public ?EditLock $editLock = null,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $updatedAt = null,
        public array $metadata = [],
    ) {
        if (empty($this->articleId)) {
            throw new InvalidContentException('Article ID cannot be empty');
        }

        if (empty(trim($this->title))) {
            throw new InvalidContentException('Article title cannot be empty');
        }

        if (empty(trim($this->slug))) {
            throw new InvalidContentException('Article slug cannot be empty');
        }

        if (empty($this->versionHistory)) {
            throw new InvalidContentException('Article must have at least one version');
        }

        if ($this->translationGroupId !== null && empty($this->languageCode)) {
            throw new InvalidContentException('Language code required for translated articles');
        }
    }

    /**
     * Create new article with initial draft version (L1.1)
     */
    public static function create(
        string $articleId,
        string $title,
        string $slug,
        ArticleCategory $category,
        ContentVersion $initialVersion,
        bool $isPublic = false,
        ?string $translationGroupId = null,
        ?string $languageCode = null,
        array $accessControlPartyIds = [],
        array $metadata = [],
    ): self {
        if ($initialVersion->status !== ContentStatus::Draft) {
            throw new InvalidContentException('Initial version must be Draft');
        }

        $now = new \DateTimeImmutable();

        return new self(
            articleId: $articleId,
            title: $title,
            slug: $slug,
            category: $category,
            isPublic: $isPublic,
            versionHistory: [$initialVersion],
            translationGroupId: $translationGroupId,
            languageCode: $languageCode,
            accessControlPartyIds: $accessControlPartyIds,
            editLock: null,
            createdAt: $now,
            updatedAt: $now,
            metadata: $metadata,
        );
    }

    /**
     * Get the currently active (published) version
     */
    public function getActiveVersion(): ?ContentVersion
    {
        foreach ($this->versionHistory as $version) {
            if ($version->status === ContentStatus::Published) {
                return $version;
            }
        }

        return null;
    }

    /**
     * Get the latest draft version
     */
    public function getLatestDraft(): ?ContentVersion
    {
        $drafts = array_filter(
            $this->versionHistory,
            fn(ContentVersion $v) => $v->status === ContentStatus::Draft
        );

        if (empty($drafts)) {
            return null;
        }

        usort($drafts, fn($a, $b) => $b->versionNumber <=> $a->versionNumber);

        return $drafts[0];
    }

    /**
     * Get latest version (any status)
     */
    public function getLatestVersion(): ContentVersion
    {
        $versions = $this->versionHistory;
        usort($versions, fn($a, $b) => $b->versionNumber <=> $a->versionNumber);

        return $versions[0];
    }

    /**
     * Add new version to history (L2.1, L2.2)
     */
    public function withNewVersion(ContentVersion $newVersion): self
    {
        return new self(
            articleId: $this->articleId,
            title: $this->title,
            slug: $this->slug,
            category: $this->category,
            isPublic: $this->isPublic,
            versionHistory: [...$this->versionHistory, $newVersion],
            translationGroupId: $this->translationGroupId,
            languageCode: $this->languageCode,
            accessControlPartyIds: $this->accessControlPartyIds,
            editLock: $this->editLock,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            metadata: $this->metadata,
        );
    }

    /**
     * Update specific version status
     */
    public function withUpdatedVersion(ContentVersion $updatedVersion): self
    {
        $newHistory = array_map(
            fn(ContentVersion $v) => $v->versionId === $updatedVersion->versionId
                ? $updatedVersion
                : $v,
            $this->versionHistory
        );

        return new self(
            articleId: $this->articleId,
            title: $this->title,
            slug: $this->slug,
            category: $this->category,
            isPublic: $this->isPublic,
            versionHistory: $newHistory,
            translationGroupId: $this->translationGroupId,
            languageCode: $this->languageCode,
            accessControlPartyIds: $this->accessControlPartyIds,
            editLock: $this->editLock,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            metadata: $this->metadata,
        );
    }

    /**
     * Apply edit lock (L3.2)
     */
    public function withLock(EditLock $lock): self
    {
        return new self(
            articleId: $this->articleId,
            title: $this->title,
            slug: $this->slug,
            category: $this->category,
            isPublic: $this->isPublic,
            versionHistory: $this->versionHistory,
            translationGroupId: $this->translationGroupId,
            languageCode: $this->languageCode,
            accessControlPartyIds: $this->accessControlPartyIds,
            editLock: $lock,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            metadata: $this->metadata,
        );
    }

    /**
     * Remove edit lock
     */
    public function withoutLock(): self
    {
        return new self(
            articleId: $this->articleId,
            title: $this->title,
            slug: $this->slug,
            category: $this->category,
            isPublic: $this->isPublic,
            versionHistory: $this->versionHistory,
            translationGroupId: $this->translationGroupId,
            languageCode: $this->languageCode,
            accessControlPartyIds: $this->accessControlPartyIds,
            editLock: null,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            metadata: $this->metadata,
        );
    }

    /**
     * Check if article is currently locked
     */
    public function isLocked(\DateTimeImmutable $currentTime): bool
    {
        if ($this->editLock === null) {
            return false;
        }

        return !$this->editLock->isExpired($currentTime);
    }

    /**
     * Check if user can edit (has lock or no lock exists)
     */
    public function canBeEditedBy(string $userId, \DateTimeImmutable $currentTime): bool
    {
        if ($this->editLock === null) {
            return true;
        }

        if ($this->editLock->isExpired($currentTime)) {
            return true;
        }

        return $this->editLock->isOwnedBy($userId);
    }

    /**
     * Verify edit access and throw exception if locked
     */
    public function ensureEditableBy(string $userId, \DateTimeImmutable $currentTime): void
    {
        if (!$this->canBeEditedBy($userId, $currentTime)) {
            throw new ContentLockedException(
                sprintf(
                    'Article is locked by user %s until %s',
                    $this->editLock->lockedByUserId,
                    $this->editLock->expiresAt->format('Y-m-d H:i:s')
                )
            );
        }
    }

    /**
     * Check if user has view permission (L3.5)
     */
    public function canBeViewedBy(?string $partyId): bool
    {
        // Public articles can be viewed by anyone
        if ($this->isPublic) {
            return true;
        }

        // Not public, anonymous users cannot view
        if ($partyId === null) {
            return false;
        }

        // Check access control list
        return empty($this->accessControlPartyIds)
            || in_array($partyId, $this->accessControlPartyIds, true);
    }
}
