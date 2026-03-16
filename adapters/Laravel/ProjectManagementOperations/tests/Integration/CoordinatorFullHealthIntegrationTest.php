<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Integration;

use Nexus\Common\ValueObjects\Money;
use Nexus\Laravel\ProjectManagementOperations\Adapters\AttendanceQueryAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\BudgetPersistAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\BudgetQueryAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\MessagingServiceAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\ProjectQueryAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\ReceivablePersistAdapter;
use Nexus\Laravel\ProjectManagementOperations\Adapters\SchedulerQueryAdapter;
use Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory\InMemoryMilestoneQuery;
use Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory\InMemoryProjectBudgetPersist;
use Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory\InMemoryProjectBudgetQuery;
use Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory\InMemoryProjectMessagingSender;
use Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory\InMemoryProjectQuery;
use Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory\InMemoryProjectTaskIdsQuery;
use Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory\InMemoryTimesheetQuery;
use Nexus\Milestone\Enums\MilestoneStatus;
use Nexus\Milestone\ValueObjects\MilestoneSummary;
use Nexus\Project\Enums\ProjectStatus;
use Nexus\Project\ValueObjects\ProjectSummary;
use Nexus\ProjectManagementOperations\Contracts\MilestoneBillingServiceInterface;
use Nexus\ProjectManagementOperations\ProjectManagementOperationsCoordinator;
use Nexus\ProjectManagementOperations\Services\ExpenseHealthService;
use Nexus\ProjectManagementOperations\Services\LaborHealthService;
use Nexus\ProjectManagementOperations\Services\MilestoneBillingService;
use Nexus\ProjectManagementOperations\Services\TimelineDriftService;
use Nexus\Receivable\Contracts\CustomerInvoiceInterface;
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Nexus\TimeTracking\Enums\TimesheetStatus;
use Nexus\TimeTracking\ValueObjects\TimesheetSummary;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end integration test: wire coordinator to adapters backed by in-memory L1 and app contracts,
 * then call getFullHealth and assert on labor, expense, and timeline health.
 */
final class CoordinatorFullHealthIntegrationTest extends TestCase
{
    private const TENANT_ID = 'tenant-e2e-1';
    private const PROJECT_ID = 'proj-e2e-1';

