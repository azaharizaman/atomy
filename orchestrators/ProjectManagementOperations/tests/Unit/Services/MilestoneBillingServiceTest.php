<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProjectManagementOperations\Contracts\BudgetPersistInterface;
use Nexus\ProjectManagementOperations\Contracts\MessagingServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\ReceivablePersistInterface;
use Nexus\ProjectManagementOperations\DTOs\MilestoneDTO;
use Nexus\ProjectManagementOperations\Services\MilestoneBillingService;
use PHPUnit\Framework\TestCase;

final class MilestoneBillingServiceTest extends TestCase
{
    public function test_it_creates_draft_invoice_when_milestone_is_completed(): void
    {
        $projectId = 'proj-123';
        $customerId = 'cust-456';
        $tenantId = 'tenant-789';

        $milestone = new MilestoneDTO(
            id: 'm1',
            projectId: $projectId,
            name: 'Milestone 1',
            dueDate: new \DateTimeImmutable('2024-01-15'),
            completedAt: new \DateTimeImmutable('2024-01-14'),
            isBillable: true
        );

        $projectQuery = $this->createMock(ProjectQueryInterface::class);
        $projectQuery->method('getProjectOwner')->with($projectId)->willReturn($customerId);

        $receivablePersist = $this->createMock(ReceivablePersistInterface::class);
        $receivablePersist->expects($this->once())
            ->method('createDraftInvoice')
            ->with(
                $tenantId,
                $customerId,
                $this->callback(fn($lines) => count($lines) === 1 && $lines[0]['description'] === 'Billing for Milestone: Milestone 1')
            )
            ->willReturn('inv-001');

        $messagingService = $this->createMock(MessagingServiceInterface::class);
        $messagingService->expects($this->once())
            ->method('sendNotification')
            ->with($tenantId, $customerId, 'milestone_completed', ['milestone_name' => 'Milestone 1']);

        $budgetPersist = $this->createMock(BudgetPersistInterface::class);
        $budgetPersist->expects($this->once())
            ->method('updateEarnedRevenue')
            ->with($projectId, Money::of(1000.00, 'MYR'));

        $service = new MilestoneBillingService(
            $projectQuery,
            $receivablePersist,
            $messagingService,
            $budgetPersist
        );

        $invoiceId = $service->processMilestoneCompletion($tenantId, $milestone, Money::of(1000.00, 'MYR'));

        $this->assertEquals('inv-001', $invoiceId);
    }

    public function test_it_throws_exception_for_non_billable_milestones(): void
    {
        $milestone = new MilestoneDTO(
            id: 'm1',
            projectId: 'p1',
            name: 'Milestone 1',
            dueDate: new \DateTimeImmutable(),
            completedAt: new \DateTimeImmutable(),
            isBillable: false
        );

        $service = new MilestoneBillingService(
            $this->createMock(ProjectQueryInterface::class),
            $this->createMock(ReceivablePersistInterface::class),
            $this->createMock(MessagingServiceInterface::class),
            $this->createMock(BudgetPersistInterface::class)
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Milestone m1 is not billable');

        $service->processMilestoneCompletion('t1', $milestone, Money::of(100.00, 'MYR'));
    }
}
