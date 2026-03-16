<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProjectManagementOperations\Contracts\AttendanceQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\BudgetQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\DTOs\ProjectDTO;
use Nexus\ProjectManagementOperations\Services\LaborHealthService;
use PHPUnit\Framework\TestCase;

final class LaborHealthServiceTest extends TestCase
{
    public function test_it_calculates_labor_health_correctly(): void
    {
        $tenantId = 'tenant-1';
        $projectId = 'proj-123';
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $projectQuery = $this->createMock(ProjectQueryInterface::class);
        $projectQuery->method('findById')->with($tenantId, $projectId)->willReturn(new ProjectDTO(
            id: $projectId,
            name: 'Test Project',
            startDate: $startDate,
            endDate: $endDate,
            status: 'active'
        ));

        $budgetQuery = $this->createMock(BudgetQueryInterface::class);
        $budgetQuery->method('getLaborBudget')->with($tenantId, $projectId)->willReturn(Money::of(1000.00, 'MYR'));
        $budgetQuery->method('getActualLaborCost')->with($tenantId, $projectId)->willReturn(Money::of(250.00, 'MYR'));

        $attendanceQuery = $this->createMock(AttendanceQueryInterface::class);
        $attendanceQuery->method('getTotalHoursByProject')->with($tenantId, $projectId, $startDate, $endDate)->willReturn(25.5);

        $service = new LaborHealthService(
            $projectQuery,
            $budgetQuery,
            $attendanceQuery
        );

        $health = $service->calculate($projectId);

        $this->assertEquals($projectId, $health->projectId);
        $this->assertEquals(25.5, $health->actualHours);
        $this->assertTrue($health->budgetedLaborCost->equals(Money::of(1000.00, 'MYR')));
        $this->assertTrue($health->actualLaborCost->equals(Money::of(250.00, 'MYR')));
        $this->assertEquals(25.0, $health->healthPercentage); // (250 / 1000) * 100
    }

    public function test_it_throws_exception_if_project_is_not_found(): void
    {
        $projectQuery = $this->createMock(ProjectQueryInterface::class);
        $projectQuery->method('findById')->with('tenant-1', 'unknown')->willReturn(null);

        $service = new LaborHealthService(
            $projectQuery,
            $this->createMock(BudgetQueryInterface::class),
            $this->createMock(AttendanceQueryInterface::class)
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project with ID unknown not found');

        $service->calculate('unknown');
    }

    public function test_it_handles_zero_budget_correctly(): void
    {
        $projectId = 'proj-123';
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $projectQuery = $this->createMock(ProjectQueryInterface::class);
        $projectQuery->method('findById')->with('tenant-1', $projectId)->willReturn(new ProjectDTO($projectId, 'Test', $startDate, $endDate, 'active'));

        $budgetQuery = $this->createMock(BudgetQueryInterface::class);
        $budgetQuery->method('getLaborBudget')->with('tenant-1', $projectId)->willReturn(Money::zero('MYR'));
        $budgetQuery->method('getActualLaborCost')->with('tenant-1', $projectId)->willReturn(Money::zero('MYR'));

        $attendanceQuery = $this->createMock(AttendanceQueryInterface::class);
        $attendanceQuery->method('getTotalHoursByProject')->with('tenant-1', $projectId, $startDate, $endDate)->willReturn(0.0);

        $service = new LaborHealthService($projectQuery, $budgetQuery, $attendanceQuery);
        $health = $service->calculate($projectId);

        $this->assertEquals(0.0, $health->healthPercentage);
    }
}
