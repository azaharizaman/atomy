<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProjectManagementOperations\Contracts\BudgetQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\DTOs\ProjectDTO;
use Nexus\ProjectManagementOperations\Services\ExpenseHealthService;
use PHPUnit\Framework\TestCase;

final class ExpenseHealthServiceTest extends TestCase
{
    public function test_it_calculates_expense_health_correctly(): void
    {
        $projectId = 'proj-123';
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $projectQuery = $this->createMock(ProjectQueryInterface::class);
        $projectQuery->method('findById')->willReturn(new ProjectDTO(
            id: $projectId,
            name: 'Test Project',
            startDate: $startDate,
            endDate: $endDate,
            status: 'active'
        ));

        $budgetQuery = $this->createMock(BudgetQueryInterface::class);
        $budgetQuery->method('getExpenseBudget')->willReturn(Money::of(5000.00, 'MYR'));
        $budgetQuery->method('getActualExpenseCost')->willReturn(Money::of(1000.00, 'MYR'));

        $service = new ExpenseHealthService(
            $projectQuery,
            $budgetQuery
        );

        $health = $service->calculate($projectId);

        $this->assertEquals($projectId, $health->projectId);
        $this->assertTrue($health->budgetedExpenseCost->equals(Money::of(5000.00, 'MYR')));
        $this->assertTrue($health->actualExpenseCost->equals(Money::of(1000.00, 'MYR')));
        $this->assertEquals(20.0, $health->healthPercentage); // (1000 / 5000) * 100
    }

    public function test_it_throws_exception_if_project_is_not_found(): void
    {
        $projectQuery = $this->createMock(ProjectQueryInterface::class);
        $projectQuery->method('findById')->willReturn(null);

        $service = new ExpenseHealthService(
            $projectQuery,
            $this->createMock(BudgetQueryInterface::class)
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
        $projectQuery->method('findById')->willReturn(new ProjectDTO($projectId, 'Test', $startDate, $endDate, 'active'));

        $budgetQuery = $this->createMock(BudgetQueryInterface::class);
        $budgetQuery->method('getExpenseBudget')->willReturn(Money::zero('MYR'));
        $budgetQuery->method('getActualExpenseCost')->willReturn(Money::zero('MYR'));

        $service = new ExpenseHealthService($projectQuery, $budgetQuery);
        $health = $service->calculate($projectId);

        $this->assertEquals(0.0, $health->healthPercentage);
    }
}
