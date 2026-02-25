<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProjectManagementOperations\Contracts\BudgetPersistInterface;
use Nexus\ProjectManagementOperations\Contracts\MessagingServiceInterface;
use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\ReceivablePersistInterface;
use Nexus\ProjectManagementOperations\DTOs\MilestoneDTO;

final readonly class MilestoneBillingService
{
    public function __construct(
        private ProjectQueryInterface $projectQuery,
        private ReceivablePersistInterface $receivablePersist,
        private MessagingServiceInterface $messagingService,
        private BudgetPersistInterface $budgetPersist
    ) {
    }

    public function processMilestoneCompletion(
        string $tenantId,
        MilestoneDTO $milestone,
        Money $amount
    ): string {
        if (!$milestone->isBillable) {
            throw new \InvalidArgumentException("Milestone {$milestone->id} is not billable");
        }

        $customerId = $this->projectQuery->getProjectOwner($milestone->projectId);

        $invoiceId = $this->receivablePersist->createDraftInvoice(
            $tenantId,
            $customerId,
            [
                [
                    'description' => "Billing for Milestone: {$milestone->name}",
                    'amount' => $amount
                ]
            ]
        );

        $this->messagingService->sendNotification(
            $tenantId,
            $customerId,
            'milestone_completed',
            ['milestone_name' => $milestone->name]
        );

        $this->budgetPersist->updateEarnedRevenue($milestone->projectId, $amount);

        return $invoiceId;
    }
}
