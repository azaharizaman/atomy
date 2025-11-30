<?php

declare(strict_types=1);

namespace Nexus\Compliance\Core\Engine;

use Nexus\Compliance\Exceptions\SodViolationException;
use Psr\Log\LoggerInterface;

/**
 * SOD (Segregation of Duties) validator.
 * 
 * Validates that creators and approvers are different users and enforces
 * role-based separation rules.
 */
final class SodValidator
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Validate that creator and approver are different.
     *
     * @param string $creatorId The creator user ID
     * @param string $approverId The approver user ID
     * @param string $transactionType The transaction type
     * @throws SodViolationException If validation fails
     * @return bool True if validation passes
     */
    public function validateCreatorApproverSeparation(
        string $creatorId,
        string $approverId,
        string $transactionType
    ): bool {
        $this->logger->debug("Validating creator/approver separation", [
            'creator_id' => $creatorId,
            'approver_id' => $approverId,
            'transaction_type' => $transactionType,
        ]);

        if ($creatorId === $approverId) {
            $this->logger->warning("SOD violation: creator cannot be approver", [
                'user_id' => $creatorId,
                'transaction_type' => $transactionType,
            ]);

            throw new SodViolationException(
                $transactionType,
                $creatorId,
                $approverId,
                'creator_cannot_approve'
            );
        }

        return true;
    }

    /**
     * Validate role-based separation.
     *
     * @param array<string> $creatorRoles The creator's roles
     * @param array<string> $approverRoles The approver's roles
     * @param string $requiredCreatorRole The role required for creation
     * @param string $requiredApproverRole The role required for approval
     * @param string $transactionType The transaction type
     * @throws SodViolationException If validation fails
     * @return bool True if validation passes
     */
    public function validateRoleSeparation(
        array $creatorRoles,
        array $approverRoles,
        string $requiredCreatorRole,
        string $requiredApproverRole,
        string $transactionType
    ): bool {
        $this->logger->debug("Validating role-based separation", [
            'creator_roles' => $creatorRoles,
            'approver_roles' => $approverRoles,
            'required_creator_role' => $requiredCreatorRole,
            'required_approver_role' => $requiredApproverRole,
            'transaction_type' => $transactionType,
        ]);

        // Check if creator has required role
        if (!in_array($requiredCreatorRole, $creatorRoles, true)) {
            $this->logger->warning("Creator does not have required role", [
                'required_role' => $requiredCreatorRole,
                'creator_roles' => $creatorRoles,
            ]);

            throw new SodViolationException(
                $transactionType,
                'creator',
                'approver',
                "creator_missing_role_{$requiredCreatorRole}"
            );
        }

        // Check if approver has required role
        if (!in_array($requiredApproverRole, $approverRoles, true)) {
            $this->logger->warning("Approver does not have required role", [
                'required_role' => $requiredApproverRole,
                'approver_roles' => $approverRoles,
            ]);

            throw new SodViolationException(
                $transactionType,
                'creator',
                'approver',
                "approver_missing_role_{$requiredApproverRole}"
            );
        }

        // Check if roles overlap (same person holding both roles)
        $roleOverlap = array_intersect(
            [$requiredCreatorRole],
            [$requiredApproverRole]
        );

        if (!empty($roleOverlap)) {
            $this->logger->info("Roles overlap, but users are different - allowed", [
                'overlapping_roles' => $roleOverlap,
            ]);
        }

        return true;
    }

    /**
     * Validate delegation chain (max 3 levels).
     *
     * @param array<string> $delegationChain Array of user IDs in delegation chain
     * @param string $transactionType The transaction type
     * @throws SodViolationException If validation fails
     * @return bool True if validation passes
     */
    public function validateDelegationChain(
        array $delegationChain,
        string $transactionType
    ): bool {
        $this->logger->debug("Validating delegation chain", [
            'chain_length' => count($delegationChain),
            'transaction_type' => $transactionType,
        ]);

        if (count($delegationChain) > 3) {
            $this->logger->warning("Delegation chain too long", [
                'max_allowed' => 3,
                'actual' => count($delegationChain),
            ]);

            throw new SodViolationException(
                $transactionType,
                'creator',
                'approver',
                'delegation_chain_too_long'
            );
        }

        // Check for circular delegation
        if (count($delegationChain) !== count(array_unique($delegationChain))) {
            $this->logger->warning("Circular delegation detected", [
                'delegation_chain' => $delegationChain,
            ]);

            throw new SodViolationException(
                $transactionType,
                'creator',
                'approver',
                'circular_delegation'
            );
        }

        return true;
    }
}
