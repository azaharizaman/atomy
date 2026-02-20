<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

interface TreasuryAuditInterface
{
    public function log(
        string $entityId,
        string $action,
        string $description,
        array $context = []
    ): void;

    public function logPolicyChange(
        string $policyId,
        string $action,
        array $oldValues,
        array $newValues,
        string $userId
    ): void;

    public function logApprovalAction(
        string $approvalId,
        string $action,
        string $userId,
        ?string $reason = null
    ): void;

    public function logInvestmentAction(
        string $investmentId,
        string $action,
        array $details
    ): void;

    public function logIntercompanyLoanAction(
        string $loanId,
        string $action,
        array $details
    ): void;

    public function logAuthorizationChange(
        string $matrixId,
        string $action,
        array $changes,
        string $userId
    ): void;

    public function getAuditTrail(
        string $entityType,
        string $entityId,
        ?int $limit = null
    ): array;
}
