<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services\Approval;

use Nexus\ProcurementOperations\Contracts\DelegationServiceInterface;
use Nexus\Workflow\Contracts\DelegationInterface;
use Nexus\Workflow\Contracts\DelegationRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Handles approval delegation resolution.
 *
 * This service resolves active delegations to find the actual
 * approver when the primary approver is unavailable (e.g., on leave).
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Service performs cross-boundary logic (Workflow package)
 * - Returns processed results for use by coordinators
 * - Does NOT make approval decisions (that's the coordinator's job)
 */
final readonly class DelegationService implements DelegationServiceInterface
{
    private const MAX_CHAIN_DEPTH = 3;

    public function __construct(
        private DelegationRepositoryInterface $delegationRepository,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get logger instance.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Resolve the actual approver considering active delegations.
     *
     * @param string $tenantId Tenant context
     * @param string $approverId Original approver user ID
     * @param string $taskType Task type (e.g., 'requisition_approval')
     * @return array{
     *     actualApproverId: string,
     *     actualApproverName: ?string,
     *     isDelegated: bool,
     *     delegationChain: array<array{
     *         fromUserId: string,
     *         toUserId: string,
     *         delegationId: string,
     *         startsAt: \DateTimeImmutable,
     *         endsAt: \DateTimeImmutable
     *     }>,
     *     originalApproverId: string
     * }
     */
    public function resolveApprover(string $tenantId, string $approverId, string $taskType): array
    {
        $this->getLogger()->debug('Resolving approver with delegation', [
            'tenant_id' => $tenantId,
            'approver_id' => $approverId,
            'task_type' => $taskType,
        ]);

        $delegationChain = [];
        $currentApproverId = $approverId;
        $depth = 0;

        while ($depth < self::MAX_CHAIN_DEPTH) {
            $activeDelegations = $this->delegationRepository->findActiveForUser($currentApproverId);

            // Find applicable delegation for this task type
            $applicableDelegation = $this->findApplicableDelegation($activeDelegations, $taskType);

            if ($applicableDelegation === null) {
                // No more delegations, we found our approver
                break;
            }

            // Add to chain
            $delegationChain[] = [
                'fromUserId' => $currentApproverId,
                'toUserId' => $applicableDelegation->getDelegateeId(),
                'delegationId' => $applicableDelegation->getId(),
                'startsAt' => \DateTimeImmutable::createFromInterface($applicableDelegation->getStartsAt()),
                'endsAt' => \DateTimeImmutable::createFromInterface($applicableDelegation->getEndsAt()),
            ];

            // Move to delegatee
            $currentApproverId = $applicableDelegation->getDelegateeId();
            $depth++;

            $this->getLogger()->debug('Delegation found', [
                'from_user_id' => $delegationChain[count($delegationChain) - 1]['fromUserId'],
                'to_user_id' => $currentApproverId,
                'depth' => $depth,
            ]);
        }

        $isDelegated = !empty($delegationChain);

        if ($isDelegated) {
            $this->getLogger()->info('Approver resolved via delegation', [
                'original_approver_id' => $approverId,
                'actual_approver_id' => $currentApproverId,
                'chain_depth' => count($delegationChain),
            ]);
        }

        return [
            'actualApproverId' => $currentApproverId,
            'actualApproverName' => null, // Would be populated by coordinator with user lookup
            'isDelegated' => $isDelegated,
            'delegationChain' => $delegationChain,
            'originalApproverId' => $approverId,
        ];
    }

    /**
     * Check if a user has any active delegations.
     */
    public function hasActiveDelegation(string $tenantId, string $userId): bool
    {
        $delegations = $this->delegationRepository->findActiveForUser($userId);
        return !empty($delegations);
    }

    /**
     * Get the full delegation chain for a user.
     *
     * @return array<DelegationInterface>
     */
    public function getDelegationChain(string $tenantId, string $userId): array
    {
        return $this->delegationRepository->getDelegationChain($userId);
    }

    /**
     * Find an applicable delegation for the given task type.
     *
     * @param array<DelegationInterface> $delegations
     * @param string $taskType
     * @return DelegationInterface|null
     */
    private function findApplicableDelegation(array $delegations, string $taskType): ?DelegationInterface
    {
        foreach ($delegations as $delegation) {
            if (!$delegation->isActive()) {
                continue;
            }

            // For now, all active delegations apply to all task types
            // In the future, we could add task type filtering via delegation metadata
            return $delegation;
        }

        return null;
    }
}
