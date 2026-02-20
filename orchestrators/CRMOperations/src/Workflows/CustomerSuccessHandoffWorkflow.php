<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Workflows;

use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Psr\Log\LoggerInterface;

final readonly class CustomerSuccessHandoffWorkflow
{
    public function __construct(
        private NotificationProviderInterface $notificationProvider,
        private ?LoggerInterface $logger = null
    ) {}

    public function triggerOnClose(string $opportunityId, string $customerId): void
    {
        $this->logger?->info('Customer success handoff triggered', [
            'opportunity_id' => $opportunityId,
            'customer_id' => $customerId
        ]);
    }

    public function createOnboardingTasks(string $customerId, string $productId): array
    {
        $taskIds = ['task_1', 'task_2', 'task_3'];
        
        $this->logger?->info('Onboarding tasks created', [
            'customer_id' => $customerId,
            'tasks' => $taskIds
        ]);
        
        return $taskIds;
    }

    public function transferOwnership(string $customerId, string $newOwnerId): void
    {
        $this->logger?->info('Account ownership transferred', [
            'customer_id' => $customerId,
            'new_owner' => $newOwnerId
        ]);
    }

    public function notifyCustomerSuccessTeam(string $customerId, string $opportunityId): void
    {
        $this->notificationProvider->notify(
            'customer_success',
            'New Customer Handoff',
            sprintf('Customer %s has been won and requires onboarding', $customerId),
            [
                'customer_id' => $customerId,
                'opportunity_id' => $opportunityId
            ]
        );
    }

    public function createSuccessPlan(string $customerId): array
    {
        return [
            'customer_id' => $customerId,
            'milestones' => [],
            'health_score' => 100
        ];
    }
}
