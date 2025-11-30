<?php

declare(strict_types=1);

namespace Nexus\Content\ValueObjects;

use Nexus\Content\Enums\ContentStatus;
use Nexus\Content\Exceptions\InvalidContentException;

/**
 * Immutable content version value object
 * 
 * Represents a single version of article content with its metadata.
 * Each version is immutable once created.
 * 
 * @property-read string $versionId Unique identifier for this version
 * @property-read int $versionNumber Sequential version number
 * @property-read string $textContent Raw content (Markdown, HTML, etc.)
 * @property-read ContentStatus $status Current lifecycle status
 * @property-read string $authorId User who created this version
 * @property-read \DateTimeImmutable $createdAt When this version was created
 * @property-read \DateTimeImmutable|null $publishedAt When this version was published
 * @property-read \DateTimeImmutable|null $scheduledPublishAt Scheduled publish time (L3.1)
 * @property-read array<string, mixed> $metadata Additional version metadata
 */
final readonly class ContentVersion
{
    /**
     * @param string $versionId
     * @param int $versionNumber
     * @param string $textContent
     * @param ContentStatus $status
     * @param string $authorId
     * @param \DateTimeImmutable $createdAt
     * @param \DateTimeImmutable|null $publishedAt
     * @param \DateTimeImmutable|null $scheduledPublishAt
     * @param array<string, mixed> $metadata
     */
    private function __construct(
        public string $versionId,
        public int $versionNumber,
        public string $textContent,
        public ContentStatus $status,
        public string $authorId,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $publishedAt = null,
        public ?\DateTimeImmutable $scheduledPublishAt = null,
        public array $metadata = [],
    ) {
        if (empty($this->versionId)) {
            throw new InvalidContentException('Version ID cannot be empty');
        }

        if ($this->versionNumber < 1) {
            throw new InvalidContentException('Version number must be positive');
        }

        if (empty(trim($this->textContent))) {
            throw new InvalidContentException('Content cannot be empty');
        }

        if (empty($this->authorId)) {
            throw new InvalidContentException('Author ID cannot be empty');
        }
    }

    /**
     * Create initial draft version
     */
    public static function createDraft(
        string $versionId,
        string $textContent,
        string $authorId,
        ?\DateTimeImmutable $scheduledPublishAt = null,
        array $metadata = [],
    ): self {
        return new self(
            versionId: $versionId,
            versionNumber: 1,
            textContent: $textContent,
            status: ContentStatus::Draft,
            authorId: $authorId,
            createdAt: new \DateTimeImmutable(),
            scheduledPublishAt: $scheduledPublishAt,
            metadata: $metadata,
        );
    }

    /**
     * Create next version from current
     */
    public static function createNext(
        string $versionId,
        ContentVersion $previousVersion,
        string $textContent,
        string $authorId,
        ?\DateTimeImmutable $scheduledPublishAt = null,
        array $metadata = [],
    ): self {
        return new self(
            versionId: $versionId,
            versionNumber: $previousVersion->versionNumber + 1,
            textContent: $textContent,
            status: ContentStatus::Draft,
            authorId: $authorId,
            createdAt: new \DateTimeImmutable(),
            scheduledPublishAt: $scheduledPublishAt,
            metadata: $metadata,
        );
    }

    /**
     * Create version with updated status
     */
    public function withStatus(ContentStatus $newStatus): self
    {
        $publishedAt = $newStatus === ContentStatus::Published
            ? new \DateTimeImmutable()
            : $this->publishedAt;

        return new self(
            versionId: $this->versionId,
            versionNumber: $this->versionNumber,
            textContent: $this->textContent,
            status: $newStatus,
            authorId: $this->authorId,
            createdAt: $this->createdAt,
            publishedAt: $publishedAt,
            scheduledPublishAt: $this->scheduledPublishAt,
            metadata: $this->metadata,
        );
    }

    /**
     * Check if this version is scheduled for future publish
     */
    public function isScheduled(): bool
    {
        if ($this->scheduledPublishAt === null) {
            return false;
        }

        return $this->scheduledPublishAt > new \DateTimeImmutable();
    }

    /**
     * Check if scheduled publish time has arrived
     */
    public function shouldAutoPublish(\DateTimeImmutable $currentTime): bool
    {
        if ($this->scheduledPublishAt === null || $this->status !== ContentStatus::Draft) {
            return false;
        }

        return $currentTime >= $this->scheduledPublishAt;
    }
}
