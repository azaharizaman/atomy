<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\Integration\PeriodValidationInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalQueryInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalPersistInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Entities\TreasuryApproval;
use Nexus\Treasury\Enums\ApprovalStatus;
use Nexus\Treasury\Exceptions\DuplicateApprovalException;
use Nexus\Treasury\Exceptions\PeriodClosedException;
use Nexus\Treasury\Exceptions\SegregationOfDutiesViolationException;
use Nexus\Treasury\Exceptions\TreasuryApprovalNotFoundException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class TreasuryApprovalService
{
    private const DEFAULT_EXPIRY_DAYS = 7;

    public function __construct(
        private TreasuryApprovalQueryInterface $query,
        private TreasuryApprovalPersistInterface $persist,
        private TreasuryPolicyQueryInterface $policyQuery,
        private AuthorizationMatrixService $authMatrixService,
        private ?PeriodValidationInterface $periodValidation = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function submit(
        string $tenantId,
        string $transactionType,
        string $transactionId,
        Money $amount,
        string $requestedBy,
        ?string $description = null,
        ?string $transactionReference = null
    ): TreasuryApprovalInterface {
        $this->validatePeriod();

        $existing = $this->query->findByTransactionId($transactionId);
        if ($existing !== null && $existing->isPending()) {
            throw DuplicateApprovalException::alreadyExists($transactionId);
        }

        $this->authMatrixService->validateAuthorization(
            $tenantId,
            $requestedBy,
            $transactionType,
            $amount
        );

        $now = new DateTimeImmutable();
        $expiresAt = $now->modify('+' . self::DEFAULT_EXPIRY_DAYS . ' days');

        $approval = new TreasuryApproval(
            id: $this->generateId(),
            tenantId: $tenantId,
            transactionType: $transactionType,
            transactionId: $transactionId,
            transactionReference: $transactionReference,
            amount: $amount,
            description: $description,
            status: ApprovalStatus::PENDING,
            requestedBy: $requestedBy,
            requestedAt: $now,
            approvers: [],
            approvedBy: null,
            approvedAt: null,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            approvalNotes: null,
            expiresAt: $expiresAt,
            createdAt: $now,
            updatedAt: $now
        );

        $this->persist->save($approval);

        $this->logger->info('Approval request submitted', [
            'approval_id' => $approval->getId(),
            'tenant_id' => $tenantId,
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'amount' => $amount->format(),
            'requested_by' => $requestedBy,
        ]);

        return $approval;
    }

    public function approve(
        string $approvalId,
        string $approvedBy,
        ?string $notes = null
    ): TreasuryApprovalInterface {
        $approval = $this->query->findOrFail($approvalId);

        $this->validateCanBeApproved($approval, $approvedBy);

        $this->validateSegregationOfDuties($approval, $approvedBy);

        $now = new DateTimeImmutable();
        $approvers = $approval->getApprovers();
        $approvers[] = [
            'user_id' => $approvedBy,
            'approved_at' => $now->format('Y-m-d H:i:s'),
            'notes' => $notes,
        ];

        $approved = new TreasuryApproval(
            id: $approval->getId(),
            tenantId: $approval->getTenantId(),
            transactionType: $approval->getTransactionType(),
            transactionId: $approval->getTransactionId(),
            transactionReference: $approval->getTransactionReference(),
            amount: $approval->getAmount(),
            description: $approval->getDescription(),
            status: ApprovalStatus::APPROVED,
            requestedBy: $approval->getRequestedBy(),
            requestedAt: $approval->getRequestedAt(),
            approvers: $approvers,
            approvedBy: $approvedBy,
            approvedAt: $now,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            approvalNotes: $notes,
            expiresAt: $approval->getExpiresAt(),
            createdAt: $approval->getCreatedAt(),
            updatedAt: $now
        );

        $this->persist->save($approved);

        $this->logger->info('Approval granted', [
            'approval_id' => $approvalId,
            'approved_by' => $approvedBy,
            'transaction_id' => $approval->getTransactionId(),
        ]);

        return $approved;
    }

    public function reject(
        string $approvalId,
        string $rejectedBy,
        string $reason
    ): TreasuryApprovalInterface {
        $approval = $this->query->findOrFail($approvalId);

        if (!$approval->isPending()) {
            throw DuplicateApprovalException::cannotApproveTwice($approvalId);
        }

        $now = new DateTimeImmutable();

        $rejected = new TreasuryApproval(
            id: $approval->getId(),
            tenantId: $approval->getTenantId(),
            transactionType: $approval->getTransactionType(),
            transactionId: $approval->getTransactionId(),
            transactionReference: $approval->getTransactionReference(),
            amount: $approval->getAmount(),
            description: $approval->getDescription(),
            status: ApprovalStatus::REJECTED,
            requestedBy: $approval->getRequestedBy(),
            requestedAt: $approval->getRequestedAt(),
            approvers: $approval->getApprovers(),
            approvedBy: null,
            approvedAt: null,
            rejectedBy: $rejectedBy,
            rejectedAt: $now,
            rejectionReason: $reason,
            approvalNotes: null,
            expiresAt: $approval->getExpiresAt(),
            createdAt: $approval->getCreatedAt(),
            updatedAt: $now
        );

        $this->persist->save($rejected);

        $this->logger->info('Approval rejected', [
            'approval_id' => $approvalId,
            'rejected_by' => $rejectedBy,
            'reason' => $reason,
            'transaction_id' => $approval->getTransactionId(),
        ]);

        return $rejected;
    }

    public function get(string $approvalId): TreasuryApprovalInterface
    {
        return $this->query->findOrFail($approvalId);
    }

    public function getPendingApprovals(string $tenantId): array
    {
        return $this->query->findPendingByTenantId($tenantId);
    }

    public function getPendingForApprover(string $approverId): array
    {
        return $this->query->findPendingByApprover($approverId);
    }

    public function getByTransaction(string $transactionId): ?TreasuryApprovalInterface
    {
        return $this->query->findByTransactionId($transactionId);
    }

    public function isApproved(string $transactionId): bool
    {
        $approval = $this->query->findByTransactionId($transactionId);
        return $approval !== null && $approval->isApproved();
    }

    public function isPending(string $transactionId): bool
    {
        $approval = $this->query->findByTransactionId($transactionId);
        return $approval !== null && $approval->isPending();
    }

    public function requiresApproval(string $tenantId, Money $amount): bool
    {
        $policy = $this->policyQuery->findEffectiveForDate(
            $tenantId,
            new DateTimeImmutable()
        );

        if ($policy === null) {
            return true;
        }

        return $policy->isApprovalRequired() &&
               $amount->greaterThan($policy->getApprovalThreshold());
    }

    public function expireApprovals(): int
    {
        $expired = $this->query->findExpired(new DateTimeImmutable());
        $count = 0;

        foreach ($expired as $approval) {
            if ($approval->isPending()) {
                $expiredEntity = new TreasuryApproval(
                    id: $approval->getId(),
                    tenantId: $approval->getTenantId(),
                    transactionType: $approval->getTransactionType(),
                    transactionId: $approval->getTransactionId(),
                    transactionReference: $approval->getTransactionReference(),
                    amount: $approval->getAmount(),
                    description: $approval->getDescription(),
                    status: ApprovalStatus::EXPIRED,
                    requestedBy: $approval->getRequestedBy(),
                    requestedAt: $approval->getRequestedAt(),
                    approvers: $approval->getApprovers(),
                    approvedBy: null,
                    approvedAt: null,
                    rejectedBy: null,
                    rejectedAt: null,
                    rejectionReason: null,
                    approvalNotes: null,
                    expiresAt: $approval->getExpiresAt(),
                    createdAt: $approval->getCreatedAt(),
                    updatedAt: new DateTimeImmutable()
                );

                $this->persist->save($expiredEntity);
                $count++;
            }
        }

        if ($count > 0) {
            $this->logger->info('Expired approvals processed', ['count' => $count]);
        }

        return $count;
    }

    private function validateCanBeApproved(TreasuryApprovalInterface $approval, string $userId): void
    {
        if ($approval->isExpired()) {
            throw TreasuryApprovalNotFoundException::forId($approval->getId());
        }

        if (!$approval->isPending()) {
            throw DuplicateApprovalException::alreadyApproved(
                $approval->getId(),
                $approval->getApprovedBy() ?? $approval->getRejectedBy() ?? 'unknown'
            );
        }

        foreach ($approval->getApprovers() as $approver) {
            if ($approver['user_id'] === $userId) {
                throw SegregationOfDutiesViolationException::sameUserMultipleApprovals(
                    $userId,
                    $approval->getTransactionId()
                );
            }
        }
    }

    private function validateSegregationOfDuties(
        TreasuryApprovalInterface $approval,
        string $approvedBy
    ): void {
        if ($approval->getRequestedBy() === $approvedBy) {
            throw SegregationOfDutiesViolationException::sameUserCannotApprove(
                $approvedBy,
                $approval->getTransactionId()
            );
        }
    }

    private function validatePeriod(): void
    {
        if ($this->periodValidation === null) {
            return;
        }

        if (!$this->periodValidation->isPostingAllowed(new DateTimeImmutable())) {
            throw PeriodClosedException::forDate(new DateTimeImmutable());
        }
    }

    private function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-APP-' . $uuid;
    }
}
