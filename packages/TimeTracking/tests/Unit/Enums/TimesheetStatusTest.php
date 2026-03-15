<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\Tests\Unit\Enums;

use Nexus\TimeTracking\Enums\TimesheetStatus;
use PHPUnit\Framework\TestCase;

final class TimesheetStatusTest extends TestCase
{
    public function test_approved_is_immutable(): void
    {
        self::assertTrue(TimesheetStatus::Approved->isImmutable());
    }

    public function test_draft_can_edit(): void
    {
        self::assertTrue(TimesheetStatus::Draft->canEdit());
    }

    public function test_approved_cannot_edit(): void
    {
        self::assertFalse(TimesheetStatus::Approved->canEdit());
    }
}
