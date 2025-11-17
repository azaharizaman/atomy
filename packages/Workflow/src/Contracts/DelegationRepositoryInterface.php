<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Persistence contract for delegations.
 */
interface DelegationRepositoryInterface
{
    /**
     * Find a delegation by ID.
     */
    public function findById(string $id): DelegationInterface;

    /**
     * Find active delegations for a user.
     *
     * @return DelegationInterface[]
     */
    public function findActiveForUser(string $userId): array;

    /**
     * Find delegation chain for a user.
     *
     * Returns the full chain: user -> delegatee -> delegatee's delegatee, etc.
     *
     * @return DelegationInterface[]
     */
    public function getDelegationChain(string $userId): array;

    /**
     * Save delegation.
     */
    public function save(DelegationInterface $delegation): void;

    /**
     * Delete delegation.
     */
    public function delete(string $id): void;
}
