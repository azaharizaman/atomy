<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use Nexus\Treasury\Enums\ApprovalStatus;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\ValueObjects\AuthorizationLimit;
use Nexus\Treasury\ValueObjects\TreasuryPolicyData;

/**
 * Treasury Manager Interface
 *
 * Main orchestrator for treasury operations
 */
interface TreasuryManagerInterface
{
    /**
     * Create a new treasury policy
     */
    public function createPolicy(
        string $tenantId,
        TreasuryPolicyData $policyData
    ): TreasuryPolicyInterface;

    /**
     * Update an existing treasury policy
     */
    public function updatePolicy(
        string $policyId,
        TreasuryPolicyData $policyData
    ): TreasuryPolicyInterface;

    /**
     * Get treasury policy by ID
     */
    public function getPolicy(string $policyId): TreasuryPolicyInterface;

    /**
     * Get all treasury policies for a tenant
     *
     * @return array<TreasuryPolicyInterface>
     */
    public function getPolicies(string $tenantId): array;

    /**
     * Update treasury policy status
     */
    public function updatePolicyStatus(
        string $policyId,
        TreasuryStatus $status
    ): void;

    /**
     * Create authorization limit
     */
    public function createAuthorizationLimit(
        string $tenantId,
        AuthorizationLimit $limit
    ): AuthorizationLimitInterface;

    /**
     * Get authorization limit for amount
     */
    public function getAuthorizationLimitForAmount(
        string $tenantId,
        float $amount,
        string $currency
    ): ?AuthorizationLimitInterface;

    /**
     * Check if transaction requires approval
     */
    public function requiresApproval(
        string $tenantId,
        float $amount,
        string $currency
    ): bool;

    /**
     * Submit transaction for approval
     */
    public function submitForApproval(
        string $tenantId,
        string $transactionType,
        float $amount,
        string $currency,
        string $description,
        string $submittedBy
    ): TreasuryApprovalInterface;

    /**
     * Approve treasury transaction
     */
    public function approveTransaction(
        string $approvalId,
        string $approvedBy,
        ?string $comments = null
    ): TreasuryApprovalInterface;

    /**
     * Reject treasury transaction
     */
    public function rejectTransaction(
        string $approvalId,
        string $rejectedBy,
        string $reason
    ): TreasuryApprovalInterface;

    /**
     * Get approval by ID
     */
    public function getApproval(string $approvalId): TreasuryApprovalInterface;

    /**
     * Get pending approvals for user
     *
     * @return array<TreasuryApprovalInterface>
     */
    public function getPendingApprovals(string $userId): array;

    /**
     * Get approvals by status
     *
     * @return array<TreasuryApprovalInterface>
     */
    public function getApprovalsByStatus(
        string $tenantId,
        ApprovalStatus $status
    ): array;
}
