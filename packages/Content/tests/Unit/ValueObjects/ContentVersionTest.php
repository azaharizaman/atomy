<?php

declare(strict_types=1);

namespace Nexus\Content\Tests\Unit\ValueObjects;

use Nexus\Content\Enums\ContentStatus;
use Nexus\Content\Exceptions\InvalidContentException;
use Nexus\Content\ValueObjects\ContentVersion;
use PHPUnit\Framework\TestCase;

final class ContentVersionTest extends TestCase
{
    public function test_create_draft_version(): void
    {
        $version = ContentVersion::createDraft(
            versionId: 'v1',
            textContent: '# Test Content',
            authorId: 'user-123'
        );

        $this->assertSame('v1', $version->versionId);
        $this->assertSame(1, $version->versionNumber);
        $this->assertSame('# Test Content', $version->textContent);
        $this->assertSame('user-123', $version->authorId);
        $this->assertSame(ContentStatus::Draft, $version->status);
        $this->assertNull($version->publishedAt);
        $this->assertNull($version->scheduledPublishAt);
    }

    public function test_create_next_version_increments_number(): void
    {
        $v1 = ContentVersion::createDraft(
            versionId: 'v1',
            textContent: '# Original',
            authorId: 'user-123'
        );

        $v2 = ContentVersion::createNext(
            versionId: 'v2',
            previousVersion: $v1,
            textContent: '# Updated',
            authorId: 'user-456'
        );

        $this->assertSame(2, $v2->versionNumber);
        $this->assertSame('# Updated', $v2->textContent);
        $this->assertSame('user-456', $v2->authorId);
        $this->assertSame(ContentStatus::Draft, $v2->status);
    }

    public function test_with_status_updates_status(): void
    {
        $draft = ContentVersion::createDraft(
            versionId: 'v1',
            textContent: '# Test',
            authorId: 'user-123'
        );

        $published = $draft->withStatus(ContentStatus::Published);

        $this->assertSame(ContentStatus::Draft, $draft->status);
        $this->assertSame(ContentStatus::Published, $published->status);
        $this->assertNotNull($published->publishedAt);
        $this->assertNull($draft->publishedAt);
    }

    public function test_scheduled_publish_is_detected(): void
    {
        $futureDate = new \DateTimeImmutable('+1 day');
        
        $version = ContentVersion::createDraft(
            versionId: 'v1',
            textContent: '# Test',
            authorId: 'user-123',
            scheduledPublishAt: $futureDate
        );

        $this->assertTrue($version->isScheduled());
        $this->assertFalse($version->shouldAutoPublish(new \DateTimeImmutable()));
        $this->assertTrue($version->shouldAutoPublish(new \DateTimeImmutable('+2 days')));
    }

    public function test_cannot_create_version_with_empty_content(): void
    {
        $this->expectException(InvalidContentException::class);
        $this->expectExceptionMessage('Content cannot be empty');

        ContentVersion::createDraft(
            versionId: 'v1',
            textContent: '   ',
            authorId: 'user-123'
        );
    }

    public function test_cannot_create_version_with_invalid_version_number(): void
    {
        $this->expectException(InvalidContentException::class);
        
        new ContentVersion(
            versionId: 'v1',
            versionNumber: 0,
            textContent: 'Test',
            status: ContentStatus::Draft,
            authorId: 'user-123',
            createdAt: new \DateTimeImmutable()
        );
    }
}
