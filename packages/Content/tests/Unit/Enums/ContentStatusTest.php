<?php

declare(strict_types=1);

namespace Nexus\Content\Tests\Unit\Enums;

use Nexus\Content\Enums\ContentStatus;
use PHPUnit\Framework\TestCase;

final class ContentStatusTest extends TestCase
{
    public function test_draft_is_editable(): void
    {
        $status = ContentStatus::Draft;
        
        $this->assertTrue($status->isEditable());
        $this->assertFalse($status->isPubliclyVisible());
    }

    public function test_published_is_publicly_visible(): void
    {
        $status = ContentStatus::Published;
        
        $this->assertFalse($status->isEditable());
        $this->assertTrue($status->isPubliclyVisible());
    }

    public function test_draft_can_transition_to_pending_review(): void
    {
        $status = ContentStatus::Draft;
        
        $this->assertTrue($status->canTransitionTo(ContentStatus::PendingReview));
        $this->assertTrue($status->canTransitionTo(ContentStatus::Published));
        $this->assertFalse($status->canTransitionTo(ContentStatus::Archived));
    }

    public function test_published_can_only_transition_to_archived(): void
    {
        $status = ContentStatus::Published;
        
        $this->assertTrue($status->canTransitionTo(ContentStatus::Archived));
        $this->assertFalse($status->canTransitionTo(ContentStatus::Draft));
        $this->assertFalse($status->canTransitionTo(ContentStatus::PendingReview));
    }

    public function test_pending_review_can_transition_to_draft_or_published(): void
    {
        $status = ContentStatus::PendingReview;
        
        $this->assertTrue($status->canTransitionTo(ContentStatus::Draft));
        $this->assertTrue($status->canTransitionTo(ContentStatus::Published));
        $this->assertFalse($status->canTransitionTo(ContentStatus::Archived));
    }

    public function test_archived_can_be_republished(): void
    {
        $status = ContentStatus::Archived;
        
        $this->assertTrue($status->canTransitionTo(ContentStatus::Published));
        $this->assertFalse($status->canTransitionTo(ContentStatus::Draft));
    }
}
