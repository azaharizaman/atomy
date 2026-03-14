<?php

declare(strict_types=1);

namespace Nexus\Milestone\Tests\Unit\ValueObjects;

use Nexus\Milestone\Enums\MilestoneStatus;
use Nexus\Milestone\ValueObjects\MilestoneSummary;
use PHPUnit\Framework\TestCase;

final class MilestoneSummaryTest extends TestCase
{
    public function test_empty_title_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Milestone title cannot be empty');
        new MilestoneSummary('m1', 'p1', '', null, '100', 'USD', MilestoneStatus::Draft);
    }

    public function test_empty_context_id_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MilestoneSummary('m1', '', 'Title', null, '100', 'USD', MilestoneStatus::Draft);
    }
}
