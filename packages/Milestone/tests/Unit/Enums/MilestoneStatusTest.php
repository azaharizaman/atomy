<?php

declare(strict_types=1);

namespace Nexus\Milestone\Tests\Unit\Enums;

use Nexus\Milestone\Enums\MilestoneStatus;
use PHPUnit\Framework\TestCase;

final class MilestoneStatusTest extends TestCase
{
    public function test_approved_is_billable(): void
    {
        self::assertTrue(MilestoneStatus::Approved->isBillable());
    }

    public function test_draft_is_not_billable(): void
    {
        self::assertFalse(MilestoneStatus::Draft->isBillable());
    }
}
