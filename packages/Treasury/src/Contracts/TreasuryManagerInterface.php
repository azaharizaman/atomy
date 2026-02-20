<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Enums\ForecastScenario;
use Nexus\Treasury\Enums\InvestmentType;
use Nexus\Treasury\ValueObjects\TreasuryPolicyData;

interface TreasuryManagerInterface
{
    public function createPolicy(string $tenantId, TreasuryPolicyData $data): TreasuryPolicyInterface;

    public function getPolicy(string $policyId): TreasuryPolicyInterface;

    public function getActivePolicy(string $tenantId): ?TreasuryPolicyInterface;

    public function createLiquidityPool(
        string $tenantId,
        string $name,
        string $currency,
        array $bankAccountIds,
        ?string $description = null
    ): LiquidityPoolInterface;

    public function getLiquidityPool(string $poolId): LiquidityPoolInterface;

    public function calculateTreasuryPosition(
        string $tenantId,
        ?string $entityId = null,
        ?DateTimeImmutable $asOfDate = null
    ): TreasuryPositionInterface;

    public function executeCashSweep(
        string $tenantId,
        string $concentrationId
    ): bool;

    public function generateForecast(
        string $tenantId,
        ForecastScenario $scenario,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): TreasuryForecastInterface;

    public function calculateKPIs(
        string $tenantId,
        ?DateTimeImmutable $asOfDate = null
    ): TreasuryAnalyticsInterface;

    public function submitForApproval(
        string $tenantId,
        string $transactionType,
        string $transactionId,
        Money $amount,
        string $requestedBy,
        ?string $description = null
    ): TreasuryApprovalInterface;

    public function approveTransaction(
        string $approvalId,
        string $approvedBy,
        ?string $notes = null
    ): TreasuryApprovalInterface;

    public function rejectTransaction(
        string $approvalId,
        string $rejectedBy,
        string $reason
    ): TreasuryApprovalInterface;

    public function getPendingApprovals(string $tenantId): array;

    public function recordInvestment(
        string $tenantId,
        InvestmentType $type,
        string $name,
        Money $principal,
        float $interestRate,
        DateTimeImmutable $maturityDate,
        string $bankAccountId,
        ?string $referenceNumber = null
    ): InvestmentInterface;

    public function matureInvestment(string $investmentId): InvestmentInterface;

    public function calculateWorkingCapitalMetrics(
        string $tenantId,
        ?DateTimeImmutable $asOfDate = null
    ): WorkingCapitalOptimizerInterface;

    public function getDashboardData(
        string $tenantId,
        ?DateTimeImmutable $asOfDate = null
    ): TreasuryDashboardInterface;

    public function setAuthorizationLimit(
        string $tenantId,
        string $transactionType,
        Money $approvalLimit,
        ?string $userId = null,
        ?string $roleId = null,
        ?Money $dailyLimit = null,
        ?Money $weeklyLimit = null,
        ?Money $monthlyLimit = null,
        bool $requiresDualApproval = false
    ): AuthorizationMatrixInterface;

    public function canAuthorize(
        string $tenantId,
        string $userId,
        string $transactionType,
        Money $amount
    ): bool;

    public function recordIntercompanyLoan(
        string $tenantId,
        string $fromEntityId,
        string $toEntityId,
        Money $principal,
        float $interestRate,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $maturityDate = null,
        ?string $referenceNumber = null
    ): IntercompanyTreasuryInterface;

    public function calculateIntercompanyInterest(string $loanId): Money;
}
