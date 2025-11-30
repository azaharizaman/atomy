<?php

declare(strict_types=1);

namespace Nexus\Workflow\Services;

use Nexus\Workflow\Contracts\{
    DelegationInterface,
    DelegationRepositoryInterface
};
use Nexus\Workflow\Exceptions\DelegationChainExceededException;

/**
 * Delegation Service - User delegation management.
 *
 * Public API for delegation operations.
 */
final readonly class DelegationService
{
    private const MAX_CHAIN_DEPTH = 3;

    public function __construct(
        private DelegationRepositoryInterface $delegationRepository
    ) {}

    /**
     * Create a delegation.
     *
     * @throws DelegationChainExceededException
     */
    public function createDelegation(
        string $delegatorId,
        string $delegateeId,
        \DateTimeInterface $startsAt,
        \DateTimeInterface $endsAt
    ): DelegationInterface {
        // Check chain depth
        $chain = $this->delegationRepository->getDelegationChain($delegateeId);
        if (count($chain) >= self::MAX_CHAIN_DEPTH) {
            throw DelegationChainExceededException::maxDepth(self::MAX_CHAIN_DEPTH);
        }
        
        // Create delegation - implementation in repository
        throw new \RuntimeException('Implementation required in repository layer');
    }

    /**
     * Revoke a delegation.
     */
    public function revokeDelegation(string $delegationId): void
    {
        $this->delegationRepository->delete($delegationId);
    }

    /**
     * Get active delegations for a user.
     *
     * @return DelegationInterface[]
     */
    public function getActiveDelegations(string $userId): array
    {
        return $this->delegationRepository->findActiveForUser($userId);
    }

    /**
     * Get delegation chain for a user.
     *
     * @return DelegationInterface[]
     */
    public function getDelegationChain(string $userId): array
    {
        return $this->delegationRepository->getDelegationChain($userId);
    }
}
