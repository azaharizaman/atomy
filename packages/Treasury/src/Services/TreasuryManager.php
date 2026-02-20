<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use Nexus\Treasury\Contracts\AuthorizationLimitInterface;
use Nexus\Treasury\Contracts\AuthorizationLimitRepositoryInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalRepositoryInterface;
use Nexus\Treasury\Contracts\TreasuryManagerInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyRepositoryInterface;
use Nexus\Treasury\Enums\ApprovalStatus;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\Exceptions\TreasuryPolicyNotFoundException;
use Nexus\Treasury\Models\AuthorizationLimit;
use Nexus\Treasury\Models\TreasuryApproval;
use Nexus\Treasury\Models\TreasuryPolicy;
use Nexus\Treasury\ValueObjects\AuthorizationLimit as AuthorizationLimitVO;

/**
 * Treasury Manager Service
 */
final readonly class TreasuryManager implements TreasuryManagerInterface
{
    public function __construct(
        private TreasuryPolicyRepositoryInterface $policyRepository,
        private AuthorizationLimitRepositoryInterface $limitRepository,
        private TreasuryApprovalRepositoryInterface $approvalRepository,
        private SequenceGeneratorInterface $sequenceGenerator,
    ) {
    }

    public function createPolicy(
        string $tenantId,
        \Nexus\Treasury\ValueObjects\TreasuryPolicyData $policyData
    ): TreasuryPolicyInterface {
        $now = new \DateTimeImmutable();
        $policy = new TreasuryPolicy(
            id: $this->sequenceGenerator->generateId('TRS-POL'),
            tenantId: $tenantId,
            name: $policyData->name,
            description: $policyData->description,
            status: TreasuryStatus::ACTIVE,
            minimumCashBalance: $policyData->minimumCashBalance,
            minimumCashBalanceCurrency: $policyData->minimumCashBalanceCurrency,
            maximumSingleTransaction: $policyData->maximumSingleTransaction,
            maximumSingleTransactionCurrency: $policyData->maximumSingleTransactionCurrency,
            approvalRequired: $policyData->approvalRequired,
            approvalThreshold: $policyData->approvalThreshold,
            approvalThresholdCurrency: $policyData->approvalThresholdCurrency,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->policyRepository->save($policy);

        return $policy;
    }

    public function updatePolicy(
        string $policyId,
        \Nexus\Treasury\ValueObjects\TreasuryPolicyData $policyData
    ): TreasuryPolicyInterface {
        $policy = $this->getPolicy($policyId);

        $updatedPolicy = new TreasuryPolicy(
            id: $policy->getId(),
            tenantId: $policy->getTenantId(),
            name: $policyData->name,
            description: $policyData->description,
            status: $policy->getStatus(),
            minimumCashBalance: $policyData->minimumCashBalance,
            minimumCashBalanceCurrency: $policyData->minimumCashBalanceCurrency,
            maximumSingleTransaction: $policyData->maximumSingleTransaction,
            maximumSingleTransactionCurrency: $policyData->maximumSingleTransactionCurrency,
            approvalRequired: $policyData->approvalRequired,
            approvalThreshold: $policyData->approvalThreshold,
            approvalThresholdCurrency: $policyData->approvalThresholdCurrency,
            createdAt: $policy->getCreatedAt(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->policyRepository->save($updatedPolicy);

        return $updatedPolicy;
    }

    public function getPolicy(string $policyId): TreasuryPolicyInterface
    {
        $policy = $this->policyRepository->findById($policyId);

        if ($policy === null) {
            throw new TreasuryPolicyNotFoundException($policyId);
        }

        return $policy;
    }

    public function getPolicies(string $tenantId): array
    {
        return $this->policyRepository->findByTenantId($tenantId);
    }

    public function updatePolicyStatus(
        string $policyId,
        TreasuryStatus $status
    ): void {
        $policy = $this->getPolicy($policyId);
        $updatedPolicy = $policy->withStatus($status);
        $this->policyRepository->save($updatedPolicy);
    }

    public function createAuthorizationLimit(
        string $tenantId,
        AuthorizationLimitVO $limit
    ): AuthorizationLimitInterface {
        $now = new \DateTimeImmutable();
        $limitModel = new AuthorizationLimit(
            id: $this->sequenceGenerator->generateId('TRS-LIM'),
            tenantId: $tenantId,
            userId: $limit->userId,
            roleId: $limit->roleId,
            amount: $limit->amount,
            currency: $limit->currency,
            transactionType: $limit->transactionType,
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->limitRepository->save($limitModel);

        return $limitModel;
    }

    public function getAuthorizationLimitForAmount(
        string $tenantId,
        float $amount,
        string $currency
    ): ?AuthorizationLimitInterface {
        return $this->limitRepository->findActiveByAmount($tenantId, $amount, $currency);
    }

    public function requiresApproval(
        string $tenantId,
        float $amount,
        string $currency
    ): bool {
        // Check if there's an active policy that requires approval
        $policies = $this->policyRepository->findByStatus($tenantId, TreasuryStatus::ACTIVE);

        foreach ($policies as $policy) {
            if ($policy->isApprovalRequired() 
                && $currency === $policy->getApprovalThresholdCurrency()
                && $amount >= $policy->getApprovalThreshold()
            ) {
                return true;
            }
        }

        // Check if there's an authorization limit for this amount
        // If limit exists but amount exceeds it, approval is required
        $limit = $this->getAuthorizationLimitForAmount($tenantId, $amount, $currency);
        
        return $limit !== null && $limit->getAmount() < $amount;
    }

    public function submitForApproval(
        string $tenantId,
        string $transactionType,
        float $amount,
        string $currency,
        string $description,
        string $submittedBy
    ): TreasuryApprovalInterface {
        $now = new \DateTimeImmutable();
        $approval = new TreasuryApproval(
            id: $this->sequenceGenerator->generateId('TRS-APR'),
            tenantId: $tenantId,
            transactionType: $transactionType,
            amount: $amount,
            currency: $currency,
            description: $description,
            status: ApprovalStatus::PENDING,
            submittedBy: $submittedBy,
            submittedAt: $now,
            approvedBy: null,
            approvedAt: null,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            comments: null,
        );

        $this->approvalRepository->save($approval);

        return $approval;
    }

    public function approveTransaction(
        string $approvalId,
        string $approvedBy,
        ?string $comments = null
    ): TreasuryApprovalInterface {
        $approval = $this->getApproval($approvalId);
        $now = new \DateTimeImmutable();

        $approvedApproval = new TreasuryApproval(
            id: $approval->getId(),
            tenantId: $approval->getTenantId(),
            transactionType: $approval->getTransactionType(),
            amount: $approval->getAmount(),
            currency: $approval->getCurrency(),
            description: $approval->getDescription(),
            status: ApprovalStatus::APPROVED,
            submittedBy: $approval->getSubmittedBy(),
            submittedAt: $approval->getSubmittedAt(),
            approvedBy: $approvedBy,
            approvedAt: $now,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            comments: $comments,
        );

        $this->approvalRepository->save($approvedApproval);

        return $approvedApproval;
    }

    public function rejectTransaction(
        string $approvalId,
        string $rejectedBy,
        string $reason
    ): TreasuryApprovalInterface {
        $approval = $this->getApproval($approvalId);
        $now = new \DateTimeImmutable();

        $rejectedApproval = new TreasuryApproval(
            id: $approval->getId(),
            tenantId: $approval->getTenantId(),
            transactionType: $approval->getTransactionType(),
            amount: $approval->getAmount(),
            currency: $approval->getCurrency(),
            description: $approval->getDescription(),
            status: ApprovalStatus::REJECTED,
            submittedBy: $approval->getSubmittedBy(),
            submittedAt: $approval->getSubmittedAt(),
            approvedBy: null,
            approvedAt: null,
            rejectedBy: $rejectedBy,
            rejectedAt: $now,
            rejectionReason: $reason,
            comments: null,
        );

        $this->approvalRepository->save($rejectedApproval);

        return $rejectedApproval;
    }

    public function getApproval(string $approvalId): TreasuryApprovalInterface
    {
        $approval = $this->approvalRepository->findById($approvalId);

        if ($approval === null) {
            throw new \Nexus\Treasury\Exceptions\TreasuryException("Approval not found: {$approvalId}");
        }

        return $approval;
    }

    public function getPendingApprovals(string $userId): array
    {
        return $this->approvalRepository->findPendingByUserId($userId);
    }

    public function getApprovalsByStatus(
        string $tenantId,
        ApprovalStatus $status
    ): array {
        return $this->approvalRepository->findByStatus($tenantId, $status);
    }
}
