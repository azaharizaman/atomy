<?php

declare(strict_types=1);

namespace Nexus\Milestone\Enums;

/**
 * Milestone lifecycle (FUN-PRO-0569). REL-PRO-0408: approval workflow resumable after failure.
 */
enum MilestoneStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Billed = 'billed';

    public function isBillable(): bool
    {
        return $this === self::Approved || $this === self::Billed;
    }
}
