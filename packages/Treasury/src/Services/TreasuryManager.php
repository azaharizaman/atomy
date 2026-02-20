<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\AuthorizationMatrixInterface;
use Nexus\Treasury\Contracts\InvestmentInterface;
use Nexus\Treasury\Contracts\InvestmentPersistInterface;
use Nexus\Treasury\Contracts\InvestmentQueryInterface;
use Nexus\Treasury\Contracts\IntercompanyLoanPersistInterface;
use Nexus\Treasury\Contracts\IntercompanyLoanQueryInterface;
use Nexus\Treasury\Contracts\IntercompanyTreasuryInterface;
use Nexus\Treasury\Contracts\LiquidityPoolInterface;
use Nexus\Treasury\Contracts\LiquidityPoolPersistInterface;
use Nexus\Treasury\Contracts\LiquidityPoolQueryInterface;
use Nexus\Treasury\Contracts\TreasuryAnalyticsInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalInterface;
use Nexus\Treasury\Support\InterestCalculator;
use Nexus\Treasury\Contracts\TreasuryDashboardInterface;
use Nexus\Treasury\Contracts\TreasuryForecastInterface;
use Nexus\Treasury\Contracts\TreasuryManagerInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyInterface;
use Nexus\Treasury\Contracts\TreasuryPositionInterface;
use Nexus\Treasury\Contracts\WorkingCapitalOptimizerInterface;
use Nexus\Treasury\Contracts\Integration\CashManagementProviderInterface;
use Nexus\Treasury\Enums\InvestmentStatus;
use Nexus\Treasury\Enums\InvestmentType;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\Exceptions\InvestmentNotFoundException;
use Nexus\Treasury\Exceptions\IntercompanyLoanNotFoundException;
use Nexus\Treasury\Exceptions\LiquidityPoolNotFoundException;
use Nexus\Treasury\ValueObjects\TreasuryPolicyData;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

