<?php

declare(strict_types=1);

namespace Nexus\Content\Enums;

/**
 * Content lifecycle status
 * 
 * Defines the workflow states for content versions:
 * - Draft: Work in progress, editable
 * - PendingReview: Submitted for approval
 * - Published: Active, visible to users
 * - Archived: Previously published, now hidden/deprecated
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * Check if this status allows content editing
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if this status is visible to public users
     */
    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }

    /**
     * Get valid next statuses from current status
     * 
     * @return array<self>
     */
    public function getValidTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::PendingReview, self::Published],
            self::PendingReview => [self::Draft, self::Published],
            self::Published => [self::Archived],
            self::Archived => [self::Published],
        };
    }

    /**
     * Check if transition to target status is valid
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->getValidTransitions(), true);
    }
}
