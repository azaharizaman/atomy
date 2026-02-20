<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\Integration\CashManagementProviderInterface;
use Nexus\Treasury\Contracts\Integration\PayableDataProviderInterface;
use Nexus\Treasury\Contracts\Integration\ReceivableDataProviderInterface;
use Nexus\Treasury\Contracts\InvestmentQueryInterface;
use Nexus\Treasury\Contracts\LiquidityPoolQueryInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Contracts\TreasuryPositionInterface;
use Nexus\Treasury\ValueObjects\TreasuryPosition;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class TreasuryPositionService
{
    public function __construct(
        private TreasuryPolicyQueryInterface $policyQuery,
        private LiquidityPoolQueryInterface $liquidityPoolQuery,
        private InvestmentQueryInterface $investmentQuery,
        private ?CashManagementProviderInterface $cashManagementProvider = null,
        private ?PayableDataProviderInterface $payableProvider = null,
        private ?ReceivableDataProviderInterface $receivableProvider = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function calculatePosition(
        string $tenantId,
        ?string $entityId = null,
        ?DateTimeImmutable $asOfDate = null
    ): TreasuryPositionInterface {
        $date = $asOfDate ?? new DateTimeImmutable();
        $currency = $this->getDefaultCurrency($tenantId);

        $totalCashBalance = $this->calculateTotalCashBalance($tenantId, $entityId, $currency);
        $availableCashBalance = $this->calculateAvailableCashBalance($tenantId, $entityId, $currency);
        $reservedCashBalance = $totalCashBalance->subtract($availableCashBalance);
        $investedCashBalance = $this->calculateInvestedCashBalance($tenantId, $currency);

        $projectedInflows = $this->calculateProjectedInflows($tenantId, $entityId, $currency);
        $projectedOutflows = $this->calculateProjectedOutflows($tenantId, $entityId, $currency);

        $position = new TreasuryPosition(
            id: TreasuryPosition::generateId(),
            tenantId: $tenantId,
            entityId: $entityId,
            totalCashBalance: $totalCashBalance,
            availableCashBalance: $availableCashBalance,
            reservedCashBalance: $reservedCashBalance,
            investedCashBalance: $investedCashBalance,
            projectedInflows: $projectedInflows,
            projectedOutflows: $projectedOutflows,
            positionDate: $date
        );

        $this->logger->info('Treasury position calculated', [
            'tenant_id' => $tenantId,
            'entity_id' => $entityId,
            'total_cash' => $totalCashBalance->format(),
            'as_of_date' => $date->format('Y-m-d'),
        ]);

        return $position;
    }

    public function getNetCashPosition(
        string $tenantId,
        ?string $entityId = null,
        ?DateTimeImmutable $asOfDate = null
    ): Money {
        $position = $this->calculatePosition($tenantId, $entityId, $asOfDate);
        return $position->getNetPosition();
    }

    public function hasSufficientLiquidity(
        string $tenantId,
        Money $amount,
        ?string $entityId = null
    ): bool {
        $position = $this->calculatePosition($tenantId, $entityId);
        return $position->hasSufficientLiquidity($amount);
    }

    public function getLiquidityGap(
        string $tenantId,
        Money $requiredAmount,
        ?string $entityId = null
    ): Money {
        $position = $this->calculatePosition($tenantId, $entityId);
        $available = $position->getAvailableCashBalance();

        if ($requiredAmount->getCurrency() !== $available->getCurrency()) {
            throw new \InvalidArgumentException(sprintf(
                'Currency mismatch in getLiquidityGap: required currency %s does not match available currency %s',
                $requiredAmount->getCurrency(),
                $available->getCurrency()
            ));
        }

        if ($available->greaterThanOrEqual($requiredAmount)) {
            return Money::of(0, $requiredAmount->getCurrency());
        }

        return $requiredAmount->subtract($available);
    }

    public function getDaysCashOnHand(
        string $tenantId,
        ?string $entityId = null,
        ?DateTimeImmutable $asOfDate = null
    ): float {
        $position = $this->calculatePosition($tenantId, $entityId, $asOfDate);

        $averageDailyOutflow = $this->estimateAverageDailyOutflow($tenantId, $entityId);
        if ($averageDailyOutflow <= 0) {
            return PHP_FLOAT_MAX;
        }

        $availableCash = $position->getAvailableCashBalance()->getAmount();
        return $availableCash / $averageDailyOutflow;
    }

    public function compareToMinimumBalance(
        string $tenantId,
        ?string $entityId = null
    ): array {
        $policy = $this->policyQuery->findEffectiveForDate($tenantId, new DateTimeImmutable());
        $position = $this->calculatePosition($tenantId, $entityId);

        $minimumBalance = $policy?->getMinimumCashBalance() ?? Money::of(0, $position->getCurrency());
        $availableBalance = $position->getAvailableCashBalance();
        $shortfall = Money::of(0, $position->getCurrency());
        $surplus = Money::of(0, $position->getCurrency());

        if ($availableBalance->lessThan($minimumBalance)) {
            $shortfall = $minimumBalance->subtract($availableBalance);
        } else {
            $surplus = $availableBalance->subtract($minimumBalance);
        }

        return [
            'minimum_balance' => $minimumBalance,
            'available_balance' => $availableBalance,
            'shortfall' => $shortfall,
            'surplus' => $surplus,
            'is_compliant' => $shortfall->isZero(),
        ];
    }

    public function getInvestedCashBreakdown(string $tenantId, string $currency): array
    {
        $activeInvestments = $this->investmentQuery->findActiveByTenantId($tenantId);

        $breakdown = [];
        foreach ($activeInvestments as $investment) {
            if ($investment->getPrincipalAmount()->getCurrency() === $currency) {
                $breakdown[] = [
                    'investment_id' => $investment->getId(),
                    'name' => $investment->getName(),
                    'type' => $investment->getInvestmentType()->value,
                    'principal' => $investment->getPrincipalAmount()->getAmount(),
                    'accrued_interest' => $investment->getAccruedInterest()->getAmount(),
                    'days_to_maturity' => $investment->getDaysToMaturity(),
                ];
            }
        }

        return $breakdown;
    }

    private function calculateTotalCashBalance(string $tenantId, ?string $entityId, string $currency): Money
    {
        $total = Money::of(0, $currency);

        if ($this->cashManagementProvider === null) {
            $pools = $this->liquidityPoolQuery->findActiveByTenantId($tenantId);
            foreach ($pools as $pool) {
                if ($pool->getCurrency() === $currency) {
                    $total = $total->add($pool->getTotalBalance());
                }
            }
            return $total;
        }

        $accountIds = $this->cashManagementProvider->getBankAccountIdsByTenant($tenantId);

        foreach ($accountIds as $accountId) {
            $balance = $this->cashManagementProvider->getCurrentBalance($accountId);
            $accountCurrency = $this->cashManagementProvider->getCurrency($accountId);

            if ($accountCurrency === $currency) {
                $total = $total->add(Money::of($balance, $currency));
            }
        }

        return $total;
    }

    private function calculateAvailableCashBalance(string $tenantId, ?string $entityId, string $currency): Money
    {
        $available = Money::of(0, $currency);

        $pools = $this->liquidityPoolQuery->findActiveByTenantId($tenantId);
        foreach ($pools as $pool) {
            if ($pool->getCurrency() === $currency) {
                $available = $available->add($pool->getAvailableBalance());
            }
        }

        return $available;
    }

    private function calculateInvestedCashBalance(string $tenantId, string $currency): Money
    {
        $invested = Money::of(0, $currency);

        $activeInvestments = $this->investmentQuery->findActiveByTenantId($tenantId);
        foreach ($activeInvestments as $investment) {
            if ($investment->getPrincipalAmount()->getCurrency() === $currency) {
                $invested = $invested->add($investment->getPrincipalAmount());
            }
        }

        return $invested;
    }

    private function calculateProjectedInflows(string $tenantId, ?string $entityId, string $currency): Money
    {
        if ($this->receivableProvider === null) {
            return Money::of(0, $currency);
        }

        $totalReceivables = $this->receivableProvider->getTotalReceivables(
            $tenantId,
            (new DateTimeImmutable())->format('Y-m-d')
        );

        $collectionPeriod = $this->receivableProvider->getAverageCollectionPeriod($tenantId);
        if ($collectionPeriod <= 0) {
            $collectionPeriod = 30;
        }

        $dailyInflow = $totalReceivables / $collectionPeriod;
        $monthlyProjected = $dailyInflow * 30;

        return Money::of($monthlyProjected, $currency);
    }

    private function calculateProjectedOutflows(string $tenantId, ?string $entityId, string $currency): Money
    {
        if ($this->payableProvider === null) {
            return Money::of(0, $currency);
        }

        $totalPayables = $this->payableProvider->getTotalPayables(
            $tenantId,
            (new DateTimeImmutable())->format('Y-m-d')
        );

        $paymentPeriod = $this->payableProvider->getAveragePaymentPeriod($tenantId);
        if ($paymentPeriod <= 0) {
            $paymentPeriod = 30;
        }

        $dailyOutflow = $totalPayables / $paymentPeriod;
        $monthlyProjected = $dailyOutflow * 30;

        return Money::of($monthlyProjected, $currency);
    }

    private function estimateAverageDailyOutflow(string $tenantId, ?string $entityId): float
    {
        if ($this->payableProvider === null) {
            return 0.0;
        }

        $totalPayables = $this->payableProvider->getTotalPayables(
            $tenantId,
            (new DateTimeImmutable())->format('Y-m-d')
        );

        $paymentPeriod = $this->payableProvider->getAveragePaymentPeriod($tenantId);
        if ($paymentPeriod <= 0) {
            return 0.0;
        }

        return $totalPayables / $paymentPeriod;
    }

    private function getDefaultCurrency(string $tenantId): string
    {
        $policy = $this->policyQuery->findEffectiveForDate(
            $tenantId,
            new DateTimeImmutable()
        );

        if ($policy !== null) {
            return $policy->getMinimumCashBalance()->getCurrency();
        }

        return $_ENV['DEFAULT_CURRENCY'] ?? 'USD';
    }
}
