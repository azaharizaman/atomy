<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Workflow\Contracts\DelegationInterface;

/**
 * Contract for delegation resolution service.
 *
 * Handles finding the actual approver when the primary
 * approver has delegated their approval authority.
 */
interface DelegationServiceInterface
{
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
    public function resolveApprover(string $tenantId, string $approverId, string $taskType): array;

    /**
     * Check if a user has any active delegations.
     */
    public function hasActiveDelegation(string $tenantId, string $userId): bool;

    /**
     * Get the full delegation chain for a user.
     *
     * @return array<DelegationInterface>
     */
    public function getDelegationChain(string $tenantId, string $userId): array;
}
