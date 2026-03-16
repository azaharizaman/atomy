<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Tests\Unit;

use Nexus\ProjectManagementOperations\ProjectManagementOperationsCoordinator;
use Nexus\ProjectManagementOperations\Contracts\ExpenseHealthServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\LaborHealthServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\MilestoneBillingServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\TimelineDriftServiceInterface;
use Nexus\ProjectManagementOperations\DTOs\LaborHealthDTO;
use Nexus\ProjectManagementOperations\DTOs\ExpenseHealthDTO;
use Nexus\ProjectManagementOperations\DTOs\TimelineHealthDTO;
use Nexus\Common\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class ProjectManagementOperationsCoordinatorTest extends TestCase
{
    public function test_it_executes_full_health_check_correctly(): void
    {
        $projectId = 'proj-123';

        $laborService = $this->createMock(LaborHealthServiceInterface::class);
        $laborService->method('calculate')->willReturn(new LaborHealthDTO(
            $projectId, 10.0, Money::of(100, 'MYR'), Money::of(50, 'MYR'), 50.0
        ));

        $expenseService = $this->createMock(ExpenseHealthServiceInterface::class);
        $expenseService->method('calculate')->willReturn(new ExpenseHealthDTO(
            $projectId, Money::of(200, 'MYR'), Money::of(100, 'MYR'), 50.0
        ));

        $timelineService = $this->createMock(TimelineDriftServiceInterface::class);
        $timelineService->method('calculate')->willReturn(new TimelineHealthDTO(
            $projectId, 10, 5, 0, 50.0, []
        ));

        $billingService = $this->createMock(MilestoneBillingServiceInterface::class);

        $coordinator = new ProjectManagementOperationsCoordinator(
            $laborService,
            $expenseService,
            $timelineService,
            $billingService
        );

        $health = $coordinator->getFullHealth($projectId);

        $this->assertEquals(50.0, $health->overallScore);
        $this->assertEquals(50.0, $health->laborHealth->healthPercentage);
        $this->assertEquals(50.0, $health->expenseHealth->healthPercentage);
        $this->assertEquals(50.0, $health->timelineHealth->completionPercentage);
    }

    public function test_it_correctly_delegates_to_all_services(): void
    {
        $projectId = 'p1';

        $labor = $this->createMock(LaborHealthServiceInterface::class);
        $labor->expects($this->once())->method('calculate')->with($projectId)->willReturn(
            new LaborHealthDTO($projectId, 0, Money::zero('MYR'), Money::zero('MYR'), 0)
        );

        $expense = $this->createMock(ExpenseHealthServiceInterface::class);
        $expense->expects($this->once())->method('calculate')->with($projectId)->willReturn(
            new ExpenseHealthDTO($projectId, Money::zero('MYR'), Money::zero('MYR'), 0)
        );

        $timeline = $this->createMock(TimelineDriftServiceInterface::class);
        $timeline->expects($this->once())->method('calculate')->with($projectId)->willReturn(
            new TimelineHealthDTO($projectId, 0, 0, 0, 0, [])
        );

        $coordinator = new ProjectManagementOperationsCoordinator(
            $labor, $expense, $timeline, $this->createMock(MilestoneBillingServiceInterface::class)
        );

        $coordinator->getFullHealth($projectId);
    }
}
