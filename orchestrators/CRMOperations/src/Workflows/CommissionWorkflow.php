<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Workflows;

use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Psr\Log\LoggerInterface;

final readonly class CommissionWorkflow
{
    public function __construct(
        private NotificationProviderInterface $notificationProvider,
        private ?LoggerInterface $logger = null
    ) {}

    public function calculateCommission(string $opportunityId, array $splits = []): array
    {
        $baseCommission = 10000;
        $commission = [
            'opportunity_id' => $opportunityId,
            'base_amount' => $baseCommission,
            'splits' => []
        ];

        foreach ($splits as $split) {
            $commission['splits'][] = [
                'user_id' => $split['user_id'],
                'percentage' => $split['percentage'],
                'amount' => (int) ($baseCommission * $split['percentage'] / 100)
            ];
        }

        $this->logger?->info('Commission calculated', $commission);

        return $commission;
    }

    public function applyTieredRates(int $opportunityValue): int
    {
        return match (true) {
            $opportunityValue >= 100000 => (int) ($opportunityValue * 0.10),
            $opportunityValue >= 50000 => (int) ($opportunityValue * 0.08),
            $opportunityValue >= 10000 => (int) ($opportunityValue * 0.05),
            default => (int) ($opportunityValue * 0.03),
        };
    }

    public function handleSplitCommissions(array $commissions): array
    {
        $processed = [];
        
        foreach ($commissions as $commission) {
            $processed[] = $commission;
        }
        
        return $processed;
    }

    public function submitForApproval(string $commissionId): void
    {
        $this->notificationProvider->notify(
            'sales_manager',
            'Commission Approval Required',
            sprintf('Commission %s requires approval', $commissionId),
            ['commission_id' => $commissionId]
        );
    }

    public function adjustCommission(string $commissionId, int $adjustmentAmount, string $reason): void
    {
        $this->logger?->info('Commission adjusted', [
            'commission_id' => $commissionId,
            'adjustment' => $adjustmentAmount,
            'reason' => $reason
        ]);
    }

    public function generateStatement(string $userId, string $period): array
    {
        return [
            'user_id' => $userId,
            'period' => $period,
            'total_commission' => 5000,
            'commissions' => []
        ];
    }
}
