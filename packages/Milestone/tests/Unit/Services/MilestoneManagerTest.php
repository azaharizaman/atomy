<?php

declare(strict_types=1);

namespace Nexus\Milestone\Tests\Unit\Services;

use Nexus\Milestone\Contracts\BudgetReservationInterface;
use Nexus\Milestone\Contracts\MilestonePersistInterface;
use Nexus\Milestone\Contracts\MilestoneQueryInterface;
use Nexus\Milestone\Enums\MilestoneStatus;
use Nexus\Milestone\Exceptions\BudgetExceededException;
use Nexus\Milestone\Services\MilestoneManager;
use Nexus\Milestone\ValueObjects\MilestoneSummary;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class MilestoneManagerTest extends TestCase
{
    private MilestonePersistInterface&MockObject $persist;
    private MilestoneManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->persist = $this->createMock(MilestonePersistInterface::class);
        $this->manager = new MilestoneManager($this->persist);
    }

    public function test_create_persists_when_no_budget_check(): void
    {
        $m = new MilestoneSummary('m1', 'p1', 'Milestone', null, '1000', 'USD', MilestoneStatus::Draft);
        $this->persist->expects(self::once())->method('persist')->with($m);
        $this->manager->create($m);
    }

    public function test_create_throws_when_budget_reservation_denies(): void
    {
        $budget = $this->createMock(BudgetReservationInterface::class);
        $budget->method('canReserve')->with('p1', '1000', 'USD')->willReturn(false);
        $manager = new MilestoneManager($this->persist, $budget);
        $m = new MilestoneSummary('m1', 'p1', 'Milestone', null, '1000', 'USD', MilestoneStatus::Draft);
        $this->persist->expects(self::never())->method('persist');
        $this->expectException(BudgetExceededException::class);
        $manager->create($m);
    }

    public function test_create_persists_when_budget_reservation_allows(): void
    {
        $budget = $this->createMock(BudgetReservationInterface::class);
        $budget->method('canReserve')->willReturn(true);
        $manager = new MilestoneManager($this->persist, $budget);
        $m = new MilestoneSummary('m1', 'p1', 'Milestone', null, '1000', 'USD', MilestoneStatus::Draft);
        $this->persist->expects(self::once())->method('persist');
        $manager->create($m);
    }
}