final readonly class TreasuryManager implements TreasuryManagerInterface
{
    public function __construct(
        private TreasuryPolicyService $policyService,
        private AuthorizationMatrixService $authMatrixService,
        private TreasuryApprovalService $approvalService,
        private LiquidityPoolQueryInterface $liquidityPoolQuery,
        private LiquidityPoolPersistInterface $liquidityPoolPersist,
        private InvestmentQueryInterface $investmentQuery,
        private InvestmentPersistInterface $investmentPersist,
        private IntercompanyLoanQueryInterface $loanQuery,
        private IntercompanyLoanPersistInterface $loanPersist,
        private ?CashManagementProviderInterface $cashManagementProvider = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function createPolicy(string $tenantId, TreasuryPolicyData $data): TreasuryPolicyInterface
    {
        return $this->policyService->create($tenantId, $data);
    }

    public function getPolicy(string $policyId): TreasuryPolicyInterface
    {
        return $this->policyService->get($policyId);
    }

    public function getActivePolicy(string $tenantId): ?TreasuryPolicyInterface
    {
        return $this->policyService->getActive($tenantId);
    }

    public function createLiquidityPool(
        string $tenantId,
        string $name,
        string $currency,
        array $bankAccountIds,
        ?string $description = null
    ): LiquidityPoolInterface {
        $now = new DateTimeImmutable();
        $zeroBalance = Money::of(0, $currency);

        $pool = new \Nexus\Treasury\Entities\LiquidityPool(
            id: $this->generateId('LIQ'),
            tenantId: $tenantId,
            name: $name,
            description: $description,
            currency: $currency,
            totalBalance: $zeroBalance,
            availableBalance: $zeroBalance,
            reservedBalance: $zeroBalance,
            status: TreasuryStatus::PENDING,
            bankAccountIds: $bankAccountIds,
            createdAt: $now,
            updatedAt: $now
        );

        $this->liquidityPoolPersist->save($pool);

        $this->logger->info('Liquidity pool created', [
            'pool_id' => $pool->getId(),
            'tenant_id' => $tenantId,
            'name' => $name,
            'currency' => $currency,
        ]);

        return $pool;
    }

    public function getLiquidityPool(string $poolId): LiquidityPoolInterface
    {
        return $this->liquidityPoolQuery->findOrFail($poolId);
    }

    public function calculateTreasuryPosition(
        string $tenantId,
        ?string $entityId = null,
        ?DateTimeImmutable $asOfDate = null
    ): TreasuryPositionInterface {
        throw new RuntimeException(
            'TreasuryPosition calculation requires TreasuryPositionService - implement in Phase 5'
        );
    }

    public function executeCashSweep(string $tenantId, string $concentrationId): bool
    {
        throw new RuntimeException(
            'Cash sweep execution requires CashConcentrationService - implement in Phase 5'
        );
    }

    public function generateForecast(
        string $tenantId,
        \Nexus\Treasury\Enums\ForecastScenario $scenario,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): TreasuryForecastInterface {
        throw new RuntimeException(
            'Forecast generation requires TreasuryForecastService - implement in Phase 6'
        );
    }

    public function calculateKPIs(string $tenantId, ?DateTimeImmutable $asOfDate = null): TreasuryAnalyticsInterface
    {
        throw new RuntimeException(
            'KPI calculation requires TreasuryAnalyticsService - implement in Phase 6'
        );
    }

    public function submitForApproval(
        string $tenantId,
        string $transactionType,
        string $transactionId,
        Money $amount,
        string $requestedBy,
        ?string $description = null
    ): TreasuryApprovalInterface {
        return $this->approvalService->submit(
            $tenantId,
            $transactionType,
            $transactionId,
            $amount,
            $requestedBy,
            $description
        );
    }

    public function approveTransaction(
        string $approvalId,
        string $approvedBy,
        ?string $notes = null
    ): TreasuryApprovalInterface {
        return $this->approvalService->approve($approvalId, $approvedBy, $notes);
    }

    public function rejectTransaction(
        string $approvalId,
        string $rejectedBy,
        string $reason
    ): TreasuryApprovalInterface {
        return $this->approvalService->reject($approvalId, $rejectedBy, $reason);
    }

    public function getPendingApprovals(string $tenantId): array
    {
        return $this->approvalService->getPendingApprovals($tenantId);
    }

    public function recordInvestment(
        string $tenantId,
        InvestmentType $type,
        string $name,
        Money $principal,
        float $interestRate,
        DateTimeImmutable $maturityDate,
        string $bankAccountId,
        ?string $referenceNumber = null
    ): InvestmentInterface {
        $now = new DateTimeImmutable();
        $maturityAmount = $this->calculateMaturityAmount($principal, $interestRate, $now, $maturityDate);

        $investment = new \Nexus\Treasury\Entities\Investment(
            id: $this->generateId('INV'),
            tenantId: $tenantId,
            investmentType: $type,
            name: $name,
            description: null,
            principalAmount: $principal,
            interestRate: $interestRate,
            maturityDate: $maturityDate,
            investmentDate: $now,
            status: InvestmentStatus::ACTIVE,
            maturityAmount: $maturityAmount,
            accruedInterest: Money::of(0, $principal->getCurrency()),
            bankAccountId: $bankAccountId,
            referenceNumber: $referenceNumber,
            createdAt: $now,
            updatedAt: $now
        );

        $this->investmentPersist->save($investment);

        $this->logger->info('Investment recorded', [
            'investment_id' => $investment->getId(),
            'tenant_id' => $tenantId,
            'type' => $type->value,
            'principal' => $principal->format(),
            'interest_rate' => $interestRate,
            'maturity_date' => $maturityDate->format('Y-m-d'),
        ]);

        return $investment;
    }

    public function matureInvestment(string $investmentId): InvestmentInterface
    {
        $investment = $this->investmentQuery->findOrFail($investmentId);

        if ($investment->isMatured()) {
            return $investment;
        }

        $now = new DateTimeImmutable();
        $accruedInterest = $this->calculateAccruedInterest(
            $investment->getPrincipalAmount(),
            $investment->getInterestRate(),
            $investment->getInvestmentDate(),
            $now
        );

        $matured = new \Nexus\Treasury\Entities\Investment(
            id: $investment->getId(),
            tenantId: $investment->getTenantId(),
            investmentType: $investment->getInvestmentType(),
            name: $investment->getName(),
            description: $investment->getDescription(),
            principalAmount: $investment->getPrincipalAmount(),
            interestRate: $investment->getInterestRate(),
            maturityDate: $investment->getMaturityDate(),
            investmentDate: $investment->getInvestmentDate(),
            status: InvestmentStatus::MATURED,
            maturityAmount: $investment->getMaturityAmount(),
            accruedInterest: $accruedInterest,
            bankAccountId: $investment->getBankAccountId(),
            referenceNumber: $investment->getReferenceNumber(),
            createdAt: $investment->getCreatedAt(),
            updatedAt: $now
        );

        $this->investmentPersist->save($matured);

        $this->logger->info('Investment matured', [
            'investment_id' => $investmentId,
            'maturity_amount' => $matured->getMaturityAmount()->format(),
        ]);

        return $matured;
    }

    public function calculateWorkingCapitalMetrics(
        string $tenantId,
        ?DateTimeImmutable $asOfDate = null
    ): WorkingCapitalOptimizerInterface {
        throw new RuntimeException(
            'Working capital metrics require WorkingCapitalOptimizer - implement in Phase 6'
        );
    }

    public function getDashboardData(
        string $tenantId,
        ?DateTimeImmutable $asOfDate = null
    ): TreasuryDashboardInterface {
        throw new RuntimeException(
            'Dashboard data requires TreasuryDashboardService - implement in Phase 5'
        );
    }

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
    ): AuthorizationMatrixInterface {
        return $this->authMatrixService->setLimit(
            $tenantId,
            $transactionType,
            $approvalLimit,
            $userId,
            $roleId,
            $dailyLimit,
            $weeklyLimit,
            $monthlyLimit,
            $requiresDualApproval
        );
    }

    public function canAuthorize(
        string $tenantId,
        string $userId,
        string $transactionType,
        Money $amount
    ): bool {
        return $this->authMatrixService->canAuthorize($tenantId, $userId, $transactionType, $amount);
    }

    public function recordIntercompanyLoan(
        string $tenantId,
        string $fromEntityId,
        string $toEntityId,
        Money $principal,
        float $interestRate,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $maturityDate = null,
        ?string $referenceNumber = null
    ): IntercompanyTreasuryInterface {
        $now = new DateTimeImmutable();

        $loan = new \Nexus\Treasury\Entities\IntercompanyLoan(
            id: $this->generateId('ICL'),
            tenantId: $tenantId,
            fromEntityId: $fromEntityId,
            toEntityId: $toEntityId,
            loanType: 'intercompany',
            principalAmount: $principal,
            interestRate: $interestRate,
            outstandingBalance: $principal,
            startDate: $startDate,
            maturityDate: $maturityDate,
            accruedInterest: Money::of(0, $principal->getCurrency()),
            paymentSchedule: [],
            referenceNumber: $referenceNumber,
            notes: null,
            createdAt: $now,
            updatedAt: $now
        );

        $this->loanPersist->save($loan);

        $this->logger->info('Intercompany loan recorded', [
            'loan_id' => $loan->getId(),
            'tenant_id' => $tenantId,
            'from_entity' => $fromEntityId,
            'to_entity' => $toEntityId,
            'principal' => $principal->format(),
            'interest_rate' => $interestRate,
        ]);

        return $loan;
    }

    public function calculateIntercompanyInterest(string $loanId): Money
    {
        $loan = $this->loanQuery->findOrFail($loanId);
        
        return $this->calculateAccruedInterest(
            $loan->getPrincipalAmount(),
            $loan->getInterestRate(),
            $loan->getStartDate(),
            new DateTimeImmutable()
        );
    }

    private function calculateMaturityAmount(
        Money $principal,
        float $interestRate,
        DateTimeImmutable $investmentDate,
        DateTimeImmutable $maturityDate
    ): Money {
        $days = (int) $investmentDate->diff($maturityDate)->days;
        $years = $days / 365;
        $maturityValue = $principal->getAmount() * (1 + ($interestRate / 100) * $years);

        return Money::of($maturityValue, $principal->getCurrency());
    }

    private function calculateAccruedInterest(
        Money $principal,
        float $interestRate,
        DateTimeImmutable $startDate,
        DateTimeImmutable $asOfDate
    ): Money {
        return InterestCalculator::calculateSimpleInterest(
            $principal,
            $interestRate,
            $startDate,
            $asOfDate
        );
    }

    private function generateId(string $prefix): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-' . $prefix . '-' . $uuid;
    }
}