    public function test_get_full_health_returns_aggregated_health_from_adapters(): void
    {
        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-12-31');

        // L1 in-memory
        $l1ProjectQuery = new InMemoryProjectQuery();
        $l1ProjectQuery->add(new ProjectSummary(
            id: self::PROJECT_ID,
            name: 'E2E Project',
            clientId: 'client-1',
            startDate: $start,
            endDate: $end,
            projectManagerId: 'pm-1',
            status: ProjectStatus::Active
        ));

        $l1MilestoneQuery = new InMemoryMilestoneQuery();
        $l1MilestoneQuery->add(new MilestoneSummary(
            id: 'm1',
            contextId: self::PROJECT_ID,
            title: 'Milestone 1',
            dueDate: $end,
            billingAmount: '10000',
            currency: 'MYR',
            status: MilestoneStatus::Draft
        ));

        $l1TimesheetQuery = new InMemoryTimesheetQuery();
        $taskId = 'task-1';
        $l1TimesheetQuery->add(new TimesheetSummary(
            id: 'ts1',
            userId: 'user-1',
            workItemId: $taskId,
            date: new \DateTimeImmutable('2024-06-15'),
            hours: 8.0,
            description: 'Work',
            status: TimesheetStatus::Approved
        ));
        $l1TimesheetQuery->add(new TimesheetSummary(
            id: 'ts2',
            userId: 'user-1',
            workItemId: $taskId,
            date: new \DateTimeImmutable('2024-06-16'),
            hours: 4.0,
            description: 'Draft entry',
            status: TimesheetStatus::Draft
        ));

        // App in-memory (tenant-scoped)
        $taskIdsQuery = new InMemoryProjectTaskIdsQuery();
        $taskIdsQuery->setTaskIdsForProject(self::TENANT_ID, self::PROJECT_ID, [$taskId]);

        $budgetQuery = new InMemoryProjectBudgetQuery();
        // Use minor units (e.g. cents): 10000 = 100.00 MYR
        $budgetQuery->setLaborBudget(self::TENANT_ID, self::PROJECT_ID, new Money(10000, 'MYR'));
        $budgetQuery->setActualLaborCost(self::TENANT_ID, self::PROJECT_ID, new Money(2500, 'MYR'));
        $budgetQuery->setExpenseBudget(self::TENANT_ID, self::PROJECT_ID, new Money(2000, 'MYR'));
        $budgetQuery->setActualExpenseCost(self::TENANT_ID, self::PROJECT_ID, new Money(500, 'MYR'));

        $budgetPersist = new InMemoryProjectBudgetPersist();
        $messagingSender = new InMemoryProjectMessagingSender();

        // Receivable stub (only needed for MilestoneBillingService; not used in getFullHealth)
        $stubInvoice = $this->createMock(CustomerInvoiceInterface::class);
        $stubInvoice->method('getId')->willReturn('inv-stub-1');
        $stubInvoice->method('getTenantId')->willReturn('tenant-1');
        $stubReceivableManager = $this->createMock(ReceivableManagerInterface::class);
        $stubReceivableManager->method('createInvoice')->willReturn($stubInvoice);

        // Adapters (orchestrator contracts)
        $projectQueryAdapter = new ProjectQueryAdapter($l1ProjectQuery, $l1MilestoneQuery);
        $attendanceAdapter = new AttendanceQueryAdapter($taskIdsQuery, $l1TimesheetQuery);
        $budgetQueryAdapter = new BudgetQueryAdapter($budgetQuery);
        $budgetPersistAdapter = new BudgetPersistAdapter($budgetPersist);
        $schedulerAdapter = new SchedulerQueryAdapter($l1ProjectQuery);
        $receivableAdapter = new ReceivablePersistAdapter($stubReceivableManager);
        $messagingAdapter = new MessagingServiceAdapter($messagingSender);

        // Orchestrator services
        $laborService = new LaborHealthService($projectQueryAdapter, $budgetQueryAdapter, $attendanceAdapter);
        $expenseService = new ExpenseHealthService($projectQueryAdapter, $budgetQueryAdapter);
        $timelineService = new TimelineDriftService($projectQueryAdapter, $schedulerAdapter);
        $billingService = new MilestoneBillingService(
            $projectQueryAdapter,
            $receivableAdapter,
            $messagingAdapter,
            $budgetPersistAdapter
        );

        $coordinator = new ProjectManagementOperationsCoordinator(
            $laborService,
            $expenseService,
            $timelineService,
            $billingService
        );

        $health = $coordinator->getFullHealth(self::TENANT_ID, self::PROJECT_ID);

        self::assertSame(self::PROJECT_ID, $health->laborHealth->projectId);
        self::assertSame(8.0, $health->laborHealth->actualHours);
        self::assertSame(10000, $health->laborHealth->budgetedLaborCost->getAmountInMinorUnits());
        self::assertSame(2500, $health->laborHealth->actualLaborCost->getAmountInMinorUnits());
        self::assertGreaterThanOrEqual(0, $health->laborHealth->healthPercentage);

        self::assertSame(self::PROJECT_ID, $health->expenseHealth->projectId);
        self::assertSame(2000, $health->expenseHealth->budgetedExpenseCost->getAmountInMinorUnits());
        self::assertSame(500, $health->expenseHealth->actualExpenseCost->getAmountInMinorUnits());

        self::assertSame(self::PROJECT_ID, $health->timelineHealth->projectId);
        self::assertSame(1, $health->timelineHealth->totalMilestones);
        self::assertGreaterThanOrEqual(0, $health->overallScore);
    }
}
