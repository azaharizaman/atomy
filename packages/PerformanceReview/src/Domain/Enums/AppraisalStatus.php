<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Domain\Enums;

enum AppraisalStatus: string
{
    case Draft = 'draft';
    case SelfReview = 'self_review';
    case ManagerReview = 'manager_review';
    case FinalReview = 'final_review';
    case Completed = 'completed';
    case Archived = 'archived';

    public function isCompleted(): bool
    {
        return $this === self::Completed || $this === self::Archived;
    }

    public function canEdit(): bool
    {
        return $this === self::Draft || $this === self::SelfReview || $this === self::ManagerReview;
    }
}
